<?php
/**
 * Footer global de la tienda.
 *
 * Los datos de contacto salen de la tabla `configuracion` para no duplicar
 * valores entre el header, el checkout y el footer. Si una clave no existe,
 * se usa un valor de respaldo.
 */
$configTienda = static function (string $clave, string $porDefecto = ''): string {
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

$nombreTienda = trim($configTienda('nombre_tienda', 'JEREMY-TECH')) ?: 'JEREMY-TECH';
$emailTienda = trim($configTienda('email', 'jeresarmientotarojo@gmail.com')) ?: 'jeresarmientotarojo@gmail.com';
$telefonoTienda = trim($configTienda('telefono', '997833866')) ?: '997833866';
$whatsappTienda = trim($configTienda('whatsapp_numero', '51997833866')) ?: '51997833866';

$telefonoDigitos = preg_replace('/\D+/', '', $telefonoTienda) ?? '';
$whatsappDigitos = preg_replace('/\D+/', '', $whatsappTienda) ?? '';
if ($telefonoDigitos === '') {
    $telefonoDigitos = '997833866';
}
if ($whatsappDigitos === '') {
    $whatsappDigitos = '51997833866';
}
$telefonoTel = str_starts_with($telefonoDigitos, '51') ? '+' . $telefonoDigitos : '+51' . $telefonoDigitos;
$whatsappUrl = 'https://wa.me/' . $whatsappDigitos . '?text=' . rawurlencode('Hola, necesito ayuda con JEREMY TECH.');

$direccionConfig = trim($configTienda('direccion', ''));
$direccionTienda = ($direccionConfig !== '' && mb_stripos($direccionConfig, 'Arequipa') !== false)
    ? ucfirst($direccionConfig)
    : 'La Paz Soldán, frente a Los Portales, La Joya – Arequipa';

$e = static fn (mixed $valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
?>
<footer class="site-footer">
  <div class="footer-grid">
    <div class="footer-brand">
      <a class="footer-logo" href="?ruta=catalogo" aria-label="<?= $e($nombreTienda) ?> — Ir al catálogo">
        <img src="img/logo.svg" alt="<?= $e($nombreTienda) ?>" loading="lazy"/>
      </a>
      <p class="footer-desc">Tu tienda de tecnología de confianza. Productos originales, garantía oficial y envíos a todo el Perú.</p>
      <div class="footer-payments" aria-label="Métodos de pago aceptados (modo demostración)">
        <span class="pay-badge">VISA</span>
        <span class="pay-badge">Mastercard</span>
        <span class="pay-badge">Yape</span>
        <span class="pay-badge">Plin</span>
        <span class="pay-badge">Transferencia</span>
        <span class="pay-badge">Contraentrega</span>
      </div>
    </div>

    <div class="footer-col" role="navigation" aria-labelledby="footerAyuda">
      <h2 id="footerAyuda">Ayuda</h2>
      <ul>
        <li><a href="?ruta=pagina&amp;p=faq">Preguntas frecuentes</a></li>
        <li><a href="?ruta=soporte">Mis tickets de soporte</a></li>
        <li><a href="?ruta=pagina&amp;p=envios">Envíos y devoluciones</a></li>
        <li><a href="?ruta=pagina&amp;p=envios#garantias">Garantías</a></li>
        <li><a href="?ruta=pagina&amp;p=privacidad">Política de privacidad</a></li>
        <li><a href="?ruta=pagina&amp;p=terminos">Términos y condiciones</a></li>
      </ul>
    </div>

    <div class="footer-col footer-contact" aria-labelledby="footerContacto">
      <h2 id="footerContacto">Contacto</h2>
      <ul>
        <li>
          <a href="?ruta=pagina&amp;p=contacto">Contacto y soporte</a>
        </li>
        <li>
          <a href="<?= $e($whatsappUrl) ?>" target="_blank" rel="noopener">
            WhatsApp: <?= $e($telefonoTienda) ?>
          </a>
        </li>
        <li>
          <a href="mailto:<?= $e($emailTienda) ?>"><?= $e($emailTienda) ?></a>
        </li>
        <li>
          <a href="tel:<?= $e($telefonoTel) ?>"><?= $e($telefonoTienda) ?></a>
        </li>
        <li><?= $e($direccionTienda) ?></li>
        <li>Lun–Sáb: 9:00 – 19:00</li>
      </ul>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="footer-bottom-inner">
      <span>© <?= date('Y') ?> <?= $e($nombreTienda) ?>. Todos los derechos reservados.</span>
      <span class="footer-bottom-links">
        <a href="?ruta=pagina&amp;p=privacidad">Privacidad</a>
        <a href="?ruta=pagina&amp;p=terminos">Términos</a>
        <span>Moneda: <strong id="footerMoneda">PEN</strong></span>
      </span>
    </div>
  </div>
</footer>

<!-- Toast de Notificaciones -->
<div id="cartToast" class="cart-toast" role="status" aria-live="polite">
 <div class="toast-icon">✓</div>
 <div class="toast-message" id="toastMessage">Producto agregado al carrito</div>
</div>

<?php require BASE_PATH . '/views/partials/chat_widget.php'; ?>

<script src="js/puente-digital.js?v=<?= (int) @filemtime(BASE_PATH . '/public/js/puente-digital.js') ?>"></script>
<script src="js/cuenta.js?v=<?= (int) @filemtime(BASE_PATH . '/public/js/cuenta.js') ?>"></script>
<script src="js/carrito.js?v=<?= (int) @filemtime(BASE_PATH . '/public/js/carrito.js') ?>"></script>
<script src="js/script.js?v=<?= (int) @filemtime(BASE_PATH . '/public/js/script.js') ?>"></script>
<script src="js/chat.js?v=<?= (int) @filemtime(BASE_PATH . '/public/js/chat.js') ?>"></script>
<script src="js/analitica.js?v=<?= (int) @filemtime(BASE_PATH . '/public/js/analitica.js') ?>"></script>
</body>
</html>
