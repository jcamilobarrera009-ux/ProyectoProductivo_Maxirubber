-- ================================================================
--  MAXIRUBBER S.A.S — Datos de prueba para el inventario automático
--  Ejecutar DESPUÉS de database.sql (pestaña SQL de phpMyAdmin).
--  El inventario aplica solo al producto terminado (suelas).
-- ================================================================
USE maxirubber;

-- Materias primas (solo nombre)
INSERT INTO materia_prima (nombre, tipo_materia) VALUES
('Caucho natural', 'General'),
('Negro de humo',  'General'),
('Azufre',         'General');

-- Productos del catálogo
INSERT INTO productos (nombre, tipo, talla, precio) VALUES
('Suela de caucho', 'Suela', '38-42', 25000),
('Tapete de caucho', 'Tapete', 'Único', 40000);

-- Inventario inicial de producto terminado en 0
INSERT INTO inventario (producto_id, cantidad)
SELECT id, 0 FROM productos
WHERE id NOT IN (SELECT producto_id FROM inventario);
