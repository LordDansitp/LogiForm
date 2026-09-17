<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../providers/Auth.php';

Auth::requerirAutenticacion();

$pdo = Database::conectar();

// --- Conteos por categoría (para las donas) ---
$conteos = $pdo->query(
    'SELECT t.id, t.nombre, COUNT(c.id) AS total
     FROM tipos_caso t
     LEFT JOIN casos c ON c.tipo_caso_id = t.id
     GROUP BY t.id, t.nombre
     ORDER BY t.id'
)->fetchAll();

$totalCasos = array_sum(array_column($conteos, 'total'));
$coloresDona = ['#165DA9', '#3688C9', '#0F4A87'];
$circunferencia = 2 * M_PI * 52;

// --- Lista de casos (con filtros) ---
$estado = $_GET['estado'] ?? '';
$categoria = $_GET['categoria'] ?? '';

$sql = 'SELECT c.id, c.numero_referencia, c.es_anonimo, c.contacto_nombre, c.contacto_empresa,
               c.contacto_email, c.contacto_telefono, c.fecha_suceso, c.estado, c.descripcion,
               c.creado_en, t.nombre AS categoria,
               (SELECT COUNT(*) FROM casos_adjuntos ca WHERE ca.caso_id = c.id) AS num_adjuntos
        FROM casos c
        JOIN tipos_caso t ON t.id = c.tipo_caso_id
        WHERE 1=1';
$parametros = [];

if ($estado !== '') {
    $sql .= ' AND c.estado = :estado';
    $parametros['estado'] = $estado;
}
if ($categoria !== '') {
    $sql .= ' AND c.tipo_caso_id = :categoria';
    $parametros['categoria'] = $categoria;
}
$sql .= ' ORDER BY c.creado_en DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$casos = $stmt->fetchAll();

