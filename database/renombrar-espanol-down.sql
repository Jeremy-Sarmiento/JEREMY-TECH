-- PostgreSQL 14+. Revierte database/renombrar-espanol.sql.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/renombrar-espanol-down.sql
-- Tambien se puede pegar en pgAdmin/DBeaver: no usa meta-comandos de psql.
-- Es idempotente: si un objeto ya tiene el nombre viejo, lo omite.
SET lock_timeout = '3s';

BEGIN;

-- 1. Indices ---------------------------------------------------------------
DO $$
DECLARE
  par record;
BEGIN
  FOR par IN SELECT * FROM (VALUES
    ('idx_usuarios_rol',             'idx_users_users_rol_index'),
    ('idx_pagos_eventos_pedido',     'idx_pagos_webhooks_idx_webhook_pedido'),
    ('idx_pagos_eventos_pago',       'idx_pagos_webhooks_idx_webhook_pago')
  ) AS t(vieja, nueva)
  LOOP
    IF to_regclass(format('public.%I', par.vieja)) IS NOT NULL
       AND to_regclass(format('public.%I', par.nueva)) IS NULL THEN
      EXECUTE format('ALTER INDEX public.%I RENAME TO %I', par.vieja, par.nueva);
    END IF;
  END LOOP;
END
$$;

-- 2. Constraints -----------------------------------------------------------
DO $$
DECLARE
  par record;
BEGIN
  FOR par IN SELECT * FROM (VALUES
    ('usuarios',      'uq_usuarios_email',            'uq_users_users_email_unique'),
    ('usuarios',      'uq_usuarios_usuario',          'uq_users_users_usuario_unique'),
    ('usuarios',      'ck_usuarios_comision',         'ck_users_comision'),
    ('pagos_eventos', 'uq_pagos_eventos_evento',      'uq_pagos_webhooks_uq_webhook_evento')
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

-- 3. Columnas --------------------------------------------------------------
DO $$
DECLARE
  par record;
BEGIN
  FOR par IN SELECT * FROM (VALUES
    ('pagos_eventos', 'contenido',     'payload'),
    ('pedidos',       'mensajeria',    'courier'),
    ('productos',     'actualizado_en', 'updated_at'),
    ('productos',     'creado_en',     'created_at'),
    ('usuarios',      'eliminado_en',  'deleted_at'),
    ('usuarios',      'actualizado_en', 'updated_at'),
    ('usuarios',      'creado_en',     'created_at'),
    ('usuarios',      'nombre',        'name')
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

-- 4. Tablas ----------------------------------------------------------------
DO $$
BEGIN
  IF to_regclass('public.pagos_eventos') IS NOT NULL
     AND to_regclass('public.pagos_webhooks') IS NULL THEN
    ALTER TABLE public.pagos_eventos RENAME TO pagos_webhooks;
  END IF;

  IF to_regclass('public.usuarios') IS NOT NULL
     AND to_regclass('public.users') IS NULL THEN
    ALTER TABLE public.usuarios RENAME TO users;
  END IF;
END
$$;

-- 5. Disparadores ----------------------------------------------------------
CREATE OR REPLACE FUNCTION public.touch_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE
  t record;
BEGIN
  IF to_regclass('public.users') IS NOT NULL THEN
    FOR t IN
      SELECT tr.tgname
        FROM pg_trigger tr
        JOIN pg_class c ON c.oid = tr.tgrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
       WHERE n.nspname = 'public' AND c.relname = 'users' AND NOT tr.tgisinternal
         AND (tr.tgname IN ('trg_usuarios_actualizado_en', 'trg_users_updated_at')
              OR tr.tgfoid IN (SELECT p.oid
                                 FROM pg_proc p
                                 JOIN pg_namespace pn ON pn.oid = p.pronamespace
                                WHERE pn.nspname = 'public'
                                  AND p.proname IN ('touch_actualizado_en', 'touch_updated_at')))
    LOOP
      EXECUTE format('DROP TRIGGER %I ON public.users', t.tgname);
    END LOOP;
    EXECUTE 'CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON public.users
             FOR EACH ROW EXECUTE FUNCTION public.touch_updated_at()';
  END IF;

  IF to_regclass('public.productos') IS NOT NULL THEN
    FOR t IN
      SELECT tr.tgname
        FROM pg_trigger tr
        JOIN pg_class c ON c.oid = tr.tgrelid
        JOIN pg_namespace n ON n.oid = c.relnamespace
       WHERE n.nspname = 'public' AND c.relname = 'productos' AND NOT tr.tgisinternal
         AND (tr.tgname IN ('trg_productos_actualizado_en', 'trg_productos_updated_at')
              OR tr.tgfoid IN (SELECT p.oid
                                 FROM pg_proc p
                                 JOIN pg_namespace pn ON pn.oid = p.pronamespace
                                WHERE pn.nspname = 'public'
                                  AND p.proname IN ('touch_actualizado_en', 'touch_updated_at')))
    LOOP
      EXECUTE format('DROP TRIGGER %I ON public.productos', t.tgname);
    END LOOP;
    EXECUTE 'CREATE TRIGGER trg_productos_updated_at BEFORE UPDATE ON public.productos
             FOR EACH ROW EXECUTE FUNCTION public.touch_updated_at()';
  END IF;
END
$$;

COMMIT;
