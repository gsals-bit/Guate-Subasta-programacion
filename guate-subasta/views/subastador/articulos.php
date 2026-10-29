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

$esAdminUsr = ($_SESSION['user']['rol'] ?? '') === 'Administrador';

// Primera imagen del artículo (el campo guarda un JSON con nombres de archivo en /uploads)
$primeraImg = function ($raw): ?string {
    if (empty($raw)) return null;
    $lista = json_decode((string)$raw, true);
    $nombre = is_array($lista) ? ($lista[0] ?? null) : (string)$raw;
    return $nombre ? 'uploads/' . rawurlencode(basename($nombre)) : null;
};
?>
<!-- Mapa (Leaflet). Las reglas de abajo evitan que las baldosas se salgan del mapa si el CSS del CDN se bloquea. -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoQ2/tR0dRlyto=" crossorigin="">
<style>
  #mapa-entrega {width:100%;height:350px;min-height:300px;border:1px solid #d3d8e6;border-radius:12px;z-index:0;margin:10px 0}
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
<p>Registra lo que quieres subastar y revisa el estado de tus artículos.</p>

<div class="articles-grid">

  <!-- ===== Formulario de nuevo artículo ===== -->
  <section class="panel">
    <h2>Nuevo artículo</h2>
    <form method="post" action="index.php?action=articulo" enctype="multipart/form-data" id="form-articulo">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">

      <label for="in-nombre" style="margin-top:0">Nombre</label>
      <input id="in-nombre" name="nombre" required>

      <label for="input-categoria">Categoría</label>
      <input name="categoria" id="input-categoria" list="lista-categorias" autocomplete="off" placeholder="Elige una sugerencia o escribe una nueva" required>
      <datalist id="lista-categorias">
        <?php foreach($todas_categorias as $cat): ?>
          <option value="<?=e($cat)?>">
        <?php endforeach; ?>
      </datalist>

      <label for="input-descripcion">Descripción</label>
      <textarea name="descripcion" id="input-descripcion" maxlength="500" rows="4" required style="resize:none"></textarea>
      <div class="char-count" id="contador-desc">0 / 500 caracteres</div>

      <div class="form-row">
        <div>
          <label for="in-estado">Estado físico</label>
          <select id="in-estado" name="estado_fisico" required>
            <option value="Excelente">Excelente</option>
            <option value="Bueno">Bueno</option>
            <option value="Regular">Regular</option>
          </select>
        </div>
        <div>
          <label for="in-precio-visible">Precio base (Q)</label>
          <input id="in-precio-visible" type="text" inputmode="decimal" autocomplete="off" placeholder="0.00" required>
          <input id="in-precio" type="hidden" name="precio_base">
        </div>
      </div>

      <label for="in-modelo">Modelo</label>
      <input id="in-modelo" name="modelo" placeholder="Ej: 2024, V5, etc.">

      <div class="form-section">
        <h3>Fotografías</h3>
        <p class="hint">De 1 a 10 fotos, JPG, PNG o WEBP, máximo 2 MB cada una. Puedes agregarlas por tandas sin perder las anteriores.</p>
        <label for="selector-imagenes" class="visually-hidden">Seleccionar fotografías</label>
        <input type="file" id="selector-imagenes" accept="image/jpeg,image/png,image/webp" multiple>
        <input type="file" name="imagenes[]" id="input-imagenes" accept="image/jpeg,image/png,image/webp" multiple tabindex="-1" aria-hidden="true" style="display:none;">
        <p id="contador-imagenes" class="hint" aria-live="polite">0 de 10 fotografías seleccionadas.</p>
        <div id="vista-imagenes" class="foto-preview" aria-label="Vista previa de imágenes"></div>
      </div>

      <div class="form-section">
        <h3>Punto de entrega</h3>
        <label for="ubicacion-nombre">Dirección o referencia (la verá quien puje)</label>
        <input name="ubicacion_nombre" id="ubicacion-nombre" placeholder="Ej. Centro comercial o punto público de entrega" maxlength="200">
        <p class="hint" style="margin-top:12px">Haz clic en el mapa, arrastra el marcador o usa tu ubicación actual. Elige un lugar público; la ubicación actual requiere permiso del navegador y puede ser aproximada.</p>
        <div class="mapa-acciones">
          <button type="button" id="btn-ubicacion-actual" class="sm">Usar mi ubicación actual</button>
          <button type="button" id="btn-limpiar-ubicacion" class="sm ghost">Limpiar selección</button>
        </div>
        <div id="mapa-entrega" role="application" aria-label="Mapa para seleccionar punto de entrega"></div>
        <p id="estado-mapa" class="hint" aria-live="polite">Aún no seleccionaste una ubicación de entrega.</p>
        <a id="enlace-maps" href="#" target="_blank" rel="noopener noreferrer" style="display:none;">Ver el punto en Google Maps</a>
        <!-- Estos campos no se muestran; mantienen compatibilidad con index.php y Articulo.php. -->
        <input type="hidden" name="ubicacion_lat" id="ubicacion-lat">
        <input type="hidden" name="ubicacion_lng" id="ubicacion-lng">
      </div>

      <button type="submit" class="gold block">Guardar artículo</button>
    </form>
  </section>

  <!-- ===== Listado ===== -->
  <section class="panel">
    <h2><?= $esAdminUsr ? 'Artículos registrados' : 'Mis artículos' ?></h2>

    <?php if($esAdminUsr && !empty($articulos)): ?>
      <form method="post" action="index.php?action=archivar_articulos" id="form-archivar" class="admin-bar" onsubmit="return confirm('¿Archivar los artículos seleccionados? Sus pujas y pagos se conservarán.');">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <p>Marca los artículos que quieres retirar del catálogo.</p>
        <button type="button" class="sm ghost" onclick="document.querySelectorAll('.archivar-check').forEach(x=>x.checked=true)">Seleccionar todos</button>
        <button type="submit" class="sm danger">Archivar seleccionados</button>
      </form>
    <?php endif; ?>

    <?php if(empty($articulos)): ?>
      <div class="empty"><p>Todavía no has registrado ningún artículo. Completa el formulario para añadir el primero.</p></div>
    <?php else: ?>
      <div class="art-list">
      <?php foreach($articulos as $a):
        // Estado de la subasta del artículo
        $estSubasta = strtolower(trim($a['subasta_estado'] ?? ''));
        $fechaFin = $a['subasta_fecha_fin'] ?? '';

        if (empty($estSubasta)) {
            $estadoMostrar = 'Sin programar'; $claseBadge = 'muted';
        } else {
            $vencida = !empty($fechaFin) && strtotime($fechaFin) <= time();
            if ($estSubasta === 'cerrada' || $estSubasta === 'finalizada' || $vencida) {
                $estadoMostrar = 'Cerrada'; $claseBadge = 'cerrada';
            } elseif ($estSubasta === 'programada') {
                $estadoMostrar = 'Programada'; $claseBadge = 'programada';
            } else {
                $estadoMostrar = 'Activa'; $claseBadge = '';
            }
        }

        $esPropietario = $esAdminUsr || (isset($a['creado_por']) && (int)$a['creado_por'] === (int)$_SESSION['user']['id']);
        $miniatura = $primeraImg($a['imagen'] ?? null);
      ?>
        <div class="art-row">
          <?php if($esAdminUsr): ?>
            <input class="archivar-check" type="checkbox" form="form-archivar" name="ids[]" value="<?=(int)$a['id']?>" aria-label="Seleccionar <?=e($a['nombre'])?>">
          <?php endif; ?>
          <div class="lot-tile">
            <?php if($miniatura): ?><img src="<?=e($miniatura)?>" alt="" loading="lazy"><?php else: ?><?=e(mb_strtoupper(mb_substr($a['nombre'], 0, 1)))?><?php endif; ?>
          </div>
          <div class="art-main">
            <b><?=e($a['nombre'])?></b>
            <small><?=e($a['categoria'])?>, base Q <?=number_format((float)$a['precio_base'], 2)?></small>
          </div>
          <span class="badge <?=e($claseBadge)?>"><?=e($estadoMostrar)?></span>
          <div class="art-actions">
            <button type="button" class="sm ghost btn-ver-detalles"
                    data-nombre="<?=e($a['nombre'])?>"
                    data-categoria="<?=e($a['categoria'])?>"
                    data-precio="<?=number_format((float)$a['precio_base'], 2)?>"
                    data-estado-fisico="<?=e($a['estado_fisico'] ?? 'N/A')?>"
                    data-modelo="<?=e($a['modelo'] ?: 'N/A')?>"
                    data-descripcion="<?=e($a['descripcion'] ?: 'Sin descripción')?>"
                    data-estado-subasta="<?=e($estadoMostrar)?>"
                    data-clase-estado="<?=e($claseBadge)?>"
                    data-subasta-id="<?=(int)($a['subasta_id'] ?? 0)?>"
                    data-imagenes="<?=e($a['imagen'] ?? '[]')?>">Ver detalles</button>
            <?php if($esPropietario): ?>
              <form method="post" action="index.php?action=eliminar_articulo" onsubmit="return confirm('¿Seguro que deseas eliminar este artículo?');">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="id" value="<?=(int)$a['id']?>">
                <button type="submit" class="sm danger">Eliminar</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<!-- Ventana con los detalles del artículo -->
