<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::requerirAutenticacion();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Adjunto no encontrado.');
}

$pdo = Database::conectar();
$stmt = $pdo->prepare('SELECT nombre_original, nombre_guardado, tipo_mime FROM casos_adjuntos WHERE id = :id');
$stmt->execute(['id' => $id]);
$adjunto = $stmt->fetch();

if (!$adjunto) {
    http_response_code(404);
    exit('Adjunto no encontrado.');
}

$ruta = __DIR__ . '/../storage/adjuntos/' . basename($adjunto['nombre_guardado']);

if (!is_file($ruta)) {
    http_response_code(404);
    exit('El archivo ya no está disponible en el servidor.');
}

header('Content-Type: ' . $adjunto['tipo_mime']);
header('Content-Disposition: inline; filename="' . rawurlencode($adjunto['nombre_original']) . '"');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
readfile($ruta);
exit;
