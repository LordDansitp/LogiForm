<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::cerrarSesion();

header('Location: login.php');
exit;
