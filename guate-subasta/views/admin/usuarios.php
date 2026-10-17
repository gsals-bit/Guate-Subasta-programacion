<?php
$rolesDisponibles = ['Cliente', 'Subastador', 'Auditor', 'Administrador'];
$idSesionActual = (int)($_SESSION['user']['id'] ?? 0);
?>
<h1>Usuarios y roles</h1>
<p>Administración de cuentas registradas, asignación de permisos y estado de acceso.</p>

<section class="panel" style="margin-bottom:20px; padding:15px;">
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; align-items:end;">
    <div>
      <label style="margin-top:0;">Buscar por nombre o correo</label>
      <input type="text" id="filtro-usuario-texto" placeholder="Escribe nombre o email...">
    </div>
    <div>
      <label style="margin-top:0;">Filtrar por rol</label>
      <select id="filtro-usuario-rol">
        <option value="">Todos los roles</option>
        <?php foreach($rolesDisponibles as $r): ?>
          <option value="<?=e(mb_strtolower($r))?>"><?=e($r)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="margin-top:0;">Estado de cuenta</label>
      <select id="filtro-usuario-activo">
        <option value="">Todos</option>
        <option value="1">Activos</option>
        <option value="0">Inactivos</option>
      </select>
    </div>
  </div>
</section>

<table>
  <tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Correo</th>
    <th>Rol actual</th>
    <th>Estado</th>
    <th>Actualizar permisos / acceso</th>
  </tr>
  <?php foreach($usuarios as $u): 
    $esMismoUsuario = ((int)$u['id'] === $idSesionActual);
    $activoInt = (int)($u['activo'] ?? 1);
  ?>
    <tr class="fila-usuario"
        data-texto="<?=e(mb_strtolower(($u['nombre'] ?? '').' '.($u['email'] ?? '')))?>"
        data-rol="<?=e(mb_strtolower($u['rol'] ?? ''))?>"
        data-activo="<?=$activoInt?>">
      <td>#<?=(int)$u['id']?></td>
      <td>
        <strong><?=e($u['nombre'])?></strong>
        <?php if($esMismoUsuario): ?>
          <small style="opacity:0.75;">(Tu cuenta)</small>
        <?php endif; ?>
      </td>
      <td><?=e($u['email'])?></td>
      <td><span class="badge"><?=e($u['rol'])?></span></td>
      <td>
        <?php if($activoInt === 1): ?>
          <span style="background:#16a34a; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8em; font-weight:bold;">Activo</span>
        <?php else: ?>
          <span style="background:#dc2626; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8em; font-weight:bold;">Inactivo</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if($esMismoUsuario): ?>
          <small style="opacity:0.75;">Sesión actual protegida</small>
        <?php else: ?>
          <form method="post" action="index.php?action=actualizar_usuario" style="display:flex; gap:8px; align-items:center; margin:0;">
            <input type="hidden" name="csrf" value="<?=csrf_token()?>">
            <input type="hidden" name="usuario_id" value="<?=(int)$u['id']?>">
            <select name="rol" style="margin:0; padding:6px; width:auto;">
              <?php foreach($rolesDisponibles as $r): ?>
                <option value="<?=e($r)?>" <?=($u['rol'] === $r) ? 'selected' : ''?>><?=e($r)?></option>
              <?php endforeach; ?>
            </select>
            <select name="activo" style="margin:0; padding:6px; width:auto;">
              <option value="1" <?=$activoInt === 1 ? 'selected' : ''?>>Activo</option>
              <option value="0" <?=$activoInt === 0 ? 'selected' : ''?>>Inactivo</option>
            </select>
            <button type="submit" style="padding:6px 12px; margin:0;">Guardar</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<p id="usuarios-sin-resultados" style="display:none; opacity:0.8; margin-top:15px;">No se encontraron usuarios con esos criterios.</p>

<script>
(function() {
  const inputTxt = document.getElementById('filtro-usuario-texto');
  const selectRol = document.getElementById('filtro-usuario-rol');
  const selectActivo = document.getElementById('filtro-usuario-activo');
  const filas = document.querySelectorAll('.fila-usuario');
  const msgVacio = document.getElementById('usuarios-sin-resultados');

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
  }

  inputTxt.addEventListener('input', filtrarUsuarios);
  selectRol.addEventListener('change', filtrarUsuarios);
  selectActivo.addEventListener('change', filtrarUsuarios);
})();
</script>