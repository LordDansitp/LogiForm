<?php

// Para agregar o modificar un administrador, edita este arreglo.
// La contraseña NO va en texto plano: hay que generar su hash primero.
//
// En terminal (donde tengas PHP), por cada persona:
//   php -r "echo password_hash('la_contraseña_elegida', PASSWORD_DEFAULT), PHP_EOL;"
// Copia el resultado completo (empieza con $2y$ o $2b$) como 'password'.

return [
    ['nombre' => 'Persona 1', 'password' => '$2y$10$pegaAquiElHashGenerado'],
    ['nombre' => 'Persona 2', 'password' => '$2y$10$pegaAquiElHashGenerado'],
    ['nombre' => 'Persona 3', 'password' => '$2y$10$pegaAquiElHashGenerado'],
    ['nombre' => 'Persona 4', 'password' => '$2y$10$pegaAquiElHashGenerado'],
    ['nombre' => 'Persona 5', 'password' => '$2y$10$pegaAquiElHashGenerado'],
];
