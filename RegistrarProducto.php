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
    $nombre = trim($_POST['nombre'] ?? '');
    $tipoP  = trim($_POST['tipo']   ?? '');
    $talla  = trim($_POST['talla']  ?? '');
    $precio = (int)preg_replace('/\D/', '', $_POST['precio'] ?? '');

    if ($nombre === '') {
        $msg = 'El nombre es obligatorio.'; $tipo = 'error';
    } elseif ($tipoP === '') {
        $msg = 'El tipo es obligatorio.'; $tipo = 'error';
    } elseif ($precio <= 0) {
        $msg = 'El precio debe ser mayor a 0.'; $tipo = 'error';
    } else {
        $dup = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE nombre = ? AND COALESCE(talla,'') = ?");
        $dup->execute([$nombre, $talla]);
        if ($dup->fetchColumn() > 0) {
            $msg = 'Ya existe ese producto con la misma talla.'; $tipo = 'error';
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO productos (nombre, tipo, talla, precio) VALUES (?, ?, ?, ?)")
                    ->execute([$nombre, $tipoP, $talla ?: null, $precio]);
                // Se crea su fila de inventario en 0
                $pdo->prepare("INSERT INTO inventario (producto_id, cantidad) VALUES (?, 0)")
                    ->execute([$pdo->lastInsertId()]);
                $pdo->commit();
                $msg = "Producto \"$nombre\" registrado con inventario 0. Se llena al registrar producción.";
                $tipo = 'exito';
                $_POST = [];
            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $msg = 'No se pudo registrar el producto.'; $tipo = 'error';
            }
        }
    }
}

$lista = $pdo->query("
    SELECT p.nombre, p.tipo, p.talla, p.precio, COALESCE(i.cantidad,0) AS stock
    FROM productos p LEFT JOIN inventario i ON i.producto_id = p.id
    ORDER BY p.nombre
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Registrar Producto</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Registrar Producto</h1>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">
                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <form method="POST" novalidate>

                    <label>Nombre del producto</label>
                    <input type="text" name="nombre" maxlength="60" placeholder="Ej: Suela de caucho"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">

                    <label>Tipo</label>
                    <input type="text" name="tipo" maxlength="50" placeholder="Ej: Suela, Tapete"
                           value="<?= htmlspecialchars($_POST['tipo'] ?? '') ?>">

                    <label>Talla <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span></label>
                    <input type="text" name="talla" maxlength="20" placeholder="Ej: 38-42"
                           value="<?= htmlspecialchars($_POST['talla'] ?? '') ?>">

                    <label>Precio de venta ($)</label>
                    <input type="text" inputmode="numeric" name="precio" id="precio" maxlength="10" placeholder="Ej: 25000"
                           value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
                    <small class="field-hint">Este precio se rellena solo al registrar una venta</small>

                    <button type="submit">Registrar Producto</button>
                </form>
            </div>

            <div class="panel" style="margin-top:22px;">
                <div class="panel-header">
                    <span class="panel-title"><?= count($lista) ?> registro(s)</span>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead><tr><th>Nombre</th><th>Tipo</th><th>Talla</th><th>Precio</th><th>Stock</th></tr></thead>
                        <tbody>
                        <?php foreach ($lista as $r): ?>
                            <tr><td><?= htmlspecialchars($r['nombre']) ?></td><td><?= htmlspecialchars($r['tipo']) ?></td><td><?= htmlspecialchars($r['talla'] ?? '—') ?></td><td>$<?= number_format($r['precio'], 0, ',', '.') ?></td><td><?= (int)$r['stock'] ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista)): ?>
                            <tr><td colspan="5" style="text-align:center;color:var(--text-3);">Aún no hay registros.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    // Solo números: bloquea teclas no numéricas y limpia lo pegado
    function soloNumeros(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keypress', e => {
            if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    soloNumeros('precio');
</script>
</body>
</html>
