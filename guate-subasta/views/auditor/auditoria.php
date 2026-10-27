<?php
$tipos_evento = array_values(array_filter(array_unique(array_column($eventos, 'evento'))));
sort($tipos_evento);
?>
<h1>Auditoría</h1>
<div class="audit-actions"><button type="button" onclick="window.print()">🖨 Imprimir bitácora / Guardar como PDF</button></div>
<p>Bitácora de operaciones relevantes de la plataforma.</p>

<section class="panel" style="margin-bottom:20px; padding:15px;">
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; align-items:end;">
    <div>
      <label style="margin-top:0;">Buscar por usuario, detalle o fecha</label>
      <input type="text" id="filtro-auditoria-texto" placeholder="Ej: cliente1, Subasta, 2026-10...">
    </div>
    <div>
      <label style="margin-top:0;">Tipo de evento</label>
      <select id="filtro-auditoria-evento">
        <option value="">Todos los eventos</option>
        <?php foreach($tipos_evento as $ev): ?>
          <option value="<?=e(mb_strtolower($ev))?>"><?=e($ev)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="text-align:right; font-size:0.9em; opacity:0.85; padding-bottom:8px;">
      Mostrando <strong id="contador-eventos"><?=count($eventos)?></strong> registros
    </div>
  </div>
</section>

<?php if(empty($eventos)): ?>
  <section class="panel">
    <p>No existen registros en la bitácora de auditoría.</p>
  </section>
<?php else: ?>
  <table id="tabla-auditoria">
    <tr>
      <th>ID</th>
      <th>Fecha</th>
      <th>Usuario</th>
      <th>Rol</th>
      <th>Evento</th>
      <th>Detalle</th>
    </tr>
    <?php foreach($eventos as $x): 
      $evUpper = strtoupper($x['evento'] ?? '');
      $bgBadge = '#374151';
      if (str_contains($evUpper, 'ELIMINAR')) {
          $bgBadge = '#dc2626';
      } elseif (str_contains($evUpper, 'CREAR') || str_contains($evUpper, 'PROGRAMAR') || str_contains($evUpper, 'REGISTRO')) {
          $bgBadge = '#16a34a';
      } elseif (str_contains($evUpper, 'PUJA')) {
          $bgBadge = '#2563eb';
      }
      $usuarioNombre = $x['usuario'] ?? 'Sistema';
      $rolUsuario = $x['rol_usuario'] ?? 'N/A';
    ?>
      <tr class="fila-evento"
          data-busqueda="<?=e(mb_strtolower($usuarioNombre.' '.$rolUsuario.' '.($x['detalle'] ?? '').' '.($x['fecha'] ?? '')))?>"
          data-evento="<?=e(mb_strtolower($x['evento'] ?? ''))?>">
        <td>#<?=(int)$x['id']?></td>
        <td><?=e($x['fecha'])?></td>
        <td><strong><?=e($usuarioNombre)?></strong></td>
        <td><?=e($rolUsuario)?></td>
        <td>
          <span style="background:<?=$bgBadge?>; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8em; font-weight:bold; display:inline-block;">
            <?=e($x['evento'])?>
          </span>
        </td>
        <td><?=e($x['detalle'])?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <p id="auditoria-sin-resultados" style="display:none; opacity:0.8; margin-top:15px;">No se encontraron registros que coincidan con el filtro.</p>
<?php endif; ?>

<script>
(function() {
  const inputTexto = document.getElementById('filtro-auditoria-texto');
  const selectEvento = document.getElementById('filtro-auditoria-evento');
  const filas = document.querySelectorAll('.fila-evento');
  const contador = document.getElementById('contador-eventos');
  const msgVacio = document.getElementById('auditoria-sin-resultados');

  if (!inputTexto || !selectEvento) return;

  function filtrarBitacora() {
    const txt = (inputTexto.value || '').toLowerCase().trim();
    const ev = (selectEvento.value || '').toLowerCase().trim();
    let visibles = 0;

    filas.forEach(function(fila) {
      const coincideTxt = !txt || fila.getAttribute('data-busqueda').includes(txt);
      const coincideEv = !ev || fila.getAttribute('data-evento') === ev;

      if (coincideTxt && coincideEv) {
        fila.style.display = '';
        visibles++;
      } else {
        fila.style.display = 'none';
      }
    });

    if (contador) contador.textContent = visibles;
    if (msgVacio) msgVacio.style.display = visibles === 0 ? 'block' : 'none';
  }

  inputTexto.addEventListener('input', filtrarBitacora);
  selectEvento.addEventListener('change', filtrarBitacora);
})();
</script>
