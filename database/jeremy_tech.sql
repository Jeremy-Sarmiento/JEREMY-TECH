-- ============================================================
-- jeremy_tech — Esquema completo + semillas (Jeremy Tech MVC)
-- ------------------------------------------------------------
-- Única fuente de verdad de la base de datos de JEREMY-TECH.
-- Reemplaza el antiguo bootstrapBaseDeDatos() de config/database.php.
--
-- Importar (idempotente, seguro sobre datos existentes):
--   mysql -u root -p < database/jeremy_tech.sql
--
-- 26 tablas en total:
--   Base (5):        productos, users, pedidos, detalle_pedido, pagos
--   Tienda (14):     categorias, marcas, garantias, movimientos_inventario,
--                    producto_variantes, tarifas_envio, cupones, direcciones,
--                    favoritos, resenas, carritos, carrito_items,
--                    notificaciones, soporte_tickets
--   Sistema (7):     tokens_recuperacion, auditoria_admin, eventos_analitica,
--                    calificaciones_vendedor, comisiones, pagos_vendedor,
--                    configuracion
--
-- `users` reemplaza a `clientes` (tienda) y a `usuarios_admin` (panel):
-- una sola fila por persona con `rol`, `activo` y soft-delete (`deleted_at`).
-- Las tablas viejas se dropean en la sección ETAPA 4, al final del archivo.
--
-- Todos los CREATE/INSERT son idempotentes (IF NOT EXISTS / INSERT IGNORE
-- / ADD COLUMN IF NOT EXISTS) y pueden re-ejecutarse sin dañar datos.
-- ============================================================

CREATE DATABASE IF NOT EXISTS jeremy_tech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jeremy_tech;

-- ---------------------------------------------------------------------------
-- 1. AJUSTES A LAS TABLAS BASE EXISTENTES
-- ---------------------------------------------------------------------------

-- productos: stock, activo y marca
ALTER TABLE productos ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 0 AFTER precio_pen;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER stock;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS marca_id INT DEFAULT NULL AFTER activo;

