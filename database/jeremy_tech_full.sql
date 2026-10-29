/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-13.0.2-MariaDB, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: jeremy_tech
-- ------------------------------------------------------
-- Server version	13.0.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `jeremy_tech`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `jeremy_tech` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `jeremy_tech`;

--
-- Table structure for table `auditoria_admin`
--

DROP TABLE IF EXISTS `auditoria_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_usuario` varchar(60) NOT NULL,
  `accion` varchar(60) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `detalle` varchar(255) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditoria_admin`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auditoria_admin` WRITE;
/*!40000 ALTER TABLE `auditoria_admin` DISABLE KEYS */;
INSERT INTO `auditoria_admin` VALUES
(18,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-27 23:21:00'),
(19,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-28 01:01:40'),
(20,'vendedor','admin/login',2,'Ingreso al panel de administración','2026-10-28 01:03:42'),
(21,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-28 01:08:42'),
(22,'jeremytech','chatbot/faq/editar',1,'Actualizó la FAQ #1','2026-10-28 01:10:33'),
(23,'jeremytech','chatbot/faq/editar',1,'Actualizó la FAQ #1','2026-10-28 01:10:43'),
(24,'jeremytech','chatbot/faq/editar',1,'Actualizó la FAQ #1','2026-10-28 01:10:53'),
(25,'jeremytech','admin/cerrar',1,'Cierre de sesión del panel','2026-10-28 01:24:49'),
(36,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-28 02:59:32'),
(40,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-28 03:16:10'),
(41,'jeremytech','pedidos/reembolsar',9,'Reembolsó S/ 1,907.00 del pedido #9 y devolvió stock · cecea','2026-10-28 05:00:39'),
(42,'jeremytech','soporte/responder',2,'Respondió el ticket #ST-00002','2026-10-28 05:02:29'),
(43,'jeremytech','soporte/estado',2,'Cambió el ticket #ST-00002 a Cerrado','2026-10-28 05:03:10'),
(44,'jeremytech','soporte/estado',2,'Cambió el ticket #ST-00002 a Cerrado','2026-10-28 05:03:11'),
(45,'jeremytech','soporte/estado',2,'Cambió el ticket #ST-00002 a Resuelto','2026-10-28 05:03:14'),
(46,'jeremytech','chatbot/faq/editar',7,'Actualizó la FAQ #7','2026-10-28 05:35:00'),
(47,'jeremytech','chatbot/faq/editar',7,'Actualizó la FAQ #7','2026-10-28 05:36:37'),
(48,'vendedor','admin/login',2,'Ingreso al panel de administración','2026-10-29 02:09:36'),
(49,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-29 02:09:36'),
(50,'jeremytech','admin/login',1,'Ingreso al panel de administración','2026-10-29 02:35:28'),
(51,'jeremytech','usuarios/editar',18,'Editó al usuario \"ab\"','2026-10-29 02:53:06'),
(52,'jeremytech','usuarios/editar',18,'Editó al usuario \"ab\"','2026-10-29 02:54:00'),
(53,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 02:54:23'),
(54,'jeremytech','usuarios/editar',18,'Editó al usuario \"ab\"','2026-10-29 02:54:31'),
(55,'jeremytech','usuarios/editar',2,'Editó al usuario \"Sound\"','2026-10-29 02:55:26'),
(56,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 02:56:09'),
(57,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 03:07:29'),
(58,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 03:54:54'),
(59,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 03:55:08'),
(60,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 03:55:20'),
(61,'jeremytech','usuarios/editar',2,'Editó al usuario \"Sound\"','2026-10-29 03:57:23'),
(62,'jeremytech','usuarios/editar',11,'Editó al usuario \"Cliente Demo\"','2026-10-29 04:08:44'),
(63,'jeremytech','usuarios/editar',18,'Editó al usuario \"ab\"','2026-10-29 04:16:10'),
(64,'jeremytech','solicitudes/aprobar',5,'Aprobó la solicitud de vendedor #5','2026-10-29 04:52:19');
/*!40000 ALTER TABLE `auditoria_admin` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `calificaciones_vendedor`
--

DROP TABLE IF EXISTS `calificaciones_vendedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificaciones_vendedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendedor_id` bigint(20) unsigned NOT NULL,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `calificacion` tinyint(4) NOT NULL,
  `comentario` varchar(500) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `calificacion_vendedor_unica` (`vendedor_id`,`cliente_id`),
  KEY `fk_cal_cliente` (`cliente_id`),
  CONSTRAINT `fk_cal_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_cal_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones_vendedor`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `calificaciones_vendedor` WRITE;
/*!40000 ALTER TABLE `calificaciones_vendedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `calificaciones_vendedor` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `carrito_items`
--

DROP TABLE IF EXISTS `carrito_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrito_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrito_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `producto_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carrito_item_unico` (`carrito_id`,`producto_id`),
  KEY `fk_caritem_producto` (`producto_id`),
  CONSTRAINT `fk_caritem_carrito` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`),
  CONSTRAINT `fk_caritem_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carrito_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `carrito_items` WRITE;
/*!40000 ALTER TABLE `carrito_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `carrito_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `carritos`
--

DROP TABLE IF EXISTS `carritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `fk_car_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carritos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `carritos` WRITE;
/*!40000 ALTER TABLE `carritos` DISABLE KEYS */;
/*!40000 ALTER TABLE `carritos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL DEFAULT '',
  `descripcion` varchar(255) NOT NULL DEFAULT '',
  `imagen_url` varchar(500) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES
(1,'Celulares','celulares','Smartphones de última generación de Apple, Samsung, Xiaomi y más','','2026-10-27 01:38:38'),
(2,'Laptops','laptops','Laptops y notebooks para trabajo, estudio y gaming','','2026-10-27 01:38:38'),
(3,'Audifonos','audifonos','Audífonos inalámbricos y con cancelación de ruido','','2026-10-27 01:38:38'),
(4,'Accesorios','accesorios','Cargadores, fundas, monitores y periféricos','','2026-10-27 01:38:38'),
(5,'Smartwatches','smartwatches','Smartwatches y bandas de actividad física','','2026-10-27 01:38:38'),
(6,'Tablets','tablets','Tablets para creatividad y entretenimiento','','2026-10-27 01:38:38'),
(7,'Gaming','gaming','Accesorios y equipos para gamers','','2026-10-27 01:38:38');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chatbot_faq`
--

DROP TABLE IF EXISTS `chatbot_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatbot_faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pregunta` varchar(255) NOT NULL,
  `palabras_clave` varchar(255) NOT NULL DEFAULT '',
  `respuesta` text NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_faq_activo_orden` (`activo`,`orden`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chatbot_faq`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chatbot_faq` WRITE;
/*!40000 ALTER TABLE `chatbot_faq` DISABLE KEYS */;
INSERT INTO `chatbot_faq` VALUES
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
/*!40000 ALTER TABLE `chatbot_faq` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `comisiones`
--

DROP TABLE IF EXISTS `comisiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comisiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `vendedor_id` bigint(20) unsigned NOT NULL,
  `monto_venta` decimal(10,2) NOT NULL,
  `porcentaje_comision` decimal(5,2) NOT NULL,
  `monto_comision` decimal(10,2) NOT NULL,
  `monto_vendedor` decimal(10,2) NOT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `comision_unica` (`pedido_id`,`vendedor_id`),
  KEY `fk_com_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_com_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `fk_com_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comisiones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `comisiones` WRITE;
/*!40000 ALTER TABLE `comisiones` DISABLE KEYS */;
INSERT INTO `comisiones` VALUES
(5,10,2,6598.00,15.00,838.73,4752.80,'2026-10-28 05:51:24'),
(6,12,2,2499.00,15.00,317.67,1800.13,'2026-10-29 04:07:31');
/*!40000 ALTER TABLE `comisiones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion` (
  `clave` varchar(60) NOT NULL,
  `valor` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES
('comision_marketplace_porcentaje','15'),
('igv_porcentaje','18'),
('moneda_base','PEN'),
('whatsapp_numero','51999000000');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cupones`
--

DROP TABLE IF EXISTS `cupones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cupones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `tipo` enum('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `usos_maximos` int(11) DEFAULT NULL,
  `usos_actuales` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cupones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cupones` WRITE;
/*!40000 ALTER TABLE `cupones` DISABLE KEYS */;
INSERT INTO `cupones` VALUES
(1,'JEREMYTECH10','porcentaje',10.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38'),
(2,'PRIMERACOMPRA','porcentaje',15.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38'),
(3,'JEREMY50','fijo',50.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38'),
(4,'OFERTA2026','porcentaje',12.00,NULL,NULL,NULL,0,1,'2026-10-27 01:38:38');
/*!40000 ALTER TABLE `cupones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario_pen` decimal(10,2) NOT NULL DEFAULT 0.00,
  `variante_id` int(11) DEFAULT NULL,
  `variante_nombre` varchar(120) NOT NULL DEFAULT '',
  `producto_id` bigint(20) unsigned NOT NULL,
  `producto_nombre` varchar(190) NOT NULL DEFAULT '',
  `vendedor_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalle_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_detalle_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
INSERT INTO `detalle_pedido` VALUES
(1,1,1,7499.00,NULL,'',3,'Samsung Galaxy S24 Ultra',NULL),
(2,2,1,4299.00,NULL,'',5,'Xiaomi 14',NULL),
(3,3,1,100.00,NULL,'',6,'Xiaomi 14 Pro',NULL),
(4,4,1,4999.00,NULL,'',10,'HP Pavilion 15',NULL),
(5,4,1,8799.00,NULL,'',9,'Lenovo ThinkPad X1 Carbon',NULL),
(9,8,1,5499.00,NULL,'',1,'iPhone 15 Pro',NULL),
(10,8,3,7499.00,NULL,'',3,'Samsung Galaxy S24 Ultra',NULL),
(11,8,3,11999.00,11,'16 GB · 512 GB',7,'MacBook Air M3',NULL),
(12,9,1,1899.00,NULL,'',12,'Apple AirPods Pro 2',NULL),
(13,10,2,3299.00,NULL,'',16,'Bose QuietComfort Ultra',2),
(14,10,1,5499.00,NULL,'',1,'iPhone 15 Pro',NULL),
(15,10,3,7499.00,NULL,'',3,'Samsung Galaxy S24 Ultra',NULL),
(16,11,5,1899.00,NULL,'',12,'Apple AirPods Pro 2',NULL),
(17,12,1,2499.00,NULL,'',13,'Sony WH-1000XM5',2);
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `direcciones`
--

DROP TABLE IF EXISTS `direcciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `direcciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `etiqueta` varchar(60) NOT NULL DEFAULT 'Principal',
  `direccion` varchar(255) NOT NULL,
  `referencia` varchar(180) DEFAULT NULL,
  `tarifa_envio_id` int(11) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `predeterminada` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_direcciones_cliente` (`cliente_id`),
  KEY `fk_direcciones_tarifa` (`tarifa_envio_id`),
  CONSTRAINT `fk_direcciones_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_direcciones_tarifa` FOREIGN KEY (`tarifa_envio_id`) REFERENCES `tarifas_envio` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `direcciones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `direcciones` WRITE;
/*!40000 ALTER TABLE `direcciones` DISABLE KEYS */;
INSERT INTO `direcciones` VALUES
(1,3,'Entrega','Av. Demo 123','Ref. demo',2,NULL,NULL,1,'2026-10-28 02:01:18'),
(5,11,'Principal','Av. Demo 123','Ref. demo',2,NULL,NULL,1,'2026-10-28 02:55:40'),
(8,11,'Entrega','Av. Demo 123','Ref. demo',4,NULL,NULL,0,'2026-10-28 05:54:14'),
(9,18,'Principal','Av. Demo 123','Ref. demo',2,NULL,NULL,1,'2026-10-29 02:38:55');
/*!40000 ALTER TABLE `direcciones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `eventos_analitica`
--

DROP TABLE IF EXISTS `eventos_analitica`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos_analitica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_evento` varchar(60) NOT NULL,
  `datos_json` varchar(1000) NOT NULL DEFAULT '{}',
  `pagina` varchar(80) NOT NULL DEFAULT '',
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_eventos_analitica_tipo_fecha` (`tipo_evento`,`creado_en`),
  KEY `idx_eventos_analitica_fecha` (`creado_en`)
) ENGINE=InnoDB AUTO_INCREMENT=1211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos_analitica`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `eventos_analitica` WRITE;
/*!40000 ALTER TABLE `eventos_analitica` DISABLE KEYS */;
INSERT INTO `eventos_analitica` VALUES
(46,'visita','[]','index.php',NULL,'2026-10-28 01:08:29'),
(47,'visita','[]','index.php',NULL,'2026-10-28 01:08:31'),
(48,'visita','[]','index.php',NULL,'2026-10-28 01:09:21'),
(49,'visita','[]','index.php',NULL,'2026-10-28 01:09:27'),
(50,'visita','[]','index.php',NULL,'2026-10-28 01:10:35'),
(51,'visita','[]','index.php',NULL,'2026-10-28 01:11:01'),
(52,'pregunta_chatbot','{\"faq_id\":1,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:11:05'),
(53,'pregunta_chatbot','{\"faq_id\":3,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:11:19'),
(54,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:11:20'),
(55,'pregunta_chatbot','{\"faq_id\":4,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:11:21'),
(56,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',NULL,'2026-10-28 01:11:26'),
(57,'visita','[]','index.php',NULL,'2026-10-28 01:11:36'),
(58,'visita','[]','index.php',NULL,'2026-10-28 01:11:39'),
(59,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:11:44'),
(60,'pregunta_chatbot','{\"faq_id\":3,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:11:51'),
(61,'visita','[]','index.php',NULL,'2026-10-28 01:11:52'),
(62,'visita','[]','index.php',NULL,'2026-10-28 01:12:03'),
(63,'visita','[]','index.php',NULL,'2026-10-28 01:12:06'),
(64,'visita','[]','index.php',NULL,'2026-10-28 01:12:06'),
(65,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:12:08'),
(66,'pregunta_chatbot','{\"faq_id\":7,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:12:15'),
(67,'visita','[]','index.php',NULL,'2026-10-28 01:12:28'),
(68,'visita','[]','index.php',NULL,'2026-10-28 01:12:31'),
(69,'visita','[]','index.php',NULL,'2026-10-28 01:12:32'),
(70,'visita','[]','index.php',NULL,'2026-10-28 01:12:34'),
(71,'visita','[]','index.php',NULL,'2026-10-28 01:12:37'),
(72,'visita','[]','index.php',NULL,'2026-10-28 01:12:41'),
(73,'visita','[]','index.php',NULL,'2026-10-28 01:12:45'),
(74,'visita','[]','index.php',NULL,'2026-10-28 01:12:46'),
(75,'visita','[]','index.php',NULL,'2026-10-28 01:12:47'),
(76,'pregunta_chatbot','{\"faq_id\":3,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-28 01:12:48'),
(77,'visita','[]','index.php',NULL,'2026-10-28 01:12:54'),
(78,'visita','[]','index.php',NULL,'2026-10-28 01:12:57'),
(79,'visita','[]','index.php',NULL,'2026-10-28 01:12:59'),
(80,'visita','[]','index.php',NULL,'2026-10-28 01:13:04'),
(81,'visita','[]','index.php',NULL,'2026-10-28 01:13:08'),
(82,'visita','[]','index.php',NULL,'2026-10-28 01:14:30'),
(83,'visita','[]','index.php',NULL,'2026-10-28 01:14:33'),
(84,'visita','[]','index.php',NULL,'2026-10-28 01:14:42'),
(85,'visita','[]','index.php',NULL,'2026-10-28 01:14:48'),
(86,'visita','[]','index.php',NULL,'2026-10-28 01:14:51'),
(87,'visita','[]','index.php',NULL,'2026-10-28 01:14:56'),
(88,'visita','[]','index.php',NULL,'2026-10-28 01:14:59'),
(89,'visita','[]','index.php',NULL,'2026-10-28 01:15:04'),
(90,'visita','[]','index.php',NULL,'2026-10-28 01:15:06'),
(91,'visita','[]','index.php',NULL,'2026-10-28 01:15:11'),
(92,'visita','[]','index.php',NULL,'2026-10-28 01:15:13'),
(93,'visita','[]','index.php',NULL,'2026-10-28 01:15:15'),
(94,'visita','[]','index.php',NULL,'2026-10-28 01:15:17'),
(95,'visita','[]','index.php',NULL,'2026-10-28 01:15:19'),
(96,'visita','[]','index.php',NULL,'2026-10-28 01:16:12'),
(97,'visita','[]','index.php',NULL,'2026-10-28 01:16:14'),
(98,'visita','[]','index.php',NULL,'2026-10-28 01:17:52'),
(99,'visita','[]','index.php',NULL,'2026-10-28 01:19:34'),
(100,'visita','[]','index.php',NULL,'2026-10-28 01:20:32'),
(101,'visita','[]','index.php',NULL,'2026-10-28 01:20:34'),
(102,'visita','[]','index.php',NULL,'2026-10-28 01:20:42'),
(103,'visita','[]','index.php',NULL,'2026-10-28 01:20:49'),
(104,'visita','[]','index.php',NULL,'2026-10-28 01:20:59'),
(105,'visita','[]','index.php',NULL,'2026-10-28 01:21:04'),
(106,'visita','[]','index.php',NULL,'2026-10-28 01:21:09'),
(107,'visita','[]','index.php',NULL,'2026-10-28 01:21:13'),
(108,'visita','[]','index.php',NULL,'2026-10-28 01:21:20'),
(109,'visita','[]','index.php',NULL,'2026-10-28 01:21:31'),
(110,'visita','[]','index.php',NULL,'2026-10-28 01:21:44'),
(111,'visita','[]','index.php',NULL,'2026-10-28 01:21:48'),
(112,'visita','[]','index.php',NULL,'2026-10-28 01:23:34'),
(113,'visita','[]','index.php',NULL,'2026-10-28 01:23:40'),
(114,'visita','[]','index.php',NULL,'2026-10-28 01:23:42'),
(115,'visita','[]','index.php',NULL,'2026-10-28 01:24:08'),
(116,'visita','[]','index.php',NULL,'2026-10-28 01:24:16'),
(117,'visita','[]','index.php',NULL,'2026-10-28 01:24:17'),
(118,'visita','[]','index.php',NULL,'2026-10-28 01:24:19'),
(119,'visita','[]','index.php',NULL,'2026-10-28 01:24:24'),
(120,'visita','[]','index.php',NULL,'2026-10-28 01:24:26'),
(121,'visita','[]','index.php',NULL,'2026-10-28 01:24:29'),
(122,'visita','[]','index.php',NULL,'2026-10-28 01:24:31'),
(123,'visita','[]','index.php',NULL,'2026-10-28 01:24:56'),
(124,'visita','[]','index.php',NULL,'2026-10-28 01:24:58'),
(125,'visita','[]','index.php',NULL,'2026-10-28 01:25:00'),
(126,'visita','[]','index.php',NULL,'2026-10-28 01:25:01'),
(127,'visita','[]','index.php',NULL,'2026-10-28 01:25:03'),
(128,'visita','[]','index.php',NULL,'2026-10-28 01:25:18'),
(129,'visita','[]','index.php',NULL,'2026-10-28 01:25:22'),
(130,'visita','[]','index.php',NULL,'2026-10-28 01:25:24'),
(131,'visita','[]','index.php',NULL,'2026-10-28 01:25:25'),
(132,'visita','[]','index.php',NULL,'2026-10-28 01:25:25'),
(133,'visita','[]','index.php',NULL,'2026-10-28 01:25:29'),
(134,'visita','[]','index.php',NULL,'2026-10-28 01:25:33'),
(135,'visita','[]','index.php',NULL,'2026-10-28 01:25:37'),
(136,'visita','[]','index.php',NULL,'2026-10-28 01:30:21'),
(137,'visita','[]','index.php',NULL,'2026-10-28 01:30:22'),
(138,'visita','[]','index.php',NULL,'2026-10-28 01:30:24'),
(139,'visita','[]','index.php',NULL,'2026-10-28 01:30:27'),
(140,'visita','[]','index.php',NULL,'2026-10-28 01:31:00'),
(141,'visita','[]','index.php',NULL,'2026-10-28 01:31:17'),
(142,'visita','[]','index.php',NULL,'2026-10-28 01:31:22'),
(143,'visita','[]','index.php',NULL,'2026-10-28 01:31:33'),
(144,'visita','[]','index.php',NULL,'2026-10-28 01:31:34'),
(145,'visita','[]','index.php',NULL,'2026-10-28 01:31:36'),
(146,'visita','[]','index.php',NULL,'2026-10-28 01:31:44'),
(147,'visita','[]','index.php',NULL,'2026-10-28 01:31:55'),
(148,'visita','[]','index.php',NULL,'2026-10-28 01:31:56'),
(149,'visita','[]','index.php',NULL,'2026-10-28 01:40:39'),
(150,'visita','[]','index.php',NULL,'2026-10-28 01:40:41'),
(151,'visita','[]','index.php',NULL,'2026-10-28 01:40:44'),
(152,'visita','[]','index.php',NULL,'2026-10-28 01:40:48'),
(153,'visita','[]','index.php',NULL,'2026-10-28 01:40:49'),
(154,'visita','[]','index.php',NULL,'2026-10-28 01:40:51'),
(155,'visita','[]','index.php',NULL,'2026-10-28 01:41:02'),
(156,'visita','[]','index.php',NULL,'2026-10-28 01:41:05'),
(157,'visita','[]','index.php',NULL,'2026-10-28 01:41:15'),
(158,'visita','[]','index.php',NULL,'2026-10-28 01:41:44'),
(159,'visita','[]','index.php',NULL,'2026-10-28 01:47:10'),
(160,'visita','[]','index.php',NULL,'2026-10-28 01:51:51'),
(161,'visita','[]','index.php',NULL,'2026-10-28 01:51:56'),
(162,'visita','[]','index.php',NULL,'2026-10-28 01:52:25'),
(163,'visita','[]','index.php',NULL,'2026-10-28 01:52:26'),
(164,'visita','[]','index.php',NULL,'2026-10-28 01:52:26'),
(165,'visita','[]','index.php',NULL,'2026-10-28 01:52:27'),
(166,'visita','[]','index.php',NULL,'2026-10-28 01:52:29'),
(167,'visita','[]','index.php',NULL,'2026-10-28 01:52:32'),
(168,'visita','[]','index.php',NULL,'2026-10-28 01:52:34'),
(169,'visita','[]','index.php',NULL,'2026-10-28 01:52:47'),
(170,'visita','[]','index.php',NULL,'2026-10-28 01:53:11'),
(171,'visita','[]','index.php',NULL,'2026-10-28 01:53:15'),
(172,'visita','[]','index.php',NULL,'2026-10-28 01:59:17'),
(173,'visita','[]','index.php',NULL,'2026-10-28 01:59:18'),
(174,'visita','[]','index.php',NULL,'2026-10-28 01:59:39'),
(175,'visita','[]','index.php',NULL,'2026-10-28 01:59:41'),
(176,'agregar_carrito','{\"producto_id\":10}','ficha',NULL,'2026-10-28 01:59:42'),
(177,'agregar_carrito','{\"producto_id\":9}','ficha',NULL,'2026-10-28 01:59:43'),
(178,'visita','[]','index.php',NULL,'2026-10-28 01:59:44'),
(179,'compra','{\"pedido_id\":4}','checkout',NULL,'2026-10-28 02:01:18'),
(180,'visita','[]','index.php',NULL,'2026-10-28 02:01:21'),
(181,'visita','[]','index.php',NULL,'2026-10-28 02:01:27'),
(182,'visita','[]','index.php',NULL,'2026-10-28 02:01:29'),
(183,'visita','[]','index.php',NULL,'2026-10-28 02:01:38'),
(184,'visita','[]','index.php',NULL,'2026-10-28 02:01:46'),
(185,'visita','[]','index.php',NULL,'2026-10-28 02:01:54'),
(186,'visita','[]','index.php',NULL,'2026-10-28 02:02:02'),
(187,'visita','[]','index.php',NULL,'2026-10-28 02:02:05'),
(188,'visita','[]','index.php',NULL,'2026-10-28 02:02:30'),
(189,'visita','[]','index.php',NULL,'2026-10-28 02:02:32'),
(190,'visita','[]','index.php',NULL,'2026-10-28 02:02:38'),
(191,'visita','[]','index.php',NULL,'2026-10-28 02:02:40'),
(192,'visita','[]','index.php',NULL,'2026-10-28 02:02:49'),
(193,'visita','[]','index.php',NULL,'2026-10-28 02:02:50'),
(194,'visita','[]','index.php',NULL,'2026-10-28 02:02:53'),
(195,'visita','[]','index.php',NULL,'2026-10-28 02:02:55'),
(196,'visita','[]','index.php',NULL,'2026-10-28 02:03:00'),
(197,'visita','[]','index.php',NULL,'2026-10-28 02:03:06'),
(198,'visita','[]','index.php',NULL,'2026-10-28 02:03:08'),
(199,'visita','[]','index.php',NULL,'2026-10-28 02:03:10'),
(200,'visita','[]','index.php',NULL,'2026-10-28 02:03:12'),
(201,'visita','[]','index.php',NULL,'2026-10-28 02:03:13'),
(202,'visita','[]','index.php',NULL,'2026-10-28 02:03:18'),
(203,'visita','[]','index.php',NULL,'2026-10-28 02:03:19'),
(204,'visita','[]','index.php',NULL,'2026-10-28 02:03:22'),
(205,'visita','[]','index.php',NULL,'2026-10-28 02:03:23'),
(206,'visita','[]','index.php',NULL,'2026-10-28 02:06:01'),
(207,'visita','[]','index.php',NULL,'2026-10-28 02:06:11'),
(208,'visita','[]','index.php',NULL,'2026-10-28 02:06:15'),
(209,'visita','[]','index.php',NULL,'2026-10-28 02:06:19'),
(210,'visita','[]','index.php',NULL,'2026-10-28 02:06:35'),
(211,'visita','[]','index.php',NULL,'2026-10-28 02:08:37'),
(212,'visita','[]','index.php',NULL,'2026-10-28 02:08:43'),
(213,'visita','[]','index.php',NULL,'2026-10-28 02:08:47'),
(214,'visita','[]','index.php',NULL,'2026-10-28 02:08:58'),
(215,'visita','[]','index.php',NULL,'2026-10-28 02:08:59'),
(216,'visita','[]','index.php',NULL,'2026-10-28 02:08:59'),
(217,'visita','[]','index.php',NULL,'2026-10-28 02:09:00'),
(218,'visita','[]','index.php',NULL,'2026-10-28 02:09:20'),
(219,'visita','[]','index.php',NULL,'2026-10-28 02:09:23'),
(220,'visita','[]','index.php',NULL,'2026-10-28 02:09:30'),
(221,'clic_whatsapp','{\"producto\":\"Logitech MX Master 3S - Inalambrico | 8K DPI | USB-C | Silence Click\"}','tienda',NULL,'2026-10-28 02:09:33'),
(222,'clic_whatsapp','{\"producto\":\"JBL Charge 5 - Bluetooth 5.1 | 20h bateria | IP67 | Powerbank\"}','tienda',NULL,'2026-10-28 02:09:43'),
(223,'visita','[]','index.php',NULL,'2026-10-28 02:09:50'),
(224,'visita','[]','index.php',NULL,'2026-10-28 02:12:29'),
(225,'visita','[]','index.php',NULL,'2026-10-28 02:12:32'),
(226,'visita','[]','index.php',NULL,'2026-10-28 02:12:42'),
(227,'visita','[]','index.php',NULL,'2026-10-28 02:12:43'),
(228,'visita','[]','index.php',NULL,'2026-10-28 02:12:47'),
(229,'visita','[]','index.php',NULL,'2026-10-28 02:12:49'),
(230,'visita','[]','index.php',NULL,'2026-10-28 02:12:50'),
(231,'visita','[]','index.php',NULL,'2026-10-28 02:13:39'),
(232,'visita','[]','index.php',NULL,'2026-10-28 02:13:43'),
(233,'visita','[]','index.php',NULL,'2026-10-28 02:13:46'),
(234,'clic_whatsapp','{\"producto\":\"iPhone 15 Pro - Chip A17 Pro | 256GB | Camara 48MP\"}','tienda',NULL,'2026-10-28 02:13:48'),
(235,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-28 02:13:50'),
(236,'visita','[]','index.php',NULL,'2026-10-28 02:13:51'),
(237,'visita','[]','index.php',NULL,'2026-10-28 02:16:02'),
(238,'visita','[]','index.php',NULL,'2026-10-28 02:16:38'),
(239,'visita','[]','index.php',NULL,'2026-10-28 02:16:54'),
(240,'pregunta_chatbot','{\"faq_id\":7,\"origen\":\"faq\"}','chatbot',3,'2026-10-28 02:16:56'),
(241,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',3,'2026-10-28 02:16:58'),
(242,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',3,'2026-10-28 02:16:59'),
(243,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',3,'2026-10-28 02:16:59'),
(244,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"fallback\"}','chatbot',3,'2026-10-28 02:17:01'),
(245,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"fallback\"}','chatbot',3,'2026-10-28 02:17:10'),
(246,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',3,'2026-10-28 02:17:38'),
(247,'visita','[]','index.php',NULL,'2026-10-28 02:18:17'),
(248,'visita','[]','index.php',NULL,'2026-10-28 02:18:30'),
(249,'visita','[]','index.php',NULL,'2026-10-28 02:28:30'),
(250,'visita','[]','index.php',NULL,'2026-10-28 02:28:31'),
(251,'visita','[]','index.php',NULL,'2026-10-28 02:45:10'),
(252,'visita','[]','index.php',NULL,'2026-10-28 02:48:30'),
(253,'visita','[]','index.php',NULL,'2026-10-28 02:55:40'),
(254,'visita','[]','index.php',NULL,'2026-10-28 02:55:54'),
(255,'visita','[]','index.php',NULL,'2026-10-28 02:55:58'),
(256,'visita','[]','index.php',NULL,'2026-10-28 02:57:02'),
(257,'visita','[]','index.php',NULL,'2026-10-28 02:57:04'),
(258,'visita','[]','index.php',NULL,'2026-10-28 02:59:36'),
(259,'visita','[]','index.php',NULL,'2026-10-28 02:59:38'),
(260,'visita','[]','index.php',NULL,'2026-10-28 02:59:39'),
(261,'visita','[]','index.php',NULL,'2026-10-28 02:59:43'),
(262,'visita','[]','index.php',NULL,'2026-10-28 03:01:26'),
(263,'visita','[]','index.php',NULL,'2026-10-28 03:01:30'),
(264,'visita','[]','index.php',NULL,'2026-10-28 03:04:16'),
(265,'visita','[]','index.php',NULL,'2026-10-28 03:08:46'),
(266,'visita','[]','index.php',NULL,'2026-10-28 03:08:48'),
(267,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-28 03:08:52'),
(268,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-28 03:08:52'),
(269,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-28 03:08:53'),
(270,'visita','[]','index.php',NULL,'2026-10-28 03:08:57'),
(271,'visita','[]','index.php',NULL,'2026-10-28 03:08:59'),
(272,'visita','[]','index.php',NULL,'2026-10-28 03:09:01'),
(273,'agregar_carrito','{\"producto_id\":7}','ficha',NULL,'2026-10-28 03:09:05'),
(274,'visita','[]','index.php',NULL,'2026-10-28 03:09:07'),
(275,'visita','[]','index.php',NULL,'2026-10-28 03:09:09'),
(276,'compra','{\"pedido_id\":8}','checkout',NULL,'2026-10-28 03:09:44'),
(277,'visita','[]','index.php',NULL,'2026-10-28 03:09:45'),
(278,'visita','[]','index.php',NULL,'2026-10-28 03:10:05'),
(279,'visita','[]','index.php',NULL,'2026-10-28 03:10:08'),
(280,'agregar_carrito','{\"producto_id\":12}','ficha',NULL,'2026-10-28 03:10:10'),
(281,'visita','[]','index.php',NULL,'2026-10-28 03:10:11'),
(282,'visita','[]','index.php',NULL,'2026-10-28 03:10:23'),
(283,'visita','[]','index.php',NULL,'2026-10-28 03:10:25'),
(284,'visita','[]','index.php',NULL,'2026-10-28 03:11:00'),
(285,'visita','[]','index.php',NULL,'2026-10-28 03:11:12'),
(286,'compra','{\"pedido_id\":9}','checkout',NULL,'2026-10-28 03:11:45'),
(287,'visita','[]','index.php',NULL,'2026-10-28 03:11:48'),
(288,'visita','[]','index.php',NULL,'2026-10-28 03:12:03'),
(289,'agregar_carrito','{\"producto_id\":16}','ficha',NULL,'2026-10-28 03:12:06'),
(290,'agregar_carrito','{\"producto_id\":16}','ficha',NULL,'2026-10-28 03:12:06'),
(291,'visita','[]','index.php',NULL,'2026-10-28 03:12:07'),
(292,'visita','[]','index.php',NULL,'2026-10-28 03:12:25'),
(293,'visita','[]','index.php',NULL,'2026-10-28 03:12:30'),
(294,'visita','[]','index.php',NULL,'2026-10-28 03:12:35'),
(295,'visita','[]','index.php',NULL,'2026-10-28 03:12:35'),
(296,'visita','[]','index.php',NULL,'2026-10-28 03:14:17'),
(297,'visita','[]','index.php',NULL,'2026-10-28 03:14:19'),
(298,'visita','[]','index.php',NULL,'2026-10-28 03:15:12'),
(299,'visita','[]','index.php',NULL,'2026-10-28 03:15:17'),
(300,'visita','[]','index.php',NULL,'2026-10-28 03:15:21'),
(301,'visita','[]','index.php',NULL,'2026-10-28 03:15:26'),
(302,'visita','[]','index.php',NULL,'2026-10-28 03:15:32'),
(303,'visita','[]','index.php',NULL,'2026-10-28 03:15:33'),
(304,'visita','[]','index.php',NULL,'2026-10-28 03:15:40'),
(305,'visita','[]','index.php',NULL,'2026-10-28 03:15:40'),
(306,'visita','[]','index.php',NULL,'2026-10-28 03:16:03'),
(307,'visita','[]','index.php',NULL,'2026-10-28 03:17:26'),
(308,'visita','[]','index.php',NULL,'2026-10-28 03:17:28'),
(309,'visita','[]','index.php',NULL,'2026-10-28 03:17:30'),
(310,'visita','[]','index.php',NULL,'2026-10-28 03:17:35'),
(311,'visita','[]','index.php',NULL,'2026-10-28 03:17:43'),
(312,'visita','[]','index.php',NULL,'2026-10-28 03:17:48'),
(313,'visita','[]','index.php',NULL,'2026-10-28 03:18:04'),
(314,'visita','[]','index.php',NULL,'2026-10-28 03:18:07'),
(315,'visita','[]','index.php',NULL,'2026-10-28 03:18:38'),
(316,'visita','[]','index.php',NULL,'2026-10-28 03:18:40'),
(317,'visita','[]','index.php',NULL,'2026-10-28 03:18:41'),
(318,'visita','[]','index.php',NULL,'2026-10-28 03:19:31'),
(319,'visita','[]','index.php',NULL,'2026-10-28 03:19:39'),
(320,'visita','[]','index.php',NULL,'2026-10-28 03:19:41'),
(321,'visita','[]','index.php',NULL,'2026-10-28 03:22:04'),
(322,'visita','[]','index.php',NULL,'2026-10-28 03:22:47'),
(323,'visita','[]','index.php',NULL,'2026-10-28 03:22:57'),
(324,'visita','[]','index.php',NULL,'2026-10-28 03:29:25'),
(325,'visita','[]','index.php',NULL,'2026-10-28 03:29:27'),
(326,'visita','[]','index.php',NULL,'2026-10-28 03:29:30'),
(327,'visita','[]','index.php',NULL,'2026-10-28 03:29:35'),
(328,'visita','[]','index.php',NULL,'2026-10-28 03:29:43'),
(329,'visita','[]','index.php',NULL,'2026-10-28 03:39:09'),
(330,'visita','[]','index.php',NULL,'2026-10-28 03:40:40'),
(331,'visita','[]','index.php',NULL,'2026-10-28 03:41:08'),
(332,'visita','[]','index.php',NULL,'2026-10-28 03:41:10'),
(333,'visita','[]','index.php',NULL,'2026-10-28 03:41:14'),
(334,'visita','[]','index.php',NULL,'2026-10-28 03:41:18'),
(335,'visita','[]','index.php',NULL,'2026-10-28 03:41:28'),
(336,'visita','[]','index.php',NULL,'2026-10-28 03:41:28'),
(337,'visita','[]','index.php',NULL,'2026-10-28 03:41:29'),
(338,'visita','[]','index.php',NULL,'2026-10-28 03:41:32'),
(339,'visita','[]','index.php',NULL,'2026-10-28 03:41:39'),
(340,'visita','[]','index.php',NULL,'2026-10-28 03:42:12'),
(341,'visita','[]','index.php',NULL,'2026-10-28 03:42:13'),
(342,'visita','[]','index.php',NULL,'2026-10-28 03:42:17'),
(343,'visita','[]','index.php',NULL,'2026-10-28 03:42:20'),
(344,'visita','[]','index.php',NULL,'2026-10-28 03:47:22'),
(345,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-28 03:48:56'),
(346,'visita','[]','index.php',NULL,'2026-10-28 03:48:57'),
(347,'visita','[]','index.php',NULL,'2026-10-28 03:55:52'),
(348,'visita','[]','index.php',NULL,'2026-10-28 03:55:56'),
(349,'visita','[]','index.php',NULL,'2026-10-28 03:55:58'),
(350,'visita','[]','index.php',NULL,'2026-10-28 04:02:57'),
(351,'visita','[]','index.php',NULL,'2026-10-28 04:03:39'),
(352,'visita','[]','index.php',NULL,'2026-10-28 04:03:44'),
(353,'visita','[]','index.php',NULL,'2026-10-28 04:03:53'),
(354,'visita','[]','index.php',NULL,'2026-10-28 04:03:54'),
(355,'visita','[]','index.php',NULL,'2026-10-28 04:04:10'),
(356,'visita','[]','index.php',NULL,'2026-10-28 04:04:32'),
(357,'visita','[]','index.php',NULL,'2026-10-28 04:04:33'),
(358,'visita','[]','index.php',NULL,'2026-10-28 04:04:39'),
(359,'visita','[]','index.php',NULL,'2026-10-28 04:06:09'),
(360,'visita','[]','index.php',NULL,'2026-10-28 04:24:28'),
(361,'visita','[]','index.php',NULL,'2026-10-28 04:24:38'),
(362,'visita','[]','index.php',NULL,'2026-10-28 04:24:43'),
(363,'visita','[]','index.php',NULL,'2026-10-28 04:24:49'),
(364,'visita','[]','index.php',NULL,'2026-10-28 04:37:27'),
(365,'visita','[]','index.php',NULL,'2026-10-28 04:37:45'),
(366,'visita','[]','index.php',NULL,'2026-10-28 04:37:47'),
(367,'visita','[]','index.php',NULL,'2026-10-28 04:47:21'),
(368,'visita','[]','index.php',NULL,'2026-10-28 04:48:37'),
(369,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',11,'2026-10-28 04:48:54'),
(370,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',11,'2026-10-28 04:49:11'),
(371,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',11,'2026-10-28 04:49:29'),
(372,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',11,'2026-10-28 04:49:59'),
(373,'visita','[]','index.php',NULL,'2026-10-28 04:58:33'),
(374,'visita','[]','index.php',NULL,'2026-10-28 04:58:42'),
(375,'visita','[]','index.php',NULL,'2026-10-28 04:58:48'),
(376,'visita','[]','index.php',NULL,'2026-10-28 04:58:51'),
(377,'visita','[]','index.php',NULL,'2026-10-28 04:59:00'),
(378,'visita','[]','index.php',NULL,'2026-10-28 04:59:03'),
(379,'visita','[]','index.php',NULL,'2026-10-28 05:00:42'),
(380,'visita','[]','index.php',NULL,'2026-10-28 05:00:47'),
(381,'visita','[]','index.php',NULL,'2026-10-28 05:01:27'),
(382,'visita','[]','index.php',NULL,'2026-10-28 05:02:34'),
(383,'visita','[]','index.php',NULL,'2026-10-28 05:05:30'),
(384,'visita','[]','index.php',NULL,'2026-10-28 05:13:29'),
(385,'visita','[]','index.php',NULL,'2026-10-28 05:13:33'),
(386,'visita','[]','index.php',NULL,'2026-10-28 05:20:37'),
(387,'visita','[]','index.php',NULL,'2026-10-28 05:31:03'),
(388,'visita','[]','index.php',NULL,'2026-10-28 05:31:09'),
(389,'visita','[]','index.php',NULL,'2026-10-28 05:32:45'),
(390,'pregunta_chatbot','{\"faq_id\":7,\"origen\":\"faq\"}','chatbot',11,'2026-10-28 05:32:55'),
(391,'pregunta_chatbot','{\"faq_id\":0,\"origen\":\"ia\"}','chatbot',11,'2026-10-28 05:33:22'),
(392,'visita','[]','index.php',NULL,'2026-10-28 05:34:31'),
(393,'visita','[]','index.php',NULL,'2026-10-28 05:34:35'),
(394,'pregunta_chatbot','{\"faq_id\":7,\"origen\":\"faq\"}','chatbot',11,'2026-10-28 05:35:05'),
(395,'visita','[]','index.php',NULL,'2026-10-28 05:35:30'),
(396,'visita','[]','index.php',NULL,'2026-10-28 05:36:41'),
(397,'visita','[]','soporte',NULL,'2026-10-28 05:39:02'),
(398,'visita','[]','soporte',NULL,'2026-10-28 05:50:30'),
(399,'visita','[]','perfil',NULL,'2026-10-28 05:50:33'),
(400,'visita','[]','catalogo',NULL,'2026-10-28 05:50:35'),
(401,'visita','[]','producto',NULL,'2026-10-28 05:50:37'),
(402,'visita','[]','catalogo',NULL,'2026-10-28 05:50:40'),
(403,'visita','[]','producto',NULL,'2026-10-28 05:50:41'),
(404,'visita','[]','catalogo',NULL,'2026-10-28 05:50:43'),
(405,'visita','[]','producto',NULL,'2026-10-28 05:50:46'),
(406,'visita','[]','catalogo',NULL,'2026-10-28 05:50:48'),
(407,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-28 05:50:54'),
(408,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-28 05:50:54'),
(409,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-28 05:50:55'),
(410,'visita','[]','carrito',NULL,'2026-10-28 05:50:56'),
(411,'compra','{\"pedido_id\":10}','checkout',NULL,'2026-10-28 05:51:24'),
(412,'visita','[]','mis-pedidos',NULL,'2026-10-28 05:51:26'),
(413,'visita','[]','perfil',NULL,'2026-10-28 05:51:32'),
(414,'visita','[]','mis-pedidos',NULL,'2026-10-28 05:51:48'),
(415,'visita','[]','carrito',NULL,'2026-10-28 05:51:53'),
(416,'visita','[]','catalogo',NULL,'2026-10-28 05:51:57'),
(417,'agregar_carrito','{\"producto_id\":12}','ficha',NULL,'2026-10-28 05:51:58'),
(418,'agregar_carrito','{\"producto_id\":12}','ficha',NULL,'2026-10-28 05:51:59'),
(419,'visita','[]','carrito',NULL,'2026-10-28 05:51:59'),
(420,'visita','[]','perfil',NULL,'2026-10-28 05:53:35'),
(421,'visita','[]','direcciones',NULL,'2026-10-28 05:53:37'),
(422,'visita','[]','perfil',NULL,'2026-10-28 05:53:40'),
(423,'visita','[]','carrito',NULL,'2026-10-28 05:53:43'),
(424,'visita','[]','carrito',NULL,'2026-10-28 05:53:46'),
(425,'visita','[]','carrito',NULL,'2026-10-28 05:53:47'),
(426,'visita','[]','carrito',NULL,'2026-10-28 05:53:47'),
(427,'compra','{\"pedido_id\":11}','checkout',NULL,'2026-10-28 05:54:14'),
(428,'visita','[]','mis-pedidos',NULL,'2026-10-28 05:54:15'),
(429,'visita','[]','perfil',NULL,'2026-10-28 05:54:21'),
(430,'visita','[]','mis-pedidos',NULL,'2026-10-28 05:54:29'),
(431,'visita','[]','notificaciones',NULL,'2026-10-28 05:54:31'),
(432,'visita','[]','direcciones',NULL,'2026-10-28 05:54:32'),
(433,'visita','[]','carrito',NULL,'2026-10-28 05:54:41'),
(434,'visita','[]','catalogo',NULL,'2026-10-28 05:54:45'),
(435,'agregar_carrito','{\"producto_id\":12}','ficha',NULL,'2026-10-28 05:54:47'),
(436,'agregar_carrito','{\"producto_id\":12}','ficha',NULL,'2026-10-28 05:54:47'),
(437,'agregar_carrito','{\"producto_id\":12}','ficha',NULL,'2026-10-28 05:54:47'),
(438,'visita','[]','carrito',NULL,'2026-10-28 05:54:48'),
(439,'pregunta_chatbot','{\"faq_id\":5,\"origen\":\"faq\"}','chatbot',NULL,'2026-10-29 02:10:40'),
(440,'visita','[]','catalogo',NULL,'2026-10-29 02:12:09'),
(441,'visita','[]','catalogo',NULL,'2026-10-29 02:14:47'),
(442,'visita','[]','catalogo',NULL,'2026-10-29 02:14:56'),
(443,'visita','[]','perfil',NULL,'2026-10-29 02:14:59'),
(444,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:15:01'),
(445,'visita','[]','catalogo',NULL,'2026-10-29 02:15:21'),
(446,'visita','[]','catalogo',NULL,'2026-10-29 02:15:29'),
(447,'visita','[]','login',NULL,'2026-10-29 02:15:30'),
(448,'visita','[]','carrito',NULL,'2026-10-29 02:15:34'),
(449,'visita','[]','mis-productos',NULL,'2026-10-29 02:15:38'),
(450,'visita','[]','perfil',NULL,'2026-10-29 02:15:41'),
(451,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:15:47'),
(452,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:15:51'),
(453,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:15:53'),
(454,'visita','[]','catalogo',NULL,'2026-10-29 02:15:55'),
(455,'visita','[]','catalogo',NULL,'2026-10-29 02:15:58'),
(456,'visita','[]','login',NULL,'2026-10-29 02:15:59'),
(457,'visita','[]','carrito',NULL,'2026-10-29 02:16:02'),
(458,'visita','[]','perfil',NULL,'2026-10-29 02:16:06'),
(459,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:16:10'),
(460,'visita','[]','mis-productos',NULL,'2026-10-29 02:16:12'),
(461,'visita','[]','catalogo',NULL,'2026-10-29 02:16:22'),
(462,'visita','[]','login',NULL,'2026-10-29 02:16:23'),
(463,'visita','[]','carrito',NULL,'2026-10-29 02:16:37'),
(464,'visita','[]','perfil',NULL,'2026-10-29 02:16:40'),
(465,'visita','[]','perfil',NULL,'2026-10-29 02:17:05'),
(466,'visita','[]','perfil',NULL,'2026-10-29 02:17:08'),
(467,'visita','[]','catalogo',NULL,'2026-10-29 02:17:10'),
(468,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:17:13'),
(469,'visita','[]','perfil',NULL,'2026-10-29 02:17:19'),
(470,'visita','[]','perfil',NULL,'2026-10-29 02:17:23'),
(471,'visita','[]','perfil',NULL,'2026-10-29 02:17:25'),
(472,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:17:26'),
(473,'visita','[]','perfil',NULL,'2026-10-29 02:17:27'),
(474,'visita','[]','perfil',NULL,'2026-10-29 02:17:31'),
(475,'visita','[]','catalogo',NULL,'2026-10-29 02:17:38'),
(476,'visita','[]','login',NULL,'2026-10-29 02:17:39'),
(477,'visita','[]','carrito',NULL,'2026-10-29 02:17:43'),
(478,'visita','[]','perfil',NULL,'2026-10-29 02:17:46'),
(479,'visita','[]','mis-productos',NULL,'2026-10-29 02:17:50'),
(480,'visita','[]','notificaciones',NULL,'2026-10-29 02:17:51'),
(481,'visita','[]','direcciones',NULL,'2026-10-29 02:17:52'),
(482,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:17:56'),
(483,'visita','[]','mis-productos',NULL,'2026-10-29 02:18:01'),
(484,'visita','[]','mis-productos/nuevo',NULL,'2026-10-29 02:18:03'),
(485,'visita','[]','mis-productos',NULL,'2026-10-29 02:18:51'),
(486,'visita','[]','catalogo',NULL,'2026-10-29 02:18:58'),
(487,'visita','[]','catalogo',NULL,'2026-10-29 02:19:11'),
(488,'visita','[]','producto',NULL,'2026-10-29 02:19:17'),
(489,'visita','[]','catalogo',NULL,'2026-10-29 02:19:29'),
(490,'visita','[]','mis-productos',NULL,'2026-10-29 02:19:34'),
(491,'visita','[]','producto',NULL,'2026-10-29 02:19:38'),
(492,'visita','[]','mis-productos',NULL,'2026-10-29 02:19:46'),
(493,'visita','[]','mis-productos',NULL,'2026-10-29 02:19:48'),
(494,'visita','[]','mis-productos/editar',NULL,'2026-10-29 02:19:50'),
(495,'visita','[]','mis-productos/editar',NULL,'2026-10-29 02:20:09'),
(496,'visita','[]','mis-productos/editar',NULL,'2026-10-29 02:20:25'),
(497,'visita','[]','mis-productos/editar',NULL,'2026-10-29 02:20:37'),
(498,'visita','[]','perfil',NULL,'2026-10-29 02:20:40'),
(499,'visita','[]','catalogo',NULL,'2026-10-29 02:20:44'),
(500,'visita','[]','catalogo',NULL,'2026-10-29 02:20:49'),
(501,'visita','[]','producto',NULL,'2026-10-29 02:20:53'),
(502,'visita','[]','catalogo',NULL,'2026-10-29 02:21:02'),
(503,'visita','[]','producto',NULL,'2026-10-29 02:21:10'),
(504,'visita','[]','catalogo',NULL,'2026-10-29 02:21:12'),
(505,'visita','[]','producto',NULL,'2026-10-29 02:21:14'),
(506,'visita','[]','catalogo',NULL,'2026-10-29 02:21:16'),
(507,'visita','[]','producto',NULL,'2026-10-29 02:21:18'),
(508,'visita','[]','catalogo',NULL,'2026-10-29 02:21:20'),
(509,'visita','[]','producto',NULL,'2026-10-29 02:21:22'),
(510,'visita','[]','catalogo',NULL,'2026-10-29 02:21:23'),
(511,'visita','[]','producto',NULL,'2026-10-29 02:21:25'),
(512,'visita','[]','catalogo',NULL,'2026-10-29 02:21:29'),
(513,'visita','[]','producto',NULL,'2026-10-29 02:21:31'),
(514,'visita','[]','catalogo',NULL,'2026-10-29 02:21:35'),
(515,'visita','[]','producto',NULL,'2026-10-29 02:21:37'),
(516,'visita','[]','producto',NULL,'2026-10-29 02:21:38'),
(517,'visita','[]','catalogo',NULL,'2026-10-29 02:21:44'),
(518,'visita','[]','producto',NULL,'2026-10-29 02:21:46'),
(519,'visita','[]','catalogo',NULL,'2026-10-29 02:21:52'),
(520,'visita','[]','producto',NULL,'2026-10-29 02:21:55'),
(521,'visita','[]','catalogo',NULL,'2026-10-29 02:21:59'),
(522,'visita','[]','producto',NULL,'2026-10-29 02:22:02'),
(523,'visita','[]','catalogo',NULL,'2026-10-29 02:22:08'),
(524,'visita','[]','producto',NULL,'2026-10-29 02:22:13'),
(525,'visita','[]','catalogo',NULL,'2026-10-29 02:22:14'),
(526,'visita','[]','producto',NULL,'2026-10-29 02:22:17'),
(527,'visita','[]','catalogo',NULL,'2026-10-29 02:22:21'),
(528,'visita','[]','producto',NULL,'2026-10-29 02:22:24'),
(529,'visita','[]','catalogo',NULL,'2026-10-29 02:22:26'),
(530,'visita','[]','producto',NULL,'2026-10-29 02:22:28'),
(531,'visita','[]','catalogo',NULL,'2026-10-29 02:22:29'),
(532,'visita','[]','catalogo',NULL,'2026-10-29 02:29:54'),
(533,'visita','[]','mis-productos',NULL,'2026-10-29 02:29:56'),
(534,'visita','[]','mis-productos',NULL,'2026-10-29 02:30:11'),
(535,'visita','[]','mis-productos',NULL,'2026-10-29 02:30:12'),
(536,'visita','[]','catalogo',NULL,'2026-10-29 02:30:14'),
(537,'visita','[]','catalogo',NULL,'2026-10-29 02:30:15'),
(538,'visita','[]','mis-productos',NULL,'2026-10-29 02:30:17'),
(539,'visita','[]','catalogo',NULL,'2026-10-29 02:30:22'),
(540,'visita','[]','login',NULL,'2026-10-29 02:30:24'),
(541,'visita','[]','carrito',NULL,'2026-10-29 02:30:28'),
(542,'visita','[]','carrito',NULL,'2026-10-29 02:30:45'),
(543,'visita','[]','catalogo',NULL,'2026-10-29 02:31:50'),
(544,'visita','[]','login',NULL,'2026-10-29 02:31:52'),
(545,'visita','[]','login',NULL,'2026-10-29 02:35:00'),
(546,'visita','[]','inicio',NULL,'2026-10-29 02:35:49'),
(547,'busqueda','{\"consulta\":\"brte\"}','catalogo',NULL,'2026-10-29 02:35:51'),
(548,'visita','[]','catalogo',NULL,'2026-10-29 02:35:52'),
(549,'visita','[]','login',NULL,'2026-10-29 02:37:38'),
(550,'visita','[]','carrito',NULL,'2026-10-29 02:38:55'),
(551,'visita','[]','favoritos',NULL,'2026-10-29 02:39:02'),
(552,'visita','[]','catalogo',NULL,'2026-10-29 02:39:05'),
(553,'visita','[]','catalogo',NULL,'2026-10-29 02:40:11'),
(554,'visita','[]','catalogo',NULL,'2026-10-29 02:40:12'),
(555,'visita','[]','perfil',NULL,'2026-10-29 02:40:18'),
(556,'visita','[]','direcciones',NULL,'2026-10-29 02:40:27'),
(557,'visita','[]','perfil',NULL,'2026-10-29 02:40:29'),
(558,'visita','[]','notificaciones',NULL,'2026-10-29 02:40:32'),
(559,'visita','[]','favoritos',NULL,'2026-10-29 02:40:33'),
(560,'visita','[]','soporte',NULL,'2026-10-29 02:40:34'),
(561,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:40:37'),
(562,'visita','[]','perfil',NULL,'2026-10-29 02:40:39'),
(563,'visita','[]','catalogo',NULL,'2026-10-29 02:41:50'),
(564,'visita','[]','catalogo',NULL,'2026-10-29 02:41:58'),
(565,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:42:32'),
(566,'visita','[]','perfil',NULL,'2026-10-29 02:42:35'),
(567,'visita','[]','perfil',NULL,'2026-10-29 02:42:42'),
(568,'visita','[]','catalogo',NULL,'2026-10-29 02:44:36'),
(569,'visita','[]','catalogo',NULL,'2026-10-29 02:44:42'),
(570,'visita','[]','catalogo',NULL,'2026-10-29 02:46:09'),
(571,'visita','[]','catalogo',NULL,'2026-10-29 02:46:11'),
(572,'visita','[]','catalogo',NULL,'2026-10-29 02:46:12'),
(573,'visita','[]','catalogo',NULL,'2026-10-29 02:46:17'),
(574,'visita','[]','catalogo',NULL,'2026-10-29 02:46:18'),
(575,'visita','[]','catalogo',NULL,'2026-10-29 02:46:19'),
(576,'visita','[]','catalogo',NULL,'2026-10-29 02:46:27'),
(577,'visita','[]','catalogo',NULL,'2026-10-29 02:46:28'),
(578,'visita','[]','catalogo',NULL,'2026-10-29 02:46:42'),
(579,'visita','[]','login',NULL,'2026-10-29 02:46:44'),
(580,'visita','[]','login',NULL,'2026-10-29 02:47:00'),
(581,'visita','[]','login',NULL,'2026-10-29 02:47:51'),
(582,'visita','[]','login',NULL,'2026-10-29 02:47:58'),
(583,'visita','[]','producto',NULL,'2026-10-29 02:48:02'),
(584,'producto_visto','{\"producto_id\":16}','producto',NULL,'2026-10-29 02:48:02'),
(585,'visita','[]','producto',NULL,'2026-10-29 02:48:02'),
(586,'producto_visto','{\"producto_id\":16}','producto',NULL,'2026-10-29 02:48:03'),
(587,'visita','[]','producto',NULL,'2026-10-29 02:48:03'),
(588,'visita','[]','catalogo',NULL,'2026-10-29 02:48:04'),
(589,'visita','[]','catalogo',NULL,'2026-10-29 02:48:07'),
(590,'visita','[]','catalogo',NULL,'2026-10-29 02:48:17'),
(591,'visita','[]','catalogo',NULL,'2026-10-29 02:48:22'),
(592,'visita','[]','login',NULL,'2026-10-29 02:48:26'),
(593,'visita','[]','login',NULL,'2026-10-29 02:48:44'),
(594,'visita','[]','catalogo',NULL,'2026-10-29 02:50:09'),
(595,'visita','[]','login',NULL,'2026-10-29 02:50:11'),
(596,'visita','[]','carrito',NULL,'2026-10-29 02:50:21'),
(597,'visita','[]','catalogo',NULL,'2026-10-29 02:50:25'),
(598,'visita','[]','login',NULL,'2026-10-29 02:50:27'),
(599,'visita','[]','carrito',NULL,'2026-10-29 02:50:32'),
(600,'visita','[]','perfil',NULL,'2026-10-29 02:50:40'),
(601,'visita','[]','catalogo',NULL,'2026-10-29 02:50:47'),
(602,'visita','[]','login',NULL,'2026-10-29 02:50:49'),
(603,'visita','[]','login',NULL,'2026-10-29 02:51:11'),
(604,'visita','[]','carrito',NULL,'2026-10-29 02:51:20'),
(605,'visita','[]','catalogo',NULL,'2026-10-29 02:51:22'),
(606,'visita','[]','login',NULL,'2026-10-29 02:51:43'),
(607,'visita','[]','carrito',NULL,'2026-10-29 02:51:48'),
(608,'visita','[]','catalogo',NULL,'2026-10-29 02:51:55'),
(609,'visita','[]','login',NULL,'2026-10-29 02:51:57'),
(610,'visita','[]','carrito',NULL,'2026-10-29 02:52:21'),
(611,'visita','[]','catalogo',NULL,'2026-10-29 02:52:25'),
(612,'visita','[]','login',NULL,'2026-10-29 02:52:27'),
(613,'visita','[]','login',NULL,'2026-10-29 02:52:31'),
(614,'visita','[]','carrito',NULL,'2026-10-29 02:52:34'),
(615,'visita','[]','carrito',NULL,'2026-10-29 02:53:09'),
(616,'visita','[]','catalogo',NULL,'2026-10-29 02:53:18'),
(617,'visita','[]','login',NULL,'2026-10-29 02:53:19'),
(618,'visita','[]','carrito',NULL,'2026-10-29 02:53:23'),
(619,'visita','[]','catalogo',NULL,'2026-10-29 02:53:26'),
(620,'visita','[]','login',NULL,'2026-10-29 02:53:30'),
(621,'visita','[]','login',NULL,'2026-10-29 02:54:04'),
(622,'visita','[]','carrito',NULL,'2026-10-29 02:54:08'),
(623,'visita','[]','carrito',NULL,'2026-10-29 02:54:34'),
(624,'visita','[]','perfil',NULL,'2026-10-29 02:54:44'),
(625,'visita','[]','mis-pedidos',NULL,'2026-10-29 02:54:49'),
(626,'visita','[]','catalogo',NULL,'2026-10-29 02:54:53'),
(627,'visita','[]','catalogo',NULL,'2026-10-29 02:55:29'),
(628,'visita','[]','catalogo',NULL,'2026-10-29 02:55:30'),
(629,'visita','[]','catalogo',NULL,'2026-10-29 02:55:33'),
(630,'visita','[]','catalogo',NULL,'2026-10-29 02:55:39'),
(631,'visita','[]','login',NULL,'2026-10-29 02:55:41'),
(632,'visita','[]','carrito',NULL,'2026-10-29 02:55:46'),
(633,'visita','[]','carrito',NULL,'2026-10-29 02:56:11'),
(634,'visita','[]','perfil',NULL,'2026-10-29 02:56:18'),
(635,'visita','[]','perfil',NULL,'2026-10-29 02:56:24'),
(636,'visita','[]','perfil',NULL,'2026-10-29 02:56:31'),
(637,'visita','[]','catalogo',NULL,'2026-10-29 02:56:33'),
(638,'visita','[]','perfil',NULL,'2026-10-29 02:56:38'),
(639,'visita','[]','perfil',NULL,'2026-10-29 02:56:49'),
(640,'visita','[]','perfil',NULL,'2026-10-29 02:59:41'),
(641,'visita','[]','perfil',NULL,'2026-10-29 03:00:20'),
(642,'visita','[]','perfil',NULL,'2026-10-29 03:00:24'),
(643,'visita','[]','perfil',NULL,'2026-10-29 03:00:36'),
(644,'visita','[]','perfil',NULL,'2026-10-29 03:00:42'),
(645,'visita','[]','catalogo',NULL,'2026-10-29 03:01:32'),
(646,'visita','[]','login',NULL,'2026-10-29 03:01:34'),
(647,'visita','[]','login',NULL,'2026-10-29 03:02:19'),
(648,'visita','[]','carrito',NULL,'2026-10-29 03:03:43'),
(649,'visita','[]','perfil',NULL,'2026-10-29 03:03:48'),
(650,'visita','[]','perfil',NULL,'2026-10-29 03:04:36'),
(651,'visita','[]','perfil',NULL,'2026-10-29 03:04:40'),
(652,'visita','[]','perfil',NULL,'2026-10-29 03:05:03'),
(653,'visita','[]','perfil',NULL,'2026-10-29 03:05:18'),
(654,'visita','[]','perfil',NULL,'2026-10-29 03:05:21'),
(655,'visita','[]','perfil',NULL,'2026-10-29 03:07:35'),
(656,'visita','[]','mis-productos',NULL,'2026-10-29 03:09:33'),
(657,'visita','[]','catalogo',NULL,'2026-10-29 03:09:38'),
(658,'visita','[]','mis-productos',NULL,'2026-10-29 03:09:40'),
(659,'visita','[]','catalogo',NULL,'2026-10-29 03:10:58'),
(660,'visita','[]','login',NULL,'2026-10-29 03:11:00'),
(661,'visita','[]','login',NULL,'2026-10-29 03:11:28'),
(662,'visita','[]','carrito',NULL,'2026-10-29 03:11:32'),
(663,'visita','[]','perfil',NULL,'2026-10-29 03:11:39'),
(664,'visita','[]','catalogo',NULL,'2026-10-29 03:14:21'),
(665,'producto_visto','{\"producto_id\":16}','producto',NULL,'2026-10-29 03:14:33'),
(666,'visita','[]','producto',NULL,'2026-10-29 03:14:33'),
(667,'visita','[]','catalogo',NULL,'2026-10-29 03:14:36'),
(668,'producto_visto','{\"producto_id\":16}','producto',NULL,'2026-10-29 03:14:43'),
(669,'visita','[]','producto',NULL,'2026-10-29 03:14:43'),
(670,'visita','[]','catalogo',NULL,'2026-10-29 03:14:51'),
(671,'visita','[]','catalogo',NULL,'2026-10-29 03:14:57'),
(672,'visita','[]','catalogo',NULL,'2026-10-29 03:15:03'),
(673,'visita','[]','catalogo',NULL,'2026-10-29 03:15:05'),
(674,'producto_visto','{\"producto_id\":42}','producto',NULL,'2026-10-29 03:15:06'),
(675,'visita','[]','producto',NULL,'2026-10-29 03:15:06'),
(676,'visita','[]','catalogo',NULL,'2026-10-29 03:15:09'),
(677,'visita','[]','catalogo',NULL,'2026-10-29 03:15:10'),
(678,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:15:13'),
(679,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:15:21'),
(680,'visita','[]','catalogo',NULL,'2026-10-29 03:15:22'),
(681,'visita','[]','perfil',NULL,'2026-10-29 03:16:31'),
(682,'visita','[]','perfil',NULL,'2026-10-29 03:18:12'),
(683,'visita','[]','perfil',NULL,'2026-10-29 03:18:12'),
(684,'visita','[]','perfil',NULL,'2026-10-29 03:18:13'),
(685,'visita','[]','perfil',NULL,'2026-10-29 03:18:14'),
(686,'visita','[]','perfil',NULL,'2026-10-29 03:18:23'),
(687,'visita','[]','perfil',NULL,'2026-10-29 03:18:24'),
(688,'visita','[]','perfil',NULL,'2026-10-29 03:18:25'),
(689,'visita','[]','perfil',NULL,'2026-10-29 03:18:25'),
(690,'visita','[]','login',NULL,'2026-10-29 03:18:34'),
(691,'visita','[]','perfil',NULL,'2026-10-29 03:18:37'),
(692,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:18:40'),
(693,'visita','[]','catalogo',NULL,'2026-10-29 03:18:41'),
(694,'visita','[]','perfil',NULL,'2026-10-29 03:18:43'),
(695,'visita','[]','catalogo',NULL,'2026-10-29 03:18:55'),
(696,'visita','[]','login',NULL,'2026-10-29 03:18:56'),
(697,'visita','[]','carrito',NULL,'2026-10-29 03:19:00'),
(698,'visita','[]','perfil',NULL,'2026-10-29 03:19:03'),
(699,'visita','[]','perfil',NULL,'2026-10-29 03:19:16'),
(700,'visita','[]','carrito',NULL,'2026-10-29 03:19:32'),
(701,'visita','[]','perfil',NULL,'2026-10-29 03:19:34'),
(702,'visita','[]','perfil',NULL,'2026-10-29 03:19:46'),
(703,'visita','[]','perfil',NULL,'2026-10-29 03:19:47'),
(704,'visita','[]','direcciones',NULL,'2026-10-29 03:19:58'),
(705,'visita','[]','perfil',NULL,'2026-10-29 03:19:59'),
(706,'visita','[]','perfil',NULL,'2026-10-29 03:20:58'),
(707,'visita','[]','perfil',NULL,'2026-10-29 03:21:23'),
(708,'visita','[]','login',NULL,'2026-10-29 03:22:09'),
(709,'visita','[]','carrito',NULL,'2026-10-29 03:22:10'),
(710,'visita','[]','login',NULL,'2026-10-29 03:22:44'),
(711,'visita','[]','carrito',NULL,'2026-10-29 03:22:44'),
(712,'visita','[]','perfil',NULL,'2026-10-29 03:22:45'),
(713,'visita','[]','login',NULL,'2026-10-29 03:23:37'),
(714,'visita','[]','carrito',NULL,'2026-10-29 03:23:38'),
(715,'visita','[]','perfil',NULL,'2026-10-29 03:23:39'),
(716,'visita','[]','perfil',NULL,'2026-10-29 03:23:40'),
(717,'visita','[]','perfil',NULL,'2026-10-29 03:23:40'),
(718,'visita','[]','perfil',NULL,'2026-10-29 03:23:40'),
(719,'visita','[]','login',NULL,'2026-10-29 03:23:51'),
(720,'visita','[]','carrito',NULL,'2026-10-29 03:23:52'),
(721,'visita','[]','perfil',NULL,'2026-10-29 03:23:53'),
(722,'visita','[]','login',NULL,'2026-10-29 03:24:09'),
(723,'visita','[]','carrito',NULL,'2026-10-29 03:24:10'),
(724,'visita','[]','perfil',NULL,'2026-10-29 03:24:10'),
(725,'visita','[]','login',NULL,'2026-10-29 03:24:17'),
(726,'visita','[]','carrito',NULL,'2026-10-29 03:24:18'),
(727,'visita','[]','perfil',NULL,'2026-10-29 03:24:18'),
(728,'visita','[]','perfil',NULL,'2026-10-29 03:24:20'),
(729,'visita','[]','perfil',NULL,'2026-10-29 03:24:20'),
(730,'visita','[]','perfil',NULL,'2026-10-29 03:24:21'),
(731,'visita','[]','login',NULL,'2026-10-29 03:24:32'),
(732,'visita','[]','carrito',NULL,'2026-10-29 03:24:33'),
(733,'visita','[]','perfil',NULL,'2026-10-29 03:24:33'),
(734,'visita','[]','perfil',NULL,'2026-10-29 03:25:18'),
(735,'visita','[]','login',NULL,'2026-10-29 03:30:33'),
(736,'visita','[]','carrito',NULL,'2026-10-29 03:30:34'),
(737,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:30:35'),
(738,'visita','[]','producto',NULL,'2026-10-29 03:30:35'),
(739,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 03:30:35'),
(740,'visita','[]','catalogo',NULL,'2026-10-29 03:30:36'),
(741,'visita','[]','catalogo',NULL,'2026-10-29 03:30:38'),
(742,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:30:40'),
(743,'visita','[]','producto',NULL,'2026-10-29 03:30:40'),
(744,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:30:41'),
(745,'visita','[]','producto',NULL,'2026-10-29 03:30:41'),
(746,'visita','[]','catalogo',NULL,'2026-10-29 03:30:42'),
(747,'visita','[]','catalogo',NULL,'2026-10-29 03:30:43'),
(748,'visita','[]','carrito',NULL,'2026-10-29 03:30:44'),
(749,'visita','[]','carrito',NULL,'2026-10-29 03:30:46'),
(750,'visita','[]','carrito',NULL,'2026-10-29 03:30:47'),
(751,'visita','[]','carrito',NULL,'2026-10-29 03:30:48'),
(752,'visita','[]','carrito',NULL,'2026-10-29 03:30:49'),
(753,'visita','[]','carrito',NULL,'2026-10-29 03:30:50'),
(754,'visita','[]','perfil',NULL,'2026-10-29 03:30:51'),
(755,'visita','[]','perfil',NULL,'2026-10-29 03:30:52'),
(756,'visita','[]','favoritos',NULL,'2026-10-29 03:30:53'),
(757,'visita','[]','favoritos',NULL,'2026-10-29 03:30:54'),
(758,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:30:54'),
(759,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:30:55'),
(760,'visita','[]','carrito',NULL,'2026-10-29 03:30:56'),
(761,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:30:57'),
(762,'visita','[]','producto',NULL,'2026-10-29 03:30:57'),
(763,'visita','[]','perfil',NULL,'2026-10-29 03:31:16'),
(764,'visita','[]','login',NULL,'2026-10-29 03:31:20'),
(765,'visita','[]','login',NULL,'2026-10-29 03:31:21'),
(766,'visita','[]','login',NULL,'2026-10-29 03:31:22'),
(767,'visita','[]','login',NULL,'2026-10-29 03:31:23'),
(768,'visita','[]','login',NULL,'2026-10-29 03:31:32'),
(769,'visita','[]','carrito',NULL,'2026-10-29 03:31:33'),
(770,'visita','[]','carrito',NULL,'2026-10-29 03:31:33'),
(771,'visita','[]','carrito',NULL,'2026-10-29 03:31:34'),
(772,'visita','[]','carrito',NULL,'2026-10-29 03:31:34'),
(773,'visita','[]','catalogo',NULL,'2026-10-29 03:31:35'),
(774,'visita','[]','catalogo',NULL,'2026-10-29 03:31:35'),
(775,'visita','[]','catalogo',NULL,'2026-10-29 03:31:36'),
(776,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:31:36'),
(777,'visita','[]','producto',NULL,'2026-10-29 03:31:36'),
(778,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:31:37'),
(779,'visita','[]','producto',NULL,'2026-10-29 03:31:37'),
(780,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:31:37'),
(781,'visita','[]','producto',NULL,'2026-10-29 03:31:37'),
(782,'visita','[]','perfil',NULL,'2026-10-29 03:31:38'),
(783,'visita','[]','perfil',NULL,'2026-10-29 03:31:38'),
(784,'visita','[]','perfil',NULL,'2026-10-29 03:31:38'),
(785,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:31:39'),
(786,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:31:39'),
(787,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:31:40'),
(788,'visita','[]','perfil',NULL,'2026-10-29 03:33:12'),
(789,'visita','[]','login',NULL,'2026-10-29 03:33:20'),
(790,'visita','[]','carrito',NULL,'2026-10-29 03:33:21'),
(791,'visita','[]','catalogo',NULL,'2026-10-29 03:33:21'),
(792,'visita','[]','carrito',NULL,'2026-10-29 03:33:22'),
(793,'visita','[]','carrito',NULL,'2026-10-29 03:33:22'),
(794,'visita','[]','catalogo',NULL,'2026-10-29 03:36:54'),
(795,'visita','[]','catalogo',NULL,'2026-10-29 03:37:00'),
(796,'visita','[]','catalogo',NULL,'2026-10-29 03:37:07'),
(797,'visita','[]','catalogo',NULL,'2026-10-29 03:37:18'),
(798,'visita','[]','login',NULL,'2026-10-29 03:37:46'),
(799,'visita','[]','carrito',NULL,'2026-10-29 03:37:47'),
(800,'visita','[]','carrito',NULL,'2026-10-29 03:37:47'),
(801,'visita','[]','catalogo',NULL,'2026-10-29 03:38:03'),
(802,'visita','[]','login',NULL,'2026-10-29 03:38:36'),
(803,'visita','[]','carrito',NULL,'2026-10-29 03:38:37'),
(804,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:38:37'),
(805,'visita','[]','login',NULL,'2026-10-29 03:38:43'),
(806,'visita','[]','carrito',NULL,'2026-10-29 03:38:43'),
(807,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:38:44'),
(808,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:38:44'),
(809,'visita','[]','perfil',NULL,'2026-10-29 03:39:08'),
(810,'visita','[]','catalogo',NULL,'2026-10-29 03:43:16'),
(811,'visita','[]','catalogo',NULL,'2026-10-29 03:52:29'),
(812,'visita','[]','catalogo',NULL,'2026-10-29 03:52:30'),
(813,'visita','[]','catalogo',NULL,'2026-10-29 03:53:03'),
(814,'visita','[]','carrito',NULL,'2026-10-29 03:53:04'),
(815,'visita','[]','perfil',NULL,'2026-10-29 03:53:18'),
(816,'visita','[]','catalogo',NULL,'2026-10-29 03:54:11'),
(817,'visita','[]','login',NULL,'2026-10-29 03:54:21'),
(818,'visita','[]','catalogo',NULL,'2026-10-29 03:54:22'),
(819,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:54:22'),
(820,'visita','[]','producto',NULL,'2026-10-29 03:54:22'),
(821,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 03:54:23'),
(822,'visita','[]','carrito',NULL,'2026-10-29 03:54:24'),
(823,'visita','[]','carrito',NULL,'2026-10-29 03:54:24'),
(824,'visita','[]','carrito',NULL,'2026-10-29 03:54:25'),
(825,'visita','[]','carrito',NULL,'2026-10-29 03:54:25'),
(826,'visita','[]','carrito',NULL,'2026-10-29 03:54:25'),
(827,'visita','[]','carrito',NULL,'2026-10-29 03:54:26'),
(828,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:54:36'),
(829,'visita','[]','mis-productos',NULL,'2026-10-29 03:54:37'),
(830,'visita','[]','login',NULL,'2026-10-29 03:54:43'),
(831,'visita','[]','catalogo',NULL,'2026-10-29 03:54:44'),
(832,'visita','[]','catalogo',NULL,'2026-10-29 03:54:44'),
(833,'visita','[]','carrito',NULL,'2026-10-29 03:54:46'),
(834,'visita','[]','mis-productos',NULL,'2026-10-29 03:54:58'),
(835,'visita','[]','mis-productos',NULL,'2026-10-29 03:54:59'),
(836,'visita','[]','mis-productos',NULL,'2026-10-29 03:55:11'),
(837,'visita','[]','mis-productos',NULL,'2026-10-29 03:55:13'),
(838,'visita','[]','login',NULL,'2026-10-29 03:55:18'),
(839,'visita','[]','catalogo',NULL,'2026-10-29 03:55:19'),
(840,'visita','[]','catalogo',NULL,'2026-10-29 03:55:19'),
(841,'visita','[]','carrito',NULL,'2026-10-29 03:55:21'),
(842,'visita','[]','mis-productos',NULL,'2026-10-29 03:55:22'),
(843,'visita','[]','mis-productos',NULL,'2026-10-29 03:55:23'),
(844,'visita','[]','login',NULL,'2026-10-29 03:55:43'),
(845,'visita','[]','catalogo',NULL,'2026-10-29 03:55:44'),
(846,'visita','[]','carrito',NULL,'2026-10-29 03:55:44'),
(847,'visita','[]','login',NULL,'2026-10-29 03:55:56'),
(848,'visita','[]','catalogo',NULL,'2026-10-29 03:55:57'),
(849,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:55:57'),
(850,'visita','[]','producto',NULL,'2026-10-29 03:55:57'),
(851,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 03:55:57'),
(852,'visita','[]','carrito',NULL,'2026-10-29 03:55:58'),
(853,'visita','[]','login',NULL,'2026-10-29 03:56:40'),
(854,'visita','[]','catalogo',NULL,'2026-10-29 03:56:40'),
(855,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:56:41'),
(856,'visita','[]','producto',NULL,'2026-10-29 03:56:41'),
(857,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 03:56:41'),
(858,'visita','[]','carrito',NULL,'2026-10-29 03:56:42'),
(859,'visita','[]','login',NULL,'2026-10-29 03:57:03'),
(860,'visita','[]','catalogo',NULL,'2026-10-29 03:57:04'),
(861,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:57:04'),
(862,'visita','[]','producto',NULL,'2026-10-29 03:57:04'),
(863,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 03:57:04'),
(864,'visita','[]','carrito',NULL,'2026-10-29 03:57:05'),
(865,'visita','[]','mis-productos',NULL,'2026-10-29 03:58:58'),
(866,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:59:00'),
(867,'visita','[]','catalogo',NULL,'2026-10-29 03:59:01'),
(868,'visita','[]','catalogo',NULL,'2026-10-29 03:59:05'),
(869,'visita','[]','login',NULL,'2026-10-29 03:59:07'),
(870,'visita','[]','catalogo',NULL,'2026-10-29 03:59:08'),
(871,'visita','[]','catalogo',NULL,'2026-10-29 03:59:09'),
(872,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 03:59:10'),
(873,'visita','[]','producto',NULL,'2026-10-29 03:59:10'),
(874,'visita','[]','perfil',NULL,'2026-10-29 03:59:11'),
(875,'visita','[]','carrito',NULL,'2026-10-29 03:59:11'),
(876,'visita','[]','favoritos',NULL,'2026-10-29 03:59:12'),
(877,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:59:13'),
(878,'visita','[]','perfil',NULL,'2026-10-29 03:59:14'),
(879,'visita','[]','mis-productos',NULL,'2026-10-29 03:59:14'),
(880,'visita','[]','mis-productos',NULL,'2026-10-29 03:59:15'),
(881,'visita','[]','notificaciones',NULL,'2026-10-29 03:59:16'),
(882,'visita','[]','direcciones',NULL,'2026-10-29 03:59:16'),
(883,'visita','[]','soporte',NULL,'2026-10-29 03:59:17'),
(884,'visita','[]','pagina',NULL,'2026-10-29 03:59:18'),
(885,'visita','[]','login',NULL,'2026-10-29 03:59:26'),
(886,'visita','[]','catalogo',NULL,'2026-10-29 03:59:27'),
(887,'visita','[]','catalogo',NULL,'2026-10-29 03:59:27'),
(888,'visita','[]','carrito',NULL,'2026-10-29 03:59:28'),
(889,'visita','[]','mis-pedidos',NULL,'2026-10-29 03:59:28'),
(890,'visita','[]','soporte',NULL,'2026-10-29 03:59:29'),
(891,'visita','[]','mis-productos',NULL,'2026-10-29 04:02:04'),
(892,'visita','[]','mis-productos',NULL,'2026-10-29 04:02:06'),
(893,'visita','[]','mis-productos',NULL,'2026-10-29 04:02:07'),
(894,'visita','[]','login',NULL,'2026-10-29 04:02:38'),
(895,'visita','[]','catalogo',NULL,'2026-10-29 04:02:39'),
(896,'visita','[]','catalogo',NULL,'2026-10-29 04:02:39'),
(897,'visita','[]','carrito',NULL,'2026-10-29 04:02:40'),
(898,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:02:40'),
(899,'visita','[]','producto',NULL,'2026-10-29 04:02:40'),
(900,'visita','[]','login',NULL,'2026-10-29 04:03:11'),
(901,'visita','[]','catalogo',NULL,'2026-10-29 04:03:12'),
(902,'visita','[]','catalogo',NULL,'2026-10-29 04:03:12'),
(903,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:03:14'),
(904,'visita','[]','producto',NULL,'2026-10-29 04:03:14'),
(905,'visita','[]','carrito',NULL,'2026-10-29 04:03:15'),
(906,'visita','[]','favoritos',NULL,'2026-10-29 04:03:15'),
(907,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:03:16'),
(908,'visita','[]','perfil',NULL,'2026-10-29 04:03:17'),
(909,'visita','[]','mis-productos',NULL,'2026-10-29 04:03:18'),
(910,'visita','[]','notificaciones',NULL,'2026-10-29 04:03:18'),
(911,'visita','[]','direcciones',NULL,'2026-10-29 04:03:19'),
(912,'visita','[]','soporte',NULL,'2026-10-29 04:03:20'),
(913,'visita','[]','pagina',NULL,'2026-10-29 04:03:21'),
(914,'visita','[]','login',NULL,'2026-10-29 04:03:27'),
(915,'visita','[]','catalogo',NULL,'2026-10-29 04:03:28'),
(916,'visita','[]','catalogo',NULL,'2026-10-29 04:03:28'),
(917,'visita','[]','carrito',NULL,'2026-10-29 04:03:29'),
(918,'visita','[]','pagina',NULL,'2026-10-29 04:03:29'),
(919,'visita','[]','pagina',NULL,'2026-10-29 04:03:30'),
(920,'visita','[]','pagina',NULL,'2026-10-29 04:03:30'),
(921,'visita','[]','pagina',NULL,'2026-10-29 04:03:31'),
(922,'visita','[]','pagina',NULL,'2026-10-29 04:03:31'),
(923,'visita','[]','soporte',NULL,'2026-10-29 04:04:00'),
(924,'visita','[]','soporte',NULL,'2026-10-29 04:04:02'),
(925,'visita','[]','perfil',NULL,'2026-10-29 04:04:04'),
(926,'visita','[]','perfil',NULL,'2026-10-29 04:04:07'),
(927,'visita','[]','perfil',NULL,'2026-10-29 04:04:13'),
(928,'visita','[]','catalogo',NULL,'2026-10-29 04:04:28'),
(929,'visita','[]','carrito',NULL,'2026-10-29 04:04:28'),
(930,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:04:30'),
(931,'visita','[]','perfil',NULL,'2026-10-29 04:04:35'),
(932,'visita','[]','carrito',NULL,'2026-10-29 04:04:37'),
(933,'visita','[]','mis-productos',NULL,'2026-10-29 04:04:41'),
(934,'visita','[]','login',NULL,'2026-10-29 04:05:02'),
(935,'visita','[]','catalogo',NULL,'2026-10-29 04:05:03'),
(936,'visita','[]','catalogo',NULL,'2026-10-29 04:05:04'),
(937,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:05:05'),
(938,'visita','[]','producto',NULL,'2026-10-29 04:05:05'),
(939,'visita','[]','carrito',NULL,'2026-10-29 04:05:06'),
(940,'visita','[]','favoritos',NULL,'2026-10-29 04:05:07'),
(941,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:05:08'),
(942,'visita','[]','perfil',NULL,'2026-10-29 04:05:09'),
(943,'visita','[]','mis-productos',NULL,'2026-10-29 04:05:10'),
(944,'visita','[]','notificaciones',NULL,'2026-10-29 04:05:10'),
(945,'visita','[]','direcciones',NULL,'2026-10-29 04:05:11'),
(946,'visita','[]','soporte',NULL,'2026-10-29 04:05:12'),
(947,'visita','[]','pagina',NULL,'2026-10-29 04:05:13'),
(948,'visita','[]','catalogo',NULL,'2026-10-29 04:05:27'),
(949,'visita','[]','login',NULL,'2026-10-29 04:05:40'),
(950,'visita','[]','catalogo',NULL,'2026-10-29 04:05:41'),
(951,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:05:41'),
(952,'visita','[]','producto',NULL,'2026-10-29 04:05:41'),
(953,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:05:42'),
(954,'visita','[]','carrito',NULL,'2026-10-29 04:05:43'),
(955,'visita','[]','login',NULL,'2026-10-29 04:05:53'),
(956,'visita','[]','catalogo',NULL,'2026-10-29 04:05:54'),
(957,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:05:54'),
(958,'visita','[]','producto',NULL,'2026-10-29 04:05:54'),
(959,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:05:55'),
(960,'visita','[]','carrito',NULL,'2026-10-29 04:05:56'),
(961,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:06:29'),
(962,'visita','[]','producto',NULL,'2026-10-29 04:06:29'),
(963,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:06:30'),
(964,'visita','[]','carrito',NULL,'2026-10-29 04:06:31'),
(965,'visita','[]','carrito',NULL,'2026-10-29 04:06:31'),
(966,'visita','[]','login',NULL,'2026-10-29 04:06:32'),
(967,'visita','[]','catalogo',NULL,'2026-10-29 04:06:33'),
(968,'visita','[]','perfil',NULL,'2026-10-29 04:06:33'),
(969,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:06:34'),
(970,'visita','[]','producto',NULL,'2026-10-29 04:06:34'),
(971,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:06:34'),
(972,'visita','[]','carrito',NULL,'2026-10-29 04:06:35'),
(973,'visita','[]','carrito',NULL,'2026-10-29 04:06:36'),
(974,'visita','[]','login',NULL,'2026-10-29 04:07:05'),
(975,'visita','[]','catalogo',NULL,'2026-10-29 04:07:06'),
(976,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:07:06'),
(977,'visita','[]','producto',NULL,'2026-10-29 04:07:06'),
(978,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:07:06'),
(979,'visita','[]','carrito',NULL,'2026-10-29 04:07:07'),
(980,'visita','[]','carrito',NULL,'2026-10-29 04:07:08'),
(981,'visita','[]','login',NULL,'2026-10-29 04:07:27'),
(982,'visita','[]','catalogo',NULL,'2026-10-29 04:07:28'),
(983,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:07:28'),
(984,'visita','[]','producto',NULL,'2026-10-29 04:07:28'),
(985,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:07:28'),
(986,'visita','[]','carrito',NULL,'2026-10-29 04:07:29'),
(987,'compra','{\"pedido_id\":12}','checkout',NULL,'2026-10-29 04:07:31'),
(988,'visita','[]','login',NULL,'2026-10-29 04:08:20'),
(989,'visita','[]','catalogo',NULL,'2026-10-29 04:08:21'),
(990,'visita','[]','catalogo',NULL,'2026-10-29 04:08:21'),
(991,'visita','[]','login',NULL,'2026-10-29 04:08:27'),
(992,'visita','[]','catalogo',NULL,'2026-10-29 04:08:28'),
(993,'visita','[]','catalogo',NULL,'2026-10-29 04:08:28'),
(994,'visita','[]','perfil',NULL,'2026-10-29 04:08:29'),
(995,'visita','[]','login',NULL,'2026-10-29 04:08:38'),
(996,'visita','[]','catalogo',NULL,'2026-10-29 04:08:39'),
(997,'visita','[]','catalogo',NULL,'2026-10-29 04:08:40'),
(998,'visita','[]','catalogo',NULL,'2026-10-29 04:08:40'),
(999,'visita','[]','catalogo',NULL,'2026-10-29 04:08:41'),
(1000,'visita','[]','catalogo',NULL,'2026-10-29 04:08:42'),
(1001,'visita','[]','carrito',NULL,'2026-10-29 04:08:42'),
(1002,'visita','[]','pagina',NULL,'2026-10-29 04:08:43'),
(1003,'visita','[]','catalogo',NULL,'2026-10-29 04:08:44'),
(1004,'visita','[]','perfil',NULL,'2026-10-29 04:08:47'),
(1005,'visita','[]','soporte',NULL,'2026-10-29 04:08:54'),
(1006,'visita','[]','favoritos',NULL,'2026-10-29 04:08:56'),
(1007,'visita','[]','login',NULL,'2026-10-29 04:09:02'),
(1008,'visita','[]','catalogo',NULL,'2026-10-29 04:09:03'),
(1009,'visita','[]','catalogo',NULL,'2026-10-29 04:09:03'),
(1010,'visita','[]','catalogo',NULL,'2026-10-29 04:09:04'),
(1011,'visita','[]','catalogo',NULL,'2026-10-29 04:09:04'),
(1012,'visita','[]','catalogo',NULL,'2026-10-29 04:09:05'),
(1013,'visita','[]','carrito',NULL,'2026-10-29 04:09:06'),
(1014,'visita','[]','pagina',NULL,'2026-10-29 04:09:06'),
(1015,'visita','[]','catalogo',NULL,'2026-10-29 04:09:07'),
(1016,'visita','[]','favoritos',NULL,'2026-10-29 04:09:07'),
(1017,'visita','[]','catalogo',NULL,'2026-10-29 04:09:18'),
(1018,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:09:19'),
(1019,'visita','[]','catalogo',NULL,'2026-10-29 04:09:31'),
(1020,'visita','[]','perfil',NULL,'2026-10-29 04:09:41'),
(1021,'visita','[]','login',NULL,'2026-10-29 04:09:44'),
(1022,'visita','[]','catalogo',NULL,'2026-10-29 04:09:45'),
(1023,'visita','[]','catalogo',NULL,'2026-10-29 04:09:45'),
(1024,'visita','[]','carrito',NULL,'2026-10-29 04:09:46'),
(1025,'visita','[]','pagina',NULL,'2026-10-29 04:09:47'),
(1026,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:09:48'),
(1027,'visita','[]','catalogo',NULL,'2026-10-29 04:09:49'),
(1028,'visita','[]','login',NULL,'2026-10-29 04:10:11'),
(1029,'visita','[]','catalogo',NULL,'2026-10-29 04:10:13'),
(1030,'visita','[]','catalogo',NULL,'2026-10-29 04:10:13'),
(1031,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:10:16'),
(1032,'visita','[]','producto',NULL,'2026-10-29 04:10:16'),
(1033,'visita','[]','carrito',NULL,'2026-10-29 04:10:17'),
(1034,'visita','[]','favoritos',NULL,'2026-10-29 04:10:18'),
(1035,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:10:19'),
(1036,'visita','[]','perfil',NULL,'2026-10-29 04:10:20'),
(1037,'visita','[]','mis-productos',NULL,'2026-10-29 04:10:21'),
(1038,'visita','[]','notificaciones',NULL,'2026-10-29 04:10:22'),
(1039,'visita','[]','direcciones',NULL,'2026-10-29 04:10:23'),
(1040,'visita','[]','soporte',NULL,'2026-10-29 04:10:23'),
(1041,'visita','[]','pagina',NULL,'2026-10-29 04:10:24'),
(1042,'visita','[]','login',NULL,'2026-10-29 04:14:20'),
(1043,'visita','[]','catalogo',NULL,'2026-10-29 04:14:21'),
(1044,'visita','[]','direcciones',NULL,'2026-10-29 04:14:21'),
(1045,'visita','[]','soporte',NULL,'2026-10-29 04:14:22'),
(1046,'visita','[]','login',NULL,'2026-10-29 04:15:03'),
(1047,'visita','[]','catalogo',NULL,'2026-10-29 04:15:04'),
(1048,'visita','[]','direcciones',NULL,'2026-10-29 04:15:04'),
(1049,'visita','[]','soporte',NULL,'2026-10-29 04:15:05'),
(1050,'visita','[]','login',NULL,'2026-10-29 04:15:29'),
(1051,'visita','[]','catalogo',NULL,'2026-10-29 04:15:30'),
(1052,'visita','[]','direcciones',NULL,'2026-10-29 04:15:30'),
(1053,'visita','[]','login',NULL,'2026-10-29 04:16:02'),
(1054,'visita','[]','login',NULL,'2026-10-29 04:17:07'),
(1055,'visita','[]','catalogo',NULL,'2026-10-29 04:17:08'),
(1056,'visita','[]','direcciones',NULL,'2026-10-29 04:17:08'),
(1057,'visita','[]','soporte',NULL,'2026-10-29 04:17:09'),
(1058,'visita','[]','login',NULL,'2026-10-29 04:17:19'),
(1059,'visita','[]','catalogo',NULL,'2026-10-29 04:17:20'),
(1060,'visita','[]','direcciones',NULL,'2026-10-29 04:17:20'),
(1061,'visita','[]','login',NULL,'2026-10-29 04:18:10'),
(1062,'visita','[]','catalogo',NULL,'2026-10-29 04:18:11'),
(1063,'visita','[]','direcciones',NULL,'2026-10-29 04:18:11'),
(1064,'visita','[]','soporte',NULL,'2026-10-29 04:18:12'),
(1065,'visita','[]','login',NULL,'2026-10-29 04:18:18'),
(1066,'visita','[]','catalogo',NULL,'2026-10-29 04:18:19'),
(1067,'visita','[]','catalogo',NULL,'2026-10-29 04:18:20'),
(1068,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:18:21'),
(1069,'visita','[]','producto',NULL,'2026-10-29 04:18:21'),
(1070,'visita','[]','carrito',NULL,'2026-10-29 04:18:22'),
(1071,'visita','[]','favoritos',NULL,'2026-10-29 04:18:23'),
(1072,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:18:24'),
(1073,'visita','[]','perfil',NULL,'2026-10-29 04:18:25'),
(1074,'visita','[]','mis-productos',NULL,'2026-10-29 04:18:25'),
(1075,'visita','[]','notificaciones',NULL,'2026-10-29 04:18:26'),
(1076,'visita','[]','direcciones',NULL,'2026-10-29 04:18:27'),
(1077,'visita','[]','soporte',NULL,'2026-10-29 04:18:28'),
(1078,'visita','[]','pagina',NULL,'2026-10-29 04:18:29'),
(1079,'visita','[]','login',NULL,'2026-10-29 04:18:55'),
(1080,'visita','[]','catalogo',NULL,'2026-10-29 04:18:56'),
(1081,'visita','[]','direcciones',NULL,'2026-10-29 04:18:56'),
(1082,'visita','[]','login',NULL,'2026-10-29 04:18:57'),
(1083,'visita','[]','catalogo',NULL,'2026-10-29 04:18:58'),
(1084,'visita','[]','direcciones',NULL,'2026-10-29 04:18:58'),
(1085,'visita','[]','login',NULL,'2026-10-29 04:19:22'),
(1086,'visita','[]','catalogo',NULL,'2026-10-29 04:19:23'),
(1087,'visita','[]','direcciones',NULL,'2026-10-29 04:19:23'),
(1088,'visita','[]','login',NULL,'2026-10-29 04:19:41'),
(1089,'visita','[]','catalogo',NULL,'2026-10-29 04:19:42'),
(1090,'visita','[]','direcciones',NULL,'2026-10-29 04:19:42'),
(1091,'visita','[]','login',NULL,'2026-10-29 04:19:54'),
(1092,'visita','[]','catalogo',NULL,'2026-10-29 04:19:55'),
(1093,'visita','[]','direcciones',NULL,'2026-10-29 04:19:55'),
(1094,'visita','[]','login',NULL,'2026-10-29 04:20:17'),
(1095,'visita','[]','catalogo',NULL,'2026-10-29 04:20:18'),
(1096,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:20:18'),
(1097,'visita','[]','producto',NULL,'2026-10-29 04:20:18'),
(1098,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:20:18'),
(1099,'visita','[]','carrito',NULL,'2026-10-29 04:20:19'),
(1100,'visita','[]','login',NULL,'2026-10-29 04:20:34'),
(1101,'visita','[]','catalogo',NULL,'2026-10-29 04:20:35'),
(1102,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:20:36'),
(1103,'visita','[]','producto',NULL,'2026-10-29 04:20:36'),
(1104,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:20:36'),
(1105,'visita','[]','carrito',NULL,'2026-10-29 04:20:37'),
(1106,'visita','[]','login',NULL,'2026-10-29 04:20:39'),
(1107,'visita','[]','catalogo',NULL,'2026-10-29 04:20:40'),
(1108,'visita','[]','direcciones',NULL,'2026-10-29 04:20:41'),
(1109,'visita','[]','login',NULL,'2026-10-29 04:21:01'),
(1110,'visita','[]','catalogo',NULL,'2026-10-29 04:21:02'),
(1111,'visita','[]','direcciones',NULL,'2026-10-29 04:21:02'),
(1112,'visita','[]','login',NULL,'2026-10-29 04:21:19'),
(1113,'visita','[]','catalogo',NULL,'2026-10-29 04:21:20'),
(1114,'visita','[]','direcciones',NULL,'2026-10-29 04:21:20'),
(1115,'visita','[]','login',NULL,'2026-10-29 04:21:38'),
(1116,'visita','[]','catalogo',NULL,'2026-10-29 04:21:39'),
(1117,'visita','[]','catalogo',NULL,'2026-10-29 04:21:39'),
(1118,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:21:41'),
(1119,'visita','[]','producto',NULL,'2026-10-29 04:21:41'),
(1120,'visita','[]','carrito',NULL,'2026-10-29 04:21:42'),
(1121,'visita','[]','favoritos',NULL,'2026-10-29 04:21:43'),
(1122,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:21:43'),
(1123,'visita','[]','perfil',NULL,'2026-10-29 04:21:45'),
(1124,'visita','[]','mis-productos',NULL,'2026-10-29 04:21:45'),
(1125,'visita','[]','notificaciones',NULL,'2026-10-29 04:21:46'),
(1126,'visita','[]','direcciones',NULL,'2026-10-29 04:21:47'),
(1127,'visita','[]','soporte',NULL,'2026-10-29 04:21:48'),
(1128,'visita','[]','pagina',NULL,'2026-10-29 04:21:48'),
(1129,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:26:01'),
(1130,'visita','[]','producto',NULL,'2026-10-29 04:26:01'),
(1131,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:26:01'),
(1132,'visita','[]','carrito',NULL,'2026-10-29 04:26:02'),
(1133,'visita','[]','carrito',NULL,'2026-10-29 04:26:03'),
(1134,'visita','[]','login',NULL,'2026-10-29 04:26:04'),
(1135,'visita','[]','catalogo',NULL,'2026-10-29 04:26:05'),
(1136,'visita','[]','perfil',NULL,'2026-10-29 04:26:05'),
(1137,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:26:05'),
(1138,'visita','[]','producto',NULL,'2026-10-29 04:26:05'),
(1139,'agregar_carrito','{\"producto_id\":13}','ficha',NULL,'2026-10-29 04:26:05'),
(1140,'visita','[]','carrito',NULL,'2026-10-29 04:26:06'),
(1141,'visita','[]','carrito',NULL,'2026-10-29 04:26:07'),
(1142,'visita','[]','catalogo',NULL,'2026-10-29 04:26:26'),
(1143,'visita','[]','login',NULL,'2026-10-29 04:27:20'),
(1144,'visita','[]','login',NULL,'2026-10-29 04:28:03'),
(1145,'visita','[]','catalogo',NULL,'2026-10-29 04:28:05'),
(1146,'visita','[]','catalogo',NULL,'2026-10-29 04:28:05'),
(1147,'producto_visto','{\"producto_id\":13}','producto',NULL,'2026-10-29 04:28:06'),
(1148,'visita','[]','producto',NULL,'2026-10-29 04:28:06'),
(1149,'visita','[]','carrito',NULL,'2026-10-29 04:28:07'),
(1150,'visita','[]','favoritos',NULL,'2026-10-29 04:28:08'),
(1151,'visita','[]','mis-pedidos',NULL,'2026-10-29 04:28:09'),
(1152,'visita','[]','perfil',NULL,'2026-10-29 04:28:10'),
(1153,'visita','[]','mis-productos',NULL,'2026-10-29 04:28:11'),
(1154,'visita','[]','notificaciones',NULL,'2026-10-29 04:28:12'),
(1155,'visita','[]','direcciones',NULL,'2026-10-29 04:28:12'),
(1156,'visita','[]','soporte',NULL,'2026-10-29 04:28:13'),
(1157,'visita','[]','pagina',NULL,'2026-10-29 04:28:14'),
(1158,'visita','[]','perfil',NULL,'2026-10-29 04:32:05'),
(1159,'visita','[]','carrito',NULL,'2026-10-29 04:32:21'),
(1160,'visita','[]','catalogo',NULL,'2026-10-29 04:32:25'),
(1161,'agregar_carrito','{\"producto_id\":3}','ficha',NULL,'2026-10-29 04:32:27'),
(1162,'visita','[]','carrito',NULL,'2026-10-29 04:32:28'),
(1163,'visita','[]','catalogo',NULL,'2026-10-29 04:32:39'),
(1164,'visita','[]','perfil',NULL,'2026-10-29 04:37:57'),
(1165,'visita','[]','favoritos',NULL,'2026-10-29 04:38:14'),
(1166,'visita','[]','perfil',NULL,'2026-10-29 04:38:42'),
(1167,'visita','[]','catalogo',NULL,'2026-10-29 04:46:15'),
(1168,'visita','[]','catalogo',NULL,'2026-10-29 04:46:33'),
(1169,'visita','[]','catalogo',NULL,'2026-10-29 04:46:51'),
(1170,'visita','[]','catalogo',NULL,'2026-10-29 04:47:00'),
(1171,'visita','[]','catalogo',NULL,'2026-10-29 04:47:02'),
(1172,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:50:37'),
(1173,'visita','[]','producto',NULL,'2026-10-29 04:50:37'),
(1174,'visita','[]','catalogo',NULL,'2026-10-29 04:50:47'),
(1175,'producto_visto','{\"producto_id\":3}','producto',NULL,'2026-10-29 04:50:49'),
(1176,'visita','[]','producto',NULL,'2026-10-29 04:50:49'),
(1177,'visita','[]','catalogo',NULL,'2026-10-29 04:50:53'),
(1178,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:50:54'),
(1179,'visita','[]','producto',NULL,'2026-10-29 04:50:54'),
(1180,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:51:00'),
(1181,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:51:02'),
(1182,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:51:04'),
(1183,'visita','[]','carrito',NULL,'2026-10-29 04:51:05'),
(1184,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:51:13'),
(1185,'visita','[]','producto',NULL,'2026-10-29 04:51:13'),
(1186,'visita','[]','catalogo',NULL,'2026-10-29 04:51:14'),
(1187,'visita','[]','carrito',NULL,'2026-10-29 04:51:46'),
(1188,'visita','[]','catalogo',NULL,'2026-10-29 04:51:50'),
(1189,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:51:51'),
(1190,'visita','[]','producto',NULL,'2026-10-29 04:51:51'),
(1191,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:51:54'),
(1192,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:51:55'),
(1193,'visita','[]','carrito',NULL,'2026-10-29 04:51:57'),
(1194,'visita','[]','carrito',NULL,'2026-10-29 04:52:06'),
(1195,'visita','[]','carrito',NULL,'2026-10-29 04:52:07'),
(1196,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:52:08'),
(1197,'visita','[]','producto',NULL,'2026-10-29 04:52:08'),
(1198,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:52:09'),
(1199,'visita','[]','carrito',NULL,'2026-10-29 04:52:11'),
(1200,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:52:12'),
(1201,'visita','[]','producto',NULL,'2026-10-29 04:52:12'),
(1202,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:52:14'),
(1203,'visita','[]','carrito',NULL,'2026-10-29 04:52:15'),
(1204,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:52:17'),
(1205,'visita','[]','producto',NULL,'2026-10-29 04:52:17'),
(1206,'agregar_carrito','{\"producto_id\":1}','ficha',NULL,'2026-10-29 04:52:20'),
(1207,'visita','[]','carrito',NULL,'2026-10-29 04:52:21'),
(1208,'producto_visto','{\"producto_id\":1}','producto',NULL,'2026-10-29 04:52:39'),
(1209,'visita','[]','producto',NULL,'2026-10-29 04:52:39'),
(1210,'visita','[]','catalogo',NULL,'2026-10-29 04:52:40');
/*!40000 ALTER TABLE `eventos_analitica` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `favoritos`
--

DROP TABLE IF EXISTS `favoritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `producto_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorito_unico` (`cliente_id`,`producto_id`),
  KEY `fk_fav_producto` (`producto_id`),
  CONSTRAINT `fk_fav_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_fav_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favoritos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `favoritos` WRITE;
/*!40000 ALTER TABLE `favoritos` DISABLE KEYS */;
INSERT INTO `favoritos` VALUES
(2,3,'2026-10-27 01:39:37',3),
(4,1,'2026-10-28 01:15:07',10),
(5,1,'2026-10-28 01:15:08',7);
/*!40000 ALTER TABLE `favoritos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `garantias`
--

DROP TABLE IF EXISTS `garantias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `garantias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `duracion_meses` int(11) NOT NULL DEFAULT 6,
  `cobertura` varchar(500) NOT NULL DEFAULT '',
  `precio_pen` decimal(10,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_garantias_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `garantias`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `garantias` WRITE;
/*!40000 ALTER TABLE `garantias` DISABLE KEYS */;
INSERT INTO `garantias` VALUES
(1,'Garantia del fabricante 6 meses',6,'Defectos de fabricacion y componentes',0.00,1,'2026-10-27 01:38:38'),
(2,'Garantia extendida 12 meses',12,'Cobertura ampliada de fabricacion',49.90,1,'2026-10-27 01:38:38'),
(3,'Garantia premium 24 meses',24,'Cobertura total incluyendo pantalla y bateria',149.90,1,'2026-10-27 01:38:38'),
(4,'Garantia importacion 3 meses',3,'Solo defectos de funcionamiento',0.00,1,'2026-10-27 01:38:38');
/*!40000 ALTER TABLE `garantias` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `intentos_acceso`
--

DROP TABLE IF EXISTS `intentos_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intentos_acceso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ambito` varchar(40) NOT NULL,
  `clave` varchar(190) NOT NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `intentos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` timestamp NULL DEFAULT NULL,
  `ultimo_intento` timestamp NULL DEFAULT current_timestamp(),
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_intento_ambito_clave` (`ambito`,`clave`),
  KEY `idx_intento_bloqueo` (`bloqueado_hasta`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intentos_acceso`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `intentos_acceso` WRITE;
/*!40000 ALTER TABLE `intentos_acceso` DISABLE KEYS */;
INSERT INTO `intentos_acceso` VALUES
(1,'cliente_recuperar','cliente2@example.com','127.0.0.1',4,NULL,'2026-10-28 01:52:34','2026-10-28 01:30:27'),
(4,'cliente_recuperar','admin@example.com','127.0.0.1',1,NULL,'2026-10-28 01:52:47','2026-10-28 01:31:43'),
(37,'cliente_pregunta_solicitud','admin@example.com','127.0.0.1',1,NULL,'2026-10-28 01:41:02','2026-10-28 01:41:02'),
(38,'cliente_pregunta_solicitud','vendedor@example.com','127.0.0.1',1,NULL,'2026-10-28 01:41:05','2026-10-28 01:41:05'),
(41,'cliente_pregunta_solicitud','cliente2@example.com','127.0.0.1',1,NULL,'2026-10-28 01:52:29','2026-10-28 01:52:29'),
(44,'cliente_login','jeremytech','127.0.0.1',1,NULL,'2026-10-28 03:59:25','2026-10-28 03:59:25'),
(45,'admin_login','admin','127.0.0.1',2,NULL,'2026-10-28 05:35:24','2026-10-28 05:35:08'),
(47,'chat','ip:127.0.0.1','127.0.0.1',1,NULL,'2026-10-29 02:10:40','2026-10-29 02:10:40');
/*!40000 ALTER TABLE `intentos_acceso` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `marcas`
--

DROP TABLE IF EXISTS `marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marcas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL DEFAULT '',
  `logo_url` varchar(255) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marcas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `marcas` WRITE;
/*!40000 ALTER TABLE `marcas` DISABLE KEYS */;
INSERT INTO `marcas` VALUES
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
/*!40000 ALTER TABLE `marcas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(180) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `producto_id` bigint(20) unsigned NOT NULL,
  `variante_id` int(11) DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mov_producto` (`producto_id`),
  KEY `fk_mov_variante` (`variante_id`),
  KEY `idx_mov_pedido` (`pedido_id`),
  KEY `idx_mov_creado` (`creado_en`),
  CONSTRAINT `fk_mov_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_mov_variante` FOREIGN KEY (`variante_id`) REFERENCES `producto_variantes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_inventario`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `movimientos_inventario` WRITE;
/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
INSERT INTO `movimientos_inventario` VALUES
(14,'salida',1,'Venta del pedido #4 · HP Pavilion 15','2026-10-28 02:01:18',10,NULL,4),
(15,'salida',1,'Venta del pedido #4 · Lenovo ThinkPad X1 Carbon','2026-10-28 02:01:18',9,NULL,4),
(19,'salida',1,'Venta del pedido #8 · iPhone 15 Pro','2026-10-28 03:09:44',1,NULL,8),
(20,'salida',3,'Venta del pedido #8 · Samsung Galaxy S24 Ultra','2026-10-28 03:09:44',3,NULL,8),
(21,'salida',3,'Venta del pedido #8 · MacBook Air M3 (16 GB · 512 GB)','2026-10-28 03:09:44',7,11,8),
(22,'salida',1,'Venta del pedido #9 · Apple AirPods Pro 2','2026-10-28 03:11:44',12,NULL,9),
(27,'entrada',1,'Cancelación del pedido #9 · Apple AirPods Pro 2','2026-10-28 05:00:39',12,NULL,9),
(28,'salida',2,'Venta del pedido #10 · Bose QuietComfort Ultra','2026-10-28 05:51:24',16,NULL,10),
(29,'salida',1,'Venta del pedido #10 · iPhone 15 Pro','2026-10-28 05:51:24',1,NULL,10),
(30,'salida',3,'Venta del pedido #10 · Samsung Galaxy S24 Ultra','2026-10-28 05:51:24',3,NULL,10),
(31,'salida',5,'Venta del pedido #11 · Apple AirPods Pro 2','2026-10-28 05:54:14',12,NULL,11),
(32,'salida',1,'Venta del pedido #12 · Sony WH-1000XM5','2026-10-29 04:07:31',13,NULL,12);
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `destinatario` varchar(190) NOT NULL DEFAULT '',
  `tipo` varchar(60) NOT NULL,
  `asunto` varchar(180) NOT NULL,
  `cuerpo` varchar(500) NOT NULL DEFAULT '',
  `archivo` varchar(255) NOT NULL DEFAULT '',
  `error` varchar(255) NOT NULL DEFAULT '',
  `enviado` tinyint(1) NOT NULL DEFAULT 0,
  `enviado_en` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notif_cliente` (`cliente_id`),
  KEY `idx_notif_pedido` (`pedido_id`),
  CONSTRAINT `fk_notif_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
INSERT INTO `notificaciones` VALUES
(13,1,NULL,'admin@example.com','recuperacion_cliente','Recuperación de contraseña — Jeremy Tech','JEREMY-TECH\nRecuperación de contraseña\n\nHola Jeremy.\nRecibimos una solicitud para restablecer la contraseña de tu cuenta.\n\nAbre este enlace para elegir una nueva contraseña:\nhttp://127.0.0.1:8099/index.php?ruta=login/restablecer&token=0000000000000000000000000000000000000000000000000000000000000000\n\nEl enlace vence en 30 minutos y solo puede usarse una vez.\nSi no solicitaste este cambio, ignora este mensaje.\n\nEste mensaje fue generado en modo de demostración. No se envió por correo.\n','/dev/shm/jeremy-tech-mail/2026/10/20261027_203143_recuperacion_cliente_pedido-0_0cf0c7a7.eml','',0,NULL,'2026-10-28 01:31:43'),
(18,1,NULL,'admin@example.com','recuperacion_cliente','Recuperación de contraseña — Jeremy Tech','Solicitud de recuperación de contraseña. El enlace vence en 30 minutos y solo puede usarse una vez.','/dev/shm/jeremy-tech-mail/2026/10/20261027_205247_recuperacion_cliente_pedido-0_6a02fdd4.eml','',0,NULL,'2026-10-28 01:52:47'),
(19,3,4,'cliente@example.com','pago_confirmado','Pago confirmado — Pedido #JT-00004','JEREMY-TECH\nPago confirmado — #JT-00004\n\nHola Cliente Demo.\nRecibimos el pago de tu pedido. Ya estamos preparándolo.\nMétodo de pago: Tarjeta\nEstado: Pagado\nReferencia: SIM-CARD-20261027210118-4741\n\nProductos:\n- 1x HP Pavilion 15\n- 1x Lenovo ThinkPad X1 Carbon\n\nTotal: S/ 13,806.00\nEntrega: Entrega — Calle Demo 456\n\nVer seguimiento: http://localhost/JEREMY-TECH/public/index.php?ruta=carrito#historial\n\nEste mensaje fue generado en modo de demostración. No se envió por correo.','','No se pudo crear el directorio de correos simulados.',0,NULL,'2026-10-28 02:01:18'),
(23,11,8,'cliente2@example.com','pago_confirmado','Pago confirmado — Pedido #JT-00008','JEREMY-TECH\nPago confirmado — #JT-00008\n\nHola Cliente Demo.\nRecibimos el pago de tu pedido. Ya estamos preparándolo.\nMétodo de pago: Yape\nEstado: Pagado\nReferencia: SIM-YAPE-20261027220944-9459\n\nProductos:\n- 1x iPhone 15 Pro\n- 3x Samsung Galaxy S24 Ultra\n- 3x MacBook Air M3 (16 GB · 512 GB)\n\nTotal: S/ 64,001.00\nEntrega: Principal — Av. Demo 123\n\nVer seguimiento: http://localhost/JEREMY-TECH/public/index.php?ruta=carrito#historial\n\nEste mensaje fue generado en modo de demostración.','','No se pudo crear el directorio de correos simulados.',0,NULL,'2026-10-28 03:09:44'),
(24,11,9,'cliente2@example.com','pago_confirmado','Pago confirmado — Pedido #JT-00009','JEREMY-TECH\nPago confirmado — #JT-00009\n\nHola Cliente Demo.\nRecibimos el pago de tu pedido. Ya estamos preparándolo.\nMétodo de pago: Yape\nEstado: Pagado\nReferencia: SIM-YAPE-20261027221144-6688\n\nProductos:\n- 1x Apple AirPods Pro 2\n\nTotal: S/ 1,907.00\nEntrega: Principal — Av. Demo 123\n\nVer seguimiento: http://localhost/JEREMY-TECH/public/index.php?ruta=carrito#historial\n\nEste mensaje fue generado en modo de demostración. No se envió por correo.','','No se pudo crear el directorio de correos simulados.',0,NULL,'2026-10-28 03:11:45'),
(25,11,9,'cliente2@example.com','reembolso','Reembolso procesado — Pedido #JT-00009','JEREMY-TECH\nReembolso procesado — #JT-00009\n\nHola Cliente Demo.\nProcesamos el reembolso de tu pedido. El monto se verá reflejado según el medio de pago utilizado.\nMétodo de pago: Yape\nEstado del pago: Reembolsado\nReferencia: SIM-YAPE-20261027221144-6688\n\nProductos:\n- 1x Apple AirPods Pro 2\n\nTotal: S/ 1,907.00\nEntrega: Principal — Av. Demo 123\n\nVer seguimiento: http://localhost/JEREMY-TECH/public/index.php?ruta=carrito#historial\n\nEste mensaje fue generado en modo de demostración. No se envió','','No se pudo crear el directorio de correos simulados.',0,NULL,'2026-10-28 05:00:39'),
(26,11,8,'cliente2@example.com','soporte_respuesta','Respuesta a tu ticket #ST-00002 — Jeremy Tech','Nuestro equipo respondió tu ticket \"reembolso\". Ingresa a Mis tickets para leer la respuesta completa.','','No se pudo crear el directorio de correos simulados.',0,NULL,'2026-10-28 05:02:29'),
(27,11,10,'cliente2@example.com','pago_confirmado','Pago confirmado — Pedido #JT-00010','JEREMY-TECH\nPago confirmado — #JT-00010\n\nHola Cliente Demo.\nRecibimos el pago de tu pedido. Ya estamos preparándolo.\nMétodo de pago: Yape\nEstado: Pagado\nReferencia: SIM-YAPE-20261028005124-2991\n\nProductos:\n- 2x Bose QuietComfort Ultra\n- 1x iPhone 15 Pro\n- 3x Samsung Galaxy S24 Ultra\n\nTotal: S/ 34,602.00\nEntrega: Principal — Av. Demo 123\n\nVer seguimiento: http://localhost/JEREMY-TECH/public/index.php?ruta=carrito#historial\n\nEste mensaje fue generado en modo de demostración. No se','','No se pudo crear el directorio de correos simulados.',0,NULL,'2026-10-28 05:51:24'),
(28,3,12,'cliente@example.com','pago_confirmado','Pago confirmado — Pedido #JT-00012','JEREMY-TECH\nPago confirmado — #JT-00012\n\nHola Cliente Demo.\nRecibimos el pago de tu pedido. Ya estamos preparándolo.\nMétodo de pago: Tarjeta\nEstado: Pagado\nReferencia: SIM-CARD-20261028230731-7571\n\nProductos:\n- 1x Sony WH-1000XM5\n\nTotal: S/ 2,507.00\nEntrega: Entrega — Calle Demo 456\n\nVer seguimiento: http://127.0.0.1:8099/index.php?ruta=carrito#historial\n\nEste mensaje fue generado en modo de demostración. No se envió por correo.','/dev/shm/jeremy-tech-mail/2026/10/20261028_230731_pago_confirmado_pedido-12_4763c418.eml','',0,NULL,'2026-10-29 04:07:31');
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `monto_pen` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo` varchar(40) NOT NULL DEFAULT 'Tarjeta',
  `transaccion_id` varchar(60) NOT NULL DEFAULT '',
  `pasarela` varchar(40) NOT NULL DEFAULT 'simulada',
  `referencia_externa` varchar(120) NOT NULL DEFAULT '',
  `estado` varchar(30) NOT NULL DEFAULT '',
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pagos_referencia` (`referencia_externa`),
  KEY `idx_pagos_transaccion` (`transaccion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES
(1,1,7499.00,'Tarjeta','TEST-123','simulada','','Pagado','2026-10-28 02:08:07','2026-10-27 01:42:49'),
(2,4,13806.00,'tarjeta','SIM-CARD-20261027210118-4741','simulada','','Pagado','2026-10-28 02:08:07','2026-10-28 02:01:18'),
(6,8,64001.00,'yape','SIM-YAPE-20261027220944-9459','simulada','','Pagado','2026-10-28 03:09:44','2026-10-28 03:09:44'),
(7,9,1907.00,'yape','SIM-YAPE-20261027221144-6688','simulada','','Reembolsado','2026-10-28 05:00:39','2026-10-28 03:11:44'),
(10,10,34602.00,'yape','SIM-YAPE-20261028005124-2991','simulada','','Pagado','2026-10-28 05:51:24','2026-10-28 05:51:24'),
(11,11,9513.00,'contraentrega','SIM-CONTRA-20261028005414-8567','simulada','','Pendiente','2026-10-28 05:54:14','2026-10-28 05:54:14'),
(12,12,2507.00,'tarjeta','SIM-CARD-20261028230731-7571','simulada','','Pagado','2026-10-29 04:07:31','2026-10-29 04:07:31');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pagos_vendedor`
--

DROP TABLE IF EXISTS `pagos_vendedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_vendedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendedor_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo` varchar(60) NOT NULL DEFAULT '',
  `referencia` varchar(120) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pagven_vendedor` (`vendedor_id`),
  CONSTRAINT `fk_pagven_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_vendedor`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pagos_vendedor` WRITE;
/*!40000 ALTER TABLE `pagos_vendedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_vendedor` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pagos_webhooks`
--

DROP TABLE IF EXISTS `pagos_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pasarela` varchar(40) NOT NULL DEFAULT '',
  `evento_id` varchar(120) NOT NULL,
  `tipo` varchar(60) NOT NULL DEFAULT '',
  `pedido_id` int(11) DEFAULT NULL,
  `pago_id` int(11) DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT '',
  `resultado` varchar(255) NOT NULL DEFAULT '',
  `payload` text DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_webhook_evento` (`pasarela`,`evento_id`),
  KEY `idx_webhook_pedido` (`pedido_id`),
  KEY `idx_webhook_pago` (`pago_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_webhooks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pagos_webhooks` WRITE;
/*!40000 ALTER TABLE `pagos_webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_webhooks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pedido_eventos`
--

DROP TABLE IF EXISTS `pedido_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `evento` varchar(40) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT '',
  `detalle` varchar(255) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_evento_pedido` (`pedido_id`,`id`),
  CONSTRAINT `fk_evento_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_eventos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pedido_eventos` WRITE;
/*!40000 ALTER TABLE `pedido_eventos` DISABLE KEYS */;
INSERT INTO `pedido_eventos` VALUES
(9,4,'pedido_creado','Pagado','Pedido registrado en la tienda.','2026-10-28 02:01:18'),
(10,4,'pago_confirmado','Pagado','Pago aprobado en el checkout simulado.','2026-10-28 02:01:18'),
(15,8,'pedido_creado','Pagado','Pedido registrado en la tienda.','2026-10-28 03:09:44'),
(16,8,'pago_confirmado','Pagado','Pago aprobado en el checkout simulado.','2026-10-28 03:09:44'),
(17,9,'pedido_creado','Pagado','Pedido registrado en la tienda.','2026-10-28 03:11:45'),
(18,9,'pago_confirmado','Pagado','Pago aprobado en el checkout simulado.','2026-10-28 03:11:45'),
(19,9,'pedido_reembolsado','Cancelado','Reembolso de S/ 1,907.00 · cecea','2026-10-28 05:00:39'),
(20,10,'pedido_creado','Pagado','Pedido registrado en la tienda.','2026-10-28 05:51:24'),
(21,10,'pago_confirmado','Pagado','Pago aprobado en el checkout simulado.','2026-10-28 05:51:24'),
(22,11,'pedido_creado','Pendiente','Pedido registrado en la tienda.','2026-10-28 05:54:14'),
(23,12,'pedido_creado','Pagado','Pedido registrado en la tienda.','2026-10-29 04:07:31'),
(24,12,'pago_confirmado','Pagado','Pago aprobado en el checkout simulado.','2026-10-29 04:07:31');
/*!40000 ALTER TABLE `pedido_eventos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `total_pen` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `igv_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `envio_costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `envio_zona` varchar(120) NOT NULL DEFAULT '',
  `descuento_aplicado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cupon_id` int(11) DEFAULT NULL,
  `direccion_id` int(11) DEFAULT NULL,
  `direccion_entrega` text DEFAULT NULL,
  `comprobante_tipo` varchar(10) NOT NULL DEFAULT 'boleta',
  `comprobante_documento` varchar(11) NOT NULL DEFAULT '',
  `comprobante_razon_social` varchar(150) NOT NULL DEFAULT '',
  `courier` varchar(80) NOT NULL DEFAULT '',
  `guia_numero` varchar(80) NOT NULL DEFAULT '',
  `fecha_envio` timestamp NULL DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Pendiente',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES
(1,3,7499.00,7499.00,0.00,0.00,'',0.00,NULL,NULL,NULL,'boleta','','','','',NULL,'Pagado','2026-10-27 01:42:49'),
(2,3,4299.00,4299.00,0.00,0.00,'',0.00,NULL,NULL,NULL,'boleta','','','','',NULL,'Entregado','2026-10-27 02:11:33'),
(3,3,100.00,100.00,0.00,0.00,'',0.00,NULL,NULL,NULL,'boleta','','','','',NULL,'Cancelado','2026-10-27 02:11:33'),
(4,3,13806.00,13798.00,2483.64,8.00,'Arequipa Metropolitana',0.00,NULL,1,'Av. Demo 123','boleta','','','','',NULL,'Pagado','2026-10-28 02:01:18'),
(8,11,64001.00,63993.00,11518.74,8.00,'Arequipa Metropolitana',0.00,NULL,5,'Av. Demo 123','boleta','','','','',NULL,'Pagado','2026-10-28 03:09:44'),
(9,11,1907.00,1899.00,341.82,8.00,'Arequipa Metropolitana',0.00,NULL,5,'Av. Demo 123','boleta','','','','',NULL,'Cancelado','2026-10-28 03:11:44'),
(10,11,34602.00,34594.00,6226.92,8.00,'Arequipa Metropolitana',0.00,NULL,5,'Av. Demo 123','boleta','','','','',NULL,'Pagado','2026-10-28 05:51:24'),
(11,11,9513.00,9495.00,1709.10,18.00,'Lima',0.00,NULL,8,'Av. Demo 123','boleta','','','','',NULL,'Pendiente','2026-10-28 05:54:14'),
(12,3,2507.00,2499.00,381.20,8.00,'Arequipa Metropolitana',0.00,NULL,1,'Av. Demo 123','boleta','12345678','','','',NULL,'Pagado','2026-10-29 04:07:31');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `precios_volumen`
--

DROP TABLE IF EXISTS `precios_volumen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `precios_volumen` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint(20) unsigned NOT NULL,
  `cantidad_minima` int(10) unsigned NOT NULL,
  `cantidad_maxima` int(10) unsigned DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pv_producto` (`producto_id`,`cantidad_minima`),
  CONSTRAINT `fk_pv_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `precios_volumen`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `precios_volumen` WRITE;
/*!40000 ALTER TABLE `precios_volumen` DISABLE KEYS */;
INSERT INTO `precios_volumen` VALUES
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
/*!40000 ALTER TABLE `precios_volumen` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `producto_variantes`
--

DROP TABLE IF EXISTS `producto_variantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_variantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_variante` varchar(120) NOT NULL,
  `precio_extra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `color` varchar(30) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `producto_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_var_producto` (`producto_id`),
  CONSTRAINT `fk_var_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_variantes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `producto_variantes` WRITE;
/*!40000 ALTER TABLE `producto_variantes` DISABLE KEYS */;
INSERT INTO `producto_variantes` VALUES
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
/*!40000 ALTER TABLE `producto_variantes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `slug` varchar(190) NOT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `categoria` varchar(60) NOT NULL,
  `nombre` varchar(190) NOT NULL,
  `especificaciones` text NOT NULL,
  `precio_pen` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `archivado_en` timestamp NULL DEFAULT NULL,
  `marca_id` int(11) DEFAULT NULL,
  `garantia_id` int(11) DEFAULT NULL,
  `vendedor_id` bigint(20) unsigned DEFAULT NULL,
  `colores` varchar(255) NOT NULL DEFAULT '',
  `imagen` varchar(255) NOT NULL DEFAULT '',
  `codigo_sku` varchar(60) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `peso_kg` decimal(8,3) DEFAULT NULL,
  `dimensiones` varchar(80) DEFAULT NULL,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_productos_slug` (`slug`),
  UNIQUE KEY `uq_productos_sku` (`codigo_sku`),
  KEY `fk_productos_garantia` (`garantia_id`),
  KEY `idx_productos_vendedor` (`vendedor_id`),
  KEY `idx_productos_archivado` (`vendedor_id`,`archivado_en`),
  CONSTRAINT `fk_productos_garantia` FOREIGN KEY (`garantia_id`) REFERENCES `garantias` (`id`),
  CONSTRAINT `fk_productos_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES
('iphone-15-pro-256',1,'Celulares','iPhone 15 Pro','Chip A17 Pro | 256GB | Camara 48MP',5499.00,10,1,NULL,1,NULL,NULL,'[\"#3B4C5A\",\"#F5F5DC\",\"#1A1C72\"]','img/Celulares/iPhone_15_Pro/imagen_1.png','JT-00001',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:51:24'),
('iphone-15-pro-512',2,'Celulares','iPhone 15 Pro','Chip A17 Pro | 512GB | Camara 48MP',10999.00,8,1,NULL,1,NULL,NULL,'[\"#3B4C5A\",\"#F5F5DC\"]','img/Celulares/iPhone_15_Pro/imagen_2.png','JT-00002',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-galaxy-s24-ultra-256',3,'Celulares','Samsung Galaxy S24 Ultra','Snapdragon 8 Gen 3 | 256GB | Camara 200MP',7499.00,9,1,NULL,2,NULL,NULL,'[\"#2C2C2C\",\"#E8D5B7\",\"#4A6741\"]','img/Celulares/Samsung_Galaxy_S24_Ultra/imagen_1.png','JT-00003',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:51:24'),
('samsung-galaxy-s24-ultra-512',4,'Celulares','Samsung Galaxy S24 Ultra','Snapdragon 8 Gen 3 | 512GB | Camara 200MP',8999.00,9,1,NULL,2,NULL,NULL,'[\"#2C2C2C\",\"#E8D5B7\"]','img/Celulares/Samsung_Galaxy_S24_Ultra/imagen_2.png','JT-00004',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-14-256',5,'Celulares','Xiaomi 14','Snapdragon 8 Gen 3 | 256GB | Camara Leica',4299.00,18,1,NULL,3,NULL,NULL,'[\"#1A1A2E\",\"#F0E68C\",\"#4A90A4\"]','img/Celulares/Xiaomi_14/imagen_1.png','JT-00005',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-14-512',6,'Celulares','Xiaomi 14','Snapdragon 8 Gen 3 | 512GB | Camara Leica',5199.00,10,1,NULL,3,NULL,NULL,'[\"#1A1A2E\",\"#F0E68C\",\"#4A90A4\"]','img/Celulares/Xiaomi_14/imagen_2.png','JT-00006',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('macbook-air-m3-256',7,'Laptops','MacBook Air M3','Chip M3 | 8GB RAM | 256GB SSD | Pantalla 13.6\"',9499.00,4,1,NULL,1,NULL,NULL,'[\"#C0C0C0\",\"#2C2C2E\",\"#E8D5C4\"]','img/Laptops/MacBook_Air_M3/imagen_1.png','JT-00007',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 03:09:44'),
('macbook-air-m3-512',8,'Laptops','MacBook Air M3','Chip M3 | 16GB RAM | 512GB SSD | Pantalla 13.6\"',11999.00,5,1,NULL,1,NULL,NULL,'[\"#C0C0C0\",\"#2C2C2E\"]','img/Laptops/MacBook_Air_M3/imagen_2.png','JT-00008',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('lenovo-thinkpad-x1-carbon',9,'Laptops','Lenovo ThinkPad X1 Carbon','Intel i7 | 16GB RAM | 512GB SSD | Pantalla 14\"',8799.00,5,1,NULL,4,NULL,NULL,'[\"#1A1A1A\"]','img/Laptops/Lenovo_ThinkPad_X1_Carbon/imagen_1.png','JT-00009',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 02:01:18'),
('hp-pavilion-15-ryzen5',10,'Laptops','HP Pavilion 15','AMD Ryzen 5 | 8GB RAM | 512GB SSD | Pantalla 15.6\"',4999.00,10,1,NULL,5,NULL,NULL,'[\"#B8C4CC\",\"#2C2C2E\"]','img/Laptops/HP_Pavilion_15/imagen_1.png','JT-00010',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 02:01:18'),
('hp-pavilion-15-ryzen7',11,'Laptops','HP Pavilion 15','AMD Ryzen 7 | 16GB RAM | 1TB SSD | Pantalla 15.6\"',6499.00,8,1,NULL,5,NULL,NULL,'[\"#B8C4CC\",\"#2C2C2E\"]','img/Laptops/HP_Pavilion_15/imagen_2.png','JT-00011',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('airpods-pro-2-usbc',12,'Audifonos','Apple AirPods Pro 2','Cancelacion de ruido activa | USB-C | H2 Chip',1899.00,15,1,NULL,1,NULL,NULL,'[\"#FFFFFF\"]','img/Audifonos/Apple_AirPods_Pro_2/imagen_1.png','JT-00012',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:54:14'),
('sony-wh-1000xm5',13,'Audifonos','Sony WH-1000XM5','Cancelacion de ruido | 30h bateria | LDAC',2499.00,13,1,NULL,6,NULL,2,'[\"#1A1A1A\",\"#C0C0C0\",\"#E8D5C4\"]','img/Audifonos/Sony_WH-1000XM5/imagen_1.png','JT-00013',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-29 04:07:31'),
('jbl-tune-510bt',14,'Audifonos','JBL Tune 510BT','Bluetooth 5.0 | 40h bateria | PureBass',349.00,25,1,NULL,7,NULL,NULL,'[\"#1A1A2E\",\"#FFFFFF\",\"#4169E1\"]','img/Audifonos/JBL_Tune_510BT/imagen_1.png','JT-00014',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('jbl-charge-5',15,'Audifonos','JBL Charge 5','Bluetooth 5.1 | 20h bateria | IP67 | Powerbank',699.00,16,1,NULL,7,NULL,2,'[\"#1A1A2E\",\"#2E8B57\",\"#B22222\"]','img/Audifonos/JBL_Charge_5/imagen_1.png','JT-00015',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('bose-qc-ultra',16,'Audifonos','Bose QuietComfort Ultra','Cancelacion de ruido | Immersive Audio | 24h bateria',3299.00,7,1,NULL,8,NULL,2,'[\"#1A1A2E\",\"#C0C0C0\",\"#E8D5C4\"]','img/Audifonos/Bose_QC_Ultra/imagen_1.png','JT-00016',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-28 05:51:24'),
('cargador-magsafe',17,'Accesorios','Cargador MagSafe','Carga inalambrica 15W | Compatible iPhone',299.00,30,1,NULL,NULL,NULL,NULL,'[\"#FFFFFF\"]','img/Accesorios/Cargador_MagSafe/imagen_1.png','JT-00017',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('funda-iphone-15-pro',18,'Accesorios','Funda iPhone 15 Pro','Silicona | MagSafe | Proteccion completa',149.00,40,1,NULL,NULL,NULL,NULL,'[\"#1A1A2E\",\"#4169E1\",\"#2E8B57\"]','img/Accesorios/Funda_iPhone_15_Pro/imagen_1.png','JT-00018',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('logitech-mx-master-3s',19,'Accesorios','Logitech MX Master 3S','Inalambrico | 8K DPI | USB-C | Silence Click',649.00,22,1,NULL,10,NULL,NULL,'[\"#1A1A2E\",\"#C0C0C0\"]','img/Accesorios/Logitech_MX_Master_3S/imagen_1.png','JT-00019',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('anker-powercore-20000',20,'Accesorios','Anker PowerCore 20000','Powerbank 20000mAh | Carga rapida 20W',249.00,28,1,NULL,9,NULL,NULL,'[\"#1A1A2E\",\"#FFFFFF\"]','img/Accesorios/Anker_PowerCore_20000/imagen_1.png','JT-00020',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-monitor-27',21,'Accesorios','Samsung Monitor 27\" 4K','4K UHD | IPS | USB-C | 60Hz',1899.00,10,1,NULL,2,NULL,NULL,'[\"#1A1A2E\",\"#C0C0C0\"]','img/Accesorios/Samsung_Monitor_27/imagen_1.png','JT-00021',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('apple-watch-series-9-45',22,'Smartwatches','Apple Watch Series 9','S9 SiP | GPS | Pantalla Always-On | 45mm',3499.00,13,1,NULL,1,NULL,NULL,'[\"#C0C0C0\",\"#1A1A2E\",\"#FFB6C1\"]','img/Smartwatches/Apple_Watch_Series_9/imagen_1.png','JT-00022',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('apple-watch-series-9-41',23,'Smartwatches','Apple Watch Series 9','S9 SiP | GPS | Pantalla Always-On | 41mm',3199.00,11,1,NULL,1,NULL,NULL,'[\"#C0C0C0\",\"#FFB6C1\"]','img/Smartwatches/Apple_Watch_Series_9/imagen_2.png','JT-00023',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-galaxy-watch-6-44',24,'Smartwatches','Samsung Galaxy Watch 6','Exynos W930 | GPS | WearOS | 44mm',2199.00,14,1,NULL,2,NULL,NULL,'[\"#1A1A2E\",\"#C0C0C0\"]','img/Smartwatches/Samsung_Galaxy_Watch_6/imagen_1.png','JT-00024',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-smart-band-8',25,'Smartwatches','Xiaomi Smart Band 8','Pantalla AMOLED | 16 dias bateria | GPS',249.00,35,1,NULL,3,NULL,NULL,'[\"#1A1A2E\",\"#C0C0C0\",\"#FFB6C1\"]','img/Smartwatches/Xiaomi_Smart_Band_8/imagen_1.png','JT-00025',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xiaomi-smart-band-8-nfc',26,'Smartwatches','Xiaomi Smart Band 8','Pantalla AMOLED | 16 dias bateria | NFC',299.00,30,1,NULL,3,NULL,NULL,'[\"#1A1A2E\",\"#C0C0C0\"]','img/Smartwatches/Xiaomi_Smart_Band_8/imagen_2.png','JT-00026',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('ipad-air-m2',27,'Tablets','iPad Air M2','Chip M2 | 64GB | Pantalla 11\" Liquid Retina',2999.00,10,1,NULL,1,NULL,NULL,'[\"#C0C0C0\",\"#1A1A2E\",\"#E8D5C4\"]','img/Tablets/iPad_Air_M2/imagen_1.png','JT-00027',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('samsung-galaxy-tab-s9',28,'Tablets','Samsung Galaxy Tab S9','Snapdragon 8 Gen 2 | 128GB | AMOLED 11\"',3499.00,9,1,NULL,2,NULL,NULL,'[\"#1A1A2E\",\"#C0C0C0\"]','img/Tablets/Samsung_Galaxy_Tab_S9/imagen_1.png','JT-00028',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('dualsense-edge',29,'Gaming','PlayStation DualSense Edge','Control pro | Back buttons | Stick removibles',1299.00,6,1,NULL,12,NULL,NULL,'[\"#FFFFFF\",\"#1A1A2E\"]','img/Gaming/DualSense_Edge/imagen_1.png','JT-00029',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('xbox-controller',30,'Gaming','Xbox Wireless Controller','Bluetooth | USB-C | Compatible PC/Xbox',349.00,12,1,NULL,11,NULL,NULL,'[\"#1A1A2E\",\"#FFFFFF\",\"#2E8B57\"]','img/Gaming/Xbox_Controller/imagen_1.png','JT-00030',NULL,NULL,NULL,5,'2026-10-27 01:38:38','2026-10-27 01:38:38'),
('agua',35,'Accesorios','agua','wcqw',12.00,10,0,'2026-10-28 04:04:10',6,1,11,'[]','img/producto-sin-imagen.svg',NULL,'dd',2.000,'10x5',5,'2026-10-28 03:15:12','2026-10-28 04:04:10'),
('boton',42,'Tablets','boton','jyhgtrfedwsqawertyhu7\r\ntrbeb5ebt',500.00,40,1,NULL,3,3,11,'[]','img/producto-sin-imagen.svg',NULL,'tgh',6.000,'10x5',5,'2026-10-29 02:18:51','2026-10-29 02:19:48');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `resenas`
--

DROP TABLE IF EXISTS `resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resenas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `calificacion` tinyint(4) NOT NULL,
  `comentario` varchar(500) NOT NULL DEFAULT '',
  `estado` enum('visible','oculta') NOT NULL DEFAULT 'visible',
  `motivo_oculto` varchar(255) NOT NULL DEFAULT '',
  `moderado_por` varchar(60) NOT NULL DEFAULT '',
  `moderado_en` timestamp NULL DEFAULT NULL,
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `producto_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resena_unica` (`producto_id`,`cliente_id`),
  KEY `fk_res_cliente` (`cliente_id`),
  KEY `idx_resena_estado` (`producto_id`,`estado`),
  CONSTRAINT `fk_res_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_res_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resenas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `resenas` WRITE;
/*!40000 ALTER TABLE `resenas` DISABLE KEYS */;
INSERT INTO `resenas` VALUES
(6,3,4,'cevqerv','visible','','',NULL,'2026-10-28 02:18:39','2026-10-28 02:02:16',3),
(10,3,4,'','visible','','',NULL,'2026-10-28 02:12:59','2026-10-28 02:12:59',9);
/*!40000 ALTER TABLE `resenas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `solicitudes_vendedor`
--

DROP TABLE IF EXISTS `solicitudes_vendedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_vendedor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) unsigned NOT NULL,
  `tipo` enum('B2C','B2B','C2C') NOT NULL DEFAULT 'B2C',
  `mensaje` varchar(500) NOT NULL DEFAULT '',
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` varchar(500) NOT NULL DEFAULT '',
  `resuelto_por` varchar(60) NOT NULL DEFAULT '',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `resuelto_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_solicitud_estado` (`estado`,`creado_en`),
  KEY `idx_solicitud_usuario` (`usuario_id`,`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_vendedor`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `solicitudes_vendedor` WRITE;
/*!40000 ALTER TABLE `solicitudes_vendedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_vendedor` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `soporte_tickets`
--

DROP TABLE IF EXISTS `soporte_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `soporte_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned DEFAULT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `asunto` varchar(180) NOT NULL,
  `categoria` varchar(80) NOT NULL DEFAULT 'General',
  `descripcion` varchar(1000) NOT NULL DEFAULT '',
  `respuesta` varchar(1500) NOT NULL DEFAULT '',
  `respondido_por` varchar(60) NOT NULL DEFAULT '',
  `respondido_en` timestamp NULL DEFAULT NULL,
  `estado` enum('abierto','en_proceso','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_soporte_cliente` (`cliente_id`),
  KEY `idx_soporte_cliente` (`cliente_id`,`estado`),
  KEY `idx_soporte_estado` (`estado`,`creado_en`),
  CONSTRAINT `fk_soporte_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soporte_tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `soporte_tickets` WRITE;
/*!40000 ALTER TABLE `soporte_tickets` DISABLE KEYS */;
INSERT INTO `soporte_tickets` VALUES
(2,11,8,'reembolso','Pedido','rembolsame','no','jeremytech','2026-10-28 05:02:29','resuelto','2026-10-28 05:03:14','2026-10-28 05:01:27');
/*!40000 ALTER TABLE `soporte_tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tarifas_envio`
--

DROP TABLE IF EXISTS `tarifas_envio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifas_envio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zona` varchar(120) NOT NULL,
  `costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dias_estimados` varchar(60) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `zona` (`zona`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tarifas_envio`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tarifas_envio` WRITE;
/*!40000 ALTER TABLE `tarifas_envio` DISABLE KEYS */;
INSERT INTO `tarifas_envio` VALUES
(1,'Recojo en tienda (Cercado, Arequipa)',0.00,'El mismo día',1,'2026-10-27 01:38:38'),
(2,'Arequipa Metropolitana',8.00,'1-2 días',1,'2026-10-27 01:38:38'),
(3,'Arequipa Provincia',15.00,'2-4 días',1,'2026-10-27 01:38:38'),
(4,'Lima',18.00,'3-5 días',1,'2026-10-27 01:38:38'),
(5,'Otras regiones del Perú',25.00,'4-7 días',1,'2026-10-27 01:38:38');
/*!40000 ALTER TABLE `tarifas_envio` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tokens_recuperacion`
--

DROP TABLE IF EXISTS `tokens_recuperacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens_recuperacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('admin','cliente') NOT NULL,
  `usuario_id` bigint(20) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_en` timestamp NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_tokens_tipo_usuario` (`tipo`,`usuario_id`,`usado`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokens_recuperacion`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tokens_recuperacion` WRITE;
/*!40000 ALTER TABLE `tokens_recuperacion` DISABLE KEYS */;
INSERT INTO `tokens_recuperacion` VALUES
(2,'cliente',1,'75e2c402bd94ae6120970a34fbb6c85702daf622ba2a9b2a1bcc33ecb06c2ffa','2026-10-28 02:01:43',1,'2026-10-28 01:31:43'),
(7,'cliente',1,'d1201d02510457be4a0f319b34f6b96809e67d85523bc37ebce87aedfa5239e4','2026-10-28 02:22:47',0,'2026-10-28 01:52:47');
/*!40000 ALTER TABLE `tokens_recuperacion` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `apellidos` varchar(180) NOT NULL DEFAULT '',
  `email` varchar(190) NOT NULL,
  `usuario` varchar(60) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(30) NOT NULL DEFAULT 'cliente',
  `comision_porcentaje` decimal(5,2) DEFAULT NULL,
  `verificado` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `telefono` varchar(20) NOT NULL DEFAULT '',
  `direccion` varchar(255) NOT NULL DEFAULT '',
  `tipo` enum('B2C','B2B','C2C') NOT NULL DEFAULT 'B2C',
  `empresa` varchar(120) NOT NULL DEFAULT '',
  `ruc` varchar(11) NOT NULL DEFAULT '',
  `plataforma_reventa` varchar(120) NOT NULL DEFAULT '',
  `dni` varchar(8) NOT NULL DEFAULT '',
  `pregunta_secreta` varchar(180) NOT NULL DEFAULT '',
  `respuesta_secreta_hash` varchar(255) NOT NULL DEFAULT '',
  `ultimo_ingreso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_usuario_unique` (`usuario`),
  KEY `users_rol_index` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Admin','Demo','admin@example.com','jeremytech','$2y$12$h6t9y9/nJVYDtYH2nRALUu1WgUv1StECs0MGqmFufzGIn0Jq/XfFK','admin',NULL,0,1,'999000000','Av. Demo 123','C2C','','','','','','','2026-10-29 02:35:28','2026-10-27 01:38:38','2026-10-29 04:52:56',NULL),
(2,'Vendedor','Demo','vendedor@example.com','vendedor','$2y$12$DAvLpC1IqRUe.mxKXGSlAOrwCF.vKw0EvSj7Ip7yQbDyj3zotI0c6','vendedor',15.00,1,1,'999000001','Av. Demo 123','B2B','','','','','¿Cuál es el nombre de tu primera mascota?','$2y$12$NKjVyGkafkvm3A3QK0TLrOwajZxO9mthKrHo9dgo2PytGc9o1EQNu','2026-10-29 02:09:36','2026-10-27 01:38:38','2026-10-29 04:52:56',NULL),
(3,'Cliente','','cliente@example.com',NULL,'$2y$12$nD6dIfvWyBC0DwP6UV.IFeNL8vfZE03ehO.LQoyzCOSs6sQW.l6SC','cliente',NULL,0,1,'999000002','Av. Demo 123','B2C','','','','','','',NULL,'2026-10-27 01:38:48','2026-10-29 04:52:56',NULL),
(11,'Cliente','Demo2','cliente2@example.com','cliente2','$2y$12$9/uh8C2l1U5BBN.ToahxXeNTYeMVYezUl/YOJhFauFV0KXveiB.Dm','cliente',NULL,0,1,'999000003','Av. Demo 123','B2C','Empresa Demo','20123456789','','00000001','¿En qué ciudad naciste?','$2y$12$NO4DkvjUt40RSSlr9.OLPONJltsrf09L9TTSJqVIwPjrGe6fDPr0K',NULL,'2026-10-28 02:55:40','2026-10-29 04:52:56',NULL),
(18,'Empresa','Demo','empresa@example.com','empresa','$2y$12$yfzi08jn2TafhdIYcZrtqeYOys6IGvLDpAXMx6vISKUoABH/l1yxa','vendedor',NULL,0,1,'999000004','Av. Demo 123','B2B','Empresa Demo SAC','20123456789','','00000002','¿En qué ciudad naciste?','$2y$12$cJU1qPj9CIbswjSnhY3PVe0tXNpmU2JSXF076g8L//GxyxoeS1QQO',NULL,'2026-10-29 02:38:55','2026-10-29 04:52:56',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping events for database 'jeremy_tech'
--

--
-- Dumping routines for database 'jeremy_tech'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-28 23:53:00
