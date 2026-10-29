# Jeremy Tech

Tienda de tecnología en PHP puro (MVC propio) con panel de
administración y soporte multi-vendedor. BD MariaDB `jeremy_tech`.

**Stack:** PHP 8.1+ (probado en 8.5) · MariaDB 10.11+ · PDO `utf8mb4`

## Entregables

Informes PDF de las sesiones de desarrollo:

- [Sesión 1](entregables/Sesion-001-A3-2-Entregable.pdf)
- [Sesion 2 - El Puente Digital — Reporte Técnico](entregables/El%20Puente%20Digital%20—%20Reporte%20Técnico.pdf)
- [Sesion 2 - La Radiografía Técnica](entregables/La%20Radiografía%20Técnica%20—%20Sesión%202.pdf)
- [Sesión 3](entregables/Sesion-003-A3-2-Entregable.pdf)

## Instalación

```bash
mysql -u root -p < database/jeremy_tech_full.sql
cp .env.example .env   # rellenar DB_USER / DB_PASS
php -S 127.0.0.1:8000 -t public
```

El volcado incluye `CREATE DATABASE` y los datos: impórtalo sobre una
instalación vacía (no es idempotente).

## Credenciales

| Rol | Usuario / email | Contraseña |
| --- | --- | --- |
| Admin | `jeremytech` | `jeremytech` |

## Registro de clientes

El formulario `?ruta=login&tab=registro` pide los mismos datos que la
Sesión 8: nombre/razón social, apellidos (opcionales para empresas), DNI,
teléfono, dirección de envío, correo (usuario), contraseña y confirmación,
tipo de cliente **B2C/B2B/C2C**, RUC para B2B, plataforma de reventa para C2C
y coordenadas opcionales (mapa Leaflet/OpenStreetMap).

La primera dirección se guarda como `Principal` y predeterminada en
`direcciones`; la zona de envío se elige en el mismo formulario porque el
checkout calcula el costo con la zona de la dirección. La pregunta de
seguridad se conserva para la recuperación de contraseña sin correo.

## Roles: cliente y vendedor

Toda cuenta se registra como **cliente** y elige su tipo comercial
(B2C, B2B o C2C). Comprar está disponible desde el primer momento; **publicar
requiere `rol = 'vendedor'`**. El tipo no habilita la venta: solo decide qué
panel ve el vendedor.

1. El cliente envía la solicitud desde **Mi perfil → Vender en Jeremy Tech**
   (`POST ?ruta=perfil/solicitar-vendedor`).
2. El administrador la revisa en **Panel → Solicitudes**
   (`?ruta=admin/solicitudes`), donde el menú muestra las pendientes.
3. Al aprobar, solo cambia `users.rol`; se conservan tipo, empresa, RUC,
   dirección, teléfono y claves. Cada publicación queda atada a
   `productos.vendedor_id` y el reparto de comisiones se congela al comprar.

El panel de vendedor (`?ruta=mis-productos`) tiene tres vistas separadas
según el tipo: B2C (persona), B2B (empresa, con ficha fiscal y compradores)
y C2C (segunda mano, con stock y reputación).

## Catálogo y buscador

`?ruta=catalogo` combina búsqueda y filtros en la misma consulta:

- **Categoría** (`categoria`), **marca** (`marca`), **rango de precio**
  (`precio_min`, `precio_max`) y **orden** (`orden`: relevancia, nombre,
  precio ascendente o descendente).
- El buscador (`q`) sigue recorriendo nombre, modelo, SKU, categoría y
  especificaciones. Los filtros y la búsqueda se pueden combinar; las URLs
  son compartibles (`?ruta=catalogo&categoria=Laptops&marca=Apple`).
- La barra de resultados muestra el **total de productos** y, cuando hay
  variantes, también cuántas publicaciones los agrupan.
