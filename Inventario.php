<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php');
    exit;
}

// ── Umbrales de alerta (cámbialos aquí cuando quieras) ───────────
const MINIMO_PRODUCTO = 20;   // unidades de producto terminado

// Estado de stock según el umbral
function estadoStock(int $stock, int $minimo): array {
    if ($stock <= 0)       { return ['Sin stock',   'badge-red']; }
    if ($stock <= $minimo) { return ['Stock bajo',  'badge-amber']; }
    return                          ['Normal',      'badge-green'];
}

// Producto terminado (valor al precio de venta)
$productos = $pdo->query("
    SELECT p.nombre, p.talla, p.precio,
           COALESCE(i.cantidad, 0) AS stock,
           COALESCE(i.cantidad, 0) * p.precio AS valor
    FROM productos p
    LEFT JOIN inventario i ON i.producto_id = p.id
    ORDER BY p.nombre
")->fetchAll();

// Totales
$totalProductos = array_sum(array_column($productos, 'stock'));
$valorProductos = array_sum(array_column($productos, 'valor'));

$alertas = 0;
foreach ($productos as $p) { if ($p['stock'] <= MINIMO_PRODUCTO) { $alertas++; } }

// Movimientos recientes (últimos 8, de producción y ventas)
$movimientos = $pdo->query("
    (SELECT fecha, 'Producción' AS tipo, nombre_producto AS detalle,
            cantidad AS cantidad, '+ producto' AS efecto, created_at
       FROM produccion WHERE estado = 'Completado')
    UNION ALL
    (SELECT fecha, 'Venta', producto, cantidad, '- producto', created_at
       FROM ventas WHERE producto_id IS NOT NULL)
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Inventario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Inventario</h1>
        </div>
        <div style="padding:22px 24px;">

            <div class="cards">
                <div class="card">
                    <div class="card-label">Unidades terminadas</div>
                    <div class="card-numero"><?= number_format($totalProductos, 0, ',', '.') ?></div>
                </div>
                <div class="card">
                    <div class="card-label">Valor productos (precio venta)</div>
                    <div class="card-numero">$<?= number_format($valorProductos, 0, ',', '.') ?></div>
                </div>
                <div class="card">
                    <div class="card-label">Alertas de stock</div>
                    <div class="card-numero"><?= $alertas ?></div>
                </div>
            </div>

            <hr class="separador">

            <!-- PRODUCTO TERMINADO -->
            <div class="panel" style="margin-bottom:20px;">
                <div class="panel-header">
                    <span class="panel-title">Producto terminado</span>
                    <span style="font-size:11px;color:var(--text-3);">
                        Alerta si hay <?= MINIMO_PRODUCTO ?> unidades o menos
                    </span>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr><th>Producto</th><th>Talla</th><th>Precio</th>
                                <th>Stock</th><th>Valor</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productos as $p):
                            [$txt, $cls] = estadoStock((int)$p['stock'], MINIMO_PRODUCTO); ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars($p['talla'] ?? '—') ?></td>
                                <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                                <td><?= (int)$p['stock'] ?></td>
                                <td>$<?= number_format($p['valor'], 0, ',', '.') ?></td>
                                <td><span class="badge <?= $cls ?>"><?= $txt ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($productos)): ?>
                            <tr><td colspan="6" style="text-align:center;color:var(--text-3);">
                                No hay productos en el catálogo.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOVIMIENTOS RECIENTES -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Movimientos recientes</span>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr><th>Fecha</th><th>Tipo</th><th>Detalle</th>
                                <th>Cantidad</th><th>Efecto</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($movimientos as $mv): ?>
                            <tr>
                                <td><?= htmlspecialchars($mv['fecha']) ?></td>
                                <td><?= htmlspecialchars($mv['tipo']) ?></td>
                                <td><?= htmlspecialchars($mv['detalle']) ?></td>
                                <td><?= (int)$mv['cantidad'] ?></td>
                                <td><?= htmlspecialchars($mv['efecto']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($movimientos)): ?>
                            <tr><td colspan="5" style="text-align:center;color:var(--text-3);">
                                Aún no hay movimientos.</td></tr>
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
