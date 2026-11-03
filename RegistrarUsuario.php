<?php
session_start();
require_once 'conexion.php';
 
// true  = cualquiera puede crear una cuenta, siempre con rol Empleado.
// false = solo un Administrador con sesión puede registrar usuarios.
define('REGISTRO_PUBLICO', true);
 
$esAdmin = (($_SESSION['rol'] ?? '') === 'Administrador');
if (!$esAdmin && !REGISTRO_PUBLICO) {
    header('Location: InicioSesion.php');
    exit;
}
 
$msg  = '';
$tipo = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario']    ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $confirmar  = trim($_POST['confirmar']  ?? '');
    // Solo un Administrador puede elegir el rol; el registro público siempre crea Empleados
    $rol        = $esAdmin ? ($_POST['rol'] ?? 'Empleado') : 'Empleado';
    if (!in_array($rol, ['Empleado', 'Administrador'], true)) { $rol = 'Empleado'; }
 
    if (!$usuario || !$contrasena || !$confirmar) {
        $msg = 'Completa todos los campos.'; $tipo = 'error';
    } elseif (!preg_match('/[a-zA-Z]/', $usuario)) {
        $msg = 'El usuario debe contener al menos una letra.'; $tipo = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $usuario)) {
        $msg = 'Usuario: 3-20 caracteres, solo letras, números y _.'; $tipo = 'error';
    } elseif (strlen($contrasena) < 6) {
        $msg = 'La contraseña debe tener mínimo 6 caracteres.'; $tipo = 'error';
    } elseif ($contrasena !== $confirmar) {
        $msg = 'Las contraseñas no coinciden.'; $tipo = 'error';
    } else {
        // Verificar si el usuario ya existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
        $check->execute([$usuario]);
        if ($check->fetch()) {
            $msg = 'Ese usuario ya existe.'; $tipo = 'error';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (usuario, contrasena, rol) VALUES (?, ?, ?)"
            );
            $stmt->execute([$usuario, password_hash($contrasena, PASSWORD_DEFAULT), $rol]);
            $msg = 'Usuario registrado con éxito.'; $tipo = 'exito';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Registrar Usuario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="container">
    <div class="logo">
        <h1>MAXIRUBBER</h1>
        <p>Registro de Usuario Interno</p>
    </div>
 
    <?php if ($msg): ?>
        <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
        <?php if ($tipo === 'exito'): ?>
            <script>setTimeout(() => location.href = '<?= $esAdmin ? 'GestionUsuario.php' : 'InicioSesion.php' ?>', 1500);</script>
        <?php endif; ?>
    <?php endif; ?>
 
    <form method="POST" novalidate>
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario"
               placeholder="Ej: juan_garcia" maxlength="20" autocomplete="off"
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
 
        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena"
               placeholder="Mínimo 6 caracteres" maxlength="30">
 
        <label for="confirmar">Confirmar contraseña</label>
        <input type="password" id="confirmar" name="confirmar"
               placeholder="Repite la contraseña" maxlength="30">
 
        <?php if ($esAdmin): ?>
        <label for="rol">Rol</label>
        <select id="rol" name="rol">
            <option value="Empleado">Empleado</option>
            <option value="Administrador">Administrador</option>
        </select>
        <?php endif; ?>
 
        <button type="submit">Registrar Usuario</button>
    </form>
    <a href="InicioSesion.php" class="link-center">¿Ya tienes cuenta? Inicia sesión</a>
</div>
 
<script src="Validaciones.js"></script>
<script>
    V.bloquear('usuario',    /[a-zA-Z0-9_]/);
    V.bloquear('contrasena', /[^\s]/);
    V.bloquear('confirmar',  /[^\s]/);
 
    document.getElementById('usuario').addEventListener('blur',    () => V.usuario('usuario'));
    document.getElementById('contrasena').addEventListener('blur', () => V.contrasena('contrasena'));
    document.getElementById('confirmar').addEventListener('blur', function() {
        const p1 = document.getElementById('contrasena').value;
        if (!this.value) return V.error('confirmar', 'Confirma la contraseña.');
        if (p1 !== this.value) return V.error('confirmar', 'Las contraseñas no coinciden.');
        V.ok('confirmar');
    });
</script>
</body>
</html>