- La paginación es numerada y de **12 tarjetas por página**. Se pagina por
  tarjeta (`categoria` + `nombre`), no por fila: las variantes de un mismo
  producto nunca se separan entre dos páginas.
- Al ordenar por precio o nombre, el catálogo se muestra en una sola grilla
  para que el orden se aplique a todos los resultados, no solo dentro de cada
  categoría.
- Los chips de filtros activos permiten quitar uno a uno; cualquier cambio
  vuelve a la página 1. El filtro de vendedor (`vendedor`) y la búsqueda se
  conservan al cambiar el resto de filtros.

La búsqueda usa `LIKE` sobre el texto normalizado porque necesita
coincidencias parciales (`%son%`). Un índice `FULLTEXT` queda como mejora
opcional para catálogos mucho más grandes: habría que cambiar la consulta a
`MATCH ... AGAINST` y aceptar la semántica de palabras completas/prefijos.
La paginación en servidor ya evita cargar todo el catálogo en cada visita.

### Búsqueda con filtros activos (Bloque 5)

La barra de búsqueda del encabezado (`views/layout/header.php`) incluye
campos ocultos para `categoria`, `marca`, `precio_min`, `precio_max` y `orden`,
tomados de la URL actual. Al enviar una nueva búsqueda desde la barra
superior se **conservan los filtros activos**: la búsqueda se aplica dentro
del contexto del filtro (por ejemplo, buscar "mac" con la categoría `Laptops`
y la marca `Apple` sigue acotada a esos valores).

El mismo criterio ya se aplicaba a los chips de filtros activos y al
`limpiarBusqueda()` del script principal; con este cambio la coherencia queda
completa entre: navegación, chips, formulario de filtros, búsqueda y botón
de limpiar.

## Panel del vendedor

`?ruta=mis-productos` no solo publica: también resume la actividad comercial
del vendedor. La vista depende de su `tipo` (B2C, B2B o C2C).

- **Mis ganancias**: vendido sin IGV, comisión de la plataforma, neto,
  liquidado y saldo pendiente. Solo suma pedidos cobrados.
- **Quién me compró**: comprador, producto, cantidad, subtotal, pedido y
  estado del pago, acotado a las líneas que pertenecen al vendedor.
- **Historial de mi stock**: últimos 20 movimientos (ventas, reembolsos y
  ajustes) de sus propias publicaciones.
- **Mi reputación**: promedio y últimas calificaciones recibidas como
  vendedor.

Todo se calcula desde `comisiones`, `detalle_pedido`,
`movimientos_inventario` y `calificaciones_vendedor`, sin tablas nuevas, y
cada consulta se acota por el `vendedor_id` de la sesión.

### Variantes

Cada publicación puede tener variantes propias (color, capacidad, talla) con
su precio extra y su stock. Se gestionan desde la misma ficha de edición
(`?ruta=mis-productos/editar&slug=...#variantes`): crear, editar,
activar/ocultar y eliminar. La ficha pública solo muestra las activas y limita
la compra al menor stock entre producto y variante. Las ventas pasadas
conservan el nombre y el precio de la variante aunque luego se elimine.

### Archivar y eliminar

- **Archivar** saca la publicación del panel y del catálogo sin borrar nada:
  conserva ventas, reseñas y movimientos. Aparece en la pestaña **Archivados**
  y se puede restaurar (vuelve como oculta para revisar el stock).
- **Eliminar** borra definitivamente solo publicaciones sin historial. Si el
  producto tiene líneas de pedido, movimientos de inventario o reseñas, la
  operación se rechaza y se ofrece archivarlo.

El archivo se guarda en `productos.archivado_en` (columna añadida en el
Bloque 2). Una publicación archivada nunca se vende, aunque un administrador
la reactive: el catálogo, la ficha y el carrito también filtran por esa
columna.

### Calificaciones al vendedor

En **Mis pedidos**, cada pedido pagado muestra un widget para calificar a
cada vendedor distinto que participó (una sola vez por vendedor, aunque haya
varias líneas suyas). Es independiente de la reseña del producto:

