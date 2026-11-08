# Jeremy Tech

Tienda de tecnología en PHP puro (MVC propio) con panel de administración y soporte multi-vendedor. Base de datos PostgreSQL `jeremy_tech`.

**Stack:** PHP 8.2+ · PostgreSQL 14+ · PDO `pdo_pgsql`

## Entregables

Informes PDF de las sesiones de desarrollo:

- [Sesión 1](entregables/Sesion-001-A3-2-Entregable.pdf)
- [Sesión 2 - El Puente Digital — Reporte Técnico](entregables/El%20Puente%20Digital%20—%20Reporte%20Técnico.pdf)
- [Sesión 2 - La Radiografía Técnica](entregables/La%20Radiografía%20Técnica%20—%20Sesión%202.pdf)
- [Sesión 3](entregables/Sesion-003-A3-2-Entregable.pdf)

## Instalación local

```bash
# 1. Crear base de datos
createdb jeremy_tech

# 2. Importar estructura y datos (en este orden)
psql -d jeremy_tech -f database/jeremy_tech_estructura.sql
psql -d jeremy_tech -f database/jeremy_tech_datos.sql

# 3. Configurar entorno
cp .env.example .env
# Editar .env con tus credenciales (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)

# 4. Ejecutar servidor de desarrollo
php -S 127.0.0.1:8000 -t public
```

La app queda en `http://127.0.0.1:8000`.

## Despliegue con Docker

Ver [README.Docker.md](README.Docker.md). Resumen:

```bash
docker volume create jeremy-tech-pg-data
docker compose up -d --build
```

La app queda en `http://localhost:8080` y PostgreSQL en `127.0.0.1:5432`.

## Estructura del proyecto

```
public/         Punto de entrada (index.php) y assets
src/            Controladores, modelos y seguridad
config/         Conexión PDO y carga de .env
database/       Estructura y datos SQL
docker/         Dockerfile, Apache, PHP y entrypoint
```

## Características principales

- Catálogo con filtros, búsqueda y paginación
- Carrito de compras por cuenta de usuario
- Checkout con pagos simulados (tarjeta, Yape, transferencia, contraentrega)
- Panel de vendedor (publicar productos, variantes, precios por volumen, reportes)
- Panel de administración (usuarios, pedidos, cupones, configuración, auditoría)
- Tickets de soporte, reseñas y notificaciones simuladas

## Seguridad

- El archivo `.env` no se sube al repositorio.
- Los `.env*` reales no entran en Git ni en la imagen Docker.
