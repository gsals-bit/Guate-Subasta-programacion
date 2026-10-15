<h1>Gestión de artículos</h1>
<div class="grid2">
  <section class="panel">
    <h2>Nuevo artículo</h2>
    <form method="post" action="index.php?action=articulo">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <label>Nombre</label>
      <input name="nombre" required>
      <label>Categoría</label>
      <input name="categoria" required>
      <label>Descripción</label>
      <textarea name="descripcion"></textarea>
      <label>Estado físico</label>
      <select name="estado_fisico">
        <option value="Excelente">Excelente</option>
        <option value="Bueno">Bueno</option>
        <option value="Regular">Regular</option>
      </select>
      <label>Precio base (Q)</label>
      <input type="number" step=".01" min="0.01" name="precio_base" required>
      <label>Certificado (referencia)</label>
      <input name="certificado">
      <button type="submit">Guardar artículo</button>
    </form>
  </section>

  <section>
    <h2>Artículos registrados</h2>
    <table>
      <tr>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Precio</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
      <?php foreach($articulos as $a): ?>
      <tr>
        <td><?=e($a['nombre'])?></td>
        <td><?=e($a['categoria'])?></td>
        <td>Q <?=number_format($a['precio_base'],2)?></td>
        <td><?=e($a['estado'] ?? $a['estado_fisico'] ?? 'Registrado')?></td>
        <td>
          <form method="post" action="index.php?action=eliminar_articulo" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?');" style="display:inline;">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <input type="hidden" name="articulo_id" value="<?=(int)$a['id']?>">
            <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:6px 10px;border-radius:4px;cursor:pointer;">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach;?>
    </table>
  </section>
</div>
