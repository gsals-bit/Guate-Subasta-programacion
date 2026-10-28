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
    <form method="post" action="index.php?action=login">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <label for="login-email">Correo electrónico</label>
      <input id="login-email" type="email" name="email" autocomplete="username" required autofocus>
      <label for="login-password">Contraseña</label>
      <div class="pw-wrap">
        <input id="login-password" type="password" name="password" autocomplete="current-password" required>
        <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Mostrar u ocultar la contraseña">Mostrar</button>
      </div>
      <button type="submit" class="gold block" style="margin-top:20px">Iniciar sesión</button>
    </form>
    <p class="auth-foot">¿No tienes cuenta? <a href="index.php?page=registro">Regístrate</a></p>

    <div class="demo-box">
      <span><b>Cuenta de demostración:</b> admin@guate.test / Admin123!</span>
      <button type="button" class="sm ghost" id="demo-fill">Usar estos datos</button>
    </div>
  </div>
</div>

<script>
(function () {
  const pw = document.getElementById('login-password');
  const toggle = document.getElementById('pw-toggle');
  toggle.addEventListener('click', function () {
    const oculto = pw.type === 'password';
    pw.type = oculto ? 'text' : 'password';
    toggle.textContent = oculto ? 'Ocultar' : 'Mostrar';
  });
  document.getElementById('demo-fill').addEventListener('click', function () {
    document.getElementById('login-email').value = 'admin@guate.test';
    pw.value = 'Admin123!';
  });
})();
</script>