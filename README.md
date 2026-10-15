# Jeremy Tech

Tienda de tecnología en PHP puro (MVC propio, sin framework ni Composer) con panel de
administración estilo Filament oscuro y soporte multi-vendedor. Base de datos MariaDB
`jeremy_tech`.

- **Repositorio:** https://github.com/Jeremy-Sarmiento/JEREMY-TECH
- **Stack:** PHP 8.5 · MariaDB 10.11 (PDO, `utf8mb4`) · HTML/CSS/JS sin build
- **Referencia de diseño:** panel Filament de un proyecto Laravel hermano

## Requisitos

- PHP 8.1 o superior (probado en 8.5) con PDO MySQL habilitado
- MariaDB 10.11+ (o MySQL 8+)
- Servidor web con PHP embebido, o bien `php -S` para desarrollo

No hay Composer, npm ni paso de build: el proyecto se sirve tal cual.

## Instalación

1. **Crear la base de datos** y cargar el esquema con semillas:

   ```bash
   mysql -u root -p -e "CREATE DATABASE jeremy_tech CHARACTER SET utf8mb4;"
   mysql -u root -p jeremy_tech < database/jeremy_tech.sql
   ```

2. **Configurar credenciales** en `.env` (está fuera de control de versiones):

   ```bash
   cp .env.example .env
   ```

   ```dotenv
   DB_HOST=127.0.0.1
   DB_NAME=jeremy_tech
   DB_USER=root
   DB_PASS=tu_password
   APP_TIMEZONE=America/Lima
   ```

   `config/conexion.php` lo lee al arrancar, define las constantes `DB_*` y
   `APP_TIMEZONE`, fija la zona horaria de PHP y alinea la sesión MySQL. Si `.env`
   falta o una clave está vacía, cae a valores por defecto de desarrollo local
   (`root` sin password).

3. **Arrancar** en desarrollo:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

El archivo `database/jeremy_tech.sql` es **idempotente**: puede re-ejecutarse sobre
una base existente sin dañar datos (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT
EXISTS`, `INSERT ... WHERE NOT EXISTS`, procedimientos para añadir foreign keys).

## Entregables

Informes PDF de las sesiones de desarrollo:

- [Sesión 1](entregables/Sesion-001-A3-2-Entregable.pdf)
- [Sesión 3](entregables/Sesion-003-A3-2-Entregable.pdf)
- [El Puente Digital — Reporte Técnico](entregables/El%20Puente%20Digital%20—%20Reporte%20Técnico.pdf)
- [La Radiografía Técnica — Sesión 2](entregables/La%20Radiografía%20Técnica%20—%20Sesión%202.pdf)

## Estructura

```
public/            Documento raíz del servidor
  index.php        Único punto de entrada: enruta admin y tienda
  css/ js/         Estáticos (styles.css es la hoja principal de la tienda)
src/
  Controller/      Controladores (Producto, Carrito, Login, Cuenta, Admin, ...)
  Model/           Modelos con SQL directo vía PDO (Producto, Pedido, Inventario, ...)
views/
  layout/          header.php + footer.php de la tienda
  admin/           panel.php (shell con sidebar) + login.php
  admin/secciones/ Una vista por sección del panel
  partials/        card.php (tarjeta de producto)
  *.php            Vistas de la tienda (catalogo, detalle, carrito, ...)
config/
  conexion.php     Carga .env, autocarga PSR-4 (Model\, Controller\) y singleton PDO
database/
  jeremy_tech.sql  Esquema completo + semillas + 8 etapas de evolución
entregables/       Informes PDF de sesión
```

## Credenciales de demostración

| Rol | Usuario / email | Contraseña |
| --- | --- | --- |
| Administrador | `jeremytech` | `jeremytech` |
| Vendedor | `vendedor` | `vendedor123` |
| Cliente | `jeremytech@jeremytech.pe` | `jeremytech` |

## Rutas

Todas pasan por `?ruta=...` y se resuelven en `public/index.php`.

### Tienda

| Ruta | Qué hace |
| --- | --- |
| `catalogo` (por defecto) | Listado de productos con filtros |
| `producto` | Ficha: galería, variantes, precios por volumen |
| `carrito` | Carrito con líneas por producto+variante |
| `carrito/agregar` · `cambiar-cantidad` · `eliminar` · `aplicar-cupon` · `procesar` | Acciones del carrito y checkout |
| `login` · `registrar` · `iniciar` · `cerrar` · `recuperar-pregunta` | Cuenta de cliente |
| `direcciones` · `guardar` · `eliminar` · `predeterminada` | Libreta de direcciones con zona y costo de envío |

### Panel de administración

| Ruta | Qué hace |
| --- | --- |
| `admin/login` · `autenticar` · `cerrar` | Acceso al panel (sesión propia, clave `admin_id`) |
| `admin` · `admin?seccion=...` | Dashboard y secciones |

El enrutador delega en `AdminController`, que valida la sección contra
`Administrador::seccionesPermitidas()` según el rol.

- **Admin:** dashboard, productos, variantes, categorías, marcas, garantías, cupones,
  envíos, pedidos, usuarios, configuración, auditoría, vendedores, comisiones.
- **Vendedor:** dashboard, productos, variantes, pedidos, ventas. Ve solo sus propios
  productos y su comisión.

<!-- CONTINUAR -->
