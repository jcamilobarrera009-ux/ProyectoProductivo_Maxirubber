-- ================================================================
--  MAXIRUBBER S.A.S — Cambio para ventas con inventario automático
--  Ejecutar UNA SOLA VEZ en phpMyAdmin (pestaña SQL).
--  Las ventas antiguas quedan con producto_id vacío y siguen
--  funcionando igual en facturas y reportes.
-- ================================================================
USE maxirubber;

ALTER TABLE ventas
    ADD COLUMN producto_id INT NULL AFTER usuario_id,
    ADD CONSTRAINT fk_ventas_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT;

-- La materia prima usada ahora puede listar varias materias
ALTER TABLE produccion MODIFY materia_usada VARCHAR(255) DEFAULT NULL;

-- Unidad de medida en la compra de materia prima
ALTER TABLE compra_materia
    ADD COLUMN unidad VARCHAR(20) NOT NULL DEFAULT 'kg' AFTER cantidad;
