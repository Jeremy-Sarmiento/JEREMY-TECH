-- MariaDB 10.2+. Migración para una BD existente; no importar la estructura.
-- Uso: mysql -u root -p jeremy_tech < database/integridad-rendimiento.sql
-- También admite PDO::exec del archivo completo.
-- No borra tablas/filas ni repara datos previos.
-- MariaDB valida las filas existentes al agregar un CHECK: si el historial ya
-- tiene valores fuera de rango, la migración se detiene y hay que decidir su
-- tratamiento antes de reintentar. No corrige nada por su cuenta.
-- Los nombres de esta migración deben conservar sus definiciones.

-- Los compuestos sustituyen la necesidad de índices simples en sus primeras
-- columnas. UNIQUE ya cubre carritos(cliente_id), resenas(producto_id,cliente_id)
-- y comisiones(pedido_id,vendedor_id); no se duplican esos índices.
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

-- ROLLBACK MANUAL: ejecutar SOLO si estos objetos fueron creados por esta
-- migración (no quitar objetos preexistentes); conservar el inventario previo
-- de information_schema para distinguirlos. No elimina ni restaura filas.
-- Los ids puestos a NULL por borrados posteriores no se reconstruyen al bajar.
/*
ALTER TABLE calificaciones_vendedor DROP CONSTRAINT IF EXISTS ck_calificaciones_vendedor_rango;
ALTER TABLE carrito_items DROP CONSTRAINT IF EXISTS ck_carrito_items_cantidad;
ALTER TABLE comisiones DROP CONSTRAINT IF EXISTS ck_comisiones_montos, DROP CONSTRAINT IF EXISTS ck_comisiones_porcentaje;
ALTER TABLE cupones DROP CONSTRAINT IF EXISTS ck_cupones_valor, DROP CONSTRAINT IF EXISTS ck_cupones_usos;
ALTER TABLE detalle_pedido DROP CONSTRAINT IF EXISTS ck_detalle_pedido_cantidad, DROP CONSTRAINT IF EXISTS ck_detalle_pedido_precio,
  DROP CONSTRAINT IF EXISTS fk_detalle_pedido, DROP CONSTRAINT IF EXISTS fk_detalle_producto, DROP CONSTRAINT IF EXISTS fk_detalle_variante;
ALTER TABLE garantias DROP CONSTRAINT IF EXISTS ck_garantias_precio;
ALTER TABLE movimientos_inventario DROP CONSTRAINT IF EXISTS ck_movimientos_inventario_cantidad;
ALTER TABLE pagos DROP CONSTRAINT IF EXISTS ck_pagos_monto, DROP CONSTRAINT IF EXISTS fk_pagos_pedido;
ALTER TABLE pagos_vendedor DROP CONSTRAINT IF EXISTS ck_pagos_vendedor_monto;
ALTER TABLE pedidos DROP CONSTRAINT IF EXISTS ck_pedidos_montos, DROP CONSTRAINT IF EXISTS fk_pedidos_cliente,
  DROP CONSTRAINT IF EXISTS fk_pedidos_cupon, DROP CONSTRAINT IF EXISTS fk_pedidos_direccion;
ALTER TABLE precios_volumen DROP CONSTRAINT IF EXISTS ck_precios_volumen_cantidades, DROP CONSTRAINT IF EXISTS ck_precios_volumen_precio;
ALTER TABLE producto_variantes DROP CONSTRAINT IF EXISTS ck_producto_variantes_stock, DROP CONSTRAINT IF EXISTS ck_producto_variantes_precio;
ALTER TABLE productos DROP CONSTRAINT IF EXISTS ck_productos_stock, DROP CONSTRAINT IF EXISTS ck_productos_precio;
ALTER TABLE resenas DROP CONSTRAINT IF EXISTS ck_resenas_calificacion;
ALTER TABLE tarifas_envio DROP CONSTRAINT IF EXISTS ck_tarifas_envio_costo;
ALTER TABLE users DROP CONSTRAINT IF EXISTS ck_users_comision;
DROP INDEX IF EXISTS idx_pagos_pedido_id_desc ON pagos;
DROP INDEX IF EXISTS idx_pedidos_cliente_id_desc ON pedidos;
DROP INDEX IF EXISTS idx_detalle_pedido_pedido ON detalle_pedido;
DROP INDEX IF EXISTS idx_detalle_pedido_producto ON detalle_pedido;
DROP INDEX IF EXISTS idx_detalle_pedido_variante ON detalle_pedido;
DROP INDEX IF EXISTS idx_pedidos_cupon ON pedidos;
DROP INDEX IF EXISTS idx_pedidos_direccion ON pedidos;
DROP INDEX IF EXISTS idx_resenas_visibles_recientes ON resenas;
*/
