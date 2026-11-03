<?php
session_start();
require_once 'conexion.php';
 
if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php');
    exit;
}
 
function cop($n) { return '$' . number_format((float)$n, 0, ',', '.'); }
 
$id     = (int)($_GET['id'] ?? 0);
$ventas = [];      // lista de ventas (cuando no hay id)
$venta  = null;    // venta a facturar (cuando hay id)
$auto   = false;
 
if ($id <= 0) {
    // Sin id: se muestra la lista para elegir la venta
    $ventas = $pdo->query("
        SELECT v.id, v.codigo, v.fecha, v.producto, v.cantidad, v.total, c.nombre AS cliente
        FROM ventas v
        JOIN clientes c ON c.id = v.cliente_id
        ORDER BY v.fecha DESC, v.id DESC
        LIMIT 100
    ")->fetchAll();
} else {
    // Con id: se carga la venta para mostrar su factura
    $stmt = $pdo->prepare("
        SELECT v.*, c.nombre AS cliente, c.telefono, u.usuario AS vendedor
        FROM ventas v
        JOIN clientes c ON c.id = v.cliente_id
        JOIN usuarios u ON u.id = v.usuario_id
        WHERE v.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $venta = $stmt->fetch();
 
    if (!$venta) {
        die('VENTA NO ENCONTRADA - <a href="GenerarFactura.php">volver a la lista</a>');
    }
    $auto = !empty($_GET['auto']);
}
?>
<?php if ($id <= 0): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Generar Factura</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Generar Factura</h1>
            <div class="topbar-actions">
                <a href="RegistrarVenta.php" class="btn btn-ghost btn-sm">← Volver a ventas</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Selecciona la venta a facturar</span>
                    <span style="font-size:11px;color:var(--text-3);"><?= count($ventas) ?> venta(s)</span>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th><th>Fecha</th><th>Cliente</th>
                                <th>Producto</th><th>Cantidad</th><th>Total</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['codigo']) ?></td>
                                <td><?= htmlspecialchars($v['fecha']) ?></td>
                                <td><?= htmlspecialchars($v['cliente']) ?></td>
                                <td><?= htmlspecialchars($v['producto']) ?></td>
                                <td><?= (int)$v['cantidad'] ?></td>
                                <td><?= cop($v['total']) ?></td>
                                <td><a href="GenerarFactura.php?id=<?= (int)$v['id'] ?>"
                                       class="btn btn-ghost btn-sm" style="width:auto;">Ver factura</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventas)): ?>
                            <tr><td colspan="7" style="text-align:center;color:var(--text-3);">
                                No hay ventas registradas aún.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= htmlspecialchars($venta['codigo']) ?> — MAXIRUBBER</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
 
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #fff;
            color: #14263D;
            font-size: 14px;
            padding: 40px 48px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .hoja { max-width: 760px; margin: 0 auto; }
 
        .f-head {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding-bottom: 16px; border-bottom: 2px solid #14263D; margin-bottom: 20px;
        }
        .f-empresa { font-size: 1.4rem; font-weight: 700; letter-spacing: .5px; }
        .f-sub { font-size: 10px; color: #71839A; letter-spacing: 1px; margin-top: 4px; }
        .f-num { text-align: right; }
        .f-num strong { display: block; font-size: 1.1rem; color: #2E6BE6; }
        .f-num span { font-size: 12px; color: #526780; }
 
        .f-datos {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px;
            margin-bottom: 22px;
        }
        .f-datos .lbl { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #71839A; }
        .f-datos .val { font-size: 14px; }
 
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th {
            background: #F1F5FA; text-align: left; font-size: 11px;
            text-transform: uppercase; letter-spacing: .8px; color: #526780;
            padding: 10px 12px; border-bottom: 1px solid #DCE5F0;
        }
        td { padding: 12px; border-bottom: 1px solid #E8EEF6; }
        .der { text-align: right; }
 
        .f-total {
            display: flex; justify-content: space-between; align-items: baseline;
            border-top: 2px solid #14263D; margin-top: 12px; padding-top: 14px;
        }
        .f-total .lbl { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #526780; }
        .f-total .val { font-size: 1.6rem; font-weight: 700; }
 
        .f-pie { text-align: center; margin-top: 34px; font-size: 10px; color: #AAB8C8; letter-spacing: .5px; }
 
        .no-print { margin-top: 32px; text-align: center; }
        .no-print button {
            padding: 10px 26px; background: #2E6BE6; color: #fff; border: none;
            border-radius: 8px; font-size: 14px; cursor: pointer;
        }
        .no-print button:hover { background: #1a55c8; }
        .no-print a { margin-left: 14px; font-size: 13px; color: #71839A; text-decoration: none; }
        .no-print a:hover { color: #14263D; }
 
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { size: A4 portrait; margin: 14mm; }
        }
    </style>
</head>
<body>
 
<div class="hoja" id="factura">
    <div class="f-head">
        <div>
            <div class="f-empresa">MAXIRUBBER S.A.S</div>
            <div class="f-sub">PRODUCCIÓN, VENTAS Y FACTURACIÓN</div>
        </div>
        <div class="f-num">
            <strong>Factura N° <?= htmlspecialchars($venta['codigo']) ?></strong>
            <span><?= htmlspecialchars($venta['fecha']) ?></span>
        </div>
    </div>
 
    <div class="f-datos">
        <div><span class="lbl">Cliente</span><span class="val"><?= htmlspecialchars($venta['cliente']) ?></span></div>
        <div><span class="lbl">Teléfono</span><span class="val"><?= htmlspecialchars($venta['telefono']) ?></span></div>
        <div><span class="lbl">Vendedor</span><span class="val"><?= htmlspecialchars($venta['vendedor']) ?></span></div>
        <div><span class="lbl">Fecha</span><span class="val"><?= htmlspecialchars($venta['fecha']) ?></span></div>
    </div>
 
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="der">Cantidad</th>
                <th class="der">Precio unitario</th>
                <th class="der">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= htmlspecialchars($venta['producto']) ?></td>
                <td class="der"><?= number_format((int)$venta['cantidad'], 0, ',', '.') ?></td>
                <td class="der"><?= cop($venta['precio']) ?></td>
                <td class="der"><?= cop($venta['total']) ?></td>
            </tr>
        </tbody>
    </table>
 
    <div class="f-total">
        <span class="lbl">Total a pagar</span>
        <span class="val"><?= cop($venta['total']) ?></span>
    </div>
 
    <div class="f-pie">Factura generada por el sistema MAXIRUBBER S.A.S</div>
</div>
 
<div class="no-print">
    <button type="button" onclick="window.print()">🖨 Imprimir</button>
    <a href="GenerarFactura.php">← Volver a la lista de ventas</a>
</div>
 
<?php if ($auto): ?>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
<?php endif; ?>
 

</body>
</html>
<?php endif; ?>

