<?php
/**
 * VISTA: páginas informativas de la tienda (Jeremy Tech).
 * Datos de entrada:
 *   $pagina  array{titulo, icono, descripcion, slug}
 *
 * Las páginas son estáticas; la de envíos consulta las tarifas reales y la de
 * contacto reutiliza la configuración de la tienda.
 */
$e = static fn (mixed $valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$config = static function (string $clave, string $porDefecto = ''): string {
    static $cache = [];
    if (array_key_exists($clave, $cache)) {
        return $cache[$clave];
    }
    try {
        $cache[$clave] = \Model\Carrito::configuracion($clave, $porDefecto);
    } catch (\Throwable) {
        $cache[$clave] = $porDefecto;
    }
    return $cache[$clave];
};

$tema = (string) $pagina['slug'];

$emailTienda = trim($config('email', 'jeresarmientotarojo@gmail.com')) ?: 'jeresarmientotarojo@gmail.com';
$telefonoTienda = trim($config('telefono', '997833866')) ?: '997833866';
$whatsappTienda = trim($config('whatsapp_numero', '51997833866')) ?: '51997833866';
$whatsappDigitos = preg_replace('/\D+/', '', $whatsappTienda) ?? '51997833866';
$telefonoDigitos = preg_replace('/\D+/', '', $telefonoTienda) ?? '997833866';
$telefonoTel = str_starts_with($telefonoDigitos, '51') ? '+' . $telefonoDigitos : '+51' . $telefonoDigitos;
$whatsappUrl = 'https://wa.me/' . $whatsappDigitos . '?text=' . rawurlencode('Hola, necesito ayuda con JEREMY TECH.');

$direccionConfig = trim($config('direccion', ''));
$direccionTienda = ($direccionConfig !== '' && mb_stripos($direccionConfig, 'Arequipa') !== false)
    ? ucfirst($direccionConfig)
    : 'La Paz Soldán, frente a Los Portales, La Joya – Arequipa';

$zonasEnvio = [];
if ($tema === 'envios') {
    try {
        $zonasEnvio = \Model\Envio::zonas();
    } catch (\Throwable) {
        $zonasEnvio = [];
    }
}
?>
<main class="page-shell">
  <div class="page-hero">
    <p class="page-eyebrow">JEREMY TECH · INFORMACIÓN</p>
    <h1>
     <svg class="inline-icon" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-<?= $e($pagina['icono']) ?>"/></svg>
     <?= $e($pagina['titulo']) ?>
    </h1>
    <p class="page-lead"><?= $e($pagina['descripcion']) ?></p>
  </div>

  <div class="page-content legal-page">
    <?php if ($tema === 'privacidad'): ?>
      <p class="legal-note">Esta tienda es un proyecto de demostración: los pagos se procesan de forma simulada y no se realiza ningún cobro real.</p>
      <p class="legal-meta">Última actualización: <?= date('d/m/Y') ?></p>

      <h2>1. Responsable del tratamiento</h2>
      <p><?= $e($config('nombre_tienda', 'JEREMY-TECH')) ?>, con domicilio en <?= $e($direccionTienda) ?>, Arequipa, Perú. Correo de contacto: <a href="mailto:<?= $e($emailTienda) ?>"><?= $e($emailTienda) ?></a>.</p>

      <h2>2. Datos que recopilamos</h2>
      <ul>
        <li><strong>Datos de cuenta:</strong> nombre, apellidos, correo electrónico, teléfono, dirección y contraseña almacenada como hash.</li>
        <li><strong>Datos de pedidos:</strong> productos, importes, dirección de entrega, método de pago y estado del pedido.</li>
        <li><strong>Datos técnicos:</strong> cookies de sesión, token CSRF y datos del navegador necesarios para el funcionamiento del sitio.</li>
      </ul>

      <h2>3. Finalidad del tratamiento</h2>
      <p>Gestionar tu cuenta, procesar pedidos, coordinar entregas, atender consultas, resolver devoluciones y enviarte avisos relacionados con tus compras.</p>

      <h2>4. Base legal y conservación</h2>
      <p>Tratamos los datos con tu consentimiento y para ejecutar la relación de compraventa, conforme a la Ley N.° 29733 de Protección de Datos Personales. Conservamos los datos de tu cuenta mientras permanezca activa y los de tus pedidos durante el plazo exigido por las obligaciones fiscales.</p>

      <h2>5. Destinatarios</h2>
      <p>No vendemos tus datos. Solo los compartimos con empresas de mensajería y proveedores necesarios para procesar el pedido, cuando corresponda.</p>

      <h2>6. Tus derechos</h2>
      <p>Puedes solicitar acceso, rectificación, cancelación u oposición escribiendo a <a href="mailto:<?= $e($emailTienda) ?>"><?= $e($emailTienda) ?></a>. Responderemos en un plazo máximo de 30 días.</p>

      <h2>7. Cookies</h2>
      <p>Usamos cookies técnicas para la sesión, el carrito y la moneda seleccionada. No usamos cookies publicitarias de terceros.</p>

      <h2>8. Seguridad</h2>
      <p>Aplicamos contraseñas con hash, tokens CSRF, consultas preparadas y validación de datos. Te recomendamos usar una conexión segura y no compartir tu contraseña.</p>

    <?php elseif ($tema === 'terminos'): ?>
      <p class="legal-meta">Última actualización: <?= date('d/m/Y') ?></p>

      <h2>1. Aceptación</h2>
      <p>Al registrarte o comprar en <?= $e($config('nombre_tienda', 'JEREMY-TECH')) ?> aceptas estos términos y condiciones.</p>

      <h2>2. Productos</h2>
      <p>Vendemos productos tecnológicos originales con garantía según el fabricante. Las imágenes y descripciones son referenciales; las características definitivas aparecen en la ficha de cada producto.</p>

      <h2>3. Precios y moneda</h2>
      <p>Los precios base están en soles (PEN). El selector de moneda muestra una conversión referencial; el pedido se registra en PEN. El importe final puede variar ligeramente según la tasa aplicada por tu banco.</p>

      <h2>4. Proceso de compra</h2>
      <ol>
        <li>Agrega productos al carrito.</li>
        <li>Inicia sesión y completa tus datos de contacto y entrega.</li>
        <li>Elige el método de pago y confirma el pedido.</li>
        <li>Recibe el resumen y sigue el estado desde <a href="?ruta=carrito#historial">Mis Pedidos</a>.</li>
      </ol>

      <h2>5. Pagos</h2>
      <p>Aceptamos tarjeta, Yape/Plin, transferencia y contraentrega. En esta demostración los pagos son simulados: no se cobra dinero real ni se almacenan datos de tarjeta.</p>

      <h2>6. Envíos</h2>
      <p>El costo se calcula según la zona de la dirección de entrega. Consulta las tarifas vigentes en <a href="?ruta=pagina&amp;p=envios">Envíos y devoluciones</a>.</p>

      <h2>7. Devoluciones y garantía</h2>
      <p>Puedes solicitar una devolución dentro de los 30 días posteriores a la recepción, con el producto sin uso y en su empaque original. La garantía cubre defectos de fábrica según el plazo indicado en la ficha del producto.</p>

      <h2>8. Cancelaciones</h2>
      <p>Puedes cancelar sin costo mientras el pedido esté pendiente o pagado. Si el pedido ya fue enviado, se aplica la política de devoluciones.</p>

      <h2>9. Propiedad intelectual</h2>
      <p>El contenido, las marcas y las imágenes de este sitio pertenecen a sus respectivos titulares. No está permitida su reproducción sin autorización.</p>

      <h2>10. Limitación de responsabilidad</h2>
      <p>No respondemos por interrupciones de servicios de terceros, uso indebido de los productos o daños derivados de un uso distinto al indicado por el fabricante.</p>

      <h2>11. Legislación y contacto</h2>
      <p>Estos términos se rigen por la legislación peruana. Para consultas, escríbenos a <a href="mailto:<?= $e($emailTienda) ?>"><?= $e($emailTienda) ?></a> o desde la página de <a href="?ruta=pagina&amp;p=contacto">contacto</a>.</p>

    <?php elseif ($tema === 'envios'): ?>
      <h2>Zonas y tarifas</h2>
      <p>El costo de envío se calcula automáticamente según la zona de la dirección de entrega. Estas son las tarifas vigentes:</p>

      <?php if ($zonasEnvio !== []): ?>
        <div class="table-scroll">
          <table class="shipping-table">
            <thead>
              <tr>
                <th scope="col">Zona</th>
                <th scope="col">Plazo estimado</th>
                <th scope="col">Costo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($zonasEnvio as $zona): ?>
                <?php $costo = (float) ($zona['costo'] ?? 0); ?>
                <tr>
                  <td><?= $e($zona['zona'] ?? '') ?></td>
                  <td><?= $e($zona['dias_estimados'] ?? '') ?></td>
                  <td><?= $costo <= 0 ? 'Gratis' : 'S/ ' . number_format($costo, 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p>No hay tarifas de envío activas en este momento. Escríbenos para coordinar tu entrega.</p>
      <?php endif; ?>

      <p>El recojo en tienda no tiene costo. Los pedidos se preparan después de confirmar el pago y recibirás el estado actualizado en <a href="?ruta=carrito#historial">Mis Pedidos</a>.</p>

      <h2 id="garantias">Garantías y devoluciones</h2>
      <ul>
        <li>Todos los productos son originales y cuentan con garantía según el fabricante.</li>
        <li>Puedes solicitar una devolución dentro de los 30 días posteriores a la recepción, con el producto sin uso y en su empaque original.</li>
        <li>Para iniciar una devolución, escríbenos por WhatsApp o correo indicando tu número de pedido.</li>
        <li>Los reembolsos se coordinan por el mismo medio de pago utilizado en la compra.</li>
      </ul>

      <p><a class="page-btn" href="<?= $e($whatsappUrl) ?>" target="_blank" rel="noopener">Iniciar devolución por WhatsApp</a></p>

    <?php elseif ($tema === 'faq'): ?>
      <?php
      // La página pública y el chatbot comparten la misma fuente: `chatbot_faq`.
      // El admin gestiona el contenido desde ?ruta=admin/chatbot.
      $faqsPublicas = [];
      try {
          $faqsPublicas = \Model\ChatbotFaq::activos();
      } catch (\Throwable) {
          $faqsPublicas = [];
      }
      ?>
      <div class="faq-list">
        <?php if ($faqsPublicas === []): ?>
          <p>No hay preguntas frecuentes publicadas en este momento. Escríbenos por WhatsApp y con gusto te ayudamos.</p>
        <?php endif; ?>

        <?php foreach ($faqsPublicas as $indice => $faqPublica): ?>
          <details class="faq-item"<?= $indice === 0 ? ' open' : '' ?>>
            <summary><?= $e($faqPublica['pregunta'] ?? '') ?></summary>
            <p><?= $e($faqPublica['respuesta'] ?? '') ?></p>
          </details>
        <?php endforeach; ?>
      </div>

    <?php elseif ($tema === 'contacto'): ?>
      <div class="contact-grid">
        <article class="contact-card">
          <span class="contact-icon" aria-hidden="true"><svg class="inline-icon" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-whatsapp"/></svg></span>
          <h2>WhatsApp</h2>
          <a href="<?= $e($whatsappUrl) ?>" target="_blank" rel="noopener">+<?= $e($whatsappDigitos) ?></a>
          <p>La forma más rápida de consultar stock, envíos o garantías.</p>
        </article>

        <article class="contact-card">
          <span class="contact-icon" aria-hidden="true"><svg class="inline-icon" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-chat"/></svg></span>
          <h2>Correo</h2>
          <a href="mailto:<?= $e($emailTienda) ?>"><?= $e($emailTienda) ?></a>
          <p>Para consultas detalladas, comprobantes o solicitudes de devolución.</p>
        </article>

        <article class="contact-card">
          <span class="contact-icon" aria-hidden="true"><svg class="inline-icon" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-phone"/></svg></span>
          <h2>Teléfono</h2>
          <a href="tel:<?= $e($telefonoTel) ?>"><?= $e($telefonoTienda) ?></a>
          <p>Atención telefónica en horario de tienda.</p>
        </article>

        <article class="contact-card">
          <span class="contact-icon" aria-hidden="true"><svg class="inline-icon" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pin"/></svg></span>
          <h2>Tienda</h2>
          <p><?= $e($direccionTienda) ?></p>
          <p>Lun–Sáb: 9:00 – 19:00</p>
        </article>
      </div>

      <h2>Antes de escribirnos</h2>
      <p>Si tu consulta es sobre un pedido, ten a mano tu número de pedido o el correo con el que compraste. Así podremos ayudarte más rápido.</p>
      <p>También puedes revisar las <a href="?ruta=pagina&amp;p=faq">preguntas frecuentes</a> o consultar el estado de tus compras en <a href="?ruta=carrito#historial">Mis Pedidos</a>.</p>

      <div class="contact-actions">
        <a class="page-btn page-btn-primary" href="<?= $e($whatsappUrl) ?>" target="_blank" rel="noopener">Abrir WhatsApp</a>
        <a class="page-btn" href="mailto:<?= $e($emailTienda) ?>?subject=Consulta%20JEREMY%20TECH">Enviar correo</a>
      </div>
    <?php endif; ?>
  </div>
</main>
