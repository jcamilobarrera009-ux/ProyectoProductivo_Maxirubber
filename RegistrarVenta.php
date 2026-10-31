<?php
session_start();
require_once 'conexion.php';

// Protección: debe haber sesión activa
if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php');
    exit;
}

// ── CORRECCIÓN: lógica real de registro de venta ───────────────
//   (antes había código de ejemplo que insertaba en 'clientes')
$msg  = '';
$tipo = '';
$hoy  = date('Y-m-d');

// Cargar clientes para el <select>
$clientes = $pdo->query(
    "SELECT id, nombre, telefono FROM clientes ORDER BY nombre"
)->fetchAll();

// Cargar productos con su stock disponible
$productos = $pdo->query("
    SELECT p.id, p.nombre, p.precio, COALESCE(i.cantidad, 0) AS stock
    FROM productos p
    LEFT JOIN inventario i ON i.producto_id = p.id
    ORDER BY p.nombre
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $producto   = '';
    foreach ($productos as $p) {
        if ((int)$p['id'] === $producto_id) { $producto = $p['nombre']; }
    }
    $cantidad   = (int)($_POST['cantidad']   ?? 0);
    $precio     = (int)preg_replace('/\D/', '', $_POST['precio'] ?? '');
    $fecha      = $_POST['fecha']            ?? '';

    // Validaciones
    if (!$cliente_id) {
        $msg = 'Selecciona un cliente.'; $tipo = 'error';
    } elseif (!$producto) {
        $msg = 'Selecciona un producto del catálogo.'; $tipo = 'error';
    } elseif ($cantidad < 1) {
        $msg = 'La cantidad debe ser al menos 1.'; $tipo = 'error';
    } elseif ($precio <= 0) {
        $msg = 'El precio debe ser mayor a 0.'; $tipo = 'error';
    } elseif (!$fecha || $fecha > $hoy) {
        $msg = 'La fecha no puede ser futura.'; $tipo = 'error';
    } else {
        $total  = $cantidad * $precio;
        // Código único: V + 6 caracteres alfanuméricos en mayúscula
        $codigo = 'V' . strtoupper(substr(uniqid(), -6));

        $ok = false;
        try {
            $pdo->beginTransaction();

            // 1. Verificar stock del producto terminado (fila bloqueada)
            $inv = $pdo->prepare(
                "SELECT cantidad FROM inventario WHERE producto_id = ? FOR UPDATE"
            );
            $inv->execute([$producto_id]);
            $disponible = (int)($inv->fetchColumn() ?: 0);

            if ($disponible < $cantidad) {
                throw new Exception(
                    "Stock insuficiente de $producto: disponible $disponible, solicitado $cantidad."
                );
            }

            // 2. Guardar la venta
            $pdo->prepare("
                INSERT INTO ventas
                    (codigo, cliente_id, usuario_id, producto_id, producto,
                     cantidad, precio, total, fecha)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $codigo, $cliente_id, $_SESSION['usuario_id'], $producto_id,
                $producto, $cantidad, $precio, $total, $fecha
            ]);

            // 3. DESCONTAR del inventario
            $pdo->prepare(
                "UPDATE inventario SET cantidad = cantidad - ? WHERE producto_id = ?"
            )->execute([$cantidad, $producto_id]);

            $pdo->commit();
            $ok = true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $msg = htmlspecialchars($e->getMessage()); $tipo = 'error';
        }

        if ($ok) {
            $msg  = "Venta registrada. Código: <strong>$codigo</strong> — Total: $"
                  . number_format($total, 0, ',', '.');
            $tipo = 'exito';
            $_POST = [];
            // Actualizar el stock mostrado en el select
            foreach ($productos as &$pp) {
                if ((int)$pp['id'] === $producto_id) { $pp['stock'] -= $cantidad; }
            }
            unset($pp);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Registrar Venta</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Registrar Venta</h1>
            <div class="topbar-actions">
                <a href="GenerarFactura.php" class="btn btn-ghost btn-sm">Ver facturas →</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">
                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= $msg ?></div>
                <?php endif; ?>
                <form method="POST" novalidate id="formVenta">

                    <label>Cliente</label>
                    <select name="cliente_id" id="cliente_id">
                        <option value="">-- Seleccionar cliente --</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= (($_POST['cliente_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?> — <?= htmlspecialchars($c['telefono']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($clientes)): ?>
                            <option disabled>⚠ No hay clientes. Registra uno primero.</option>
                        <?php endif; ?>
                    </select>

                    <label>Producto</label>
                    <select name="producto_id" id="producto_id">
                        <option value="">-- Seleccionar producto --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                    data-precio="<?= $p['precio'] ?>"
                                    data-stock="<?= $p['stock'] ?>"
                                <?= (($_POST['producto_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?> (disp: <?= (int)$p['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-hint" id="hint-stock">Stock disponible: —</small>

                    <label>Cantidad</label>
                    <input type="text" inputmode="numeric" name="cantidad" id="cantidad"
                           placeholder="Ej: 50" maxlength="6"
                           value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>">

                    <label>Precio unitario ($)</label>
                    <input type="text" inputmode="numeric" name="precio" id="precio"
                           placeholder="Ej: 5000" maxlength="10"
                           value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
                    <small class="field-hint">Valor en pesos colombianos</small>

                    <label>Fecha de venta</label>
                    <input type="date" name="fecha" id="fecha"
                           max="<?= $hoy ?>"
                           value="<?= htmlspecialchars($_POST['fecha'] ?? $hoy) ?>">

                    <div id="preview-total" class="preview-total" style="display:none;">
                        <span class="preview-total-label">Total estimado</span>
                        <strong id="preview-valor" class="preview-total-valor"></strong>
                    </div>

                    <button type="submit">Registrar Venta</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="Validaciones.js"></script>
<script>
    document.getElementById('cantidad').addEventListener('keypress', e => {
        if (e.key === '.' || e.key === ',') e.preventDefault();
    });
    document.getElementById('cantidad').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, ''); calcularTotal();
    });
    document.getElementById('precio').addEventListener('keypress', e => {
        if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
    });
    document.getElementById('precio').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, ''); calcularTotal();
    });

    // Al elegir producto: rellena el precio y muestra el stock disponible
    document.getElementById('producto_id').addEventListener('change', function () {
        const o = this.options[this.selectedIndex];
        const hint = document.getElementById('hint-stock');
        if (this.value) {
            document.getElementById('precio').value = Math.round(parseFloat(o.dataset.precio));
            hint.textContent = 'Stock disponible: ' + o.dataset.stock;
            document.getElementById('cantidad').max = o.dataset.stock;
        } else {
            hint.textContent = 'Stock disponible: —';
        }
        calcularTotal();
    });

    function calcularTotal() {
        const q = parseInt(document.getElementById('cantidad').value) || 0;
        const p = parseFloat(document.getElementById('precio').value) || 0;
        const d = document.getElementById('preview-total');
        if (q > 0 && p > 0) {
            document.getElementById('preview-valor').textContent =
                '$' + (q * p).toLocaleString('es-CO', {minimumFractionDigits: 0});
            d.style.display = 'flex';
        } else { d.style.display = 'none'; }
    }

    document.getElementById('cliente_id').addEventListener('blur', () => V.select('cliente_id', 'un cliente'));
    document.getElementById('producto_id').addEventListener('blur', () => V.select('producto_id', 'un producto'));
    document.getElementById('cantidad').addEventListener('blur',   () => V.cantidad('cantidad'));
    document.getElementById('precio').addEventListener('blur',     () => V.precio('precio'));
    document.getElementById('fecha').addEventListener('blur',      () => V.fecha('fecha', false));
</script>
</body>
</html>
