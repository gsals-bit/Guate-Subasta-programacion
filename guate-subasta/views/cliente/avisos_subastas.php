<h1>Notificaciones de subastas</h1>
<p>Cuando se programa una subasta nueva o el propietario cambia su horario, aparece un aviso aquí.</p>
<section class="panel" style="margin-bottom:18px">
 <h2>Avisos por correo (opcional)</h2>
 <p>Los avisos en esta página funcionan sin configurar SMTP. Si activas el correo, solo recibirás notificaciones sobre eventos futuros cuando el servidor tenga el servicio SMTP configurado. No se enviarán a la dirección ficticia de pruebas.</p>
 <form method="post" action="index.php?action=preferencias_avisos" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
   <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
   <input type="hidden" name="correo_habilitado" value="0">
   <label style="display:flex;align-items:center;gap:8px;margin:0">
     <input type="checkbox" name="correo_habilitado" value="1" <?=$correoAgenda?'checked':''?>>
     Recibir también correos de nuevas subastas y cambios de horario
   </label>
   <button type="submit">Guardar preferencia</button>
 </form>
</section>
<?php if ($avisosAgenda): ?>
<form action="index.php?action=marcar_avisos" method="post" style="margin-bottom:12px">
 <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
 <button type="submit">Marcar todos como leídos</button>
</form>
<div style="display:grid;gap:12px">
<?php foreach($avisosAgenda as $av): ?>
  <section class="panel" style="border-left:5px solid <?=!$av['leido']?'#2563eb':'#cbd5e1'?>">
    <h3 style="margin-top:0"><?=e($av['titulo'])?> <?=!$av['leido']?'🔵':''?></h3>
    <p><?=e($av['mensaje'])?></p>
    <p><small>Fecha del aviso: <?=e(date('d/m/Y H:i',strtotime($av['creado_en'])))?></small></p>
    <a class="button" href="index.php?page=detalle&amp;id=<?=(int)$av['subasta_id']?>">Ver subasta</a>
  </section>
<?php endforeach; ?>
</div>
<?php else: ?>
<section class="panel"><p>No hay avisos de nuevas subastas por ahora. Los eventos futuros aparecerán aquí.</p></section>
<?php endif; ?>