-- ---------------------------------------------------------------------------
-- Identidad unificada: `users` (Etapa 4)
-- Sustituye a `clientes` (tienda) + `usuarios_admin` (panel), al estilo
-- techmarket: una fila por persona con `rol`, `activo` y soft-delete.
-- Se crea ANTES que las tablas hijas porque sus 11 FKs apuntan aquí.
-- La carga del dato legado y el dropeo de las tablas viejas viven en la
-- sección ETAPA 4 (al final del archivo).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    apellidos VARCHAR(180) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL,
    usuario VARCHAR(60) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NOT NULL DEFAULT 'cliente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    telefono VARCHAR(20) NOT NULL DEFAULT '',
    direccion VARCHAR(255) NOT NULL DEFAULT '',
    tipo ENUM('B2C','B2B') NOT NULL DEFAULT 'B2C',
    empresa VARCHAR(120) NOT NULL DEFAULT '',
    ruc VARCHAR(11) NOT NULL DEFAULT '',
    dni VARCHAR(8) NOT NULL DEFAULT '',
    pregunta_secreta VARCHAR(180) NOT NULL DEFAULT '',
    respuesta_secreta_hash VARCHAR(255) NOT NULL DEFAULT '',
    ultimo_ingreso TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_usuario_unique (usuario),
    KEY users_rol_index (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pedidos: resumen monetario y descuentos (para boleta/factura SUNAT)
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_pen;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS igv_monto DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS envio_costo DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER igv_monto;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS envio_zona VARCHAR(120) NOT NULL DEFAULT '' AFTER envio_costo;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS descuento_aplicado DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER envio_zona;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS cupon_id INT DEFAULT NULL AFTER descuento_aplicado;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS direccion_id INT DEFAULT NULL AFTER cupon_id;

-- detalle_pedido: soporte de variantes
ALTER TABLE detalle_pedido ADD COLUMN IF NOT EXISTS variante_id INT DEFAULT NULL AFTER precio_unitario_pen;
ALTER TABLE detalle_pedido ADD COLUMN IF NOT EXISTS variante_nombre VARCHAR(120) NOT NULL DEFAULT '' AFTER variante_id;

-- pagos: transacción externa
ALTER TABLE pagos ADD COLUMN IF NOT EXISTS transaccion_id VARCHAR(60) NOT NULL DEFAULT '' AFTER monto_pen;

-- ---------------------------------------------------------------------------
-- 2. TABLAS NUEVAS
-- ---------------------------------------------------------------------------

-- Marca (tienda tecnológica: Apple, Samsung, ...)
CREATE TABLE IF NOT EXISTS marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(80) NOT NULL DEFAULT '',
    logo_url VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorías del catálogo
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(80) NOT NULL DEFAULT '',
    descripcion VARCHAR(255) NOT NULL DEFAULT '',
    imagen_url VARCHAR(500) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garantías oficiales/especiales de dispositivos
CREATE TABLE IF NOT EXISTS garantias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    duracion_meses INT NOT NULL DEFAULT 6,
    cobertura VARCHAR(500) NOT NULL DEFAULT '',
    precio_pen DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de stock (entradas/salidas)
CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id VARCHAR(60) NOT NULL,
    tipo ENUM('entrada','salida') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(180) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mov_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Variantes (color/capacidad) con precio adicional
CREATE TABLE IF NOT EXISTS producto_variantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id VARCHAR(60) NOT NULL,
    nombre_variante VARCHAR(120) NOT NULL,
    precio_extra DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    color VARCHAR(30) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_var_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración clave/valor
CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(60) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarifas de envío por zona
CREATE TABLE IF NOT EXISTS tarifas_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona VARCHAR(120) NOT NULL UNIQUE,
    costo DECIMAL(10,2) NOT NULL DEFAULT 0,
    dias_estimados VARCHAR(60) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cupones de descuento (admin)
CREATE TABLE IF NOT EXISTS cupones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    tipo ENUM('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
    valor DECIMAL(10,2) NOT NULL,
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    usos_maximos INT DEFAULT NULL,
    usos_actuales INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Direcciones de cliente
CREATE TABLE IF NOT EXISTS direcciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NOT NULL DEFAULT 'Principal',
    direccion VARCHAR(255) NOT NULL,
    latitud DECIMAL(10,7) DEFAULT NULL,
    longitud DECIMAL(10,7) DEFAULT NULL,
    predeterminada TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_direcciones_cliente FOREIGN KEY (cliente_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favoritos
CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    producto_id VARCHAR(60) NOT NULL,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY favorito_unico (cliente_id, producto_id),
    CONSTRAINT fk_fav_cliente FOREIGN KEY (cliente_id) REFERENCES users(id),
    CONSTRAINT fk_fav_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reseñas/valoraciones de productos
CREATE TABLE IF NOT EXISTS resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id VARCHAR(60) NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    calificacion TINYINT NOT NULL,
    comentario VARCHAR(500) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY resena_unica (producto_id, cliente_id),
    CONSTRAINT fk_res_producto FOREIGN KEY (producto_id) REFERENCES productos(id),
    CONSTRAINT fk_res_cliente FOREIGN KEY (cliente_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carritos persistentes (una fila por cliente)
CREATE TABLE IF NOT EXISTS carritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL UNIQUE,
    actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_car_cliente FOREIGN KEY (cliente_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items del carrito persistente
CREATE TABLE IF NOT EXISTS carrito_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    carrito_id INT NOT NULL,
    producto_id VARCHAR(60) NOT NULL,
    cantidad INT NOT NULL,
    UNIQUE KEY carrito_item_unico (carrito_id, producto_id),
    CONSTRAINT fk_caritem_carrito FOREIGN KEY (carrito_id) REFERENCES carritos(id),
    CONSTRAINT fk_caritem_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificaciones
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED DEFAULT NULL,
    tipo VARCHAR(60) NOT NULL,
    asunto VARCHAR(180) NOT NULL,
    cuerpo VARCHAR(500) NOT NULL DEFAULT '',
    enviado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_cliente FOREIGN KEY (cliente_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS tokens_recuperacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('admin','cliente') NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_en TIMESTAMP NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auditoría de acciones de administradores
CREATE TABLE IF NOT EXISTS auditoria_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_usuario VARCHAR(60) NOT NULL,
    accion VARCHAR(60) NOT NULL,
    referencia_id INT DEFAULT NULL,
    detalle VARCHAR(255) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eventos de analítica (comportamiento en tienda)
CREATE TABLE IF NOT EXISTS eventos_analitica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_evento VARCHAR(60) NOT NULL,
    datos_json VARCHAR(1000) NOT NULL DEFAULT '{}',
    pagina VARCHAR(80) NOT NULL DEFAULT '',
    cliente_id BIGINT UNSIGNED DEFAULT NULL,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Calificaciones a vendedores (marketplace)
CREATE TABLE IF NOT EXISTS calificaciones_vendedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendedor_id BIGINT UNSIGNED NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    calificacion TINYINT NOT NULL,
    comentario VARCHAR(500) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY calificacion_vendedor_unica (vendedor_id, cliente_id),
    CONSTRAINT fk_cal_vendedor FOREIGN KEY (vendedor_id) REFERENCES users(id),
    CONSTRAINT fk_cal_cliente FOREIGN KEY (cliente_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comisiones por venta canalizada a vendedores
CREATE TABLE IF NOT EXISTS comisiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    vendedor_id BIGINT UNSIGNED NOT NULL,
    monto_venta DECIMAL(10,2) NOT NULL,
    porcentaje_comision DECIMAL(5,2) NOT NULL,
    monto_comision DECIMAL(10,2) NOT NULL,
    monto_vendedor DECIMAL(10,2) NOT NULL,
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY comision_unica (pedido_id, vendedor_id),
    CONSTRAINT fk_com_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    CONSTRAINT fk_com_vendedor FOREIGN KEY (vendedor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos realizados a vendedores
CREATE TABLE IF NOT EXISTS pagos_vendedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendedor_id BIGINT UNSIGNED NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(60) NOT NULL DEFAULT '',
    referencia VARCHAR(120) NOT NULL DEFAULT '',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagven_vendedor FOREIGN KEY (vendedor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (Etapa 4) `usuarios_admin` desapareció: su DDL y su semilla viven ahora en
-- `users` (creada arriba) y en la sección ETAPA 4.

-- Tickets de soporte/garantía postventa
CREATE TABLE IF NOT EXISTS soporte_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED DEFAULT NULL,
    asunto VARCHAR(180) NOT NULL,
    categoria VARCHAR(80) NOT NULL DEFAULT 'General',
    descripcion VARCHAR(1000) NOT NULL DEFAULT '',
    estado ENUM('abierto','en_proceso','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
    creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_soporte_cliente FOREIGN KEY (cliente_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. SEMILLAS (idempotentes)
-- ---------------------------------------------------------------------------

-- Categorías de la tienda (las 7 usadas por GRIDS)
INSERT IGNORE INTO categorias (nombre, slug, descripcion) VALUES
    ('Celulares',    'celulares',    'Smartphones de última generación de Apple, Samsung, Xiaomi y más'),
    ('Laptops',      'laptops',      'Laptops y notebooks para trabajo, estudio y gaming'),
    ('Audifonos',    'audifonos',    'Audífonos inalámbricos y con cancelación de ruido'),
    ('Accesorios',   'accesorios',   'Cargadores, fundas, monitores y periféricos'),
    ('Smartwatches', 'smartwatches', 'Smartwatches y bandas de actividad física'),
    ('Tablets',      'tablets',      'Tablets para creatividad y entretenimiento'),
    ('Gaming',       'gaming',       'Accesorios y equipos para gamers');

-- Marcas
INSERT IGNORE INTO marcas (nombre, slug) VALUES
    ('Apple',      'apple'),
    ('Samsung',    'samsung'),
    ('Xiaomi',     'xiaomi'),
    ('Lenovo',     'lenovo'),
    ('HP',         'hp'),
    ('Sony',       'sony'),
    ('JBL',        'jbl'),
    ('Bose',       'bose'),
    ('Anker',      'anker'),
    ('Logitech',   'logitech'),
    ('Microsoft',  'microsoft'),
    ('PlayStation','playstation');

-- Configuración
INSERT IGNORE INTO configuracion (clave, valor) VALUES
    ('whatsapp_numero',  '51997833866'),
    ('igv_porcentaje',   '18'),
    ('moneda_base',      'PEN'),
    ('comision_marketplace_porcentaje', '0');

-- Tarifas de envío (el carrito mapea: tienda -> Recojo en tienda, domicilio -> Arequipa Provincia)
INSERT IGNORE INTO tarifas_envio (zona, costo, dias_estimados) VALUES
    ('Recojo en tienda (Cercado, Arequipa)', 0, 'El mismo día'),
    ('Arequipa Metropolitana',               8, '1-2 días'),
    ('Arequipa Provincia',                  15, '2-4 días'),
    ('Lima',                                18, '3-5 días'),
    ('Otras regiones del Perú',             25, '4-7 días');

-- Cupones (espejo de los que manejaba Model\Carrito)
INSERT IGNORE INTO cupones (codigo, tipo, valor) VALUES
    ('JEREMYTECH10',  'porcentaje', 10.00),
    ('PRIMERACOMPRA', 'porcentaje', 15.00),
    ('JEREMY50',      'fijo',       50.00),
    ('OFERTA2026',    'porcentaje', 12.00);

-- (Etapa 4) El usuario administrador por defecto se siembra al final del
-- archivo, después de migrar los datos legados a `users`.

-- (Etapa 2) Las semillas de productos y la asignación de marca viven al final
-- del archivo: deben correr DESPUÉS de migrar `productos.id` a numérico + `slug`,
-- porque el INSERT identifica cada producto por su `slug`.


-- ============================================================
-- ETAPA 1 — Garantías como catálogo (idempotente)
-- ------------------------------------------------------------
-- El vínculo producto <-> garantía se administra desde el panel
-- (?ruta=admin/garantias). Re-ejecutable sin dañar datos.
-- ============================================================

-- Vínculo producto <-> garantía
ALTER TABLE productos ADD COLUMN IF NOT EXISTS garantia_id INT DEFAULT NULL AFTER marca_id;

-- Clave única para que las semillas usen INSERT IGNORE
ALTER TABLE garantias ADD UNIQUE KEY IF NOT EXISTS uq_garantias_nombre (nombre);

-- FK (ADD CONSTRAINT no admite IF NOT EXISTS -> se dropea por nombre y se
-- recrea; así la definición queda siempre como la de este archivo).
-- ON DELETE RESTRICT: no se puede borrar una garantía que un producto use.
SET @existe_fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'productos'
    AND CONSTRAINT_NAME = 'fk_productos_garantia');
SET @sql := IF(@existe_fk > 0,
  'ALTER TABLE productos DROP FOREIGN KEY fk_productos_garantia',
  'DO 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE productos
  ADD CONSTRAINT fk_productos_garantia FOREIGN KEY (garantia_id) REFERENCES garantias (id) ON DELETE RESTRICT;

-- Semillas de garantías de ejemplo
INSERT IGNORE INTO garantias (nombre, duracion_meses, cobertura, precio_pen, activo) VALUES
  ('Garantia del fabricante 6 meses', 6, 'Defectos de fabricacion y componentes', 0, 1),
  ('Garantia extendida 12 meses', 12, 'Cobertura ampliada de fabricacion', 49.90, 1),
  ('Garantia premium 24 meses', 24, 'Cobertura total incluyendo pantalla y bateria', 149.90, 1),
  ('Garantia importacion 3 meses', 3, 'Solo defectos de funcionamiento', 0, 1);

-- ============================================================
-- ETAPA 2 — Productos: id numérico BIGINT + slug (idempotente)
-- ------------------------------------------------------------
-- `productos.id` dejaba de ser el slug (VARCHAR) y pasa a ser la clave real;
-- el slug se conserva en una columna propia:
--   id    BIGINT UNSIGNED AUTO_INCREMENT  -> clave primaria (como techmarket)
--   slug  VARCHAR(190) UNIQUE             -> identificador en UI, carrito y URLs
-- Las tablas que referenciaban `productos.id` (VARCHAR slug) migran a BIGINT
-- conservando el mapeo slug -> id. Re-ejecutable sin dañar datos.
-- ============================================================

DROP PROCEDURE IF EXISTS etapa2_drop_fk;
DROP PROCEDURE IF EXISTS etapa2_drop_idx;
DROP PROCEDURE IF EXISTS etapa2_add_fk;
DROP PROCEDURE IF EXISTS etapa2_swap_hijo;
DROP PROCEDURE IF EXISTS etapa2_productos_id_numerico;

DELIMITER $$

CREATE PROCEDURE etapa2_drop_fk(IN p_tabla VARCHAR(64), IN p_fk VARCHAR(64))
BEGIN
  DECLARE v_existe INT DEFAULT 0;
  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla AND CONSTRAINT_NAME = p_fk;
  IF v_existe > 0 THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` DROP FOREIGN KEY `', p_fk, '`');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$

CREATE PROCEDURE etapa2_drop_idx(IN p_tabla VARCHAR(64), IN p_idx VARCHAR(64))
proc: BEGIN
  DECLARE v_existe INT DEFAULT 0;
  IF p_idx IS NULL THEN
    LEAVE proc;
  END IF;
  SELECT COUNT(*) INTO v_existe FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla AND INDEX_NAME = p_idx;
  IF v_existe > 0 THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` DROP INDEX `', p_idx, '`');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$

CREATE PROCEDURE etapa2_add_fk(IN p_tabla VARCHAR(64), IN p_fk VARCHAR(64))
BEGIN
  DECLARE v_existe INT DEFAULT 0;
  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla AND CONSTRAINT_NAME = p_fk;
  IF v_existe = 0 THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` ADD CONSTRAINT `', p_fk,
                     '` FOREIGN KEY (producto_id) REFERENCES productos (id) ON DELETE RESTRICT');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$

-- Convierte `producto_id` (VARCHAR slug) -> BIGINT en una tabla hija.
-- La FK se recrea después, cuando `productos.id` ya es numérico.
-- `p_aux_cols`: columnas del índice único que NO son producto_id. El índice
-- único compuesto puede estar sirviendo de índice a otra FK (p.ej.
-- fk_caritem_carrito usa el prefijo carrito_id de carrito_item_unico), así
-- que antes de dropearlo se crea un índice auxiliar temporal.
CREATE PROCEDURE etapa2_swap_hijo(IN p_tabla VARCHAR(64), IN p_fk VARCHAR(64), IN p_uniq VARCHAR(64), IN p_uniq_cols VARCHAR(200), IN p_aux_cols VARCHAR(200))
proc: BEGIN
  DECLARE v_col VARCHAR(32) DEFAULT NULL;

  SELECT DATA_TYPE INTO v_col FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla AND COLUMN_NAME = 'producto_id';

  IF v_col IS NULL OR v_col <> 'varchar' THEN
    LEAVE proc; -- ya migrada (o tabla inexistente)
  END IF;

  IF p_uniq IS NOT NULL AND p_aux_cols IS NOT NULL THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` ADD INDEX `etapa2_aux` (', p_aux_cols, ')');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;

  CALL etapa2_drop_fk(p_tabla, p_fk);
  CALL etapa2_drop_idx(p_tabla, p_uniq);

  SET @s := CONCAT('ALTER TABLE `', p_tabla, '` ADD COLUMN producto_id_nuevo BIGINT UNSIGNED NULL');
  PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

  SET @s := CONCAT('UPDATE `', p_tabla, '` t JOIN productos p ON p.slug = t.producto_id SET t.producto_id_nuevo = p.id_num');
  PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

  SET @s := CONCAT('ALTER TABLE `', p_tabla, '` DROP COLUMN producto_id');
  PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

  SET @s := CONCAT('ALTER TABLE `', p_tabla, '` CHANGE COLUMN producto_id_nuevo producto_id BIGINT UNSIGNED NOT NULL');
  PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

  IF p_uniq IS NOT NULL AND p_uniq_cols IS NOT NULL THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` ADD UNIQUE KEY `', p_uniq, '` (', p_uniq_cols, ')');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;

  CALL etapa2_drop_idx(p_tabla, 'etapa2_aux');
END$$

CREATE PROCEDURE etapa2_productos_id_numerico()
proc: BEGIN
  DECLARE v_legado INT DEFAULT 0;

  SELECT COUNT(*) INTO v_legado FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos'
     AND COLUMN_NAME = 'id' AND DATA_TYPE = 'varchar';

  IF v_legado = 0 THEN
    LEAVE proc; -- ya migrado
  END IF;

  -- 1) El slug actual pasa a su propia columna.
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'slug') = 0 THEN
    ALTER TABLE productos ADD COLUMN slug VARCHAR(190) NULL AFTER id;
  END IF;
  UPDATE productos SET slug = id WHERE slug IS NULL OR slug = '';

  -- 2) Clave numérica temporal (AUTO_INCREMENT exige un índice -> UNIQUE).
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'id_num') = 0 THEN
    ALTER TABLE productos ADD COLUMN id_num BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE AFTER slug;
  END IF;

  -- 3) Hijas: slug -> id_num (índice único recreado; la FK va en el paso 5).
  CALL etapa2_swap_hijo('carrito_items', 'fk_caritem_producto', 'carrito_item_unico', 'carrito_id, producto_id', 'carrito_id');
  CALL etapa2_swap_hijo('favoritos',     'fk_fav_producto',     'favorito_unico',     'cliente_id, producto_id', 'cliente_id');
  CALL etapa2_swap_hijo('resenas',       'fk_res_producto',     'resena_unica',       'producto_id, cliente_id', NULL);
  CALL etapa2_swap_hijo('movimientos_inventario', 'fk_mov_producto', NULL, NULL, NULL);
  CALL etapa2_swap_hijo('producto_variantes',     'fk_var_producto', NULL, NULL, NULL);
  CALL etapa2_swap_hijo('detalle_pedido',         NULL,               NULL, NULL, NULL);

  -- 4) productos: la clave primaria pasa al id numérico.
  ALTER TABLE productos DROP PRIMARY KEY;
  ALTER TABLE productos DROP COLUMN id;
  ALTER TABLE productos CHANGE COLUMN id_num id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
  ALTER TABLE productos ADD PRIMARY KEY (id);
  ALTER TABLE productos DROP INDEX id_num;
  ALTER TABLE productos MODIFY COLUMN slug VARCHAR(190) NOT NULL;
  ALTER TABLE productos ADD UNIQUE KEY uq_productos_slug (slug);

  -- 5) FKs contra el nuevo id numérico.
  CALL etapa2_add_fk('carrito_items', 'fk_caritem_producto');
  CALL etapa2_add_fk('favoritos',     'fk_fav_producto');
  CALL etapa2_add_fk('resenas',       'fk_res_producto');
  CALL etapa2_add_fk('movimientos_inventario', 'fk_mov_producto');
  CALL etapa2_add_fk('producto_variantes',     'fk_var_producto');

  -- 6) InnoDB deja el contador AUTO_INCREMENT inflado tras reconstruir la
  -- tabla; se normaliza al max(id)+1 para que los próximos id no salten.
  SET @ai := (SELECT IFNULL(MAX(id) + 1, 1) FROM productos);
  SET @s := CONCAT('ALTER TABLE productos AUTO_INCREMENT = ', @ai);
  PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
END$$

DELIMITER ;

CALL etapa2_productos_id_numerico();
DROP PROCEDURE IF EXISTS etapa2_productos_id_numerico;
DROP PROCEDURE IF EXISTS etapa2_swap_hijo;
DROP PROCEDURE IF EXISTS etapa2_add_fk;
DROP PROCEDURE IF EXISTS etapa2_drop_idx;
DROP PROCEDURE IF EXISTS etapa2_drop_fk;

-- Catálogo base (30 productos / 23 tarjetas agrupadas por nombre).
-- Idempotente gracias a la clave única `uq_productos_slug`.
INSERT IGNORE INTO productos (slug, categoria, nombre, especificaciones, precio_pen, stock, activo, colores, imagen) VALUES
    ('iphone-15-pro-256', 'Celulares', 'iPhone 15 Pro', 'Chip A17 Pro | 256GB | Camara 48MP', 5499, 12, 1, '["#3B4C5A","#F5F5DC","#1A1C72"]', 'img/Celulares/iPhone_15_Pro/imagen_1.png'),
    ('iphone-15-pro-512', 'Celulares', 'iPhone 15 Pro', 'Chip A17 Pro | 512GB | Camara 48MP', 10999, 8, 1, '["#3B4C5A","#F5F5DC"]', 'img/Celulares/iPhone_15_Pro/imagen_2.png'),
    ('samsung-galaxy-s24-ultra-256', 'Celulares', 'Samsung Galaxy S24 Ultra', 'Snapdragon 8 Gen 3 | 256GB | Camara 200MP', 7499, 15, 1, '["#2C2C2C","#E8D5B7","#4A6741"]', 'img/Celulares/Samsung_Galaxy_S24_Ultra/imagen_1.png'),
    ('samsung-galaxy-s24-ultra-512', 'Celulares', 'Samsung Galaxy S24 Ultra', 'Snapdragon 8 Gen 3 | 512GB | Camara 200MP', 8999, 9, 1, '["#2C2C2C","#E8D5B7"]', 'img/Celulares/Samsung_Galaxy_S24_Ultra/imagen_2.png'),
    ('xiaomi-14-256', 'Celulares', 'Xiaomi 14', 'Snapdragon 8 Gen 3 | 256GB | Camara Leica', 4299, 18, 1, '["#1A1A2E","#F0E68C","#4A90A4"]', 'img/Celulares/Xiaomi_14/imagen_1.png'),
    ('xiaomi-14-512', 'Celulares', 'Xiaomi 14', 'Snapdragon 8 Gen 3 | 512GB | Camara Leica', 5199, 10, 1, '["#1A1A2E","#F0E68C","#4A90A4"]', 'img/Celulares/Xiaomi_14/imagen_2.png'),

    ('macbook-air-m3-256', 'Laptops', 'MacBook Air M3', 'Chip M3 | 8GB RAM | 256GB SSD | Pantalla 13.6"', 9499, 7, 1, '["#C0C0C0","#2C2C2E","#E8D5C4"]', 'img/Laptops/MacBook_Air_M3/imagen_1.png'),
    ('macbook-air-m3-512', 'Laptops', 'MacBook Air M3', 'Chip M3 | 16GB RAM | 512GB SSD | Pantalla 13.6"', 11999, 5, 1, '["#C0C0C0","#2C2C2E"]', 'img/Laptops/MacBook_Air_M3/imagen_2.png'),
    ('lenovo-thinkpad-x1-carbon', 'Laptops', 'Lenovo ThinkPad X1 Carbon', 'Intel i7 | 16GB RAM | 512GB SSD | Pantalla 14"', 8799, 6, 1, '["#1A1A1A"]', 'img/Laptops/Lenovo_ThinkPad_X1_Carbon/imagen_1.png'),
    ('hp-pavilion-15-ryzen5', 'Laptops', 'HP Pavilion 15', 'AMD Ryzen 5 | 8GB RAM | 512GB SSD | Pantalla 15.6"', 4999, 11, 1, '["#B8C4CC","#2C2C2E"]', 'img/Laptops/HP_Pavilion_15/imagen_1.png'),
    ('hp-pavilion-15-ryzen7', 'Laptops', 'HP Pavilion 15', 'AMD Ryzen 7 | 16GB RAM | 1TB SSD | Pantalla 15.6"', 6499, 8, 1, '["#B8C4CC","#2C2C2E"]', 'img/Laptops/HP_Pavilion_15/imagen_2.png'),

    ('airpods-pro-2-usbc', 'Audifonos', 'Apple AirPods Pro 2', 'Cancelacion de ruido activa | USB-C | H2 Chip', 1899, 20, 1, '["#FFFFFF"]', 'img/Audifonos/Apple_AirPods_Pro_2/imagen_1.png'),
    ('sony-wh-1000xm5', 'Audifonos', 'Sony WH-1000XM5', 'Cancelacion de ruido | 30h bateria | LDAC', 2499, 14, 1, '["#1A1A1A","#C0C0C0","#E8D5C4"]', 'img/Audifonos/Sony_WH-1000XM5/imagen_1.png'),
    ('jbl-tune-510bt', 'Audifonos', 'JBL Tune 510BT', 'Bluetooth 5.0 | 40h bateria | PureBass', 349, 25, 1, '["#1A1A2E","#FFFFFF","#4169E1"]', 'img/Audifonos/JBL_Tune_510BT/imagen_1.png'),
    ('jbl-charge-5', 'Audifonos', 'JBL Charge 5', 'Bluetooth 5.1 | 20h bateria | IP67 | Powerbank', 699, 16, 1, '["#1A1A2E","#2E8B57","#B22222"]', 'img/Audifonos/JBL_Charge_5/imagen_1.png'),
    ('bose-qc-ultra', 'Audifonos', 'Bose QuietComfort Ultra', 'Cancelacion de ruido | Immersive Audio | 24h bateria', 3299, 9, 1, '["#1A1A2E","#C0C0C0","#E8D5C4"]', 'img/Audifonos/Bose_QC_Ultra/imagen_1.png'),

    ('cargador-magsafe', 'Accesorios', 'Cargador MagSafe', 'Carga inalambrica 15W | Compatible iPhone', 299, 30, 1, '["#FFFFFF"]', 'img/Accesorios/Cargador_MagSafe/imagen_1.png'),
    ('funda-iphone-15-pro', 'Accesorios', 'Funda iPhone 15 Pro', 'Silicona | MagSafe | Proteccion completa', 149, 40, 1, '["#1A1A2E","#4169E1","#2E8B57"]', 'img/Accesorios/Funda_iPhone_15_Pro/imagen_1.png'),
    ('logitech-mx-master-3s', 'Accesorios', 'Logitech MX Master 3S', 'Inalambrico | 8K DPI | USB-C | Silence Click', 649, 22, 1, '["#1A1A2E","#C0C0C0"]', 'img/Accesorios/Logitech_MX_Master_3S/imagen_1.png'),
    ('anker-powercore-20000', 'Accesorios', 'Anker PowerCore 20000', 'Powerbank 20000mAh | Carga rapida 20W', 249, 28, 1, '["#1A1A2E","#FFFFFF"]', 'img/Accesorios/Anker_PowerCore_20000/imagen_1.png'),
    ('samsung-monitor-27', 'Accesorios', 'Samsung Monitor 27" 4K', '4K UHD | IPS | USB-C | 60Hz', 1899, 10, 1, '["#1A1A2E","#C0C0C0"]', 'img/Accesorios/Samsung_Monitor_27/imagen_1.png'),

    ('apple-watch-series-9-45', 'Smartwatches', 'Apple Watch Series 9', 'S9 SiP | GPS | Pantalla Always-On | 45mm', 3499, 13, 1, '["#C0C0C0","#1A1A2E","#FFB6C1"]', 'img/Smartwatches/Apple_Watch_Series_9/imagen_1.png'),
    ('apple-watch-series-9-41', 'Smartwatches', 'Apple Watch Series 9', 'S9 SiP | GPS | Pantalla Always-On | 41mm', 3199, 11, 1, '["#C0C0C0","#FFB6C1"]', 'img/Smartwatches/Apple_Watch_Series_9/imagen_2.png'),
    ('samsung-galaxy-watch-6-44', 'Smartwatches', 'Samsung Galaxy Watch 6', 'Exynos W930 | GPS | WearOS | 44mm', 2199, 14, 1, '["#1A1A2E","#C0C0C0"]', 'img/Smartwatches/Samsung_Galaxy_Watch_6/imagen_1.png'),
    ('xiaomi-smart-band-8', 'Smartwatches', 'Xiaomi Smart Band 8', 'Pantalla AMOLED | 16 dias bateria | GPS', 249, 35, 1, '["#1A1A2E","#C0C0C0","#FFB6C1"]', 'img/Smartwatches/Xiaomi_Smart_Band_8/imagen_1.png'),
    ('xiaomi-smart-band-8-nfc', 'Smartwatches', 'Xiaomi Smart Band 8', 'Pantalla AMOLED | 16 dias bateria | NFC', 299, 30, 1, '["#1A1A2E","#C0C0C0"]', 'img/Smartwatches/Xiaomi_Smart_Band_8/imagen_2.png'),

    ('ipad-air-m2', 'Tablets', 'iPad Air M2', 'Chip M2 | 64GB | Pantalla 11" Liquid Retina', 2999, 10, 1, '["#C0C0C0","#1A1A2E","#E8D5C4"]', 'img/Tablets/iPad_Air_M2/imagen_1.png'),
    ('samsung-galaxy-tab-s9', 'Tablets', 'Samsung Galaxy Tab S9', 'Snapdragon 8 Gen 2 | 128GB | AMOLED 11"', 3499, 9, 1, '["#1A1A2E","#C0C0C0"]', 'img/Tablets/Samsung_Galaxy_Tab_S9/imagen_1.png'),

    ('dualsense-edge', 'Gaming', 'PlayStation DualSense Edge', 'Control pro | Back buttons | Stick removibles', 1299, 6, 1, '["#FFFFFF","#1A1A2E"]', 'img/Gaming/DualSense_Edge/imagen_1.png'),
    ('xbox-controller', 'Gaming', 'Xbox Wireless Controller', 'Bluetooth | USB-C | Compatible PC/Xbox', 349, 12, 1, '["#1A1A2E","#FFFFFF","#2E8B57"]', 'img/Gaming/Xbox_Controller/imagen_1.png');

-- Asignación de marca a los productos existentes (idempotente)
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Apple') WHERE nombre LIKE 'iPhone%' OR nombre LIKE 'MacBook%' OR nombre LIKE 'Apple AirPods%' OR nombre LIKE 'Apple Watch%' OR nombre LIKE 'iPad%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Samsung') WHERE nombre LIKE 'Samsung Galax%' OR nombre = 'Samsung Monitor 27" 4K';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Xiaomi') WHERE nombre LIKE 'Xiaomi%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Lenovo') WHERE nombre LIKE 'Lenovo%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'HP') WHERE nombre LIKE 'HP%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Sony') WHERE nombre LIKE 'Sony%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'JBL') WHERE nombre LIKE 'JBL%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Bose') WHERE nombre LIKE 'Bose%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Anker') WHERE nombre LIKE 'Anker%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Logitech') WHERE nombre LIKE 'Logitech%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'Microsoft') WHERE nombre LIKE 'Xbox%';
UPDATE productos SET marca_id = (SELECT id FROM marcas WHERE nombre = 'PlayStation') WHERE nombre LIKE 'PlayStation%';

-- `INSERT IGNORE` consume valores del auto-increment aunque la fila se salte,
-- e InnoDB no permite bajar el contador con `ALTER ... AUTO_INCREMENT = n`.
-- Quitar y reponer la propiedad lo deja en max(id)+1.
ALTER TABLE productos MODIFY COLUMN id BIGINT UNSIGNED NOT NULL;
ALTER TABLE productos MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

-- ============================================================
-- ETAPA 3 — Productos ampliados + precio DECIMAL(10,2) (idempotente)
-- ------------------------------------------------------------
-- Ficha técnica del producto y control de stock mínimo en el panel
-- (?ruta=admin/productos). Va DESPUÉS de las semillas para que el
-- relleno de SKU/timestamps alcance también a los productos que
-- acaban de insertarse en una base nueva.
-- ============================================================

-- Ficha técnica
ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_sku VARCHAR(60) NULL DEFAULT NULL;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS modelo VARCHAR(120) NULL DEFAULT NULL;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS peso_kg DECIMAL(8,3) NULL DEFAULT NULL;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS dimensiones VARCHAR(80) NULL DEFAULT NULL;

-- Alerta de reposición: el panel marca "Stock bajo" cuando stock <= stock_minimo.
ALTER TABLE productos ADD COLUMN IF NOT EXISTS stock_minimo INT NOT NULL DEFAULT 5;

-- Auditoría de edición (created_at lo rellena el DEFAULT; updated_at lo
-- marca la base con ON UPDATE CURRENT_TIMESTAMP en cada UPDATE).
ALTER TABLE productos ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Índice único de SKU. Varios productos pueden quedar sin SKU: MySQL/MariaDB
-- permite varios NULL en un índice único, así que el relleno posterior es seguro.
ALTER TABLE productos ADD UNIQUE KEY IF NOT EXISTS uq_productos_sku (codigo_sku);

-- Precio en soles con decimales. Todo el dinero derivado (pedidos.subtotal,
-- pedidos.total_pen, detalle_pedido.precio_unitario_pen, pagos.monto_pen,
-- cupones.valor, garantias.precio_pen) ya era DECIMAL(10,2): productos.precio_pen
-- era el último entero y truncaba los centavos al volcar a un pedido.
SET @precio_entero := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos'
    AND COLUMN_NAME = 'precio_pen' AND DATA_TYPE = 'int');
SET @sql := IF(@precio_entero > 0,
  'ALTER TABLE productos MODIFY COLUMN precio_pen DECIMAL(10,2) NOT NULL',
  'DO 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Relleno idempotente: solo toca filas que aún no tienen valor.
-- El SKU deriva del id (único) y no depende del orden del INSERT del catálogo.
UPDATE productos SET codigo_sku = CONCAT('JT-', LPAD(id, 5, '0')) WHERE codigo_sku IS NULL;
UPDATE productos SET created_at = NOW() WHERE created_at IS NULL;


-- ============================================================
-- ETAPA 4 — Usuarios unificados en `users` (idempotente)
-- ------------------------------------------------------------
-- `clientes` (tienda) y `usuarios_admin` (panel) se fusionan en una sola
-- tabla `users`, como en techmarket:
--   * los clientes conservan su `id` (11 FKs dependen de él);
--   * los admins se insertan con id autoasignado (sus ids no tienen FK);
--   * cada fila recibe un `rol`: 'cliente' | 'admin' | 'vendedor';
--   * al terminar se ensanchan las columnas hijas a BIGINT UNSIGNED, se
--     repuntan las 11 FKs a `users(id)` y se dropean las tablas viejas.
-- Re-ejecutable: si `clientes` ya no existe, no hace nada.
-- ============================================================

DROP PROCEDURE IF EXISTS etapa4_repuntar_fk;
DROP PROCEDURE IF EXISTS etapa4_unificar_usuarios;

DELIMITER $$

-- Ensancha una columna a BIGINT UNSIGNED y (re)apunta su FK a `users(id)`.
CREATE PROCEDURE etapa4_repuntar_fk(IN p_tabla VARCHAR(64), IN p_col VARCHAR(64),
                                   IN p_fk VARCHAR(64), IN p_nulo VARCHAR(10))
proc: BEGIN
  DECLARE v_existe INT DEFAULT 0;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla;
  IF v_existe = 0 THEN
    LEAVE proc; -- la tabla hija todavía no existe (instalación nueva)
  END IF;

  -- La FK se dropea antes del MODIFY: cambiarle el tipo en su sitio la rompe.
  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla AND CONSTRAINT_NAME = p_fk;
  IF v_existe > 0 THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` DROP FOREIGN KEY `', p_fk, '`');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;

  SET @s := CONCAT('ALTER TABLE `', p_tabla, '` MODIFY COLUMN `', p_col,
                   '` BIGINT UNSIGNED ', p_nulo);
  PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = p_tabla AND CONSTRAINT_NAME = p_fk;
  IF v_existe = 0 THEN
    SET @s := CONCAT('ALTER TABLE `', p_tabla, '` ADD CONSTRAINT `', p_fk,
                     '` FOREIGN KEY (`', p_col, '`) REFERENCES users (id) ON DELETE RESTRICT');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$

-- Migra el dato legado de `clientes` + `usuarios_admin` hacia `users`.
CREATE PROCEDURE etapa4_unificar_usuarios()
proc: BEGIN
  DECLARE v_existe INT DEFAULT 0;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes';
  IF v_existe = 0 THEN
    LEAVE proc; -- ya migrado
  END IF;

  -- 1) Columnas que este archivo ya no crea fuera de `users`.
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS nombre VARCHAR(120) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS apellidos VARCHAR(180) NOT NULL DEFAULT '' AFTER nombre;
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER nombre;
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS pregunta_secreta VARCHAR(180) NOT NULL DEFAULT '' AFTER password_hash;
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS respuesta_secreta_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER pregunta_secreta;
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS email VARCHAR(120) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS direccion VARCHAR(255) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS tipo ENUM('B2C','B2B') NOT NULL DEFAULT 'B2C';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS empresa VARCHAR(120) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS ruc VARCHAR(11) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS dni VARCHAR(8) NOT NULL DEFAULT '';
  ALTER TABLE clientes ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

  -- 2) Clientes -> users. Se conserva el `id` original: las 11 FKs dependen
  --    de él, así que no se puede renumerar. `email` es NOT NULL + UNIQUE,
  --    así que los vacíos reciben un placeholder por fila.
  INSERT IGNORE INTO users (id, name, apellidos, email, password, telefono, direccion,
                            tipo, empresa, ruc, dni, pregunta_secreta,
                            respuesta_secreta_hash, activo, created_at)
  SELECT id,
         COALESCE(NULLIF(TRIM(nombre), ''), CONCAT('cliente-', id)),
         COALESCE(NULLIF(TRIM(apellidos), ''), ''),
         LOWER(CASE WHEN email IS NULL OR TRIM(email) = ''
                    THEN CONCAT('cliente-', id, '@sin-correo.local')
                    ELSE TRIM(email) END),
         COALESCE(password_hash, ''),
         COALESCE(telefono, ''),
         COALESCE(direccion, ''),
         COALESCE(tipo, 'B2C'),
         COALESCE(empresa, ''),
         COALESCE(ruc, ''),
         COALESCE(dni, ''),
         COALESCE(pregunta_secreta, ''),
         COALESCE(respuesta_secreta_hash, ''),
         1,
         COALESCE(fecha_registro, NOW())
    FROM clientes;

  -- 3) Administradores del panel -> users con rol 'admin'.
  --    Su `id` no tiene FKs, así que se reasigna solo.
  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_admin';
  IF v_existe > 0 THEN
    ALTER TABLE usuarios_admin ADD COLUMN IF NOT EXISTS nombre VARCHAR(120) NOT NULL DEFAULT '' AFTER usuario;
    ALTER TABLE usuarios_admin ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash;
    ALTER TABLE usuarios_admin ADD COLUMN IF NOT EXISTS ultimo_ingreso TIMESTAMP NULL DEFAULT NULL AFTER activo;
    ALTER TABLE usuarios_admin ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NULL DEFAULT NULL;

    INSERT INTO users (name, email, usuario, password, rol, activo, ultimo_ingreso, created_at)
    SELECT COALESCE(NULLIF(TRIM(nombre), ''), usuario),
           CONCAT(usuario, '@jeremytech.pe'),
           usuario,
           password_hash,
           'admin',
           activo,
           ultimo_ingreso,
           COALESCE(creado_en, NOW())
      FROM usuarios_admin;
  END IF;

  -- 4) Las 11 FKs que apuntaban a `clientes(id)`.
  CALL etapa4_repuntar_fk('pedidos', 'cliente_id', 'fk_pedidos_cliente', 'NOT NULL');
  CALL etapa4_repuntar_fk('direcciones', 'cliente_id', 'fk_direcciones_cliente', 'NOT NULL');
  CALL etapa4_repuntar_fk('favoritos', 'cliente_id', 'fk_fav_cliente', 'NOT NULL');
  CALL etapa4_repuntar_fk('resenas', 'cliente_id', 'fk_res_cliente', 'NOT NULL');
  CALL etapa4_repuntar_fk('carritos', 'cliente_id', 'fk_car_cliente', 'NOT NULL');
  CALL etapa4_repuntar_fk('notificaciones', 'cliente_id', 'fk_notif_cliente', 'NULL');
  CALL etapa4_repuntar_fk('calificaciones_vendedor', 'vendedor_id', 'fk_cal_vendedor', 'NOT NULL');
  CALL etapa4_repuntar_fk('calificaciones_vendedor', 'cliente_id', 'fk_cal_cliente', 'NOT NULL');
  CALL etapa4_repuntar_fk('comisiones', 'vendedor_id', 'fk_com_vendedor', 'NOT NULL');
  CALL etapa4_repuntar_fk('pagos_vendedor', 'vendedor_id', 'fk_pagven_vendedor', 'NOT NULL');
  CALL etapa4_repuntar_fk('soporte_tickets', 'cliente_id', 'fk_soporte_cliente', 'NULL');

  --    Columnas de cliente sin FK: igualmente se ensanchan por consistencia.
  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eventos_analitica';
  IF v_existe > 0 THEN
    ALTER TABLE eventos_analitica MODIFY COLUMN cliente_id BIGINT UNSIGNED NULL;
  END IF;
  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tokens_recuperacion';
  IF v_existe > 0 THEN
    ALTER TABLE tokens_recuperacion MODIFY COLUMN usuario_id BIGINT UNSIGNED NOT NULL;
  END IF;

  -- 5) Tablas viejas: ya no tienen ninguna referencia.
  DROP TABLE usuarios_admin;
  DROP TABLE clientes;
