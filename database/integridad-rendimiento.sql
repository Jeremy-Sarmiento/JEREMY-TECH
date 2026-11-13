-- PostgreSQL 14+. Migracion para una BD existente; no importar la estructura.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/integridad-rendimiento.sql
-- Requiere psql (usa \gexec), autocommit y un solo operador. NO usar BEGIN,
-- --single-transaction / -1 ni PDO::exec con el archivo completo: CONCURRENTLY
-- debe ejecutarse como sentencia independiente fuera de una transaccion.
-- No borra tablas/filas, no repara datos previos ni valida automaticamente.
-- Cada ALTER toma brevemente un bloqueo de metadatos; lock_timeout evita
-- esperar indefinidamente. Si falla, resolver la contencion y volver a ejecutar.
-- Guardar antes pg_indexes y pg_constraint para el rollback. Si estos nombres
-- ya existen por cambios externos, verificar sus definiciones: no se reemplazan.
\set ON_ERROR_STOP on
SET lock_timeout = '3s';

-- Los compuestos sustituyen la necesidad de indices simples en sus primeras
-- columnas. UNIQUE ya cubre carritos(cliente_id), resenas(producto_id,cliente_id)
-- y comisiones(pedido_id,vendedor_id); no se duplican esos indices.
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pagos_pedido_id_desc ON public.pagos (pedido_id, id DESC);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pedidos_cliente_id_desc ON public.pedidos (cliente_id, id DESC);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_detalle_pedido_pedido ON public.detalle_pedido (pedido_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_detalle_pedido_producto ON public.detalle_pedido (producto_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_detalle_pedido_variante ON public.detalle_pedido (variante_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pedidos_cupon ON public.pedidos (cupon_id);
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_pedidos_direccion ON public.pedidos (direccion_id);
-- El indice existente (producto_id,estado) filtra pero no satisface este orden.
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_resenas_visibles_recientes ON public.resenas (producto_id, creado_en DESC, id DESC) WHERE estado = 'visible';

-- Un CREATE INDEX CONCURRENTLY interrumpido puede dejar un indice INVALID.
-- IF NOT EXISTS no lo reconstruye: detenerse, inspeccionarlo, y solo si esta
-- invalido hacer DROP INDEX CONCURRENTLY public.<nombre>; despues reintentar.
DO $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM pg_index i JOIN pg_class c ON c.oid = i.indexrelid
    WHERE c.relnamespace = 'public'::regnamespace AND NOT i.indisvalid
      AND c.relname IN ('idx_pagos_pedido_id_desc', 'idx_pedidos_cliente_id_desc',
        'idx_detalle_pedido_pedido', 'idx_detalle_pedido_producto', 'idx_detalle_pedido_variante',
        'idx_pedidos_cupon', 'idx_pedidos_direccion', 'idx_resenas_visibles_recientes')
  ) THEN
    RAISE EXCEPTION 'Indice de integridad/rendimiento invalido: inspeccionar pg_index y reconstruir antes de continuar';
  END IF;
END;
$$;

-- NOT VALID conserva filas historicas invalidas, pero exige integridad en
-- nuevas escrituras. CHECK tambien revisa un UPDATE de otra columna de una
-- fila antigua: inventariar esas filas y decidir su tratamiento antes de validar.
-- El maximo coincide con numeric(10,2) y tambien rechaza NaN (>= 0 no basta).
-- No se imponen igualdades de totales/redondeo ni usos_actuales <= usos_maximos:
-- el admin puede reducir el limite de un cupon sin alterar usos ya registrados.
-- \gexec envia cada ALTER por separado para liberar sus bloqueos al terminar.
SELECT format('ALTER TABLE public.%I ADD CONSTRAINT %I %s NOT VALID;', tabla, nombre, definicion)
FROM (VALUES
  ('calificaciones_vendedor', 'ck_calificaciones_vendedor_rango', 'CHECK (calificacion BETWEEN 1 AND 5)'),
  ('carrito_items', 'ck_carrito_items_cantidad', 'CHECK (cantidad > 0)'),
  ('comisiones', 'ck_comisiones_montos', 'CHECK (monto_venta BETWEEN 0 AND 99999999.99 AND monto_comision BETWEEN 0 AND 99999999.99 AND monto_vendedor BETWEEN 0 AND 99999999.99)'),
  ('comisiones', 'ck_comisiones_porcentaje', 'CHECK (porcentaje_comision BETWEEN 0 AND 100)'),
  ('cupones', 'ck_cupones_valor', 'CHECK (valor > 0 AND valor <= 99999999.99 AND (tipo <> ''porcentaje'' OR valor <= 100))'),
  ('cupones', 'ck_cupones_usos', 'CHECK (usos_actuales >= 0 AND (usos_maximos IS NULL OR usos_maximos >= 0))'),
  ('detalle_pedido', 'ck_detalle_pedido_cantidad', 'CHECK (cantidad > 0)'),
  ('detalle_pedido', 'ck_detalle_pedido_precio', 'CHECK (precio_unitario_pen BETWEEN 0 AND 99999999.99)'),
  ('garantias', 'ck_garantias_precio', 'CHECK (precio_pen BETWEEN 0 AND 99999999.99)'),
  ('movimientos_inventario', 'ck_movimientos_inventario_cantidad', 'CHECK (cantidad > 0)'),
  ('pagos', 'ck_pagos_monto', 'CHECK (monto_pen BETWEEN 0 AND 99999999.99)'),
  ('pagos_vendedor', 'ck_pagos_vendedor_monto', 'CHECK (monto BETWEEN 0 AND 99999999.99)'),
  ('pedidos', 'ck_pedidos_montos', 'CHECK (total_pen BETWEEN 0 AND 99999999.99 AND subtotal BETWEEN 0 AND 99999999.99 AND igv_monto BETWEEN 0 AND 99999999.99 AND envio_costo BETWEEN 0 AND 99999999.99 AND descuento_aplicado BETWEEN 0 AND 99999999.99)'),
  ('precios_volumen', 'ck_precios_volumen_cantidades', 'CHECK (cantidad_minima > 0 AND (cantidad_maxima IS NULL OR cantidad_maxima >= cantidad_minima))'),
  ('precios_volumen', 'ck_precios_volumen_precio', 'CHECK (precio_unitario BETWEEN 0 AND 99999999.99)'),
  ('producto_variantes', 'ck_producto_variantes_stock', 'CHECK (stock >= 0)'),
  ('producto_variantes', 'ck_producto_variantes_precio', 'CHECK (precio_extra BETWEEN 0 AND 99999999.99)'),
  ('productos', 'ck_productos_stock', 'CHECK (stock >= 0 AND stock_minimo >= 0)'),
  ('productos', 'ck_productos_precio', 'CHECK (precio_pen BETWEEN 0 AND 99999999.99)'),
  ('resenas', 'ck_resenas_calificacion', 'CHECK (calificacion BETWEEN 1 AND 5)'),
  ('tarifas_envio', 'ck_tarifas_envio_costo', 'CHECK (costo BETWEEN 0 AND 99999999.99)'),
  ('usuarios', 'ck_usuarios_comision', 'CHECK (comision_porcentaje BETWEEN 0 AND 100)'),
  -- Las cuentas usan baja logica. El inventario necesita el producto al devolver
  -- stock: con ventas se archiva (ProductoVendedor::eliminar), no se borra.
  -- NO CASCADE en pedidos, detalles o pagos: nunca eliminar dinero/historial.
  -- DEFERRABLE permite importar el seed sin reordenarlo: psql -1 con
  -- -c 'SET CONSTRAINTS ALL DEFERRED' -f database/jeremy_tech_datos.sql.
  -- INITIALLY IMMEDIATE mantiene las comprobaciones normales por sentencia.
  ('pedidos', 'fk_pedidos_cliente', 'FOREIGN KEY (cliente_id) REFERENCES public.usuarios (id) DEFERRABLE INITIALLY IMMEDIATE'),
  ('detalle_pedido', 'fk_detalle_pedido', 'FOREIGN KEY (pedido_id) REFERENCES public.pedidos (id) DEFERRABLE INITIALLY IMMEDIATE'),
  ('detalle_pedido', 'fk_detalle_producto', 'FOREIGN KEY (producto_id) REFERENCES public.productos (id) DEFERRABLE INITIALLY IMMEDIATE'),
  ('pagos', 'fk_pagos_pedido', 'FOREIGN KEY (pedido_id) REFERENCES public.pedidos (id) DEFERRABLE INITIALLY IMMEDIATE'),
  -- Estas columnas ya son nullable. Sus snapshots (nombre/precio, descuento,
  -- direccion_entrega) permanecen. fk_mov_variante sigue bloqueando variantes
  -- con movimientos: no se relajan restricciones existentes de inventario.
  ('detalle_pedido', 'fk_detalle_variante', 'FOREIGN KEY (variante_id) REFERENCES public.producto_variantes (id) ON DELETE SET NULL DEFERRABLE INITIALLY IMMEDIATE'),
  ('pedidos', 'fk_pedidos_cupon', 'FOREIGN KEY (cupon_id) REFERENCES public.cupones (id) ON DELETE SET NULL DEFERRABLE INITIALLY IMMEDIATE'),
  ('pedidos', 'fk_pedidos_direccion', 'FOREIGN KEY (direccion_id) REFERENCES public.direcciones (id) ON DELETE SET NULL DEFERRABLE INITIALLY IMMEDIATE')
) AS reglas(tabla, nombre, definicion)
WHERE NOT EXISTS (
  SELECT 1 FROM pg_constraint
  WHERE conrelid = format('public.%I', tabla)::regclass AND conname = nombre
)
\gexec

RESET lock_timeout;

-- VALIDACION DIFERIDA (manual, nunca como reparacion automatica):
-- 1. Revisar con SELECT los CHECK incumplidos y LEFT JOIN ... WHERE padre.id
--    IS NULL para huerfanos. Exportar evidencia; no borrar ni reasignar historial.
-- 2. Acordar con negocio/contabilidad cualquier correccion; respaldarla aparte.
-- 3. Con esa decision, validar individualmente en ventana de baja carga:
--      ALTER TABLE public.productos VALIDATE CONSTRAINT ck_productos_stock;
--      ALTER TABLE public.pagos VALIDATE CONSTRAINT fk_pagos_pedido;
--    Lista pendiente: SELECT conrelid::regclass, conname FROM pg_constraint
--    WHERE connamespace = 'public'::regnamespace AND NOT convalidated;
-- 4. ANALYZE de las tablas afectadas y EXPLAIN (ANALYZE, BUFFERS) de listados
--    en una copia representativa; no se prometen tiempos ni uso forzado del indice.

-- ROLLBACK MANUAL: ejecutar SOLO si estos objetos fueron creados por esta
-- migracion (no quitar objetos preexistentes); conservar el inventario previo
-- de pg_constraint/pg_indexes para distinguirlos. No elimina ni restaura filas.
-- Los ids puestos a NULL por borrados posteriores no se reconstruyen al bajar.
-- Usar psql con autocommit, ON_ERROR_STOP y lock_timeout igual que en UP.
/*
SET lock_timeout = '3s';
ALTER TABLE public.calificaciones_vendedor DROP CONSTRAINT IF EXISTS ck_calificaciones_vendedor_rango;
ALTER TABLE public.carrito_items DROP CONSTRAINT IF EXISTS ck_carrito_items_cantidad;
ALTER TABLE public.comisiones DROP CONSTRAINT IF EXISTS ck_comisiones_montos, DROP CONSTRAINT IF EXISTS ck_comisiones_porcentaje;
ALTER TABLE public.cupones DROP CONSTRAINT IF EXISTS ck_cupones_valor, DROP CONSTRAINT IF EXISTS ck_cupones_usos;
ALTER TABLE public.detalle_pedido DROP CONSTRAINT IF EXISTS ck_detalle_pedido_cantidad, DROP CONSTRAINT IF EXISTS ck_detalle_pedido_precio,
  DROP CONSTRAINT IF EXISTS fk_detalle_pedido, DROP CONSTRAINT IF EXISTS fk_detalle_producto, DROP CONSTRAINT IF EXISTS fk_detalle_variante;
ALTER TABLE public.garantias DROP CONSTRAINT IF EXISTS ck_garantias_precio;
ALTER TABLE public.movimientos_inventario DROP CONSTRAINT IF EXISTS ck_movimientos_inventario_cantidad;
ALTER TABLE public.pagos DROP CONSTRAINT IF EXISTS ck_pagos_monto, DROP CONSTRAINT IF EXISTS fk_pagos_pedido;
ALTER TABLE public.pagos_vendedor DROP CONSTRAINT IF EXISTS ck_pagos_vendedor_monto;
ALTER TABLE public.pedidos DROP CONSTRAINT IF EXISTS ck_pedidos_montos, DROP CONSTRAINT IF EXISTS fk_pedidos_cliente,
  DROP CONSTRAINT IF EXISTS fk_pedidos_cupon, DROP CONSTRAINT IF EXISTS fk_pedidos_direccion;
ALTER TABLE public.precios_volumen DROP CONSTRAINT IF EXISTS ck_precios_volumen_cantidades, DROP CONSTRAINT IF EXISTS ck_precios_volumen_precio;
ALTER TABLE public.producto_variantes DROP CONSTRAINT IF EXISTS ck_producto_variantes_stock, DROP CONSTRAINT IF EXISTS ck_producto_variantes_precio;
ALTER TABLE public.productos DROP CONSTRAINT IF EXISTS ck_productos_stock, DROP CONSTRAINT IF EXISTS ck_productos_precio;
ALTER TABLE public.resenas DROP CONSTRAINT IF EXISTS ck_resenas_calificacion;
ALTER TABLE public.tarifas_envio DROP CONSTRAINT IF EXISTS ck_tarifas_envio_costo;
ALTER TABLE public.usuarios DROP CONSTRAINT IF EXISTS ck_usuarios_comision;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_pagos_pedido_id_desc;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_pedidos_cliente_id_desc;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_detalle_pedido_pedido;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_detalle_pedido_producto;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_detalle_pedido_variante;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_pedidos_cupon;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_pedidos_direccion;
DROP INDEX CONCURRENTLY IF EXISTS public.idx_resenas_visibles_recientes;
RESET lock_timeout;
*/
