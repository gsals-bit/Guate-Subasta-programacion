<?php if (!isset($ic, $icons)) { $ic = fn($d) => ''; $icons = []; } ?>
<div class="auth-split">
  <div class="auth-aside">
    <div class="brand-mark"><?=$ic($icons['gavel'] ?? '')?></div>
    <h2>Crea tu cuenta y empieza a pujar</h2>
    <p>Es gratis y toma menos de un minuto.</p>
    <ul class="auth-points">
      <li><?=$ic($icons['pujas'] ?? '')?><span>Puja en las subastas activas y sigue tus ofertas desde «Mis pujas».</span></li>
      <li><?=$ic($icons['box'] ?? '')?><span>Registra tus propios artículos y prográmalos para subastarlos.</span></li>
      <li><?=$ic($icons['bell'] ?? '')?><span>Recibe avisos cuando haya subastas nuevas o cambien de horario.</span></li>
    </ul>
  </div>

  <div class="auth-form">
    <h1>Crear cuenta</h1>
    <p class="hint" style="margin:0 0 6px">Completa tus datos para registrarte.</p>
    <form method="post" action="index.php?action=registro">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <label for="reg-nombre">Nombre</label>
      <input id="reg-nombre" name="nombre" autocomplete="name" required autofocus>
      <label for="reg-email">Correo electrónico</label>
      <input id="reg-email" type="email" name="email" autocomplete="email" required>
      <label for="reg-password">Contraseña</label>
      <div class="pw-wrap">
        <input id="reg-password" type="password" name="password" minlength="6" autocomplete="new-password" required>
        <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Mostrar u ocultar la contraseña">Mostrar</button>
      </div>
      <p class="hint" style="margin:6px 0 0">Usa al menos 6 caracteres.</p>
      <button type="submit" class="gold block" style="margin-top:20px">Crear cuenta</button>
    </form>
    <p class="auth-foot">¿Ya tienes cuenta? <a href="index.php?page=login">Inicia sesión</a></p>
  </div>
</div>

<script>
(function () {
  const pw = document.getElementById('reg-password');
  const toggle = document.getElementById('pw-toggle');
  toggle.addEventListener('click', function () {
    const oculto = pw.type === 'password';
    pw.type = oculto ? 'text' : 'password';
    toggle.textContent = oculto ? 'Ocultar' : 'Mostrar';
  });
})();
</script>