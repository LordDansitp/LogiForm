<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::requerirAutenticacion();

$pdo = Database::conectar();
$registros = $pdo->query(
    'SELECT nombre_admin, accion, detalle, creado_en FROM bitacora_admin ORDER BY creado_en DESC LIMIT 200'
)->fetchAll();

$etiquetas = [
    'login' => 'Inicio de sesión',
    'login_fallido' => 'Intento fallido',
    'logout' => 'Cierre de sesión',
    'cambiar_estado' => 'Cambio de estado',
    'eliminar_caso' => 'Caso eliminado',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bitácora — Canal Ético</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --azul:#165DA9; --azul-claro:#3688C9; --fondo:#F3F6FA; --texto:#12324A;
    --texto-muted:#64809A; --borde:#E3EAF1;
  }
  *{box-sizing:border-box;}
  body{margin:0; font-family:'Poppins',sans-serif; background:var(--fondo); color:var(--texto);}
  .barra{
    background:#fff; border-bottom:1px solid var(--borde); padding:0 32px;
    display:flex; align-items:center; justify-content:space-between; height:64px;
  }
  .marca{display:flex; align-items:center; gap:10px;}
  .marca img{height:26px; filter:invert(1);}
  .marca span{font-weight:600; font-size:15px;}
  .nav{display:flex; gap:28px; font-size:14px;}
  .nav a{color:var(--texto-muted); text-decoration:none; font-weight:500;}
  .nav a.activo{color:var(--azul);}
  .cuenta{display:flex; align-items:center; gap:18px; font-size:13px; color:var(--texto-muted);}
  .cuenta a{color:var(--texto-muted); text-decoration:none;}
  main{padding:32px;}
  h1{font-size:20px; font-weight:600; margin:0 0 20px;}
  .tarjeta{background:#fff; border:1px solid var(--borde); border-radius:12px; overflow:hidden;}
  table{width:100%; border-collapse:collapse; font-size:13px;}
  th{
    text-align:left; padding:14px 22px; font-size:11px; text-transform:uppercase;
    letter-spacing:.05em; color:var(--texto-muted); border-bottom:1px solid var(--borde);
  }
  td{padding:12px 22px; border-bottom:1px solid var(--borde);}
  tr:last-child td{border-bottom:none;}
  .badge{
    display:inline-block; background:#E8F1FA; color:var(--azul); font-size:12px;
    padding:3px 10px; border-radius:12px;
  }
  .vacio{padding:40px; text-align:center; color:var(--texto-muted);}
</style>
</head>
<body>

<div class="barra">
  <div class="marca">
    <img src="assets/logo-logi-blanco.png" alt="LOGI">
    <span>Canal Ético LOGI</span>
  </div>
  <div class="nav">
    <a href="admin.php">Casos recibidos</a>
    <a href="bitacora.php" class="activo">Bitácora</a>
  </div>
  <div class="cuenta">
    <span>Hola, <?= htmlspecialchars(Auth::nombreActual() ?? '') ?></span>
    <a href="logout.php">Cerrar sesión</a>
  </div>
</div>

<main>
  <h1>Bitácora de administradores</h1>
  <div class="tarjeta">
    <?php if (empty($registros)): ?>
      <div class="vacio">Todavía no hay actividad registrada.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Fecha y hora</th><th>Administrador</th><th>Acción</th><th>Detalle</th></tr>
      </thead>
      <tbody>
        <?php foreach ($registros as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['creado_en']) ?></td>
          <td><?= htmlspecialchars($r['nombre_admin']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($etiquetas[$r['accion']] ?? $r['accion']) ?></span></td>
          <td><?= htmlspecialchars($r['detalle'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
