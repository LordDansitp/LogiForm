<?php

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    $fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if ($error !== null && in_array($error['type'], $fatales, true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'error' => 'Error interno: ' . $error['message'] . ' (' . basename($error['file']) . ':' . $error['line'] . ')',
        ]);
    }
});

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/EmailService.php';

header('Content-Type: application/json; charset=utf-8');

const CATEGORIAS_VALIDAS = [1, 2, 3];
const NOMBRES_CATEGORIA = [
    1 => 'Queja o Reclamo',
    2 => 'Sugerencia de Mejora',
    3 => 'Reporte de Irregularidades',
];
const EXTENSIONES_PERMITIDAS = ['pdf', 'png', 'jpg', 'jpeg'];
const MAX_ARCHIVOS = 10;
const MAX_BYTES_TOTAL = 10 * 1024 * 1024;
const CARPETA_ADJUNTOS = __DIR__ . '/../storage/adjuntos';

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

$tipoCasoId = filter_input(INPUT_POST, 'tipo_caso_id', FILTER_VALIDATE_INT);
$fechaSuceso = $_POST['fecha_suceso'] ?? '';
$descripcion = trim($_POST['descripcion'] ?? '');
$esAnonimo = ($_POST['es_anonimo'] ?? '0') === '1';
$contactoNombre = trim($_POST['contacto_nombre'] ?? '') ?: null;
$contactoEmpresa = trim($_POST['contacto_empresa'] ?? '') ?: null;
$contactoEmail = trim($_POST['contacto_email'] ?? '') ?: null;
$contactoTelefono = trim($_POST['contacto_telefono'] ?? '') ?: null;

if (!in_array($tipoCasoId, CATEGORIAS_VALIDAS, true)) {
    responder(422, ['error' => 'Selecciona una categoría válida.']);
}

$fecha = DateTime::createFromFormat('Y-m-d', $fechaSuceso);
$hoy = new DateTime('today');
if (!$fecha || $fecha > $hoy) {
    responder(422, ['error' => 'La fecha del suceso no es válida.']);
}

if ($descripcion === '' || strlen($descripcion) > 2500) {
    responder(422, ['error' => 'La descripción es obligatoria y no puede superar 2500 caracteres.']);
}

if ($contactoEmail !== null && !filter_var($contactoEmail, FILTER_VALIDATE_EMAIL)) {
    responder(422, ['error' => 'El correo indicado no es válido.']);
}

$archivos = [];
if (!empty($_FILES['adjuntos']['name'][0])) {
    $cantidad = count($_FILES['adjuntos']['name']);
    if ($cantidad > MAX_ARCHIVOS) {
        responder(422, ['error' => 'Máximo ' . MAX_ARCHIVOS . ' archivos por envío.']);
    }

    $totalBytes = 0;
    $mimePorExtension = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    for ($i = 0; $i < $cantidad; $i++) {
        if ($_FILES['adjuntos']['error'][$i] !== UPLOAD_ERR_OK) {
            responder(422, ['error' => 'Hubo un problema subiendo uno de los archivos.']);
        }

        $rutaTemp = $_FILES['adjuntos']['tmp_name'][$i];
        $nombreOriginal = $_FILES['adjuntos']['name'][$i];
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $tamano = (int) $_FILES['adjuntos']['size'][$i];

        if (!in_array($extension, EXTENSIONES_PERMITIDAS, true)) {
            responder(422, ['error' => "El archivo \"{$nombreOriginal}\" no es un tipo permitido (solo PDF, PNG, JPG)."]);
        }

        $totalBytes += $tamano;
        if ($totalBytes > MAX_BYTES_TOTAL) {
            responder(422, ['error' => 'Los adjuntos superan 10 MB en total.']);
        }

        $archivos[] = [
            'ruta_temp' => $rutaTemp,
            'nombre_original' => $nombreOriginal,
            'tipo_mime' => $mimePorExtension[$extension] ?? 'application/octet-stream',
            'tamano_bytes' => $tamano,
        ];
    }
}

$pdo = null;
try {
    $pdo = Database::conectar();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO casos
            (numero_referencia, tipo_caso_id, es_anonimo, descripcion, fecha_suceso,
             contacto_nombre, contacto_empresa, contacto_email, contacto_telefono,
             origen, ip_origen)
         VALUES
            (:numero_referencia, :tipo_caso_id, :es_anonimo, :descripcion, :fecha_suceso,
             :contacto_nombre, :contacto_empresa, :contacto_email, :contacto_telefono,
             :origen, :ip_origen)
         RETURNING id'
    );

    $stmt->execute([
        'numero_referencia' => uniqid('tmp_'),
        'tipo_caso_id' => $tipoCasoId,
        'es_anonimo' => $esAnonimo ? 'true' : 'false',
        'descripcion' => $descripcion,
        'fecha_suceso' => $fechaSuceso,
        'contacto_nombre' => $esAnonimo ? null : $contactoNombre,
        'contacto_empresa' => $esAnonimo ? null : $contactoEmpresa,
        'contacto_email' => $esAnonimo ? null : $contactoEmail,
        'contacto_telefono' => $esAnonimo ? null : $contactoTelefono,
        'origen' => 'web_cliente',
        'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $casoId = (int) $stmt->fetchColumn();
    $numeroReferencia = 'CASO-' . date('Y') . '-' . str_pad((string) $casoId, 6, '0', STR_PAD_LEFT);

    $pdo->prepare('UPDATE casos SET numero_referencia = :ref WHERE id = :id')
        ->execute(['ref' => $numeroReferencia, 'id' => $casoId]);

    if (!is_dir(CARPETA_ADJUNTOS)) {
        mkdir(CARPETA_ADJUNTOS, 0755, true);
    }

    $stmtAdjunto = $pdo->prepare(
        'INSERT INTO casos_adjuntos (caso_id, nombre_original, nombre_guardado, tipo_mime, tamano_bytes)
         VALUES (:caso_id, :nombre_original, :nombre_guardado, :tipo_mime, :tamano_bytes)'
    );

    foreach ($archivos as $archivo) {
        $extension = strtolower(pathinfo($archivo['nombre_original'], PATHINFO_EXTENSION));
        $nombreGuardado = bin2hex(random_bytes(16)) . '.' . $extension;
        $destino = CARPETA_ADJUNTOS . '/' . $nombreGuardado;

        if (!move_uploaded_file($archivo['ruta_temp'], $destino)) {
            throw new RuntimeException('No se pudo guardar uno de los adjuntos.');
        }

        $stmtAdjunto->execute([
            'caso_id' => $casoId,
            'nombre_original' => $archivo['nombre_original'],
            'nombre_guardado' => $nombreGuardado,
            'tipo_mime' => $archivo['tipo_mime'],
            'tamano_bytes' => $archivo['tamano_bytes'],
        ]);
    }

    $pdo->commit();

    try {
        EmailService::notificarNuevoCaso($numeroReferencia, NOMBRES_CATEGORIA[$tipoCasoId]);
    } catch (Throwable $errorCorreo) {
        error_log('No se pudo enviar la notificación por correo: ' . $errorCorreo->getMessage());
    }

    responder(200, ['numero_referencia' => $numeroReferencia]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error guardando caso: ' . $e->getMessage());
    responder(500, ['error' => 'Ocurrió un error guardando tu reporte. Intenta de nuevo en unos minutos.']);
}