-- PostgreSQL 14+. Revierte database/renombrar-espanol.sql.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/renombrar-espanol-down.sql
\set ON_ERROR_STOP on
SET lock_timeout = '3s';

BEGIN;

DROP TRIGGER IF EXISTS trg_usuarios_actualizado_en ON public.usuarios;
DROP TRIGGER IF EXISTS trg_productos_actualizado_en ON public.productos;

ALTER INDEX public.idx_pagos_eventos_pedido RENAME TO idx_pagos_webhooks_idx_webhook_pedido;
ALTER INDEX public.idx_pagos_eventos_pago RENAME TO idx_pagos_webhooks_idx_webhook_pago;
ALTER TABLE public.pagos_eventos RENAME CONSTRAINT uq_pagos_eventos_evento TO uq_pagos_webhooks_uq_webhook_evento;

ALTER INDEX public.idx_usuarios_rol RENAME TO idx_users_users_rol_index;
ALTER TABLE public.usuarios RENAME CONSTRAINT ck_usuarios_comision TO ck_users_comision;
ALTER TABLE public.usuarios RENAME CONSTRAINT uq_usuarios_usuario TO uq_users_users_usuario_unique;
ALTER TABLE public.usuarios RENAME CONSTRAINT uq_usuarios_email TO uq_users_users_email_unique;

ALTER TABLE public.pagos_eventos RENAME COLUMN contenido TO payload;
ALTER TABLE public.pagos_eventos RENAME TO pagos_webhooks;

ALTER TABLE public.pedidos RENAME COLUMN mensajeria TO courier;

ALTER TABLE public.productos RENAME COLUMN actualizado_en TO updated_at;
ALTER TABLE public.productos RENAME COLUMN creado_en TO created_at;

ALTER TABLE public.usuarios RENAME COLUMN eliminado_en TO deleted_at;
ALTER TABLE public.usuarios RENAME COLUMN actualizado_en TO updated_at;
ALTER TABLE public.usuarios RENAME COLUMN creado_en TO created_at;
ALTER TABLE public.usuarios RENAME COLUMN nombre TO name;
ALTER TABLE public.usuarios RENAME TO users;

CREATE OR REPLACE FUNCTION public.touch_updated_at() RETURNS trigger AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON public.users
  FOR EACH ROW EXECUTE FUNCTION public.touch_updated_at();
CREATE TRIGGER trg_productos_updated_at BEFORE UPDATE ON public.productos
  FOR EACH ROW EXECUTE FUNCTION public.touch_updated_at();

COMMIT;
