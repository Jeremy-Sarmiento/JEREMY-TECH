# Examen de recuperación: resumen de las sesiones 1 a 8

## Datos del proyecto

- **Estudiante:** Jeremy Sarmiento Tapia, según los entregables anteriores.
- **DNI:** no proporcionado; debe completarlo el estudiante.
- **Proyecto:** Jeremy Tech, tienda de tecnología orientada a clientes y negocios de La Joya, Arequipa.
- **Asignatura:** Negocios Electrónicos y E-business.
- **Versión documentada:** rama `postgresql`, con PHP 8.2+, arquitectura MVC propia, JavaScript y PostgreSQL mediante PDO.
- **Repositorio:** <https://github.com/Jeremy-Sarmiento/JEREMY-TECH>.

### Criterio de elaboración

Este documento responde a la consigna de la imagen: resumir los temas principales de cada sesión, relacionarlos con el proyecto, incluir código fuente numerado y agregar diez preguntas al final. El orden y los dos entregables de cada sesión se tomaron de `/srv/http/sesion-08/recorrido.js`, en la definición `SESIONES`.

La referencia corresponde a Grafiluz; aquí se adapta a Jeremy Tech. Las sesiones organizan pedagógicamente las funcionalidades actuales, no representan una reconstrucción de las fechas de cada cambio. Los objetivos comerciales de los informes anteriores se distinguen de las funciones implementadas: una propuesta de facturación electrónica, por ejemplo, no demuestra una integración con SUNAT.

Los fragmentos numerados son extractos del código del proyecto. El número de la izquierda corresponde a la línea del archivo de origen y no forma parte del código ejecutable. El código completo se consulta en los enlaces de cada sesión.

## Orden de las sesiones

| Sesión | Tema principal | Entregables del recorrido |
| --- | --- | --- |
| 1 | Catálogo base e identidad del negocio | El ADN Corporativo; Los Planos de Transformación |
| 2 | Puente Digital y servicios externos | La Radiografía Técnica; El Puente Digital |
| 3 | Mi Pedido y modelo relacional | La Radiografía Comercial; El Plano Maestro de la Memoria |
| 4 | Buscador y organización del catálogo | El Mapa Maestro; La Arquitectura del Gigante |
| 5 | Analítica y captación de visitas | El Lanzamiento Invisible; La Tienda que Observa |
| 6 | Asistente Virtual | El Benchmark Tecnológico; El Asistente Virtual en Acción |
| 7 | Pago y confirmación mediante webhook | La Transacción Perfecta; El Panel de Control Inteligente |
| 8 | Ética, seguridad y publicación | La Tienda Virtual Creada y Publicada; Informe de Ética y Ciberseguridad |

## Sesión 1: catálogo base e identidad del negocio

### Resumen

La primera sesión estudia cómo un negocio define su identidad antes de transformarse digitalmente. En Jeremy Tech, el punto de partida es una tienda que presenta celulares, laptops, audífonos y otros productos tecnológicos mediante un catálogo y facilita la consulta por WhatsApp. Esta vitrina permite mostrar la oferta, pero no equivale por sí sola a automatizar una compra. La transformación consiste en conectar la presentación comercial con cuentas de usuario, inventario, pedidos, pagos y atención posterior.

### Aspectos más relevantes

1. **ADN corporativo:** identificar el público, el problema que se busca resolver y la propuesta de valor. El proyecto plantea acercar la tecnología a consumidores y negocios de La Joya, con información de productos y asesoramiento.
2. **Estado As-Is:** catálogo y atención comercial con intervención humana para confirmar disponibilidad, pago y entrega, según el informe inicial del proyecto.
3. **Estado To-Be:** compra desde la web con carrito, registro del pedido, control de stock y seguimiento de la operación. La plataforma actual desarrolla parte de este objetivo.
4. **Separación de responsabilidades:** HTML y CSS presentan la tienda; JavaScript gestiona interacciones; PHP procesa las operaciones; PostgreSQL conserva la información.

### Aplicación y entregables

- **El ADN Corporativo:** Jeremy Tech propone una oferta especializada de tecnología para personas y empresas. Su valor consiste en facilitar la consulta, comparación y compra, acompañada de atención y gestión de pedidos. Los plazos de entrega y garantías comerciales deben respaldarse con políticas reales; no se deducen del código.
- **Los Planos de Transformación:** el flujo inicial es `cliente → catálogo → consulta por WhatsApp → coordinación manual`. El flujo objetivo es `cliente → catálogo → carrito → pedido → pago → seguimiento`. El código actual usa un punto de entrada, controladores, modelos y vistas, en lugar de concentrar toda la lógica en una sola página.

