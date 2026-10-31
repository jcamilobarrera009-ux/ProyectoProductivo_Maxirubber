<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php'); exit;
}

$id  = (int)($_GET['id'] ?? 0);
$msg = ''; $tipo = '';

// Buscar registro existente
$stmt = $pdo->prepare("SELECT * FROM produccion WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$registro = $stmt->fetch();

if (!$registro) {
    header('Location: ConsultarProduccion.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_producto  = trim($_POST['nombre_producto']    ?? '');
    // cantidad y estado NO se editan: mueven inventario y materia prima
    $cantidad         = (int)$registro['cantidad'];
    $fecha            = $_POST['fecha']                    ?? '';
    $estado           = $registro['estado'];
    $responsable      = trim($_POST['responsable']         ?? '');
    $costo_produccion = (int)preg_replace('/\D/', '', $_POST['costo_produccion'] ?? '');
    $materia_usada    = trim($_POST['materia_usada']       ?? '');
    $observaciones    = trim($_POST['observaciones']       ?? '');

    if (!$nombre_producto) {
        $msg = 'El nombre del producto es obligatorio.'; $tipo = 'error';
    } elseif ($cantidad < 1) {
        $msg = 'La cantidad debe ser al menos 1.'; $tipo = 'error';
    } elseif (!$fecha) {
        $msg = 'La fecha es obligatoria.'; $tipo = 'error';
    } else {
        $pdo->prepare("
            UPDATE produccion SET
                nombre_producto  = ?,
                cantidad         = ?,
                fecha            = ?,
                estado           = ?,
                responsable      = ?,
                costo_produccion = ?,
                materia_usada    = ?,
                observaciones    = ?
            WHERE id = ?
        ")->execute([
            $nombre_producto,
            $cantidad,
            $fecha,
            $estado,
            $responsable      ?: null,
            $costo_produccion ?: 0,
            $materia_usada    ?: null,
            $observaciones    ?: null,
            $id
        ]);

        $msg = 'Producción actualizada con éxito.'; $tipo = 'exito';
        $stmt->execute([$id]);
        $registro = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Editar Producción</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Editar Producción</h1>
            <div class="topbar-actions">
                <a href="ConsultarProduccion.php" class="btn btn-ghost btn-sm">← Volver</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">

                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>

                    <label>Nombre del producto</label>
                    <input type="text" name="nombre_producto" id="nombre_producto" maxlength="60"
                           value="<?= htmlspecialchars($_POST['nombre_producto'] ?? $registro['nombre_producto']) ?>">

                    <label>Cantidad producida</label>
                    <input type="number" name="cantidad" id="cantidad" readonly
                           value="<?= htmlspecialchars($registro['cantidad']) ?>">
                    <small class="field-hint">La cantidad no se edita porque ya movió el inventario. Para corregirla, elimina el registro y créalo de nuevo.</small>

                    <label>Fecha de producción</label>
                    <input type="date" name="fecha" id="fecha"
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['fecha'] ?? $registro['fecha']) ?>">

                    <label>Estado</label>
                    <select name="estado" disabled>
                        <?php foreach (['Completado','En proceso','Cancelado'] as $e): ?>
                            <option value="<?= $e ?>"
                                <?= (($_POST['estado'] ?? $registro['estado']) === $e) ? 'selected' : '' ?>>
                                <?= $e ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Responsable</label>
                    <input type="text" name="responsable" id="responsable" maxlength="60"
                           value="<?= htmlspecialchars($_POST['responsable'] ?? $registro['responsable'] ?? '') ?>">

                    <label>Costo de producción ($)
                        <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span>
                    </label>
                    <input type="text" inputmode="numeric" name="costo_produccion" id="costo_produccion" maxlength="10"
                           value="<?= htmlspecialchars($_POST['costo_produccion'] ?? $registro['costo_produccion']) ?>">

                    <label>Materia prima usada
                        <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span>
                    </label>
                    <input type="text" name="materia_usada" maxlength="255"
                           value="<?= htmlspecialchars($_POST['materia_usada'] ?? $registro['materia_usada'] ?? '') ?>">

                    <label>Observaciones
                        <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span>
                    </label>
                    <textarea name="observaciones" rows="3"><?= htmlspecialchars($_POST['observaciones'] ?? $registro['observaciones'] ?? '') ?></textarea>

                    <button type="submit">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="Validaciones.js">    const costo = document.getElementById('costo_produccion');
    costo.addEventListener('keypress', e => {
        if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
    });
    costo.addEventListener('input', function () { this.value = this.value.replace(/[^0-9]/g, ''); });
</script>
<script>
    V.bloquear('nombre_producto', /[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]/);
    document.getElementById('cantidad').addEventListener('keypress', e => {
        if (e.key === '.' || e.key === ',') e.preventDefault();
    });
    document.getElementById('cantidad').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>
</body>
</html>