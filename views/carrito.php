<?php
/**
 * VISTA: página del carrito/checkout/historial (Jeremy Tech).
 * Datos de entrada (los prepara src/Controller/CarritoController::ver()):
 *   $lineas, $subtotal, $cupon, $descuento, $totalItems, $historial.
 */

$estaVacio = $totalItems <= 0;
$envioTienda = \Model\Carrito::costoEnvio('tienda');

// Direcciones guardadas del cliente + zonas activas (Etapa 5). El costo del
// envío a domicilio lo decide la ZONA de la dirección elegida.
$usuarioId      = \Model\Cuenta::idSesion();
$usuario        = $usuarioId !== null ? \Model\Cuenta::usuarioActual() : null;
$direcciones    = $usuarioId !== null ? \Model\Direccion::delCliente($usuarioId) : [];
$zonas          = \Model\Envio::zonas();
$predeterminada = null;
foreach ($direcciones as $dirCliente) {
    if ((int) $dirCliente['predeterminada'] === 1) {
        $predeterminada = $dirCliente;
        break;
    }
}
$tarifaPreseleccionada = 0;
if ($predeterminada !== null && $predeterminada['tarifa_envio_id'] !== null) {
    $tarifaPreseleccionada = (int) $predeterminada['tarifa_envio_id'];
} elseif ($zonas !== []) {
    // Por defecto la primera ZONA DE DOMICILIO (no la de recojo en tienda).
    foreach ($zonas as $zonaSel) {
        if ((string) $zonaSel['zona'] !== \Model\Envio::ZONA_RECOJO) {
            $tarifaPreseleccionada = (int) $zonaSel['id'];
            break;
        }
    }
    if ($tarifaPreseleccionada === 0) {
        $tarifaPreseleccionada = (int) $zonas[0]['id'];
    }
}
$envioDomicilio = \Model\Carrito::costoEnvio('domicilio', $tarifaPreseleccionada > 0 ? $tarifaPreseleccionada : null);
$direccionPreseleccionada = $predeterminada !== null ? (int) $predeterminada['id'] : 0;

$fmt = fn (float $monto, string $simbolo = 'PEN'): string => $simbolo . ' ' . number_format($monto, 2);
$fechaFmt = static function (?string $fecha): string {
    $fecha = trim((string) $fecha);
    if ($fecha === '') {
        return '';
    }
    $ts = strtotime($fecha);
    return $ts === false ? $fecha : date('d/m/Y H:i', $ts);
};

$direccionesJson = array_map(static fn (array $d): array => [
    'id'        => (int) $d['id'],
    'etiqueta'  => (string) $d['etiqueta'],
    'direccion' => (string) $d['direccion'],
    'zona'      => (string) ($d['zona'] ?? ''),
    'costo'     => (float) ($d['costo'] ?? 0),
    'tarifaId'  => $d['tarifa_envio_id'] !== null ? (int) $d['tarifa_envio_id'] : 0,
], $direcciones);
$zonasJson = array_map(static fn (array $z): array => [
    'id'    => (int) $z['id'],
    'zona'  => (string) $z['zona'],
    'costo' => (float) $z['costo'],
], $zonas);
?>
<script nonce="<?= htmlspecialchars(\Security\Proteccion::nonce(), ENT_QUOTES, 'UTF-8') ?>">
 const JT_CARRO = {
  estaVacio: <?= $estaVacio ? 'true' : 'false' ?>,
  subtotalPEN: <?= (float) $subtotal ?>,
  descuentoPEN: <?= (float) $descuento ?>,
  envioDomicilio: <?= (float) $envioDomicilio ?>,
  envioTienda: <?= (float) $envioTienda ?>,
  totalItems: <?= (int) $totalItems ?>,
  direcciones: <?= json_encode($direccionesJson, JSON_UNESCAPED_UNICODE) ?>,
  zonas: <?= json_encode($zonasJson, JSON_UNESCAPED_UNICODE) ?>,
  tarifaPreseleccionada: <?= (int) $tarifaPreseleccionada ?>,
  direccionPreseleccionada: <?= (int) $direccionPreseleccionada ?>
 };
 // `const` no crea propiedad en window: carrito.js comprueba window.JT_CARRO.
 window.JT_CARRO = JT_CARRO;
</script>

