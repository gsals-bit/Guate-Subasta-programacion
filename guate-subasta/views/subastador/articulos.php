<?php
//variables que sean como arreglos para evitar errores
$categorias_base = $categorias_base ?? ['Accesorios', 'Arte', 'Audio y Sonido', 'Coleccionables', 'Tecnología', 'Vehículos'];
$categorias_usadas = $categorias_usadas ?? [];

$todas_categorias = array_unique(array_merge($categorias_base, $categorias_usadas));
sort($todas_categorias);
?>
<h1>Gestión de artículos</h1>

<div class="grid2">
  <section class="panel">
    <h2>Nuevo artículo</h2>
    <form method="post" action="index.php?action=articulo" enctype="multipart/form-data" id="form-articulo">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      
      <label>Nombre</label>
      <input name="nombre" required>
      
      <label>Categoría <small style="opacity:0.7;">(Elige una sugerencia o escribe una nueva)</small></label>
      <input name="categoria" id="input-categoria" list="lista-categorias" autocomplete="off" placeholder="Escribe ej: Construcción, Tecnología, Maquinaria..." required>
      <datalist id="lista-categorias">
        <?php foreach($todas_categorias as $cat): ?>
          <option value="<?=e($cat)?>">
        <?php endforeach; ?>
      </datalist>
      
      <label>Descripción</label>
      <textarea name="descripcion" id="input-descripcion" maxlength="500" rows="4" required style="resize: none; margin-bottom: 5px; padding-bottom: 10px;"></textarea>
      <div style="text-align:right; font-size:0.85em; color:#16a34a; margin-bottom:15px;" id="contador-desc">0 / 500 caracteres</div>
      
      <label>Imágenes del artículo (Obligatorio, máximo 3 fotos)</label>
      <input type="file" name="imagenes[]" id="input-imagenes" accept="image/jpeg, image/png, image/webp" multiple required style="margin-bottom:5px;">
      <small style="display:block; margin-bottom:15px; opacity:0.7;">Formatos: JPG, PNG, WEBP. Tamaño máx: 2MB por imagen.</small>

      <label>Estado físico</label>
      <select name="estado_fisico" required>
        <option value="Excelente">Excelente</option>
        <option value="Bueno">Bueno</option>
        <option value="Regular">Regular</option>
      </select>
      
      <label>Precio base (Q)</label>
      <input type="number" step=".01" min="0.01" name="precio_base" required>
      
      <label>Modelo</label>
      <input name="modelo" placeholder="Ej: 2024, V5, etc.">
      
      <button type="submit">Guardar artículo</button>
    </form>
  </section>

  <section class="panel">
    <h2>Artículos registrados</h2>
    <?php if(empty($articulos)): ?>
      <p>No hay artículos registrados aún.</p>
    <?php else: ?>
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
            <td>Q <?=number_format($a['precio_base'], 2)?></td>
            <td><?=e($a['estado'] ?? 'validado')?></td>
            <td>
              <form method="post" action="index.php?action=eliminar_articulo" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?');" style="margin:0;">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="id" value="<?=(int)$a['id']?>">
                <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer;">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>
</div>

<script>
(function() {
  // Lógica del contador de caracteres con colores dinámicos
  const inputDesc = document.getElementById('input-descripcion');
  const contadorDesc = document.getElementById('contador-desc');
  
  if (inputDesc && contadorDesc) {
    inputDesc.addEventListener('input', function() {
      const longitud = this.value.length;
      contadorDesc.textContent = longitud + ' / 500 caracteres';
      
      if (longitud >= 450) {
        contadorDesc.style.color = '#dc2626';
        contadorDesc.style.fontWeight = 'bold';
      } else if (longitud >= 250) {
        contadorDesc.style.color = '#d97706';
        contadorDesc.style.fontWeight = 'normal';
      } else {
        contadorDesc.style.color = '#16a34a';
        contadorDesc.style.fontWeight = 'normal';
      }
    });
  }

  // Lógica de validación de imágenes
  const inputImg = document.getElementById('input-imagenes');
  const form = document.getElementById('form-articulo');
  
  if (inputImg && form) {
    inputImg.addEventListener('change', function() {
      if (this.files.length > 3) {
        alert('Por favor, selecciona un máximo de 3 imágenes.');
        this.value = ''; 
      } else {
        for (let i = 0; i < this.files.length; i++) {
          if (this.files[i].size > 2097152) {
            alert('La imagen "' + this.files[i].name + '" supera el límite de 2MB.');
            this.value = '';
            break;
          }
        }
      }
    });
  }
})();
</script>