<h1>Programar subasta</h1>
<section class="panel narrow">
  <?php if(empty($articulos)): ?>
    <p>No hay artículos disponibles para programar en este momento. Todos los artículos registrados ya tienen una subasta activa o programada.</p>
    <a class="button" href="index.php?page=articulos">Registrar un nuevo artículo</a>
  <?php else: ?>
    <form method="post" action="index.php?action=programar" id="formProgramar">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      
      <!-- Campos ocultos que envían el formato correcto (YYYY-MM-DD HH:MM:SS) a la base de datos -->
      <input type="hidden" name="inicio" id="inicio_hidden">
      <input type="hidden" name="fin" id="fin_hidden">

      <label>Artículo disponible</label>
      <select name="articulo_id" id="articulo_select" required>
        <option value="">Selecciona un artículo para calcular el incremento...</option>
        <?php foreach($articulos as $a): ?>
          <option value="<?=(int)$a['id']?>" data-precio="<?=(float)$a['precio_base']?>">
            <?=e($a['nombre'])?> - <?=e($a['categoria'])?> (Base: Q <?=number_format($a['precio_base'], 2)?>)
          </option>
        <?php endforeach;?>
      </select>

      <div style="display:flex; gap:15px; margin-bottom:15px; margin-top:10px;">
        <div style="flex:1;">
          <label style="margin-top:0;">Fecha de inicio</label>
          <input type="date" id="fecha_inicio" value="<?=date('Y-m-d')?>" required>
        </div>
        <div style="flex:1;">
          <label style="margin-top:0;">Hora de inicio</label>
          <input type="time" id="hora_inicio" value="<?=date('H:00')?>" required>
        </div>
      </div>

      <div style="display:flex; gap:15px; margin-bottom:15px;">
        <div style="flex:1;">
          <label style="margin-top:0;">Fecha de fin</label>
          <input type="date" id="fecha_fin" value="<?=date('Y-m-d', strtotime('+7 days'))?>" required>
        </div>
        <div style="flex:1;">
          <label style="margin-top:0;">Hora de fin</label>
          <input type="time" id="hora_fin" value="<?=date('H:00', strtotime('+2 hours'))?>" required>
        </div>
      </div>

      <label>Incremento mínimo (Q) <small style="opacity:0.7;">(Sugerido por estándar de subasta)</small></label>
      <input type="number" step=".01" min="0.01" name="incremento" id="incremento" required>

      <button type="submit">Programar subasta</button>
    </form>
  <?php endif; ?>
</section>

<script>
(function() {
  const selectArticulo = document.getElementById('articulo_select');
  const inputIncremento = document.getElementById('incremento');
  const form = document.getElementById('formProgramar');

  // Calcular el incremento estándar al seleccionar un artículo
  if (selectArticulo) {
    selectArticulo.addEventListener('change', function() {
      const opcion = this.options[this.selectedIndex];
      if (!opcion.value) {
        inputIncremento.value = '';
        return;
      }

      const precioBase = parseFloat(opcion.getAttribute('data-precio'));
      let incrementoSugerido = 10; // Incremento mínimo por defecto

      // Escalas estándar de casas de subastas comerciales
      if (precioBase >= 10000) {
        incrementoSugerido = 500;
      } else if (precioBase >= 5000) {
        incrementoSugerido = 250;
      } else if (precioBase >= 1000) {
        incrementoSugerido = 100;
      } else if (precioBase >= 500) {
        incrementoSugerido = 50;
      } else if (precioBase >= 100) {
        incrementoSugerido = 20;
      }

      inputIncremento.value = incrementoSugerido.toFixed(2);
    });
  }

  // Unir la fecha y la hora justo antes de enviar el formulario a PHP
  if (form) {
    form.addEventListener('submit', function() {
      const fi = document.getElementById('fecha_inicio').value;
      const hi = document.getElementById('hora_inicio').value;
      const ff = document.getElementById('fecha_fin').value;
      const hf = document.getElementById('hora_fin').value;

      document.getElementById('inicio_hidden').value = fi + ' ' + hi + ':00';
      document.getElementById('fin_hidden').value = ff + ' ' + hf + ':00';
    });
  }
})();
</script>