<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER S.A.S. | Inicio</title>
    <link rel="stylesheet" href="./estilos.css?v=3">
    <style>
        body.landing-page { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #14263D; background: #F7F9FC; }
        /* Respaldo para evitar que el logo se desborde si el CSS externo falla */
        .landing-logo { width: min(340px, 42vw); max-width: 100%; height: auto; display: block; }
        .landing-header a { display: block; max-width: 100%; }
        .landing-card-logo { display: none !important; }
    </style>
</head>
<body class="landing-page">

    <header class="landing-header">
        <a href="Index.php" aria-label="Página principal de MAXIRUBBER">
            <img src="MAXIRUBBER.png" alt="Logo de MAXIRUBBER S.A.S." class="landing-logo" style="width: min(340px, 42vw); max-width: 100%; height: auto;">
        </a>
        <div class="landing-tagline">Calidad que se siente</div>
    </header>

    <main class="landing-main">
        <section class="landing-copy">
            <p class="landing-eyebrow">Bienvenido a</p>
            <h1 class="landing-title">MAXIRUBBER S.A.S.</h1>
            <p class="landing-description">
                Sistema de gestión para organizar la producción, las ventas
                y la facturación de suelas de caucho y tapetes.
            </p>

            <div class="landing-features">
                <article class="landing-feature">
                    <div class="landing-feature-icon" aria-hidden="true">
                        <svg class="icon-production" viewBox="0 0 24 24">
                            <path d="M3 21h18"/>
                            <path d="M5 21V8l7-4 7 4v13"/>
                            <path d="M9 21v-6h6v6"/>
                            <path d="M8 10h.01M12 10h.01M16 10h.01"/>
                        </svg>
                    </div>
                    <h2>Producción</h2>
                    <p>Organiza y consulta la información de producción.</p>
                </article>

                <article class="landing-feature">
                    <div class="landing-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>
                        </svg>
                    </div>
                    <h2>Ventas</h2>
                    <p>Gestiona los clientes y los registros de ventas.</p>
                </article>

                <article class="landing-feature">
                    <div class="landing-feature-icon" aria-hidden="true">
                        <svg class="icon-invoice" viewBox="0 0 24 24">
                            <path d="M6 3h9l4 4v14H6z"/>
                            <path d="M14 3v5h5"/>
                            <path d="M9 12h7M9 16h7"/>
                            <path d="M9 8h1"/>
                        </svg>
                    </div>
                    <h2>Facturación</h2>
                    <p>Consulta y genera facturas de las ventas registradas.</p>
                </article>
            </div>

            <div class="landing-note">
                <span class="landing-note-mark" aria-hidden="true"></span>
                Una plataforma para centralizar la gestión de la empresa.
            </div>
        </section>

        <section class="landing-login-card" aria-labelledby="landing-card-title">
            <p class="landing-card-kicker">Plataforma empresarial</p>
            <h2 id="landing-card-title" class="landing-card-title">Bienvenido</h2>
            <p class="landing-card-text">
                Inicia sesión para acceder a las herramientas de gestión de MAXIRUBBER.
            </p>

            <a href="InicioSesion.php" class="landing-login-button">
                Iniciar sesión <span aria-hidden="true">→</span>
            </a>

            <p class="landing-register">
                ¿No tienes cuenta?
                <a href="RegistrarUsuario.php">Regístrate aquí</a>
            </p>

            <div class="landing-card-divider">MAXIRUBBER S.A.S.</div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-footer-item">
            <span class="landing-footer-mark" aria-hidden="true">⌖</span>
            Bogotá, Colombia
        </div>
        <div class="landing-footer-item">
            <span class="landing-footer-mark" aria-hidden="true">◇</span>
            Gestión de producción, ventas y facturación
        </div>
        <div class="landing-footer-item">
            <span class="landing-footer-mark" aria-hidden="true">✓</span>
            Acceso para usuarios autorizados
        </div>
    </footer>

</body>
</html>
