<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::requerirAutenticacion();

$casoId = filter_input(INPUT_POST, 'caso_id', FILTER_VALIDATE_INT);

if ($casoId) {
    $pdo = Database::conectar();

    $stmt = $pdo->prepare('SELECT numero_referencia FROM casos WHERE id = :id');
    $stmt->execute(['id' => $casoId]);
    $caso = $stmt->fetch();

    if ($caso) {
        // Guardamos los nombres de archivo ANTES de borrar, porque el
        // ON DELETE CASCADE en casos_adjuntos se lleva esos registros.
        $stmtAdj = $pdo->prepare('SELECT nombre_guardado FROM casos_adjuntos WHERE caso_id = :id');
        $stmtAdj->execute(['id' => $casoId]);
        $nombresGuardados = $stmtAdj->fetchAll(PDO::FETCH_COLUMN);

        $pdo->prepare('DELETE FROM casos WHERE id = :id')->execute(['id' => $casoId]);

        foreach ($nombresGuardados as $nombreGuardado) {
            $ruta = __DIR__ . '/../storage/adjuntos/' . basename($nombreGuardado);
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        Auth::registrarBitacora(
            Auth::nombreActual() ?? 'desconocido',
            'eliminar_caso',
            "Caso {$caso['numero_referencia']} eliminado"
        );
    }
}

header('Location: admin.php');
exit;
