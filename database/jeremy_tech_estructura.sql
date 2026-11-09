-- Jeremy Tech: estructura para MariaDB 10.2+
-- Crea la base, las tablas, indices y claves foraneas. No carga datos.
-- Uso: mariadb -h 127.0.0.1 -u root -p < database/jeremy_tech_estructura.sql
-- Despues importar database/jeremy_tech_datos.sql.
-- Solo para instalaciones nuevas: elimina las tablas existentes.
-- No reimportar sobre una base con datos que se deban conservar.
-- El SKU automatico (JT-...) usa una tabla de secuencia y un trigger, el
-- equivalente en MariaDB a la secuencia y al trigger de PostgreSQL.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE DATABASE IF NOT EXISTS `jeremy_tech`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `jeremy_tech`;

DROP TABLE IF EXISTS `auditoria_admin`;
CREATE TABLE `auditoria_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_usuario` varchar(60) NOT NULL,
  `accion` varchar(60) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `detalle` varchar(255) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `calificaciones_vendedor`;
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
DROP TABLE IF EXISTS `carrito_items`;
CREATE TABLE `carrito_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrito_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `producto_id` bigint(20) unsigned NOT NULL,
  `variante_id` int(11) DEFAULT NULL,
  `variante_clave` int(11) GENERATED ALWAYS AS (coalesce(`variante_id`,0)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_carrito_items_linea` (`carrito_id`,`producto_id`,`variante_clave`),
  KEY `fk_caritem_producto` (`producto_id`),
  KEY `idx_carrito_items_variante` (`variante_id`),
  CONSTRAINT `fk_caritem_carrito` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`),
  CONSTRAINT `fk_caritem_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_caritem_variante` FOREIGN KEY (`variante_id`) REFERENCES `producto_variantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ck_carrito_items_variante` CHECK (`variante_id` is null or `variante_id` > 0),
  CONSTRAINT `ck_carrito_items_cantidad` CHECK (`cantidad` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `carritos`;
CREATE TABLE `carritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `fk_car_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL DEFAULT '',
  `descripcion` varchar(255) NOT NULL DEFAULT '',
  `imagen_url` varchar(500) NOT NULL DEFAULT '',
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `chatbot_faq`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `comisiones`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `clave` varchar(60) NOT NULL,
  `valor` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `cupones`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `detalle_pedido`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `direcciones`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `eventos_analitica`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `favoritos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `garantias`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `intentos_acceso`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `marcas`;
CREATE TABLE `marcas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL DEFAULT '',
  `logo_url` varchar(255) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `movimientos_inventario`;
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
  CONSTRAINT `fk_mov_variante` FOREIGN KEY (`variante_id`) REFERENCES `producto_variantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `notificaciones`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `pagos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `pagos_vendedor`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `pagos_webhooks`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `pedido_eventos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `pedidos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `precios_volumen`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `producto_imagenes`;
CREATE TABLE `producto_imagenes` (
  `producto_id` bigint(20) unsigned NOT NULL,
  `mime` varchar(60) NOT NULL,
  `datos` longblob NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`producto_id`),
  CONSTRAINT `fk_prodimg_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `producto_variantes`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `productos`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `resenas`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `solicitudes_vendedor`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `soporte_tickets`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `tarifas_envio`;
