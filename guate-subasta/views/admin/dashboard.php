<?php
$rolActual = $_SESSION['user']['rol'] ?? 'Cliente';
$nombreUsuario = $_SESSION['user']['nombre'] ?? 'Usuario';

$resumenEstados = ['activa' => 0, 'programada' => 0, 'cerrada' => 0];
$proximasSubastas = [];

if (isset($db)) {
    $qEst = $db->query("SELECT estado, COUNT(*) AS total FROM subastas GROUP BY estado")->fetchAll();
    foreach ($qEst as $fila) {
        $est = $fila['estado'] ?? '';
        if (isset($resumenEstados[$est])) {
            $resumenEstados[$est] = (int)$fila['total'];
        }
    }

    $proximasSubastas = $db->query("
        SELECT s.id, s.monto_actual, s.fecha_fin, s.estado, a.nombre AS articulo, a.categoria,
               (SELECT COUNT(*) FROM pujas p WHERE p.subasta_id = s.id) AS total_pujas
        FROM subastas s
        JOIN articulos a ON a.id = s.articulo_id
        WHERE s.estado IN ('activa', 'programada')
        ORDER BY FIELD(s.estado, 'activa', 'programada'), s.fecha_fin ASC
        LIMIT 5
    ")->fetchAll();
}
?>
<h1>Panel principal</h1>
<p>Bienvenido, <strong><?=e($nombreUsuario)?></strong> (Rol: <?=e($rolActual)?>). Monitoreo general de usuarios, artículos, subastas, pujas y auditoría.</p>

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
    <b><?=(int)$stats['subastas']?></b>
    <small style="display:block; margin-top:4px; opacity:0.8;">
      Activas: <?=$resumenEstados['activa']?> | Programadas: <?=$resumenEstados['programada']?> | Cerradas: <?=$resumenEstados['cerrada']?>
    </small>
  </div>
  <div>
    <small>Pujas realizadas</small>
    <b><?=(int)$stats['pujas']?></b>
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
            <td><?=e($ps['fecha_fin'])?></td>
            <td>
              <a class="button" href="index.php?page=detalle&id=<?=(int)$ps['id']?>" style="padding:5px 10px; font-size:0.85em;">Abrir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>
</div>