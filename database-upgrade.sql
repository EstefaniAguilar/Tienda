-- M&T Tienda Virtual: migración incremental (NO elimina tablas ni registros).
-- Ejecutar sobre la base mt_tienda existente desde phpMyAdmin.
USE mt_tienda;

ALTER TABLE products ADD COLUMN IF NOT EXISTS owner_id BIGINT UNSIGNED NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS seller_id BIGINT UNSIGNED NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS courier_id BIGINT UNSIGNED NULL;
ALTER TABLE users MODIFY profile ENUM('Cliente','Vendedor','Administrador','Repartidor') NOT NULL DEFAULT 'Cliente';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS public_token CHAR(48) NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_code_hash VARCHAR(255) NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_confirmed_at DATETIME NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_confirmed_by BIGINT UNSIGNED NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_locked_until DATETIME NULL;
ALTER TABLE orders MODIFY status ENUM('Nuevo','Preparado','Pago confirmado','En preparación','Empacado','Enviado','En camino','Llegando a tu zona','Cerca de tu calle','Entregado','Cancelado') NOT NULL DEFAULT 'Nuevo';

CREATE TABLE IF NOT EXISTS product_images (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 image_path VARCHAR(500) NOT NULL,
 uploaded_by BIGINT UNSIGNED NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(product_id),
 CONSTRAINT fk_product_images_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_product_images_user FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_attributes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 attribute_name VARCHAR(80) NOT NULL,
 attribute_value VARCHAR(255) NOT NULL,
 CONSTRAINT fk_product_attributes_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 INDEX(product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_tracking (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(50) NOT NULL,
 location_label VARCHAR(255) NULL,
 notes VARCHAR(1000) NULL,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_tracking_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_tracking_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(order_id,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS delivery_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 courier_id BIGINT UNSIGNED NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy_m DECIMAL(8,2) NULL,
 recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_delivery_location_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_delivery_location_user FOREIGN KEY(courier_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(order_id,recorded_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS returns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 kind ENUM('Devolución','Garantía','Cambio') NOT NULL DEFAULT 'Devolución',
 reason TEXT NOT NULL,
 status ENUM('Pendiente','En revisión','Aprobada','Rechazada','Resuelta') NOT NULL DEFAULT 'Pendiente',
 seller_response TEXT NULL,
 reviewed_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_returns_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_returns_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT,
 CONSTRAINT fk_returns_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_returns_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(status), INDEX(user_id)
) ENGINE=InnoDB;

ALTER TABLE returns ADD COLUMN IF NOT EXISTS low_value TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS warranties (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 return_id BIGINT UNSIGNED NOT NULL,
 warranty_type VARCHAR(80) NOT NULL,
 start_date DATE NULL,
 end_date DATE NULL,
 resolution TEXT NULL,
 status VARCHAR(40) NOT NULL DEFAULT 'Abierta',
 CONSTRAINT fk_warranties_return FOREIGN KEY(return_id) REFERENCES returns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS return_evidence (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 return_id BIGINT UNSIGNED NOT NULL,
 file_path VARCHAR(500) NOT NULL,
 uploaded_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_return_evidence_return FOREIGN KEY(return_id) REFERENCES returns(id) ON DELETE CASCADE,
 CONSTRAINT fk_return_evidence_user FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL,
 body VARCHAR(1000) NOT NULL,
 read_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_notifications_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX(user_id,read_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 details JSON NULL,
 ip_address VARCHAR(45) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(action), INDEX(created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS review_reports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 review_id BIGINT UNSIGNED NOT NULL,
 reported_by BIGINT UNSIGNED NOT NULL,
 reason VARCHAR(500) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_review_reports_review FOREIGN KEY(review_id) REFERENCES reviews(id) ON DELETE CASCADE,
 CONSTRAINT fk_review_reports_user FOREIGN KEY(reported_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_price_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 old_price DECIMAL(12,2) NULL,
 new_price DECIMAL(12,2) NOT NULL,
 changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_price_history_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 CONSTRAINT fk_price_history_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(product_id,changed_at)
) ENGINE=InnoDB;

-- Asociar pedidos previos a su comprador no se altera. Las filas históricas quedan seller_id=NULL.

CREATE TABLE IF NOT EXISTS login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 ip_address VARCHAR(45) NOT NULL,
 attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(email,ip_address,attempted_at)
) ENGINE=InnoDB;


-- Componentes base para el configurador Arma tu PC (no duplica productos existentes).
INSERT INTO products(name,category,price,stock,min_stock,image,description,active,featured)
SELECT 'Procesador AMD Ryzen 5 5600G','Componentes PC',2499,10,2,
'https://images.unsplash.com/photo-1555617981-dac3880eac6e?auto=format&fit=crop&w=900&q=80',
'Procesador para equipos de uso general y gaming de entrada.',1,0
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='Procesador AMD Ryzen 5 5600G');

INSERT INTO products(name,category,price,stock,min_stock,image,description,active,featured)
SELECT 'SSD NVMe 1 TB','Componentes PC',1399,12,2,
'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=900&q=80',
'Unidad de almacenamiento SSD NVMe de 1 TB.',1,0
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name='SSD NVMe 1 TB');
