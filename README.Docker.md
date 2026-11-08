# Ejecutar con Docker

App Jeremy Tech + PostgreSQL 16 con Docker Compose.

## Requisitos

- Docker y Docker Compose.
- Puerto `8080` libre para la app.
- Puerto `5432` libre en el host para PostgreSQL.

## Despliegue

El servicio de base de datos usa un volumen externo llamado `jeremy-tech-pg-data`. Debes crearlo **antes** de levantar los contenedores.

### 1. Crear el volumen de la base de datos

```bash
docker volume create jeremy-tech-pg-data
```

### 2. Preparar el entorno

```bash
cp .env.example .env
```

### 3. Construir y levantar los servicios

```bash
docker compose up -d --build
```

### 4. Importar estructura y datos de la base de datos

```bash
docker cp database/jeremy_tech_estructura.sql jeremy-tech-db:/tmp/estructura.sql
docker cp database/jeremy_tech_datos.sql jeremy-tech-db:/tmp/datos.sql

docker exec jeremy-tech-db psql -X -v ON_ERROR_STOP=1 -U postgres -d jeremy_tech -f /tmp/estructura.sql
docker exec jeremy-tech-db psql -X -v ON_ERROR_STOP=1 -U postgres -d jeremy_tech -f /tmp/datos.sql
```

### 5. Verificar

```bash
docker compose ps
curl -I http://localhost:8080/
```

> **Nota:** si al importar los datos aparece el error `value too long for type character varying(500)` en la tabla `notificaciones`, ejecuta:
> ```bash
> docker exec jeremy-tech-db psql -U postgres -d jeremy_tech -c "ALTER TABLE notificaciones ALTER COLUMN cuerpo TYPE text;"
> ```
> y vuelve a lanzar el comando de importación de datos.

## Parar y limpiar

```bash
docker compose down
docker compose down --rmi local
```

El volumen `jeremy-tech-pg-data` es externo, así que `docker compose down -v` no lo borra. Para eliminarlo:

```bash
docker volume rm jeremy-tech-pg-data
```