END$$

DELIMITER ;

CALL etapa4_unificar_usuarios();
DROP PROCEDURE IF EXISTS etapa4_unificar_usuarios;
DROP PROCEDURE IF EXISTS etapa4_repuntar_fk;

-- Usuario administrador por defecto (jeremytech / jeremytech).
-- La semilla vive AL FINAL: si no, `etapa4_unificar_usuarios()` volvería a
-- insertar el registro legado sobre un id ya ocupado.
INSERT INTO users (name, email, usuario, password, rol, activo)
SELECT 'Jeremy', 'jeremytech@jeremytech.pe', 'jeremytech',
       '$2y$12$h6t9y9/nJVYDtYH2nRALUu1WgUv1StECs0MGqmFufzGIn0Jq/XfFK', 'admin', 1
  FROM DUAL
 WHERE NOT EXISTS (
       SELECT 1 FROM (SELECT usuario, email FROM users) t
        WHERE t.usuario = 'jeremytech' OR t.email = 'jeremytech@jeremytech.pe'
 );

-- ============================================================
-- ETAPA 5 — Direcciones de envío con zona y costo (idempotente)
-- ------------------------------------------------------------
-- Antes el checkout solo tenía un switch "domicilio / tienda" y el costo
-- salía de la constante ZONAS_MODALIDAD (siempre "Arequipa Provincia").
-- Ahora:
--   * `direcciones` gana `referencia` + `tarifa_envio_id` (FK a
--     `tarifas_envio`, ON DELETE SET NULL) igual que en techmarket;
--   * el costo del envío se calcula desde la ZONA de la dirección elegida;
--   * el pedido guarda un snapshot inmutable del destino:
--     `direccion_id`, `direccion_entrega`, `envio_zona`, `envio_costo`.
-- ============================================================

