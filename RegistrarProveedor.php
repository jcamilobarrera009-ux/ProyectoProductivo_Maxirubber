<?php
session_start();
require_once 'conexion.php';

// Solo el Administrador gestiona catálogos
if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'Administrador') {
    header('Location: InicioSesion.php');
    exit;
}

$msg  = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') {
        $msg = 'El nombre es obligatorio.'; $tipo = 'error';
    } elseif (!preg_match('/^[0-9]{7,10}$/', $telefono)) {
        $msg = 'El teléfono debe tener entre 7 y 10 dígitos.'; $tipo = 'error';
    } elseif ($direccion === '') {
        $msg = 'La dirección es obligatoria.'; $tipo = 'error';
    } else {
        $dup = $pdo->prepare("SELECT COUNT(*) FROM proveedores WHERE nombre = ?");
        $dup->execute([$nombre]);
        if ($dup->fetchColumn() > 0) {
            $msg = 'Ya existe un proveedor con ese nombre.'; $tipo = 'error';
        } else {
            $pdo->prepare("INSERT INTO proveedores (nombre, telefono, direccion) VALUES (?, ?, ?)")
                ->execute([$nombre, $telefono, $direccion]);
            $msg = "Proveedor \"$nombre\" registrado."; $tipo = 'exito';
            $_POST = [];
        }
    }
}

$lista = $pdo->query("SELECT nombre, telefono, direccion FROM proveedores ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Registrar Proveedor</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Registrar Proveedor</h1>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">
                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <form method="POST" novalidate>

                    <label>Nombre del proveedor</label>
                    <input type="text" name="nombre" maxlength="60" placeholder="Ej: Cauchos del Eje S.A.S"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">

                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="telefono" maxlength="10" placeholder="Ej: 3101234567"
                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                    <small class="field-hint">Solo números, entre 7 y 10 dígitos</small>

                    <label>Dirección</label>
                    <input type="text" name="direccion" maxlength="100" placeholder="Ej: Calle 20 # 10-15, Pereira"
                           value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">

                    <button type="submit">Registrar Proveedor</button>
                </form>
            </div>

            <div class="panel" style="margin-top:22px;">
                <div class="panel-header">
                    <span class="panel-title"><?= count($lista) ?> registro(s)</span>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead><tr><th>Nombre</th><th>Teléfono</th><th>Dirección</th></tr></thead>
                        <tbody>
                        <?php foreach ($lista as $r): ?>
                            <tr><td><?= htmlspecialchars($r['nombre']) ?></td><td><?= htmlspecialchars($r['telefono']) ?></td><td><?= htmlspecialchars($r['direccion']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista)): ?>
                            <tr><td colspan="3" style="text-align:center;color:var(--text-3);">Aún no hay registros.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    document.getElementById('telefono').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>
</body>
</html>
