-- PostgreSQL 14+. Relaciones de catalogo/cuentas y una solicitud pendiente por usuario.
-- Uso en produccion (esquema explicito, conexion confiable):
-- psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -c 'SET search_path TO public' -f database/relaciones-integridad.sql
-- Tambien admite PDO::exec del archivo completo, SIN una transaccion exterior.
-- Respeta search_path para poder probar/importar en esquemas aislados, sin public de respaldo.
-- Aplicar ANTES del codigo que usa ON CONFLICT de pendientes. Respaldar y pausar escrituras:
-- ALTER y los indices transaccionales bloquean tablas hasta COMMIT; NO es una migracion sin bloqueos.
-- lock_timeout limita la espera de cada bloqueo a 3s, no la duracion de la validacion/creacion.
-- shortcut: indices transaccionales para una ventana de mantenimiento; con tablas grandes,
-- preparar los mismos indices CONCURRENTLY fuera de la transaccion en un despliegue separado.
-- Los nombres reservados aqui deben mantener sus definiciones y sus indices validos.
-- No borra, reasigna ni repara historial: una FK invalida o pendientes duplicadas abortan TODO.
-- Ante fallo: ROLLBACK en PDO; diagnosticar/restaurar datos con autorizacion antes de reintentar.
-- Rollback minimo, con escrituras pausadas y PRIMERO el codigo anterior (sin este ON CONFLICT):
-- BEGIN; SET LOCAL lock_timeout = '3s';
-- ALTER TABLE productos DROP CONSTRAINT IF EXISTS fk_productos_marca;
-- ALTER TABLE productos DROP CONSTRAINT IF EXISTS fk_productos_categoria;
-- ALTER TABLE solicitudes_vendedor DROP CONSTRAINT IF EXISTS fk_solicitudes_usuario;
-- ALTER TABLE tokens_recuperacion DROP CONSTRAINT IF EXISTS fk_tokens_usuario;
-- DROP INDEX IF EXISTS uq_solicitudes_vendedor_pendiente;
-- DROP INDEX IF EXISTS idx_productos_marca;
-- DROP INDEX IF EXISTS idx_productos_categoria;
-- DROP INDEX IF EXISTS idx_tokens_recuperacion_usuario;
-- COMMIT;
-- Conservar varchar(80): estrechar a 60 podria perder compatibilidad con categorias ya guardadas.
-- El rollback retira protecciones, nunca filas; no revierte renombres legitimos de categorias.

BEGIN;
SET LOCAL lock_timeout = '3s';

DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = current_schema() AND table_name = 'productos' AND column_name = 'categoria'
      AND data_type = 'character varying' AND character_maximum_length = 80
  ) THEN
    ALTER TABLE productos ALTER COLUMN categoria TYPE varchar(80);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'productos'::regclass AND conname = 'fk_productos_marca'
  ) THEN
    ALTER TABLE productos ADD CONSTRAINT fk_productos_marca
      FOREIGN KEY (marca_id) REFERENCES marcas (id) ON UPDATE NO ACTION ON DELETE NO ACTION;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'productos'::regclass AND conname = 'fk_productos_categoria'
  ) THEN
    ALTER TABLE productos ADD CONSTRAINT fk_productos_categoria
      FOREIGN KEY (categoria) REFERENCES categorias (nombre)
      ON UPDATE CASCADE ON DELETE NO ACTION DEFERRABLE INITIALLY IMMEDIATE;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'solicitudes_vendedor'::regclass AND conname = 'fk_solicitudes_usuario'
  ) THEN
    ALTER TABLE solicitudes_vendedor ADD CONSTRAINT fk_solicitudes_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON UPDATE NO ACTION ON DELETE NO ACTION;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conrelid = 'tokens_recuperacion'::regclass AND conname = 'fk_tokens_usuario'
  ) THEN
    ALTER TABLE tokens_recuperacion ADD CONSTRAINT fk_tokens_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON UPDATE NO ACTION ON DELETE NO ACTION;
  END IF;
END;
$$;

-- Tambien rechaza historial invalido si una instalacion dejo estas FK como NOT VALID.
ALTER TABLE productos VALIDATE CONSTRAINT fk_productos_marca;
ALTER TABLE productos VALIDATE CONSTRAINT fk_productos_categoria;
ALTER TABLE solicitudes_vendedor VALIDATE CONSTRAINT fk_solicitudes_usuario;
ALTER TABLE tokens_recuperacion VALIDATE CONSTRAINT fk_tokens_usuario;

CREATE INDEX IF NOT EXISTS idx_productos_marca ON productos (marca_id);
CREATE INDEX IF NOT EXISTS idx_productos_categoria ON productos (categoria);
-- El indice previo (tipo, usuario_id, usado) no cubre usuario_id como primera columna.
CREATE INDEX IF NOT EXISTS idx_tokens_recuperacion_usuario ON tokens_recuperacion (usuario_id);
CREATE UNIQUE INDEX IF NOT EXISTS uq_solicitudes_vendedor_pendiente
  ON solicitudes_vendedor (usuario_id) WHERE estado = 'pendiente';

COMMIT;
