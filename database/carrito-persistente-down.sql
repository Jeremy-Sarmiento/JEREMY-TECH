-- PostgreSQL 14+. Rollback idempotente; nunca borra, fusiona ni reasigna filas.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/carrito-persistente-down.sql
-- También admite PDO::exec del archivo completo, SIN una transacción exterior.
-- Pausar el código que usa variante_id antes de ejecutar y conservar un respaldo.
-- Rehúsa continuar si perdería identidad de variantes o unicidad del esquema viejo.
-- ACCESS EXCLUSIVE evita escrituras entre las guardas y el cambio del esquema.
-- lock_timeout limita cada espera de bloqueo a 3s, NO la duración del trabajo.
-- Si falla, hacer ROLLBACK (PDO): no capturar el error para continuar el despliegue.

BEGIN;
SET LOCAL lock_timeout = '3s';
LOCK TABLE public.carrito_items IN ACCESS EXCLUSIVE MODE;

DO $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM pg_attribute
    WHERE attrelid = 'public.carrito_items'::regclass
      AND attname = 'variante_id' AND NOT attisdropped
  ) THEN
    IF EXISTS (SELECT 1 FROM public.carrito_items WHERE variante_id IS NOT NULL) THEN
      RAISE EXCEPTION USING ERRCODE = '23514',
        MESSAGE = 'No se puede revertir carrito: hay variantes cuya identidad se perdería';
    END IF;
  END IF;

  IF EXISTS (
    SELECT 1 FROM public.carrito_items GROUP BY carrito_id, producto_id HAVING COUNT(*) > 1
  ) THEN
    RAISE EXCEPTION USING ERRCODE = '23514',
      MESSAGE = 'No se puede revertir carrito: hay líneas duplicadas para carrito/producto';
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'public.carrito_items'::regclass AND conname = 'uq_carrito_items_carrito_item_unico'
  ) THEN
    ALTER TABLE public.carrito_items ADD CONSTRAINT uq_carrito_items_carrito_item_unico
      UNIQUE (carrito_id, producto_id);
  END IF;
END;
$$;

DROP INDEX IF EXISTS public.uq_carrito_items_linea;
DROP INDEX IF EXISTS public.idx_carrito_items_variante;
ALTER TABLE public.carrito_items
  DROP CONSTRAINT IF EXISTS fk_caritem_variante,
  DROP CONSTRAINT IF EXISTS ck_carrito_items_variante,
  DROP COLUMN IF EXISTS variante_id;

COMMIT;
