# Jeremy Tech

Tienda de tecnología en PHP puro (MVC propio, sin Composer ni build) con panel de
administración y soporte multi-vendedor. BD MariaDB `jeremy_tech`.

**Stack:** PHP 8.1+ (probado en 8.5) · MariaDB 10.11+ · PDO `utf8mb4`

## Instalación

```bash
mysql -u root -p -e "CREATE DATABASE jeremy_tech CHARACTER SET utf8mb4;"
mysql -u root -p jeremy_tech < database/jeremy_tech.sql
cp .env.example .env   # rellenar DB_USER / DB_PASS
php -S 127.0.0.1:8000 -t public
```

El `.sql` es idempotente: puede re-ejecutarse sin dañar datos.

## Credenciales

| Rol | Usuario / email | Contraseña |
| --- | --- | --- |
| Admin | `jeremytech` | `jeremytech` |

## Estructura

```
public/index.php    Único punto de entrada (enruta admin y tienda)
src/Controller/     Controladores (Producto, Carrito, Login, Admin, ...)
src/Model/          Modelos con SQL directo vía PDO
views/              Vistas de tienda + views/admin/ (panel y secciones)
config/conexion.php Carga .env, autocarga PSR-4 y singleton PDO
database/           jeremy_tech.sql (esquema + semillas)
```

## Rutas

Todas pasan por `?ruta=...` (`public/index.php`).

**Tienda:** `catalogo` · `producto` · `carrito` (+ `agregar`, `cambiar-cantidad`,
`eliminar`, `aplicar-cupon`, `procesar`) · `login` (+ `registrar`, `iniciar`,
`cerrar`, `recuperar-pregunta`) · `direcciones` (+ `guardar`, `eliminar`,
`predeterminada`)

**Panel:** `admin/login` (autenticar, cerrar) y `admin?seccion=...`. Secciones según
rol vía `Administrador::seccionesPermitidas()`:

- **Admin:** dashboard, productos, variantes, categorías, marcas, garantías, cupones,
  envíos, pedidos, usuarios, configuración, auditoría, vendedores, comisiones.
- **Vendedor:** dashboard, productos, variantes, pedidos, ventas (solo los suyos).
