<?php
$lider = $historial[0] ?? null;
$esPropietario = isset($_SESSION['user']['id'], $subasta['creado_por']) && ((int)$_SESSION['user']['id'] === (int)$subasta['creado_por']);

$esCerrada = in_array(strtolower($subasta['estado'] ?? ''), ['cerrada', 'finalizada']) || strtotime($subasta['fecha_fin']) <= time();
$estadoReal = $esCerrada ? 'cerrada' : (empty($subasta['estado']) ? 'activa' : strtolower($subasta['estado']));

$fechaFinFormateada = date('d/m/Y h:i A', strtotime($subasta['fecha_fin']));
$fechaInicioFormateada = date('d/m/Y h:i A', strtotime($subasta['fecha_inicio']));

// Extraer el arreglo de imágenes
$imagenes = json_decode($subasta['imagen'] ?? '[]', true);
if (!is_array($imagenes)) $imagenes = [];

$minimoPuja = (float)$subasta['monto_actual'] + (float)$subasta['incremento_minimo'];
$esGanador = $esCerrada && $lider && (int)($lider['usuario_id'] ?? 0) === (int)$_SESSION['user']['id'];
$tieneMapa = is_numeric($subasta['ubicacion_lat'] ?? null) && is_numeric($subasta['ubicacion_lng'] ?? null);
?>
<a class="back-link" href="index.php?page=catalogo">
  <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
  Volver a subastas
</a>
<div class="title-row">
  <h1><?=e($subasta['articulo'])?></h1>
  <span class="badge <?=e($estadoReal)?>"><?=e($estadoReal)?></span>
</div>
<p><?=e($subasta['categoria'])?></p>

