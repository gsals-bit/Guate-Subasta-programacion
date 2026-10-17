<h1>Programar subasta</h1>
<section class="panel narrow">
  <?php if(empty($articulos)): ?>
    <p>No hay artículos disponibles para programar en este momento. Todos los artículos registrados ya tienen una subasta activa o programada.</p>
    <a class="button" href="index.php?page=articulos">Registrar un nuevo artículo</a>
  <?php else: ?>
    <form method="post" action="index.php?action=programar">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      
      <label>Artículo disponible</label>
      <select name="articulo_id" required>
        <?php foreach($articulos as $a): ?>
          <option value="<?=(int)$a['id']?>"><?=e($a['nombre'])?> - <?=e($a['categoria'])?> (Base: Q <?=number_format($a['precio_base'], 2)?>)</option>
        <?php endforeach;?>
      </select>
      
      <label>Fecha y hora de inicio (Ej: 2026-10-17 14:30:00)</label>
      <input type="text" name="inicio" placeholder="2026-10-17 14:30:00" required>
      
      <label>Fecha y hora de fin (Ej: 2026-10-17 16:30:00)</label>
      <input type="text" name="fin" placeholder="2026-10-17 16:30:00" required>
      
      <label>Incremento mínimo (Q)</label>
      <input type="number" step=".01" min="0.01" name="incremento" required>
      
      <button type="submit">Programar subasta</button>
    </form>
  <?php endif; ?>
</section>