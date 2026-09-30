<div class="auth-card"><h1>Guate-Subasta</h1><p>Subastas privadas de artículos exclusivos</p>
<form method="post" action="index.php?action=login"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
<label>Correo electrónico</label><input type="email" name="email" required>
<label>Contraseña</label><input type="password" name="password" required>
<button>Iniciar sesión</button></form><p>¿No tienes cuenta? <a href="index.php?page=registro">Regístrate</a></p>
<div class="demo"><b>Demo:</b> admin@guate.test / Admin123!</div></div>