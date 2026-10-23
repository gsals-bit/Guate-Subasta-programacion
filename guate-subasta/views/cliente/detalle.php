<?php
$lider = $historial[0] ?? null;
$esPropietario = isset($_SESSION['user']['id'], $subasta['creado_por']) && ((int)$_SESSION['user']['id'] === (int)$subasta['creado_por']);

$esCerrada = in_array(strtolower($subasta['estado'] ?? ''), ['cerrada', 'finalizada']) || strtotime($subasta['fecha_fin']) <= time();
$estadoReal = $esCerrada ? 'cerrada' : (empty($subasta['estado']) ? 'activa' : strtolower($subasta['estado']));

$colorBadge = '#16a34a'; 
if ($estadoReal === 'cerrada') $colorBadge = '#dc2626'; 
if ($estadoReal === 'programada') $colorBadge = '#d97706'; 

$fechaFinFormateada = date('d/m/Y h:i:s A', strtotime($subasta['fecha_fin']));
$fechaInicioFormateada = date('d/m/Y h:i:s A', strtotime($subasta['fecha_inicio']));

// Extraer el arreglo de imágenes
$imagenes = json_decode($subasta['imagen'] ?? '[]', true);
?>
<div class="grid2">
  <section class="panel">
    <h1><?=e($subasta['articulo'])?></h1>
    
    <span class="badge" style="background: <?=$colorBadge?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; display: inline-block; text-transform: capitalize; margin-bottom: 10px;">
      <?=e($estadoReal)?>
    </span>

    <!-- Galería horizontal de imágenes -->
    <?php if(!empty($imagenes) && is_array($imagenes)): ?>
      <div style="display:flex; gap:10px; margin: 15px 0; overflow-x:auto; padding-bottom:10px;">
        <?php foreach($imagenes as $img): ?>
          <img src="uploads/<?=e($img)?>" alt="Imagen de <?=e($subasta['articulo'])?>" style="height: 250px; width: auto; max-width: 100%; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <?php endforeach; ?>
      </div>
    <?php else: ?>
       <div style="width:100%; height:150px; background:#f3f4f6; border-radius:6px; margin:15px 0; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-style:italic; border:1px solid #e5e7eb;">Sin imágenes registradas</div>
    <?php endif; ?>
    
    <p><strong>Categoría:</strong> <?=e($subasta['categoria'])?></p>
    <p><strong>Descripción:</strong> <?=e($subasta['descripcion'])?></p>
    <p><strong>Modelo:</strong> <?=e($subasta['modelo'] ?: 'N/A')?></p>
    <hr style="margin:15px 0;">
    <p><strong>Finaliza:</strong> <?=e($fechaFinFormateada)?></p>

    <?php if($estadoReal !== 'cerrada'): ?>
      <p><strong>Tiempo restante:</strong> <span id="contador-subasta" data-fin="<?=e(str_replace(' ', 'T', $subasta['fecha_fin']))?>" style="font-weight:bold; color:#fbbf24;">Calculando...</span></p>
    <?php endif; ?>

    <h3>Precio Actual: Q <?=number_format($subasta['monto_actual'], 2)?></h3>
    <p><small>Incremento mínimo requerido: Q <?=number_format($subasta['incremento_minimo'], 2)?></small></p>

    <?php if($estadoReal === 'activa' && $lider): ?>
      <div style="background:#1e3a8a; color:#dbeafe; padding:10px; border-radius:4px; margin-top:12px;">
        <strong>Oferta líder actual:</strong> <?=e($lider['usuario'])?> con Q <?=number_format($lider['monto'], 2)?>
      </div>
    <?php endif; ?>
    
    <?php if($estadoReal === 'activa'): ?>
      <?php if($esPropietario): ?>
        <div style="background:#374151; color:#f3f4f6; padding:10px; border-radius:4px; margin-top:20px;">
          Registraste este artículo. No puedes realizar ofertas en tu propia subasta.
        </div>
      <?php else: ?>
        <form method="post" action="index.php?action=pujar" style="margin-top:20px;">
          <input type="hidden" name="csrf" value="<?=csrf_token()?>">
          <input type="hidden" name="subasta_id" value="<?=(int)$subasta['id']?>">
          <label>Monto de tu puja (Q)</label>
          <input type="number" step=".01" min="<?=($subasta['monto_actual'] + $subasta['incremento_minimo'])?>" value="<?=number_format($subasta['monto_actual'] + $subasta['incremento_minimo'], 2, '.', '')?>" name="monto" required>
          <button type="submit">Realizar Puja</button>
        </form>
      <?php endif; ?>
    <?php elseif($estadoReal === 'programada'): ?>
      <div style="background:#fef3c7; color:#92400e; padding:10px; border-radius:4px; margin-top:20px;">
        Esta subasta aún no inicia. Podrás pujar a partir de: <?=e($fechaInicioFormateada)?>.
      </div>
    <?php else: ?>
      <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:4px; margin-top:20px;">
        <strong>Esta subasta ya finalizó.</strong><br>
        <?php if($lider): ?>
          Adjudicada a: <strong><?=e($lider['usuario'])?></strong> por un monto final de <strong>Q <?=number_format($lider['monto'], 2)?></strong>.
        <?php else: ?>
          La subasta cerró sin recibir ofertas.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if($estadoReal === 'cerrada' && in_array($_SESSION['user']['rol'], ['Subastador', 'Administrador'])): ?>
      <form method="post" action="index.php?action=eliminar_subasta" onsubmit="return confirm('¿Seguro que deseas eliminar esta subasta cerrada?');" style="margin-top:15px;">
        <input type="hidden" name="csrf" value="<?=csrf_token()?>">
        <input type="hidden" name="subasta_id" value="<?=(int)$subasta['id']?>">
        <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:8px 12px; border-radius:4px; cursor:pointer; width:100%;">Eliminar Subasta</button>
      </form>
    <?php endif; ?>
  </section>

  <section>
    <h2>Historial de Pujas</h2>
    <?php if(empty($historial)): ?>
      <p>Aún no hay pujas para este artículo. ¡Sé el primero!</p>
    <?php else: ?>
      <table>
        <tr>
          <th>Posición</th>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Monto</th>
        </tr>
        <?php foreach($historial as $index => $h): ?>
          <tr>
            <td><?= $index === 0 ? '<strong>1 (Líder)</strong>' : ($index + 1) ?></td>
            <td><?=e(date('d/m/Y h:i:s A', strtotime($h['fecha'])))?></td>
            <td><?=e($h['usuario'])?></td>
            <td>Q <?=number_format($h['monto'], 2)?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </section>
</div>

<?php if($estadoReal !== 'cerrada'): ?>
<script>
(function() {
  const el = document.getElementById('contador-subasta');
  if (!el) return;
  const fechaFin = new Date(el.getAttribute('data-fin')).getTime();
  let recargando = false;

  const actualizarContador = function() {
    const ahora = new Date().getTime();
    const diferencia = fechaFin - ahora;

    if (diferencia <= 0) {
      el.textContent = 'Tiempo agotado. Actualizando estado...';
      if (!recargando) {
        recargando = true;
        setTimeout(function() { window.location.reload(); }, 1500);
      }
      return false;
    }

    const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
    const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
    const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

    let texto = '';
    if (dias > 0) texto += dias + 'd ';
    texto += String(horas).padStart(2, '0') + 'h ' + String(minutos).padStart(2, '0') + 'm ' + String(segundos).padStart(2, '0') + 's';
    el.textContent = texto;
    return true;
  };

  if (actualizarContador()) {
    const intervalo = setInterval(function() {
      if (!actualizarContador()) clearInterval(intervalo);
    }, 1000);
  }
})();
</script>
<?php endif; ?>