<h1><?=e($subasta['articulo'])?></h1><div class="detail"><section class="panel">
<div class="hero">🔨</div><h3>Descripción</h3><p><?=e($subasta['descripcion'])?></p>
<?php if($subasta['certificado']): ?><p>📄 Certificado: <?=e($subasta['certificado'])?></p><?php endif;?></section>
<section class="panel"><span class="badge"><?=e($subasta['estado'])?></span>
<p>Precio base: <b>Q <?=number_format($subasta['precio_base'],2)?></b></p>
<p>Monto actual: <b>Q <?=number_format($subasta['monto_actual'],2)?></b></p>
<p>Incremento mínimo: Q <?=number_format($subasta['incremento_minimo'],2)?></p>
<p>Finaliza: <?=e($subasta['fecha_fin'])?></p>
<?php if(($_SESSION['user']['rol']??'')==='Cliente'): ?><form method="post" action="index.php?action=pujar">
<input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="subasta_id" value="<?=$subasta['id']?>">
<label>Monto de mi puja (Q)</label><input type="number" step=".01" name="monto" min="<?=($subasta['monto_actual']+$subasta['incremento_minimo'])?>" required>
<button>Realizar puja</button></form><?php endif;?></section></div>
<h2>Historial de pujas</h2><table><tr><th>Usuario</th><th>Monto</th><th>Fecha</th></tr>
<?php foreach($historial as $p): ?><tr><td><?=e($p['usuario'])?></td><td>Q <?=number_format($p['monto'],2)?></td><td><?=e($p['fecha'])?></td></tr><?php endforeach;?></table>