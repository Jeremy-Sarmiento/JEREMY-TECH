-- PostgreSQL 14+. Renombra al español las tablas y columnas de esta tanda.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/renombrar-espanol.sql
-- Tambien se puede pegar en pgAdmin/DBeaver: no usa meta-comandos de psql.
-- Es idempotente: si un objeto ya tiene el nombre nuevo (o no existe el viejo),
-- lo omite en vez de fallar. Solo cambia metadatos; no copia ni borra filas.
-- Aplicar junto con el despliegue del codigo nuevo: los nombres viejos dejan
-- de existir en el acto.
SET lock_timeout = '3s';

BEGIN;

-- 1. Tablas ----------------------------------------------------------------
DO $$
BEGIN
  IF to_regclass('public.users') IS NOT NULL
     AND to_regclass('public.usuarios') IS NULL THEN
    ALTER TABLE public.users RENAME TO usuarios;
  END IF;

  IF to_regclass('public.pagos_webhooks') IS NOT NULL
     AND to_regclass('public.pagos_eventos') IS NULL THEN
    ALTER TABLE public.pagos_webhooks RENAME TO pagos_eventos;
  END IF;
END
$$;

-- 2. Columnas --------------------------------------------------------------
DO $$
DECLARE
  par record;
BEGIN
  FOR par IN SELECT * FROM (VALUES
    ('usuarios',      'name',       'nombre'),
    ('usuarios',      'created_at', 'creado_en'),
    ('usuarios',      'updated_at', 'actualizado_en'),
    ('usuarios',      'deleted_at', 'eliminado_en'),
    ('productos',     'created_at', 'creado_en'),
    ('productos',     'updated_at', 'actualizado_en'),
    ('pedidos',       'courier',    'mensajeria'),
    ('pagos_eventos', 'payload',    'contenido')
  ) AS t(tabla, vieja, nueva)
  LOOP
    IF EXISTS (SELECT 1 FROM information_schema.columns
                WHERE table_schema = 'public' AND table_name = par.tabla
                  AND column_name = par.vieja)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                        WHERE table_schema = 'public' AND table_name = par.tabla
                          AND column_name = par.nueva) THEN
      EXECUTE format('ALTER TABLE public.%I RENAME COLUMN %I TO %I',
                     par.tabla, par.vieja, par.nueva);
    END IF;
  END LOOP;
END
$$;

-- 3. Disparadores de actualizado_en ---------------------------------------
-- Los cuerpos plpgsql no se reescriben con RENAME: se recrean los disparadores
-- para que sigan apuntando a la columna renombrada.
CREATE OR REPLACE FUNCTION public.touch_actualizado_en() RETURNS trigger AS $$
BEGIN
  NEW.actualizado_en = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE
  t record;
