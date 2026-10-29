<?php
$categorias_catalogo = array_values(array_filter(array_unique(array_column($subastas, 'categoria'))));
sort($categorias_catalogo);

$rolSesion = $_SESSION['user']['rol'] ?? '';
$idSesion = (int)($_SESSION['user']['id'] ?? 0);
$puedeProgramar = in_array($rolSesion, ['Cliente', 'Subastador', 'Administrador'], true);
?>
<div class="page-head">
  <div>
    <h1>Subastas</h1>
    <p>Consulta los artículos programados, activos y finalizados.</p>
  </div>
  <?php if($puedeProgramar): ?>
    <a class="button gold" href="index.php?page=programar">Programar subasta</a>
  <?php endif; ?>
</div>

<?php if(empty($subastas)): ?>
  <section class="panel">
    <div class="empty">
      <p>Todavía no hay subastas en el catálogo.</p>
      <?php if($puedeProgramar): ?><a class="button sm" href="index.php?page=programar">Programar la primera</a><?php endif; ?>
    </div>
  </section>
<?php else: ?>

<section class="panel filters">
  <div class="search">
    <label for="filtro-texto">Buscar artículo</label>
    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" id="filtro-texto" placeholder="Nombre o descripción">
  </div>
  <div>
    <label for="filtro-categoria">Categoría</label>
    <select id="filtro-categoria">
      <option value="">Todas las categorías</option>
      <?php foreach($categorias_catalogo as $cat): ?>
        <option value="<?=e(mb_strtolower($cat))?>"><?=e($cat)?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="filtro-estado">Estado</label>
    <select id="filtro-estado">
      <option value="">Todos los estados</option>
      <option value="activa">Activa</option>
      <option value="programada">Programada</option>
      <option value="cerrada">Cerrada</option>
    </select>
  </div>
  <div class="count" id="conteo" aria-live="polite"></div>
</section>

<div class="cards" id="contenedor-subastas">
<?php foreach($subastas as $s):
    $esCerrada = in_array(strtolower($s['estado'] ?? ''), ['cerrada', 'finalizada']) || strtotime($s['fecha_fin']) <= time();
    $estadoReal = $esCerrada ? 'cerrada' : (empty($s['estado']) ? 'activa' : strtolower($s['estado']));

    $fechaFin = date('d/m/Y h:i A', strtotime($s['fecha_fin']));
    $fechaInicio = !empty($s['fecha_inicio']) ? date('d/m/Y h:i A', strtotime($s['fecha_inicio'])) : null;

    // Primera imagen del artículo (el campo guarda un JSON con los nombres de archivo)
    $imagenes = json_decode($s['imagen'] ?? '[]', true);
    $imagenPrincipal = (!empty($imagenes) && is_array($imagenes)) ? 'uploads/' . $imagenes[0] : null;

    $esCreador = (int)($s['creado_por'] ?? 0) === $idSesion;
