<div class="auth-card"><h1>Crear cuenta</h1><form method="post" action="index.php?action=registro">
<input type="hidden" name="csrf" value="<?=csrf_token()?>"><label>Nombre</label><input name="nombre" required>
<label>Correo</label><input type="email" name="email" required><label>Contraseña</label><input type="password" name="password" minlength="6" required>
<button>Registrarme</button></form><a href="index.php?page=login">Volver al inicio</a></div>