BEGIN
  IF to_regclass('public.usuarios') IS NOT NULL THEN
    FOR t IN
      SELECT tr.tgname
        FROM pg_trigger tr
        JOIN pg_class c ON c.oid = tr.tgrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
       WHERE n.nspname = 'public' AND c.relname = 'usuarios' AND NOT tr.tgisinternal
         AND (tr.tgname IN ('trg_users_updated_at', 'trg_usuarios_actualizado_en')
              OR tr.tgfoid IN (SELECT p.oid
                                 FROM pg_proc p
                                 JOIN pg_namespace pn ON pn.oid = p.pronamespace
                                WHERE pn.nspname = 'public'
                                  AND p.proname IN ('touch_updated_at', 'touch_actualizado_en')))
    LOOP
      EXECUTE format('DROP TRIGGER %I ON public.usuarios', t.tgname);
    END LOOP;
    EXECUTE 'CREATE TRIGGER trg_usuarios_actualizado_en BEFORE UPDATE ON public.usuarios
             FOR EACH ROW EXECUTE FUNCTION public.touch_actualizado_en()';
  END IF;

  IF to_regclass('public.productos') IS NOT NULL THEN
    FOR t IN
      SELECT tr.tgname
        FROM pg_trigger tr
        JOIN pg_class c ON c.oid = tr.tgrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
       WHERE n.nspname = 'public' AND c.relname = 'productos' AND NOT tr.tgisinternal
         AND (tr.tgname IN ('trg_productos_updated_at', 'trg_productos_actualizado_en')
              OR tr.tgfoid IN (SELECT p.oid
                                 FROM pg_proc p
                                 JOIN pg_namespace pn ON pn.oid = p.pronamespace
                                WHERE pn.nspname = 'public'
                                  AND p.proname IN ('touch_updated_at', 'touch_actualizado_en')))
    LOOP
      EXECUTE format('DROP TRIGGER %I ON public.productos', t.tgname);
    END LOOP;
    EXECUTE 'CREATE TRIGGER trg_productos_actualizado_en BEFORE UPDATE ON public.productos
             FOR EACH ROW EXECUTE FUNCTION public.touch_actualizado_en()';
  END IF;

  -- La funcion vieja solo se elimina si ya no queda ningun disparador que la use.
  IF EXISTS (SELECT 1 FROM pg_proc p
               JOIN pg_namespace n ON n.oid = p.pronamespace
              WHERE n.nspname = 'public' AND p.proname = 'touch_updated_at')
     AND NOT EXISTS (SELECT 1 FROM pg_trigger tr
                       JOIN pg_proc p ON p.oid = tr.tgfoid
                       JOIN pg_namespace n ON n.oid = p.pronamespace
                      WHERE n.nspname = 'public' AND p.proname = 'touch_updated_at'
                        AND NOT tr.tgisinternal) THEN
    DROP FUNCTION public.touch_updated_at();
  END IF;
END
$$;

-- 4. Constraints -----------------------------------------------------------
DO $$
DECLARE
  par record;
BEGIN
  FOR par IN SELECT * FROM (VALUES
    ('usuarios',      'uq_users_users_email_unique',       'uq_usuarios_email'),
    ('usuarios',      'uq_users_users_usuario_unique',     'uq_usuarios_usuario'),
    ('usuarios',      'ck_users_comision',                 'ck_usuarios_comision'),
    ('pagos_eventos', 'uq_pagos_webhooks_uq_webhook_evento', 'uq_pagos_eventos_evento')
  ) AS t(tabla, vieja, nueva)
  LOOP
    IF EXISTS (SELECT 1 FROM pg_constraint co
                 JOIN pg_class c ON c.oid = co.conrelid
                 JOIN pg_namespace n ON n.oid = c.relnamespace
                WHERE n.nspname = 'public' AND c.relname = par.tabla
                  AND co.conname = par.vieja)
       AND NOT EXISTS (SELECT 1 FROM pg_constraint co
                         JOIN pg_class c ON c.oid = co.conrelid
                         JOIN pg_namespace n ON n.oid = c.relnamespace
                        WHERE n.nspname = 'public' AND c.relname = par.tabla
                          AND co.conname = par.nueva) THEN
      EXECUTE format('ALTER TABLE public.%I RENAME CONSTRAINT %I TO %I',
                     par.tabla, par.vieja, par.nueva);
    END IF;
  END LOOP;
END
$$;

-- 5. Indices ---------------------------------------------------------------
DO $$
DECLARE
  par record;
BEGIN
  FOR par IN SELECT * FROM (VALUES
    ('idx_users_users_rol_index',                 'idx_usuarios_rol'),
    ('idx_pagos_webhooks_idx_webhook_pedido',     'idx_pagos_eventos_pedido'),
    ('idx_pagos_webhooks_idx_webhook_pago',       'idx_pagos_eventos_pago')
  ) AS t(vieja, nueva)
  LOOP
    IF to_regclass(format('public.%I', par.vieja)) IS NOT NULL
       AND to_regclass(format('public.%I', par.nueva)) IS NULL THEN
      EXECUTE format('ALTER INDEX public.%I RENAME TO %I', par.vieja, par.nueva);
    END IF;
  END LOOP;
END
$$;

COMMIT;
