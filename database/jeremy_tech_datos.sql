-- Jeremy Tech: datos para PostgreSQL 14+
-- Requiere haber cargado antes database/jeremy_tech_estructura.sql.
-- Despues cargar database/seed-producto-imagenes.sql para las imagenes WebP en BD.
-- La transacción difiere las nuevas FK para cargar hijos antes de sus padres.
-- Uso: psql -d jeremy_tech -f database/jeremy_tech_datos.sql

SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
BEGIN;
SET CONSTRAINTS ALL DEFERRED;

INSERT INTO categorias VALUES
(1,'Celulares','celulares','Smartphones de última generación de Apple, Samsung, Xiaomi y más','','2026-10-27 01:38:38'),
(2,'Laptops','laptops','Laptops y notebooks para trabajo, estudio y gaming','','2026-10-27 01:38:38'),
(3,'Audifonos','audifonos','Audífonos inalámbricos y con cancelación de ruido','','2026-10-27 01:38:38'),
(4,'Accesorios','accesorios','Cargadores, fundas, monitores y periféricos','','2026-10-27 01:38:38'),
(5,'Smartwatches','smartwatches','Smartwatches y bandas de actividad física','','2026-10-27 01:38:38'),
(6,'Tablets','tablets','Tablets para creatividad y entretenimiento','','2026-10-27 01:38:38'),
(7,'Gaming','gaming','Accesorios y equipos para gamers','','2026-10-27 01:38:38');
INSERT INTO chatbot_faq VALUES
(1,'¿Cómo busco un producto?','buscar, buscador, encontrar, sugerencias, catalogo','Usa el buscador superior: acepta nombre, modelo, SKU o categoría. Mientras escribes verás sugerencias con imagen y precio; también puedes navegar por las categorías del menú.',1,10,'2026-10-28 01:03:00','2026-10-28 01:10:43'),
(2,'¿Cómo cambio la moneda?','moneda, dolar, dolares, soles, pen, usd, eur','Abre el selector de moneda en la barra superior y elige PEN, USD, EUR, GBP, BRL, ARS, CLP o COP. La conversión es referencial y el pedido se registra en soles (PEN).',1,20,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(3,'¿Cómo sigo mi pedido?','pedido, seguimiento, rastrear, historial, estado','Inicia sesión y abre Mis Pedidos. Ahí verás el estado, el pago y la línea de tiempo de tu compra. Si no tienes cuenta, puedes crear una desde registro.',1,30,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(4,'¿Qué métodos de pago aceptan?','pago, pagar, tarjeta, yape, plin, transferencia, contraentrega','Aceptamos tarjeta de crédito o débito, Yape/Plin, transferencia bancaria y pago contraentrega. En esta demostración los pagos son simulados y no se realiza ningún cobro real.',1,40,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(5,'¿Cuánto cuesta el envío?','envio, envios, tarifa, delivery, zona','Depende de la zona de entrega. Revisa la tabla completa en Envíos y devoluciones. El recojo en tienda es gratuito.',1,50,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(6,'¿Puedo recoger mi pedido en tienda?','recojo, recoger, sede, sucursal','Sí. Elige la modalidad de recojo al finalizar la compra. Nuestra dirección es La Paz Soldán, frente a Los Portales, La Joya – Arequipa.',1,60,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(7,'¿Cómo devuelvo un producto?','devolver, devolucion, devoluciones, reembolso','Escríbenos dentro de los 30 días posteriores a la recepción, con el producto sin uso y en su empaque original. Te indicaremos los pasos para la devolución o garantía.',1,70,'2026-10-28 01:03:00','2026-10-28 05:36:37'),
(8,'¿Los productos tienen garantía?','garantia, garantias, original, fabricante','Sí. Todos los productos son originales y la garantía depende del fabricante y del modelo; la ficha del producto muestra la cobertura aplicable.',1,80,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(9,'¿Mis datos y pagos están seguros?','seguridad, seguro, datos, privacidad, contrasena','Las contraseñas se almacenan con hash, los formularios usan tokens CSRF y las consultas son preparadas. No almacenamos datos de tarjeta. En esta demo los pagos no son reales.',1,90,'2026-10-28 01:03:00','2026-10-28 01:03:00'),
(10,'¿Cómo contacto a una persona?','contacto, whatsapp, correo, telefono, asesor','Puedes escribirnos por WhatsApp al +51 999 000 000, al correo soporte@example.com o usar el chatbot de la tienda.',1,100,'2026-10-28 01:03:00','2026-10-29 04:52:49');
INSERT INTO configuracion VALUES
('comision_marketplace_porcentaje','15'),
('igv_porcentaje','18'),
('moneda_base','PEN'),
('whatsapp_numero','51999000000');
INSERT INTO cupones VALUES
(1,'JEREMYTECH10','porcentaje',10.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38'),
(2,'PRIMERACOMPRA','porcentaje',15.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38'),
(3,'JEREMY50','fijo',50.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38'),
(4,'OFERTA2026','porcentaje',12.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38');
INSERT INTO garantias VALUES
(1,'Garantia del fabricante 6 meses',6,'Defectos de fabricacion y componentes',0.00,1,'2026-10-27 01:38:38'),
(2,'Garantia extendida 12 meses',12,'Cobertura ampliada de fabricacion',49.90,1,'2026-10-27 01:38:38'),
(3,'Garantia premium 24 meses',24,'Cobertura total incluyendo pantalla y bateria',149.90,1,'2026-10-27 01:38:38'),
(4,'Garantia importacion 3 meses',3,'Solo defectos de funcionamiento',0.00,1,'2026-10-27 01:38:38');
INSERT INTO marcas VALUES
(1,'Apple','apple','',1,'2026-10-27 01:38:38'),
(2,'Samsung','samsung','',1,'2026-10-27 01:38:38'),
(3,'Xiaomi','xiaomi','',1,'2026-10-27 01:38:38'),
(4,'Lenovo','lenovo','',1,'2026-10-27 01:38:38'),
(5,'HP','hp','',1,'2026-10-27 01:38:38'),
(6,'Sony','sony','',1,'2026-10-27 01:38:38'),
(7,'JBL','jbl','',1,'2026-10-27 01:38:38'),
(8,'Bose','bose','',1,'2026-10-27 01:38:38'),
(9,'Anker','anker','',1,'2026-10-27 01:38:38'),
(10,'Logitech','logitech','',1,'2026-10-27 01:38:38'),
(11,'Microsoft','microsoft','',1,'2026-10-27 01:38:38'),
(12,'PlayStation','playstation','',1,'2026-10-27 01:38:38');
INSERT INTO tarifas_envio VALUES
(1,'Recojo en tienda (Cercado, Arequipa)',0.00,'El mismo día',1,'2026-10-27 01:38:38'),
(2,'Arequipa Metropolitana',8.00,'1-2 días',1,'2026-10-27 01:38:38'),
(3,'Arequipa Provincia',15.00,'2-4 días',1,'2026-10-27 01:38:38'),
(4,'Lima',18.00,'3-5 días',1,'2026-10-27 01:38:38'),
(5,'Otras regiones del Perú',25.00,'4-7 días',1,'2026-10-27 01:38:38');
INSERT INTO usuarios VALUES
(1,'Admin','Demo','admin@example.com','jeremytech','!','admin',NULL,0,0,'999000000','Av. Demo 123','C2C','','','','','','','2026-10-29 02:35:28','2026-10-27 01:38:38','2026-10-29 04:52:56',NULL),
(2,'Vendedor','Demo','vendedor@example.com','vendedor','!','vendedor',15.00,1,0,'999000001','Av. Demo 123','B2B','','','','','¿Cuál es el nombre de tu primera mascota?','!','2026-10-29 02:09:36','2026-10-27 01:38:38','2026-10-29 04:52:56',NULL),
(3,'Cliente','','cliente@example.com',NULL,'$2y$12$nD6dIfvWyBC0DwP6UV.IFeNL8vfZE03ehO.LQoyzCOSs6sQW.l6SC','cliente',NULL,0,1,'999000002','Av. Demo 123','B2C','','','','','','',NULL,'2026-10-27 01:38:48','2026-10-29 04:52:56',NULL),
(11,'Cliente','Demo2','cliente2@example.com','cliente2','$2y$12$9/uh8C2l1U5BBN.ToahxXeNTYeMVYezUl/YOJhFauFV0KXveiB.Dm','cliente',NULL,0,1,'999000003','Av. Demo 123','B2C','Empresa Demo','20123456789','','00000001','¿En qué ciudad naciste?','$2y$12$NO4DkvjUt40RSSlr9.OLPONJltsrf09L9TTSJqVIwPjrGe6fDPr0K',NULL,'2026-10-28 02:55:40','2026-10-29 04:52:56',NULL),
(18,'Empresa','Demo','empresa@example.com','empresa','$2y$12$yfzi08jn2TafhdIYcZrtqeYOys6IGvLDpAXMx6vISKUoABH/l1yxa','vendedor',NULL,0,1,'999000004','Av. Demo 123','B2B','Empresa Demo SAC','20123456789','','00000002','¿En qué ciudad naciste?','$2y$12$cJU1qPj9CIbswjSnhY3PVe0tXNpmU2JSXF076g8L//GxyxoeS1QQO',NULL,'2026-10-29 02:38:55','2026-10-29 04:52:56',NULL);
INSERT INTO direcciones VALUES
(1,3,'Entrega','Av. Demo 123','Ref. demo',2,NULL,NULL,1,'2026-10-28 02:01:18'),
(5,11,'Principal','Av. Demo 123','Ref. demo',2,NULL,NULL,1,'2026-10-28 02:55:40'),
(8,11,'Entrega','Av. Demo 123','Ref. demo',4,NULL,NULL,0,'2026-10-28 05:54:14'),
(9,18,'Principal','Av. Demo 123','Ref. demo',2,NULL,NULL,1,'2026-10-29 02:38:55');
INSERT INTO productos VALUES
('iphone-15-pro-256',1,'Celulares','iPhone 15 Pro','Chip A17 Pro | 256GB | Camara 48MP',5499.00,10,1,NULL,1,NULL,NULL,'["#3B4C5A","#F5F5DC","#1A1C72"]','?ruta=producto/imagen&slug=iphone-15-pro-256','JT-00001',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:51:24'),
('iphone-15-pro-512',2,'Celulares','iPhone 15 Pro','Chip A17 Pro | 512GB | Camara 48MP',10999.00,8,1,NULL,1,NULL,NULL,'["#3B4C5A","#F5F5DC"]','?ruta=producto/imagen&slug=iphone-15-pro-512','JT-00002',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-galaxy-s24-ultra-256',3,'Celulares','Samsung Galaxy S24 Ultra','Snapdragon 8 Gen 3 | 256GB | Camara 200MP',7499.00,9,1,NULL,2,NULL,NULL,'["#2C2C2C","#E8D5B7","#4A6741"]','?ruta=producto/imagen&slug=samsung-galaxy-s24-ultra-256','JT-00003',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:51:24'),
('samsung-galaxy-s24-ultra-512',4,'Celulares','Samsung Galaxy S24 Ultra','Snapdragon 8 Gen 3 | 512GB | Camara 200MP',8999.00,9,1,NULL,2,NULL,NULL,'["#2C2C2C","#E8D5B7"]','?ruta=producto/imagen&slug=samsung-galaxy-s24-ultra-512','JT-00004',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-14-256',5,'Celulares','Xiaomi 14','Snapdragon 8 Gen 3 | 256GB | Camara Leica',4299.00,18,1,NULL,3,NULL,NULL,'["#1A1A2E","#F0E68C","#4A90A4"]','?ruta=producto/imagen&slug=xiaomi-14-256','JT-00005',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-14-512',6,'Celulares','Xiaomi 14','Snapdragon 8 Gen 3 | 512GB | Camara Leica',5199.00,10,1,NULL,3,NULL,NULL,'["#1A1A2E","#F0E68C","#4A90A4"]','?ruta=producto/imagen&slug=xiaomi-14-512','JT-00006',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('macbook-air-m3-256',7,'Laptops','MacBook Air M3','Chip M3 | 8GB RAM | 256GB SSD | Pantalla 13.6"',9499.00,4,1,NULL,1,NULL,NULL,'["#C0C0C0","#2C2C2E","#E8D5C4"]','?ruta=producto/imagen&slug=macbook-air-m3-256','JT-00007',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 03:09:44'),
('macbook-air-m3-512',8,'Laptops','MacBook Air M3','Chip M3 | 16GB RAM | 512GB SSD | Pantalla 13.6"',11999.00,5,1,NULL,1,NULL,NULL,'["#C0C0C0","#2C2C2E"]','?ruta=producto/imagen&slug=macbook-air-m3-512','JT-00008',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('lenovo-thinkpad-x1-carbon',9,'Laptops','Lenovo ThinkPad X1 Carbon','Intel i7 | 16GB RAM | 512GB SSD | Pantalla 14"',8799.00,5,1,NULL,4,NULL,NULL,'["#1A1A1A"]','?ruta=producto/imagen&slug=lenovo-thinkpad-x1-carbon','JT-00009',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 02:01:18'),
('hp-pavilion-15-ryzen5',10,'Laptops','HP Pavilion 15','AMD Ryzen 5 | 8GB RAM | 512GB SSD | Pantalla 15.6"',4999.00,10,1,NULL,5,NULL,NULL,'["#B8C4CC","#2C2C2E"]','?ruta=producto/imagen&slug=hp-pavilion-15-ryzen5','JT-00010',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 02:01:18'),
('hp-pavilion-15-ryzen7',11,'Laptops','HP Pavilion 15','AMD Ryzen 7 | 16GB RAM | 1TB SSD | Pantalla 15.6"',6499.00,8,1,NULL,5,NULL,NULL,'["#B8C4CC","#2C2C2E"]','?ruta=producto/imagen&slug=hp-pavilion-15-ryzen7','JT-00011',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('airpods-pro-2-usbc',12,'Audifonos','Apple AirPods Pro 2','Cancelacion de ruido activa | USB-C | H2 Chip',1899.00,15,1,NULL,1,NULL,NULL,'["#FFFFFF"]','?ruta=producto/imagen&slug=airpods-pro-2-usbc','JT-00012',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:54:14'),
('sony-wh-1000xm5',13,'Audifonos','Sony WH-1000XM5','Cancelacion de ruido | 30h bateria | LDAC',2499.00,13,0,NULL,6,NULL,2,'["#1A1A1A","#C0C0C0","#E8D5C4"]','?ruta=producto/imagen&slug=sony-wh-1000xm5','JT-00013',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-29 04:07:31'),
('jbl-tune-510bt',14,'Audifonos','JBL Tune 510BT','Bluetooth 5.0 | 40h bateria | PureBass',349.00,25,1,NULL,7,NULL,NULL,'["#1A1A2E","#FFFFFF","#4169E1"]','?ruta=producto/imagen&slug=jbl-tune-510bt','JT-00014',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('jbl-charge-5',15,'Audifonos','JBL Charge 5','Bluetooth 5.1 | 20h bateria | IP67 | Powerbank',699.00,16,0,NULL,7,NULL,2,'["#1A1A2E","#2E8B57","#B22222"]','?ruta=producto/imagen&slug=jbl-charge-5','JT-00015',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('bose-qc-ultra',16,'Audifonos','Bose QuietComfort Ultra','Cancelacion de ruido | Immersive Audio | 24h bateria',3299.00,7,0,NULL,8,NULL,2,'["#1A1A2E","#C0C0C0","#E8D5C4"]','?ruta=producto/imagen&slug=bose-qc-ultra','JT-00016',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:51:24'),
('cargador-magsafe',17,'Accesorios','Cargador MagSafe','Carga inalambrica 15W | Compatible iPhone',299.00,30,1,NULL,NULL,NULL,NULL,'["#FFFFFF"]','?ruta=producto/imagen&slug=cargador-magsafe','JT-00017',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('funda-iphone-15-pro',18,'Accesorios','Funda iPhone 15 Pro','Silicona | MagSafe | Proteccion completa',149.00,40,1,NULL,NULL,NULL,NULL,'["#1A1A2E","#4169E1","#2E8B57"]','?ruta=producto/imagen&slug=funda-iphone-15-pro','JT-00018',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('logitech-mx-master-3s',19,'Accesorios','Logitech MX Master 3S','Inalambrico | 8K DPI | USB-C | Silence Click',649.00,22,1,NULL,10,NULL,NULL,'["#1A1A2E","#C0C0C0"]','?ruta=producto/imagen&slug=logitech-mx-master-3s','JT-00019',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('anker-powercore-20000',20,'Accesorios','Anker PowerCore 20000','Powerbank 20000mAh | Carga rapida 20W',249.00,28,1,NULL,9,NULL,NULL,'["#1A1A2E","#FFFFFF"]','?ruta=producto/imagen&slug=anker-powercore-20000','JT-00020',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-monitor-27',21,'Accesorios','Samsung Monitor 27" 4K','4K UHD | IPS | USB-C | 60Hz',1899.00,10,1,NULL,2,NULL,NULL,'["#1A1A2E","#C0C0C0"]','?ruta=producto/imagen&slug=samsung-monitor-27','JT-00021',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('apple-watch-series-9-45',22,'Smartwatches','Apple Watch Series 9','S9 SiP | GPS | Pantalla Always-On | 45mm',3499.00,13,1,NULL,1,NULL,NULL,'["#C0C0C0","#1A1A2E","#FFB6C1"]','?ruta=producto/imagen&slug=apple-watch-series-9-45','JT-00022',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('apple-watch-series-9-41',23,'Smartwatches','Apple Watch Series 9','S9 SiP | GPS | Pantalla Always-On | 41mm',3199.00,11,1,NULL,1,NULL,NULL,'["#C0C0C0","#FFB6C1"]','?ruta=producto/imagen&slug=apple-watch-series-9-41','JT-00023',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-galaxy-watch-6-44',24,'Smartwatches','Samsung Galaxy Watch 6','Exynos W930 | GPS | WearOS | 44mm',2199.00,14,1,NULL,2,NULL,NULL,'["#1A1A2E","#C0C0C0"]','?ruta=producto/imagen&slug=samsung-galaxy-watch-6-44','JT-00024',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-smart-band-8',25,'Smartwatches','Xiaomi Smart Band 8','Pantalla AMOLED | 16 dias bateria | GPS',249.00,35,1,NULL,3,NULL,NULL,'["#1A1A2E","#C0C0C0","#FFB6C1"]','?ruta=producto/imagen&slug=xiaomi-smart-band-8','JT-00025',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-smart-band-8-nfc',26,'Smartwatches','Xiaomi Smart Band 8','Pantalla AMOLED | 16 dias bateria | NFC',299.00,30,1,NULL,3,NULL,NULL,'["#1A1A2E","#C0C0C0"]','?ruta=producto/imagen&slug=xiaomi-smart-band-8-nfc','JT-00026',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('ipad-air-m2',27,'Tablets','iPad Air M2','Chip M2 | 64GB | Pantalla 11" Liquid Retina',2999.00,10,1,NULL,1,NULL,NULL,'["#C0C0C0","#1A1A2E","#E8D5C4"]','?ruta=producto/imagen&slug=ipad-air-m2','JT-00027',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-galaxy-tab-s9',28,'Tablets','Samsung Galaxy Tab S9','Snapdragon 8 Gen 2 | 128GB | AMOLED 11"',3499.00,9,1,NULL,2,NULL,NULL,'["#1A1A2E","#C0C0C0"]','?ruta=producto/imagen&slug=samsung-galaxy-tab-s9','JT-00028',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('dualsense-edge',29,'Gaming','PlayStation DualSense Edge','Control pro | Back buttons | Stick removibles',1299.00,6,1,NULL,12,NULL,NULL,'["#FFFFFF","#1A1A2E"]','?ruta=producto/imagen&slug=dualsense-edge','JT-00029',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xbox-controller',30,'Gaming','Xbox Wireless Controller','Bluetooth | USB-C | Compatible PC/Xbox',349.00,12,1,NULL,11,NULL,NULL,'["#1A1A2E","#FFFFFF","#2E8B57"]','?ruta=producto/imagen&slug=xbox-controller','JT-00030',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('agua',35,'Accesorios','agua','wcqw',12.00,10,0,'2026-10-28 04:04:10',6,1,11,'[]','img/producto-sin-imagen.svg','JT-00035','dd',2.000,'10x5',5,'2026-10-28 03:15:12','2026-10-28 04:04:10'),
('boton',42,'Tablets','boton','jyhgtrfedwsqawertyhu7
trbeb5ebt',500.00,40,0,NULL,3,3,11,'[]','img/producto-sin-imagen.svg','JT-00042','tgh',6.000,'10x5',5,'2026-10-29 02:18:51','2026-10-29 02:19:48');
INSERT INTO precios_volumen VALUES
(1,15,2,4,679.00,'2026-10-27 01:38:38'),
(2,15,5,NULL,649.00,'2026-10-27 01:38:38'),
(4,13,2,3,2449.00,'2026-10-27 01:38:38'),
(5,13,4,NULL,2379.00,'2026-10-27 01:38:38'),
(7,18,2,4,139.00,'2026-10-27 01:38:38'),
(8,18,5,NULL,129.00,'2026-10-27 01:38:38'),
(10,30,2,4,335.00,'2026-10-27 01:38:38'),
(11,30,5,NULL,320.00,'2026-10-27 01:38:38'),
(13,20,2,4,239.00,'2026-10-27 01:38:38'),
(14,20,5,NULL,229.00,'2026-10-27 01:38:38');
INSERT INTO producto_variantes VALUES
(1,'Titanio Natural',0.00,6,'#B0B0A0',1,'2026-10-27 01:38:38',1),
(2,'Titanio Azul',0.00,4,'#4A5A6B',1,'2026-10-27 01:38:38',1),
(3,'Titanio Negro',0.00,5,'#2B2B2B',1,'2026-10-27 01:38:38',1),
(4,'Negro',0.00,8,'#111111',1,'2026-10-27 01:38:38',15),
(5,'Azul Medianoche',0.00,5,'#1F3A5F',1,'2026-10-27 01:38:38',15),
(6,'Rojo',0.00,3,'#C0392B',1,'2026-10-27 01:38:38',15),
(7,'Verde Bosque',0.00,2,'#1E6F4B',1,'2026-10-27 01:38:38',15),
(11,'16 GB · 512 GB',2500.00,1,'',1,'2026-10-27 01:38:38',7),
(12,'24 GB · 1 TB',4500.00,3,'',1,'2026-10-27 01:38:38',7),
(16,'plkjhnji',10.00,50,'',1,'2026-10-29 02:20:25',42);

SELECT setval(pg_get_serial_sequence('categorias', 'id'), COALESCE((SELECT MAX(id) FROM categorias), 1), (SELECT COUNT(*) > 0 FROM categorias));
SELECT setval(pg_get_serial_sequence('chatbot_faq', 'id'), COALESCE((SELECT MAX(id) FROM chatbot_faq), 1), (SELECT COUNT(*) > 0 FROM chatbot_faq));
SELECT setval(pg_get_serial_sequence('cupones', 'id'), COALESCE((SELECT MAX(id) FROM cupones), 1), (SELECT COUNT(*) > 0 FROM cupones));
SELECT setval(pg_get_serial_sequence('direcciones', 'id'), COALESCE((SELECT MAX(id) FROM direcciones), 1), (SELECT COUNT(*) > 0 FROM direcciones));
SELECT setval(pg_get_serial_sequence('garantias', 'id'), COALESCE((SELECT MAX(id) FROM garantias), 1), (SELECT COUNT(*) > 0 FROM garantias));
SELECT setval(pg_get_serial_sequence('marcas', 'id'), COALESCE((SELECT MAX(id) FROM marcas), 1), (SELECT COUNT(*) > 0 FROM marcas));
SELECT setval(pg_get_serial_sequence('precios_volumen', 'id'), COALESCE((SELECT MAX(id) FROM precios_volumen), 1), (SELECT COUNT(*) > 0 FROM precios_volumen));
SELECT setval(pg_get_serial_sequence('producto_variantes', 'id'), COALESCE((SELECT MAX(id) FROM producto_variantes), 1), (SELECT COUNT(*) > 0 FROM producto_variantes));
SELECT setval(pg_get_serial_sequence('productos', 'id'), COALESCE((SELECT MAX(id) FROM productos), 1), (SELECT COUNT(*) > 0 FROM productos));
SELECT setval(pg_get_serial_sequence('tarifas_envio', 'id'), COALESCE((SELECT MAX(id) FROM tarifas_envio), 1), (SELECT COUNT(*) > 0 FROM tarifas_envio));
SELECT setval(pg_get_serial_sequence('usuarios', 'id'), COALESCE((SELECT MAX(id) FROM usuarios), 1), (SELECT COUNT(*) > 0 FROM usuarios));
COMMIT;