<main class="cart-page">

  <!-- ================= RESUMEN DEL CARRITO ================= -->
  <div class="cart-page-header">
   <h1>Tu Carrito de Compras</h1>
   <p>Revisa tus productos, aplica tu cupón y simula un pedido sin cobros ni envíos reales.</p>
  </div>

  <div class="cart-page-grid">

   <!-- Columna izquierda: items -->
   <div class="cart-page-items">
    <?php if ($estaVacio): ?>
    <div id="cartEmptyState" class="cart-empty-state">
     <div class="empty-icon"><svg class="inline-icon" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-cart"/></svg></div>
     <h4>Tu carrito está vacío</h4>
     <p>Explora nuestro catálogo y agrega los mejores dispositivos tecnológicos.</p>
     <a class="btn-explorar" href="?ruta=catalogo">Explorar Productos</a>
    </div>
    <?php else: ?>
    <div class="cart-page-items-head">
     <span><?= $totalItems ?> <?= $totalItems === 1 ? 'producto' : 'productos' ?> en tu carrito</span>
    </div>
    <div id="cartItemsList" class="cart-items-list">
     <?php foreach ($lineas as $linea):
         $prod = $linea['producto'];
         $id = htmlspecialchars($linea['clave'], ENT_QUOTES);
         $varianteTexto = trim((string) $linea['varianteNombre']);
     ?>
     <div class="cart-item" data-item="<?= $id ?>" data-cantidad="<?= (int) $linea['cantidad'] ?>">
      <img src="<?= htmlspecialchars(\Model\Producto::urlImagen($prod), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($prod['nombre'], ENT_QUOTES) ?>"/>
      <div class="cart-item-info">
       <div class="cart-item-title"><?= htmlspecialchars($prod['nombre'], ENT_QUOTES) ?></div>
       <?php if ($varianteTexto !== ''): ?>
       <div class="cart-item-spec"><?= htmlspecialchars($varianteTexto, ENT_QUOTES) ?> · <?= htmlspecialchars($prod['especificaciones'], ENT_QUOTES) ?></div>
       <?php else: ?>
       <div class="cart-item-spec"><?= htmlspecialchars($prod['especificaciones'], ENT_QUOTES) ?></div>
       <?php endif; ?>
       <div class="cart-item-row">
        <div class="cart-item-controls">
         <button class="qty-btn" onclick="cambiarCantidad('<?= $id ?>', -1)" aria-label="Disminuir cantidad">−</button>
         <span class="qty-val"><?= (int) $linea['cantidad'] ?></span>
         <button class="qty-btn" onclick="cambiarCantidad('<?= $id ?>', 1)" aria-label="Aumentar cantidad">+</button>
        </div>
        <?php if ($linea['tramo'] !== null): ?>
        <span class="cart-item-unit">S/ <?= number_format((float) $linea['precioUnitario'], 2) ?> c/u</span>
        <?php endif; ?>
        <div class="cart-item-price price" data-precio="<?= htmlspecialchars((string) (float) $linea['subtotal'], ENT_QUOTES) ?>"><?= $fmt((float) $linea['subtotal']) ?></div>
       </div>
      </div>
      <button class="cart-item-remove" type="button" onclick="eliminarDelCarrito('<?= $id ?>')" title="Eliminar" aria-label="Eliminar producto">✕</button>
     </div>
     <?php endforeach; ?>
    </div>
    <?php endif; ?>
   </div>

   <!-- Columna derecha: resumen / cupón / envío -->
   <div class="cart-page-summary">
    <div class="cart-panel">
     <h2>Resumen del Pedido</h2>

     <div class="cart-coupon-area">
      <div class="cart-coupon-input-wrap">
       <input type="text" id="cuponInput" placeholder="Cupón (ej. JEREMYTECH10)" autocomplete="off"/>
       <button type="button" class="btn-apply-coupon" onclick="aplicarCupon()">Aplicar</button>
      </div>
      <div id="cuponTagMsg" class="coupon-tag-msg" style="display:none;"></div>
     </div>

     <div class="cart-delivery-selector">
      <div class="delivery-opt-label">Modalidad de entrega:</div>
      <div class="delivery-opts-grid">
       <label class="delivery-opt active" id="delivOptDomicilio" onclick="cambiarOpcionEnvio('domicilio')">
        <input type="radio" name="tipoEnvio" value="domicilio" checked/>
        <span>Domicilio</span>
       </label>
       <label class="delivery-opt" id="delivOptTienda" onclick="cambiarOpcionEnvio('tienda')">
        <input type="radio" name="tipoEnvio" value="tienda"/>
        <span>En Tienda (Gratis)</span>
       </label>
      </div>
     </div>

     <div class="cart-summary-row">
      <span>Subtotal:</span>
      <span id="cartSubtotal" class="price" data-precio="<?= (float) $subtotal ?>"><?= $fmt((float) $subtotal) ?></span>
     </div>
     <div class="cart-summary-row discount-row" id="cartDiscountRow" style="<?= $cupon ? '' : 'display:none;' ?>">
      <span>Descuento Cupón<?= $cupon ? ' (' . htmlspecialchars($cupon['desc'], ENT_QUOTES) . ')' : '' ?>:</span>
      <span id="cartDiscount" class="price" data-precio="<?= -(float) $descuento ?>">-<?= $fmt((float) $descuento) ?></span>
     </div>
     <div class="cart-summary-row">
      <span>Envío:</span>
      <span id="cartEnvio" class="price" data-precio="<?= (float) $envioDomicilio ?>"><?= $fmt($envioDomicilio) ?></span>
     </div>
     <p class="cart-envio-zona" id="cartEnvioZona">
      <?= $predeterminada !== null
           ? 'Zona: ' . htmlspecialchars((string) ($predeterminada['zona'] ?? ''), ENT_QUOTES)
           : 'Elige una dirección de entrega para calcular la zona y el costo de envío' ?>
     </p>
     <div class="cart-summary-row total-row">
      <span>Total simulado:</span>
      <span id="cartTotal" class="price" data-precio="<?= $subtotal - $descuento + $envioDomicilio ?>"><?= $fmt($subtotal - $descuento + $envioDomicilio) ?></span>
     </div>

     <div class="cart-footer-actions">
      <?php if (!$estaVacio): ?>
       <?php if (\Model\Cuenta::estaRegistrado()): ?>
      <button class="btn-checkout-card" onclick="abrirCheckout()">
       <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <use href="img/icons.svg#icon-card"/>
       </svg>
       Continuar con la simulación
      </button>
       <?php else: ?>
      <a class="btn-checkout-card" href="?ruta=login">
       <svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-lock"/></svg> Inicia sesión para simular una compra
      </a>
       <?php endif; ?>
      <?php endif; ?>
      <button class="btn-vaciar-cart" onclick="vaciarCarrito()">
       Vaciar Carrito
      </button>
     </div>
    </div>
   </div>
  </div>

  <!-- ================= CHECKOUT MULTIMÉTODO (PÁGINA) ================= -->
  <?php if (!$estaVacio): ?><?php if (\Model\Cuenta::estaRegistrado()): ?>
  <section id="checkout" class="cart-page-section">
   <div class="cart-page-section-head">
    <h2>Simular pedido</h2>
    <p>Prueba los métodos de pago sin realizar una compra real.</p>
   </div>

   <p class="sim-note" id="checkoutDemoNotice">
    <strong>Pago de prueba.</strong> No introduzcas tarjetas reales ni envíes dinero por Yape o transferencia.
    Usa datos ficticios para el cliente y la entrega. No se enviarán productos.
   </p>

   <form id="checkoutForm" method="post" action="?ruta=carrito/procesar" onsubmit="procesarPago(event)" aria-describedby="checkoutDemoNotice">
    <?= \Security\Proteccion::campo() ?>
    <input type="hidden" name="metodo" value="tarjeta"/>
    <input type="hidden" name="modalidad" value="domicilio"/>
    <input type="hidden" name="comprobante" value="boleta"/>

    <!-- PASO 1: Datos Personales y Entrega -->
    <div class="form-section-title">1. Datos del Cliente y Entrega</div>
    <div class="form-row">
     <div class="form-group">
      <label for="clienteNombre">Nombre Completo *</label>
      <input type="text" id="clienteNombre" name="nombre" placeholder="Ej. Juan Pérez"
             value="<?= htmlspecialchars(trim((string) ($usuario['nombre'] ?? '') . ' ' . (string) ($usuario['apellidos'] ?? '')), ENT_QUOTES) ?>" required/>
     </div>
     <div class="form-group">
      <label for="clienteTelefono">Teléfono / WhatsApp *</label>
      <input type="tel" id="clienteTelefono" name="telefono" placeholder="Ej. 999000002"
             value="<?= htmlspecialchars((string) ($usuario['telefono'] ?? ''), ENT_QUOTES) ?>" required/>
     </div>
    </div>
    <div class="form-row">
     <div class="form-group">
      <label for="clienteEmail">Correo Electrónico *</label>
      <input type="email" id="clienteEmail" name="email" placeholder="correo@ejemplo.com"
             value="<?= htmlspecialchars((string) ($usuario['email'] ?? ''), ENT_QUOTES) ?>" required/>
     </div>
    </div>

    <!-- Entrega (Etapa 5): dirección guardada con su ZONA, o una dirección nueva -->
    <div id="bloqueDireccionEntrega">
     <div class="form-section-title">Dirección de entrega</div>
     <?php if ($direcciones !== []): ?>
     <div class="addr-opts">
      <?php foreach ($direcciones as $d): ?>
      <label class="addr-opt<?= $direccionPreseleccionada === (int) $d['id'] ? ' active' : '' ?>">
       <input type="radio" name="direccion_id" value="<?= (int) $d['id'] ?>"
              data-costo="<?= (float) ($d['costo'] ?? 0) ?>"
              data-zona="<?= htmlspecialchars((string) ($d['zona'] ?? ''), ENT_QUOTES) ?>"
              data-texto="<?= htmlspecialchars(\Model\Direccion::texto($d), ENT_QUOTES) ?>"
              onchange="cambiarDireccionEntrega()"
              <?= $direccionPreseleccionada === (int) $d['id'] ? 'checked' : '' ?>>
       <span class="addr-opt-cuerpo">
        <strong><?= htmlspecialchars((string) $d['etiqueta'], ENT_QUOTES) ?></strong>
        <span class="addr-opt-dir"><?= htmlspecialchars((string) $d['direccion'], ENT_QUOTES) ?></span>
        <span class="addr-opt-zona"><?= htmlspecialchars((string) ($d['zona'] ?? 'Sin zona'), ENT_QUOTES) ?> — <?= $fmt((float) ($d['costo'] ?? 0)) ?> (<?= htmlspecialchars((string) ($d['dias_estimados'] ?? ''), ENT_QUOTES) ?>)</span>
       </span>
      </label>
      <?php endforeach; ?>
      <label class="addr-opt<?= $direccionPreseleccionada === 0 ? ' active' : '' ?>">
       <input type="radio" name="direccion_id" value="0" onchange="cambiarDireccionEntrega()"
              <?= $direccionPreseleccionada === 0 ? 'checked' : '' ?>>
       <span class="addr-opt-cuerpo"><strong>Usar una dirección nueva</strong></span>
      </label>
     </div>
     <?php else: ?>
     <input type="hidden" name="direccion_id" value="0"/>
     <?php endif; ?>

     <div class="addr-nueva" id="bloqueNuevaDireccion" style="<?= $direccionPreseleccionada === 0 ? '' : 'display:none;' ?>">
      <div class="form-row">
       <div class="form-group">
        <label for="clienteDireccion">Dirección *</label>
        <input type="text" id="clienteDireccion" name="nueva_direccion" maxlength="255"
               placeholder="Ej. La Joya / Arequipa, Calle..." <?= $direccionPreseleccionada === 0 ? 'required' : '' ?>/>
       </div>
       <div class="form-group">
        <label for="dirZonaNueva">Zona de envío *</label>
        <select id="dirZonaNueva" name="tarifa_envio_id" onchange="recalcularTotales()">
         <?php foreach ($zonas as $z): ?>
         <option value="<?= (int) $z['id'] ?>" data-costo="<?= (float) $z['costo'] ?>"
                 <?= $tarifaPreseleccionada === (int) $z['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string) $z['zona'], ENT_QUOTES) ?> — <?= $fmt((float) $z['costo']) ?> (<?= htmlspecialchars((string) $z['dias_estimados'], ENT_QUOTES) ?>)
         </option>
         <?php endforeach; ?>
        </select>
       </div>
      </div>
      <div class="form-group">
       <label for="dirReferenciaNueva">Referencia</label>
       <input type="text" id="dirReferenciaNueva" name="nueva_referencia" maxlength="180"
              placeholder="Ej. Frente al parque, puerta verde"/>
      </div>
      <a class="btn-mini addr-gestionar" href="?ruta=direcciones">Guardar esta dirección en "Mis direcciones"</a>
     </div>
    </div>

    <!-- PASO 2: Facturación Electrónica (SUNAT) -->
    <div class="form-section-title">2. Comprobante de prueba (sin validez fiscal)</div>
    <div class="invoice-type-selector">
     <label class="invoice-opt active" id="optBoleta" onclick="cambiarTipoComprobante('boleta')">
      <input type="radio" name="tipoComprobante" value="boleta" checked/>
      <span>Boleta de Venta (DNI)</span>
     </label>
     <label class="invoice-opt" id="optFactura" onclick="cambiarTipoComprobante('factura')">
      <input type="radio" name="tipoComprobante" value="factura"/>
      <span>Factura Electrónica (RUC)</span>
     </label>
    </div>

    <div id="campoBoletaDni" class="form-group">
     <label for="clienteDni">Número de DNI (8 dígitos) *</label>
     <input type="text" id="clienteDni" name="dni" maxlength="8" placeholder="Ej. 72345678"
            value="<?= htmlspecialchars((string) ($usuario['dni'] ?? ''), ENT_QUOTES) ?>"/>
    </div>

    <div id="campoFacturaRuc" class="form-row" style="display: none;">
     <div class="form-group">
      <label for="clienteRuc">Número de RUC (11 dígitos) *</label>
      <input type="text" id="clienteRuc" name="ruc" maxlength="11" placeholder="Ej. 20601234567"
             value="<?= htmlspecialchars((string) ($usuario['ruc'] ?? ''), ENT_QUOTES) ?>"/>
     </div>
     <div class="form-group">
      <label for="clienteRazonSocial">Razón Social *</label>
      <input type="text" id="clienteRazonSocial" name="empresa" placeholder="Ej. Tech Solutions S.A.C."
             value="<?= htmlspecialchars((string) ($usuario['empresa'] ?? ''), ENT_QUOTES) ?>"/>
     </div>
    </div>

    <!-- PASO 3: Método de Pago -->
    <div class="form-section-title">3. Selecciona tu Método de Pago</div>
    <div class="checkout-method-tabs">
     <button type="button" class="pay-tab-btn active" id="tabTarjeta" onclick="cambiarMetodoPago('tarjeta')">
      <span class="tab-icon"><svg class="inline-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-card"/></svg></span>
      <span class="tab-title">Tarjeta</span>
      <span class="tab-sub">Débito / Crédito</span>
     </button>
     <button type="button" class="pay-tab-btn" id="tabYape" onclick="cambiarMetodoPago('yape')">
      <span class="tab-icon"><svg class="inline-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-phone"/></svg></span>
      <span class="tab-title">Yape / Plin</span>
      <span class="tab-sub">Código de prueba</span>
     </button>
     <button type="button" class="pay-tab-btn" id="tabTransf" onclick="cambiarMetodoPago('transferencia')">
      <span class="tab-icon"><svg class="inline-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bank"/></svg></span>
      <span class="tab-title">Transferencia</span>
      <span class="tab-sub">Sin envío de dinero</span>
     </button>
     <button type="button" class="pay-tab-btn" id="tabContra" onclick="cambiarMetodoPago('contraentrega')">
      <span class="tab-icon"><svg class="inline-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-cash"/></svg></span>
      <span class="tab-title">En Tienda</span>
      <span class="tab-sub">Sin retiro real</span>
     </button>
    </div>

    <!-- Panel 1: Tarjeta de Crédito/Débito -->
    <div class="payment-box" id="panelMetodoTarjeta">
     <div class="virtual-card" id="virtualCard">
      <div class="card-chip">
       <div class="chip-line"></div>
       <div class="chip-line"></div>
      </div>
      <div class="card-brand-badge" id="cardBrandLogo">TARJETA</div>
      <div class="card-number-display" id="cardPreviewNumber">•••• •••• •••• ••••</div>
      <div class="card-bottom">
       <div class="card-info-group">
        <span class="card-label">TITULAR</span>
        <span class="card-val" id="cardPreviewName">NOMBRE Y APELLIDO</span>
       </div>
       <div class="card-info-group">
        <span class="card-label">EXPIRA</span>
        <span class="card-val" id="cardPreviewExpiry">MM/AA</span>
       </div>
      </div>
     </div>

     <p class="sim-note">
      <strong>Solo tarjetas de prueba:</strong> no se realiza ningún cobro real y los datos de la tarjeta no se guardan.
      Aprobada: <code>4111 1111 1111 1111</code> · rechazada: <code>4000 0000 0000 0002</code> · vence <code>12/40</code> · CVV <code>123</code>.
     </p>

     <div class="form-group">
      <label for="tarjetaNumero">Número de tarjeta de prueba *</label>
      <div class="input-with-badge">
       <input type="text" id="tarjetaNumero" name="tarjeta_numero" maxlength="19" placeholder="4111 1111 1111 1111"
              inputmode="numeric" autocomplete="off"/>
       <span class="input-card-icon" id="inputBrandIcon"><svg class="inline-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-card"/></svg></span>
      </div>
     </div>
     <div class="form-group">
      <label for="tarjetaTitular">Titular ficticio de la tarjeta *</label>
      <input type="text" id="tarjetaTitular" name="tarjeta_titular" maxlength="80"
             placeholder="CLIENTE DEMO" autocomplete="off"/>
     </div>
     <div class="form-row">
      <div class="form-group">
       <label for="tarjetaExpiracion">Expiración (MM/AA) *</label>
       <input type="text" id="tarjetaExpiracion" name="tarjeta_expiracion" maxlength="5"
              placeholder="MM/AA" inputmode="numeric" autocomplete="off"/>
      </div>
      <div class="form-group">
       <label for="tarjetaCvv">CVV / CVC *</label>
       <input type="password" id="tarjetaCvv" name="tarjeta_cvv" maxlength="4"
              placeholder="•••" inputmode="numeric" autocomplete="off"/>
      </div>
     </div>
    </div>

    <!-- Panel 2: Yape y Plin -->
    <div class="payment-box" id="panelMetodoYape" style="display:none;">
     <div class="qr-details">
      <div class="qr-info-row">
       <span>Importe simulado (no enviar):</span>
       <strong class="highlight-pen" id="yapeMontoPEN"><?= $fmt($subtotal - $descuento + $envioDomicilio) ?></strong>
      </div>
     </div>
     <p class="sim-note">
      <strong>Yape / Plin simulado:</strong> no abras la aplicación ni envíes dinero. Usa el código <code>849201</code> para aprobar
      o <code>000000</code> para simular un rechazo.
     </p>
     <div class="form-row" style="margin-top: 15px;">
      <div class="form-group">
       <label for="yapeTelefono">Celular ficticio de prueba *</label>
       <input type="tel" id="yapeTelefono" name="yape_telefono" maxlength="9"
              placeholder="Ej. 999000002" inputmode="numeric"
              value="<?= htmlspecialchars((string) ($usuario['telefono'] ?? ''), ENT_QUOTES) ?>"/>
      </div>
      <div class="form-group">
       <label for="yapeCodigoOperacion">Código de prueba Yape/Plin *</label>
       <input type="text" id="yapeCodigoOperacion" name="yape_codigo" placeholder="Ej. 849201"
              maxlength="6" inputmode="numeric"/>
      </div>
     </div>
    </div>

    <!-- Panel 3: Transferencia Bancaria Directa -->
    <div class="payment-box" id="panelMetodoTransf" style="display:none;">
     <p class="sim-note">
      <strong>Transferencia simulada:</strong> no transfieras dinero. Usa una referencia ficticia como <code>DEMO1234</code>.
      El pedido de prueba quedará <strong>Pendiente</strong> y el pago <strong>En verificación</strong>, sin consultar al banco.
     </p>
     <div class="form-group" style="margin-top: 15px;">
      <label for="transfCodigoOperacion">Referencia ficticia de transferencia *</label>
      <input type="text" id="transfCodigoOperacion" name="transf_codigo" placeholder="Ej. DEMO1234"
             maxlength="15" minlength="4" inputmode="text"/>
     </div>
    </div>

    <!-- Panel 4: Contra Entrega / En Tienda -->
    <div class="payment-box" id="panelMetodoContra" style="display:none;">
     <div class="store-pickup-box">
      <div class="pickup-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-briefcase"/></svg></div>
      <h4>Simulación de retiro en tienda / contraentrega</h4>
      <p>Prueba esta modalidad sin visitar una sede ni realizar pagos.</p>
      <p class="sim-note">
       <strong>Modo demostración:</strong> el pedido de prueba quedará <strong>Pendiente</strong>. No habrá entrega ni cobro real.
      </p>
     </div>
    </div>

    <!-- Resumen Total y Botón de Pago -->
    <div class="checkout-total-banner">
     <div>
      <span>Total simulado:</span>
      <div id="checkoutDiscountNote" class="checkout-discount-note" style="<?= $cupon ? '' : 'display:none;' ?>"><?= $cupon ? 'Cupón aplicado: ' . htmlspecialchars($cupon['desc'], ENT_QUOTES) : '' ?></div>
     </div>
     <strong id="checkoutTotalMonto" class="price" data-precio="<?= $subtotal - $descuento + $envioDomicilio ?>"><?= $fmt($subtotal - $descuento + $envioDomicilio) ?></strong>
    </div>

    <div class="security-badges">
     <span><svg class="inline-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-flask"/></svg> Pago simulado sin cobro real</span>
     <span><svg class="inline-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-truck"/></svg> Sin envíos reales</span>
    </div>

    <button type="submit" class="btn-pagar-ahora" id="btnPagarAhora">
     <span class="btn-text"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-flask"/></svg> Simular compra</span>
     <span class="btn-spinner" style="display:none;"></span>
    </button>
   </form>
  </section>
  <?php else: ?>
  <section class="cart-page-section">
   <div class="auth-gate">
    <div class="gate-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-lock"/></svg></div>
    <h4>Inicia sesión para simular una compra</h4>
    <p>El checkout es exclusivo para clientes registrados. Inicia sesión o crea tu cuenta en menos de un minuto y tu pedido quedará asociado a tu cuenta.</p>
    <a class="btn-auth-gate" href="?ruta=login">Iniciar sesión / Crear cuenta</a>
   </div>
  </section>
  <?php endif; ?>
  <?php endif; ?>

  <!-- ================= HISTORIAL DE PEDIDOS / SEGUIMIENTO ================= -->
  <?php /* Plegado por defecto: sin esto el carrito medía 3.822 px y el
           checkout quedaba enterrado bajo el historial. */ ?>
  <details id="historial" class="cart-historial"<?= \Model\Cuenta::estaRegistrado() ? '' : ' open' ?>>
   <summary class="cart-historial-summary">Mis Pedidos y Seguimiento</summary>
   <section class="cart-page-section">
   <div class="cart-page-section-head">
    <p>
     Historial y seguimiento simulado de tus pedidos de prueba en Jeremy Tech
     <?php if (\Model\Cuenta::estaRegistrado()): ?>
     · <a class="cart-historial-link" href="?ruta=mis-pedidos">Ver todos en Mis pedidos</a>
     <?php endif; ?>
    </p>
   </div>

   <div id="ordersListContainer" class="orders-list-container">
    <?php if (!\Model\Cuenta::estaRegistrado()): ?>
    <div class="auth-gate">
     <div class="gate-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-lock"/></svg></div>
     <h4>Inicia sesión para ver tus pedidos</h4>
     <p>Tu historial de compras y seguimiento quedará asociado a tu cuenta de cliente.</p>
     <a class="btn-auth-gate" href="?ruta=login">Iniciar sesión / Crear cuenta</a>
    </div>
    <?php elseif (!$historial): ?>
    <div class="orders-empty">
     <div class="empty-icon"><svg class="inline-icon" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg></div>
     <h4>Aún no tienes pedidos</h4>
     <p>Cuando simules tu primera compra, aparecerá aquí el detalle y el estado del pedido de prueba.</p>
    </div>
    <?php else: ?>
     <?php foreach ($historial as $pedido): ?>
     <div class="order-card" id="pedido-<?= (int) $pedido['id'] ?>">
      <div class="order-card-header">
       <span class="order-id">Pedido de prueba #JT-<?= str_pad((string) $pedido['id'], 5, '0', STR_PAD_LEFT) ?></span>
       <span class="order-status status-<?= strtolower(htmlspecialchars((string) $pedido['estado'], ENT_QUOTES)) ?>"><?= htmlspecialchars((string) $pedido['estado'], ENT_QUOTES) ?></span>
      </div>
      <p class="sim-note">No se realizó ningún cobro real. El seguimiento es simulado; no se enviarán productos.</p>
      <div class="order-card-meta">
       <span><?= htmlspecialchars($fechaFmt((string) ($pedido['creado_en'] ?? '')), ENT_QUOTES) ?></span>
       <span>Total simulado: <?= $fmt((float) $pedido['total_pen']) ?></span>
       <span><?= htmlspecialchars((string) ($pedido['envio_zona'] ?? 'Recojo en tienda'), ENT_QUOTES) ?> · <?= $fmt((float) ($pedido['envio_costo'] ?? 0)) ?></span>
      </div>
      <?php if (!empty($pedido['pago'])): ?>
      <div class="order-card-meta">
       <span><?= htmlspecialchars(ucfirst((string) $pedido['pago']['metodo']), ENT_QUOTES) ?> · <?= htmlspecialchars((string) $pedido['pago']['estado'], ENT_QUOTES) ?></span>
       <?php if ((string) ($pedido['pago']['transaccion_id'] ?? '') !== ''): ?>
       <span><?= htmlspecialchars((string) $pedido['pago']['transaccion_id'], ENT_QUOTES) ?></span>
       <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ((string) ($pedido['direccion_entrega'] ?? '') !== ''): ?>
      <div class="order-card-meta">
       <span><?= htmlspecialchars((string) $pedido['direccion_entrega'], ENT_QUOTES) ?></span>
      </div>
      <?php endif; ?>

      <?php $pasos = \Model\PedidoEvento::lineaTiempo($pedido); ?>
      <div class="order-timeline" aria-label="Seguimiento del pedido #JT-<?= str_pad((string) $pedido['id'], 5, '0', STR_PAD_LEFT) ?>">
       <?php foreach ($pasos as $paso): ?>
       <div class="timeline-step <?= htmlspecialchars((string) $paso['estado'], ENT_QUOTES) ?> step-<?= htmlspecialchars((string) $paso['clave'], ENT_QUOTES) ?>">
        <span class="timeline-dot" aria-hidden="true"><?= $paso['estado'] === 'completed' ? '✓' : ($paso['estado'] === 'current' ? '●' : '○') ?></span>
        <div class="timeline-content">
         <span class="timeline-label"><?= htmlspecialchars((string) $paso['etiqueta'], ENT_QUOTES) ?></span>
         <?php if ((string) ($paso['detalle'] ?? '') !== ''): ?>
         <span class="timeline-detail"><?= htmlspecialchars((string) $paso['detalle'], ENT_QUOTES) ?></span>
         <?php endif; ?>
         <?php if ((string) ($paso['fecha'] ?? '') !== ''): ?>
         <span class="timeline-date"><?= htmlspecialchars($fechaFmt((string) $paso['fecha']), ENT_QUOTES) ?></span>
         <?php endif; ?>
        </div>
       </div>
       <?php endforeach; ?>
      </div>

      <ul class="order-card-items">
       <?php foreach ($pedido['detalle'] as $item): ?>
       <li><span><?= htmlspecialchars((string) $item['producto_nombre'], ENT_QUOTES) ?></span><span>×<?= (int) $item['cantidad'] ?></span></li>
       <?php endforeach; ?>
      </ul>
      <button class="btn-order-whatsapp" onclick="contactarOrdenWhatsApp(<?= (int) $pedido['id'] ?>)">
       <svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-chat"/></svg> Consultar por WhatsApp
      </button>
     </div>
     <?php endforeach; ?>
    <?php endif; ?>
   </div>
   </section>
  </details>

</main>

<!-- ================= MODAL DE ÉXITO / TICKET DIGITAL ================= -->
<div id="successModal" class="modal-overlay" style="display:none;">
 <div class="modal-card success-card">
  <div class="success-icon-wrap">
   <img class="checkmark" src="img/checkmark.svg" alt="Pedido confirmado"/>
  </div>
  <h2 id="successTitle">Pedido de prueba registrado</h2>
  <p class="success-subtitle" id="successSubtitle">No se realizó ningún cobro real. No se enviarán productos.</p>

  <div class="ticket-recibo" id="ticketRecibo">
   <div class="ticket-header">
    <span class="ticket-brand">JEREMY TECH</span>
    <span class="ticket-order-id" id="ticketOrderId">#JT-00000</span>
   </div>
   <div class="ticket-body">
    <div class="ticket-row"><span>Cliente:</span> <strong id="ticketCliente"></strong></div>
    <div class="ticket-row"><span>Documento:</span> <strong id="ticketDocumento"></strong></div>
    <div class="ticket-row"><span>Comprobante de prueba:</span> <strong id="ticketComprobante"></strong></div>
    <div class="ticket-row"><span>Método de Pago:</span> <strong id="ticketMetodoPago"></strong></div>
    <div class="ticket-row"><span>Estado del pago simulado:</span> <strong id="ticketPagoEstado"></strong></div>
    <div class="ticket-row"><span>Referencia:</span> <strong id="ticketTransaccion"></strong></div>
    <div class="ticket-row"><span>Entrega simulada:</span> <strong id="ticketEntrega"></strong></div>
    <div class="ticket-row"><span>Fecha y Hora:</span> <strong id="ticketFecha"></strong></div>
    <div class="ticket-divider"></div>
    <div class="ticket-items" id="ticketItems"></div>
    <div class="ticket-divider"></div>
    <div class="ticket-row total-ticket"><span id="ticketTotalLabel">Total simulado:</span> <strong id="ticketTotal"></strong></div>
   </div>
  </div>

  <div class="success-actions">
   <button class="btn-whatsapp-recibo" onclick="enviarReciboWhatsApp()">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
     <use href="img/icons.svg#icon-whatsapp"/>
    </svg>
    Compartir pedido de prueba por WhatsApp
   </button>
   <button class="btn-ver-mis-pedidos" onclick="cerrarModalExito(); abrirHistorialPedidos();">
    <svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg> Ver en Mis Pedidos
   </button>
   <button class="btn-seguir-comprando" onclick="cerrarModalExito()">Seguir Comprando</button>
  </div>
 </div>
</div>
