# Ejecutar con Docker

App y MariaDB en contenedores. Para Apache y MariaDB en tu PC, sigue
**[README.md](README.md)**.

## Elige qué levantar

| Quieres | Comando |
| --- | --- |
| Solo la base, con PHP en tu PC | `docker compose up -d db` |
| Solo la app, con una base externa | `docker compose up -d --build app --no-deps` |
| App y base en Docker | `docker compose up -d --build` |

La app queda en <http://localhost:8080> y la base en el puerto `3306`.

Si ya tienes MariaDB en tu PC, no levantes `db`: ocuparía el `3306`. Ejecuta la
app desde el host, como explica README.md.

## Primera vez con la base de Docker

1. Copiar el entorno:

   ```bash
   cp .env.example .env
   ```

2. Levantar todo:

   ```bash
   docker compose up -d --build
   ```

   El contenedor `db` importa `database/jeremy_tech_full.sql` la primera vez que
   crea el volumen. La app espera a que la base esté sana y queda en
   <http://localhost:8080>.

La base y la app en Docker usan `jeremy` / `admin`. El `.env` aporta el resto de
la configuración (chat, pagos). Si ejecutas PHP en el host, ahí sí manda el
`.env`.

El dump solo se ejecuta al crear el volumen. Para recargarlo hay que borrarlo, y
eso elimina los datos:

```bash
docker compose down -v
docker compose up -d --build
```

## Cuenta administrador

Para crear el administrador o cambiar su contraseña:

```bash
read -rs -p 'Nueva contraseña: ' ADMIN_INITIAL_PASSWORD
export ADMIN_INITIAL_PASSWORD
docker compose exec -e ADMIN_EMAIL=tu@correo.com -e ADMIN_USUARIO=tu-usuario \
  -e ADMIN_INITIAL_PASSWORD app php tools/establecer-admin.php
unset ADMIN_INITIAL_PASSWORD
```

`tools/establecer-admin.php` solo actúa sobre cuentas que ya tienen rol `admin`.
No convierte clientes en administradores.

## Render

El repositorio incluye `Dockerfile`, `docker/apache.conf` y `docker/php.ini`.
La imagen usa PHP 8.2 con Apache, `pdo_mysql` y el DocumentRoot en `public/`.
La base MariaDB debe estar alojada por separado y aceptar conexiones desde el
servicio.

1. Importa `database/jeremy_tech_full.sql` en la base MariaDB de destino.
2. En Render, crea un Web Service desde la rama `main` y selecciona Docker.
3. Define `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` y
   `APP_URL=https://<servicio>.onrender.com`.
4. Configura `TRUSTED_PROXIES` solo con las IPs exactas de proxies propios. Sin
   proxy propio, déjalo vacío.

El disco del contenedor es efímero. Las sesiones y los correos simulados se
pierden al reiniciar; usa una ruta persistente fuera del DocumentRoot si deben
conservarse.

## Puerto 3306 ocupado

Cambia el mapeo en `compose.yaml` a `127.0.0.1:3307:3306` y pon
`DB_PORT=3307` en `.env` para el PHP del host.

## Parar

```bash
docker compose down
```

`down` conserva los datos. `docker compose down -v` borra la base.