$tipos = $pdo->query('SELECT id, nombre FROM tipos_caso ORDER BY id')->fetchAll();
$estadosPosibles = ['recibido', 'en revisión', 'en investigación', 'resuelto', 'cerrado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canal Ético — Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --azul: #165DA9;
    --azul-claro: #3688C9;
    --fondo: #F3F6FA;
    --texto: #12324A;
    --texto-muted: #64809A;
    --borde: #E3EAF1;
    --rojo: #E4572E;
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
  .cuenta a:hover{color:var(--azul);}

  main{padding:32px;}
  h1{font-size:20px; font-weight:600; margin:0 0 20px;}

  .donas{display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:32px;}
  .dona-card{
    background:#fff; border:1px solid var(--borde); border-radius:12px; padding:22px;
    display:flex; align-items:center; gap:18px;
  }
  .dona-card svg{width:88px; height:88px; flex-shrink:0;}
  .dona-anillo{transition: stroke-dashoffset 1.2s ease;}
  .dona-info .numero{font-size:26px; font-weight:600; line-height:1;}
  .dona-info .nombre{font-size:13px; color:var(--texto-muted); margin-top:4px;}

  .tarjeta{background:#fff; border:1px solid var(--borde); border-radius:12px; overflow:hidden;}
  .filtros{display:flex; gap:12px; padding:18px 22px; border-bottom:1px solid var(--borde); flex-wrap:wrap;}
  select{
    background:#fff; color:var(--texto); border:1px solid var(--borde); border-radius:6px;
    padding:8px 12px; font-family:inherit; font-size:13px;
  }

  .fila-caso{border-bottom:1px solid var(--borde);}
  .fila-caso:last-child{border-bottom:none;}
  .resumen{
    display:grid; grid-template-columns: 1.1fr 1.3fr 1fr .9fr 1fr auto; gap:16px;
    align-items:center; padding:16px 22px; cursor:pointer;
  }
  .resumen:hover{background:#FAFCFE;}
  .ref{font-weight:600; font-size:13px;}
  .muted{color:var(--texto-muted); font-size:13px;}
  .badge-cat{
    display:inline-block; background:#E8F1FA; color:var(--azul); font-size:12px;
    padding:3px 10px; border-radius:12px;
  }
  select.estado-select{
    font-size:12px; padding:5px 8px; border-radius:12px; border:1px solid var(--borde);
  }
  .btn-eliminar{
    background:none; border:1px solid #F3C9BC; color:var(--rojo); font-size:12px;
    padding:5px 10px; border-radius:6px; cursor:pointer; font-family:inherit;
  }
  .btn-eliminar:hover{background:#FDEDEA;}

  .detalle{
    display:none; padding:0 22px 20px 22px; font-size:13px; color:var(--texto);
    border-top:1px dashed var(--borde); margin-top:0;
  }
  .detalle.abierto{display:block;}
  .detalle dl{display:grid; grid-template-columns:140px 1fr; gap:8px 12px; margin:16px 0 0;}
  .detalle dt{color:var(--texto-muted); font-weight:500;}
  .detalle dd{margin:0;}
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
    <a href="admin.php" class="activo">Casos recibidos</a>
    <a href="bitacora.php">Bitácora</a>
  </div>
  <div class="cuenta">
    <span>Hola, <?= htmlspecialchars(Auth::nombreActual() ?? '') ?></span>
    <a href="logout.php">Cerrar sesión</a>
  </div>
</div>

<main>
  <h1>Casos recibidos</h1>

  <div class="donas">
    <?php foreach ($conteos as $i => $c): ?>
      <?php
        $porcentaje = $totalCasos > 0 ? ($c['total'] / $totalCasos) * 100 : 0;
        $offset = $circunferencia * (1 - $porcentaje / 100);
        $color = $coloresDona[$i % count($coloresDona)];
      ?>
      <div class="dona-card">
        <svg viewBox="0 0 120 120">
          <circle cx="60" cy="60" r="52" fill="none" stroke="#E7EEF5" stroke-width="12"/>
          <circle class="dona-anillo" cx="60" cy="60" r="52" fill="none" stroke="<?= $color ?>"
                  stroke-width="12" stroke-linecap="round"
                  stroke-dasharray="<?= $circunferencia ?>"
                  stroke-dashoffset="<?= $circunferencia ?>"
                  data-offset="<?= $offset ?>"
                  transform="rotate(-90 60 60)"/>
          <text x="60" y="66" text-anchor="middle" font-size="20" font-weight="600" fill="var(--texto)"><?= round($porcentaje) ?>%</text>
        </svg>
        <div class="dona-info">
          <div class="numero"><?= $c['total'] ?></div>
          <div class="nombre"><?= htmlspecialchars($c['nombre']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="tarjeta">
    <form class="filtros" method="GET">
      <select name="categoria" onchange="this.form.submit()">
        <option value="">Todas las categorías</option>
        <?php foreach ($tipos as $t): ?>
          <option value="<?= $t['id'] ?>" <?= (string) $categoria === (string) $t['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="estado" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        <?php foreach ($estadosPosibles as $e): ?>
          <option value="<?= htmlspecialchars($e) ?>" <?= $estado === $e ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst($e)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <?php if (empty($casos)): ?>
      <div class="vacio">No hay casos que coincidan con este filtro.</div>
    <?php else: ?>
      <?php foreach ($casos as $c): ?>
        <div class="fila-caso">
          <div class="resumen" onclick="alternarDetalle(event, 'det-<?= $c['id'] ?>')">
            <span class="ref"><?= htmlspecialchars($c['numero_referencia']) ?></span>
            <span class="badge-cat"><?= htmlspecialchars($c['categoria']) ?></span>
            <span class="muted"><?= $c['es_anonimo'] ? 'Anónimo' : htmlspecialchars($c['contacto_nombre'] ?: 'Sin nombre') ?></span>
            <span class="muted"><?= htmlspecialchars($c['fecha_suceso']) ?></span>

            <form method="POST" action="actualizar_estado.php" onclick="event.stopPropagation()">
              <input type="hidden" name="caso_id" value="<?= $c['id'] ?>">
              <select name="estado" class="estado-select" onchange="this.form.submit()">
                <?php foreach ($estadosPosibles as $e): ?>
                  <option value="<?= htmlspecialchars($e) ?>" <?= $c['estado'] === $e ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst($e)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>

            <form method="POST" action="eliminar_caso.php" onclick="event.stopPropagation()"
                  onsubmit="return confirm('¿Eliminar el caso <?= htmlspecialchars($c['numero_referencia']) ?>? No se puede deshacer.')">
              <input type="hidden" name="caso_id" value="<?= $c['id'] ?>">
              <button type="submit" class="btn-eliminar">Eliminar</button>
            </form>
          </div>
          <div class="detalle" id="det-<?= $c['id'] ?>">
            <dl>
              <dt>Descripción</dt>
              <dd><?= nl2br(htmlspecialchars($c['descripcion'])) ?></dd>

              <?php if (!$c['es_anonimo']): ?>
                <dt>Empresa</dt>
                <dd><?= htmlspecialchars($c['contacto_empresa'] ?: '—') ?></dd>
                <dt>Email</dt>
                <dd><?= htmlspecialchars($c['contacto_email'] ?: '—') ?></dd>
                <dt>Teléfono</dt>
                <dd><?= htmlspecialchars($c['contacto_telefono'] ?: '—') ?></dd>
              <?php endif; ?>

              <dt>Adjuntos</dt>
              <dd><?= (int) $c['num_adjuntos'] ?></dd>
              <dt>Recibido</dt>
              <dd><?= htmlspecialchars($c['creado_en']) ?></dd>
            </dl>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<script>
  document.querySelectorAll('.dona-anillo').forEach(function(circulo){
    const offset = circulo.dataset.offset;
    requestAnimationFrame(function(){
      setTimeout(function(){ circulo.style.strokeDashoffset = offset; }, 50);
    });
  });

  function alternarDetalle(evento, id){
    document.getElementById(id).classList.toggle('abierto');
  }
</script>
</body>
</html>
