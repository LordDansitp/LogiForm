<?php

require_once __DIR__ . '/bootstrap/app.php';

try {
    $pdo = Database::conectar();
    $stmt = $pdo->query("SELECT nombre FROM tipos_caso ORDER BY id");
    $tipos = $stmt->fetchAll();

    echo "Conexion exitosa. Tipos de caso encontrados:\n";
    foreach ($tipos as $tipo) {
        echo "- {$tipo['nombre']}\n";
    }
} catch (Throwable $e) {
    echo "Error de conexion: " . $e->getMessage() . "\n";
    echo "Si dice 'relation \"tipos_caso\" does not exist', todavia falta correr el script de la Fase 1 en el SQL Editor de Supabase.\n";
}
