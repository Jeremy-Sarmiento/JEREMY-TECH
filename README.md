# Jeremy Tech

Tienda de tecnología en PHP puro (MVC propio) con panel de administración y
soporte multi-vendedor. Base PostgreSQL `jeremy_tech`.

**Stack:** PHP 8.2+ · PostgreSQL 14+ · PDO `pdo_pgsql`

## Entregables

- [Sesión 1](entregables/Sesion-001-A3-2-Entregable.pdf)
- [Sesion 2 - El Puente Digital — Reporte Técnico](entregables/El%20Puente%20Digital%20—%20Reporte%20Técnico.pdf)
- [Sesion 2 - La Radiografía Técnica](entregables/La%20Radiografía%20Técnica%20—%20Sesión%202.pdf)
- [Sesión 3](entregables/Sesion-003-A3-2-Entregable.pdf)

## Docker

App y PostgreSQL en contenedores, sin PostgreSQL en el host. Los modos de arranque, la primera vez, Supabase y Render están en
**[README.Docker.md](README.Docker.md).**

## Local

Requiere PHP 8.2+ con `pdo_pgsql`, PostgreSQL 14+
1. Crea e importa la base:

   ```bash
   createdb jeremy_tech
   psql -d jeremy_tech -f database/jeremy_tech_estructura.sql
   psql -d jeremy_tech -f database/jeremy_tech_datos.sql
   ```

   Se importan en ese orden sobre una base vacía. La estructura crea tablas,
   índices, claves y disparadores; los datos cargan las filas.

2. Copia el entorno y rellena las credenciales:

   ```bash
   cp .env.example .env   # DB_USER y DB_PASS
   ```

   `DB_HOST=127.0.0.1` y `DB_PORT=5432` apuntan a tu PostgreSQL local.

Para desarrollo rápido también sirve:

```bash
php -S 127.0.0.1:8000 -t public
```

## Administrador inicial

El seed deja las cuentas de admin y vendedor demo desactivadas. Crea o recupera
una cuenta admin desde el servidor:

```bash
env ADMIN_EMAIL="tu@correo.com" ADMIN_USUARIO="tu-usuario" ADMIN_INITIAL_PASSWORD="tu_contrasena" php tools/establecer-admin.php
```

Requiere 8+ caracteres y rechaza claves comunes. Verifica la base de destino
antes de ejecutarlo. Los `.env*` reales no se versionan; si una credencial se
filtró, rótala en el proveedor.


## Estructura

```text
public/index.php    Único punto de entrada (tienda y admin)
src/Controller/     Controladores
src/Model/          Modelos con SQL directo vía PDO
views/              Vistas de tienda y views/admin/
config/conexion.php Carga .env, autocarga PSR-4 y PDO
database/           Esquema, datos y migraciones
```

`public/css/` se divide en `base/`, `componentes/`, `paginas/` y `tema/`. El
orden de carga está en `config/estilos.php`. Ver
[mapa de estilos](public/css/README.md) antes de mover reglas.

## Rutas

Todas pasan por `?ruta=...` en `public/index.php`.

- **Tienda:** `catalogo`, `producto`, `carrito`, `login`, `direcciones`.
- **Información:** `pagina&p=privacidad`, `terminos`, `envios`, `faq`, `contacto`.
- **Panel:** `admin?seccion=...`, con secciones según el rol.

## Funcionalidad

- Catálogo con búsqueda, filtros, paginación y variantes por color o capacidad.
- Carrito de invitado y por cuenta, persistente entre dispositivos.
- Checkout simulado con tarjeta, Yape, transferencia y contraentrega.
- Panel de vendedor B2C, B2B y C2C con stock, reportes y precios por volumen.
- Reseñas, calificaciones de vendedor, tickets de soporte y moderación.
- Pasarela externa por webhook HMAC con idempotencia.
- Analítica de eventos y recomendaciones en la ficha.

Pagos de prueba: tarjeta `4111 1111 1111 1111` (12/40, CVV 123) aprueba y
`4000 0000 0000 0002` rechaza. Yape `987654321` con código `849201` aprueba y
`000000` rechaza. No se almacenan datos de tarjeta.

## Pruebas

```bash
composer test        # PHPUnit
composer cs-check    # estilo
composer stan        # análisis estático
bash tools/smoke-test.sh
```

La integración continua corre lo mismo contra un PostgreSQL temporal en cada
push y pull request.