ALTER TABLE direcciones ADD COLUMN IF NOT EXISTS referencia VARCHAR(180) NULL AFTER direccion;
ALTER TABLE direcciones ADD COLUMN IF NOT EXISTS tarifa_envio_id INT NULL AFTER referencia;

DROP PROCEDURE IF EXISTS etapa5_fk_tarifa;

DELIMITER $$

CREATE PROCEDURE etapa5_fk_tarifa()
proc: BEGIN
  DECLARE v_existe INT DEFAULT 0;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND TABLE_NAME = 'direcciones' AND CONSTRAINT_NAME = 'fk_direcciones_tarifa';
  IF v_existe > 0 THEN
    LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tarifas_envio';
  IF v_existe = 0 THEN
    LEAVE proc; -- instalacion muy antigua sin tarifas: no se puede apuntar
  END IF;

  ALTER TABLE direcciones
    ADD CONSTRAINT fk_direcciones_tarifa
    FOREIGN KEY (tarifa_envio_id) REFERENCES tarifas_envio (id) ON DELETE SET NULL;
END$$

DELIMITER ;

CALL etapa5_fk_tarifa();
DROP PROCEDURE IF EXISTS etapa5_fk_tarifa;

-- Snapshot del destino en el pedido. TEXT porque es un texto congelado en el
-- momento de la compra (etiqueta + direccion + referencia + zona), no un FK.
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS direccion_entrega TEXT NULL AFTER direccion_id;

