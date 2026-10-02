<h1>Subastas</h1><p>Consulta artículos programados y activos.</p><div class="cards">
<?php foreach($subastas as $s): ?><article class="card"><div class="placeholder">🔨</div><span class="badge"><?=e($s['estado'])?></span>
<h3><?=e($s['articulo'])?></h3><p><?=e($s['categoria'])?></p><b>Q <?=number_format($s['monto_actual'],2)?></b>
<p>Incremento: Q <?=number_format($s['incremento_minimo'],2)?></p><p>Finaliza: <?=e($s['fecha_fin'])?></p>
<a class="button" href="index.php?page=detalle&id=<?=$s['id']?>">Ver detalles</a></article><?php endforeach;?></div>