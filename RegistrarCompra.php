<?php
session_start();
require_once 'conexion.php';

// Solo el Administrador registra compras
if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'Administrador') {
    header('Location: InicioSesion.php');
    exit;
}

$msg  = '';
$tipo = '';
$hoy  = date('Y-m-d');

$unidades    = ['kg', 'g', 'lb', 'litros', 'metros', 'bultos', 'unidades'];
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre")->fetchAll();
$existentes  = $pdo->query("SELECT nombre FROM materia_prima ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = (int)($_POST['proveedor_id'] ?? 0);
    $materia      = trim($_POST['materia'] ?? '');
    $cantidad     = (int)preg_replace('/\D/', '', $_POST['cantidad'] ?? '');
    $unidad       = $_POST['unidad'] ?? '';
    $costo_total  = (int)preg_replace('/\D/', '', $_POST['costo_total'] ?? '');
    $fecha        = $_POST['fecha'] ?? '';

    if (!$proveedor_id) {
        $msg = 'Selecciona un proveedor.'; $tipo = 'error';
    } elseif (!preg_match('/^[\p{L}0-9\s\-]{2,60}$/u', $materia)) {
        $msg = 'Escribe el nombre de la materia prima (2 a 60 caracteres, sin símbolos).'; $tipo = 'error';
    } elseif ($cantidad < 1) {
        $msg = 'La cantidad debe ser al menos 1.'; $tipo = 'error';
    } elseif (!in_array($unidad, $unidades, true)) {
        $msg = 'Selecciona la unidad de medida.'; $tipo = 'error';
    } elseif ($costo_total <= 0) {
        $msg = 'El costo total debe ser mayor a 0.'; $tipo = 'error';
    } elseif (!$fecha || $fecha > $hoy) {
        $msg = 'La fecha es obligatoria y no puede ser futura.'; $tipo = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // La materia prima se crea sola la primera vez que se compra
            $b = $pdo->prepare("SELECT id FROM materia_prima WHERE nombre = ? LIMIT 1");
            $b->execute([$materia]);
            $materia_id = $b->fetchColumn();
            if (!$materia_id) {
                $pdo->prepare("INSERT INTO materia_prima (nombre, tipo_materia) VALUES (?, 'General')")
                    ->execute([$materia]);
                $materia_id = $pdo->lastInsertId();
            }

            $pdo->prepare("
                INSERT INTO compra_materia
                    (proveedor_id, materia_id, cantidad, unidad, costo_total, fecha)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$proveedor_id, $materia_id, $cantidad, $unidad, $costo_total, $fecha]);

            $pdo->commit();

            $msg  = "Compra registrada: $cantidad $unidad de $materia. Costo por $unidad: $"
                  . number_format($costo_total / $cantidad, 0, ',', '.') . ".";
            $tipo = 'exito';
            $_POST = [];
            $existentes = $pdo->query("SELECT nombre FROM materia_prima ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $msg = 'No se pudo registrar la compra. Intenta de nuevo.'; $tipo = 'error';
        }
    }
}

// Últimas compras
$compras = $pdo->query("
    SELECT c.fecha, p.nombre AS proveedor, m.nombre AS materia,
           c.cantidad, c.unidad, c.costo_total
    FROM compra_materia c
    JOIN proveedores   p ON p.id = c.proveedor_id
    JOIN materia_prima m ON m.id = c.materia_id
    ORDER BY c.fecha DESC, c.id DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Compra de Materia Prima</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Compra de Materia Prima</h1>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">
                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <form method="POST" novalidate>

                    <label>Proveedor</label>
                    <select name="proveedor_id" id="proveedor_id">
                        <option value="">-- Seleccionar proveedor --</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= (($_POST['proveedor_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores)): ?>
                            <option disabled>⚠ No hay proveedores registrados.</option>
                        <?php endif; ?>
                    </select>

                    <label>Materia prima comprada</label>
                    <input type="text" name="materia" id="materia" maxlength="60" list="lista-materias"
                           placeholder="Ej: Caucho natural" autocomplete="off"
                           value="<?= htmlspecialchars($_POST['materia'] ?? '') ?>">
                    <datalist id="lista-materias">
                        <?php foreach ($existentes as $n): ?>
                            <option value="<?= htmlspecialchars($n) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <small class="field-hint">Si ya se compró antes, aparece en la lista para no repetirla</small>

                    <label>Cantidad comprada</label>
                    <input type="text" inputmode="numeric" name="cantidad" id="cantidad" maxlength="6"
                           placeholder="Ej: 100"
                           value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>">

                    <label>Unidad de medida</label>
                    <select name="unidad" id="unidad">
                        <option value="">-- Seleccionar unidad --</option>
                        <?php foreach ($unidades as $u): ?>
                            <option value="<?= $u ?>" <?= (($_POST['unidad'] ?? '') === $u) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Costo total de la compra ($)</label>
                    <input type="text" inputmode="numeric" name="costo_total" id="costo_total" maxlength="10"
                           placeholder="Ej: 1200000"
                           value="<?= htmlspecialchars($_POST['costo_total'] ?? '') ?>">
                    <small class="field-hint">Solo números, sin puntos ni comas</small>

                    <label>Fecha de compra</label>
                    <input type="date" name="fecha" id="fecha" max="<?= $hoy ?>"
                           value="<?= htmlspecialchars($_POST['fecha'] ?? $hoy) ?>">

                    <div id="preview" class="preview-total" style="display:none;">
                        <span class="preview-total-label" id="preview-label">Costo por unidad</span>
                        <strong id="preview-valor" class="preview-total-valor"></strong>
                    </div>

                    <button type="submit">Registrar Compra</button>
                </form>
            </div>

            <div class="panel" style="margin-top:22px;">
                <div class="panel-header">
                    <span class="panel-title">Últimas compras</span>
                </div>
                <div class="tabla-wrapper">
                <table>
                    <thead>
                        <tr><th>Fecha</th><th>Proveedor</th><th>Materia prima</th>
                            <th>Cantidad</th><th>Costo total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($compras as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['fecha']) ?></td>
                            <td><?= htmlspecialchars($c['proveedor']) ?></td>
                            <td><?= htmlspecialchars($c['materia']) ?></td>
                            <td><?= (int)$c['cantidad'] ?> <?= htmlspecialchars($c['unidad']) ?></td>
                            <td>$<?= number_format($c['costo_total'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($compras)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-3);">Aún no hay compras registradas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    const q = document.getElementById('cantidad');
    const t = document.getElementById('costo_total');
    const u = document.getElementById('unidad');

    // Solo números: bloquea teclas no numéricas y limpia lo pegado
    function soloNumeros(el) {
        el.addEventListener('keypress', e => {
            if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    soloNumeros(q); soloNumeros(t);

    // El nombre de la materia no admite símbolos
    document.getElementById('materia').addEventListener('input', function () {
        this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]/g, '');
    });

    function calc() {
        const c = parseInt(q.value) || 0, v = parseInt(t.value) || 0;
        const d = document.getElementById('preview');
        if (c > 0 && v > 0) {
            document.getElementById('preview-label').textContent =
                'Costo por ' + (u.value || 'unidad');
            document.getElementById('preview-valor').textContent =
                '$' + (v / c).toLocaleString('es-CO', {maximumFractionDigits: 0});
            d.style.display = 'flex';
        } else { d.style.display = 'none'; }
    }
    q.addEventListener('input', calc);
    t.addEventListener('input', calc);
    u.addEventListener('change', calc);
    calc();
</script>
</body>
</html>