-- Los pedidos antiguos se quedan sin snapshot a proposito: inventar la
-- direccion con el valor actual de `users.direccion` seria falsear historial.

-- ============================================================
-- ETAPA 6 — Marketplace multi-vendedor (idempotente)
-- ------------------------------------------------------------
-- Antes todo el catálogo pertenecía a "la tienda". Ahora:
--   * `productos.vendedor_id` -> quién publica el producto (NULL = la
--     plataforma, es decir, la tienda Jeremy Tech);
--   * `detalle_pedido.vendedor_id` -> copia del dueño al momento de la
--     compra, para poder acotar "mis pedidos" a un vendedor;
--   * `users` gana la ficha comercial: `comision_porcentaje` (NULL = usa
--     la clave global `comision_marketplace_porcentaje`) y `verificado`;
--   * el reparto de dinero se congela en `comisiones` (una fila por
--     pedido y vendedor), ya existente y hasta ahora vacía.
--
-- El vendedor es un `users` con `rol = 'vendedor'`: no hay tabla nueva
-- porque las FK de `comisiones`, `pagos_vendedor` y `calificaciones_vendedor`
-- ya apuntan a `users(id)` desde la semilla original.
-- ============================================================

ALTER TABLE productos ADD COLUMN IF NOT EXISTS vendedor_id BIGINT UNSIGNED NULL AFTER garantia_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS comision_porcentaje DECIMAL(5,2) NULL DEFAULT NULL AFTER rol;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER comision_porcentaje;
ALTER TABLE detalle_pedido ADD COLUMN IF NOT EXISTS vendedor_id BIGINT UNSIGNED NULL AFTER producto_id;

