# Ejecutar con Docker

App Jeremy Tech + PostgreSQL 16 con Docker Compose.

## Requisitos

- Docker y Docker Compose.
- Puerto `8080` libre para la app.
- Puerto `5432` libre en el host para PostgreSQL.

## Despliegue

1. Preparar el entorno:

   ```bash
   cp .env.example .env
   ```

   El servicio `app` ya define `DB_HOST=db`, `DB_PORT=5432`, `DB_NAME=jeremy_tech`, `DB_USER=postgres` y `DB_PASS=admin` en `compose.yaml`. Si `.env` define `DATABASE_URL`, esa variable tiene prioridad y pisa las `DB_*`; para este compose dejarla vacía.

2. Construir y levantar:

   ```bash
   docker compose up -d --build
   ```

3. Verificar:

   ```bash
   docker compose ps
   curl -I http://localhost:8080/
   ```

4. Conectar a PostgreSQL desde el host:

   ```bash
   PGPASSWORD=admin psql -h 127.0.0.1 -p 5432 -U postgres -d jeremy_tech
   ```

## Actualizar una base existente

No vuelvas a importar `jeremy_tech_estructura.sql`: elimina las tablas existentes.
Antes de desplegar estas correcciones, respalda la base y pausa la app:

```bash
umask 077
mkdir -p "$HOME/jeremy-tech-backups"
docker compose stop app
docker compose exec -T db pg_dump -U postgres -d jeremy_tech -Fc > "$HOME/jeremy-tech-backups/antes-integridad-$(date +%Y%m%d-%H%M%S).dump"
docker compose exec -T db psql -X -U postgres -d jeremy_tech -v ON_ERROR_STOP=1 -c 'SET search_path TO public' -f /dev/stdin < database/relaciones-integridad.sql
docker compose exec -T db psql -X -U postgres -d jeremy_tech -v ON_ERROR_STOP=1 -c 'SET search_path TO public' -f /dev/stdin < database/sku-automatico.sql
docker compose up -d --build app
```

Ejecuta cada paso solo si el anterior terminó correctamente. Las migraciones
bloquean tablas y se detienen si encuentran referencias inválidas, solicitudes
pendientes duplicadas o colisiones de SKU; no reparan ni borran historial.
El SKU nuevo empieza por `JT-`, se completa si estaba vacío y no cambia al editar.
Una instalación nueva ya incluye estas protecciones en la estructura completa.

## Puerto de PostgreSQL

El compose publica `127.0.0.1:5432:5432`. El primer `5432` es el puerto del host y el segundo el del contenedor. La app usa `db:5432` por la red interna, así que este mapeo solo afecta el acceso desde el host.

Si el `5432` del host está ocupado, `docker compose up` falla con:

```text
Bind for 127.0.0.1:5432 failed: port is already allocated
```

En ese caso, cambiar el puerto del host en `compose.yaml`:

```yaml
ports:
  - "127.0.0.1:5433:5432"
```

Recrear el servicio:

```bash
docker compose up -d --force-recreate db
```

Y ajustar la conexión desde el host:

```bash
PGPASSWORD=admin psql -h 127.0.0.1 -p 5433 -U postgres -d jeremy_tech
```

La app no cambia: sigue usando `DB_HOST=db` y `DB_PORT=5432`.

## Parar y limpiar

```bash
docker compose down
docker compose down --rmi local
```

El volumen `jeremy-tech-pg-data` es externo, así que `docker compose down -v` no lo borra. Para eliminarlo:

```bash
docker volume rm jeremy-tech-pg-data
```
