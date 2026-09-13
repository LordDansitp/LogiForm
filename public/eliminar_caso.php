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
        // ON DELETE CASCADE en casos_adjuntos se encarga de esos registros.
        $pdo->prepare('DELETE FROM casos WHERE id = :id')->execute(['id' => $casoId]);

        Auth::registrarBitacora(
            Auth::nombreActual() ?? 'desconocido',
            'eliminar_caso',
            "Caso {$caso['numero_referencia']} eliminado"
        );
    }
}

header('Location: admin.php');
exit;
