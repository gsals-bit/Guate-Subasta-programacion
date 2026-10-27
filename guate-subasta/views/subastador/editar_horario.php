<?php
$zonaHorario=new DateTimeZone('America/Guatemala');
$ahoraHorario=new DateTimeImmutable('now',$zonaHorario);
$inicioHorario=new DateTimeImmutable((string)$subastaAgenda['fecha_inicio'],$zonaHorario);
$finHorario=new DateTimeImmutable((string)$subastaAgenda['fecha_fin'],$zonaHorario);
$activaHorario=$ahoraHorario >= $inicioHorario && $ahoraHorario < $finHorario;
$cerradaHorario=$ahoraHorario >= $finHorario || in_array((string)$subastaAgenda['estado'],['cerrada','finalizada','cancelada'],true);
$limiteHorario=$inicioHorario->modify('+30 minutes');
$duracionActual=(int)(($finHorario->getTimestamp()-$inicioHorario->getTimestamp())/60);
$puedeEditarHorario=!$cerradaHorario && (!$activaHorario || ($ahoraHorario < $limiteHorario && $finHorario > $ahoraHorario->modify('+2 minutes')));
?>
<h1>Modificar horario de subasta</h1>
<section class="panel narrow" style="max-width:720px">
  <h2><?=e($subastaAgenda['articulo'])?></h2>
  <p><strong>Subasta:</strong> #<?=(int)$subastaAgenda['id']?></p>
  <p><strong>Estado:</strong> <?=e($activaHorario?'activa':($cerradaHorario?'cerrada':'programada'))?></p>
  <p><strong>Inicia:</strong> <?=e($inicioHorario->format('d/m/Y H:i'))?> · <strong>Termina:</strong> <?=e($finHorario->format('d/m/Y H:i'))?></p>
  <p>La duración máxima es de <strong>30 minutos</strong>. Todos los cambios quedan en auditoría y generan un aviso para los demás usuarios.</p>
  <?php if ($puedeEditarHorario): ?>
  <form method="post" action="index.php?action=editar_horario">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="subasta_id" value="<?=(int)$subastaAgenda['id']?>">
    <?php if ($activaHorario): ?>
      <label for="fecha_hora">Adelantar la hora de cierre</label>
      <p style="font-size:.9em">Durante una subasta activa no se puede cambiar el inicio ni ampliar el plazo. Puedes adelantar el cierre dejando al menos 2 minutos para los participantes.</p>
      <input type="datetime-local" id="fecha_hora" name="fecha_hora" required
        min="<?=e($ahoraHorario->modify('+3 minutes')->format('Y-m-d\TH:i'))?>"
        max="<?=e($finHorario->modify('-1 minute')->format('Y-m-d\TH:i'))?>"
        value="<?=e($finHorario->modify('-2 minutes')->format('Y-m-d\TH:i'))?>">
    <?php else: ?>
      <label for="fecha_hora">Nueva fecha y hora de inicio</label>
      <p style="font-size:.9em">También puedes ajustar la duración entre 1 y 30 minutos antes de que empiece.</p>
      <input type="datetime-local" id="fecha_hora" name="fecha_hora" required
         min="<?=e($ahoraHorario->modify('+3 minutes')->format('Y-m-d\TH:i'))?>"
         value="<?=e($inicioHorario->format('Y-m-d\TH:i'))?>">
      <label for="duracion_minutos">Duración (minutos, máximo 30)</label>
      <input type="number" id="duracion_minutos" name="duracion_minutos" min="1" max="30" step="1" required
        value="<?=max(1,min(30,$duracionActual))?>">
      <p id="vistaCierre" style="color:#1d4ed8"></p>
    <?php endif; ?>
    <button type="submit" onclick="return confirm('¿Guardar el nuevo horario y avisar a los participantes?')">Guardar nuevo horario</button>
  </form>
  <?php else: ?>
    <p style="color:#991b1b">Esta subasta ya no admite cambios de horario.</p>
  <?php endif; ?>
  <p><a href="index.php?page=catalogo">Volver a subastas</a></p>
</section>

<script>
(function(){
 const inicio=document.getElementById('fecha_hora');
 const duracion=document.getElementById('duracion_minutos');
 const texto=document.getElementById('vistaCierre');
 if(!inicio || !duracion || !texto) return;
 function mostrar(){
   const n=Number(duracion.value);
   const d=new Date(inicio.value);
   if(!Number.isInteger(n)||n<1||n>30||isNaN(d.getTime())){
       texto.textContent='Selecciona una duración válida de 1 a 30 minutos.'; return;
   }
   d.setMinutes(d.getMinutes()+n);
   texto.textContent='El nuevo cierre será: '+d.toLocaleString('es-GT',{dateStyle:'medium',timeStyle:'short'});
 }
 inicio.addEventListener('change',mostrar);
 duracion.addEventListener('input',mostrar);
 mostrar();
})();
</script>
