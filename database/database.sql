-- =====================================================================
--  MAXIRUBBER S.A.S. - Script de base de datos
--  Motor: MySQL 5.7+ / MariaDB 10.3+  (InnoDB, utf8mb4)
--  Ajusta el nombre en conexion.php: new PDO("mysql:host=localhost;dbname=maxirubber;charset=utf8mb4", ...)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS maxirubber
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE maxirubber;

-- Limpia solo las tablas (no la carpeta de la base), para evitar el error #1010 en Windows
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS produccion;
DROP TABLE IF EXISTS inventario;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS compra_materia;
DROP TABLE IF EXISTS materia_prima;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USUARIOS
--    Pantallas: InicioSesion, RegistrarUsuario, GestionUsuario, EditarUsuario
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario     VARCHAR(20)  NOT NULL,
    contrasena  VARCHAR(255) NOT NULL,          -- el código actual la compara en texto plano
    rol         ENUM('Administrador','Empleado') NOT NULL DEFAULT 'Empleado',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. CLIENTES
--    Pantallas: RegistrarCliente, ConsultarClientes, EditarCliente
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)  NOT NULL,
    telefono    VARCHAR(10)  NOT NULL,
    correo      VARCHAR(80)  NULL,
    direccion   VARCHAR(100) NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clientes_telefono (telefono),
    KEY idx_clientes_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. PROVEEDORES
--    Pantalla: RegistrarProveedor (y el select de RegistrarCompra)
-- ---------------------------------------------------------------------
CREATE TABLE proveedores (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)  NOT NULL,
    telefono    VARCHAR(10)  NOT NULL,
    direccion   VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_proveedores_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. MATERIA PRIMA (catálogo; se crea sola al comprar por primera vez)
--    Pantallas: RegistrarCompra, RegistrarProduccion
-- ---------------------------------------------------------------------
CREATE TABLE materia_prima (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(60)  NOT NULL,
    tipo_materia  VARCHAR(50)  NOT NULL DEFAULT 'General',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_materia_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. COMPRA DE MATERIA PRIMA
--    Pantalla: RegistrarCompra
-- ---------------------------------------------------------------------
CREATE TABLE compra_materia (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proveedor_id  INT UNSIGNED NOT NULL,
    materia_id    INT UNSIGNED NOT NULL,
    cantidad      INT UNSIGNED NOT NULL,
    unidad        ENUM('kg','g','lb','litros','metros','bultos','unidades') NOT NULL,
    costo_total   DECIMAL(14,2) NOT NULL,
    fecha         DATE         NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_compra_fecha (fecha),
    CONSTRAINT fk_compra_proveedor FOREIGN KEY (proveedor_id)
        REFERENCES proveedores (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compra_materia FOREIGN KEY (materia_id)
        REFERENCES materia_prima (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_compra_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_compra_costo    CHECK (costo_total > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. PRODUCTOS (catálogo de producto terminado)
--    Pantallas: RegistrarProducto, RegistrarVenta, RegistrarProduccion, Inventario
-- ---------------------------------------------------------------------
CREATE TABLE productos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)  NOT NULL,
    tipo        VARCHAR(50)  NOT NULL,
    talla       VARCHAR(20)  NULL,
    precio      DECIMAL(12,2) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_productos_nombre_talla (nombre, talla),
    CONSTRAINT chk_productos_precio CHECK (precio > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. INVENTARIO (una fila por producto; "ON DUPLICATE KEY" depende del UNIQUE)
--    Pantallas: Inventario, RegistrarProduccion (suma), RegistrarVenta (resta)
-- ---------------------------------------------------------------------
CREATE TABLE inventario (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id  INT UNSIGNED NOT NULL,
    cantidad     INT NOT NULL DEFAULT 0,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_inventario_producto (producto_id),
    CONSTRAINT fk_inventario_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_inventario_cantidad CHECK (cantidad >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. PRODUCCIÓN
--    Pantallas: RegistrarProduccion, ConsultarProduccion, EditarProduccion
-- ---------------------------------------------------------------------
CREATE TABLE produccion (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id       INT UNSIGNED NULL,
    nombre_producto   VARCHAR(60)  NOT NULL,
    cantidad          INT UNSIGNED NOT NULL,
    materia_usada     VARCHAR(255) NULL,
    costo_produccion  DECIMAL(14,2) NOT NULL DEFAULT 0,
    responsable       VARCHAR(60)  NULL,
    estado            ENUM('Completado','En proceso','Cancelado') NOT NULL DEFAULT 'Completado',
    observaciones     TEXT         NULL,
    fecha             DATE         NOT NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_produccion_fecha (fecha),
    KEY idx_produccion_producto (nombre_producto),
    CONSTRAINT fk_produccion_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_produccion_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. VENTAS
--    Pantallas: RegistrarVenta, GenerarFactura, ReportesVenta, Inventario
--    cliente_id y usuario_id con RESTRICT: así ConsultarClientes y
--    GestionUsuario detectan que no se puede borrar quien tiene ventas.
-- ---------------------------------------------------------------------
CREATE TABLE ventas (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(10)  NOT NULL,
    cliente_id   INT UNSIGNED NOT NULL,
    usuario_id   INT UNSIGNED NOT NULL,
    producto_id  INT UNSIGNED NULL,
    producto     VARCHAR(60)  NOT NULL,
    cantidad     INT UNSIGNED NOT NULL,
    precio       DECIMAL(12,2) NOT NULL,
    total        DECIMAL(14,2) NOT NULL,
    fecha        DATE         NOT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ventas_codigo (codigo),
    KEY idx_ventas_fecha (fecha),
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ventas_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_ventas_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_ventas_precio   CHECK (precio > 0)
) ENGINE=InnoDB;

-- =====================================================================
--  DATOS INICIALES
-- =====================================================================

-- Administrador inicial  (usuario: admin  /  contraseña: admin123)  -> cámbiala al entrar
INSERT INTO usuarios (usuario, contrasena, rol) VALUES
    ('admin',    'admin123',  'Administrador'),
    ('empleado', 'empleado1', 'Empleado');

-- Productos de ejemplo (cada uno con su fila de inventario en 0)
INSERT INTO productos (nombre, tipo, talla, precio) VALUES
    ('Suela de caucho', 'Suela',  '38-42', 25000),
    ('Suela de caucho', 'Suela',  '43-45', 27000),
    ('Tapete de caucho','Tapete', NULL,    45000);

INSERT INTO inventario (producto_id, cantidad)
SELECT id, 0 FROM productos;

-- Proveedor y cliente de ejemplo (opcional, bórralos si no los quieres)
INSERT INTO proveedores (nombre, telefono, direccion) VALUES
    ('Cauchos del Eje S.A.S', '3101234567', 'Calle 20 # 10-15, Pereira');

INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES
    ('Maria Garcia Lopez', '3001234567', 'maria@correo.com', 'Calle 10 #5-20, Bogotá');