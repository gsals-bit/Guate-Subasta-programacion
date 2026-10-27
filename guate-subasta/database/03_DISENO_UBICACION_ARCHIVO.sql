-- Ejecutar una sola vez DESPUES de 01 (instalacion nueva) o 02 (base existente).
USE guate_subasta_lab;
ALTER TABLE articulos ADD COLUMN archivado TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE articulos ADD COLUMN ubicacion_nombre VARCHAR(200) NULL;
ALTER TABLE articulos ADD COLUMN ubicacion_lat DECIMAL(10,7) NULL;
ALTER TABLE articulos ADD COLUMN ubicacion_lng DECIMAL(10,7) NULL;
CREATE INDEX idx_articulos_archivado ON articulos(archivado);
