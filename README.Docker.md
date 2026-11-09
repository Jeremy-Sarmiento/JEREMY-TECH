# Ejecutar con Docker

App y MariaDB en contenedores. Para Apache y MariaDB en tu PC, sigue
**[README.md](README.md)**.

## Elige qué levantar

| Quieres | Comando |
| --- | --- |
| Solo la base, con PHP en tu PC | `docker compose up -d db` |
| App y base en Docker | `docker compose up -d --build` |

La app queda en <http://localhost:8080> y la base en el puerto `3304` del PC
(`3306` entre contenedores). Con PHP en el host, usa `DB_PORT=3304` en `.env`.

## Primera vez con la base de Docker

1. Copiar el entorno:

   ```bash
   cp .env.example .env
   ```

2. Levantar todo:

   ```bash
   docker compose up -d --build
   ```

   El contenedor `db` importa `database/jeremy_tech_estructura.sql` y
   `database/jeremy_tech_datos.sql` la primera vez que crea el volumen. La app
   espera a que la base esté sana y queda en <http://localhost:8080>.

La base y la app en Docker usan `jeremy` / `admin`. El `.env` aporta el resto de
la configuración (chat, pagos). Si ejecutas PHP en el host, ahí sí manda el
`.env`.

Los SQL solo se ejecutan al crear el volumen. Para recargarlos hay que borrarlo,
y eso elimina los datos:

```bash
docker compose down -v
docker compose up -d --build
```

## Cuenta administrador

Para crear el administrador o cambiar su contraseña:

```bash
docker compose exec -e ADMIN_EMAIL="tu@correo.com" -e ADMIN_USUARIO="tu-usuario" -e ADMIN_INITIAL_PASSWORD="tu_contrasena" app php tools/establecer-admin.php
```

`tools/establecer-admin.php` solo actúa sobre cuentas que ya tienen rol `admin`.
No convierte clientes en administradores.

## Parar

```bash
docker compose down
```

`down` conserva los datos. `docker compose down -v` borra la base.