- Solo puede calificar quien compró y pagó algo a ese vendedor; la compra se
  verifica contra `detalle_pedido` y `pedidos` en cada guardado.
- La calificación va de 1 a 5 estrellas y admite un comentario de hasta 500
  caracteres.
- `calificaciones_vendedor` tiene `UNIQUE(vendedor_id, cliente_id)`: volver a
  calificar actualiza la fila, no la duplica.
- El promedio se muestra en la ficha del producto, junto al nombre del
  vendedor, y en el panel **Mi reputación** del vendedor.

## Pagos simulados

El checkout **no cobra dinero real**. El flujo simula una pasarela para probar
tarjeta, Yape y los estados del pedido:

| Método | Prueba | Resultado |
| --- | --- | --- |
| Tarjeta | `4111 1111 1111 1111`, vence `12/40`, CVV `123` | Pedido y pago `Pagado` |
| Tarjeta | `4000 0000 0000 0002`, vence `12/40`, CVV `123` | Pago rechazado, no se crea pedido |
| Yape | Celular `987654321` y código `849201` | Pedido y pago `Pagado` |
| Yape | Código `000000` | Pago rechazado, no se crea pedido |
| Transferencia | Cualquier operación de 4 a 15 caracteres | Pedido `Pendiente`, pago `En verificación` |
| Contraentrega | Sin datos adicionales | Pedido y pago `Pendiente` |

Los números de tarjeta y códigos se validan en el servidor, pero **no se
almacenan**. La referencia `SIM-...` sí queda en `pagos.transaccion_id` para
poder rastrear la operación de demostración.

### Datos del cliente en el checkout (Bloque 6)

El checkout **no escribe en `users`**. Los datos de los pasos **1. Datos del
Cliente y Entrega** (nombre, teléfono, correo) y **2. Comprobante de Pago
(SUNAT)** (DNI, RUC, razón social, boleta/factura) son del **pedido**, no del
perfil:

- el perfil se edita en **Mi perfil** y en el registro; el checkout no lo
  sobrescribe;
- el selector Boleta/Factura ya **no cambia el tipo de cuenta**
  (`users.tipo` conserva el tipo con que se registró la cuenta);
- la dirección nueva sí se persiste, pero en `direcciones` (la tabla del
  cliente), no en `users.direccion`;
- el correo de la cuenta nunca se modifica porque es la identidad.

Si el pago se rechaza, no se descuenta stock ni se crea pedido; si se aprueba,
el pedido, el detalle, el pago y la dirección nueva quedan en una sola
transacción.

### Analítica avanzada (Bloque 8)

El dashboard de analítica (`?ruta=admin/analitica`) calcula, para un rango de
fechas (`desde` / `hasta`) y para un vendedor acotado o global:

- **Resumen**: visitas, vistas de producto, búsquedas, agregados al carrito,
  checkouts, compras, ingresos en S/ y tasa de conversión (compras / visitas).
- **Eventos registrados**: total de eventos del periodo.
- **Top productos**: productos con más interacciones (vista, agregar o compra).
- **Top páginas**: páginas con más visitas (`visita`).
- **Top búsquedas**: términos de búsqueda más usados.
- **Chatbot**: distribución FAQ / IA / fallback y total.

Los eventos se registran en `eventos_analitica` (`visita`, `producto_visto`,
`busqueda`, `agregar_carrito`, `iniciar_checkout`, `compra`, `clic_whatsapp`,
`pregunta_chatbot`) y se disparan desde el storefront (`public/js/analitica.js`)
y desde el servidor (compras, reembolsos). El endpoint público
`POST ?ruta=analitica/evento` valida el tipo y los campos permitidos antes de
escribir.

### Motor de recomendaciones (Bloque 11)

