# Jeremy Tech: resumen breve de las sesiones 1 a 8

**Estudiante:** Jeremy Sarmiento Tapia. **DNI:** por completar.

Versión de estudio de la rama `postgresql`, siguiendo el orden de `sesion-08/recorrido.js`. Cada sesión incluye un resumen y diez preguntas con respuestas cortas: **80 preguntas en total**.

El [documento completo](Resumen-Sesiones-01-08.md) conserva las fuentes, el código numerado y las explicaciones ampliadas. Repositorio: <https://github.com/Jeremy-Sarmiento/JEREMY-TECH>.

## Sesión 1: catálogo base e identidad del negocio

### Resumen

Jeremy Tech es un proyecto de tienda de tecnología dirigido a consumidores y negocios de La Joya, Arequipa. La sesión define su identidad y compara el escenario inicial, catálogo con atención manual, con el objetivo de gestionar compras, inventario y pedidos desde la web. Tener una vitrina digital no significa automatizar toda la venta.

**Entregables:** El ADN Corporativo y Los Planos de Transformación.

### Diez preguntas y respuestas

1. **¿Qué es Jeremy Tech?** Un proyecto de tienda de productos tecnológicos.
2. **¿A qué público se dirige?** A consumidores y negocios, con enfoque comercial en La Joya, Arequipa.
3. **¿Qué productos presenta?** Celulares, laptops, audífonos, accesorios y otros dispositivos tecnológicos.
4. **¿Qué define el ADN corporativo?** La identidad, el propósito, el público y la propuesta de valor del negocio.
5. **¿Cuál es la propuesta de valor planteada?** Facilitar la consulta, comparación y compra de tecnología con atención al cliente.
6. **¿Qué significa As-Is?** El estado inicial del negocio, descrito en el entregable como catálogo y coordinación manual.
7. **¿Qué significa To-Be?** El funcionamiento objetivo: carrito, pedidos, pagos y seguimiento digital.
8. **¿Para qué sirve WhatsApp en el catálogo?** Para consultar por un producto mediante un mensaje preparado.
9. **¿Un catálogo permite completar una compra por sí solo?** No; también hacen falta procesos de pedido, pago y entrega.
10. **¿Qué tecnologías utiliza esta versión?** HTML, CSS, JavaScript, PHP y PostgreSQL.

## Sesión 2: Puente Digital y servicios externos

### Resumen

La sesión diferencia e-commerce y e-business. Jeremy Tech consulta una API de tipos de cambio para mostrar precios referenciales en otras monedas, manteniendo PEN como moneda base del pedido. La integración facilita comparar precios, pero no realiza cobros. Las respuestas externas deben validarse y sus fallos deben comunicarse sin presentar conversiones incorrectas.

**Entregables:** La Radiografía Técnica y El Puente Digital.

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

### Resumen

Jeremy Tech plantea B2C como enfoque principal y contempla B2B y marketplace. PostgreSQL organiza usuarios, productos, carritos, pedidos, detalles y pagos. Las claves relacionan la información y las transacciones permiten registrar operaciones de forma consistente. El tipo comercial de una cuenta no reemplaza los permisos necesarios para vender.

**Entregables:** La Radiografía Comercial y El Plano Maestro de la Memoria (DER).

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

### Resumen

La sesión relaciona el Canvas del negocio con una arquitectura capaz de organizar el catálogo. Jeremy Tech utiliza filtros, búsqueda y consultas paginadas desde PHP y PostgreSQL. MVC separa la consulta, el procesamiento y la presentación. El modelo dispone de límites de resultados, pero la navegación actual necesita revisión para catálogos grandes.

**Entregables:** El Mapa Maestro (Canvas Real) y La Arquitectura del Gigante.

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

### Resumen

La analítica permite estudiar las acciones del visitante y orientar la promoción de la tienda. Jeremy Tech registra eventos propios y ofrece consultas desde el panel. SEO y anuncios forman parte del plan de tráfico, no de una campaña demostrada. Los eventos registrados no deben confundirse con compradores únicos ni con ingresos cobrados.

**Entregables:** El Lanzamiento Invisible y La Tienda que Observa.

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

### Resumen

El asistente orienta al visitante mediante FAQ, reglas y datos del catálogo. Puede intentar una respuesta de IA si existe configuración, pero no debe afirmarse que siempre utiliza un modelo externo. El benchmark compara herramientas complementarias; evaluar una herramienta no demuestra que esté instalada o funcionando.

**Entregables:** El Benchmark Tecnológico y El Asistente Virtual en Acción.

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
10. **¿Qué herramientas se evalúan en el entregable?** Tidio, ExchangeRate-API, Leaflet/OSM, UptimeRobot, Stripe Sandbox/Mock y Lighthouse.

## Sesión 7: pago y confirmación mediante webhook

### Resumen

El checkout actual valida un pago simulado antes de registrar el pedido. También existe un receptor de webhooks con firma HMAC e idempotencia para procesar notificaciones. No es una integración acreditada con una pasarela comercial. Antes de cobrar dinero real hacen falta el adaptador del proveedor y validaciones adicionales, como importe y moneda.

**Entregables:** La Transacción Perfecta y El Panel de Control Inteligente.

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

### Resumen

La publicación responsable requiere políticas claras, protección de cuentas, HTTPS y respaldo de datos. Jeremy Tech incluye páginas informativas, controles de acceso y configuración de ejecución. Estos elementos no certifican por sí solos un despliegue seguro ni el cumplimiento legal. El repositorio y los datos deben mantenerse sin exponer credenciales ni borrar volúmenes accidentalmente.

**Entregables:** La Tienda Virtual Creada y Publicada e Informe de Ética y Ciberseguridad.

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
