<?php
$rolActual = $_SESSION['user']['rol'] ?? 'Cliente';
$nombreUsuario = $_SESSION['user']['nombre'] ?? 'Usuario';
$usuarioId = (int)($_SESSION['user']['id'] ?? 0);

/* $ic e $icons vienen de layouts/header.php; si no existieran, el panel funciona igual sin íconos */
if (!isset($ic, $icons)) { $ic = fn($d) => ''; $icons = []; }

$resumenEstados = ['activa' => 0, 'programada' => 0, 'cerrada' => 0];
$proximasSubastas = [];
$misPujasRecientes = [];
$totalPujasMostrar = (int)($stats['pujas'] ?? 0);

if (isset($db)) {
    // 1. Sincronizar estados según la fecha actual antes de contar
    (new Subasta($db))->actualizarEstados();

    // 2. Contar estados evaluando tanto la columna estado como la fecha límite
    $todasSubastas = $db->query("SELECT estado, fecha_fin FROM subastas")->fetchAll();
    foreach ($todasSubastas as $s) {
        $est = strtolower(trim($s['estado'] ?? ''));
        $vencida = !empty($s['fecha_fin']) && strtotime($s['fecha_fin']) <= time();

        if ($est === 'cerrada' || $est === 'finalizada' || $vencida) {
            $resumenEstados['cerrada']++;
        } elseif ($est === 'programada') {
            $resumenEstados['programada']++;
        } else {
            $resumenEstados['activa']++;
        }
    }

    // 3. Si es Cliente, contar y obtener únicamente el historial de pujas que él ha hecho
    if ($rolActual === 'Cliente' && $usuarioId > 0) {
        $qCountPujas = $db->prepare("SELECT COUNT(*) FROM pujas WHERE usuario_id = ?");
        $qCountPujas->execute([$usuarioId]);
        $totalPujasMostrar = (int)$qCountPujas->fetchColumn();

        $qMisPujas = $db->prepare("
            SELECT p.monto, p.fecha, a.nombre AS articulo, a.categoria, s.id AS subasta_id, s.monto_actual, s.estado, s.fecha_fin
            FROM pujas p
            JOIN subastas s ON s.id = p.subasta_id
            JOIN articulos a ON a.id = s.articulo_id
            WHERE p.usuario_id = ?
            ORDER BY p.fecha DESC
            LIMIT 5
        ");
        $qMisPujas->execute([$usuarioId]);
        $misPujasRecientes = $qMisPujas->fetchAll();
    }

    // 4. Subastas activas y próximas a finalizar (se agrega la imagen del artículo)
    $proximasSubastas = $db->query("
        SELECT s.id, s.monto_actual, s.fecha_fin, s.estado, a.nombre AS articulo, a.categoria, a.imagen,
               (SELECT COUNT(*) FROM pujas p WHERE p.subasta_id = s.id) AS total_pujas
        FROM subastas s
        JOIN articulos a ON a.id = s.articulo_id
        WHERE s.estado IN ('activa', 'programada') AND s.fecha_fin > NOW()
        ORDER BY FIELD(s.estado, 'activa', 'programada'), s.fecha_fin ASC
        LIMIT 5
    ")->fetchAll();
}

$totalSubastasReal = array_sum($resumenEstados);

/* Primera imagen del artículo (el campo guarda un JSON con nombres de archivo en /uploads) */
$primeraImagen = function ($raw): ?string {
    if (empty($raw)) return null;
    $lista = json_decode((string)$raw, true);
    $nombre = is_array($lista) ? ($lista[0] ?? null) : (string)$raw;
    return $nombre ? 'uploads/' . rawurlencode(basename($nombre)) : null;
};

/* Accesos rápidos según el rol: [página, título, descripción, ícono] */
$accesos = [['catalogo', 'Ver subastas', 'Explora el catálogo y puja', 'gavel']];
if ($rolActual === 'Cliente') $accesos[] = ['mis-pujas', 'Mis pujas', 'Revisa tu historial y tus pagos', 'pujas'];
if (in_array($rolActual, ['Cliente', 'Subastador', 'Administrador'], true)) {
    $accesos[] = ['articulos', 'Gestionar artículos', 'Registra y edita tus artículos', 'box'];
    $accesos[] = ['programar', 'Programar subasta', 'Elige fecha de inicio y cierre', 'calendar'];
}
if ($rolActual === 'Administrador') $accesos[] = ['usuarios', 'Usuarios y roles', 'Administra cuentas y permisos', 'users'];
if (in_array($rolActual, ['Auditor', 'Administrador'], true)) $accesos[] = ['auditoria', 'Bitácora de auditoría', 'Consulta los eventos del sistema', 'shield'];
?>
<h1>Hola, <?=e($nombreUsuario)?></h1>
<?php if($rolActual === 'Cliente'): ?>
  <p>Aquí ves cómo van las subastas del catálogo y tus pujas más recientes.</p>
<?php else: ?>
  <p>Vista general de usuarios, artículos, subastas, pujas y auditoría. Tu rol: <?=e($rolActual)?>.</p>
<?php endif; ?>

<div class="stats">
  <div>
    <small>Usuarios registrados</small>
    <b><?=(int)($stats['usuarios'] ?? 0)?></b>
  </div>
  <div>
    <small>Artículos en catálogo</small>
    <b><?=(int)($stats['articulos'] ?? 0)?></b>
  </div>
  <div>
    <small>Total de subastas</small>
    <b><?=$totalSubastasReal?></b>
    <small><?=$resumenEstados['activa']?> activas, <?=$resumenEstados['programada']?> programadas, <?=$resumenEstados['cerrada']?> cerradas</small>
  </div>
  <div>
    <small><?= $rolActual === 'Cliente' ? 'Mis pujas realizadas' : 'Pujas realizadas' ?></small>
    <b><?=$totalPujasMostrar?></b>
    <?php if($rolActual === 'Cliente'): ?><small>En tu historial personal</small><?php endif; ?>
  </div>
</div>

<div class="dash-grid">
  <section class="panel">
    <div class="panel-head">
      <h2>Subastas que cierran pronto</h2>
      <a class="link-more" href="index.php?page=catalogo">Ver todas</a>
    </div>
    <?php if(empty($proximasSubastas)): ?>
      <div class="empty">
        <p>No hay subastas activas ni programadas por ahora.</p>
        <?php if(in_array($rolActual, ['Cliente','Subastador','Administrador'], true)): ?>
          <a class="button sm" href="index.php?page=programar">Programar una subasta</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach($proximasSubastas as $ps):
        $img = $primeraImagen($ps['imagen'] ?? null);
        $estadoClase = strtolower(trim($ps['estado']));
      ?>
        <div class="lot">
          <div class="lot-tile">
            <?php if($img): ?><img src="<?=e($img)?>" alt="" loading="lazy"><?php else: ?><?=e(mb_strtoupper(mb_substr($ps['articulo'], 0, 1)))?><?php endif; ?>
          </div>
          <div class="lot-main">
            <a class="lot-name" href="index.php?page=detalle&id=<?=(int)$ps['id']?>"><?=e($ps['articulo'])?></a>
            <span class="lot-meta"><?=e($ps['categoria'])?> · <?=(int)$ps['total_pujas']?> <?=((int)$ps['total_pujas'] === 1) ? 'puja' : 'pujas'?></span>
            <span class="badge <?=e($estadoClase)?>"><?=e($ps['estado'])?></span>
          </div>
          <div class="lot-price">
            <small>Oferta actual</small>
            <b>Q <?=number_format((float)$ps['monto_actual'], 2)?></b>
          </div>
          <div class="lot-time">
            <span class="countdown" data-countdown="<?=e($ps['fecha_fin'])?>"></span>
            <small>Cierra <?=e(date('d/m/Y h:i A', strtotime($ps['fecha_fin'])))?></small>
          </div>
          <a class="button sm" href="index.php?page=detalle&id=<?=(int)$ps['id']?>">Abrir</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Accesos rápidos</h2>
    <div class="quick">
      <?php foreach($accesos as $i => $ac): ?>
        <a class="quick-link<?= $i === 0 ? ' primary' : '' ?>" href="index.php?page=<?=e($ac[0])?>">
          <span class="quick-ico"><?=$ic($icons[$ac[3]] ?? '')?></span>
          <span class="quick-text"><b><?=e($ac[1])?></b><small><?=e($ac[2])?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php if($rolActual === 'Cliente'): ?>
<section class="panel" style="margin-top:20px;">
  <div class="panel-head">
    <h2>Mis pujas recientes</h2>
    <a class="link-more" href="index.php?page=mis-pujas">Ver historial completo</a>
  </div>
  <?php if(empty($misPujasRecientes)): ?>
    <div class="empty">
      <p>Todavía no has hecho ninguna puja.</p>
      <a class="button sm" href="index.php?page=catalogo">Explorar subastas</a>
    </div>
  <?php else: ?>
    <table>
      <tr>
        <th>Artículo</th>
        <th>Tu puja</th>
        <th>Oferta actual</th>
        <th>Situación</th>
        <th>Fecha</th>
        <th></th>
      </tr>
      <?php foreach($misPujasRecientes as $mp):
        $estP = strtolower(trim($mp['estado'] ?? ''));
        $cerrada = in_array($estP, ['cerrada', 'finalizada'], true) || (!empty($mp['fecha_fin']) && strtotime($mp['fecha_fin']) <= time());
        $liderando = (float)$mp['monto'] >= (float)$mp['monto_actual'];
      ?>
        <tr>
          <td>
            <strong><?=e($mp['articulo'])?></strong><br>
            <small class="muted"><?=e($mp['categoria'])?></small>
          </td>
          <td>Q <?=number_format((float)$mp['monto'], 2)?></td>
          <td><strong>Q <?=number_format((float)$mp['monto_actual'], 2)?></strong></td>
          <td>
            <?php if($cerrada): ?><span class="badge cerrada">Cerrada</span>
            <?php elseif($liderando): ?><span class="badge">Vas ganando</span>
            <?php else: ?><span class="badge warn">Superada</span><?php endif; ?>
          </td>
          <td><?=e(date('d/m/Y h:i A', strtotime($mp['fecha'])))?></td>
          <td><a class="button sm ghost" href="index.php?page=detalle&id=<?=(int)$mp['subasta_id']?>">Ver subasta</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</section>
<?php endif; ?>