<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['usuario_id'])) {
    header('Location: InicioSesion.php');
    exit;
}

$sesion = $_SESSION;
$msg    = '';
$tipo   = '';

// Catálogos
$productos = $pdo->query("SELECT id, nombre FROM productos ORDER BY nombre")->fetchAll();
$materias  = $pdo->query("SELECT id, nombre FROM materia_prima ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id   = (int)($_POST['producto_id'] ?? 0);
    $cantidad      = (int)($_POST['cantidad']    ?? 0);
    $fecha         = $_POST['fecha']             ?? '';
    $responsable   = trim($_POST['responsable']  ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    // El costo se limpia: solo dígitos
    $costo         = (int)preg_replace('/\D/', '', $_POST['costo_produccion'] ?? '');

    // Producto elegido
    $nombreProducto = '';
    foreach ($productos as $p) {
        if ((int)$p['id'] === $producto_id) { $nombreProducto = $p['nombre']; }
    }

    // Materias primas marcadas (solo se registra cuáles se usaron)
    $marcadas = array_map('intval', $_POST['materia_id'] ?? []);
    $usadas   = [];
    foreach ($materias as $m) {
        if (in_array((int)$m['id'], $marcadas, true)) { $usadas[] = $m['nombre']; }
    }

    if (!$producto_id || !$nombreProducto) {
        $msg = 'Selecciona un producto del catálogo.'; $tipo = 'error';
    } elseif ($cantidad < 1) {
        $msg = 'La cantidad debe ser al menos 1.'; $tipo = 'error';
    } elseif (!$fecha) {
        $msg = 'La fecha es obligatoria.'; $tipo = 'error';
    } elseif ($fecha > date('Y-m-d')) {
        $msg = 'La fecha no puede ser futura.'; $tipo = 'error';
    } elseif (empty($usadas)) {
        $msg = 'Marca al menos una materia prima usada.'; $tipo = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Guardar la producción
            $pdo->prepare("
                INSERT INTO produccion
                    (producto_id, nombre_producto, cantidad, materia_usada,
                     costo_produccion, responsable, estado, observaciones, fecha)
                VALUES (?, ?, ?, ?, ?, ?, 'Completado', ?, ?)
            ")->execute([
                $producto_id, $nombreProducto, $cantidad,
                mb_substr(implode(', ', $usadas), 0, 255),
                $costo, $responsable ?: null, $observaciones ?: null, $fecha
            ]);

            // 2. SUMAR al inventario del producto terminado
            $pdo->prepare("
                INSERT INTO inventario (producto_id, cantidad) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)
            ")->execute([$producto_id, $cantidad]);

            $pdo->commit();

            $msg  = "Producción de \"$nombreProducto\" registrada. Inventario actualizado: +$cantidad unidades.";
            $tipo = 'exito';
            $_POST = [];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $msg  = 'No se pudo registrar la producción. Intenta de nuevo.';
            $tipo = 'error';
        }
    }
}
$marcadasForm = array_map('intval', $_POST['materia_id'] ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXIRUBBER - Registrar Producción</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Fichas seleccionables: el CSS global usa appearance:none en todos los
           <input>, por eso las casillas normales no se ven. Aquí la casilla va
           oculta y la ficha cambia de color al marcarla. */
        .fichas { display:flex; flex-wrap:wrap; gap:8px; padding:2px 0; }
        .ficha  { position:relative; margin:0; cursor:pointer; }
        .ficha input { position:absolute; opacity:0; width:0; height:0; padding:0; border:0; }
        .ficha span {
            display:inline-block; padding:8px 16px; font-size:13px;
            border:1px solid var(--border); border-radius:999px;
            background:var(--bg-3); color:var(--text-2);
            transition:background .15s, color .15s, border-color .15s;
            user-select:none;
        }
        .ficha:hover span { border-color:var(--blue); }
        .ficha input:checked + span { background:var(--blue); border-color:var(--blue); color:#fff; }
        .ficha input:checked + span::before { content:"✓ "; }
        .ficha input:focus-visible + span { box-shadow:0 0 0 3px rgba(46,107,230,.25); }
    </style>
</head>
<body>
<div class="dashboard">
    <?php include 'nav_php.php'; ?>
    <main class="contenido">
        <div class="topbar">
            <h1>Registrar Producción</h1>
            <div class="topbar-actions">
                <a href="ConsultarProduccion.php" class="btn btn-ghost btn-sm">Ver registros →</a>
            </div>
        </div>
        <div style="padding:22px 24px;">
            <div class="form-interior">
                <?php if ($msg): ?>
                    <div class="mensaje <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <form method="POST" novalidate>

                    <label>Producto fabricado</label>
                    <select name="producto_id" id="producto_id">
                        <option value="">-- Seleccionar del catálogo --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= (($_POST['producto_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-hint">El inventario de este producto aumentará automáticamente</small>

                    <label>Cantidad producida</label>
                    <input type="text" inputmode="numeric" name="cantidad" id="cantidad"
                           placeholder="Ej: 200" maxlength="6"
                           value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>">
                    <small class="field-hint">Solo números enteros positivos</small>

                    <label>Fecha de producción</label>
                    <input type="date" name="fecha" id="fecha"
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['fecha'] ?? date('Y-m-d')) ?>">

                    <label>Responsable</label>
                    <input type="text" name="responsable" id="responsable"
                           placeholder="Ej: Carlos López" maxlength="60"
                           value="<?= htmlspecialchars($_POST['responsable'] ?? $sesion['usuario']) ?>">

                    <label>Materia prima usada</label>
                    <?php if (empty($materias)): ?>
                        <small class="field-hint">
                            Aún no hay materias primas. Aparecen aquí cuando el Administrador registra una compra de materia prima.
                        </small>
                    <?php else: ?>
                        <div class="fichas">
                            <?php foreach ($materias as $m): ?>
                                <label class="ficha">
                                    <input type="checkbox" name="materia_id[]" value="<?= $m['id'] ?>"
                                           <?= in_array((int)$m['id'], $marcadasForm, true) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($m['nombre']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="field-hint">Toca las materias primas que se usaron en este lote (se pueden elegir varias)</small>
                    <?php endif; ?>

                    <label>Costo de producción ($) <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span></label>
                    <input type="text" inputmode="numeric" name="costo_produccion" id="costo_produccion"
                           placeholder="Ej: 150000" maxlength="10"
                           value="<?= htmlspecialchars($_POST['costo_produccion'] ?? '') ?>">
                    <small class="field-hint">Solo números, sin puntos ni comas</small>

                    <label>Observaciones <span style="color:var(--text-3);font-size:.8rem;">(opcional)</span></label>
                    <textarea name="observaciones" rows="3"
                              placeholder="Notas adicionales sobre este lote..."><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>

                    <button type="submit">Registrar Producción</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="Validaciones.js"></script>
<script>
    // Solo números: bloquea teclas no numéricas y limpia lo pegado
    function soloNumeros(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('keypress', e => {
            if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
        });
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    soloNumeros('cantidad');
    soloNumeros('costo_produccion');
    V.bloquear('responsable', /[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/);
    document.getElementById('cantidad').addEventListener('blur', () => V.cantidad('cantidad'));
    document.getElementById('fecha').addEventListener('blur',    () => V.fecha('fecha', false));
</script>
</body>
</html>
