<?php 
// 1. Extraer categorías que ya hayan sido registradas antes en la base de datos
$categorias_usadas = array_filter(array_unique(array_column($articulos, 'categoria')));

// 2. Diccionario amplio con todas las categorías posibles en subastas comerciales, industriales y de colección
$categorias_base = [
    'Accesorios', 'Agropecuaria y Ganadería', 'Antigüedades', 'Arte', 
    'Audio y Sonido', 'Bebés y Niños', 'Belleza y Cuidado Personal', 'Bienes Raíces e Inmuebles',
    'Bolsos y Marroquinería', 'Camiones y Transporte', 'Ciclismo', 'Coleccionables',
    'Computación e Informática', 'Consolas y Videojuegos', 'Construcción', 'Construcción y Materiales',
    'Decoración e Iluminación', 'Deportes y Fitness', 'Electrodomésticos', 'Electrónica',
    'Energía y Paneles Solares', 'Equipos Médicos y Laboratorio', 'Equipos para Restaurantes y Comercio', 'Esculturas',
    'Ferretería', 'Fotografía y Video', 'Herramientas y Equipos', 'Hogar y Muebles',
    'Instrumentos Musicales', 'Insumos Agrícolas', 'Jardinería y Exteriores', 'Joyería',
    'Juguetes y Juegos', 'Libros, Revistas y Cómics', 'Locales Comerciales', 'Lotes de Liquidación y Mayoreo',
    'Maquinaria Agrícola', 'Maquinaria Industrial', 'Maquinaria Pesada', 'Mascotas y Accesorios',
    'Metales, Chatarra y Reciclaje', 'Mobiliario de Oficina', 'Motocicletas', 'Náutica y Embarcaciones',
    'Numismática (Monedas y Billetes)', 'Relojes', 'Repuestos y Autopartes', 'Ropa y Calzado',
    'Seguridad Industrial', 'Tecnología', 'Telefonía y Celulares', 'Terrenos y Lotes',
    'Vehículos', 'Vinos y Licores de Colección'
];

// Combinar ambas listas sin duplicados y ordenar alfabéticamente
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
    <textarea name="descripcion" id="input-descripcion" maxlength="500" rows="4" required></textarea>
    <div style="text-align:right; font-size:0.85em; opacity:0.7; margin-top:-10px; margin-bottom:15px;" id="contador-desc">0 / 500 caracteres</div>
    
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

<script>
(function() {
  // Lógica del contador de caracteres
  const inputDesc = document.getElementById('input-descripcion');
  const contadorDesc = document.getElementById('contador-desc');
  
  if (inputDesc && contadorDesc) {
    inputDesc.addEventListener('input', function() {
      const longitud = this.value.length;
      contadorDesc.textContent = longitud + ' / 500 caracteres';
      if (longitud >= 500) {
        contadorDesc.style.color = '#dc2626'; // Rojo si llega al límite
        contadorDesc.style.fontWeight = 'bold';
      } else {
        contadorDesc.style.color = 'inherit';
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
        this.value = ''; // Resetea el campo si excede el límite
      } else {
        // Validar tamaño (2MB = 2097152 bytes)
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

<script>
// Permite autocompletar la primera categoría sugerida al presionar Enter o Tab
(function() {
  const inputCat = document.getElementById('input-categoria');
  const opciones = <?=json_encode(array_values($todas_categorias))?>;

  const limpiarTexto = (txt) => txt.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();

  inputCat.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === 'Tab') {
      const valorActual = limpiarTexto(this.value);
      if (!valorActual) return;

      // Buscar si lo que escribió coincide con el inicio de alguna categoría
      const coincidencia = opciones.find(op => limpiarTexto(op).startsWith(valorActual));
      
      if (coincidencia && limpiarTexto(coincidencia) !== valorActual) {
        if (e.key === 'Enter') e.preventDefault(); // Evita que el formulario se envíe antes de tiempo
        this.value = coincidencia;
      }
    }
  });
})();
</script>