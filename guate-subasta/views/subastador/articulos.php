<h1>Gestión de artículos</h1><div class="grid2"><section class="panel"><h2>Nuevo artículo</h2>
<form method="post" action="index.php?action=articulo"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
<label>Nombre</label><input name="nombre" required><label>Categoría</label><input name="categoria" required>
<label>Descripción</label><textarea name="descripcion"></textarea><label>Estado físico</label><select name="estado_fisico"><option>Excelente</option><option>Bueno</option><option>Regular</option></select>
<label>Precio base (Q)</label><input type="number" step=".01" name="precio_base" required><label>Certificado (referencia)</label><input name="certificado">
<button>Guardar artículo</button></form></section><section><h2>Artículos registrados</h2><table><tr><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Estado</th></tr>
<?php foreach($articulos as $a): ?><tr><td><?=e($a['nombre'])?></td><td><?=e($a['categoria'])?></td><td>Q <?=number_format($a['precio_base'],2)?></td><td><?=e($a['estado'])?></td></tr><?php endforeach;?></table></section></div>