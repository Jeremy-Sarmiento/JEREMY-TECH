-- PostgreSQL 14+. SKU automatico JT- + id, con minimo cinco digitos y sin truncar bigint.
-- Uso en produccion (esquema explicito, conexion confiable):
-- psql -X -v ON_ERROR_STOP=1 -d jeremy_tech -c 'SET search_path TO public' -f database/sku-automatico.sql
-- Tambien admite PDO::exec del archivo completo, SIN una transaccion exterior.
-- Respeta search_path; funcion y trigger reservados: asignar_codigo_sku / trg_productos_codigo_sku.
-- Aplicar antes del codigo que omite el SKU. Respaldar y reservar ventana de mantenimiento:
-- SHARE ROW EXCLUSIVE bloquea INSERT/UPDATE/DELETE hasta COMMIT, pero permite lecturas.
-- lock_timeout limita cada espera a 3s, NO la duracion del backfill.
-- Solo completa NULL/vacios segun btrim; conserva SKU explicitos, datos e identidades.
-- Se desactiva SOLO trg_productos_updated_at durante el backfill y se restaura su estado O/A/R/D.
-- UNIQUE, FK y los otros triggers siguen activos. No hay secuencias ni tablas nuevas.
-- Una colision con un SKU historico produce 23505 y aborta TODO, sin reintentos ni renumeraciones.
-- Ante fallo: ROLLBACK en PDO; diagnosticar con autorizacion y respaldo antes de reintentar.
-- Rollback minimo con escrituras pausadas y el codigo anterior desplegado:
-- BEGIN; SET LOCAL lock_timeout = '3s'; LOCK TABLE productos IN SHARE ROW EXCLUSIVE MODE;
-- DROP TRIGGER IF EXISTS trg_productos_codigo_sku ON productos;
-- DROP FUNCTION IF EXISTS asignar_codigo_sku();
-- COMMIT;
-- Conservar los SKU ya asignados: recuperar NULL/vacios originales requiere respaldo;
-- nunca borrar SKU, quitar UNIQUE ni reiniciar la identidad para deshacer este cambio.

BEGIN;
SET LOCAL lock_timeout = '3s';
LOCK TABLE productos IN SHARE ROW EXCLUSIVE MODE;

CREATE OR REPLACE FUNCTION asignar_codigo_sku() RETURNS trigger AS $$
BEGIN
  IF NEW.codigo_sku IS NULL OR btrim(NEW.codigo_sku) = '' THEN
    -- Un ancho fijo de lpad truncaria los ids de mas de cinco digitos.
    NEW.codigo_sku = 'JT-' || lpad(NEW.id::text, GREATEST(5, length(NEW.id::text)), '0');
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DO $$
DECLARE
  estado_timestamp "char";
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_trigger
    WHERE tgrelid = 'productos'::regclass AND tgname = 'trg_productos_codigo_sku' AND NOT tgisinternal
  ) THEN
    CREATE TRIGGER trg_productos_codigo_sku BEFORE INSERT OR UPDATE OF codigo_sku ON productos
      FOR EACH ROW EXECUTE FUNCTION asignar_codigo_sku();
  END IF;

  IF EXISTS (SELECT 1 FROM productos WHERE codigo_sku IS NULL OR btrim(codigo_sku) = '') THEN
    SELECT tgenabled INTO estado_timestamp FROM pg_trigger
    WHERE tgrelid = 'productos'::regclass AND tgname = 'trg_productos_updated_at' AND NOT tgisinternal;

    IF estado_timestamp IN ('O', 'A', 'R') THEN
      ALTER TABLE productos DISABLE TRIGGER trg_productos_updated_at;
    END IF;

    UPDATE productos
    SET codigo_sku = 'JT-' || lpad(id::text, GREATEST(5, length(id::text)), '0')
    WHERE codigo_sku IS NULL OR btrim(codigo_sku) = '';

    IF estado_timestamp = 'O' THEN
      ALTER TABLE productos ENABLE TRIGGER trg_productos_updated_at;
    ELSIF estado_timestamp = 'A' THEN
      ALTER TABLE productos ENABLE ALWAYS TRIGGER trg_productos_updated_at;
    ELSIF estado_timestamp = 'R' THEN
      ALTER TABLE productos ENABLE REPLICA TRIGGER trg_productos_updated_at;
    END IF;
    -- D permanece desactivado; si no existe ese trigger, no se crea ni cambia ninguno.
  END IF;
END;
$$;

COMMIT;
