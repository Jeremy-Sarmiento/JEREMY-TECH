-- PostgreSQL 14+. Renombra al espanol las tablas y columnas de esta tanda.
-- Uso: psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -f database/renombrar-espanol.sql
-- Aplicar junto con el despliegue del codigo nuevo: los nombres viejos dejan
-- de existir en el acto. Solo cambia metadatos; no copia ni borra filas.
\set ON_ERROR_STOP on
SET lock_timeout = '3s';

BEGIN;

ALTER TABLE public.users RENAME TO usuarios;
ALTER TABLE public.usuarios RENAME COLUMN name TO nombre;
ALTER TABLE public.usuarios RENAME COLUMN created_at TO creado_en;
ALTER TABLE public.usuarios RENAME COLUMN updated_at TO actualizado_en;
ALTER TABLE public.usuarios RENAME COLUMN deleted_at TO eliminado_en;

ALTER TABLE public.productos RENAME COLUMN created_at TO creado_en;
ALTER TABLE public.productos RENAME COLUMN updated_at TO actualizado_en;

ALTER TABLE public.pedidos RENAME COLUMN courier TO mensajeria;

ALTER TABLE public.pagos_webhooks RENAME TO pagos_eventos;
ALTER TABLE public.pagos_eventos RENAME COLUMN payload TO contenido;

ALTER TABLE public.usuarios RENAME CONSTRAINT uq_users_users_email_unique TO uq_usuarios_email;
ALTER TABLE public.usuarios RENAME CONSTRAINT uq_users_users_usuario_unique TO uq_usuarios_usuario;
ALTER TABLE public.usuarios RENAME CONSTRAINT ck_users_comision TO ck_usuarios_comision;
ALTER INDEX public.idx_users_users_rol_index RENAME TO idx_usuarios_rol;

ALTER TABLE public.pagos_eventos RENAME CONSTRAINT uq_pagos_webhooks_uq_webhook_evento TO uq_pagos_eventos_evento;
ALTER INDEX public.idx_pagos_webhooks_idx_webhook_pedido RENAME TO idx_pagos_eventos_pedido;
ALTER INDEX public.idx_pagos_webhooks_idx_webhook_pago RENAME TO idx_pagos_eventos_pago;

-- Los cuerpos plpgsql no se reescriben con RENAME: se recrean los disparadores
-- para que sigan apuntando a la columna renombrada.
DROP TRIGGER IF EXISTS trg_users_updated_at ON public.usuarios;
DROP TRIGGER IF EXISTS trg_productos_updated_at ON public.productos;
CREATE TRIGGER trg_usuarios_actualizado_en BEFORE UPDATE ON public.usuarios
  FOR EACH ROW EXECUTE FUNCTION public.touch_actualizado_en();
CREATE TRIGGER trg_productos_actualizado_en BEFORE UPDATE ON public.productos
  FOR EACH ROW EXECUTE FUNCTION public.touch_actualizado_en();
DROP FUNCTION IF EXISTS public.touch_updated_at();

COMMIT;