La ficha pública (`?ruta=producto&slug=...`) muestra una sección
**“Recomendados para ti”** con productos de la misma categoría, excluyendo la
publicación actual. El orden prioriza afinidad de marca y cercanía de precio.

- La regla vive en `Model\Producto::recomendados()` (misma categoría activa y
  publicada, sin autocomparación, agrupación por nombre comercial);
- `Model\Producto::puntuacionRecomendado()` calcula la afinidad (misma marca +
  precio cercano);
- las tarjetas se renderizan con el partial `views/partials/card.php`, igual
  que el catálogo, para reutilizar el carrusel, favoritos y botón de carrito.

## Pasarela real y webhook

El checkout sigue siendo simulado por defecto. Para conectar una pasarela
externa se configura el secreto compartido en `.env`:

```dotenv
PAGOS_MODO=produccion
PAGOS_PROVEEDOR=culqi        # etiqueta libre: se guarda en pagos.pasarela
PAGOS_WEBHOOK_SECRET=...     # secreto HMAC compartido con la pasarela
PAGOS_MONEDA=PEN
```

Con `PAGOS_WEBHOOK_SECRET` definido queda activo
`POST index.php?ruta=pagos/webhook`. El contrato es agnóstico al proveedor:
la pasarela crea el pago y este endpoint lo concilia. El checkout de la tienda
sigue en modo simulado hasta conectar el adaptador de creación de pagos del
proveedor elegido (Culqi, Mercado Pago, Stripe, etc.).

| Cabecera | Contenido |
| --- | --- |
| `X-JT-Timestamp` | Unix timestamp en segundos |
| `X-JT-Signature` | `HMAC-SHA256(timestamp . "." . cuerpo_crudo)` en hex |
| `X-JT-Gateway` | Nombre de la pasarela (opcional) |

Cuerpo JSON:

```json
{
  "id": "evt_2026_0001",
  "type": "payment.updated",
  "data": {
    "pedido_id": 42,
    "referencia": "TX-123",
    "estado": "approved",
    "metodo": "Tarjeta"
  }
}
```

El pago se localiza por `pago_id`, `pedido_id` o `referencia`
(`pagos.referencia_externa` / `pagos.transaccion_id`). Estados aceptados:
`approved|paid|succeeded|pagado`, `rejected|failed|declined|rechazado`,
`refunded|reembolsado` y `pending|processing|pendiente`.

Garantías del endpoint:

- firma HMAC verificada con `hash_equals`;
- ventana de tolerancia de **5 minutos** contra replay;
- **idempotencia** por `(pasarela, evento_id)` en `pagos_webhooks`: un
  reintento del proveedor devuelve `200` sin volver a aplicar el evento;
- un pago aprobado confirma un pedido `Pendiente`; uno rechazado lo cancela y
  devuelve stock; un reembolso cancela y devuelve stock;
- la conciliación corre en una transacción con `FOR UPDATE` sobre el pedido.

El panel (`?ruta=admin/pagos`) muestra los pagos, la configuración activa y la
bitácora de webhooks. Para probar en local:

```bash
php tools/webhook-prueba.php --pedido=42 --estado=approved
```

El script firma el evento con el mismo `PAGOS_WEBHOOK_SECRET` y llama al
endpoint; no requiere sesión ni CSRF.

## Tickets de soporte

Cualquier cliente autenticado abre tickets en `?ruta=soporte` (también desde
«Mis pedidos» → «Necesito ayuda con este pedido»). El ticket puede asociarse a
un pedido propio; la pertenencia se valida en el servidor.

El panel los atiende en `?ruta=admin/soporte`: responder guarda el texto en el
ticket, lo pasa a `en_proceso` y crea un aviso `soporte_respuesta` en
`notificaciones` + un `.eml` simulado. Los estados son `abierto`,
`en_proceso`, `resuelto` y `cerrado`. No se añadió una tabla de mensajes: cada
ticket tiene una respuesta del equipo.

## Moderación de reseñas

