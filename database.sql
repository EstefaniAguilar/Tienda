CREATE DATABASE IF NOT EXISTS mt_tienda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mt_tienda;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 profile ENUM('Cliente','Vendedor','Administrador') NOT NULL DEFAULT 'Cliente',
 phone VARCHAR(30) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX(profile)
) ENGINE=InnoDB;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 category VARCHAR(100) NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 old_price DECIMAL(12,2) NULL,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 min_stock INT UNSIGNED NOT NULL DEFAULT 0,
 image TEXT NULL,
 description TEXT NULL,
 rating DECIMAL(2,1) NOT NULL DEFAULT 0,
 reviews INT UNSIGNED NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX(active), INDEX(category), INDEX(name)
) ENGINE=InnoDB;

CREATE TABLE coupons (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(50) NOT NULL UNIQUE,
 discount DECIMAL(5,2) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 expires_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 folio VARCHAR(35) NOT NULL UNIQUE,
 user_id BIGINT UNSIGNED NULL,
 customer VARCHAR(150) NOT NULL,
 email VARCHAR(190) NOT NULL,
 address VARCHAR(500) NOT NULL,
 order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 subtotal DECIMAL(12,2) NOT NULL,
 discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 coupon VARCHAR(50) NULL,
 total DECIMAL(12,2) NOT NULL,
 payment ENUM('Pago de prueba','Contra entrega') NOT NULL,
 status ENUM('Nuevo','Preparado','Entregado','Cancelado') NOT NULL DEFAULT 'Nuevo',
 CONSTRAINT fk_orders_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
 INDEX(user_id), INDEX(status), INDEX(order_date)
) ENGINE=InnoDB;

CREATE TABLE order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NULL,
 name VARCHAR(180) NOT NULL,
 qty INT UNSIGNED NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_items_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_items_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE,
 INDEX(product_id)
) ENGINE=InnoDB;

CREATE TABLE reviews (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 author VARCHAR(150) NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 comment TEXT NOT NULL,
 review_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_reviews_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_reviews_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
 INDEX(product_id)
) ENGINE=InnoDB;

CREATE TABLE reservations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 folio VARCHAR(35) NOT NULL UNIQUE,
 product_id BIGINT UNSIGNED NULL,
 customer_name VARCHAR(150) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 days VARCHAR(20) NOT NULL,
 status ENUM('Pendiente','Confirmada','Cancelada','Vencida') NOT NULL DEFAULT 'Pendiente',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_res_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO products
(id,name,category,price,old_price,stock,min_stock,image,description,rating,reviews,active,featured) VALUES
(1,'Laptop Lenovo IdeaPad 3','Equipos de cómputo',12499,13999,8,3,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=80','Laptop para actividades académicas, trabajo y productividad.',4.7,28,1,1),
(2,'PC de escritorio empresarial','Equipos de cómputo',15999,NULL,4,3,'https://images.unsplash.com/photo-1587831990711-23ca6441447b?auto=format&fit=crop&w=900&q=80','Equipo de escritorio para oficinas y empresas.',4.8,16,1,1),
(3,'Monitor LED 24 pulgadas','Periféricos',2899,NULL,2,4,'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=900&q=80','Monitor Full HD ideal para estudio y oficina.',4.5,12,1,0),
(4,'Teclado y mouse inalámbricos','Periféricos',699,NULL,18,5,'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=80','Combo inalámbrico cómodo para uso diario.',4.4,31,1,0),
(5,'Escritorio para oficina','Mobiliario',3299,NULL,6,2,'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80','Escritorio funcional para espacios de trabajo.',4.6,9,1,0),
(6,'Silla ergonómica ejecutiva','Mobiliario',4199,NULL,3,3,'https://images.unsplash.com/photo-1505843490701-5be5d?auto=format&fit=crop&w=900&q=80','Silla ajustable para jornadas de trabajo.',4.3,7,1,0),
(7,'Sistema punto de venta','Soluciones empresariales',5499,NULL,12,3,'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=900&q=80','Solución de punto de venta para comercios.',4.9,11,1,1),
(8,'Sistema de cobro municipal','Software',8900,NULL,0,1,'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=80','Solución de software para cobros de agua, predial y servicios municipales. Producto demostrativo.',4.8,5,1,0),
(9,'Licencia de software administrativo','Software',2199,NULL,15,4,'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=900&q=80','Herramientas digitales para administración de negocios.',4.6,19,1,0),
(10,'Tarjeta gráfica RTX de demostración','Componentes PC',9999,NULL,5,2,'https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=900&q=80','Componente gráfico para estaciones de trabajo y gaming.',4.9,22,1,0),
(11,'Memoria RAM 16 GB DDR4','Componentes PC',899,NULL,20,5,'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=900&q=80','Memoria para ampliar el rendimiento de tu computadora.',4.5,14,1,0),
(12,'Procesador AMD Ryzen 5 5600G','Componentes PC',2499,NULL,10,2,'https://images.unsplash.com/photo-1555617981-dac3880eac6e?auto=format&fit=crop&w=900&q=80','Procesador para equipos de uso general y gaming de entrada.',4.7,0,1,0),
(13,'SSD NVMe 1 TB','Componentes PC',1399,NULL,12,2,'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=900&q=80','Unidad de almacenamiento SSD NVMe de 1 TB.',4.6,0,1,0);

INSERT INTO coupons(code,discount,active) VALUES ('MT10',10,1),('ESTUDIANTE',5,1),('BIENVENIDA',15,1);
INSERT INTO reviews(product_id,author,rating,comment,review_date) VALUES
(1,'Andrea M.',5,'Muy buena para mis clases y proyectos.','2026-11-20 12:00:00'),
(1,'Luis R.',4,'Buen rendimiento y entrega rápida.','2026-11-18 12:00:00'),
(7,'Comercial del Centro',5,'El sistema es práctico para nuestro negocio.','2026-11-15 12:00:00');

-- Pedidos de demostración incluidos en el prototipo original.
INSERT INTO orders(folio,customer,email,address,order_date,subtotal,discount,coupon,total,payment,status) VALUES
('MT-1001','María Fernanda López','maria@demo.com','Dirección de demostración','2026-11-23 12:00:00',13198,0,NULL,13198,'Contra entrega','Nuevo'),
('MT-1002','Carlos Hernández','carlos@demo.com','Dirección de demostración','2026-11-24 12:00:00',2899,0,NULL,2899,'Pago de prueba','Preparado');
INSERT INTO order_items(order_id,product_id,name,qty,price)
SELECT o.id,p.id,p.name,1,p.price FROM orders o JOIN products p
 ON (o.folio='MT-1001' AND p.id IN (1,4)) OR (o.folio='MT-1002' AND p.id=3);
