<?php
session_start();
require_once 'conexion.php';

// ── CORRECCIÓN: redirect por rol para evitar loop infinito ──────
//   Antes: todos iban a GestionUsuario.php, pero ese módulo
//   bloquea a empleados → loop infinito para rol Empleado.
if (isset($_SESSION['usuario_id'])) {
    $destino = ($_SESSION['rol'] === 'Administrador')
        ? 'GestionUsuario.php'
        : 'RegistrarCliente.php';
    header('Location: ' . $destino);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario']    ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if (!$usuario || !$contrasena) {
        $error = 'Completa todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $fila = $stmt->fetch();

        if ($fila && $fila['contrasena'] === $contrasena) {
            $_SESSION['usuario_id'] = $fila['id'];
            $_SESSION['usuario']    = $fila['usuario'];
            $_SESSION['rol']        = $fila['rol'];

            // Redirigir según rol
            $destino = ($fila['rol'] === 'Administrador')
                ? 'GestionUsuario.php'
                : 'RegistrarCliente.php';
            header('Location: ' . $destino);
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Iniciar Sesión</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="container">
    <div class="logo">
        <h1>MAXIRUBBER</h1>
        <p>Sistema de Producción, Ventas y Facturación</p>
    </div>

    <?php if ($error): ?>
        <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario"
               placeholder="Tu nombre de usuario"
               maxlength="20" autocomplete="off"
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">

        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena"
               placeholder="Tu contraseña" maxlength="30">

        <button type="submit">Entrar al sistema</button>
    </form>

    <a href="RegistrarUsuario.php" class="link-center">¿No tienes cuenta? Regístrate</a>
</div>

<script src="Validaciones.js"></script>
<script>
    V.bloquear('usuario',    /[a-zA-Z0-9_]/);
    V.bloquear('contrasena', /[^\s]/);
    document.getElementById('usuario').addEventListener('blur',    () => V.usuario('usuario'));
    document.getElementById('contrasena').addEventListener('blur', () => V.contrasena('contrasena'));
</script>
</body>
</html>