-- Las FK no admiten IF NOT EXISTS: se crean desde un procedure guardado
-- que sale limpio si el constraint ya existe o si falta la tabla referida.
DROP PROCEDURE IF EXISTS etapa6_fk_vendedor;

DELIMITER $$

CREATE PROCEDURE etapa6_fk_vendedor()
proc: BEGIN
  DECLARE v_existe INT DEFAULT 0;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND CONSTRAINT_NAME = 'fk_productos_vendedor';
  IF v_existe = 0 THEN
    SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos';
    IF v_existe > 0 THEN
      ALTER TABLE productos
        ADD CONSTRAINT fk_productos_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES users (id) ON DELETE SET NULL;
    END IF;
  END IF;

  SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
   WHERE CONSTRAINT_SCHEMA = DATABASE()
     AND CONSTRAINT_NAME = 'fk_detalle_vendedor';
  IF v_existe = 0 THEN
    SELECT COUNT(*) INTO v_existe FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'detalle_pedido';
    IF v_existe > 0 THEN
      ALTER TABLE detalle_pedido
        ADD CONSTRAINT fk_detalle_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES users (id) ON DELETE SET NULL;
    END IF;
  END IF;
END$$

DELIMITER ;

CALL etapa6_fk_vendedor();
DROP PROCEDURE IF EXISTS etapa6_fk_vendedor;