CREATE TABLE `tarifas_envio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zona` varchar(120) NOT NULL,
  `costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dias_estimados` varchar(60) NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `zona` (`zona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `tokens_recuperacion`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `users`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restricciones e indices de integridad y rendimiento.
CREATE INDEX IF NOT EXISTS idx_pagos_pedido_id_desc ON pagos (pedido_id, id DESC);
CREATE INDEX IF NOT EXISTS idx_pedidos_cliente_id_desc ON pedidos (cliente_id, id DESC);
CREATE INDEX IF NOT EXISTS idx_detalle_pedido_pedido ON detalle_pedido (pedido_id);
CREATE INDEX IF NOT EXISTS idx_detalle_pedido_producto ON detalle_pedido (producto_id);
CREATE INDEX IF NOT EXISTS idx_detalle_pedido_variante ON detalle_pedido (variante_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_cupon ON pedidos (cupon_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_direccion ON pedidos (direccion_id);
-- MariaDB no tiene índices parciales: el índice cubre todo el listado.
CREATE INDEX IF NOT EXISTS idx_resenas_visibles_recientes ON resenas (producto_id, creado_en DESC, id DESC);

-- El máximo coincide con decimal(10,2).
-- No se imponen igualdades de totales/redondeo ni usos_actuales <= usos_maximos:
-- el admin puede reducir el límite de un cupón sin alterar usos ya registrados.
ALTER TABLE calificaciones_vendedor ADD CONSTRAINT IF NOT EXISTS ck_calificaciones_vendedor_rango
  CHECK (calificacion BETWEEN 1 AND 5);
ALTER TABLE carrito_items ADD CONSTRAINT IF NOT EXISTS ck_carrito_items_cantidad
  CHECK (cantidad > 0);
ALTER TABLE comisiones ADD CONSTRAINT IF NOT EXISTS ck_comisiones_montos
  CHECK (monto_venta BETWEEN 0 AND 99999999.99 AND monto_comision BETWEEN 0 AND 99999999.99 AND monto_vendedor BETWEEN 0 AND 99999999.99);
ALTER TABLE comisiones ADD CONSTRAINT IF NOT EXISTS ck_comisiones_porcentaje
  CHECK (porcentaje_comision BETWEEN 0 AND 100);
ALTER TABLE cupones ADD CONSTRAINT IF NOT EXISTS ck_cupones_valor
  CHECK (valor > 0 AND valor <= 99999999.99 AND (tipo <> 'porcentaje' OR valor <= 100));
ALTER TABLE cupones ADD CONSTRAINT IF NOT EXISTS ck_cupones_usos
  CHECK (usos_actuales >= 0 AND (usos_maximos IS NULL OR usos_maximos >= 0));
ALTER TABLE detalle_pedido ADD CONSTRAINT IF NOT EXISTS ck_detalle_pedido_cantidad
  CHECK (cantidad > 0);
ALTER TABLE detalle_pedido ADD CONSTRAINT IF NOT EXISTS ck_detalle_pedido_precio
  CHECK (precio_unitario_pen BETWEEN 0 AND 99999999.99);
ALTER TABLE garantias ADD CONSTRAINT IF NOT EXISTS ck_garantias_precio
  CHECK (precio_pen BETWEEN 0 AND 99999999.99);
ALTER TABLE movimientos_inventario ADD CONSTRAINT IF NOT EXISTS ck_movimientos_inventario_cantidad
  CHECK (cantidad > 0);
ALTER TABLE pagos ADD CONSTRAINT IF NOT EXISTS ck_pagos_monto
  CHECK (monto_pen BETWEEN 0 AND 99999999.99);
ALTER TABLE pagos_vendedor ADD CONSTRAINT IF NOT EXISTS ck_pagos_vendedor_monto
  CHECK (monto BETWEEN 0 AND 99999999.99);
ALTER TABLE pedidos ADD CONSTRAINT IF NOT EXISTS ck_pedidos_montos
  CHECK (total_pen BETWEEN 0 AND 99999999.99 AND subtotal BETWEEN 0 AND 99999999.99 AND igv_monto BETWEEN 0 AND 99999999.99 AND envio_costo BETWEEN 0 AND 99999999.99 AND descuento_aplicado BETWEEN 0 AND 99999999.99);
ALTER TABLE precios_volumen ADD CONSTRAINT IF NOT EXISTS ck_precios_volumen_cantidades
  CHECK (cantidad_minima > 0 AND (cantidad_maxima IS NULL OR cantidad_maxima >= cantidad_minima));
ALTER TABLE precios_volumen ADD CONSTRAINT IF NOT EXISTS ck_precios_volumen_precio
  CHECK (precio_unitario BETWEEN 0 AND 99999999.99);
ALTER TABLE producto_variantes ADD CONSTRAINT IF NOT EXISTS ck_producto_variantes_stock
  CHECK (stock >= 0);
ALTER TABLE producto_variantes ADD CONSTRAINT IF NOT EXISTS ck_producto_variantes_precio
  CHECK (precio_extra BETWEEN 0 AND 99999999.99);
ALTER TABLE productos ADD CONSTRAINT IF NOT EXISTS ck_productos_stock
  CHECK (stock >= 0 AND stock_minimo >= 0);
ALTER TABLE productos ADD CONSTRAINT IF NOT EXISTS ck_productos_precio
  CHECK (precio_pen BETWEEN 0 AND 99999999.99);
ALTER TABLE resenas ADD CONSTRAINT IF NOT EXISTS ck_resenas_calificacion
  CHECK (calificacion BETWEEN 1 AND 5);
ALTER TABLE tarifas_envio ADD CONSTRAINT IF NOT EXISTS ck_tarifas_envio_costo
  CHECK (costo BETWEEN 0 AND 99999999.99);
ALTER TABLE users ADD CONSTRAINT IF NOT EXISTS ck_users_comision
  CHECK (comision_porcentaje BETWEEN 0 AND 100);

-- Las cuentas usan baja lógica. El inventario necesita el producto al devolver
-- stock: con ventas se archiva (ProductoVendedor::eliminar), no se borra.
-- NO CASCADE en pedidos, detalles o pagos: nunca eliminar dinero/historial.
-- MariaDB no admite IF NOT EXISTS en FOREIGN KEY: se comprueba el catálogo.
SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'pedidos'
      AND constraint_name = 'fk_pedidos_cliente') > 0,
  'DO 0',
  'ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (cliente_id) REFERENCES users (id)'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'detalle_pedido'
      AND constraint_name = 'fk_detalle_pedido') > 0,
  'DO 0',
  'ALTER TABLE `detalle_pedido` ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (pedido_id) REFERENCES pedidos (id)'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'detalle_pedido'
      AND constraint_name = 'fk_detalle_producto') > 0,
  'DO 0',
  'ALTER TABLE `detalle_pedido` ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (producto_id) REFERENCES productos (id)'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'pagos'
      AND constraint_name = 'fk_pagos_pedido') > 0,
  'DO 0',
  'ALTER TABLE `pagos` ADD CONSTRAINT `fk_pagos_pedido` FOREIGN KEY (pedido_id) REFERENCES pedidos (id)'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'detalle_pedido'
      AND constraint_name = 'fk_detalle_variante') > 0,
  'DO 0',
  'ALTER TABLE `detalle_pedido` ADD CONSTRAINT `fk_detalle_variante` FOREIGN KEY (variante_id) REFERENCES producto_variantes (id) ON DELETE SET NULL'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'pedidos'
      AND constraint_name = 'fk_pedidos_cupon') > 0,
  'DO 0',
  'ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedidos_cupon` FOREIGN KEY (cupon_id) REFERENCES cupones (id) ON DELETE SET NULL'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'pedidos'
      AND constraint_name = 'fk_pedidos_direccion') > 0,
  'DO 0',
  'ALTER TABLE `pedidos` ADD CONSTRAINT `fk_pedidos_direccion` FOREIGN KEY (direccion_id) REFERENCES direcciones (id) ON DELETE SET NULL'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