?>
  <article class="lot-card<?= $estadoReal === 'cerrada' ? ' is-closed' : '' ?>"
           data-nombre="<?=e(mb_strtolower($s['articulo'].' '.($s['descripcion'] ?? '')))?>"
           data-categoria="<?=e(mb_strtolower($s['categoria'] ?? ''))?>"
           data-estado="<?=e($estadoReal)?>">

    <div class="lot-cover">
      <?php if($imagenPrincipal): ?>
        <img src="<?=e($imagenPrincipal)?>" alt="Imagen de <?=e($s['articulo'])?>" loading="lazy" onerror="this.hidden=true;this.parentNode.classList.add('no-img')">
      <?php else: ?>
        <span>Sin imagen</span>
      <?php endif; ?>
      <span class="badge <?=e($estadoReal)?>"><?=e($estadoReal)?></span>
      <?php if($estadoReal === 'activa'): ?>
        <span class="countdown" data-countdown="<?=e($s['fecha_fin'])?>"></span>
      <?php endif; ?>
    </div>

    <div class="lot-body">
      <span class="lot-cat"><?=e($s['categoria'] ?? 'Sin categoría')?></span>
      <h3><?=e($s['articulo'])?></h3>

      <div class="lot-money">
        <div>
          <small><?= $estadoReal === 'cerrada' ? 'Precio final' : 'Oferta actual' ?></small>
          <b>Q <?=number_format((float)($s['monto_actual'] ?? 0), 2)?></b>
        </div>
        <small><?=(int)($s['total_pujas'] ?? 0)?> <?=((int)($s['total_pujas'] ?? 0) === 1) ? 'oferta' : 'ofertas'?></small>
      </div>

      <p class="lot-facts">Incremento mínimo: Q <?=number_format((float)($s['incremento_minimo'] ?? 0), 2)?></p>
      <?php if($estadoReal === 'cerrada'): ?>
        <p class="lot-facts"><strong><?= !empty($s['lider_nombre']) ? 'Adjudicada a ' . e($s['lider_nombre']) : 'Sin ofertas' ?></strong></p>
      <?php elseif(!empty($s['lider_nombre'])): ?>
        <p class="lot-facts">Va ganando: <strong><?=e($s['lider_nombre'])?></strong></p>
      <?php endif; ?>
      <?php if($estadoReal === 'programada' && $fechaInicio): ?>
        <p class="lot-facts">Inicia: <?=e($fechaInicio)?></p>
      <?php endif; ?>
      <p class="lot-facts">Cierra: <?=e($fechaFin)?></p>

      <div class="lot-actions">
        <a class="button sm" href="index.php?page=detalle&id=<?=(int)$s['id']?>">Ver detalles</a>
        <?php if (in_array($estadoReal, ['activa','programada'], true) && $esCreador): ?>
          <a class="button sm ghost" href="index.php?page=editar-horario&amp;id=<?=(int)$s['id']?>">Modificar horario</a>
        <?php endif; ?>
        <?php if($estadoReal === 'cerrada' && ($rolSesion === 'Administrador' || $esCreador)): ?>
          <form method="post" action="index.php?action=eliminar_subasta" onsubmit="return confirm('¿Seguro que deseas eliminar esta subasta cerrada?');">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <input type="hidden" name="subasta_id" value="<?=(int)$s['id']?>">
            <button type="submit" class="sm danger">Eliminar</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </article>
<?php endforeach;?>
</div>

<div id="mensaje-sin-resultados" class="empty" style="display:none;">
  <p>Ninguna subasta coincide con los filtros. Prueba con otra búsqueda o categoría.</p>
</div>

<script>
(function() {
  const inputTexto = document.getElementById('filtro-texto');
  const selectCat = document.getElementById('filtro-categoria');
  const selectEstado = document.getElementById('filtro-estado');
  const tarjetas = document.querySelectorAll('.lot-card');
  const msgVacio = document.getElementById('mensaje-sin-resultados');
  const conteo = document.getElementById('conteo');

  function filtrar() {
    const txt = (inputTexto.value || '').toLowerCase().trim();
    const cat = (selectCat.value || '').toLowerCase().trim();
    const est = (selectEstado.value || '').toLowerCase().trim();
    let visibles = 0;

    tarjetas.forEach(function(card) {
      const coincideTexto = !txt || card.getAttribute('data-nombre').includes(txt);
      const coincideCat = !cat || card.getAttribute('data-categoria') === cat;
      const coincideEst = !est || card.getAttribute('data-estado') === est;

      if (coincideTexto && coincideCat && coincideEst) {
        card.style.display = '';
        visibles++;
      } else {
        card.style.display = 'none';
      }
    });

    msgVacio.style.display = visibles === 0 ? 'block' : 'none';
    conteo.textContent = visibles + (visibles === 1 ? ' subasta' : ' subastas');
  }

  inputTexto.addEventListener('input', filtrar);
  selectCat.addEventListener('change', filtrar);
  selectEstado.addEventListener('change', filtrar);
  filtrar();
})();
</script>
<?php endif; ?>