<h1>Programar subasta</h1>
<section class="panel narrow">
  <form method="post" action="index.php?action=programar" id="formProgramar">
    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
    
    <label>Artículo</label>
    <select name="articulo_id">
      <?php foreach($articulos as $a): ?>
        <option value="<?=$a['id']?>"><?=e($a['nombre'])?></option>
      <?php endforeach;?>
    </select>
    
    <label>Fecha y hora de inicio</label>
    <input type="datetime-local" name="inicio" id="inicio" required>
    
    <label>Fecha y hora de fin</label>
    <input type="datetime-local" name="fin" id="fin" required>
    
    <label>Incremento mínimo (Q)</label>
    <input type="number" step=".01" min="0.01" name="incremento" required>
    
    <button type="submit">Programar subasta</button>
  </form>
</section>

<script>
document.getElementById('formProgramar').addEventListener('submit', function(e) {
    // Aseguramos que los inputs no queden vacíos o con formato inválido para el navegador
    const inicio = document.getElementById('inicio').value;
    const fin = document.getElementById('fin').value;
    if (!inicio || !fin) {
        e.preventDefault();
        alert('Por favor selecciona una fecha y hora válidas para el inicio y el fin.');
    }
});
</script>