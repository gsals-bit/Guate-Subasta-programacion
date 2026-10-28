<?php
$sinLeer = 0;
foreach ($avisosAgenda as $avx) { if (empty($avx['leido'])) $sinLeer++; }
?>
<div class="page-head">
  <div>
    <h1>Notificaciones</h1>
    <p>Aquí aparecen las subastas nuevas y los cambios de horario.</p>
  </div>
  <?php if ($sinLeer > 0): ?>
    <form action="index.php?action=marcar_avisos" method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <button type="submit" class="ghost" style="margin-top:0">Marcar todas como leídas</button>
    </form>
  <?php endif; ?>
</div>

<div class="dash-grid">
  <section class="panel">
    <div class="panel-head">
      <h2>Avisos</h2>
      <?php if ($avisosAgenda): ?><span class="muted"><?= $sinLeer > 0 ? $sinLeer . ($sinLeer === 1 ? ' sin leer' : ' sin leer') : 'Todo al día' ?></span><?php endif; ?>
    </div>
    <?php if ($avisosAgenda): ?>
      <div class="notif-list">
      <?php foreach($avisosAgenda as $av): ?>
        <div class="notif<?= empty($av['leido']) ? ' unread' : '' ?>">
          <span class="notif-dot" title="<?= empty($av['leido']) ? 'Sin leer' : 'Leído' ?>"></span>
          <div>
            <h3><?=e($av['titulo'])?></h3>
            <p><?=e($av['mensaje'])?></p>
            <small><?=e(date('d/m/Y h:i A',strtotime($av['creado_en'])))?></small>
          </div>
          <a class="button sm ghost" href="index.php?page=detalle&amp;id=<?=(int)$av['subasta_id']?>">Ver subasta</a>
        </div>
      <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty">
        <p>No tienes avisos por ahora. Te avisaremos cuando se programe una subasta o cambie un horario.</p>
        <a class="button sm" href="index.php?page=catalogo">Ver subastas</a>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Avisos por correo</h2>
    <p class="hint" style="margin-top:0">Los avisos de esta página funcionan siempre. Si activas el correo, solo recibirás los eventos futuros y únicamente cuando el servidor tenga el servicio SMTP configurado. No se envían a la dirección ficticia de pruebas.</p>
    <form method="post" action="index.php?action=preferencias_avisos">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="correo_habilitado" value="0">
      <label class="toggle">
        <input type="checkbox" name="correo_habilitado" value="1" <?=$correoAgenda?'checked':''?>>
        <span class="toggle-ui"></span>
        <span>Recibir también correos de nuevas subastas y cambios de horario</span>
      </label>
      <button type="submit" class="sm">Guardar preferencia</button>
    </form>
  </section>
</div>