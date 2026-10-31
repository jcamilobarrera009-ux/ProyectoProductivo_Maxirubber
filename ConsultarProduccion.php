<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php'); exit;
}

// ── ELIMINAR (revierte el inventario del producto) ───────────────
$errorElim = '';
if (isset($_GET['eliminar'])) {
    $idElim = (int)$_GET['eliminar'];
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            "SELECT producto_id, cantidad, estado FROM produccion WHERE id = ? FOR UPDATE"
        );
        $st->execute([$idElim]);
        $prod = $st->fetch();

        if ($prod && $prod['estado'] === 'Completado') {
            // 1. Quitar del inventario lo que se había sumado
            if ($prod['producto_id']) {
                $inv = $pdo->prepare(
                    "SELECT cantidad FROM inventario WHERE producto_id = ? FOR UPDATE"
                );
                $inv->execute([$prod['producto_id']]);
                $disp = (int)($inv->fetchColumn() ?: 0);
                if ($disp < $prod['cantidad']) {
                    throw new Exception(
                        'No se puede eliminar: parte de este lote ya salió del inventario.'
                    );
                }
                $pdo->prepare(
                    "UPDATE inventario SET cantidad = cantidad - ? WHERE producto_id = ?"
                )->execute([$prod['cantidad'], $prod['producto_id']]);
            }
        }

        $pdo->prepare("DELETE FROM produccion WHERE id = ?")->execute([$idElim]);
        $pdo->commit();
        header('Location: ConsultarProduccion.php?msg=eliminado'); exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $errorElim = $e->getMessage();
    }
}

$buscar = trim($_GET['q'] ?? '');

if ($buscar !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM produccion WHERE nombre_producto LIKE ?
         ORDER BY fecha DESC, id DESC"
    );
    $stmt->execute(["%$buscar%"]);
} else {
    $stmt = $pdo->query(
        "SELECT * FROM produccion ORDER BY fecha DESC, id DESC"
    );
}
$registros = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Consultar Producción</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Registros de Producción</h1>
            <div class="topbar-actions">
                <a href="RegistrarProduccion.php" class="btn btn-sm">+ Nueva Producción</a>
            </div>
        </div>
        <div style="padding:22px 24px;">

            <?php if ($errorElim): ?>
                <div class="mensaje error" style="margin-bottom:14px;"><?= htmlspecialchars($errorElim) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
                <div class="mensaje exito" style="margin-bottom:14px;">Registro eliminado correctamente.</div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><?= count($registros) ?> registro(s)</span>
                    <form method="GET" style="display:flex;gap:8px;">
                        <input class="search" type="text" name="q"
                               placeholder="Buscar por producto..."
                               value="<?= htmlspecialchars($buscar) ?>">
                        <button type="submit" class="btn btn-sm" style="width:auto;">Buscar</button>
                        <?php if ($buscar): ?>
                            <a href="ConsultarProduccion.php" class="btn btn-ghost btn-sm">✕</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th><th>Cantidad</th><th>Estado</th>
                                <th>Responsable</th><th>Costo</th><th>Fecha</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registros)): ?>
                                <tr><td colspan="7" style="text-align:center;color:var(--text-3);">
                                    No hay registros de producción.
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($registros as $r): ?>
                                    <?php
                                    $badgeClass = match($r['estado']) {
                                        'Completado' => 'badge-green',
                                        'En proceso' => 'badge-blue',
                                        'Cancelado'  => 'badge-red',
                                        default      => 'badge-gray'
                                    };
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['nombre_producto']) ?></td>
                                        <td class="td-mono"><?= number_format($r['cantidad']) ?></td>
                                        <td><span class="badge <?= $badgeClass ?>"><?= $r['estado'] ?></span></td>
                                        <td><?= htmlspecialchars($r['responsable'] ?: '-') ?></td>
                                        <td class="td-mono">
                                            <?= $r['costo_produccion'] > 0
                                                ? '$' . number_format($r['costo_produccion'], 0, ',', '.')
                                                : '-' ?>
                                        </td>
                                        <td class="td-mono"><?= $r['fecha'] ?></td>
                                        <td class="td-actions">
                                            <a href="EditarProduccion.php?id=<?= $r['id'] ?>"
                                               class="btn btn-sm btn-ghost">✏ Editar</a>
                                            <a href="ConsultarProduccion.php?eliminar=<?= $r['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Eliminar este registro de producción?')">
                                               🗑 Eliminar
                                            </a>
                                        </td>
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
