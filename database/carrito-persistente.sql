-- MariaDB 10.2+. Upgrade idempotente de las tablas existentes; no elimina filas.
-- Uso: mariadb -u root -p jeremy_tech < database/carrito-persistente.sql
-- También admite PDO::exec del archivo completo.
-- Ejecutar antes del código que utiliza variante_id, con las escrituras pausadas.
-- Los nombres reservados por esta migración deben conservar sus definiciones.

ALTER TABLE carrito_items ADD COLUMN IF NOT EXISTS variante_id int(11) NULL;

ALTER TABLE carrito_items ADD CONSTRAINT IF NOT EXISTS ck_carrito_items_variante
  CHECK (variante_id IS NULL OR variante_id > 0);

-- Borrar una variante quita su línea; nunca la convierte en producto base.
-- MariaDB no admite IF NOT EXISTS en FOREIGN KEY: se comprueba el catálogo.
SET @jt_fk = IF(
  (SELECT COUNT(*) FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE() AND table_name = 'carrito_items'
      AND constraint_name = 'fk_caritem_variante') > 0,
  'DO 0',
  'ALTER TABLE carrito_items ADD CONSTRAINT fk_caritem_variante
     FOREIGN KEY (variante_id) REFERENCES producto_variantes (id) ON DELETE CASCADE'
);
PREPARE jt_stmt FROM @jt_fk;
EXECUTE jt_stmt;
DEALLOCATE PREPARE jt_stmt;

CREATE INDEX IF NOT EXISTS idx_carrito_items_variante ON carrito_items (variante_id);

-- Columna generada: MariaDB no tiene índices funcionales y el NULL de la
-- variante base no debe duplicar líneas.
ALTER TABLE carrito_items ADD COLUMN IF NOT EXISTS variante_clave int(11)
  AS (COALESCE(variante_id, 0)) STORED;

CREATE UNIQUE INDEX IF NOT EXISTS uq_carrito_items_linea
  ON carrito_items (carrito_id, producto_id, variante_clave);

ALTER TABLE carrito_items DROP INDEX IF EXISTS carrito_item_unico;
