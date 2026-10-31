<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: InicioSesion.php'); exit;
}

$errorElim = '';

// ── ELIMINAR ─────────────────────────────────────────────────────
if (isset($_GET['eliminar'])) {
    $idElim = (int)$_GET['eliminar'];

    if ($idElim === (int)$_SESSION['usuario_id']) {
        $errorElim = "No puedes eliminar tu propia cuenta.";
    } else {
        // Verificar si el usuario tiene ventas registradas (FK constraint)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE usuario_id = ?");
        $stmtCheck->execute([$idElim]);
        $tieneVentas = (int)$stmtCheck->fetchColumn();

        if ($tieneVentas > 0) {
            $errorElim = "No se puede eliminar este usuario porque tiene {$tieneVentas} venta(s) registrada(s).";
        } else {
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$idElim]);
            header('Location: GestionUsuario.php?msg=eliminado'); exit;
        }
    }
}

$usuarios = $pdo->query(
    "SELECT id, usuario, rol, DATE_FORMAT(created_at,'%d/%m/%Y') AS fecha
     FROM usuarios ORDER BY rol, usuario"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Gestión de Usuarios</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Gestión de Usuarios</h1>
            <div class="topbar-actions">
                <a href="RegistrarUsuario.php" class="btn btn-sm">+ Nuevo Usuario</a>
            </div>
        </div>
        <div style="padding:22px 24px;">

            <?php if (!empty($errorElim)): ?>
                <div class="mensaje error" style="margin-bottom:14px;">
                    <?= htmlspecialchars($errorElim) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
                <div class="mensaje exito" style="margin-bottom:14px;">
                    Usuario eliminado correctamente.
                </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title"><?= count($usuarios) ?> usuario(s) registrado(s)</span>
                </div>
                <div class="tabla-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Registrado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;color:var(--text-3);">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <?php $esMismo = (int)$u['id'] === (int)$_SESSION['usuario_id']; ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($u['usuario']) ?>
                                            <?php if ($esMismo): ?>
                                                <span class="badge badge-blue" style="margin-left:6px;">Tú</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $u['rol'] === 'Administrador' ? 'badge-amber' : 'badge-gray' ?>">
                                                <?= $u['rol'] ?>
                                            </span>
                                        </td>
                                        <td class="td-mono"><?= $u['fecha'] ?></td>
                                        <td class="td-actions">
                                            <a href="EditarUsuario.php?id=<?= $u['id'] ?>"
                                               class="btn btn-sm btn-ghost">✏ Editar</a>
                                            <?php if (!$esMismo): ?>
                                                <a href="GestionUsuario.php?eliminar=<?= $u['id'] ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['usuario'], ENT_QUOTES) ?>?')">
                                                   🗑 Eliminar
                                                </a>
                                            <?php else: ?>
                                                <span style="color:var(--text-3);font-size:11px;">—</span>
                                            <?php endif; ?>
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