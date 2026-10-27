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
<!-- Estilos propios del selector de fotos y el mapa -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoQ2/tR0dRlyto=" crossorigin="">
<style>
  .foto-preview {display:grid;grid-template-columns:repeat(auto-fill,minmax(115px,1fr));gap:10px;margin-bottom:12px}
  .foto-tarjeta {border:1px solid #cbd5e1;border-radius:10px;padding:6px;min-width:0;background:#f8fafc}
  .foto-tarjeta img {width:100%;height:88px;object-fit:cover;border-radius:6px}
  .foto-tarjeta small {display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin:5px 0;font-size:11px}
  .foto-quitar {width:100%;font-size:12px;background:#b42318;color:white;border:0;border-radius:6px;padding:5px;cursor:pointer}
  #mapa-entrega {width:100%;height:350px;min-height:300px;border:1px solid #cbd5e1;border-radius:12px;z-index:0;margin:10px 0}
  .mapa-acciones {display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 12px}
  .mapa-acciones button {cursor:pointer}
  /* Correccion Leaflet: evita que las baldosas se salgan del mapa cuando el CSS del CDN se bloquea o choca con estilos globales. */
  #mapa-entrega.leaflet-container {position:relative !important;overflow:hidden !important;isolation:isolate;max-width:100%;background:#dbeafe}
  #mapa-entrega .leaflet-pane,
  #mapa-entrega .leaflet-tile,
  #mapa-entrega .leaflet-marker-icon,
  #mapa-entrega .leaflet-marker-shadow,
  #mapa-entrega .leaflet-tile-container,
  #mapa-entrega .leaflet-layer,
  #mapa-entrega .leaflet-image-layer,
  #mapa-entrega .leaflet-zoom-box {position:absolute !important;left:0;top:0}
  #mapa-entrega .leaflet-tile {width:256px !important;height:256px !important;max-width:none !important;max-height:none !important;border:0 !important;padding:0 !important;margin:0 !important;object-fit:fill !important}
  #mapa-entrega .leaflet-tile-pane {z-index:200}
  #mapa-entrega .leaflet-overlay-pane {z-index:400}
  #mapa-entrega .leaflet-shadow-pane {z-index:500}
  #mapa-entrega .leaflet-marker-pane {z-index:600}
  #mapa-entrega .leaflet-tooltip-pane {z-index:650}
  #mapa-entrega .leaflet-popup-pane {z-index:700}
  #mapa-entrega .leaflet-control-container .leaflet-top,
  #mapa-entrega .leaflet-control-container .leaflet-bottom {position:absolute;z-index:1000;pointer-events:none}
  #mapa-entrega .leaflet-control-container .leaflet-top {top:0}
  #mapa-entrega .leaflet-control-container .leaflet-bottom {bottom:0}
  #mapa-entrega .leaflet-control-container .leaflet-left {left:0}
  #mapa-entrega .leaflet-control-container .leaflet-right {right:0}
  #mapa-entrega .leaflet-control {position:relative;z-index:800;pointer-events:auto}
  #mapa-entrega .leaflet-tile-container img {display:block}
</style>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

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
      
      <label for="selector-imagenes">Fotografías del artículo (de 1 a 10)</label>
      <p style="font-size:0.88em;color:#64748b;margin:4px 0 10px;">Puedes agregarlas de una en una o varias a la vez, sin perder las anteriores.</p>
      <!-- El selector permite agregar fotos por tandas. El input con name se envia al servidor. -->
      <input type="file" id="selector-imagenes" accept="image/jpeg,image/png,image/webp" multiple style="margin-bottom:8px;">
      <input type="file" name="imagenes[]" id="input-imagenes" accept="image/jpeg,image/png,image/webp" multiple tabindex="-1" aria-hidden="true" style="display:none;">
      <p id="contador-imagenes" style="color:#475569;font-size:0.9em;" aria-live="polite">0 de 10 fotografías seleccionadas.</p>
      <div id="vista-imagenes" class="foto-preview" aria-label="Vista previa de imágenes"></div>
      <small style="display:block;margin:12px 0 15px;color:#64748b;">Formatos JPG, PNG, WEBP. Máximo 2 MB por fotografía.</small>

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
      
      <label for="ubicacion-nombre">Dirección o referencia de entrega (visible al pujador)</label>
      <input name="ubicacion_nombre" id="ubicacion-nombre" placeholder="Ej. Centro comercial o punto público de entrega" maxlength="200">

      <label style="margin-top:12px;display:block;">Selecciona el punto exacto de entrega en el mapa</label>
      <p style="color:#475569;font-size:0.88em;margin:6px 0 12px;">Haz clic en el mapa, mueve el marcador o utiliza tu ubicación actual. No hace falta escribir coordenadas.</p>
      <div class="mapa-acciones">
        <button type="button" id="btn-ubicacion-actual" class="button">📍 Usar mi ubicación actual</button>
        <button type="button" id="btn-limpiar-ubicacion" style="background:#64748b;">Limpiar selección</button>
      </div>
      <div id="mapa-entrega" role="application" aria-label="Mapa para seleccionar punto de entrega"></div>
      <p id="estado-mapa" style="font-size:0.88em;color:#475569;" aria-live="polite">Aún no seleccionaste una ubicación de entrega.</p>
      <a id="enlace-maps" href="#" target="_blank" rel="noopener noreferrer" style="display:none;margin-bottom:12px;">Ver punto seleccionado en Google Maps</a>
      <!-- Estos campos NO se muestran; mantienen compatibilidad con index.php y Articulo.php. -->
      <input type="hidden" name="ubicacion_lat" id="ubicacion-lat">
      <input type="hidden" name="ubicacion_lng" id="ubicacion-lng">
      <p class="hint">Selecciona un lugar público de entrega. La ubicación actual requiere permiso del navegador y puede ser aproximada.</p>
      <button type="submit">Guardar artículo</button>
    </form>
  </section>

  <section class="panel">
    <h2>Mis artículos registrados</h2>
    <?php if($_SESSION['user']['rol']==='Administrador' && !empty($articulos)): ?>
      <form method="post" action="index.php?action=archivar_articulos" id="form-archivar" onsubmit="return confirm('¿Archivar los artículos seleccionados? Sus pujas y pagos se conservarán.');">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <p>Marca los artículos que deseas retirar del catálogo.</p>
        <button type="button" onclick="document.querySelectorAll('.archivar-check').forEach(x=>x.checked=true)">Seleccionar todos</button>
        <button type="submit" style="background:#b42318">Archivar seleccionados</button>
      </form>
    <?php endif; ?>
    <?php if(empty($articulos)): ?>
      <p>No hay artículos registrados aún.</p>
    <?php else: ?>
      <table>
        <tr>
          <?php if($_SESSION['user']['rol']==='Administrador'): ?><th>Seleccionar</th><?php endif; ?>
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
            <?php if($_SESSION['user']['rol']==='Administrador'): ?><td><input class="archivar-check" type="checkbox" form="form-archivar" name="ids[]" value="<?=(int)$a['id']?>"></td><?php endif; ?>
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

  // 2. Imagenes acumulables (de 1 a 10), con vista previa y boton para quitar.
  const form = document.getElementById('form-articulo');
  const selectorImagenes = document.getElementById('selector-imagenes');
  const inputImg = document.getElementById('input-imagenes');
  const contadorImagenes = document.getElementById('contador-imagenes');
  const vistaImagenes = document.getElementById('vista-imagenes');
  let imagenesSeleccionadas = [];
  const MAX_IMAGENES = 10;
  const MAX_BYTES = 2 * 1024 * 1024;
  const MIME_VALIDOS = ['image/jpeg', 'image/png', 'image/webp'];

  function sincronizarImagenes() {
    const transferencia = new DataTransfer();
    imagenesSeleccionadas.forEach(archivo => transferencia.items.add(archivo));
    inputImg.files = transferencia.files;
    contadorImagenes.textContent = imagenesSeleccionadas.length + ' de 10 fotografías seleccionadas.';
    vistaImagenes.innerHTML = '';
    imagenesSeleccionadas.forEach((archivo, indice) => {
      const tarjeta = document.createElement('div');
      tarjeta.className = 'foto-tarjeta';
      const foto = document.createElement('img');
      const url = URL.createObjectURL(archivo);
      foto.src = url;
      foto.alt = 'Vista previa de ' + archivo.name;
      foto.onload = () => URL.revokeObjectURL(url);
      foto.onerror = () => URL.revokeObjectURL(url);
      const nombre = document.createElement('small');
      nombre.textContent = archivo.name;
      nombre.title = archivo.name;
      const quitar = document.createElement('button');
      quitar.type = 'button';
      quitar.className = 'foto-quitar';
      quitar.textContent = 'Quitar';
      quitar.setAttribute('aria-label', 'Quitar foto ' + archivo.name);
      quitar.addEventListener('click', () => {
        imagenesSeleccionadas.splice(indice, 1);
        sincronizarImagenes();
      });
      tarjeta.append(foto, nombre, quitar);
      vistaImagenes.appendChild(tarjeta);
    });
  }

  if (selectorImagenes && inputImg) {
    selectorImagenes.addEventListener('change', function() {
      for (const archivo of Array.from(this.files)) {
        if (imagenesSeleccionadas.length >= MAX_IMAGENES) {
          alert('El límite es de 10 fotografías por artículo.');
          break;
        }
        if (!MIME_VALIDOS.includes(archivo.type)) {
          alert('El archivo "' + archivo.name + '" debe ser JPG, PNG o WEBP.');
          continue;
        }
        if (archivo.size > MAX_BYTES || archivo.size === 0) {
          alert('La fotografía "' + archivo.name + '" debe tener entre 1 byte y 2 MB.');
          continue;
        }
        const repetido = imagenesSeleccionadas.some(a => a.name === archivo.name && a.size === archivo.size && a.lastModified === archivo.lastModified);
        if (!repetido) imagenesSeleccionadas.push(archivo);
      }
      sincronizarImagenes();
      this.value = ''; // permite abrir el selector otra vez sin borrar las fotos previas
    });
  }

  // 3. Mapa con Leaflet / OpenStreetMap (sin clave de Google Maps).
  const mapaElemento = document.getElementById('mapa-entrega');
  const latitudOculta = document.getElementById('ubicacion-lat');
  const longitudOculta = document.getElementById('ubicacion-lng');
  const textoMapa = document.getElementById('estado-mapa');
  const enlaceMaps = document.getElementById('enlace-maps');
  const botonUbicacion = document.getElementById('btn-ubicacion-actual');
  const botonLimpiar = document.getElementById('btn-limpiar-ubicacion');
  let mapa = null;
  let marcador = null;

  function marcarUbicacion(lat, lng, acercar = true) {
    latitudOculta.value = Number(lat).toFixed(7);
    longitudOculta.value = Number(lng).toFixed(7);
    if (mapa) {
      if (!marcador) {
        marcador = L.marker([lat, lng], {draggable: true}).addTo(mapa);
        marcador.on('dragend', function () {
          const punto = marcador.getLatLng();
          marcarUbicacion(punto.lat, punto.lng, false);
        });
      } else marcador.setLatLng([lat, lng]);
      if (acercar) mapa.setView([lat, lng], 17);
    }
    textoMapa.textContent = '✅ Punto de entrega seleccionado. Puedes arrastrar el marcador para ajustarlo.';
    enlaceMaps.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(latitudOculta.value + ',' + longitudOculta.value);
    enlaceMaps.style.display = 'inline-block';
  }

  if (mapaElemento && window.L) {
    mapa = L.map(mapaElemento).setView([14.6349, -90.5069], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(mapa);
    mapa.on('click', e => marcarUbicacion(e.latlng.lat, e.latlng.lng));
    // Ajustar Leaflet al tamaño real del formulario.
    setTimeout(() => mapa.invalidateSize(true), 300);
    window.addEventListener('resize', () => mapa.invalidateSize(true));
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) setTimeout(() => mapa.invalidateSize(true), 150);
    });
  } else if (mapaElemento) {
    textoMapa.textContent = 'No se pudo cargar el mapa. Comprueba tu conexión a Internet.';
  }

  botonUbicacion?.addEventListener('click', function () {
    if (!navigator.geolocation) {
      textoMapa.textContent = 'Tu navegador no permite obtener la ubicación actual.';
      return;
    }
    textoMapa.textContent = 'Solicitando permiso y buscando tu ubicación...';
    navigator.geolocation.getCurrentPosition(
      posicion => {
        marcarUbicacion(posicion.coords.latitude, posicion.coords.longitude);
        textoMapa.textContent = '✅ Ubicación detectada (precisión aproximada: ' + Math.round(posicion.coords.accuracy) + ' m). Puedes ajustar el marcador.';
      },
      error => {
        const mensaje = error.code === 1 ? 'Permiso de ubicación denegado.' : 'No se pudo determinar la ubicación.';
        textoMapa.textContent = mensaje + ' Puedes elegirla manualmente en el mapa.';
      },
      {enableHighAccuracy: true, timeout: 15000, maximumAge: 0}
    );
  });
  botonLimpiar?.addEventListener('click', function () {
    latitudOculta.value = '';
    longitudOculta.value = '';
    if (mapa && marcador) { mapa.removeLayer(marcador); marcador = null; }
    enlaceMaps.style.display = 'none';
    textoMapa.textContent = 'Aún no seleccionaste una ubicación de entrega.';
  });

  form?.addEventListener('submit', function (evento) {
    if (imagenesSeleccionadas.length < 1 || imagenesSeleccionadas.length > MAX_IMAGENES) {
      evento.preventDefault();
      alert('Selecciona entre 1 y 10 fotografías antes de guardar el artículo.');
      selectorImagenes.focus();
      return;
    }
    if (!latitudOculta.value || !longitudOculta.value) {
      evento.preventDefault();
      alert('Selecciona el punto de entrega en el mapa o usa tu ubicación actual.');
      mapaElemento.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
  });

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