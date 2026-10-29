<?php if (!isset($ic, $icons)) { $ic = fn($d) => ''; $icons = []; } ?>
<div class="auth-split">
  <div class="auth-aside">
    <div class="brand-mark"><?=$ic($icons['gavel'] ?? '')?></div>
    <h2>Subastas privadas de artículos exclusivos</h2>
    <p>Puja, sigue la cuenta regresiva y gana antes de que cierre.</p>
    <ul class="auth-points">
      <li><?=$ic($icons['calendar'] ?? '')?><span>Cada subasta dura como máximo 30 minutos.</span></li>
      <li><?=$ic($icons['bell'] ?? '')?><span>Te avisamos cuando se programa una subasta nueva o cambia un horario.</span></li>
      <li><?=$ic($icons['shield'] ?? '')?><span>Cada movimiento queda registrado en la bitácora de auditoría.</span></li>
    </ul>
  </div>

  <div class="auth-form">
    <h1>Iniciar sesión</h1>
    <p class="hint" style="margin:0 0 6px">Entra con tu correo y tu contraseña.</p>
    <form method="post" action="index.php?action=login" id="form-login" autocomplete="on">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <label for="login-email">Correo electrónico</label>
      <input id="login-email" type="email" name="email" list="correos-recientes" autocomplete="email" required autofocus>
      <datalist id="correos-recientes"></datalist>
      <label for="login-password">Contraseña</label>
      <div class="pw-wrap">
        <input id="login-password" type="password" name="password" autocomplete="current-password" required>
        <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Mostrar u ocultar la contraseña">Mostrar</button>
      </div>
      <button type="submit" class="gold block" style="margin-top:20px">Iniciar sesión</button>
    </form>
    <p class="auth-foot">¿No tienes cuenta? <a href="index.php?page=registro">Regístrate</a></p>
  </div>
</div>

<script>
(function () {
  const pw = document.getElementById('login-password');
  const toggle = document.getElementById('pw-toggle');
  const emailInput = document.getElementById('login-email');
  const datalist = document.getElementById('correos-recientes');
  const formLogin = document.getElementById('form-login');
  const STORAGE_KEY = 'guate_subasta_correos';

  toggle.addEventListener('click', function () {
    const oculto = pw.type === 'password';
    pw.type = oculto ? 'text' : 'password';
    toggle.textContent = oculto ? 'Ocultar' : 'Mostrar';
  });

  function obtenerCorreosGuardados() {
    try {
      const data = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
      return Array.isArray(data) ? data : [];
    } catch (e) {
      return [];
    }
  }

  function mostrarSugerencias() {
    if (!datalist) return;
    datalist.innerHTML = '';
    obtenerCorreosGuardados().forEach(function (correo) {
      const option = document.createElement('option');
      option.value = correo;
      datalist.appendChild(option);
    });
  }

  mostrarSugerencias();

  formLogin?.addEventListener('submit', function () {
    const correo = (emailInput?.value || '').trim().toLowerCase();
    if (!correo) return;
    let lista = obtenerCorreosGuardados().filter(c => c.toLowerCase() !== correo);
    lista.unshift(correo);
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(lista.slice(0, 10)));
    } catch (e) {}
  });

  emailInput?.addEventListener('input', function () {
    const valor = this.value.trim().toLowerCase();
    const guardados = obtenerCorreosGuardados().map(c => c.toLowerCase());
    if (guardados.includes(valor) && !pw.value) {
      pw.focus();
    }
  });
})();
</script>