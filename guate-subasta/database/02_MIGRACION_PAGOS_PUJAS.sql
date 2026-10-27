-- Ejecutar SOLO si ya tienes guate_subasta_lab instalada; una sola vez.
USE guate_subasta_lab;

-- EXTENSION: pago ficticio y cancelacion logica de pujas.
ALTER TABLE pujas ADD COLUMN cancelada TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE pujas ADD COLUMN cancelada_en DATETIME NULL;
ALTER TABLE ordenes_pago ADD COLUMN referencia_demo VARCHAR(60) NULL;
