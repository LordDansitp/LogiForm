<?php

function cargarEnv(string $ruta): void
{
    if (!file_exists($ruta)) {
        throw new RuntimeException("No se encontró el archivo .env en {$ruta}. Copia .env.example y complétalo con tus datos.");
    }

    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
        $_ENV[$clave] = $valor;
        putenv("{$clave}={$valor}");
    }
}

cargarEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../providers/Database.php';
