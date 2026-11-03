-- Jeremy Tech: imágenes de producto guardadas en la base de datos.
--
-- Aplica este archivo sobre una base que ya tiene el esquema anterior
-- (por ejemplo, la de Supabase). Es idempotente.
--
-- Uso: psql "$DATABASE_URL" -f database/imagenes-producto.sql

BEGIN;

CREATE TABLE IF NOT EXISTS producto_imagenes (
  producto_id bigint NOT NULL,
  mime varchar(60) NOT NULL,
  datos bytea NOT NULL,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (producto_id),
  CONSTRAINT fk_prodimg_producto FOREIGN KEY (producto_id)
    REFERENCES productos (id) ON DELETE CASCADE
);

-- La API REST de Supabase expone el esquema public. La app entra por conexión
-- directa (rol postgres), así que RLS no la afecta y bloquea el resto.
ALTER TABLE producto_imagenes ENABLE ROW LEVEL SECURITY;

COMMIT;
