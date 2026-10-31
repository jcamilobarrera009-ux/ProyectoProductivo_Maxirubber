<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php'); exit;
}

// ── ELIMINAR ─────────────────────────────────────────────────────
if (isset($_GET['eliminar'])) {
    $idElim = (int)$_GET['eliminar'];
    try {
        $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$idElim]);
        header('Location: ConsultarClientes.php?msg=eliminado'); exit;
    } catch (PDOException $e) {
        header('Location: ConsultarClientes.php?error=tiene_ventas'); exit;
    }
}

$buscar = trim($_GET['q'] ?? '');

if ($buscar !== '') {
    $stmt = $pdo->prepare(
        "SELECT *, DATE_FORMAT(created_at,'%d/%m/%Y') AS fecha
         FROM clientes WHERE nombre LIKE ? ORDER BY nombre"
    );
    $stmt->execute(["%$buscar%"]);
} else {
    $stmt = $pdo->query(
        "SELECT *, DATE_FORMAT(created_at,'%d/%m/%Y') AS fecha
         FROM clientes ORDER BY nombre"
    );
}
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Consultar Clientes</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Listado de Clientes</h1>
            <div class="topbar-actions">
                <a href="RegistrarCliente.php" class="btn btn-sm">+ Nuevo Cliente</a>
            </div>
        </div>
        <div style="padding:22px 24px;">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
                <div class="mensaje exito" style="margin-bottom:14px;">Cliente eliminado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'tiene_ventas'): ?>
                <div class="mensaje error" style="margin-bottom:14px;">No se puede eliminar: el cliente tiene ventas registradas.</div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><?= count($clientes) ?> cliente(s)</span>
                    <form method="GET" style="display:flex;gap:8px;">
                        <input class="search" type="text" name="q"
                               placeholder="Buscar por nombre..."
                               value="<?= htmlspecialchars($buscar) ?>">
                        <button type="submit" class="btn btn-sm" style="width:auto;">Buscar</button>
                        <?php if ($buscar): ?>
                            <a href="ConsultarClientes.php" class="btn btn-ghost btn-sm">✕</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th><th>Teléfono</th>
                                <th>Correo</th><th>Registrado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--text-3);">
                                    No hay clientes registrados.
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($clientes as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                                        <td class="td-mono"><?= htmlspecialchars($c['telefono']) ?></td>
                                        <td><?= htmlspecialchars($c['correo'] ?: '-') ?></td>
                                        <td class="td-mono"><?= $c['fecha'] ?></td>
                                        <td class="td-actions">
                                            <a href="EditarCliente.php?id=<?= $c['id'] ?>"
                                               class="btn btn-sm btn-ghost">✏ Editar</a>
                                            <a href="ConsultarClientes.php?eliminar=<?= $c['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('¿Eliminar a <?= htmlspecialchars($c['nombre']) ?>?')">
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