Las reseñas se **publican al instante**. Si una incumple las normas, un
administrador puede ocultarla desde `?ruta=admin/resenas` indicando un motivo;
deja de aparecer en la ficha del producto y en el promedio de estrellas. La
reseña sigue siendo visible para su autor en «Mis pedidos» con la marca
«Oculta por moderación», y el admin puede volver a publicarla.

El filtro `estado = 'visible'` se aplica en todas las consultas públicas
(`Resena::deProducto`, `Resena::resumenDeProducto`,
`Resena::resumenDeProductos`).

## Seguimiento y correos simulados

El historial de **Mis Pedidos** muestra una línea de tiempo con:

- pedido recibido;
- pago confirmado, pendiente o en verificación;
- en preparación;
- enviado (con courier y número de guía) o listo para recojo;
- entregado o cancelado.

El seguimiento es **privado**: se consulta únicamente dentro de la cuenta del
cliente. No hay página pública de seguimiento.

### Correos

No se envía correo real: no se usa SMTP ni `mail()`. Solo se generan dos avisos:

1. **Pago confirmado**, al aprobar tarjeta/Yape en el checkout o cuando el
   administrador marca como `Pagado` una transferencia/contraentrega.
2. **Pedido enviado**, al marcar `Enviado` con courier y guía. En pedidos de
   recojo se muestra como **Listo para recojo** y no exige guía.

Cada correo se guarda en `MAIL_STORAGE_PATH/AAAA/MM/` como `.eml` y `.html`, y
se registra en `notificaciones` con `enviado = 0` (modo simulado). El contenido
completo queda en el archivo; en la tabla solo se guarda un resumen de 500
caracteres. No se almacenan datos de tarjetas.

`MAIL_STORAGE_PATH` debe estar fuera del DocumentRoot. Este Apache usa
`AllowOverride None`, así que los `.htaccess` del proyecto no se aplican. En
este servidor los correos se guardan en `/dev/shm/jeremy-tech-mail`,
configurado en `.env`, con ACL de escritura para el usuario `http`. Se usa
`/dev/shm` porque el servicio tiene `ProtectHome=on` (bloquea `/home`) y
`PrivateTmp=on` (aísla `/tmp` y `/var/tmp`). Es almacenamiento temporal en
memoria: se pierde al reiniciar el servidor. El primer correo recrea el
directorio; si necesitas inspeccionarlo como `jeremy`, vuelve a aplicar la ACL
o usa una ruta persistente. El fallback del código es `storage/mail/`; el
código rechaza escribir si la ruta cae dentro del DocumentRoot. Para
producción conviene definir una ruta persistente fuera del DocumentRoot y con
permisos para `http`.

El fallback `storage/mail/` está excluido de Git; el directorio de
almacenamiento privado activo (`/dev/shm/jeremy-tech-mail`) tampoco forma parte
del repositorio.

Si el enlace del correo debe apuntar a una URL absoluta concreta, define
`APP_URL` en `.env` (por ejemplo `APP_URL=https://tienda.ejemplo.com`). Si
no se define, en web se deduce del host de la petición; en CLI se usa
`http://localhost`. Los enlaces incluyen `index.php` explícitamente porque en
este Apache el `DirectoryIndex` global solo sirve `index.html`.

## Integración continua y vista en GitHub Pages

`.github/workflows/ci.yml` corre en cada push y pull request: sintaxis
(`php -l`), estilo (`composer cs-check`), análisis estático (`composer stan`),
PHPUnit contra un MariaDB temporal con el dump sanitizado y un smoke test HTTP
(`tools/smoke-test.sh`) que comprueba portada, login, catálogo, la redirección
del panel y el rechazo de POST sin CSRF.

`.github/workflows/pages.yml` publica en GitHub Pages una copia estática de la
portada generada con `tools/build-preview.sh` (renderiza el catálogo con la
base importada y copia `public/css`, `public/js` y `public/img`). Para verla,
activa en el repositorio **Settings → Pages → Source: GitHub Actions**. Pages
no ejecuta PHP: solo se ve la maqueta de la portada, y los enlaces con
`?ruta=...` recargan esa misma página.

