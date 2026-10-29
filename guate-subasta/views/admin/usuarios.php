<?php
$rolesDisponibles = ['Cliente', 'Subastador', 'Auditor', 'Administrador'];
$idSesionActual = (int)($_SESSION['user']['id'] ?? 0);
?>
<div class="page-head">
  <div>
    <h1>Usuarios y roles</h1>
    <p>Administra las cuentas registradas, sus permisos y su acceso.</p>
  </div>
</div>

<section class="panel filters">
  <div class="search">
    <label for="filtro-usuario-texto">Buscar por nombre o correo</label>
    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" id="filtro-usuario-texto" placeholder="Nombre o correo">
  </div>
  <div>
    <label for="filtro-usuario-rol">Rol</label>
    <select id="filtro-usuario-rol">
      <option value="">Todos los roles</option>
      <?php foreach($rolesDisponibles as $r): ?>
        <option value="<?=e(mb_strtolower($r))?>"><?=e($r)?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="filtro-usuario-activo">Estado de la cuenta</label>
    <select id="filtro-usuario-activo">
      <option value="">Todos</option>
      <option value="1">Activos</option>
      <option value="0">Inactivos</option>
    </select>
  </div>
  <div class="count" id="conteo-usuarios" aria-live="polite"></div>
</section>

<section class="panel table-panel">
  <table>
    <tr>
      <th>Usuario</th>
      <th>Rol</th>
      <th>Estado</th>
      <th>Permisos y acceso</th>
    </tr>
    <?php foreach($usuarios as $u):
      $esMismoUsuario = ((int)$u['id'] === $idSesionActual);
      $activoInt = (int)($u['activo'] ?? 1);
    ?>
      <tr class="fila-usuario"
          data-texto="<?=e(mb_strtolower(($u['nombre'] ?? '').' '.($u['email'] ?? '')))?>"
          data-rol="<?=e(mb_strtolower($u['rol'] ?? ''))?>"
          data-activo="<?=$activoInt?>">
        <td>
          <div class="user-cell">
            <span class="avatar lg"><?=e(mb_strtoupper(mb_substr($u['nombre'] ?? '?', 0, 1)))?></span>
            <span class="user-info">
              <b><?=e($u['nombre'])?><?php if($esMismoUsuario): ?><span class="lead-tag">Tu cuenta</span><?php endif; ?></b>
              <small><?=e($u['email'])?> &middot; #<?=(int)$u['id']?></small>
            </span>
          </div>
        </td>
        <td><span class="badge rol-<?=e(mb_strtolower($u['rol'] ?? 'cliente'))?>"><?=e($u['rol'])?></span></td>
        <td>
          <?php if($activoInt === 1): ?>
            <span class="badge">Activo</span>
          <?php else: ?>
            <span class="badge cancelada">Inactivo</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if($esMismoUsuario): ?>
            <small class="muted">Tu sesión actual está protegida.</small>
          <?php else: ?>
            <div class="row-actions wrap">
              <form method="post" action="index.php?action=actualizar_usuario" class="row-actions">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="usuario_id" value="<?=(int)$u['id']?>">
                <select name="rol" aria-label="Rol de <?=e($u['nombre'])?>">
                  <?php foreach($rolesDisponibles as $r): ?>
                    <option value="<?=e($r)?>" <?=($u['rol'] === $r) ? 'selected' : ''?>><?=e($r)?></option>
                  <?php endforeach; ?>
                </select>
                <select name="activo" aria-label="Estado de <?=e($u['nombre'])?>">
                  <option value="1" <?=$activoInt === 1 ? 'selected' : ''?>>Activo</option>
                  <option value="0" <?=$activoInt === 0 ? 'selected' : ''?>>Inactivo</option>
                </select>
                <button type="submit" class="sm">Guardar</button>
              </form>
              <form method="post" action="index.php?action=eliminar_usuario" onsubmit="return confirm('¿Desactivar este usuario? Se conservará el historial.');">
                <input type="hidden" name="csrf" value="<?=csrf_token()?>">
                <input type="hidden" name="usuario_id" value="<?=(int)$u['id']?>">
                <button type="submit" class="sm danger">Quitar acceso</button>
              </form>
            </div>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <div id="usuarios-sin-resultados" class="empty" style="display:none;"><p>Ningún usuario coincide con esos criterios.</p></div>
</section>

<script>
(function() {
  const inputTxt = document.getElementById('filtro-usuario-texto');
  const selectRol = document.getElementById('filtro-usuario-rol');
  const selectActivo = document.getElementById('filtro-usuario-activo');
  const filas = document.querySelectorAll('.fila-usuario');
  const msgVacio = document.getElementById('usuarios-sin-resultados');
  const conteo = document.getElementById('conteo-usuarios');

  function filtrarUsuarios() {
    const txt = (inputTxt.value || '').toLowerCase().trim();
    const rol = (selectRol.value || '').toLowerCase().trim();
    const act = selectActivo.value;
    let visibles = 0;

    filas.forEach(function(f) {
      const okTxt = !txt || f.getAttribute('data-texto').includes(txt);
      const okRol = !rol || f.getAttribute('data-rol') === rol;
      const okAct = act === '' || f.getAttribute('data-activo') === act;

      if (okTxt && okRol && okAct) {
        f.style.display = '';
        visibles++;
      } else {
        f.style.display = 'none';
      }
    });

    if (msgVacio) msgVacio.style.display = visibles === 0 ? 'block' : 'none';
    if (conteo) conteo.textContent = visibles + (visibles === 1 ? ' usuario' : ' usuarios');
  }

  inputTxt.addEventListener('input', filtrarUsuarios);
  selectRol.addEventListener('change', filtrarUsuarios);
  selectActivo.addEventListener('change', filtrarUsuarios);
  filtrarUsuarios();
})();
</script>