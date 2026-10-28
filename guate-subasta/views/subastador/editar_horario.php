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

$estadoHorario = $activaHorario ? 'activa' : ($cerradaHorario ? 'cerrada' : 'programada');
if (!isset($ic, $icons)) { $ic = fn($d) => ''; $icons = []; }
?>
<a class="back-link" href="index.php?page=catalogo">
  <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
  Volver a subastas
</a>
<h1>Modificar horario</h1>
<p>Ajusta cuándo empieza o termina tu subasta.</p>

<div class="program-grid">
  <section class="panel">
    <div class="title-row" style="margin-bottom:12px">
      <h2 style="margin:0"><?=e($subastaAgenda['articulo'])?></h2>
      <span class="badge <?=e($estadoHorario)?>"><?=e($estadoHorario)?></span>
    </div>
    <dl class="facts">
      <dt>Subasta</dt><dd>#<?=(int)$subastaAgenda['id']?></dd>
      <dt>Inicia</dt><dd><?=e($inicioHorario->format('d/m/Y h:i A'))?></dd>
      <dt>Termina</dt><dd><?=e($finHorario->format('d/m/Y h:i A'))?></dd>
    </dl>

    <?php if ($puedeEditarHorario): ?>
    <form method="post" action="index.php?action=editar_horario" class="form-section">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="subasta_id" value="<?=(int)$subastaAgenda['id']?>">
      <?php if ($activaHorario): ?>
        <label for="fecha_hora" style="margin-top:0">Nueva hora de cierre</label>
        <p class="hint" style="margin-top:0">Con la subasta en curso solo puedes adelantar el cierre. No se puede cambiar el inicio ni ampliar el plazo, y deben quedar al menos 2 minutos para los participantes.</p>
        <input type="datetime-local" id="fecha_hora" name="fecha_hora" required
          min="<?=e($ahoraHorario->modify('+3 minutes')->format('Y-m-d\TH:i'))?>"
          max="<?=e($finHorario->modify('-1 minute')->format('Y-m-d\TH:i'))?>"
          value="<?=e($finHorario->modify('-2 minutes')->format('Y-m-d\TH:i'))?>">
      <?php else: ?>
        <div class="form-row">
          <div>
            <label for="fecha_hora" style="margin-top:0">Nuevo inicio</label>
            <input type="datetime-local" id="fecha_hora" name="fecha_hora" required
               min="<?=e($ahoraHorario->modify('+3 minutes')->format('Y-m-d\TH:i'))?>"
               value="<?=e($inicioHorario->format('Y-m-d\TH:i'))?>">
          </div>
          <div>
            <label for="duracion_minutos" style="margin-top:0">Duración (minutos)</label>
            <input type="number" id="duracion_minutos" name="duracion_minutos" min="1" max="30" step="1" required
              value="<?=max(1,min(30,$duracionActual))?>">
          </div>
        </div>
        <p id="vistaCierre" class="notice info" aria-live="polite"></p>
      <?php endif; ?>
      <button type="submit" class="gold block" onclick="return confirm('¿Guardar el nuevo horario y avisar a los participantes?')">Guardar nuevo horario</button>
    </form>
    <?php else: ?>
      <div class="notice danger">Esta subasta ya no admite cambios de horario.</div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Qué debes saber</h2>
    <ul class="tips">
      <li><?=$ic($icons['calendar'] ?? '')?><span>La duración máxima de una subasta es de <strong>30 minutos</strong>.</span></li>
      <li><?=$ic($icons['bell'] ?? '')?><span>Cada cambio avisa a los demás usuarios en sus notificaciones.</span></li>
      <li><?=$ic($icons['shield'] ?? '')?><span>Todos los cambios quedan registrados en la bitácora de auditoría.</span></li>
    </ul>
  </section>
</div>

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
       texto.textContent='Elige una duración válida de 1 a 30 minutos.'; return;
   }
   d.setMinutes(d.getMinutes()+n);
   texto.textContent='El nuevo cierre será: '+d.toLocaleString('es-GT',{dateStyle:'medium',timeStyle:'short'});
 }
 inicio.addEventListener('change',mostrar);
 duracion.addEventListener('input',mostrar);
 mostrar();
})();
</script>