<div id="modal-detalle-articulo" class="modal-back" role="dialog" aria-modal="true" aria-labelledby="modal-art-nombre">
  <div class="modal-box">
    <button type="button" id="cerrar-modal-articulo" class="modal-close" aria-label="Cerrar">&times;</button>
    <h2 id="modal-art-nombre" style="margin-right:36px"></h2>
    <span id="modal-art-badge" class="badge"></span>
    <div id="modal-art-galeria" class="modal-gallery"></div>
    <dl class="facts">
      <dt>Categoría</dt><dd id="modal-art-categoria"></dd>
      <dt>Precio base</dt><dd>Q <span id="modal-art-precio"></span></dd>
      <dt>Estado físico</dt><dd id="modal-art-fisico"></dd>
      <dt>Modelo</dt><dd id="modal-art-modelo"></dd>
    </dl>
    <h3 style="margin:18px 0 6px;font-size:1rem">Descripción</h3>
    <p id="modal-art-descripcion" class="modal-desc"></p>
    <div class="modal-foot">
      <a id="modal-art-link-subasta" class="button sm gold" href="#" style="display:none;">Ir a la subasta</a>
      <button type="button" id="btn-cerrar-inferior" class="sm ghost">Cerrar</button>
    </div>
  </div>
</div>

<script>
(function() {
  // 1. Contador de caracteres de la descripción
  const inputDesc = document.getElementById('input-descripcion');
  const contadorDesc = document.getElementById('contador-desc');

  if (inputDesc && contadorDesc) {
    inputDesc.addEventListener('input', function() {
      const longitud = this.value.length;
      contadorDesc.textContent = longitud + ' / 500 caracteres';
      contadorDesc.style.color = longitud >= 450 ? '#d6403f' : (longitud >= 250 ? '#b26a00' : '#0f9d74');
      contadorDesc.style.fontWeight = longitud >= 450 ? '700' : '400';
    });
  }

  // 1b. Precio base con comas de millares mientras se escribe (50000 -> 50,000).
  // Al servidor llega solo el número limpio, a través del campo oculto "precio_base".
  const precioVisible = document.getElementById('in-precio-visible');
  const precioReal = document.getElementById('in-precio');

  function formatearPrecio() {
    const el = precioVisible;
    const pos = el.selectionStart;
    let antes = el.value.slice(0, pos).replace(/[^0-9.]/g, '').length;

    const limpio = el.value.replace(/[^0-9.]/g, '');
    const i = limpio.indexOf('.');
    let entero = (i === -1 ? limpio : limpio.slice(0, i)).replace(/^0+(?=\d)/, '').slice(0, 10);
    const decimal = i === -1 ? null : limpio.slice(i + 1).replace(/\./g, '').slice(0, 2);
    if (entero === '' && decimal !== null) { entero = '0'; antes += 1; }

    const texto = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (decimal !== null ? '.' + decimal : '');
    el.value = texto;

    // Mantener el cursor junto al mismo dígito después de insertar las comas
    let nueva = texto.length;
    if (antes === 0) {
      nueva = 0;
    } else {
      let cuenta = 0;
      for (let k = 0; k < texto.length; k++) {
        if (/[0-9.]/.test(texto[k])) cuenta++;
        if (cuenta === antes) { nueva = k + 1; break; }
      }
    }
    el.setSelectionRange(nueva, nueva);

    precioReal.value = entero + (decimal !== null ? '.' + decimal : '');
    const n = parseFloat(precioReal.value);
    el.setCustomValidity(precioReal.value !== '' && !(n >= 0.01) ? 'Ingresa un precio mayor a 0.' : '');
  }

  if (precioVisible && precioReal) {
    precioVisible.addEventListener('input', formatearPrecio);
    precioVisible.addEventListener('blur', function () {
      // Al salir del campo se muestran siempre dos decimales (50,000 -> 50,000.00)
      if (precioReal.value === '') return;
      const n = parseFloat(precioReal.value);
      if (isNaN(n)) return;
      precioVisible.value = n.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      precioReal.value = n.toFixed(2);
    });
  }

  // 2. Imágenes acumulables (de 1 a 10), con vista previa y botón para quitar
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

  // 3. Mapa con Leaflet / OpenStreetMap (sin clave de Google Maps)
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
    textoMapa.textContent = 'Punto de entrega seleccionado. Puedes arrastrar el marcador para ajustarlo.';
    enlaceMaps.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(latitudOculta.value + ',' + longitudOculta.value);
    enlaceMaps.style.display = 'inline-block';
  }

  if (mapaElemento && window.L) {
    mapa = L.map(mapaElemento).setView([14.6349, -90.5069], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
    }).addTo(mapa);
    mapa.on('click', e => marcarUbicacion(e.latlng.lat, e.latlng.lng));
    // Ajustar Leaflet al tamaño real del formulario
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
        textoMapa.textContent = 'Ubicación detectada (precisión aproximada: ' + Math.round(posicion.coords.accuracy) + ' m). Puedes ajustar el marcador.';
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

  // 4. Ventana "Ver detalles"
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
  document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModal(); });

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
      badge.className = 'badge ' + (this.getAttribute('data-clase-estado') || '');

      // Imágenes del artículo
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
          elImg.alt = '';
          galeria.appendChild(elImg);
        });
      } else {
        galeria.innerHTML = '<div class="no-image" style="height:120px;width:100%">Este artículo no tiene imágenes</div>';
      }

      // Enlace a la subasta, si ya existe una
      const subastaId = parseInt(this.getAttribute('data-subasta-id') || '0', 10);
      const linkSubasta = document.getElementById('modal-art-link-subasta');
      if (subastaId > 0) {
        linkSubasta.href = 'index.php?page=detalle&id=' + subastaId;
        linkSubasta.style.display = 'inline-flex';
      } else {
        linkSubasta.style.display = 'none';
      }

      modal.style.display = 'flex';
    });
  });
})();
</script>