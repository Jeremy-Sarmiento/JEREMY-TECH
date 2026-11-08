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

2. Construir y levantar:

   ```bash
   docker compose up -d --build
   ```

3. Verificar:

   ```bash
   docker compose ps
   curl -I http://localhost:8080/
   ```

## Base de datos (PostgreSQL)

El servicio `db` usa un volumen externo llamado `jeremy-tech-pg-data`. Debes crearlo **antes** de levantar la base de datos.

### 1. Crear el volumen de la base de datos

```bash
docker volume create jeremy-tech-pg-data
```

### 2. Levantar solo el servicio de base de datos

```bash
docker compose up -d db
```

Espera a que esté sano:

```bash
docker compose ps db
```

### 3. Importar la estructura de la base

Copia el archivo SQL al contenedor y ejecútalo:

```bash
docker cp database/jeremy_tech_estructura.sql jeremy-tech-db:/tmp/estructura.sql
docker exec jeremy-tech-db psql -X -v ON_ERROR_STOP=1 -U postgres -d jeremy_tech -f /tmp/estructura.sql
```

### 4. Importar los datos

```bash
docker cp database/jeremy_tech_datos.sql jeremy-tech-db:/tmp/datos.sql
docker exec jeremy-tech-db psql -X -v ON_ERROR_STOP=1 -U postgres -d jeremy_tech -f /tmp/datos.sql
```

### 5. Verificar que todo se importó

```bash
docker exec jeremy-tech-db psql -U postgres -d jeremy_tech -c "\dt"
```

> **Nota:** si al importar los datos aparece el error `value too long for type character varying(500)` en la tabla `notificaciones`, ejecuta:
> ```bash
> docker exec jeremy-tech-db psql -U postgres -d jeremy_tech -c "ALTER TABLE notificaciones ALTER COLUMN cuerpo TYPE text;"
> ```
> y vuelve a lanzar el comando del paso 4.

## Parar y limpiar

```bash
docker compose down
docker compose down --rmi local
```

El volumen `jeremy-tech-pg-data` es externo, así que `docker compose down -v` no lo borra. Para eliminarlo:

```bash
docker volume rm jeremy-tech-pg-data
```
