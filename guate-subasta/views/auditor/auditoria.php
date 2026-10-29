<?php
$tipos_evento = array_values(array_filter(array_unique(array_column($eventos, 'evento'))));
sort($tipos_evento);
?>
<div class="page-head">
  <div>
    <h1>Auditoría</h1>
    <p>Bitácora de las operaciones relevantes de la plataforma.</p>
  </div>
  <div class="audit-actions">
    <button type="button" class="ghost" onclick="window.print()">Imprimir o guardar como PDF</button>
  </div>
</div>

<section class="panel filters">
  <div class="search">
    <label for="filtro-auditoria-texto">Buscar por usuario, detalle o fecha</label>
    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" id="filtro-auditoria-texto" placeholder="Ej: cliente1, subasta, 2026-10">
  </div>
  <div>
    <label for="filtro-auditoria-evento">Tipo de evento</label>
    <select id="filtro-auditoria-evento">
      <option value="">Todos los eventos</option>
      <?php foreach($tipos_evento as $ev): ?>
        <option value="<?=e(mb_strtolower($ev))?>"><?=e(str_replace('_', ' ', $ev))?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div></div>
  <div class="count">Mostrando <strong id="contador-eventos"><?=count($eventos)?></strong> registros</div>
</section>

<?php if(empty($eventos)): ?>
  <section class="panel">
    <div class="empty"><p>Todavía no hay registros en la bitácora.</p></div>
  </section>
<?php else: ?>
  <section class="panel table-panel">
    <table id="tabla-auditoria">
      <tr>
        <th>Fecha</th>
        <th>Usuario</th>
        <th>Evento</th>
        <th>Detalle</th>
      </tr>
      <?php foreach($eventos as $x):
        $evUpper = strtoupper($x['evento'] ?? '');
        $claseEv = 'muted';
        if (str_contains($evUpper, 'ELIMINAR')) {
            $claseEv = 'cancelada';
        } elseif (str_contains($evUpper, 'DESACTIVAR') || str_contains($evUpper, 'ARCHIVAR') || str_contains($evUpper, 'CANCEL')) {
            $claseEv = 'warn';
        } elseif (str_contains($evUpper, 'CREAR') || str_contains($evUpper, 'PROGRAMAR') || str_contains($evUpper, 'REGISTRO')) {
            $claseEv = '';
        } elseif (str_contains($evUpper, 'PUJA')) {
            $claseEv = 'info';
        }
        $usuarioNombre = $x['usuario'] ?? 'Sistema';
        $rolUsuario = $x['rol_usuario'] ?? 'N/A';
        $tsFecha = strtotime((string)($x['fecha'] ?? ''));
      ?>
        <tr class="fila-evento"
            data-busqueda="<?=e(mb_strtolower($usuarioNombre.' '.$rolUsuario.' '.($x['detalle'] ?? '').' '.($x['fecha'] ?? '').' '.($tsFecha ? date('d/m/Y', $tsFecha) : '')))?>"
            data-evento="<?=e(mb_strtolower($x['evento'] ?? ''))?>">
          <td>
            <?=e($tsFecha ? date('d/m/Y h:i:s A', $tsFecha) : (string)$x['fecha'])?><br>
            <small class="muted">Registro #<?=(int)$x['id']?></small>
          </td>
          <td>
            <strong><?=e($usuarioNombre)?></strong><br>
            <small class="muted"><?=e($rolUsuario)?></small>
          </td>
          <td><span class="badge <?=$claseEv?>"><?=e(str_replace('_', ' ', (string)$x['evento']))?></span></td>
          <td class="cell-wrap"><?=e($x['detalle'])?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <div id="auditoria-sin-resultados" class="empty" style="display:none;"><p>Ningún registro coincide con el filtro.</p></div>
  </section>
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