<div class="detail-page">

  <!-- ===== Columna izquierda: imágenes y datos del artículo ===== -->
  <div class="stack">
    <section class="panel">
      <?php if(!empty($imagenes)): ?>
        <img id="galeria-principal" class="gallery-main zoomable" src="uploads/<?=e($imagenes[0])?>" alt="Imagen de <?=e($subasta['articulo'])?>" tabindex="0" role="button" title="Clic para ampliar">
        <?php if(count($imagenes) > 1): ?>
          <div class="gallery-thumbs">
            <?php foreach($imagenes as $i => $img): ?>
              <img class="<?= $i === 0 ? 'on' : '' ?>" src="uploads/<?=e($img)?>" alt="Miniatura <?=$i + 1?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="no-image">Este artículo no tiene imágenes</div>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Detalles del artículo</h2>
      <dl class="facts">
        <dt>Categoría</dt><dd><?=e($subasta['categoria'])?></dd>
        <dt>Modelo</dt><dd><?=e($subasta['modelo'] ?: 'N/A')?></dd>
        <dt>Descripción</dt><dd><?=e($subasta['descripcion'])?></dd>
      </dl>
    </section>

    <?php if(!empty($subasta['ubicacion_nombre'])): ?>
    <section class="panel">
      <h2>Punto de entrega</h2>
      <p style="margin:0 0 4px"><?=e($subasta['ubicacion_nombre'])?></p>
      <?php if($tieneMapa): ?>
        <a class="button sm ghost" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&amp;query=<?=rawurlencode($subasta['ubicacion_lat'].','.$subasta['ubicacion_lng'])?>">Abrir en Google Maps</a>
        <iframe loading="lazy" title="Mapa del punto de entrega" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=<?=rawurlencode($subasta['ubicacion_lat'].','.$subasta['ubicacion_lng'])?>&amp;z=14&amp;output=embed" class="map-frame"></iframe>
      <?php endif; ?>
    </section>
    <?php endif; ?>
  </div>

  <!-- ===== Columna derecha: puja e historial ===== -->
  <div class="stack">
    <section class="panel">
      <div class="price-label"><?= $estadoReal === 'cerrada' ? 'Precio final' : 'Oferta actual' ?></div>
      <div class="price-big">Q <?=number_format((float)$subasta['monto_actual'], 2)?></div>

      <div class="bid-meta"><span>Incremento mínimo</span><b>Q <?=number_format((float)$subasta['incremento_minimo'], 2)?></b></div>
      <div class="bid-meta"><span>Cierra</span><b><?=e($fechaFinFormateada)?></b></div>
      <?php if($estadoReal !== 'cerrada'): ?>
        <div class="bid-meta"><span>Tiempo restante</span><span class="countdown" id="contador-subasta" data-fin="<?=e(str_replace(' ', 'T', $subasta['fecha_fin']))?>">Calculando...</span></div>
      <?php endif; ?>

      <?php if($estadoReal === 'activa' && $lider): ?>
        <div class="notice info"><strong>Va ganando:</strong> <?=e($lider['usuario'])?> con Q <?=number_format((float)$lider['monto'], 2)?></div>
      <?php endif; ?>

      <?php if($estadoReal === 'activa'): ?>
        <?php if($esPropietario): ?>
          <div class="notice neutral">Registraste este artículo, así que no puedes pujar en tu propia subasta.</div>
        <?php else: ?>
          <form class="bid-form" method="post" action="index.php?action=pujar">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <input type="hidden" name="subasta_id" value="<?=(int)$subasta['id']?>">
            <label for="monto-visible">Tu puja</label>
            <div class="money-input">
              <span>Q</span>
              <!-- El campo visible muestra el monto con comas; el campo oculto "monto" envía el número limpio (ej. 1000.00) -->
              <input id="monto-visible" type="text" inputmode="decimal" autocomplete="off" data-minimo="<?=number_format($minimoPuja, 2, '.', '')?>" value="<?=number_format($minimoPuja, 2, '.', ',')?>" required>
              <input id="monto" type="hidden" name="monto" value="<?=number_format($minimoPuja, 2, '.', '')?>">
            </div>
            <p class="hint">La puja mínima es Q <?=number_format($minimoPuja, 2)?>.</p>
            <button type="submit" class="gold block">Realizar puja</button>
          </form>
        <?php endif; ?>
      <?php elseif($estadoReal === 'programada'): ?>
        <div class="notice warn">Esta subasta aún no inicia. Podrás pujar desde el <?=e($fechaInicioFormateada)?>.</div>
      <?php else: ?>
        <div class="notice danger">
          <strong>Esta subasta ya finalizó.</strong>
          <p>
            <?php if($lider): ?>
              Se adjudicó a <strong><?=e($lider['usuario'])?></strong> por Q <?=number_format((float)$lider['monto'], 2)?>.
            <?php else: ?>
              Cerró sin recibir ofertas.
            <?php endif; ?>
          </p>
        </div>
      <?php endif; ?>

      <?php if($esGanador): ?>
        <?php $detallePago = (new PagoDemo($db))->detalle((int)$subasta['id'], (int)$_SESSION['user']['id']); ?>
        <?php if ($detallePago && (($detallePago['pago']['estado'] ?? '') === 'pagado')): ?>
          <div class="notice success">
            <strong>Compra registrada con tarjeta ficticia.</strong>
            <p>Referencia: <?=e($detallePago['pago']['referencia_demo'] ?? '')?></p>
            <a class="button sm" href="index.php?page=pago&amp;id=<?=(int)$subasta['id']?>">Ver o imprimir comprobante</a>
          </div>
        <?php else: ?>
          <div class="notice info">
            <strong>Ganaste esta subasta.</strong>
            <p>Completa tu compra con una tarjeta de demostración. No se hace ningún cargo real.</p>
            <a class="button sm gold" href="index.php?page=pago&amp;id=<?=(int)$subasta['id']?>">Pagar con tarjeta ficticia</a>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if($estadoReal === 'cerrada' && (($_SESSION['user']['rol'] === 'Administrador') || $esPropietario)): ?>
        <form method="post" action="index.php?action=eliminar_subasta" onsubmit="return confirm('¿Seguro que deseas eliminar esta subasta cerrada?');">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="subasta_id" value="<?=(int)$subasta['id']?>">
          <button type="submit" class="danger block">Eliminar subasta</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Historial de pujas</h2>
        <?php if(!empty($historial)): ?><span class="muted"><?=count($historial)?> en total</span><?php endif; ?>
      </div>
      <?php if(empty($historial)): ?>
        <div class="empty"><p><?= $estadoReal === 'cerrada' ? 'Esta subasta no recibió pujas.' : 'Todavía no hay pujas. Sé el primero en ofertar.' ?></p></div>
      <?php else: ?>
        <ul class="bids">
          <?php foreach($historial as $index => $h): ?>
            <li class="bid-row<?= $index === 0 ? ' lead' : '' ?>">
              <span class="bid-pos"><?=$index + 1?></span>
              <span class="bid-who">
                <b><?=e($h['usuario'])?><?php if($index === 0): ?><span class="lead-tag">Líder</span><?php endif; ?></b>
                <small><?=e(date('d/m/Y h:i:s A', strtotime($h['fecha'])))?></small>
              </span>
              <span class="bid-amount">Q <?=number_format((float)$h['monto'], 2)?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>

