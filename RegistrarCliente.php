<?php
session_start();
require_once 'conexion.php';

// Protección: debe haber sesión activa
if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php');
    exit;
}

$msg  = '';
$tipo = '';

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
        $msg = 'Correo electrónico inválido.'; $tipo = 'error';
    } else {
        // Verificar teléfono duplicado
        $check = $pdo->prepare("SELECT id FROM clientes WHERE telefono = ? LIMIT 1");
        $check->execute([$telefono]);
        if ($check->fetch()) {
            $msg = 'Ya existe un cliente con ese teléfono.'; $tipo = 'error';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO clientes (nombre, telefono, correo, direccion)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $nombre,
                $telefono,
                $correo    ?: null,
                $direccion ?: null
            ]);
            $msg  = "Cliente \"$nombre\" registrado con éxito.";
            $tipo = 'exito';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Registrar Cliente</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Registrar Cliente</h1>
            <div class="topbar-actions">
                <a href="ConsultarClientes.php" class="btn btn-ghost btn-sm">Ver listado →</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">

                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" id="nombre"
                           placeholder="Ej: María García López" maxlength="60"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">

                    <label>Teléfono</label>
                    <input type="tel" name="telefono" id="telefono"
                           placeholder="Ej: 3001234567" maxlength="10"
                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                    <small class="field-hint">Solo dígitos, entre 7 y 10 números</small>

                    <label>Correo electrónico
                        <span style="color:var(--text-3); font-size:.8rem;">(opcional)</span>
                    </label>
                    <input type="email" name="correo" id="correo"
                           placeholder="Ej: cliente@correo.com" maxlength="80"
                           value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">

                    <label>Dirección
                        <span style="color:var(--text-3); font-size:.8rem;">(opcional)</span>
                    </label>
                    <input type="text" name="direccion" id="direccion"
                           placeholder="Ej: Calle 10 #5-20, Bogotá" maxlength="100"
                           value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">

                    <button type="submit">Guardar Cliente</button>
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
