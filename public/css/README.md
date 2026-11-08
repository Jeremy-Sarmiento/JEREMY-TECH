# Organización de estilos

Los CSS se sirven directamente, sin compilador ni `@import`. Las vistas de tienda
y administración usan `views/partials/estilos.php`; el orden de los archivos está
definido en `config/estilos.php`. Cada enlace tiene su propia versión `filemtime`.

```text
css/
  base/          Documento, variables de compatibilidad y ajustes responsive base
  componentes/   Catálogo, tarjetas, búsqueda y chat
  paginas/       Compra, pedidos, contenido, autenticación y administración
    cuenta/      Perfil, favoritos/notificaciones, pedidos/reseñas, vendedor y soporte
  tema/          Variables del tema y ajustes visuales finales por responsabilidad
```

## Orden y alcance

Tienda: `base`, `carrito`, `auth`, Leaflet solo en login, `cuenta`, `chat`,
`admin` si corresponde, `tema`.

Panel administrativo: `admin`, `tema`. No recibe las bases de la tienda.

Los nombres de carpetas describen responsabilidades, no condiciones de carga por
ruta. Por ejemplo, autenticación también contiene el menú de cuenta, las reseñas
se usan en las fichas y soporte reutiliza formularios de perfil. Todos los módulos
de cada grupo siguen cargándose donde se cargaba la hoja original.

## Localizar el código

| Hoja anterior | Módulos actuales, en el mismo orden |
| --- | --- |
| `styles.css` | `base/documento.css`, `componentes/catalogo.css`, `paginas/compra.css`, `paginas/pedidos.css`, `base/responsive.css`, `componentes/catalogo-producto.css`, `componentes/busqueda.css`, `paginas/contenido.css` |
| `carrito.css` | `paginas/carrito.css` |
| `auth.css` | `paginas/auth.css` |
| `cuenta.css` | `paginas/cuenta/perfil.css`, `favoritos-notificaciones.css`, `pedidos-resenas.css`, `vendedor.css`, `soporte.css` (todos dentro de `paginas/cuenta/`) |
| `chat.css` | `componentes/chat.css` |
| `admin.css` | `paginas/admin.css` |
| `rediseno.css` | `tema/base.css`, `navegacion.css`, `catalogo.css`, `producto.css`, `compra.css`, `auth.css`, `cuenta.css`, `contenido.css`, `chat.css`, `admin.css`, `responsive.css` (todos dentro de `tema/`) |

Para cambiar el diseño actual, revisar primero `tema/`: sus reglas se aplican
después de las bases. El login ancho y centrado, con encabezado centrado y campos
alineados a la izquierda, está en `tema/auth.css`; sus ajustes móviles finales,
en `tema/responsive.css`.

Se conservaron bloques contiguos, declaraciones, fallbacks, animaciones y orden.
Algunos módulos incluyen reglas compartidas o correcciones tardías de otro ámbito.
No agrupar automáticamente los `@media` ni borrar un selector repetido: las capas
posteriores pueden complementar, en vez de reemplazar, propiedades anteriores.
No se cambiaron colores ni se convirtió la paleta a HSL.

## Mantenimiento y despliegue

- Registrar cualquier hoja nueva en `config/estilos.php`, dentro de su grupo.
- Mantener el tema al final. No cargar un módulo de tema junto a su base.
- Resolver cualquier `url(...)` relativa desde la carpeta del archivo CSS que la
  contiene. Las URLs `data:` no necesitan ajustes al mover archivos.
- Desplegar juntos CSS, configuración y ambas cabeceras: las rutas anteriores
  ya no son puntos de entrada.
- La división aumenta el número de archivos enlazados. Se cargan directamente y
  se versionan por separado; no añade cascadas de descubrimiento mediante imports.

Comprobaciones sin base de datos:

```bash
vendor/bin/phpunit --no-coverage tests/Unit/EstilosCargaTest.php tests/Unit/EstilosPlanosTest.php
```

Las pruebas comprueban registro completo sin duplicados, orden de enlaces,
versionado, aislamiento del panel, Leaflet y los únicos degradados permitidos.
Después de mover reglas, comparar también el renderizado antes y después.
