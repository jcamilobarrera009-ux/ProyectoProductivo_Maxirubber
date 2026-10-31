<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    die('SIN SESION - vuelve a <a href="InicioSesion.php">iniciar sesión</a>');
}

if (empty($_GET['id'])) {
    die('SIN ID');
}

$stmt = $pdo->prepare("
    SELECT v.*, c.nombre AS cliente, c.telefono, u.usuario AS vendedor
    FROM ventas v
    JOIN clientes c ON c.id = v.cliente_id
    JOIN usuarios u ON u.id = v.usuario_id
    WHERE v.id = ?
    LIMIT 1
");
$stmt->execute([(int)$_GET['id']]);
$venta = $stmt->fetch();

if (!$venta) {
    die('VENTA NO ENCONTRADA - id: ' . (int)$_GET['id']);
}
?>
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
            font-size: 13.5px;
            padding: 48px 56px;
        }

        .factura-empresa {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .factura-sub {
            font-size: 10px;
            color: #71839A;
            text-align: center;
            margin-top: 4px;
            letter-spacing: 1px;
            font-family: monospace;
        }
        .factura-num {
            font-size: 11.5px;
            color: #2E6BE6;
            text-align: center;
            margin-top: 10px;
            font-family: monospace;
        }
        .factura-header {
            padding-bottom: 18px;
            border-bottom: 1px solid #DCE5F0;
            margin-bottom: 18px;
        }

        .factura-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e8eef6;
            font-size: 13px;
        }
        .factura-row:last-of-type { border-bottom: none; }
        .lbl { color: #71839A; }
        .val { font-family: monospace; font-size: 12.5px; color: #14263D; }

        .factura-total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-top: 2px solid #14263D;
            padding-top: 14px;
            margin-top: 8px;
        }
        .factura-total .lbl {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #526780;
        }
        .factura-total .val {
            font-family: monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: #14263D;
        }

        .factura-pie {
            text-align: center;
            margin-top: 24px;
            font-family: monospace;
            font-size: 9px;
            color: #AAB8C8;
            letter-spacing: 0.5px;
        }

        /* Botón solo en pantalla, nunca en impresión */
        .no-print {
            margin-top: 32px;
            text-align: center;
        }
        .no-print button {
            padding: 9px 24px;
            background: #2E6BE6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
        }
        .no-print button:hover { background: #1a55c8; }
        .no-print a {
            display: inline-block;
            margin-left: 12px;
            font-size: 12px;
            color: #71839A;
            text-decoration: none;
        }
        .no-print a:hover { color: #14263D; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            @page { margin: 2cm; size: A4 portrait; }
        }
    </style>
</head>
<body>

<div class="factura-header">
    <div class="factura-empresa">MAXIRUBBER S.A.S</div>
    <div class="factura-sub">SISTEMA DE PRODUCCIÓN, VENTAS Y FACTURACIÓN</div>
    <div class="factura-num">Factura N° <?= htmlspecialchars($venta['codigo']) ?></div>
</div>

<?php
$filas = [
    'Cliente'         => $venta['cliente'],
    'Teléfono'        => $venta['telefono'],
    'Producto'        => $venta['producto'],
    'Cantidad'        => number_format($venta['cantidad']),
    'Precio unitario' => '$' . number_format($venta['precio'], 0, ',', '.'),
    'Fecha'           => $venta['fecha'],
    'Vendedor'        => $venta['vendedor'],
];
foreach ($filas as $lbl => $val): ?>
    <div class="factura-row">
        <span class="lbl"><?= $lbl ?></span>
        <span class="val"><?= htmlspecialchars($val) ?></span>
    </div>
<?php endforeach; ?>

<div class="factura-total">
    <span class="lbl">Total</span>
    <span class="val">$<?= number_format($venta['total'], 0, ',', '.') ?></span>
</div>

<div class="factura-pie">Factura generada por el sistema MAXIRUBBER S.A.S</div>

<div class="no-print">
    <button onclick="window.print()">🖨 Imprimir</button>
    <a href="GenerarFactura.php?id=<?= (int)$_GET['id'] ?>">← Volver</a>
</div>

</body>
</html>