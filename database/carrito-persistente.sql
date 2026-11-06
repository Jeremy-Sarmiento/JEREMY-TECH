-- PostgreSQL 14+. Upgrade idempotente de las tablas existentes; no elimina filas.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/carrito-persistente.sql
-- También admite PDO::exec del archivo completo, SIN una transacción exterior.
-- Ejecutar antes del código que utiliza variante_id, con las escrituras pausadas.
-- Las tablas aún sin uso admiten esta ventana corta: ALTER bloquea carrito_items
-- hasta COMMIT y los índices se crean dentro de la misma transacción, no CONCURRENTLY.
-- lock_timeout limita cada espera de bloqueo a 3s, NO la duración del trabajo.
-- shortcut: tablas pequeñas y sin uso, separar índices CONCURRENTLY si crecen.
-- Si falla, hacer ROLLBACK (PDO) y resolver la causa antes de reintentar.
-- Los nombres reservados por esta migración deben conservar sus definiciones.

BEGIN;
SET LOCAL lock_timeout = '3s';

ALTER TABLE public.carrito_items ADD COLUMN IF NOT EXISTS variante_id integer NULL;

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'public.carrito_items'::regclass AND conname = 'ck_carrito_items_variante'
  ) THEN
    ALTER TABLE public.carrito_items ADD CONSTRAINT ck_carrito_items_variante
      CHECK (variante_id IS NULL OR variante_id > 0);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'public.carrito_items'::regclass AND conname = 'fk_caritem_variante'
  ) THEN
    -- Borrar una variante quita su línea; nunca la convierte en producto base.
    ALTER TABLE public.carrito_items ADD CONSTRAINT fk_caritem_variante
      FOREIGN KEY (variante_id) REFERENCES public.producto_variantes (id) ON DELETE CASCADE;
  END IF;
END;
$$;

CREATE INDEX IF NOT EXISTS idx_carrito_items_variante ON public.carrito_items (variante_id);
-- 0 es solo sentinel del índice: el CHECK impide almacenarlo como variante.
CREATE UNIQUE INDEX IF NOT EXISTS uq_carrito_items_linea
  ON public.carrito_items (carrito_id, producto_id, COALESCE(variante_id, 0));

ALTER TABLE public.carrito_items DROP CONSTRAINT IF EXISTS uq_carrito_items_carrito_item_unico;

COMMIT;
