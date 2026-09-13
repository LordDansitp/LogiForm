<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::iniciarSesion();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::iniciar($usuario, $password)) {
        header('Location: admin.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso administrador — Canal Ético LOGI</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --azul: #165DA9;
    --azul-claro: #3688C9;
    --marino: #174969;
    --texto-claro: #EAF1F8;
    --texto-muted: #6B8299;
    --rojo: #E4572E;
  }
  *{box-sizing:border-box;}
  body{
    margin:0; min-height:100vh; background:var(--marino);
    font-family:'Poppins',sans-serif; color:var(--texto-claro);
    display:flex; align-items:center; justify-content:center; padding:24px;
  }
  .marca{
    display:flex; align-items:center; gap:12px;
    position:relative; margin-bottom:-1px; z-index:2;
    padding:0 8px;
  }
  .marca img{height:34px; width:auto;}
  .marca span{font-size:17px; font-weight:600; letter-spacing:.02em;}

  .contenedor{width:100%; max-width:400px;}

  .tarjeta{
    background:#fff; color:#12324A;
    padding:52px 40px 40px;
    clip-path: polygon(0 9%, 100% 0, 100% 100%, 0% 100%);
    box-shadow: 0 20px 50px rgba(0,0,0,.25);
  }
  .tarjeta h1{font-size:20px; font-weight:600; margin:0 0 24px;}
  label{
    display:block; font-size:12px; font-weight:600; letter-spacing:.04em;
    text-transform:uppercase; color:#4A6178; margin-bottom:6px;
  }
  .campo{margin-bottom:18px;}
  input{
    width:100%; padding:11px 14px; border-radius:6px;
    border:1px solid #D6E0E8; font-size:14px; font-family:inherit;
    background:#F7FAFC; color:#12324A;
  }
  input:focus{outline:2px solid var(--azul-claro); border-color:transparent;}
  button{
    width:100%; margin-top:8px; padding:13px; border-radius:6px; border:none;
    background:var(--azul); color:#fff; font-family:inherit; font-size:14px;
    font-weight:600; cursor:pointer; transition:background .15s ease;
  }
  button:hover{background:#0F4A87;}
  .error{
    background:#FDEDEA; color:#B3361C; font-size:13px;
    padding:10px 12px; border-radius:6px; margin-bottom:16px;
  }
</style>
</head>
<body>
<div class="contenedor">
  <div class="marca">
    <img src="assets/logo-logi-blanco.png" alt="LOGI">
    <span>Canal Ético LOGI</span>
  </div>
  <div class="tarjeta">
    <h1>Acceso administrador</h1>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <div class="campo">
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required autofocus autocomplete="username">
      </div>
      <div class="campo">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit">Entrar</button>
    </form>
  </div>
</div>
</body>
</html>
