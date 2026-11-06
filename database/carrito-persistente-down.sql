-- MariaDB 10.2+. Rollback idempotente; nunca borra, fusiona ni reasigna filas.
-- Uso: mariadb -u root -p jeremy_tech < database/carrito-persistente-down.sql
-- También admite PDO::exec del archivo completo.
-- Pausar el código que usa variante_id antes de ejecutar y conservar un respaldo.
-- Rehúsa continuar si perdería identidad de variantes o unicidad del esquema viejo.
-- Si falla, la migración no cambia nada.

-- Un CHECK temporal solo permite variantes nulas: si hay alguna, la sentencia
-- falla antes de tocar el esquema y la migración se detiene.
SET @jt_guard = IF(
  (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'carrito_items'
      AND column_name = 'variante_id') > 0,
  'ALTER TABLE carrito_items ADD CONSTRAINT jt_down_guard_variante CHECK (variante_id IS NULL)',
  'DO 0'
);
PREPARE jt_stmt FROM @jt_guard;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;
ALTER TABLE carrito_items DROP CONSTRAINT IF EXISTS jt_down_guard_variante;

-- El índice legacy también rechaza duplicados (1062) sin borrar ni fusionar.
CREATE UNIQUE INDEX IF NOT EXISTS carrito_item_unico
  ON carrito_items (carrito_id, producto_id);

-- La clave foránea usa idx_carrito_items_variante: quitarla antes del índice.
ALTER TABLE carrito_items DROP FOREIGN KEY IF EXISTS fk_caritem_variante;
DROP INDEX IF EXISTS uq_carrito_items_linea ON carrito_items;
DROP INDEX IF EXISTS idx_carrito_items_variante ON carrito_items;
ALTER TABLE carrito_items DROP CONSTRAINT IF EXISTS ck_carrito_items_variante;
ALTER TABLE carrito_items DROP COLUMN IF EXISTS variante_clave;
ALTER TABLE carrito_items DROP COLUMN IF EXISTS variante_id;