CREATE INDEX IF NOT EXISTS idx_productos_vendedor ON productos (vendedor_id);
CREATE INDEX IF NOT EXISTS idx_detalle_vendedor ON detalle_pedido (vendedor_id);

-- Comisión de respaldo para los vendedores sin porcentaje propio. El UPDATE
-- solo toca el valor por defecto de la semilla ('0'), asi que una vez que el
-- administrador lo cambie a mano ya no vuelve a tocarlo.
UPDATE configuracion SET valor = '15'
 WHERE clave = 'comision_marketplace_porcentaje' AND valor = '0';

-- Vendedor de demostración (usuaria/o `vendedor` / clave `vendedor123`).
-- password: vendedor123 — respuesta de seguridad: Luna.
INSERT INTO users (name, apellidos, email, usuario, password, rol, activo,
                   verificado, comision_porcentaje, telefono, direccion,
                   pregunta_secreta, respuesta_secreta_hash)
SELECT 'Sound', 'Peru', 'vendedor@jeremytech.pe', 'vendedor',
       '$2y$12$DAvLpC1IqRUe.mxKXGSlAOrwCF.vKw0EvSj7Ip7yQbDyj3zotI0c6',
       'vendedor', 1, 1, 15.00, '997000001', 'Cercado, Arequipa',
       '¿Cuál es el nombre de tu primera mascota?',
       '$2y$12$NKjVyGkafkvm3A3QK0TLrOwajZxO9mthKrHo9dgo2PytGc9o1EQNu'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT id, usuario, email FROM users) t
     WHERE t.usuario = 'vendedor' OR t.email = 'vendedor@jeremytech.pe'
);

-- Tres productos del catálogo pasan a pertenecer al vendedor de prueba.
-- Con `AND vendedor_id IS NULL` no se pisa una reasignación hecha a mano
-- desde el panel (?ruta=admin/productos).
UPDATE productos SET vendedor_id = (
        SELECT u.id FROM (SELECT id, usuario FROM users) u
         WHERE u.usuario = 'vendedor'
    )
 WHERE slug IN ('sony-wh-1000xm5', 'bose-qc-ultra', 'jbl-charge-5')
   AND vendedor_id IS NULL;

-- Completar el dueño de las lineas de pedido ya existentes a partir del
-- producto. Solo rellena NULL: una linea historica jamás cambia de dueño.
UPDATE detalle_pedido dp
  JOIN productos p ON p.id = dp.producto_id
   SET dp.vendedor_id = p.vendedor_id
 WHERE dp.vendedor_id IS NULL;

-- ============================================================
-- ETAPA 7 — Ficha de producto: variantes y precios por volumen
-- ------------------------------------------------------------
-- Hasta aquí la tienda no tenía página de producto: solo el catálogo en
-- tarjetas. Las tablas `producto_variantes` ya existían (creadas en la
-- semilla base) pero nadie las leía, y `precios_volumen` — que sí existe
-- en la referencia techmarket — ni siquiera estaba modelada aquí.
--
-- Regla de precio (una sola, usada por la ficha, el carrito y el pedido):
--   unitario = productos.precio_pen + variante.precio_extra
--   Si la cantidad cae dentro de un tramo de volumen, el tramo SUSTITUYE el
--   precio_pen base y el extra de la variante se suma encima: el extra es un
--   añadido físico (más almacenamiento, otro color), no un descuento.
-- ============================================================

CREATE TABLE IF NOT EXISTS precios_volumen (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id     BIGINT UNSIGNED NOT NULL,
    cantidad_minima INT UNSIGNED NOT NULL,
    cantidad_maxima INT UNSIGNED DEFAULT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    creado_en       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pv_producto (producto_id, cantidad_minima),
    CONSTRAINT fk_pv_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un tramo abierto (cantidad_maxima NULL) significa "desde N en adelante" y
-- solo puede haber uno por producto: si no, el precio dependería del orden.
DELIMITER $$
DROP PROCEDURE IF EXISTS etapa7_tramo_abierto_unico$$
CREATE PROCEDURE etapa7_tramo_abierto_unico()
proc: BEGIN
    DECLARE v_filas INT DEFAULT 0;
    SELECT COUNT(*) INTO v_filas FROM (
        SELECT producto_id FROM precios_volumen
         WHERE cantidad_maxima IS NULL
         GROUP BY producto_id
        HAVING COUNT(*) > 1
    ) duplicados;
    IF v_filas = 0 THEN
        LEAVE proc;
    END IF;

    DELETE pv FROM precios_volumen pv
     WHERE pv.cantidad_maxima IS NULL
       AND pv.id NOT IN (
            SELECT MIN(id) FROM (
                SELECT MIN(id) AS id FROM precios_volumen
                 WHERE cantidad_maxima IS NULL
                 GROUP BY producto_id
            ) keep_
       );
END$$
DELIMITER ;
CALL etapa7_tramo_abierto_unico();
DROP PROCEDURE IF EXISTS etapa7_tramo_abierto_unico;

-- ------------------------------------------------------------
-- Variantes de demostración (color / capacidad).
-- El stock de la variante es independiente del del producto: es el que se
-- consulta en la ficha cuando el usuario elige una variante.
-- ------------------------------------------------------------
INSERT INTO producto_variantes (producto_id, nombre_variante, precio_extra, stock, color, activo)
SELECT p.id, v.nombre_variante, v.precio_extra, v.stock, v.color, 1
  FROM productos p
  JOIN (
        SELECT 'Titanio Natural' AS nombre_variante, 0.00 AS precio_extra, 6 AS stock, '#B0B0A0' AS color, 1 AS orden
        UNION ALL SELECT 'Titanio Azul',   0.00, 4, '#4A5A6B', 2
        UNION ALL SELECT 'Titanio Negro',  0.00, 5, '#2B2B2B', 3
  ) v ON 1 = 1
 WHERE p.slug = 'iphone-15-pro-256'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, nombre_variante FROM producto_variantes) x
         WHERE x.producto_id = p.id AND x.nombre_variante = v.nombre_variante
   );

