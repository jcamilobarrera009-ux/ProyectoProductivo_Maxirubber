<?php
session_start();
require_once 'conexion.php';

// Solo administradores
if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: InicioSesion.php');
    exit;
}

// ── CORRECCIÓN: consultas reales a ventas (antes consultaba clientes) ──
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

// Estadísticas globales
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*)          FROM ventas)     AS total_ventas,
        (SELECT COALESCE(SUM(total),0) FROM ventas) AS total_ingresos,
        (SELECT COUNT(*)          FROM clientes)   AS total_clientes,
        (SELECT COUNT(*)          FROM produccion) AS total_produccion
")->fetch();

// Ventas con filtro de rango de fechas
$params = [];
$where  = [];
if ($desde) { $where[] = 'v.fecha >= ?'; $params[] = $desde; }
if ($hasta)  { $where[] = 'v.fecha <= ?'; $params[] = $hasta; }
$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT v.codigo, v.fecha, c.nombre AS cliente, v.producto, v.cantidad, v.total
    FROM ventas v
    JOIN clientes c ON c.id = v.cliente_id
    $whereStr
    ORDER BY v.fecha DESC, v.id DESC
");
$stmt->execute($params);
$ventas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Reportes</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Reportes de Ventas</h1>
        </div>
        <div style="padding:22px 24px;">

            <!-- STATS -->
            <div class="cards">
                <div class="card">
                    <div class="card-label">Total Ventas</div>
                    <div class="card-numero"><?= number_format($stats['total_ventas']) ?></div>
                </div>
                <div class="card">
                    <div class="card-label">Ingresos Totales</div>
                    <div class="card-numero">$<?= number_format($stats['total_ingresos'], 0, ',', '.') ?></div>
                </div>
                <div class="card">
                    <div class="card-label">Clientes</div>
                    <div class="card-numero"><?= number_format($stats['total_clientes']) ?></div>
                </div>
                <div class="card">
                    <div class="card-label">Producciones</div>
                    <div class="card-numero"><?= number_format($stats['total_produccion']) ?></div>
                </div>
            </div>

            <hr class="separador">

            <!-- FILTRO -->
            <div class="panel" style="margin-bottom:16px;">
                <div class="panel-header">
                    <span class="panel-title">Filtrar por rango de fechas</span>
                </div>
                <form method="GET" style="padding:14px 16px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                    <div>
                        <label>Desde</label>
                        <input type="date" name="desde" style="width:auto;"
                               value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div>
                        <label>Hasta</label>
                        <input type="date" name="hasta" style="width:auto;"
                               value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <button type="submit" style="width:auto;">Generar Reporte</button>
                    <a href="ReportesVenta.php" class="btn btn-ghost" style="width:auto;">Limpiar</a>
                </form>
            </div>

            <!-- TABLA -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><?= count($ventas) ?> resultado(s)</span>
                    <?php if ($desde || $hasta): ?>
                        <span style="font-size:11px; color:var(--text-3);">
                            <?= $desde ? "Desde: $desde" : '' ?>
                            <?= $hasta ? " — Hasta: $hasta" : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th><th>Fecha</th><th>Cliente</th>
                                <th>Producto</th><th>Cantidad</th><th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ventas)): ?>
                                <tr><td colspan="6" style="text-align:center;color:var(--text-3);">
                                    No hay ventas en ese rango.
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($ventas as $v): ?>
                                    <tr>
                                        <td class="td-mono"><?= htmlspecialchars($v['codigo']) ?></td>
                                        <td class="td-mono"><?= $v['fecha'] ?></td>
                                        <td><?= htmlspecialchars($v['cliente']) ?></td>
                                        <td><?= htmlspecialchars($v['producto']) ?></td>
                                        <td class="td-mono"><?= number_format($v['cantidad']) ?></td>
                                        <td class="td-mono">$<?= number_format($v['total'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
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
