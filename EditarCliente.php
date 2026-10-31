<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php'); exit;
}

$id  = (int)($_GET['id'] ?? 0);
$msg = ''; $tipo = '';

// Buscar cliente existente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: ConsultarClientes.php'); exit;
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $correo    = trim($_POST['correo']    ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!$nombre || !$telefono) {
        $msg = 'Nombre y teléfono son obligatorios.'; $tipo = 'error';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,60}$/', $nombre)) {
        $msg = 'Nombre: solo letras y espacios.'; $tipo = 'error';
    } elseif (!preg_match('/^\d{7,10}$/', $telefono)) {
        $msg = 'Teléfono: solo dígitos, entre 7 y 10 números.'; $tipo = 'error';
    } elseif ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Correo inválido.'; $tipo = 'error';
    } else {
        // Verificar teléfono duplicado en OTRO cliente
        $check = $pdo->prepare("SELECT id FROM clientes WHERE telefono = ? AND id != ? LIMIT 1");
        $check->execute([$telefono, $id]);
        if ($check->fetch()) {
            $msg = 'Ese teléfono ya pertenece a otro cliente.'; $tipo = 'error';
        } else {
            $pdo->prepare("
                UPDATE clientes
                SET nombre = ?, telefono = ?, correo = ?, direccion = ?
                WHERE id = ?
            ")->execute([$nombre, $telefono, $correo ?: null, $direccion ?: null, $id]);

            $msg = 'Cliente actualizado con éxito.'; $tipo = 'exito';
            // Recargar datos actualizados
            $stmt->execute([$id]);
            $cliente = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Editar Cliente</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Editar Cliente</h1>
            <div class="topbar-actions">
                <a href="ConsultarClientes.php" class="btn btn-ghost btn-sm">← Volver al listado</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">

                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" id="nombre" maxlength="60"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? $cliente['nombre']) ?>">

                    <label>Teléfono</label>
                    <input type="tel" name="telefono" id="telefono" maxlength="10"
                           value="<?= htmlspecialchars($_POST['telefono'] ?? $cliente['telefono']) ?>">
                    <small class="field-hint">Solo dígitos, entre 7 y 10 números</small>

                    <label>Correo electrónico
                        <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span>
                    </label>
                    <input type="email" name="correo" id="correo" maxlength="80"
                           value="<?= htmlspecialchars($_POST['correo'] ?? $cliente['correo'] ?? '') ?>">

                    <label>Dirección
                        <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span>
                    </label>
                    <input type="text" name="direccion" id="direccion" maxlength="100"
                           value="<?= htmlspecialchars($_POST['direccion'] ?? $cliente['direccion'] ?? '') ?>">

                    <button type="submit">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="Validaciones.js"></script>
<script>
    V.bloquear('nombre',   /[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/);
    V.bloquear('telefono', /[0-9]/);
    document.getElementById('nombre').addEventListener('blur',   () => V.nombre('nombre'));
    document.getElementById('telefono').addEventListener('blur', () => V.telefono('telefono'));
    document.getElementById('correo').addEventListener('blur',   () => V.correo('correo'));
</script>
</body>
</html>