-- El historial de inventario debe impedir borrar una variante movida. Las
-- bases creadas antes de esta migración la dejaban en SET NULL.
SET @jt_rule = (SELECT delete_rule FROM information_schema.referential_constraints
  WHERE constraint_schema = DATABASE() AND table_name = 'movimientos_inventario'
    AND constraint_name = 'fk_mov_variante');
SET @jt_fk = CASE
  WHEN @jt_rule IS NULL THEN
    'ALTER TABLE movimientos_inventario ADD CONSTRAINT fk_mov_variante
       FOREIGN KEY (variante_id) REFERENCES producto_variantes (id)'
  WHEN @jt_rule = 'SET NULL' THEN
    'ALTER TABLE movimientos_inventario DROP FOREIGN KEY fk_mov_variante,
       ADD CONSTRAINT fk_mov_variante FOREIGN KEY (variante_id) REFERENCES producto_variantes (id)'
  ELSE 'DO 0'
END;
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

-- Secuencia de ids de productos, equivalente a productos_id_seq de PostgreSQL.
DROP TABLE IF EXISTS `productos_id_seq`;
CREATE TABLE `productos_id_seq` (
  `id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `productos_id_seq` (`id`) VALUES (0);

-- SKU automatico JT-<id con al menos cinco digitos>. El trigger asigna el id
-- desde la secuencia para poder calcular el SKU antes de insertar la fila.
DROP TRIGGER IF EXISTS `trg_productos_sku`;
DROP TRIGGER IF EXISTS `trg_productos_sku_update`;
DELIMITER ;;
CREATE TRIGGER `trg_productos_sku` BEFORE INSERT ON `productos` FOR EACH ROW
BEGIN
  IF NEW.`id` IS NULL OR NEW.`id` = 0 THEN
    UPDATE `productos_id_seq` SET `id` = LAST_INSERT_ID(`id` + 1);
    SET NEW.`id` = LAST_INSERT_ID();
  ELSE
    UPDATE `productos_id_seq` SET `id` = GREATEST(`id`, NEW.`id`);
  END IF;
  IF NEW.`codigo_sku` IS NULL OR TRIM(NEW.`codigo_sku`) = '' THEN
    SET NEW.`codigo_sku` = CONCAT('JT-', LPAD(NEW.`id`, GREATEST(5, CHAR_LENGTH(NEW.`id`)), '0'));
  END IF;
END;;

CREATE TRIGGER `trg_productos_sku_update` BEFORE UPDATE ON `productos` FOR EACH ROW
BEGIN
  IF NEW.`codigo_sku` IS NULL OR TRIM(NEW.`codigo_sku`) = '' THEN
    SET NEW.`codigo_sku` = CONCAT('JT-', LPAD(NEW.`id`, GREATEST(5, CHAR_LENGTH(NEW.`id`)), '0'));
  END IF;
END;;
DELIMITER ;

SET FOREIGN_KEY_CHECKS=1;
