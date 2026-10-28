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

if (!isset($ic, $icons)) { $ic = fn($d) => ''; $icons = []; }
?>
<h1>Programar subasta</h1>
<p>Elige un artículo, cuándo empieza y cuánto dura. El cierre se calcula solo.</p>

<div class="program-grid">
  <section class="panel">
  <?php if (empty($articulosDisponibles)): ?>
    <div class="empty">
      <p>No tienes artículos nuevos para programar. Los que ya tuvieron una subasta no se vuelven a ofrecer.</p>
      <a class="button sm" href="index.php?page=articulos">Registrar un artículo</a>
    </div>
  <?php else: ?>
    <form method="post" action="index.php?action=programar" id="formProgramar">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <label for="articulo_select" style="margin-top:0">Artículo</label>
      <select name="articulo_id" id="articulo_select" required>
        <option value="">Selecciona un artículo</option>
        <?php foreach ($articulosDisponibles as $a): ?>
        <option value="<?=(int)$a['id']?>" data-precio="<?=(float)$a['precio_base']?>">
          <?=e($a['nombre'])?> (Q <?=number_format((float)$a['precio_base'],2)?>)
        </option>
        <?php endforeach; ?>
      </select>

      <div class="form-row">
        <div>
          <label for="inicio">Inicio</label>
          <input id="inicio" type="datetime-local" name="inicio" required
                 min="<?=e($inicioSugerido->modify('-12 minutes')->format('Y-m-d\TH:i'))?>"
                 value="<?=e($inicioSugerido->format('Y-m-d\TH:i'))?>">
        </div>
        <div>
          <label for="duracion_minutos">Duración (minutos)</label>
          <input id="duracion_minutos" type="number" name="duracion_minutos" min="1" max="30" step="1" value="30" required>
        </div>
      </div>
      <div class="quick-mins" aria-label="Duraciones rápidas">
        <?php foreach([5, 10, 15, 30] as $m): ?>
          <button type="button" class="sm ghost" data-min="<?=$m?>"><?=$m?> min</button>
        <?php endforeach; ?>
      </div>
      <p id="textoFin" class="notice info" aria-live="polite"></p>

      <label for="incremento">Incremento mínimo por puja (Q)</label>
      <input id="incremento" name="incremento" type="number" step="0.01" min="0.01" required>
      <p class="hint">Se sugiere según el precio base del artículo; puedes cambiarlo.</p>

      <button type="submit" class="gold block">Programar y avisar a los usuarios</button>
    </form>
  <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Antes de programar</h2>
    <ul class="tips">
      <li><?=$ic($icons['calendar'] ?? '')?><span>Cada subasta dura entre <strong>1 y 30 minutos</strong>, sin decimales.</span></li>
      <li><?=$ic($icons['box'] ?? '')?><span>Un artículo que ya tuvo una subasta, aunque haya terminado, no puede volver a programarse.</span></li>
      <li><?=$ic($icons['gavel'] ?? '')?><span>Si te equivocas con el horario, cámbialo desde «Modificar horario» en el listado de subastas.</span></li>
      <li><?=$ic($icons['bell'] ?? '')?><span>Al programar, los usuarios reciben un aviso en sus notificaciones.</span></li>
    </ul>
  </section>
</div>

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
      fin.textContent='Elige un inicio y una duración válida entre 1 y 30 minutos.';
      return;
   }
   const fecha=new Date(fi.value);
   if(isNaN(fecha.getTime())){fin.textContent='La fecha de inicio no es válida.';return;}
   fecha.setMinutes(fecha.getMinutes()+minutos);
   fin.textContent='Termina automáticamente: '+fecha.toLocaleString('es-GT',{dateStyle:'medium',timeStyle:'short'});
 }
 fi?.addEventListener('change',actualizarCierre);
 dur?.addEventListener('input',actualizarCierre);
 document.querySelectorAll('[data-min]').forEach(function(b){
   b.addEventListener('click',function(){ dur.value=b.dataset.min; actualizarCierre(); });
 });
 actualizarCierre();
 if(art&&inc){art.addEventListener('change',function(){
  const precio=Number(this.selectedOptions[0]?.dataset.precio||0);
  if(!this.value){inc.value='';return;}
  inc.value=(precio>=10000?500:precio>=5000?250:precio>=1000?100:precio>=500?50:precio>=100?20:10).toFixed(2);
 });}
})();
</script>