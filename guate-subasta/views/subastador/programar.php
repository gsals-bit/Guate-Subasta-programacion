<?php
$zonaProgramacion = new DateTimeZone('America/Guatemala');
$inicioSugerido = (new DateTimeImmutable('now', $zonaProgramacion))->modify('+15 minutes');

// Segundo filtro: nunca ofrecer artículos que YA tengan una subasta registrada,
// incluso cuando esté cerrada/finalizada. No requiere reemplazar Articulo.php.
$propietarioProgramacion = (int)($_SESSION['user']['id'] ?? 0);
$qProgramacion = $db->prepare("
    SELECT a.id, a.nombre, a.precio_base
    FROM articulos a
    WHERE a.creado_por = ?
      AND a.archivado = 0
      AND NOT EXISTS (
          SELECT 1 FROM subastas s WHERE s.articulo_id = a.id
      )
    ORDER BY a.id DESC
");
$qProgramacion->execute([$propietarioProgramacion]);
$articulosDisponibles = $qProgramacion->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Programar subasta (máximo 30 minutos)</h1>
<section class="panel narrow" style="max-width:700px">
  <p>Selecciona un artículo, cuándo comenzará y cuánto durará. Puedes elegir desde <strong>1 hasta 30 minutos</strong>. El cierre se calcula automáticamente.</p>
  <p style="font-size:.9em;color:#475569">Un artículo que ya tenga una subasta, incluso si finalizó, no puede volver a programarse. Las subastas programadas se modifican desde «Modificar horario».</p>
  <?php if (empty($articulosDisponibles)): ?>
    <p>No hay artículos nuevos disponibles para programar. Los artículos con subastas anteriores no se muestran.</p>
    <a class="button" href="index.php?page=articulos">Registrar nuevo artículo</a>
  <?php else: ?>
  <form method="post" action="index.php?action=programar" id="formProgramar">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <label for="articulo_select">Artículo</label>
    <select name="articulo_id" id="articulo_select" required>
      <option value="">Selecciona un artículo</option>
      <?php foreach ($articulosDisponibles as $a): ?>
      <option value="<?=(int)$a['id']?>" data-precio="<?=(float)$a['precio_base']?>">
        <?=e($a['nombre'])?> (Q <?=number_format((float)$a['precio_base'],2)?>)
      </option>
      <?php endforeach; ?>
    </select>
    <label for="inicio">Fecha y hora de inicio</label>
    <input id="inicio" type="datetime-local" name="inicio" required
           min="<?=e($inicioSugerido->modify('-12 minutes')->format('Y-m-d\TH:i'))?>"
           value="<?=e($inicioSugerido->format('Y-m-d\TH:i'))?>">
    <label for="duracion_minutos">¿Cuántos minutos durará la subasta?</label>
    <input id="duracion_minutos" type="number" name="duracion_minutos" min="1" max="30" step="1" value="30" required>
    <small>Entre 1 y 30 minutos; no se admiten decimales.</small>
    <p id="textoFin" style="color:#1d4ed8" aria-live="polite"></p>
    <label for="incremento">Incremento mínimo por puja (Q)</label>
    <input id="incremento" name="incremento" type="number" step="0.01" min="0.01" required>
    <button type="submit">Programar y avisar a los usuarios</button>
  </form>
  <?php endif; ?>
</section>
<script>
(function(){
 const fi=document.getElementById('inicio');
 const dur=document.getElementById('duracion_minutos');
 const fin=document.getElementById('textoFin');
 const art=document.getElementById('articulo_select');
 const inc=document.getElementById('incremento');
 function actualizarCierre(){
   if(!fi || !dur || !fin) return;
   const minutos=Number(dur.value);
   if(!fi.value || !Number.isInteger(minutos) || minutos<1 || minutos>30){
      fin.textContent='Selecciona un inicio y una duración válida entre 1 y 30 minutos.';
      return;
   }
   const fecha=new Date(fi.value);
   if(isNaN(fecha.getTime())){fin.textContent='Fecha de inicio inválida.';return;}
   fecha.setMinutes(fecha.getMinutes()+minutos);
   fin.textContent='Termina automáticamente: '+fecha.toLocaleString('es-GT',{dateStyle:'medium',timeStyle:'short'});
 }
 fi?.addEventListener('change',actualizarCierre);
 dur?.addEventListener('input',actualizarCierre);
 actualizarCierre();
 if(art&&inc){art.addEventListener('change',function(){
  const precio=Number(this.selectedOptions[0]?.dataset.precio||0);
  if(!this.value){inc.value='';return;}
  inc.value=(precio>=10000?500:precio>=5000?250:precio>=1000?100:precio>=500?50:precio>=100?20:10).toFixed(2);
 });}
})();
</script>
