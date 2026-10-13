<div class="auth-wrapper">

    <div class="auth-header">
        <h1>Guate-Subasta</h1>
        <p>Selecciona el tipo de usuario</p>
    </div>

    <div class="auth-options">

        <!-- USUARIO -->
        <div class="auth-section">
            <div class="auth-icon">👤</div>

            <h2>Usuario</h2>

            <p class="auth-description">
                Accede para participar en subastas,
                realizar ofertas y administrar tu cuenta.
            </p>

            <form method="post" action="index.php?action=login">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= csrf_token() ?>"
                >

                <input
                    type="hidden"
                    name="tipo_usuario"
                    value="usuario"
                >

                <label>Correo electrónico</label>

                <input
                    type="email"
                    name="email"
                    placeholder="correo@ejemplo.com"
                    required
                >

                <label>Contraseña</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                >

                <button type="submit">
                    Iniciar como Usuario
                </button>

            </form>

            <p class="registro">
                ¿No tienes cuenta?
                <a href="index.php?page=registro">
                    Regístrate
                </a>
            </p>
        </div>


        <div class="auth-divider"></div>


        <!-- ADMINISTRADOR -->
        <div class="auth-section">
            <div class="auth-icon">⚙️</div>

            <h2>Administrador</h2>

            <p class="auth-description">
                Acceso exclusivo para la administración
                y control de Guate-Subasta.
            </p>

            <form method="post" action="index.php?action=login">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= csrf_token() ?>"
                >

                <input
                    type="hidden"
                    name="tipo_usuario"
                    value="admin"
                >

                <label>Correo electrónico</label>

                <input
                    type="email"
                    name="email"
                    placeholder="admin@guate.test"
                    required
                >

                <label>Contraseña</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                >

                <button type="submit">
                    Iniciar como Administrador
                </button>

            </form>

            <div class="demo">
                <b>Demo administrador:</b><br>
                admin@guate.test<br>
                Admin123!
            </div>
        </div>

    </div>

</div>