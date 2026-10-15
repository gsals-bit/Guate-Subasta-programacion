
<?php
$csrf = csrf_token();
?>

<div class="auction-login">
    <section class="auction-brand">
        <div class="brand-content">
            <span class="brand-icon">⚖</span>
            <h1>Guate-Subasta</h1>
            <p class="brand-subtitle">
                Subastas privadas de artículos exclusivos
            </p>

            <div class="brand-features">
                <div>
                    <span>♢</span>
                    <small>Seguro</small>
                </div>
                <div>
                    <span>◇</span>
                    <small>Exclusivo</small>
                </div>
                <div>
                    <span>♧</span>
                    <small>Confiable</small>
                </div>
            </div>

            <p class="brand-description">
                Descubre artículos únicos, participa en subastas
                y encuentra piezas extraordinarias.
            </p>
        </div>
    </section>

    <section class="auction-form-side">
        <div class="auction-card">
            <div class="card-emblem">G S</div>

            <h2>Bienvenido</h2>
            <p class="card-subtitle">
                Ingresa a tu cuenta para continuar
            </p>

            <?php if (!empty($error)): ?>
                <div class="login-error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php?action=login">
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"
                >

                <label for="email">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    placeholder="tu@correo.com"
                    autocomplete="username"
                    required
                >

                <label for="password">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                    required
                >

                <button type="submit" class="auction-submit">
                    Iniciar sesión <span>→</span>
                </button>
            </form>

            <p class="register-link">
                ¿No tienes cuenta?
                <a href="index.php?page=registro">Regístrate</a>
            </p>

            <div class="card-footer">
                <span>♧</span>
                Acceso seguro a Guate-Subasta
            </div>
        </div>
    </section>
</div>
