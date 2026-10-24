<?php
// 1. Catálogo amplio de sugerencias base para subastas
$categorias_base = [
    'Accesorios', 'Antigüedades', 'Arte', 'Audio y Sonido', 'Coleccionables',
    'Computación', 'Deportes', 'Electrodomésticos', 'Herramientas', 'Hogar',
    'Inmuebles', 'Instrumentos Musicales', 'Joyería y Relojes', 'Juguetes',
    'Libros y Cómics', 'Maquinaria', 'Moda y Ropa', 'Muebles', 
    'Salud y Belleza', 'Tecnología', 'Vehículos', 'Videojuegos'
];

// 2. Extraer automáticamente las categorías personalizadas que ya están en uso
$categorias_usadas = !empty($articulos) ? array_column($articulos, 'categoria') : [];

// 3. Unir ambas listas, eliminar duplicados y ordenar alfabéticamente
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
    <h2>Mis artículos registrados</h2>
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
        <?php foreach($articulos as $a): 
          // Determinar el estado de la subasta del artículo y su color
          $estSubasta = strtolower(trim($a['subasta_estado'] ?? ''));
          $fechaFin = $a['subasta_fecha_fin'] ?? '';
          
          if (empty($estSubasta)) {
              $estadoMostrar = 'Sin programar';
              $bgBadge = '#6b7280'; // Gris si aún no tiene subasta
          } else {
              $vencida = !empty($fechaFin) && strtotime($fechaFin) <= time();
              if ($estSubasta === 'cerrada' || $estSubasta === 'finalizada' || $vencida) {
                  $estadoMostrar = 'Cerrada';
                  $bgBadge = '#dc2626'; // Rojo
              } elseif ($estSubasta === 'programada') {
                  $estadoMostrar = 'Programada';
                  $bgBadge = '#d97706'; // Naranja
              } else {
                  $estadoMostrar = 'Activa';
                  $bgBadge = '#16a34a'; // Verde
              }
          }

          $esPropietario = ($_SESSION['user']['rol'] === 'Administrador') || (isset($a['creado_por']) && (int)$a['creado_por'] === (int)$_SESSION['user']['id']);
        ?>
          <tr>
            <td><?=e($a['nombre'])?></td>
            <td><?=e($a['categoria'])?></td>
            <td>Q <?=number_format($a['precio_base'], 2)?></td>
            <td>
              <span class="badge" style="background:<?=$bgBadge?>; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8em; font-weight:bold; display:inline-block;">
                <?=e($estadoMostrar)?>
              </span>
            </td>
            <td>
              <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                <!-- Botón Ver detalles disponible para todos los usuarios -->
                <button type="button" class="button btn-ver-detalles"
                        style="padding:6px 10px; font-size:0.85em; cursor:pointer; border:none;"
                        data-nombre="<?=e($a['nombre'])?>"
                        data-categoria="<?=e($a['categoria'])?>"
                        data-precio="<?=number_format($a['precio_base'], 2)?>"
                        data-estado-fisico="<?=e($a['estado_fisico'] ?? 'N/A')?>"
                        data-modelo="<?=e($a['modelo'] ?: 'N/A')?>"
                        data-descripcion="<?=e($a['descripcion'] ?: 'Sin descripción')?>"
                        data-estado-subasta="<?=e($estadoMostrar)?>"
                        data-color-estado="<?=e($bgBadge)?>"
                        data-subasta-id="<?=(int)($a['subasta_id'] ?? 0)?>"
                        data-imagenes="<?=e($a['imagen'] ?? '[]')?>">
                  Ver detalles
                </button>

                <!-- Botón Eliminar solo si el usuario registró el artículo (o es Admin) -->
                <?php if($esPropietario): ?>
                  <form method="post" action="index.php?action=eliminar_articulo" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?');" style="margin:0;">
                    <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                    <input type="hidden" name="id" value="<?=(int)$a['id']?>">
                    <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:6px 10px; border-radius:4px; font-size:0.85em; cursor:pointer;">Eliminar</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>
</div>

