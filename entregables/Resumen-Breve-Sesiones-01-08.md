# Jeremy Tech: resumen breve de las sesiones 1 a 8

## Sesión 1: catálogo base e identidad del negocio

### Resumen de lo destacable

La primera sesión estudia cómo un negocio define su identidad antes de transformarse digitalmente. En Jeremy Tech, el punto de partida es una tienda que presenta celulares, laptops, audífonos y otros productos tecnológicos mediante un catálogo y facilita la consulta por WhatsApp. Esta vitrina permite mostrar la oferta, pero no equivale por sí sola a automatizar una compra.

- **Identidad del negocio:** definir a quién se vende, qué necesidad se resuelve y qué diferencia a la tienda.
- **As-Is y To-Be:** comparar la atención manual inicial con el objetivo de gestionar pedidos, inventario y pagos digitalmente.
- **Transformación digital:** conectar procesos del negocio, no limitarse a publicar una página web.

### Diez preguntas y respuestas

1. **¿Qué es Jeremy Tech?** Un proyecto de tienda de productos tecnológicos.
2. **¿A qué público se dirige?** A consumidores y negocios, con enfoque comercial en La Joya, Arequipa.
3. **¿Qué productos presenta?** Celulares, laptops, audífonos, accesorios y otros dispositivos tecnológicos.
4. **¿Qué define el ADN corporativo?** La identidad, el propósito, el público y la propuesta de valor del negocio.
5. **¿Cuál es la propuesta de valor planteada?** Facilitar la consulta, comparación y compra de tecnología con atención al cliente.
6. **¿Qué significa As-Is?** El estado inicial del negocio, descrito aquí como catálogo y coordinación manual.
7. **¿Qué significa To-Be?** El funcionamiento objetivo: carrito, pedidos, pagos y seguimiento digital.
8. **¿Para qué sirve WhatsApp en el catálogo?** Para consultar por un producto mediante un mensaje preparado.
9. **¿Un catálogo permite completar una compra por sí solo?** No; también hacen falta procesos de pedido, pago y entrega.
10. **¿Qué tecnologías utiliza esta versión?** HTML, CSS, JavaScript, PHP y PostgreSQL.

## Sesión 2: Puente Digital y servicios externos

### Resumen de lo destacable

La sesión diferencia la venta electrónica de los procesos digitales que la sostienen. El Puente Digital de Jeremy Tech obtiene tasas de una API y convierte precios de referencia, sin ejecutar cobros. Lo importante es comprender cómo se conectan sistemas y cómo se valida la información recibida.

- **E-commerce y e-business:** comprar y vender forma parte de una operación más amplia de información, gestión y servicios.
- **API, JSON y asincronía:** `fetch` solicita datos sin recargar la página; la respuesta JSON contiene las tasas.
- **Validación:** comprobar errores y recordar que el pedido se registra en PEN, aunque la interfaz muestre otra moneda.