INSERT INTO producto_variantes (producto_id, nombre_variante, precio_extra, stock, color, activo)
SELECT p.id, v.nombre_variante, v.precio_extra, v.stock, v.color, 1
  FROM productos p
  JOIN (
        SELECT 'Negro' AS nombre_variante, 0.00 AS precio_extra, 8 AS stock, '#111111' AS color, 1 AS orden
        UNION ALL SELECT 'Azul Medianoche', 0.00, 5, '#1F3A5F', 2
        UNION ALL SELECT 'Rojo',            0.00, 3, '#C0392B', 3
        UNION ALL SELECT 'Verde Bosque',    0.00, 2, '#1E6F4B', 4
  ) v ON 1 = 1
 WHERE p.slug = 'jbl-charge-5'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, nombre_variante FROM producto_variantes) x
         WHERE x.producto_id = p.id AND x.nombre_variante = v.nombre_variante
   );

INSERT INTO producto_variantes (producto_id, nombre_variante, precio_extra, stock, color, activo)
SELECT p.id, v.nombre_variante, v.precio_extra, v.stock, v.color, 1
  FROM productos p
  JOIN (
        SELECT '16 GB · 512 GB' AS nombre_variante, 2500.00 AS precio_extra, 4 AS stock, '' AS color, 1 AS orden
        UNION ALL SELECT '24 GB · 1 TB', 4500.00, 3, '', 2
  ) v ON 1 = 1
 WHERE p.slug = 'macbook-air-m3-256'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, nombre_variante FROM producto_variantes) x
         WHERE x.producto_id = p.id AND x.nombre_variante = v.nombre_variante
   );

-- ------------------------------------------------------------
-- Precios por volumen de demostración. El tramo abierto (NULL) va el
-- último: `PrecioVolumen::deProducto()` ordena por cantidad_minima.
-- ------------------------------------------------------------
INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
SELECT p.id, v.cmin, v.cmax, v.precio
  FROM productos p
  JOIN (
        SELECT 2 AS cmin, 4 AS cmax,  679.00 AS precio, 1 AS orden
        UNION ALL SELECT 5, NULL, 649.00, 2
  ) v ON 1 = 1
 WHERE p.slug = 'jbl-charge-5'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, cantidad_minima FROM precios_volumen) x
         WHERE x.producto_id = p.id AND x.cantidad_minima = v.cmin
   );

INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
SELECT p.id, v.cmin, v.cmax, v.precio
  FROM productos p
  JOIN (
        SELECT 2 AS cmin, 3 AS cmax, 2449.00 AS precio, 1 AS orden
        UNION ALL SELECT 4, NULL, 2379.00, 2
  ) v ON 1 = 1
 WHERE p.slug = 'sony-wh-1000xm5'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, cantidad_minima FROM precios_volumen) x
         WHERE x.producto_id = p.id AND x.cantidad_minima = v.cmin
   );

INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
SELECT p.id, v.cmin, v.cmax, v.precio
  FROM productos p
  JOIN (
        SELECT 2 AS cmin, 4 AS cmax, 139.00 AS precio, 1 AS orden
        UNION ALL SELECT 5, NULL, 129.00, 2
  ) v ON 1 = 1
 WHERE p.slug = 'funda-iphone-15-pro'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, cantidad_minima FROM precios_volumen) x
         WHERE x.producto_id = p.id AND x.cantidad_minima = v.cmin
   );

INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
SELECT p.id, v.cmin, v.cmax, v.precio
  FROM productos p
  JOIN (
        SELECT 2 AS cmin, 4 AS cmax, 335.00 AS precio, 1 AS orden
        UNION ALL SELECT 5, NULL, 320.00, 2
  ) v ON 1 = 1
 WHERE p.slug = 'xbox-controller'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, cantidad_minima FROM precios_volumen) x
         WHERE x.producto_id = p.id AND x.cantidad_minima = v.cmin
   );

INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
SELECT p.id, v.cmin, v.cmax, v.precio
  FROM productos p
  JOIN (
        SELECT 2 AS cmin, 4 AS cmax, 239.00 AS precio, 1 AS orden
        UNION ALL SELECT 5, NULL, 229.00, 2
  ) v ON 1 = 1
 WHERE p.slug = 'anker-powercore-20000'
   AND NOT EXISTS (
        SELECT 1 FROM (SELECT producto_id, cantidad_minima FROM precios_volumen) x
         WHERE x.producto_id = p.id AND x.cantidad_minima = v.cmin
   );

-- ============================================================
-- ETAPA 8 — Inventario y ciclo de vida del pedido (idempotente)
-- ------------------------------------------------------------
-- El checkout de la tienda registraba el pedido pero NUNCA tocaba el
-- stock: se podía vender por encima de lo disponible y nadie podía
-- auditar de dónde salió cada unidad. Además el estado del pedido era
-- un `<select>` libre: se podía saltar de "Entregado" a "Pendiente".
--
-- Esta etapa:
--   1. Añade a `movimientos_inventario` la traza que le faltaba
--      (`variante_id` y `pedido_id`) para saber QUÉ unidad salió y en
--      qué pedido. La tabla ya existía pero estaba vacía y sin usar.
--   2. El descuento de stock pasa a ocurrir dentro de la transacción
--      del pedido, con `SELECT ... FOR UPDATE` para que dos compras
--      simultáneas no puedan vender la misma última unidad.
--   3. Cancelar un pedido devuelve el stock (entrada en el inventario),
--      una sola vez, como exige la referencia techmarket.
--   4. El estado del pedido deja de ser libre: hay un recorrido
--      validado (Pendiente -> Pagado -> Enviado -> Entregado) y
--      "Cancelado" solo desde los estados que aún no se enviaron.
-- ============================================================

-- 1. Traza en movimientos_inventario. `pedido_id`/`variante_id` son
--    NULL para los ajustes manuales del almacén (que aún no tienen UI).
ALTER TABLE movimientos_inventario
    ADD COLUMN IF NOT EXISTS variante_id INT NULL AFTER producto_id,
    ADD COLUMN IF NOT EXISTS pedido_id INT NULL AFTER variante_id;

-- ADD CONSTRAINT no admite IF NOT EXISTS en MariaDB: se usa el patrón
-- con procedimiento almacenado + etiqueta `proc` y `LEAVE proc` que ya
-- usan las Etapas 2 y 6 de este archivo.
DELIMITER $$
DROP PROCEDURE IF EXISTS etapa8_add_fk_mov$$
CREATE PROCEDURE etapa8_add_fk_mov(IN p_fk VARCHAR(64), IN p_col VARCHAR(64), IN p_ref VARCHAR(64))
proc: BEGIN
    DECLARE v_existe INT DEFAULT 0;
    SELECT COUNT(*) INTO v_existe FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'movimientos_inventario'
       AND CONSTRAINT_NAME = p_fk;
    IF v_existe > 0 THEN
        LEAVE proc;
    END IF;
    SET @s := CONCAT('ALTER TABLE `movimientos_inventario` ADD CONSTRAINT `', p_fk,
                     '` FOREIGN KEY (', p_col, ') REFERENCES ', p_ref, ' ON DELETE SET NULL');
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
END$$
DELIMITER ;
CALL etapa8_add_fk_mov('fk_mov_variante', 'variante_id', 'producto_variantes (id)');
CALL etapa8_add_fk_mov('fk_mov_pedido',   'pedido_id',   'pedidos (id)');
DROP PROCEDURE IF EXISTS etapa8_add_fk_mov;

-- Índice para el listado de movimientos de un pedido concreto.
CREATE INDEX IF NOT EXISTS idx_mov_pedido ON movimientos_inventario (pedido_id);
CREATE INDEX IF NOT EXISTS idx_mov_creado ON movimientos_inventario (creado_en);
