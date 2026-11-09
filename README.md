# Jeremy Tech

Tienda de tecnología en PHP puro (MVC propio) con panel de administración y
soporte multi-vendedor. Base MariaDB `jeremy_tech`.

**Stack:** PHP 8.2+ · MariaDB 10.2+ · PDO `pdo_mysql`

## Entregables

- [Sesión 1](entregables/Sesion-001-A3-2-Entregable.pdf)
- [Sesion 2 - El Puente Digital — Reporte Técnico](entregables/El%20Puente%20Digital%20—%20Reporte%20Técnico.pdf)
- [Sesion 2 - La Radiografía Técnica](entregables/La%20Radiografía%20Técnica%20—%20Sesión%202.pdf)
- [Sesión 3](entregables/Sesion-003-A3-2-Entregable.pdf)

## Instalación local

Requiere PHP 8.2+ con `pdo_mysql`, MariaDB 10.2+ y Apache con `mod_rewrite`, con
el DocumentRoot en `public/`.

1. Importa la estructura y los datos:

   ```bash
   mariadb -h 127.0.0.1 -u root -p < database/jeremy_tech_estructura.sql
   mariadb -h 127.0.0.1 -u root -p < database/jeremy_tech_datos.sql
   ```

   El primer archivo crea la base y las tablas. El segundo carga catálogo,
   cuentas y configuración. Solo para una instalación vacía: la estructura borra
   las tablas existentes.

2. Copia el entorno y rellena las credenciales:

   ```bash
   cp .env.example .env   # DB_USER y DB_PASS
   ```

   `DB_HOST=127.0.0.1` y `DB_PORT=3306` apuntan a tu MariaDB local. Deja `.env`
   fuera de `public/` y con permisos `600`.

3. Abre `http://localhost/` o la ruta que corresponda al sitio.

Para desarrollo rápido también sirve:

```bash
php -S 127.0.0.1:8000 -t public
```

### Base MariaDB alojada

Configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` con los datos
del proveedor. Importa `database/jeremy_tech_estructura.sql` y después
`database/jeremy_tech_datos.sql` desde un equipo autorizado. No subas `.env` al
repositorio.

## Docker

App y MariaDB en contenedores, sin Apache ni MariaDB en el host. Los modos de
arranque y la primera vez están en **[README.Docker.md](README.Docker.md)**.

## Administrador inicial

El seed deja las cuentas de admin y vendedor demo desactivadas. Crea o recupera
una cuenta admin desde el servidor:

```bash
env ADMIN_EMAIL="tu@correo.com" ADMIN_USUARIO="tu-usuario" ADMIN_INITIAL_PASSWORD="tu_contrasena" php tools/establecer-admin.php
```

Requiere 8+ caracteres y rechaza claves comunes. Solo actúa sobre cuentas con rol
`admin`; no convierte clientes. Verifica la base de destino antes de ejecutarlo.

La demo no debe abrirse a usuarios reales: el enlace de recuperación sigue
visible para quien lo solicita y la pregunta de seguridad no sustituye la
verificación por correo.

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

## Estructura

```text
public/index.php    Único punto de entrada (tienda y admin)
src/Controller/     Controladores
src/Model/          Modelos con SQL directo vía PDO
views/              Vistas de tienda y views/admin/
config/conexion.php Carga .env, autocarga PSR-4 y PDO
database/           Estructura y datos iniciales
```

## Rutas

Todas pasan por `?ruta=...` en `public/index.php`.

- **Tienda:** `catalogo`, `producto`, `carrito`, `login`, `direcciones`.
- **Recuperación:** `login/recuperar` y `login/restablecer&token=...`, con token
  de 30 minutos y un solo uso.
- **Información:** `pagina&p=privacidad`, `terminos`, `envios`, `faq`, `contacto`.
- **Panel:** `admin?seccion=...`, con secciones según el rol.

## Pruebas

```bash
composer test        # PHPUnit
composer cs-check    # estilo
composer stan        # análisis estático
bash tools/smoke-test.sh
```

La integración continua corre lo mismo contra un MariaDB temporal en cada push
y pull request.
