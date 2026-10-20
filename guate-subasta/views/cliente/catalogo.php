<?php
$categorias_catalogo = array_values(array_filter(array_unique(array_column($subastas, 'categoria'))));
sort($categorias_catalogo);
?>
<h1>Subastas</h1>
<p>Consulta artículos programados, activos y finalizados.</p>

<section class="panel" style="margin-bottom:20px; padding:15px;">
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end;">
    <div>
      <label style="margin-top:0;">Buscar artículo</label>
      <input type="text" id="filtro-texto" placeholder="Escribe el nombre o descripción...">
    </div>
    <div>
      <label style="margin-top:0;">Categoría</label>
      <select id="filtro-categoria">
        <option value="">Todas las categorías</option>
        <?php foreach($categorias_catalogo as $cat): ?>
          <option value="<?=e(mb_strtolower($cat))?>"><?=e($cat)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="margin-top:0;">Estado</label>
      <select id="filtro-estado">
        <option value="">Todos los estados</option>
        <option value="activa">Activa</option>
        <option value="programada">Programada</option>
        <option value="cerrada">Cerrada</option>
      </select>
    </div>
  </div>
</section>

<div class="cards" id="contenedor-subastas">
<?php foreach($subastas as $s): 
    // Validación de fecha dinámica: Fuerza el estado a cerrada si el tiempo ya se agotó
    $esCerrada = in_array(strtolower($s['estado'] ?? ''), ['cerrada', 'finalizada']) || strtotime($s['fecha_fin']) <= time();
    $estadoReal = $esCerrada ? 'cerrada' : (empty($s['estado']) ? 'activa' : strtolower($s['estado']));
    
    // Asignación de colores para las etiquetas
    $colorBadge = '#16a34a'; // Verde para activa
    if ($estadoReal === 'cerrada') $colorBadge = '#4b5563'; // Gris
    if ($estadoReal === 'programada') $colorBadge = '#d97706'; // Naranja
?>
  <article class="card item-subasta"
           data-nombre="<?=e(mb_strtolower($s['articulo'].' '.($s['descripcion'] ?? '')))?>"
           data-categoria="<?=e(mb_strtolower($s['categoria'] ?? ''))?>"
           data-estado="<?=e($estadoReal)?>">
    
    <span class="badge" style="background: <?=$colorBadge?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; display: inline-block; margin-bottom: 10px; text-transform: capitalize;">
      <?=e($estadoReal)?>
    </span>
    
    <h3><?=e($s['articulo'])?></h3>
    <p><strong>Categoría:</strong> <?=e($s['categoria'] ?? 'Sin categoría')?></p>
    <b>Q <?=number_format($s['monto_actual'] ?? 0, 2)?></b>
    <p>Incremento mínimo: Q <?=number_format($s['incremento_minimo'] ?? 0, 2)?></p>
    <p>Ofertas realizadas: <?=(int)($s['total_pujas'] ?? 0)?></p>

    <?php if($estadoReal === 'cerrada'): ?>
      <p>
        <strong>Resultado:</strong>
        <?php if(!empty($s['lider_nombre'])): ?>
          Adjudicada a <?=e($s['lider_nombre'])?>
        <?php else: ?>
          Sin ofertas
        <?php endif; ?>
      </p>
    <?php elseif(!empty($s['lider_nombre'])): ?>
      <p><strong>Líder actual:</strong> <?=e($s['lider_nombre'])?></p>
    <?php endif; ?>

    <p>Finaliza: <?=e($s['fecha_fin'])?></p>
    
    <div style="display:flex; gap:8px; margin-top:10px; align-items:center;">
      <a class="button" href="index.php?page=detalle&id=<?=(int)$s['id']?>">Ver detalles</a>

      <?php if($estadoReal === 'cerrada'): ?>
        <form method="post" action="index.php?action=eliminar_subasta" onsubmit="return confirm('¿Seguro que deseas eliminar esta subasta cerrada?');" style="margin:0;">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="subasta_id" value="<?=(int)$s['id']?>">
          <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:8px 12px; border-radius:4px; cursor:pointer;">Eliminar</button>
        </form>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach;?>
</div>

<p id="mensaje-sin-resultados" style="display:none; opacity:0.8; margin-top:15px;">No se encontraron subastas que coincidan con los filtros seleccionados.</p>

<script>
(function() {
  const inputTexto = document.getElementById('filtro-texto');
  const selectCat = document.getElementById('filtro-categoria');
  const selectEstado = document.getElementById('filtro-estado');
  const tarjetas = document.querySelectorAll('.item-subasta');
  const msgVacio = document.getElementById('mensaje-sin-resultados');

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
  }

  inputTexto.addEventListener('input', filtrar);
  selectCat.addEventListener('change', filtrar);
  selectEstado.addEventListener('change', filtrar);
})();
</script>