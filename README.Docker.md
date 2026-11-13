# Ejecutar con Docker

App y PostgreSQL en contenedores. Para Local y PostgreSQL en tu PC, sigue
**[README.md](README.md)**.

## Elige qué levantar

| Quieres | Comando |
| --- | --- |
| Solo la base, con PHP en tu PC | `docker compose up -d db` |
| Solo la app, con Supabase | `docker compose up -d --build app --no-deps` |
| App y base en Docker | `docker compose up -d --build` |

La app queda en <http://localhost:8080> y la base en `127.0.0.1:5432`, solo
accesible desde el host. La app en Docker sigue conectando por `db:5432`.

Si ya tienes PostgreSQL en tu PC, no levantes `db`: ocuparía el `5432`. Ejecuta
la app desde el host, como explica README.md.

## Puerto 5432 ocupado

Cambia el mapeo en `compose.yaml` a `127.0.0.1:5433:5432` y pon
`DB_PORT=5433` en `.env` para el PHP del host.

## Primera vez con la base de Docker

1. Copiar el entorno:

   ```bash
   cp .env.example .env
   ```

   Deja `DATABASE_URL` vacío.

2. Levantar todo:

   ```bash
   docker compose up -d --build
   ```

   El contenedor `db` importa la estructura y los datos la primera vez que crea
   el volumen. La app espera a que la base esté sana y queda en
   <http://localhost:8080>.



La base y la app en Docker usan `postgres` / `admin`. El `.env` aporta el resto
de la configuración (`DATABASE_URL`, chat, pagos). Si ejecutas PHP en el host,
ahí sí manda el `.env`.

Los SQL solo se ejecutan al crear el volumen. Para recargarlos hay que borrarlo,
y eso elimina los datos:

```bash
docker compose down -v
docker compose up -d --build
```

## Cuenta administrador

Para convertir una cuenta existente en administrador, cambia su rol en la base:

```bash
docker compose exec -T db psql -U postgres -d jeremy_tech \
  -c "UPDATE usuarios SET rol='admin', activo=1 WHERE email='tu@correo.com';"
```

Para crear el administrador o cambiar su contraseña:

```bash
docker compose exec -e ADMIN_EMAIL="tu@correo.com" -e ADMIN_USUARIO="tu-usuario" -e ADMIN_INITIAL_PASSWORD="tu_contrasena" app php tools/establecer-admin.php
```

`tools/establecer-admin.php` solo modifica cuentas que ya tienen rol `admin`. Si
el correo pertenece a un cliente, primero ejecuta el `UPDATE` de arriba.

## Parar

```bash
docker compose down
```

`down` conserva los datos. `docker compose down -v` borra la base.

## Supabase

Define `DATABASE_URL` en `.env` y levanta solo la app:

```bash
docker compose up -d --build app --no-deps
```

`DATABASE_URL` manda sobre las `DB_*`.

## Render

Render construye la imagen con el `Dockerfile` del repositorio: PHP 8.2 con
Apache, `pdo_pgsql` y el DocumentRoot en `public/`. La base sigue en Supabase.

1. Importa `database/jeremy_tech_estructura.sql` y `database/jeremy_tech_datos.sql`
   en Supabase y conecta la app. Si la base ya existía, aplica antes las
   migraciones pendientes (no se versionan en el repositorio).

2. En Render, New > Web Service, elige el repositorio y la rama `postgresql`.
   En Runtime selecciona Docker y define `DATABASE_URL` con la cadena del
   pooler.

3. Fija `APP_URL=https://<servicio>.onrender.com`. Si hay proxy propio, define
   `TRUSTED_PROXIES` con sus IPs exactas, separadas por comas y sin CIDR ni
   comodines; sin proxy, déjalo vacío.

### Banners de la tienda

La tabla `banners` se crea con `database/jeremy_tech_estructura.sql`, así que en
una instalación nueva no hay paso aparte. Si la base ya existía antes de esa
tabla, aplica la migración pendiente antes de desplegar (no se versiona).

Una cuenta con rol `admin` puede entrar a **Panel > Banners** para crear, editar,
ordenar, ocultar o eliminar promociones. Vendedores y clientes no tienen acceso
a esa gestión. Las imágenes se guardan en la base, no en el disco de Render.
Sin banners activos, el catálogo conserva las promociones predeterminadas.

El plan free suspende el servicio por inactividad, así que la primera visita
tarda en responder. El disco es efímero: sesiones y correos se pierden al
reiniciar; la base vive en Supabase. Comprueba cookies Secure y HTTPS en la URL
pública después del despliegue.