### Código fuente numerado

Las categorías del catálogo se obtienen desde la base de datos, permitiendo administrarlas sin mantener una lista fija en el HTML.

**Fuente del extracto:** [`src/Model/Producto.php`, líneas 18-25](../src/Model/Producto.php#L18-L25).

```text
18 |     public static function grids(): array
19 |     {
20 |         $grids = [];
21 |         foreach (conexion()->query('SELECT id, nombre FROM categorias ORDER BY id')->fetchAll() as $categoria) {
22 |             $grids[(string) $categoria['nombre']] = 'grid-categoria-' . (int) $categoria['id'];
23 |         }
24 |         return $grids;
25 |     }
```

**Material del proyecto:** [informe de la sesión 1](Sesion-001-A3-2-Entregable.pdf), [ADN corporativo](entregables2/Plantilla-ADN-Corporativo.html) y [planos de transformación](entregables2/Plantilla-Planos-Transformacion.html).

## Sesión 2: Puente Digital y servicios externos

### Resumen

La segunda sesión diferencia el comercio electrónico de la estrategia más amplia de e-business. El e-commerce comprende la interacción comercial y el proceso de compra; el e-business también incluye sistemas que ayudan a operar y tomar decisiones. En Jeremy Tech, el Puente Digital consulta una API de tipos de cambio para presentar precios en otras monedas a partir del precio base en soles. Esta integración apoya la comparación de productos, pero no cobra dinero ni sustituye una pasarela de pagos.

### Aspectos más relevantes

1. **API:** interfaz mediante la cual un sistema solicita datos a otro. El proyecto utiliza el endpoint `https://open.er-api.com/v6/latest/PEN`.
2. **Asincronía:** `fetch` solicita los datos sin recargar toda la página y la respuesta JSON contiene las tasas que utiliza la interfaz.
3. **Validación y errores:** la aplicación debe comprobar la respuesta y evitar presentar una conversión como válida cuando no dispone de tasas utilizables.
4. **Moneda base:** el precio almacenado en PEN sigue siendo la referencia. Mostrar otra moneda no demuestra que el cobro se liquide en esa moneda.

### Aplicación y entregables

**La Radiografía Técnica:** clasificación de procesos de Jeremy Tech.

| Proceso | Relación con el negocio |
| --- | --- |
| Catálogo y selección de productos | Canal comercial del e-commerce. |
| Carrito, pedido y pago | Flujo transaccional del e-commerce. |
| Conversión de moneda | Integración de e-business que apoya la consulta. |
| Gestión de inventario y reportes | Operación interna de e-business. |
| Analítica y atención automatizada | Apoyo a la mejora del negocio. |

**El Puente Digital:** la conversión sigue el esquema `precio mostrado = precio en PEN × tasa de la moneda seleccionada`. Las tasas son las publicadas por el proveedor, no cotizaciones financieras garantizadas al instante. Si el proveedor falla, la interfaz debe comunicarlo y conservar una referencia válida en soles.

**Límite actual:** `convertirPrecio()` devuelve el importe PEN si falta una tasa, pero la actualización de tarjetas puede rotularlo con la moneda seleccionada. Este caso debe revisarse antes de presentar el resultado como una conversión correcta. Véase [la conversión y actualización de precios](../public/js/puente-digital.js#L66-L99).

### Código fuente numerado

El script consulta la API, comprueba el estado HTTP y el resultado declarado por el proveedor, y captura un fallo de la consulta.

**Fuente del extracto:** [`public/js/puente-digital.js`, líneas 46-56](../public/js/puente-digital.js#L46-L56).

```text
46 |   try {
47 |     var respuesta = await fetch("https://open.er-api.com/v6/latest/PEN");
48 |     if (!respuesta.ok) throw new Error("API error: " + respuesta.status);
49 |     var datos = await respuesta.json();
50 |     if (datos.result !== "success") throw new Error("Invalid rates");
51 |     tasasDeCambio = datos.rates;
52 |     return true;
53 |   } catch (error) {
54 |     console.error("Error cargando tasas:", error);
55 |     return false;
56 |   }
```

**Material del proyecto:** [radiografía técnica](La%20Radiograf%C3%ADa%20T%C3%A9cnica%20%E2%80%94%20Sesi%C3%B3n%202.pdf), [reporte del Puente Digital](El%20Puente%20Digital%20%E2%80%94%20Reporte%20T%C3%A9cnico.pdf), [plantilla técnica](entregables2/Plantilla-Radiografia-Tecnica.html) y [plantilla de integración](entregables2/Plantilla-Reporte-Puente-Digital.html).

## Sesión 3: Mi Pedido y modelo relacional

### Resumen

La tercera sesión relaciona el modelo comercial con los datos necesarios para vender. Jeremy Tech toma B2C como enfoque principal y contempla operaciones B2B y publicaciones de terceros. Para que una compra pueda recuperarse y administrarse, el sistema separa usuarios, productos, carritos, pedidos, detalles y pagos. El carrito es una selección provisional; el pedido conserva el resultado de una operación con precios y cantidades definidos. La base relacional permite mantener estas asociaciones mediante claves y restricciones.

### Aspectos más relevantes

1. **B2C:** venta al consumidor final mediante el catálogo y el checkout.
2. **B2B:** compras para empresas, datos como RUC y razón social y precios por volumen. Registrar esos datos no equivale a emitir una factura electrónica autorizada.
3. **C2C y marketplace:** publicaciones de vendedores y separación de su catálogo. El tipo de cuenta no reemplaza la autorización por rol ni la habilitación del vendedor.
4. **Integridad:** una clave primaria identifica una fila; una clave foránea relaciona tablas y evita referencias inválidas.

### Aplicación y entregables

- **La Radiografía Comercial:** el modelo propuesto es híbrido: B2C para la compra individual, B2B para requerimientos empresariales y un componente de marketplace para vendedores. La rentabilidad y el volumen comercial no se pueden demostrar únicamente con datos de prueba.
- **El Plano Maestro de la Memoria:** estas son las relaciones principales del esquema actual; no sustituyen el DER completo del repositorio.

| Tabla | Responsabilidad | Relación principal |
| --- | --- | --- |
| `users` | Identidad, acceso y datos de la cuenta | Una cuenta puede tener varios pedidos. |
| `productos` | Catálogo, precio, stock y vendedor | Un producto puede aparecer en distintos detalles. |
| `producto_variantes` | Opciones de un producto | Cada variante pertenece a un producto. |
| `carritos` y `carrito_items` | Selección persistente por cuenta | Un carrito agrupa sus líneas. |
| `pedidos` | Cabecera, importes y estado | Cada pedido corresponde a un cliente. |
| `detalle_pedido` | Productos, cantidades y precio registrado | Cada línea pertenece a un pedido. |
| `pagos` | Operaciones de pago asociadas al pedido | Un pedido puede tener varios registros o intentos de pago. |

El esquema de esta rama contiene 33 tablas. Las relaciones anteriores describen solo su núcleo comercial, no todas las funciones de inventario, soporte, analítica y marketplace.

### Código fuente numerado

El detalle conserva el producto, nombre, cantidad, precio, variante y vendedor asociados a la compra. Los valores se envían como parámetros de la consulta preparada.

**Fuente del extracto:** [`src/Model/Carrito.php`, líneas 655-660](../src/Model/Carrito.php#L655-L660).

```text
655 |             $stmtDetalle = $pdo->prepare(
656 |                 'INSERT INTO detalle_pedido
657 |                     (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario_pen,
658 |                      variante_id, variante_nombre, vendedor_id)
659 |                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
660 |             );
```

**Material del proyecto:** [entregable de la sesión 3](Sesion-003-A3-2-Entregable.pdf), [radiografía comercial](entregables2/Plantilla-Radiografia-Comercial.html), [plano DER](entregables2/Plantilla-Plano-Maestro-DER.html) y [estructura PostgreSQL](../database/jeremy_tech_estructura.sql).

## Sesión 4: buscador y organización del catálogo

### Resumen

La cuarta sesión estudia cómo ordenar el catálogo para que el usuario encuentre productos sin recorrer toda la tienda. Jeremy Tech incorpora búsqueda, filtros y un modelo de consulta paginada, y organiza la presentación de las opciones de un producto. La referencia de clase explica un índice en memoria; la versión actual del proyecto resuelve el catálogo también desde PHP y PostgreSQL. La enseñanza común es limitar el trabajo de cada consulta y separar la organización del negocio de la presentación visual.

### Aspectos más relevantes

1. **Búsqueda y filtros:** reducir el catálogo según la necesidad del comprador, por ejemplo categoría, marca o texto de búsqueda.
2. **Paginación:** devolver un subconjunto de resultados en vez de presentar todo el catálogo en una sola respuesta.
3. **Arquitectura MVC:** el controlador recibe parámetros, el modelo consulta datos y la vista representa los resultados.
4. **Escalabilidad responsable:** paginación e índices son medidas concretas; no prueban que el sistema soporte el tráfico de Amazon o Alibaba.

### Aplicación y entregables

**El Mapa Maestro: Canvas propuesto para Jeremy Tech.**

| Bloque | Aplicación al proyecto |
| --- | --- |
| Segmentos de clientes | Consumidores de tecnología, estudiantes, profesionales y negocios. |
| Propuesta de valor | Catálogo especializado, comparación de opciones y gestión digital del pedido. |
| Canales | Tienda web y atención comercial por WhatsApp. |
| Relación con clientes | Cuentas, historial, soporte y asistente virtual. |
| Fuentes de ingresos | Venta de productos y comisiones del marketplace, según su configuración comercial. |
| Recursos clave | Catálogo, inventario, plataforma, base de datos y personal responsable. |
| Actividades clave | Publicación de productos, actualización de stock, atención y gestión de pedidos. |
| Socios clave | Proveedores de tecnología, servicios de alojamiento, operadores de pago y logística por contratar. |
| Estructura de costos | Mercadería, alojamiento, operación, atención, entregas y promoción. |

**La Arquitectura del Gigante:** la ruta lógica es `navegador → public/index.php → controlador → modelo/PDO → PostgreSQL → vista`. El diseño permite localizar errores y ampliar funciones sin mezclar consultas SQL con cada componente visual. Un crecimiento importante requeriría medir tiempos, revisar consultas, ajustar índices y evaluar infraestructura; no basta con declarar que la arquitectura es escalable.

**Límite actual:** el [controlador](../src/Controller/ProductoController.php#L19-L26) declara que ya no muestra paginador y solicita 250 grupos, mientras el [modelo](../src/Model/Producto.php#L279-L298) limita cada consulta a 48. Por ello, no se debe afirmar que la interfaz permite recorrer sin límites un catálogo grande: la navegación y sus topes necesitan revisión.

### Código fuente numerado

El modelo obtiene una página de publicaciones agrupadas por categoría, nombre y vendedor. Los filtros se pasan mediante parámetros y el límite y desplazamiento se calculan dentro del modelo.

**Fuente del extracto:** [`src/Model/Producto.php`, líneas 318-327](../src/Model/Producto.php#L318-L327).

```text
318 |         $stmt = $pdo->prepare(
319 |             'SELECT p.categoria, p.nombre, p.vendedor_id
320 |                FROM productos p
321 |               WHERE ' . $whereSql . '
322 |               GROUP BY p.categoria, p.nombre, p.vendedor_id
323 |               ORDER BY ' . self::ordenGruposCatalogo($orden) . '
324 |               LIMIT ' . $porPagina . ' OFFSET ' . $offset
325 |         );
326 |         $stmt->execute($parametros);
327 |         $gruposPagina = $stmt->fetchAll();
```

**Material del proyecto:** [Canvas](entregables2/Plantilla-Mapa-Maestro-Canvas.html) y [arquitectura lógica](entregables2/Plantilla-Arquitectura-Gigante.html).

## Sesión 5: analítica y captación de visitas

### Resumen

La quinta sesión conecta la estrategia comercial con la medición. No basta con publicar una tienda: es necesario saber qué productos se consultan y en qué etapa se abandona el proceso. Jeremy Tech dispone de analítica propia para registrar eventos y consultar resultados desde el panel. Estos registros permiten estudiar el recorrido del visitante, pero no demuestran por sí mismos que exista una campaña publicitaria activa ni que se hayan instalado Google Analytics o un píxel externo.

### Aspectos más relevantes

1. **Eventos:** representan acciones como una visita, una búsqueda, la consulta de un producto o una etapa del proceso de compra.
2. **Embudo:** organiza el recorrido desde el interés inicial hasta la compra; ayuda a localizar puntos de abandono.
3. **Indicadores:** productos consultados, búsquedas, pedidos, compras y clics de contacto aportan señales diferentes. Una visita no equivale a una venta.
4. **Privacidad:** la medición debe limitar los datos recolectados y no registrar contraseñas, tarjetas ni información innecesaria.

### Aplicación y entregables

**El Lanzamiento Invisible: plan de tráfico propuesto, no campaña ejecutada.**

1. Crear contenido de catálogo útil y específico para las categorías y productos de Jeremy Tech.
2. Redactar títulos y descripciones claros, orientados a búsquedas relacionadas con tecnología y atención en La Joya.
3. Compartir enlaces de productos en canales comerciales y, si se aprueba presupuesto, preparar anuncios segmentados.
4. Definir una acción objetivo para cada campaña, como consultar un producto, iniciar una compra o contactar por WhatsApp.
5. Comparar periodos y resultados antes de ampliar una campaña; no inventar tasas de conversión, ventas ni retorno de inversión.

**La Tienda que Observa:** el recorrido de estudio es `visita → producto consultado → carrito → inicio de checkout → compra`. El panel permite analizar eventos registrados por la aplicación. Las cantidades concretas deben obtenerse de la base o de una demostración, no de una captura ficticia.

**Límite actual:** el [frontend del checkout](../public/js/carrito.js#L277-L320) registra `compra` cuando se registra un pedido, incluso si el pago queda pendiente, y envía `pedido_id` sin `total_pen`. El [panel](../src/Model/Analitica.php#L76-L110) suma ingresos desde `total_pen` y calcula la conversión como compras entre visitas. Además, el evento `iniciar_checkout` está previsto en el modelo, pero no se encontró un emisor efectivo en el JavaScript revisado. Estos indicadores no son una conciliación de ventas cobradas ni un embudo completo ya demostrado.

### Código fuente numerado

Los eventos se guardan en PostgreSQL con una consulta preparada. El panel puede consultar después el periodo de interés; registrar un evento no verifica por sí mismo su significado comercial.

**Fuente del extracto:** [`src/Model/Analitica.php`, líneas 39-49](../src/Model/Analitica.php#L39-L49).

```text
39 |         $stmt = $pdo->prepare(
40 |             'INSERT INTO eventos_analitica (tipo_evento, datos_json, pagina, cliente_id)
41 |              VALUES (?, ?, ?, ?)'
42 |         );
43 |
44 |         return (bool) $stmt->execute([
45 |             mb_substr($tipoEvento, 0, 60),
46 |             $datosJson,
47 |             mb_substr(trim($pagina), 0, 80),
48 |             $clienteId,
49 |         ]);
```

**Material del proyecto:** [plan de tráfico](entregables2/Plantilla-Plan-de-Trafico.html) y [analítica](entregables2/Plantilla-Tienda-que-Observa.html).

## Sesión 6: Asistente Virtual

### Resumen

La sexta sesión analiza herramientas de comercio electrónico y automatización de la atención. En Jeremy Tech, el asistente utiliza preguntas frecuentes y datos del catálogo para orientar al visitante. Es importante distinguir un sistema de reglas de un modelo generativo: responder por palabras clave no significa utilizar siempre inteligencia artificial externa. El valor del asistente depende de la calidad de sus respuestas, su relación con la información real de la tienda y su capacidad de reconocer cuándo debe derivar la consulta a una persona.

### Aspectos más relevantes

1. **Base de conocimiento:** preguntas y respuestas administrables permiten atender consultas repetidas sin editar cada respuesta en el frontend.
2. **Contexto comercial:** la orientación debe respetar la disponibilidad y los datos del catálogo, en lugar de inventar productos o condiciones.
3. **Modalidad de respuesta:** el comportamiento depende del modo configurado; disponer de código para IA no prueba que un servicio externo esté activo.
4. **Atención humana:** una derivación oportuna es preferible a una respuesta que prometa descuentos, garantías o entregas no autorizadas.

### Aplicación y entregables

**El Benchmark Tecnológico: evaluación de herramientas complementarias.**

| Herramienta del benchmark | Uso evaluado | Distinción necesaria |
| --- | --- | --- |
| Tidio | Complementar la atención con un servicio de chat. | Su evaluación no demuestra que esté activo ni que exista derivación automática a un agente. |
| ExchangeRate-API | Obtener tasas para mostrar precios referenciales. | La consulta está en el Puente Digital; no es una pasarela de cobro. |
| Leaflet y OpenStreetMap | Presentar mapas para ubicaciones y direcciones. | Su operación en los formularios debe comprobarse en una demostración. |
| UptimeRobot | Supervisar disponibilidad y avisar de caídas. | No se acredita aquí un monitor configurado o una disponibilidad medida. |
| Stripe Sandbox o un mock de pago | Evaluar aprobaciones, rechazos y notificaciones sin dinero real. | El simulador propio no demuestra una integración con Stripe. |
| Lighthouse | Revisar rendimiento, accesibilidad y SEO técnico. | No se presentan puntuaciones sin un informe de ejecución. |

La comparación sigue el entregable existente, sin copiar sus cifras de precios, cuotas o rendimiento como si hubieran sido comprobadas hoy. La tienda sigue utilizando su backend PHP propio.

**El Asistente Virtual en Acción:** el controlador atiende consultas críticas de pedidos, FAQ, reglas y catálogo antes de intentar IA cuando existe configuración. Una consulta debe resolverse con información disponible y autorizada para el usuario. La demostración debe indicar si intervino la FAQ, una regla o un proveedor de IA y mostrar qué ocurre cuando no existe respuesta suficiente. No se debe presentar una respuesta genérica como confirmación de stock, autorización de venta o reserva. Si se activa IA externa, también debe revisarse qué datos e historial se envían al proveedor.

### Código fuente numerado

Una coincidencia en la FAQ permite responder desde la base de conocimiento administrable y registrar el origen de la respuesta, sin requerir una generación externa.

**Fuente del extracto:** [`src/Controller/ChatController.php`, líneas 72-80](../src/Controller/ChatController.php#L72-L80).

```text
72 |             $faq = ChatbotFaq::buscar($texto);
73 |             if ($faq !== null) {
74 |                 $respuesta = [
75 |                     'reply' => (string) ($faq['respuesta'] ?? ''),
76 |                     'links' => $this->defaultLinks(),
77 |                     'quick' => $this->defaultQuickReplies(),
78 |                 ];
79 |                 $this->registrarPregunta($texto, 'faq', (int) ($faq['id'] ?? 0));
80 |                 $this->responder($texto, $respuesta);
```

**Material del proyecto:** [benchmark](entregables2/Plantilla-Benchmark-Tecnologico.html) y [asistente virtual](entregables2/Plantilla-Asistente-Virtual.html).

## Sesión 7: pago y confirmación mediante webhook

### Resumen

La séptima sesión estudia cómo completar la compra y confirmar el resultado del pago. Jeremy Tech distingue los datos de pedido y pago; en su checkout actual valida primero el resultado simulado y después registra la operación aceptada o pendiente. También existe un endpoint separado para webhooks con un contrato HMAC propio, no un adaptador acreditado de una pasarela comercial. Un webhook es una notificación recibida por el servidor para procesar un resultado; no se debe confiar únicamente en lo que indique el navegador.

### Aspectos más relevantes

1. **Pedido y pago son distintos:** un pedido puede permanecer pendiente y no debe considerarse pagado solamente porque el cliente pulse un botón.
2. **Validación en servidor:** precios, cantidades, disponibilidad e importes no deben depender de valores manipulables en el navegador.
3. **Firma de webhook:** permite comprobar que una notificación corresponde al emisor esperado, usando el secreto configurado y una comparación segura.
4. **Idempotencia:** recibir de nuevo el mismo evento no debe duplicar el procesamiento de la operación.
5. **Sandbox o simulación:** sirve para demostrar aprobaciones y rechazos sin afirmar que se ha efectuado un cobro bancario real.

### Aplicación y entregables

**La Transacción Perfecta: recorrido de demostración.**

1. Acceder con una cuenta de prueba y elegir un producto disponible.
2. Añadirlo al carrito y revisar cantidades, variante y entrega.
3. Completar los datos de contacto, entrega y comprobante, y elegir un método de pago.
4. Confirmar el checkout: PHP procesa la simulación antes de ejecutar `Carrito::checkout()` y registrar el pedido con los importes validados.
5. Revisar el resultado y el estado del pedido en el historial y el panel.
6. Repetir con una tarjeta o Yape rechazados: el controlador devuelve el error sin crear el pedido ni descontar stock. Transferencia y contraentrega pueden registrar una operación pendiente.

**El Panel de Control Inteligente:** para evaluar el webhook se debe conservar evidencia de la notificación, el identificador del evento, la respuesta del endpoint y el estado del pedido antes y después. También se debe enviar un evento repetido y una firma incorrecta para comprobar idempotencia y rechazo. Estos son criterios de demostración; este resumen no inventa capturas ni certifica una conexión activa con Stripe, PayPal o MercadoPago.

**Límite actual:** configurar el secreto del webhook no transforma el checkout en un cobro externo. Antes de conectar un proveedor se necesita su adaptador y completar la validación de importe y moneda frente al pago: el procesamiento actual actualiza estados sin cotejar esos dos campos del evento. El flujo simulado puede consultarse en [el controlador del carrito](../src/Controller/CarritoController.php#L185-L199).

### Código fuente numerado

El receptor exige cabeceras de firma, comprueba una ventana temporal de tolerancia y utiliza `hash_equals` para comparar la firma HMAC esperada.

**Fuente del extracto:** [`src/Model/Pasarela.php`, líneas 90-101](../src/Model/Pasarela.php#L90-L101).

```text
90 |         $timestamp = trim((string) ($headers['x-jt-timestamp'] ?? ''));
91 |         $firma = trim((string) ($headers['x-jt-signature'] ?? ''));
92 |
93 |         if (!preg_match('/^\d{10}$/', $timestamp) || $firma === '') {
94 |             return ['ok' => false, 'codigo' => 401, 'mensaje' => 'Faltan las cabeceras de firma.'];
95 |         }
96 |         if (abs(time() - (int) $timestamp) > self::TOLERANCIA_SEGUNDOS) {
97 |             return ['ok' => false, 'codigo' => 401, 'mensaje' => 'El evento está fuera de la ventana de tolerancia.'];
98 |         }
99 |         if (!hash_equals(self::firmar($cuerpo, (int) $timestamp), $firma)) {
100 |             return ['ok' => false, 'codigo' => 401, 'mensaje' => 'Firma del webhook inválida.'];
101 |         }
```

**Material del proyecto:** [transacción](entregables2/Plantilla-Transaccion-Perfecta.html) y [panel](entregables2/Plantilla-Panel-Control-Inteligente.html).

## Sesión 8: ética, seguridad y publicación

### Resumen

La última sesión reúne lo comercial y lo técnico para revisar si la tienda puede publicarse de forma responsable. Jeremy Tech incluye páginas informativas, autenticación y controles de seguridad, y dispone de instrucciones para ejecutar la aplicación con PostgreSQL. Publicar no consiste solo en encender un contenedor: implica comprobar la dirección pública, HTTPS, permisos, credenciales, persistencia y respaldo de datos. Tener una política escrita o código de seguridad no equivale por sí solo a una auditoría legal o técnica completa.

### Aspectos más relevantes

1. **Transparencia:** explicar qué datos se solicitan, para qué se usan y cómo puede contactar el usuario con el responsable.
2. **Protección de acceso:** contraseñas con hash, comprobación de permisos, consultas preparadas y controles contra solicitudes no autorizadas.
3. **Secretos:** las credenciales reales permanecen fuera del repositorio; una clave publicada debe reemplazarse, no solo ocultarse después.
4. **Persistencia:** una base en un volumen conserva información al detener contenedores, pero `docker compose down -v` elimina los volúmenes asociados.
5. **Publicación verificable:** deben demostrarse HTTPS, cookies y funcionamiento del sitio en su URL pública; un Dockerfile no certifica ese resultado.

### Aplicación y entregables

**La Tienda Virtual Creada y Publicada:** el proyecto reúne catálogo, carrito, cuentas, pedidos, pagos de prueba, panel y asistente. La rama revisada usa PostgreSQL; no deben copiarse aquí comandos de MariaDB de otra rama. Las instrucciones de arranque y despliegue están en [README.md](../README.md) y [README.Docker.md](../README.Docker.md). La URL pública y sus capturas deben adjuntarse cuando se compruebe el servicio; este documento no afirma haber verificado un despliegue externo.

**Informe de Ética y Ciberseguridad:** la revisión debe cubrir, como mínimo:

- Políticas y términos coherentes con la operación real, sin promesas comerciales inexistentes.
- Acceso restringido a cuentas, pedidos y funciones de administración o vendedor.
- Exclusión de contraseñas y datos de tarjetas de la analítica y los registros innecesarios.
- Cookies de sesión y HTTPS revisados en el entorno publicado.
- Respaldo de la base y una prueba de recuperación antes de borrar o recrear volúmenes.
- Definición de conservación, corrección y eliminación de datos personales.
- Revisión de la recuperación de cuentas y de los servicios de correo y pago antes de admitir usuarios reales.

Estos puntos son requisitos y límites de la evaluación; no constituyen una certificación de cumplimiento normativo.

**Límites actuales:** las cookies `Secure` dependen de que se reconozca HTTPS. La política CSP amplia de scripts se envía como `Report-Only`, por lo que no bloquea esas infracciones. Las políticas deben revisarse si se activa IA externa y no deben usar una fecha generada al visitar la página como historial real de cambios. Véanse [los controles de sesión y cabeceras](../src/Security/Proteccion.php#L20-L80) y [las páginas legales](../views/pagina.php#L59-L112).

### Código fuente numerado

Apache establece `public/` como raíz del sitio y desactiva el listado de ese directorio. El mismo archivo restringe el acceso al resto del proyecto; esto no reemplaza TLS ni la revisión de permisos del despliegue.

**Fuente del extracto:** [`docker/apache.conf`, líneas 7-15](../docker/apache.conf#L7-L15).

```text
7 | <VirtualHost *:80>
8 |     DocumentRoot /var/www/html/public
9 |     DirectoryIndex index.php index.html
10 |
11 |     <Directory /var/www/html/public>
12 |         Options -Indexes
13 |         AllowOverride All
14 |         Require all granted
15 |     </Directory>
```

**Material del proyecto:** [tienda publicada](entregables2/Plantilla-Tienda-Publicada.html) e [informe de ética y ciberseguridad](entregables2/Plantilla-Informe-Etica-Ciberseguridad.html).

## Control de versiones y evidencias de entrega

- El repositorio remoto configurado es `Jeremy-Sarmiento/JEREMY-TECH` en GitHub.
- El resumen documenta la rama `postgresql`; la existencia de otra rama no significa que ambas utilicen el mismo motor de base de datos.
- Un commit local registra un cambio, pero no lo publica automáticamente. Antes de la entrega debe comprobarse que GitHub contiene la revisión que evaluará el docente.
- Las capturas de catálogo, API, pedido, analítica, chatbot, pago y HTTPS deben corresponder a una demostración real. Los informes anteriores son antecedentes, no una ejecución actual de esas comprobaciones.

## Diez preguntas de repaso con respuestas

1. **¿Qué diferencia existe entre e-commerce y e-business en Jeremy Tech?**
   El e-commerce reúne la interacción comercial y la compra; el e-business también comprende integraciones, inventario, atención y analítica que sostienen la operación.

2. **¿Para qué sirven los estados As-Is y To-Be?**
   El As-Is describe cómo se opera al inicio y sus problemas. El To-Be define el funcionamiento que se busca alcanzar y permite identificar las brechas de transformación.

3. **¿Por qué el Puente Digital es una integración de e-business?**
   Porque consulta tasas externas y mejora la información de precios sin ejecutar un cobro ni confirmar una venta.

4. **¿Cómo se diferencian B2C, B2B y C2C?**
   B2C vende al consumidor final; B2B atiende operaciones entre empresas; C2C conecta particulares mediante una plataforma. El proyecto contempla estos escenarios, con B2C como enfoque principal de sus informes.

5. **¿Por qué separar pedido, detalle y pago en distintas tablas?**
   Porque la cabecera, los productos comprados y los intentos de pago tienen responsabilidades diferentes. Separarlos evita repetir información y permite conservar el historial.

6. **¿Qué aportan los filtros, la paginación y los índices?**
   Los filtros reducen resultados, la paginación limita los que se muestran y los índices pueden acelerar consultas. Su eficacia debe medirse con las consultas y el volumen reales.

7. **¿Qué permite conocer el embudo de conversión?**
   En qué etapas se concentra o se pierde el interés: visita, consulta de producto, carrito, checkout y compra. Los eventos no deben confundirse automáticamente con usuarios únicos o ventas efectivas.

8. **¿Un chatbot de reglas es lo mismo que una IA generativa?**
   No. Un sistema de reglas relaciona consultas con respuestas o datos conocidos; un modelo generativo produce respuestas mediante otro mecanismo y exige controles adicionales. Debe indicarse cuál está activo.

9. **¿Por qué un webhook necesita firma e idempotencia?**
   La firma ayuda a autenticar la notificación y detectar alteraciones; la idempotencia evita procesar repetidamente el mismo evento y duplicar sus efectos.

10. **¿Qué debe verificarse antes de publicar la tienda para usuarios reales?**
    HTTPS, sesiones, permisos, recuperación de cuentas, secretos, respaldo y restauración, políticas coherentes y funcionamiento de las integraciones. Un sitio accesible no implica automáticamente que sea seguro o que cumpla todas las obligaciones legales.