Extracto de [`cargarTasas()`](../public/js/puente-digital.js#L47-L51); los números corresponden a las líneas del archivo:

```text
47 |     var respuesta = await fetch("https://open.er-api.com/v6/latest/PEN");
48 |     if (!respuesta.ok) throw new Error("API error: " + respuesta.status);
49 |     var datos = await respuesta.json();
50 |     if (datos.result !== "success") throw new Error("Invalid rates");
51 |     tasasDeCambio = datos.rates;
```

### Diez preguntas y respuestas

1. **¿Qué es e-commerce?** La interacción comercial y el proceso de compra y venta por medios electrónicos.
2. **¿Qué es e-business?** La integración de procesos digitales que sostienen el negocio, además de la venta.
3. **¿Qué es una API?** Una interfaz que permite solicitar datos o servicios a otro sistema.
4. **¿Qué servicio consulta el Puente Digital?** ExchangeRate-API, mediante `https://open.er-api.com/v6/latest/PEN`.
5. **¿Qué función solicita las tasas?** `cargarTasas()`, utilizando `fetch`.
6. **¿En qué formato llegan los datos?** En JSON, con información como el resultado y las tasas de cambio.
7. **¿Cómo se calcula un precio convertido?** Multiplicando el precio en PEN por la tasa de la moneda elegida.
8. **¿En qué moneda se registra el pedido?** En soles, PEN; la conversión visual es referencial.
9. **¿Por qué esta integración es e-business?** Porque mejora la información comercial sin ejecutar un cobro.
10. **¿Qué limitación tiene el caso sin tasa?** Puede conservar el importe PEN bajo el rótulo de otra moneda; necesita revisión.

## Sesión 3: Mi Pedido y modelo relacional

### Resumen de lo destacable

La sesión relaciona el modelo comercial con la organización de los datos. Jeremy Tech contempla B2C, B2B y marketplace, y separa usuarios, productos, carritos, pedidos y pagos en PostgreSQL. Una compra debe conservar cantidades e importes sin perder la relación con el cliente y los productos.

- **Modelos comerciales:** B2C vende al consumidor; B2B atiende empresas; C2C conecta particulares mediante una plataforma.
- **Modelo relacional:** las claves primarias identifican registros y las foráneas relacionan tablas.
- **Consistencia:** el carrito es provisional; el pedido guarda el historial y una transacción confirma o deshace la operación completa.

Consulta preparada del [detalle del pedido](../src/Model/Carrito.php#L655-L660). Los signos `?` reciben valores por separado:

```text
655 |             $stmtDetalle = $pdo->prepare(
656 |                 'INSERT INTO detalle_pedido
657 |                     (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario_pen,
658 |                      variante_id, variante_nombre, vendedor_id)
659 |                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
660 |             );
```

### Diez preguntas y respuestas

1. **¿Qué es B2C?** La venta de un negocio al consumidor final.
2. **¿Qué es B2B?** Una operación comercial entre empresas, como una compra por volumen.
3. **¿Qué es C2C?** El intercambio entre particulares mediante una plataforma intermediaria.
4. **¿Elegir C2C permite publicar productos automáticamente?** No; se necesita el rol de vendedor y una cuenta habilitada.
5. **¿Qué base de datos usa la rama documentada?** PostgreSQL.
6. **¿Qué es una clave primaria?** Un identificador único de cada fila de una tabla.
7. **¿Qué es una clave foránea?** Una referencia que relaciona una fila con otra tabla y protege la integridad.
8. **¿Cuál es la diferencia entre carrito y pedido?** El carrito es provisional; el pedido conserva una operación registrada.
9. **¿Qué guarda el detalle del pedido?** Producto, cantidad, precio, variante y vendedor asociados a la compra.
10. **¿Para qué sirve una transacción?** Para confirmar conjuntamente una operación o deshacerla si falla.

## Sesión 4: buscador y organización del catálogo

### Resumen de lo destacable

La sesión estudia cómo organizar un negocio digital y un catálogo que pueda crecer. El Canvas explica cómo se crea valor y se sostienen los costos; la arquitectura organiza el trabajo técnico. En Jeremy Tech, búsqueda y filtros se resuelven desde PHP y PostgreSQL, separando responsabilidades con MVC.

- **Búsqueda y filtros:** ayudan a encontrar productos por texto, categoría, marca, precio o vendedor.
- **Paginación e índices:** limitan el trabajo por consulta y pueden mejorar su rendimiento; deben medirse con datos reales.
- **MVC:** el modelo consulta datos, la vista los presenta y el controlador coordina la solicitud. La navegación actual del catálogo aún necesita revisión para volúmenes grandes.

### Diez preguntas y respuestas

1. **¿Para qué sirve el buscador?** Para encontrar productos sin recorrer todo el catálogo manualmente.
2. **¿Qué filtros admite el catálogo?** Texto, categoría, marca, precio y vendedor.
3. **¿Qué es la paginación?** La división de los resultados en subconjuntos para limitar cada consulta.
4. **¿Para qué sirve `LIMIT`?** Para limitar la cantidad de resultados que devuelve una consulta.
5. **¿Para qué sirve `OFFSET`?** Para indicar cuántos resultados se omiten antes de devolver los siguientes.
6. **¿Qué aporta un índice de base de datos?** Puede acelerar las consultas que aprovechan sus columnas y orden.
7. **¿Qué significa MVC?** Modelo, Vista y Controlador: datos, presentación y coordinación de la solicitud.
8. **¿Qué representa el Canvas?** Cómo el negocio crea valor, atiende clientes, obtiene ingresos y sostiene sus costos.
9. **¿Cómo se agrupan las publicaciones del catálogo?** Por categoría, nombre del producto y vendedor.
10. **¿Qué límite actual debe revisarse?** El controlador solicita 250 grupos sin paginador visible, pero el modelo limita la consulta a 48.

## Sesión 5: analítica y captación de visitas

### Resumen de lo destacable

La sesión enseña a atraer visitantes y medir sus acciones para mejorar la tienda. Jeremy Tech utiliza eventos propios y un panel de consulta. La medición sirve para entender el interés y el abandono, pero sus resultados deben interpretarse según lo que realmente registra el sistema.

- **Captación:** SEO y contenido buscan visibilidad orgánica; los anuncios utilizan presupuesto y segmentación.
- **Embudo de conversión:** permite estudiar etapas como visita, producto, carrito, checkout y compra.
- **Lectura de indicadores:** una visita no es un comprador único y el evento `compra` actual también puede corresponder a un pago pendiente. El plan de tráfico no demuestra campañas ejecutadas.

### Diez preguntas y respuestas

1. **¿Qué es la analítica web?** El registro y análisis de acciones realizadas en un sitio.
2. **¿Qué es un evento?** Una acción identificada, como visitar, buscar o consultar un producto.
3. **¿Dónde se guardan los eventos del proyecto?** En la tabla `eventos_analitica`.
4. **¿Qué es un embudo de conversión?** El recorrido conceptual desde la visita hasta la compra, con etapas intermedias.
5. **¿Qué es SEO?** La optimización del sitio y su contenido para facilitar su presencia en buscadores.
6. **¿En qué se diferencia SEO de un anuncio pagado?** SEO busca visibilidad orgánica; un anuncio utiliza presupuesto publicitario.
7. **¿El plan de tráfico prueba que hay campañas activas?** No; describe una estrategia que debe ejecutarse y medirse.
8. **¿Cómo calcula la conversión el panel?** Divide los eventos `compra` entre `visita` y multiplica por cien.
9. **¿Un evento `compra` demuestra un cobro?** No; el frontend también lo registra para pedidos pendientes y sin enviar `total_pen`.
10. **¿Qué datos deben excluirse de la analítica?** Contraseñas, datos de tarjetas y otra información personal innecesaria.

## Sesión 6: Asistente Virtual

### Resumen de lo destacable

La sesión aborda la automatización de la atención y la comparación de herramientas tecnológicas. El asistente de Jeremy Tech usa FAQ, reglas y datos del catálogo antes de intentar IA externa cuando está configurada. La utilidad del sistema depende de responder con información válida, no de aparentar inteligencia.

- **FAQ y reglas:** resuelven preguntas repetidas con respuestas administrables y datos conocidos.
- **IA y atención humana:** una IA generativa no equivale a una FAQ; si no hay respuesta suficiente debe ofrecerse contacto, sin inventar condiciones comerciales.
- **Benchmark:** comparar herramientas por utilidad, requisitos y costos, sin confundir evaluación con instalación o funcionamiento comprobado.

### Diez preguntas y respuestas

1. **¿Para qué sirve el asistente de Jeremy Tech?** Para orientar sobre productos, preguntas frecuentes y consultas permitidas.
2. **¿Qué significa FAQ?** Preguntas frecuentes y sus respuestas.
3. **¿Dónde se administran las respuestas frecuentes?** En el panel, utilizando la base de conocimiento `chatbot_faq`.
4. **¿Responder una FAQ exige IA generativa?** No; puede devolverse una respuesta ya registrada.
5. **¿De dónde debe obtener los datos de productos?** Del catálogo del proyecto, no de información inventada.
6. **¿Qué atiende antes de intentar IA?** Consultas críticas de pedidos, FAQ, reglas locales y búsquedas del catálogo.
7. **¿La IA externa funciona siempre?** No; depende de la configuración y de la disponibilidad del servicio.
8. **¿Qué ocurre si no se obtiene una respuesta?** Se utiliza una respuesta de respaldo que ofrece contacto, como WhatsApp.
9. **¿Para qué sirve un benchmark tecnológico?** Para comparar herramientas según su utilidad, requisitos y adecuación al proyecto.
10. **¿Qué herramientas incluye el benchmark?** Tidio, ExchangeRate-API, Leaflet/OSM, UptimeRobot, Stripe Sandbox/Mock y Lighthouse.

## Sesión 7: pago y confirmación mediante webhook

### Resumen de lo destacable

La sesión explica cómo confirmar una compra y procesar notificaciones de pago de forma confiable. En Jeremy Tech, el checkout valida una simulación antes de registrar el pedido. El webhook tiene un contrato HMAC propio y no demuestra, por sí solo, una conexión real con una pasarela comercial.

- **Validación en servidor:** el navegador no decide precios, stock ni confirmaciones de pago.
- **Firma HMAC:** comprueba la autenticidad e integridad del mensaje con un secreto compartido.
- **Idempotencia:** un evento repetido no debe duplicar sus efectos. Para cobrar realmente faltan el adaptador del proveedor y la validación de importe y moneda.

Función de [firma del webhook](../src/Model/Pasarela.php#L69-L72); combina el instante y el cuerpo del mensaje:

```text
69 |     public static function firmar(string $cuerpo, int $timestamp): string
70 |     {
71 |         return hash_hmac('sha256', $timestamp . '.' . $cuerpo, self::secreto());
72 |     }
```

### Diez preguntas y respuestas

1. **¿Qué es el checkout?** La etapa donde se confirman datos, entrega, método de pago y pedido.
2. **¿El proyecto cobra dinero real en su checkout actual?** No; utiliza pagos simulados.
3. **¿Cuándo se procesa la simulación?** Antes de ejecutar `Carrito::checkout()` y registrar el pedido.
4. **¿Qué ocurre con una tarjeta o Yape rechazados?** Se devuelve un error sin crear el pedido ni descontar stock.
5. **¿Qué métodos pueden quedar pendientes?** Transferencia y contraentrega, según el flujo del simulador.
6. **¿Qué es un webhook?** Una notificación que un sistema envía al servidor para informar un evento.
7. **¿Para qué sirve la firma HMAC?** Para verificar la autenticidad e integridad del mensaje con un secreto compartido.
8. **¿Qué significa idempotencia?** Que repetir un evento no debe duplicar sus efectos.
9. **¿El webhook propio demuestra una integración con Stripe?** No; utiliza un contrato propio que necesita un adaptador específico.
10. **¿Qué falta antes de usar una pasarela externa?** Adaptar su protocolo y validar importe y moneda frente al pago registrado.

## Sesión 8: ética, seguridad y publicación

### Resumen de lo destacable

La última sesión reúne ética, protección de datos y publicación responsable. Una tienda debe explicar sus condiciones, proteger las cuentas y conservar la información de forma segura. Jeremy Tech dispone de páginas informativas y controles técnicos, pero esto no certifica por sí solo el cumplimiento legal ni un despliegue seguro.

- **Privacidad y transparencia:** solicitar solo los datos necesarios y explicar sus finalidades y las condiciones del servicio.
- **Seguridad:** contraseñas con hash, consultas preparadas, tokens CSRF, permisos y HTTPS; las credenciales reales no deben publicarse.
- **Operación y versiones:** respaldar y probar la restauración de datos. `down` conserva volúmenes y `down -v` los elimina; un commit local no equivale a publicar con push.

### Diez preguntas y respuestas

1. **¿Para qué sirve la política de privacidad?** Para explicar qué datos se recogen, sus finalidades y los derechos del usuario.
2. **¿Para qué sirven los términos de servicio?** Para definir las condiciones de uso y de las operaciones comerciales.
3. **¿Cómo deben almacenarse las contraseñas?** Como hashes seguros, nunca como texto plano.
4. **¿Qué protege un token CSRF?** Ayuda a rechazar solicitudes forjadas desde otros sitios utilizando la sesión del usuario.
5. **¿Por qué se utilizan consultas preparadas?** Para separar los valores de la estructura SQL y reducir el riesgo de inyección.
6. **¿Qué aporta HTTPS?** Cifrado y protección de la comunicación entre el navegador y el servidor.
7. **¿Qué directorio debe exponerse como raíz web?** `public/`, manteniendo los archivos internos fuera del acceso público.
8. **¿Qué diferencia hay entre `down` y `down -v`?** El primero conserva los volúmenes; el segundo elimina los asociados y sus datos.
9. **¿Qué diferencia existe entre commit y push?** El commit registra cambios localmente; el push los envía al repositorio remoto.
10. **¿Qué debe comprobarse antes de admitir usuarios reales?** HTTPS, permisos, sesiones, recuperación de cuentas, integraciones, políticas, secretos y restauración de respaldos.
