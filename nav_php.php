<?php
$paginaActual = basename($_SERVER['PHP_SELF']);
$esAdmin      = ($_SESSION['rol'] ?? '') === 'Administrador';
$iniciales    = strtoupper(substr($_SESSION['usuario'] ?? 'US', 0, 2));

$grupos = [
    'Usuarios'   => [
        ['href' => 'GestionUsuario.php',      'texto' => '👤 Gestión de Usuarios',  'admin' => true],
    ],
    'Clientes'   => [
        ['href' => 'RegistrarCliente.php',    'texto' => '➕ Registrar Cliente',     'admin' => false],
        ['href' => 'ConsultarClientes.php',   'texto' => '🔍 Consultar Clientes',    'admin' => false],
    ],
    'Producción' => [
        ['href' => 'RegistrarProduccion.php', 'texto' => '🏭 Registrar Producción',  'admin' => false],
        ['href' => 'ConsultarProduccion.php', 'texto' => '📋 Consultar Producción',  'admin' => false],
    ],
    'Inventario' => [
        ['href' => 'Inventario.php',          'texto' => '📈 Ver Inventario',        'admin' => false],
        ['href' => 'RegistrarProducto.php',   'texto' => '🏷️ Registrar Producto',    'admin' => true],
    ],
    'Compras'    => [
        ['href' => 'RegistrarCompra.php',     'texto' => '📦 Compra de Materia Prima', 'admin' => true],
        ['href' => 'RegistrarProveedor.php',  'texto' => '🚚 Registrar Proveedor',   'admin' => true],
    ],
    'Ventas'     => [
        ['href' => 'RegistrarVenta.php',      'texto' => '🛒 Registrar Venta',       'admin' => false],
        ['href' => 'GenerarFactura.php',      'texto' => '🧾 Generar Factura',       'admin' => false],
        ['href' => 'ReportesVenta.php',       'texto' => '📊 Reportes',              'admin' => true],
    ],
];
?>
<nav class="menu">
    <div class="menu-brand">
        <div class="menu-brand-name">Maxirubber</div>
        <div class="menu-brand-tag">S.A.S · Gestión</div>
    </div>
    <div class="menu-nav">
        <?php foreach ($grupos as $label => $items): ?>
            <?php $visibles = array_filter($items, fn($i) => !$i['admin'] || $esAdmin); ?>
            <?php if (empty($visibles)) continue; ?>
            <div class="menu-titulo"><?= $label ?></div>
            <?php foreach ($visibles as $item): ?>
                <a href="<?= $item['href'] ?>"
                   class="<?= $paginaActual === $item['href'] ? 'activo' : '' ?>">
                    <?= $item['texto'] ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <div class="menu-footer">
        <div class="menu-user">
            <div class="menu-avatar"><?= $iniciales ?></div>
            <div>
                <div class="menu-user-name"><?= htmlspecialchars($_SESSION['usuario'] ?? '') ?></div>
                <div class="menu-user-role"><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></div>
            </div>
        </div>
        <a href="logout.php" class="cerrar-sesion"> Cerrar sesión</a>
    </div>
</nav>
