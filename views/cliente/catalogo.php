<h1>Subastas</h1>
<p>Consulta artículos programados, activos y cerrados.</p>
<div class="cards">
<?php foreach($subastas as $s): ?>
  <article class="card">
    <div class="placeholder">🔨</div>
    <span class="badge"><?=e($s['estado'])?></span>
    <h3><?=e($s['articulo'])?></h3>
    <p><?=e($s['categoria'])?></p>
    <b>Q <?=number_format($s['monto_actual'],2)?></b>
    <p>Incremento: Q <?=number_format($s['incremento_minimo'],2)?></p>
    <p>Finaliza: <?=e($s['fecha_fin'])?></p>
    
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:10px;">
      <a class="button" href="index.php?page=detalle&id=<?=(int)$s['id']?>">Ver detalles</a>
      
      <?php if($s['estado'] === 'cerrada' || strtotime($s['fecha_fin']) <= time()): ?>
        <form method="post" action="index.php?action=eliminar_subasta" onsubmit="return confirm('¿Seguro que deseas eliminar esta subasta cerrada?');" style="display:inline; margin:0;">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="subasta_id" value="<?=(int)$s['id']?>">
          <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;">Eliminar</button>
        </form>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach;?>
</div>
