<?php
session_start();
require_once 'conexion.php';

// Solo administradores
if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: InicioSesion.php'); exit;
}

$id  = (int)($_GET['id'] ?? 0);
$msg = ''; $tipo = '';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: GestionUsuario.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoUsuario  = trim($_POST['usuario']    ?? '');
    $nuevaContrasena = trim($_POST['contrasena'] ?? '');
    $rol           = $_POST['rol']             ?? 'Empleado';

    if (!$nuevoUsuario) {
        $msg = 'El nombre de usuario es obligatorio.'; $tipo = 'error';
    } elseif (!preg_match('/[a-zA-Z]/', $nuevoUsuario)) {
        $msg = 'El usuario debe contener al menos una letra.'; $tipo = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $nuevoUsuario)) {
        $msg = 'Usuario: 3-20 caracteres, solo letras, números y _.'; $tipo = 'error';
    } else {
        // Verificar duplicado en OTRO usuario
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ? LIMIT 1");
        $check->execute([$nuevoUsuario, $id]);
        if ($check->fetch()) {
            $msg = 'Ese nombre de usuario ya existe.'; $tipo = 'error';
        } else {
            // Si se escribió nueva contraseña, actualizarla también
            if ($nuevaContrasena) {
                if (strlen($nuevaContrasena) < 6) {
                    $msg = 'La contraseña debe tener mínimo 6 caracteres.'; $tipo = 'error';
                } else {
                    $pdo->prepare("UPDATE usuarios SET usuario = ?, contrasena = ?, rol = ? WHERE id = ?")
                        ->execute([$nuevoUsuario, $nuevaContrasena, $rol, $id]);
                    $msg = 'Usuario actualizado con éxito (contraseña cambiada).'; $tipo = 'exito';
                }
            } else {
                $pdo->prepare("UPDATE usuarios SET usuario = ?, rol = ? WHERE id = ?")
                    ->execute([$nuevoUsuario, $rol, $id]);
                $msg = 'Usuario actualizado con éxito.'; $tipo = 'exito';
            }

            if ($tipo === 'exito') {
                $stmt->execute([$id]);
                $usuario = $stmt->fetch();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Editar Usuario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Editar Usuario</h1>
            <div class="topbar-actions">
                <a href="GestionUsuario.php" class="btn btn-ghost btn-sm">← Volver</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">

                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <label>Nombre de usuario</label>
                    <input type="text" name="usuario" id="usuario" maxlength="20" autocomplete="off"
                           value="<?= htmlspecialchars($_POST['usuario'] ?? $usuario['usuario']) ?>">

                    <label>Nueva contraseña
                        <span style="color:var(--text-3);font-size:.8rem;">(dejar vacío para no cambiarla)</span>
                    </label>
                    <input type="password" name="contrasena" id="contrasena"
                           placeholder="Nueva contraseña" maxlength="30">

                    <label>Rol</label>
                    <select name="rol">
                        <?php foreach (['Empleado','Administrador'] as $r): ?>
                            <option value="<?= $r ?>"
                                <?= (($_POST['rol'] ?? $usuario['rol']) === $r) ? 'selected' : '' ?>>
                                <?= $r ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="Validaciones.js"></script>
<script>
    V.bloquear('usuario', /[a-zA-Z0-9_]/);
    document.getElementById('usuario').addEventListener('blur', () => V.usuario('usuario'));
</script>
</body>
</html>