En local, con la base importada:

```bash
bash tools/smoke-test.sh          # smoke test HTTP
bash tools/build-preview.sh _site # genera la vista estática
```

## Entorno

`.env` es la fuente principal de configuración. `config/conexion.php` lo lee
primero; si existe `.ht.env`, después completa las claves ausentes para
mantener compatibilidad con instalaciones anteriores. Cualquier valor definido
en `.env` tiene prioridad; usa `.env.example` como plantilla. En esta
instalación `.ht.env` ya no existe: `.env` es la única configuración activa.

En este servidor Apache el archivo `.env` no se sirve por HTTP: la regla
`/etc/httpd/conf/conf.d/jeremy-tech-env.conf` deniega `/.env` y el usuario
`http` solo tiene una ACL de lectura para que PHP pueda cargarlo. Mantén el
archivo en `600` y, si lo recreas, vuelve a aplicar
`setfacl -m u:http:r .env`; sin esa ACL PHP no puede leerlo. Si mueves el
proyecto a otro servidor con el DocumentRoot apuntando a la raíz del proyecto,
replica esa protección (o usa un DocumentRoot en `public/`). La regla global
`<Files ".ht*">` bloquearía cualquier `.ht.env` que se recreara.

`AllowOverride None` también significa que los `.htaccess` del proyecto no
bloquean `src/`, `views/` ni `database/` en este servidor. Los correos ya se
guardan fuera del DocumentRoot; para una defensa completa, configura el
DocumentRoot en `public/` o añade reglas `<Directory>` en Apache.

## Estructura

```
public/index.php    Único punto de entrada (enruta admin y tienda)
src/Controller/     Controladores (Producto, Carrito, Login, Admin, ...)
src/Model/          Modelos con SQL directo vía PDO
views/              Vistas de tienda + views/admin/ (panel y secciones)
config/conexion.php Carga .env, autocarga PSR-4 y singleton PDO
database/           jeremy_tech_full.sql (esquema + datos)
```

## Rutas

Todas pasan por `?ruta=...` (`public/index.php`).

**Tienda:** `catalogo` · `producto` · `carrito` (+ `agregar`, `cambiar-cantidad`,
`eliminar`, `aplicar-cupon`, `procesar`) · `login` (+ `registrar`, `iniciar`,
`cerrar`, `recuperar`, `restablecer`, `recuperar-pregunta`) · `direcciones`
(+ `guardar`, `eliminar`, `predeterminada`)

**Recuperación de contraseña:** `login/recuperar` solicita un enlace por correo
y `login/restablecer&token=...` fija la nueva clave. El token vive en
`tokens_recuperacion` (hash SHA-256, 30 minutos, un solo uso). Login y
recuperación usan el límite persistente de `intentos_acceso` (5 intentos por
15 minutos, bloqueo de 15 minutos). La pregunta de seguridad sigue disponible
como alternativa.

**Información:** `pagina&p=privacidad` · `pagina&p=terminos` · `pagina&p=envios`
· `pagina&p=faq` · `pagina&p=contacto`. Son páginas GET sin formularios; la de
envíos consulta las tarifas activas de `tarifas_envio` y la de contacto reutiliza
los datos de `configuracion`.

**Panel:** `admin/login` (autenticar, cerrar) · `admin/recuperar`
(+ `recuperar/restablecer`) · `admin?seccion=...`. Secciones según rol vía
`Administrador::seccionesPermitidas()`:

- **Admin:** dashboard, productos, variantes, categorías, marcas, garantías, cupones,
  envíos, pedidos, usuarios, configuración, auditoría, vendedores, comisiones.
- **Vendedor:** dashboard, productos, variantes, pedidos, ventas (solo los suyos).