<!-- Modal para visualizar todos los detalles del artículo -->
<div id="modal-detalle-articulo" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
  <div style="background:#fff; color:#1f2937; max-width:550px; width:100%; border-radius:8px; padding:24px; position:relative; max-height:90vh; overflow-y:auto; box-shadow:0 10px 25px rgba(0,0,0,0.3);">
    <button type="button" id="cerrar-modal-articulo" style="position:absolute; top:12px; right:15px; background:transparent; border:none; font-size:1.5em; cursor:pointer; color:#6b7280; line-height:1;">&times;</button>
    
    <h2 id="modal-art-nombre" style="margin-top:0; margin-bottom:8px;"></h2>
    <span id="modal-art-badge" style="color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8em; font-weight:bold; display:inline-block; margin-bottom:15px;"></span>
    
    <div id="modal-art-galeria" style="display:flex; gap:10px; overflow-x:auto; margin-bottom:15px; padding-bottom:6px;"></div>
    
    <p style="margin:6px 0;"><strong>Categoría:</strong> <span id="modal-art-categoria"></span></p>
    <p style="margin:6px 0;"><strong>Precio base:</strong> Q <span id="modal-art-precio"></span></p>
    <p style="margin:6px 0;"><strong>Estado físico:</strong> <span id="modal-art-fisico"></span></p>
    <p style="margin:6px 0;"><strong>Modelo:</strong> <span id="modal-art-modelo"></span></p>
    <hr style="margin:12px 0; border:0; border-top:1px solid #e5e7eb;">
    <p style="margin:6px 0;"><strong>Descripción:</strong></p>
    <p id="modal-art-descripcion" style="background:#f9fafb; padding:10px; border-radius:6px; border:1px solid #e5e7eb; white-space:pre-wrap; margin-top:4px;"></p>

    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:18px;">
      <a id="modal-art-link-subasta" class="button" href="#" style="display:none; padding:8px 14px;">Ir a la subasta</a>
      <button type="button" id="btn-cerrar-inferior" style="background:#4b5563; color:#fff; border:none; padding:8px 14px; border-radius:4px; cursor:pointer;">Cerrar</button>
    </div>
  </div>
</div>

<script>
(function() {
  // 1. Lógica del contador de caracteres con colores dinámicos
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

  // 2. Lógica de validación de imágenes
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

  // 3. Lógica del modal "Ver detalles"
  const modal = document.getElementById('modal-detalle-articulo');
  const btnCerrarX = document.getElementById('cerrar-modal-articulo');
  const btnCerrarInf = document.getElementById('btn-cerrar-inferior');
  const botonesDetalle = document.querySelectorAll('.btn-ver-detalles');

  function cerrarModal() {
    if (modal) modal.style.display = 'none';
  }

  if (btnCerrarX) btnCerrarX.addEventListener('click', cerrarModal);
  if (btnCerrarInf) btnCerrarInf.addEventListener('click', cerrarModal);
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) cerrarModal();
    });
  }

  botonesDetalle.forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('modal-art-nombre').textContent = this.getAttribute('data-nombre');
      document.getElementById('modal-art-categoria').textContent = this.getAttribute('data-categoria');
      document.getElementById('modal-art-precio').textContent = this.getAttribute('data-precio');
      document.getElementById('modal-art-fisico').textContent = this.getAttribute('data-estado-fisico');
      document.getElementById('modal-art-modelo').textContent = this.getAttribute('data-modelo');
      document.getElementById('modal-art-descripcion').textContent = this.getAttribute('data-descripcion');

      const badge = document.getElementById('modal-art-badge');
      badge.textContent = this.getAttribute('data-estado-subasta');
      badge.style.background = this.getAttribute('data-color-estado');

      // Renderizar imágenes del artículo
      const galeria = document.getElementById('modal-art-galeria');
      galeria.innerHTML = '';
      let imgs = [];
      try {
        imgs = JSON.parse(this.getAttribute('data-imagenes') || '[]');
      } catch (e) {
        imgs = [];
      }

      if (Array.isArray(imgs) && imgs.length > 0) {
        imgs.forEach(function(img) {
          const elImg = document.createElement('img');
          elImg.src = 'uploads/' + img;
          elImg.style.cssText = 'height:140px; width:auto; object-fit:contain; background:#fff; padding:4px; border-radius:6px; border:1px solid #e5e7eb;';
          galeria.appendChild(elImg);
        });
      } else {
        galeria.innerHTML = '<div style="width:100%; padding:20px; text-align:center; background:#f3f4f6; border-radius:6px; color:#9ca3af; font-style:italic;">Sin imágenes adjuntas</div>';
      }

      // Mostrar botón hacia la subasta si ya tiene una creada
      const subastaId = parseInt(this.getAttribute('data-subasta-id') || '0', 10);
      const linkSubasta = document.getElementById('modal-art-link-subasta');
      if (subastaId > 0) {
        linkSubasta.href = 'index.php?page=detalle&id=' + subastaId;
        linkSubasta.style.display = 'inline-block';
      } else {
        linkSubasta.style.display = 'none';
      }

      modal.style.display = 'flex';
    });
  });
})();
</script>