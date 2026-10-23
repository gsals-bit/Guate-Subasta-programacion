<?php
$rolActual = $_SESSION['user']['rol'] ?? 'Cliente';
$nombreUsuario = $_SESSION['user']['nombre'] ?? 'Usuario';
$usuarioId = (int)($_SESSION['user']['id'] ?? 0);

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

    // 4. Subastas activas y próximas a finalizar
    $proximasSubastas = $db->query("
        SELECT s.id, s.monto_actual, s.fecha_fin, s.estado, a.nombre AS articulo, a.categoria,
               (SELECT COUNT(*) FROM pujas p WHERE p.subasta_id = s.id) AS total_pujas
        FROM subastas s
        JOIN articulos a ON a.id = s.articulo_id
        WHERE s.estado IN ('activa', 'programada') AND s.fecha_fin > NOW()
        ORDER BY FIELD(s.estado, 'activa', 'programada'), s.fecha_fin ASC
        LIMIT 5
    ")->fetchAll();
}

$totalSubastasReal = array_sum($resumenEstados);
?>
<h1>Panel principal</h1>
<?php if($rolActual === 'Cliente'): ?>
  <p>Bienvenido, <strong><?=e($nombreUsuario)?></strong> (Rol: <?=e($rolActual)?>). Resumen de las subastas del catálogo y el historial de pujas que has realizado.</p>
<?php else: ?>
  <p>Bienvenido, <strong><?=e($nombreUsuario)?></strong> (Rol: <?=e($rolActual)?>). Monitoreo general de usuarios, artículos, subastas, pujas y auditoría.</p>
<?php endif; ?>

<div class="stats">
  <div>
    <small>Usuarios registrados</small>
    <b><?=(int)$stats['usuarios']?></b>
  </div>
  <div>
    <small>Artículos en catálogo</small>
    <b><?=(int)$stats['articulos']?></b>
  </div>
  <div>
    <small>Total de subastas</small>
    <b><?=$totalSubastasReal?></b>
    <small style="display:block; margin-top:4px; opacity:0.8;">
      Activas: <?=$resumenEstados['activa']?> | Programadas: <?=$resumenEstados['programada']?> | Cerradas: <?=$resumenEstados['cerrada']?>
    </small>
  </div>
  <div>
    <small><?= $rolActual === 'Cliente' ? 'Mis pujas realizadas' : 'Pujas realizadas' ?></small>
    <b><?=$totalPujasMostrar?></b>
    <?php if($rolActual === 'Cliente'): ?>
      <small style="display:block; margin-top:4px; opacity:0.8;">En tu historial personal</small>
    <?php endif; ?>
  </div>
</div>

<div class="grid2" style="margin-top:20px;">
  <section class="panel">
    <h2>Accesos rápidos</h2>
    <p>Navega directamente a las operaciones habilitadas para tu cuenta.</p>
    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:15px;">
      <a class="button" href="index.php?page=catalogo">Ver subastas</a>
      <?php if($rolActual === 'Cliente'): ?>
        <a class="button" href="index.php?page=mis-pujas">Mis pujas</a>
      <?php endif; ?>
      <?php if(in_array($rolActual, ['Cliente', 'Subastador', 'Administrador'], true)): ?>
        <a class="button" href="index.php?page=articulos">Gestionar artículos</a>
        <a class="button" href="index.php?page=programar">Programar subasta</a>
      <?php endif; ?>
      <?php if($rolActual === 'Administrador'): ?>
        <a class="button" href="index.php?page=usuarios">Usuarios y roles</a>
      <?php endif; ?>
      <?php if(in_array($rolActual, ['Auditor', 'Administrador'], true)): ?>
        <a class="button" href="index.php?page=auditoria">Bitácora de auditoría</a>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <h2>Subastas activas y próximas a finalizar</h2>
    <?php if(empty($proximasSubastas)): ?>
      <p>No hay subastas activas o programadas en este momento.</p>
    <?php else: ?>
      <table>
        <tr>
          <th>Artículo</th>
          <th>Estado</th>
          <th>Monto actual</th>
          <th>Pujas</th>
          <th>Finaliza</th>
          <th>Acción</th>
        </tr>
        <?php foreach($proximasSubastas as $ps): ?>
          <tr>
            <td>
              <strong><?=e($ps['articulo'])?></strong><br>
              <small style="opacity:0.75;"><?=e($ps['categoria'])?></small>
            </td>
            <td><span class="badge"><?=e($ps['estado'])?></span></td>
            <td>Q <?=number_format($ps['monto_actual'], 2)?></td>
            <td><?=(int)$ps['total_pujas']?></td>
            <td><?=e(date('d/m/Y h:i:s A', strtotime($ps['fecha_fin'])))?></td>
            <td>
              <a class="button" href="index.php?page=detalle&id=<?=(int)$ps['id']?>" style="padding:5px 10px; font-size:0.85em;">Abrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>
</div>

<?php if($rolActual === 'Cliente'): ?>
<section class="panel" style="margin-top:20px;">
  <h2>Mi historial reciente de pujas</h2>
  <?php if(empty($misPujasRecientes)): ?>
    <p>Aún no has realizado ninguna puja en el sistema.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>Artículo</th>
        <th>Tu Puja</th>
        <th>Precio Actual</th>
        <th>Fecha</th>
        <th>Acción</th>
      </tr>
      <?php foreach($misPujasRecientes as $mp): ?>
        <tr>
          <td>
            <strong><?=e($mp['articulo'])?></strong><br>
            <small style="opacity:0.75;"><?=e($mp['categoria'])?></small>
          </td>
          <td>Q <?=number_format($mp['monto'], 2)?></td>
          <td><strong>Q <?=number_format($mp['monto_actual'], 2)?></strong></td>
          <td><?=e(date('d/m/Y h:i:s A', strtotime($mp['fecha'])))?></td>
          <td>
            <a class="button" href="index.php?page=detalle&id=<?=(int)$mp['subasta_id']?>" style="padding:5px 10px; font-size:0.85em;">Ver subasta</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</section>
<?php endif; ?>