CREATE DATABASE IF NOT EXISTS guate_subasta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE guate_subasta;

DROP TABLE IF EXISTS auditoria; DROP TABLE IF EXISTS pagos; DROP TABLE IF EXISTS ordenes_pago; DROP TABLE IF EXISTS pujas;
DROP TABLE IF EXISTS subastas; DROP TABLE IF EXISTS articulos; DROP TABLE IF EXISTS usuarios; DROP TABLE IF EXISTS roles;

CREATE TABLE roles(id INT AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(30) UNIQUE NOT NULL);
CREATE TABLE usuarios(
 id INT AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(100) NOT NULL,email VARCHAR(150) UNIQUE NOT NULL,
 password_hash VARCHAR(255) NOT NULL,rol_id INT NOT NULL,activo TINYINT(1) DEFAULT 1,creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(rol_id) REFERENCES roles(id)
);
CREATE TABLE articulos(
 id INT AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(150) NOT NULL,categoria VARCHAR(80) NOT NULL,descripcion TEXT,
 estado_fisico VARCHAR(50),precio_base DECIMAL(12,2) NOT NULL CHECK(precio_base>=0),certificado VARCHAR(255),imagen VARCHAR(255),
 estado ENUM('en_revision','validado','adjudicado') DEFAULT 'validado',creado_por INT NOT NULL,creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(creado_por) REFERENCES usuarios(id)
);
CREATE TABLE subastas(
 id INT AUTO_INCREMENT PRIMARY KEY,articulo_id INT NOT NULL,fecha_inicio DATETIME NOT NULL,fecha_fin DATETIME NOT NULL,
 incremento_minimo DECIMAL(12,2) NOT NULL,monto_actual DECIMAL(12,2) NOT NULL,
 estado ENUM('programada','activa','finalizada','cancelada') DEFAULT 'programada',
 FOREIGN KEY(articulo_id) REFERENCES articulos(id), CHECK(fecha_fin>fecha_inicio), CHECK(incremento_minimo>0)
);
CREATE TABLE pujas(
 id BIGINT AUTO_INCREMENT PRIMARY KEY,subasta_id INT NOT NULL,usuario_id INT NOT NULL,monto DECIMAL(12,2) NOT NULL,
 fecha TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP(6),FOREIGN KEY(subasta_id) REFERENCES subastas(id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id),
 INDEX idx_puja_subasta_monto(subasta_id,monto)
);
CREATE TABLE ordenes_pago(
 id INT AUTO_INCREMENT PRIMARY KEY,subasta_id INT UNIQUE NOT NULL,usuario_id INT NOT NULL,monto DECIMAL(12,2) NOT NULL,
 comision DECIMAL(12,2) NOT NULL,estado ENUM('pendiente','pagado') DEFAULT 'pendiente',creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(subasta_id) REFERENCES subastas(id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
);
CREATE TABLE pagos(
 id INT AUTO_INCREMENT PRIMARY KEY,orden_id INT NOT NULL,monto DECIMAL(12,2) NOT NULL,metodo VARCHAR(50),estado VARCHAR(30) DEFAULT 'pendiente',
 fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(orden_id) REFERENCES ordenes_pago(id)
);
CREATE TABLE auditoria(
 id BIGINT AUTO_INCREMENT PRIMARY KEY,usuario_id INT NULL,evento VARCHAR(80) NOT NULL,detalle TEXT,fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
);

INSERT INTO roles(nombre) VALUES ('Administrador'),('Subastador'),('Cliente'),('Auditor');
-- Contraseña de los usuarios demo: Admin123!
INSERT INTO usuarios(nombre,email,password_hash,rol_id) VALUES
('Administrador General','admin@guate.test','$2y$10$JqF2hZQ3LQq4dBqGf/5rOeXrZxw4x.Rw8u/VpP7fR7Yh1JQvR3h3S',1),
('Subastador Demo','subastador@guate.test','$2y$10$JqF2hZQ3LQq4dBqGf/5rOeXrZxw4x.Rw8u/VpP7fR7Yh1JQvR3h3S',2),
('Cliente Demo','cliente@guate.test','$2y$10$JqF2hZQ3LQq4dBqGf/5rOeXrZxw4x.Rw8u/VpP7fR7Yh1JQvR3h3S',3),
('Auditor Demo','auditor@guate.test','$2y$10$JqF2hZQ3LQq4dBqGf/5rOeXrZxw4x.Rw8u/VpP7fR7Yh1JQvR3h3S',4);

INSERT INTO articulos(nombre,categoria,descripcion,estado_fisico,precio_base,certificado,creado_por) VALUES
('Reloj de colección','Relojes','Reloj de lujo con certificado de autenticidad.','Excelente',50000,'CERT-RELOJ-001',2),
('Cuadro colonial','Arte','Pieza artística para subasta privada.','Bueno',10000,'CERT-ARTE-001',2),
('Moneda de oro 1921','Coleccionables','Moneda histórica certificada.','Excelente',7000,'CERT-MONEDA-001',2);

INSERT INTO subastas(articulo_id,fecha_inicio,fecha_fin,incremento_minimo,monto_actual,estado) VALUES
(1,NOW(),DATE_ADD(NOW(),INTERVAL 7 DAY),2000,50000,'activa'),
(2,DATE_ADD(NOW(),INTERVAL 1 DAY),DATE_ADD(NOW(),INTERVAL 8 DAY),500,10000,'programada');

DELIMITER //
CREATE PROCEDURE cerrar_subasta(IN p_subasta INT)
BEGIN
 DECLARE v_user INT; DECLARE v_monto DECIMAL(12,2);
 START TRANSACTION;
 SELECT usuario_id,monto INTO v_user,v_monto FROM pujas WHERE subasta_id=p_subasta ORDER BY monto DESC,fecha ASC LIMIT 1 FOR UPDATE;
 UPDATE subastas SET estado='finalizada' WHERE id=p_subasta AND fecha_fin<=NOW() AND estado IN('activa','programada');
 IF v_user IS NOT NULL THEN
   UPDATE articulos a JOIN subastas s ON s.articulo_id=a.id SET a.estado='adjudicado' WHERE s.id=p_subasta;
   INSERT IGNORE INTO ordenes_pago(subasta_id,usuario_id,monto,comision) VALUES(p_subasta,v_user,v_monto,v_monto*0.10);
 END IF;
 INSERT INTO auditoria(usuario_id,evento,detalle) VALUES(NULL,'CIERRE_SUBASTA',CONCAT('Subasta ',p_subasta,' procesada'));
 COMMIT;
END//
DELIMITER ;
