<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::requerirAutenticacion();

const ESTADOS_VALIDOS = ['recibido', 'en revisión', 'en investigación', 'resuelto', 'cerrado'];

$casoId = filter_input(INPUT_POST, 'caso_id', FILTER_VALIDATE_INT);
$nuevoEstado = $_POST['estado'] ?? '';

if (!$casoId || !in_array($nuevoEstado, ESTADOS_VALIDOS, true)) {
    header('Location: admin.php');
    exit;
}

$pdo = Database::conectar();

$stmt = $pdo->prepare('SELECT numero_referencia, estado FROM casos WHERE id = :id');
$stmt->execute(['id' => $casoId]);
$caso = $stmt->fetch();

if ($caso) {
    $pdo->prepare('UPDATE casos SET estado = :estado WHERE id = :id')
        ->execute(['estado' => $nuevoEstado, 'id' => $casoId]);

    Auth::registrarBitacora(
        Auth::nombreActual() ?? 'desconocido',
        'cambiar_estado',
        "Caso {$caso['numero_referencia']}: {$caso['estado']} → {$nuevoEstado}"
    );
}

header('Location: admin.php');
exit;
