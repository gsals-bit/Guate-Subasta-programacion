<h1>Programar subasta</h1>
<section class="panel narrow">
  <form method="post" action="index.php?action=programar">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    
    <label>Artículo</label>
    <select name="articulo_id">
      <?php foreach($articulos as $a): ?>
        <option value="<?=$a['id']?>"><?=e($a['nombre'])?></option>
      <?php endforeach;?>
    </select>
    
    <label>Fecha y hora de inicio (Ej: 2026-10-14 14:30)</label>
    <input type="text" name="inicio" placeholder="2026-10-14 14:30:00" required>
    
    <label>Fecha y hora de fin (Ej: 2026-10-14 15:30)</label>
    <input type="text" name="fin" placeholder="2026-10-14 15:30:00" required>
    
    <label>Incremento mínimo (Q)</label>
    <input type="number" step=".01" min="0.01" name="incremento" required>
    
    <button type="submit">Programar subasta</button>
  </form>
</section>