<script>
// Monto de la puja con comas de millares mientras se escribe (1000 -> 1,000).
// Al servidor llega solo el número limpio, a través del campo oculto "monto".
(function() {
  const visible = document.getElementById('monto-visible');
  const real = document.getElementById('monto');
  if (!visible || !real) return;
  const minimo = parseFloat(visible.dataset.minimo) || 0.01;
  const enComas = n => n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

  function formatear() {
    const pos = visible.selectionStart;
    let antes = visible.value.slice(0, pos).replace(/[^0-9.]/g, '').length;

    const limpio = visible.value.replace(/[^0-9.]/g, '');
    const i = limpio.indexOf('.');
    let entero = (i === -1 ? limpio : limpio.slice(0, i)).replace(/^0+(?=\d)/, '').slice(0, 10);
    const decimal = i === -1 ? null : limpio.slice(i + 1).replace(/\./g, '').slice(0, 2);
    if (entero === '' && decimal !== null) { entero = '0'; antes += 1; }

    const texto = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (decimal !== null ? '.' + decimal : '');
    visible.value = texto;

    if (document.activeElement === visible) {
      // Mantener el cursor junto al mismo dígito después de insertar las comas
      let nueva = texto.length;
      if (antes === 0) {
        nueva = 0;
      } else {
        let cuenta = 0;
        for (let k = 0; k < texto.length; k++) {
          if (/[0-9.]/.test(texto[k])) cuenta++;
          if (cuenta === antes) { nueva = k + 1; break; }
        }
      }
      visible.setSelectionRange(nueva, nueva);
    }

    real.value = entero + (decimal !== null ? '.' + decimal : '');
    const n = parseFloat(real.value);
    visible.setCustomValidity(real.value !== '' && !(n >= minimo - 0.001) ? 'La puja mínima es Q ' + enComas(minimo) + '.' : '');
  }

  visible.addEventListener('input', formatear);
  visible.addEventListener('blur', function() {
    // Al salir del campo se muestran siempre dos decimales (1,000 -> 1,000.00)
    const n = parseFloat(real.value);
    if (isNaN(n)) return;
    visible.value = enComas(n);
    real.value = n.toFixed(2);
  });
  formatear();
})();
</script>

<script>
// Galería: al elegir una miniatura se muestra en grande
(function() {
  const principal = document.getElementById('galeria-principal');
  const miniaturas = document.querySelectorAll('.gallery-thumbs img');
  if (!principal || !miniaturas.length) return;
  miniaturas.forEach(function(m) {
    m.addEventListener('click', function() {
      principal.src = m.src;
      miniaturas.forEach(function(x) { x.classList.remove('on'); });
      m.classList.add('on');
    });
  });
})();
</script>

<?php if($estadoReal !== 'cerrada'): ?>
<script>
(function() {
  const el = document.getElementById('contador-subasta');
  if (!el) return;
  const fechaFin = new Date(el.getAttribute('data-fin')).getTime();
  let recargando = false;

  const actualizarContador = function() {
    const ahora = new Date().getTime();
    const diferencia = fechaFin - ahora;

    if (diferencia <= 0) {
      el.textContent = 'Tiempo agotado. Actualizando...';
      if (!recargando) {
        recargando = true;
        setTimeout(function() { window.location.reload(); }, 1500);
      }
      return false;
    }
    el.classList.toggle('is-urgent', diferencia < 3600000);

    const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
    const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
    const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

    let texto = '';
    if (dias > 0) texto += dias + 'd ';
    texto += String(horas).padStart(2, '0') + 'h ' + String(minutos).padStart(2, '0') + 'm ' + String(segundos).padStart(2, '0') + 's';
    el.textContent = texto;
    return true;
  };

  if (actualizarContador()) {
    const intervalo = setInterval(function() {
      if (!actualizarContador()) clearInterval(intervalo);
    }, 1000);
  }
})();
</script>
<?php endif; ?>