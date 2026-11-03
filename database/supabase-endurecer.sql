-- Endurecimiento para Supabase. Ejecutar DESPUÉS de importar la estructura y
-- los datos (database/jeremy_tech_estructura.sql y jeremy_tech_datos.sql):
--
--   psql "$DATABASE_URL" -f database/supabase-endurecer.sql
--
-- Supabase publica el esquema `public` en su API REST y, por defecto, las
-- tablas nuevas quedan accesibles con la clave anon (que es pública). La app
-- PHP entra como `postgres`, dueño de las tablas, así que RLS no le afecta.
-- Esto bloquea a `anon` y `authenticated` sin tocar al backend.

DO $$
DECLARE
    tabla record;
BEGIN
    FOR tabla IN
        SELECT tablename FROM pg_tables WHERE schemaname = 'public'
    LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', tabla.tablename);
    END LOOP;
END $$;

-- Los roles de Supabase no existen en un PostgreSQL normal; el bloque lo
-- detecta para que el archivo también pueda ejecutarse en local sin fallar.
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
        EXECUTE 'REVOKE ALL ON ALL TABLES IN SCHEMA public FROM anon, authenticated';
        EXECUTE 'REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM anon, authenticated';
        EXECUTE 'REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM anon, authenticated';
        EXECUTE 'ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON TABLES FROM anon, authenticated';
        EXECUTE 'ALTER DEFAULT PRIVILEGES IN SCHEMA public REVOKE ALL ON SEQUENCES FROM anon, authenticated';
    END IF;
END $$;
