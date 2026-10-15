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
<script>
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
</script>

<main class="cart-page">

  <!-- ================= RESUMEN DEL CARRITO ================= -->
  <div class="cart-page-header">
   <h1>🛒 Tu Carrito de Compras</h1>
   <p>Revisa tus productos, aplica tu cupón y finaliza tu pedido de forma segura.</p>
  </div>

  <div class="cart-page-grid">

   <!-- Columna izquierda: items -->
   <div class="cart-page-items">
    <?php if ($estaVacio): ?>
    <div id="cartEmptyState" class="cart-empty-state">
     <div class="empty-icon">🛒</div>
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
      <img src="<?= htmlspecialchars($prod['imagen'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($prod['nombre'], ENT_QUOTES) ?>"/>
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
      <button class="cart-item-remove" onclick="eliminarDelCarrito('<?= $id ?>')" title="Eliminar">✕</button>
     </div>
     <?php endforeach; ?>
    </div>
    <?php endif; ?>
   </div>

   <!-- Columna derecha: resumen / cupón / envío -->
   <div class="cart-page-summary">
    <div class="cart-panel">
     <h3>Resumen del Pedido</h3>

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
        <span>🚚 Domicilio</span>
       </label>
       <label class="delivery-opt" id="delivOptTienda" onclick="cambiarOpcionEnvio('tienda')">
        <input type="radio" name="tipoEnvio" value="tienda"/>
        <span>🏬 En Tienda (Gratis)</span>
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
      <span>Total a Pagar:</span>
      <span id="cartTotal" class="price" data-precio="<?= $subtotal - $descuento + $envioDomicilio ?>"><?= $fmt($subtotal - $descuento + $envioDomicilio) ?></span>
     </div>

     <div class="cart-footer-actions">
      <?php if (!$estaVacio): ?>
       <?php if (\Model\Cuenta::estaRegistrado()): ?>
      <button class="btn-checkout-card" onclick="abrirCheckout()">
       <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <use href="img/icons.svg#icon-card"/>
       </svg>
       Proceder al Checkout
      </button>
       <?php else: ?>
      <a class="btn-checkout-card" href="?ruta=login">
       🔒 Inicia sesión para comprar
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
    <h2>🛍️ Finalizar Pedido — Jeremy Tech</h2>
    <p>Checkout seguro con múltiples medios de pago</p>
   </div>

   <form id="checkoutForm" onsubmit="procesarPago(event)">
    <input type="hidden" name="metodo" value="tarjeta"/>
    <input type="hidden" name="modalidad" value="domicilio"/>
    <input type="hidden" name="comprobante" value="boleta"/>
    <input type="hidden" name="tipo" value="B2C"/>

    <!-- PASO 1: Datos Personales y Entrega -->
    <div class="form-section-title">1. Datos del Cliente y Entrega</div>
    <div class="form-row">
     <div class="form-group">
      <label for="clienteNombre">Nombre Completo *</label>
      <input type="text" id="clienteNombre" name="nombre" placeholder="Ej. Juan Pérez" required/>
     </div>
     <div class="form-group">
      <label for="clienteTelefono">Teléfono / WhatsApp *</label>
      <input type="tel" id="clienteTelefono" name="telefono" placeholder="Ej. 997833866" required/>
     </div>
    </div>
    <div class="form-row">
     <div class="form-group">
      <label for="clienteEmail">Correo Electrónico *</label>
      <input type="email" id="clienteEmail" name="email" placeholder="correo@ejemplo.com" required/>
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
        <span class="addr-opt-zona">🚚 <?= htmlspecialchars((string) ($d['zona'] ?? 'Sin zona'), ENT_QUOTES) ?> — <?= $fmt((float) ($d['costo'] ?? 0)) ?> (<?= htmlspecialchars((string) ($d['dias_estimados'] ?? ''), ENT_QUOTES) ?>)</span>
       </span>
      </label>
      <?php endforeach; ?>
      <label class="addr-opt<?= $direccionPreseleccionada === 0 ? ' active' : '' ?>">
       <input type="radio" name="direccion_id" value="0" onchange="cambiarDireccionEntrega()"
              <?= $direccionPreseleccionada === 0 ? 'checked' : '' ?>>
       <span class="addr-opt-cuerpo"><strong>➕ Usar una dirección nueva</strong></span>
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
      <a class="btn-mini addr-gestionar" href="?ruta=direcciones">📍 Guardar esta dirección en "Mis direcciones"</a>
     </div>
    </div>

    <!-- PASO 2: Facturación Electrónica (SUNAT) -->
    <div class="form-section-title">2. Comprobante de Pago (SUNAT)</div>
    <div class="invoice-type-selector">
     <label class="invoice-opt active" id="optBoleta" onclick="cambiarTipoComprobante('boleta')">
      <input type="radio" name="tipoComprobante" value="boleta" checked/>
      <span>🧾 Boleta de Venta (DNI)</span>
     </label>
     <label class="invoice-opt" id="optFactura" onclick="cambiarTipoComprobante('factura')">
      <input type="radio" name="tipoComprobante" value="factura"/>
      <span>📑 Factura Electrónica (RUC)</span>
     </label>
    </div>

    <div id="campoBoletaDni" class="form-group">
     <label for="clienteDni">Número de DNI (8 dígitos) *</label>
     <input type="text" id="clienteDni" name="dni" maxlength="8" placeholder="Ej. 72345678"/>
    </div>

    <div id="campoFacturaRuc" class="form-row" style="display: none;">
     <div class="form-group">
      <label for="clienteRuc">Número de RUC (11 dígitos) *</label>
      <input type="text" id="clienteRuc" name="ruc" maxlength="11" placeholder="Ej. 20601234567"/>
     </div>
     <div class="form-group">
      <label for="clienteRazonSocial">Razón Social *</label>
      <input type="text" id="clienteRazonSocial" name="empresa" placeholder="Ej. Tech Solutions S.A.C."/>
     </div>
    </div>

    <!-- PASO 3: Método de Pago -->
    <div class="form-section-title">3. Selecciona tu Método de Pago</div>
    <div class="checkout-method-tabs">
     <button type="button" class="pay-tab-btn active" id="tabTarjeta" onclick="cambiarMetodoPago('tarjeta')">
      <span class="tab-icon">💳</span>
      <span class="tab-title">Tarjeta</span>
      <span class="tab-sub">Débito / Crédito</span>
     </button>
     <button type="button" class="pay-tab-btn" id="tabYape" onclick="cambiarMetodoPago('yape')">
      <span class="tab-icon">📱</span>
      <span class="tab-title">Yape / Plin</span>
      <span class="tab-sub">Pago QR Móvil</span>
     </button>
     <button type="button" class="pay-tab-btn" id="tabTransf" onclick="cambiarMetodoPago('transferencia')">
      <span class="tab-icon">🏦</span>
      <span class="tab-title">Transferencia</span>
      <span class="tab-sub">BCP / Interbank</span>
     </button>
     <button type="button" class="pay-tab-btn" id="tabContra" onclick="cambiarMetodoPago('contraentrega')">
      <span class="tab-icon">💵</span>
      <span class="tab-title">En Tienda</span>
      <span class="tab-sub">Pago al Recibir</span>
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

     <div class="form-group">
      <label for="tarjetaNumero">Número de Tarjeta *</label>
      <div class="input-with-badge">
       <input type="text" id="tarjetaNumero" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="cc-number"/>
       <span class="input-card-icon" id="inputBrandIcon">💳</span>
      </div>
     </div>
     <div class="form-group">
      <label for="tarjetaTitular">Nombre del Titular *</label>
      <input type="text" id="tarjetaTitular" placeholder="COMO APARECE EN LA TARJETA" autocomplete="cc-name"/>
     </div>
     <div class="form-row">
      <div class="form-group">
       <label for="tarjetaExpiracion">Expiración (MM/AA) *</label>
       <input type="text" id="tarjetaExpiracion" maxlength="5" placeholder="MM/AA" autocomplete="cc-exp"/>
      </div>
      <div class="form-group">
       <label for="tarjetaCvv">CVV / CVC *</label>
       <input type="password" id="tarjetaCvv" maxlength="4" placeholder="•••" autocomplete="cc-csc"/>
      </div>
     </div>
    </div>

    <!-- Panel 2: Yape y Plin -->
    <div class="payment-box" id="panelMetodoYape" style="display:none;">
     <div class="qr-payment-box">
      <div class="qr-visual">
       <div class="qr-code-frame">
        <img class="qr-svg" src="img/qr.svg" alt="QR de pago Yape y Plin"/>
       </div>
       <div class="qr-brand-badges">
        <span class="yape-tag">YAPE</span>
        <span class="plin-tag">PLIN</span>
       </div>
      </div>
      <div class="qr-details">
       <div class="qr-info-row">
        <span>Número de Destino:</span>
        <div class="copy-field">
         <strong>997833866</strong>
         <button type="button" class="btn-copy-mini" onclick="copiarTexto('997833866', 'Número copiado')" title="Copiar">📋</button>
        </div>
       </div>
       <div class="qr-info-row">
        <span>Titular:</span>
        <strong>Jeremy Sarmiento / Jeremy Tech</strong>
       </div>
       <div class="qr-info-row">
        <span>Monto a Yapear/Plinear:</span>
        <strong class="highlight-pen" id="yapeMontoPEN"><?= $fmt($subtotal - $descuento + $envioDomicilio) ?></strong>
       </div>
      </div>
     </div>
     <div class="form-group" style="margin-top: 15px;">
      <label for="yapeCodigoOperacion">Código / Número de Operación Yape/Plin *</label>
      <input type="text" id="yapeCodigoOperacion" placeholder="Ej. 849201" maxlength="12"/>
     </div>
    </div>

    <!-- Panel 3: Transferencia Bancaria Directa -->
    <div class="payment-box" id="panelMetodoTransf" style="display:none;">
     <div class="bank-accounts-list">
      <div class="bank-card bcp-card">
       <div class="bank-card-header">
        <strong>🔵 BCP — Banco de Crédito</strong>
        <button type="button" class="btn-copy-mini" onclick="copiarTexto('215-98765432-0-88', 'Cuenta BCP copiada')">Copiar</button>
       </div>
       <div class="bank-card-body">
        <div>Cuenta: <code>215-98765432-0-88</code></div>
        <div>CCI: <code>002-215-0098765432088-12</code></div>
       </div>
      </div>
      <div class="bank-card interbank-card">
       <div class="bank-card-header">
        <strong>🟢 Interbank</strong>
        <button type="button" class="btn-copy-mini" onclick="copiarTexto('898-3001234567', 'Cuenta Interbank copiada')">Copiar</button>
       </div>
       <div class="bank-card-body">
        <div>Cuenta: <code>898-3001234567</code></div>
        <div>CCI: <code>003-898-013001234567-45</code></div>
       </div>
      </div>
      <div class="bank-card bbva-card">
       <div class="bank-card-header">
        <strong>🔵 BBVA Continental</strong>
        <button type="button" class="btn-copy-mini" onclick="copiarTexto('0011-0234-0200987654', 'Cuenta BBVA copiada')">Copiar</button>
       </div>
       <div class="bank-card-body">
        <div>Cuenta: <code>0011-0234-0200987654</code></div>
        <div>CCI: <code>011-234-000200987654-78</code></div>
       </div>
      </div>
     </div>
     <div class="form-group" style="margin-top: 15px;">
      <label for="transfCodigoOperacion">Número de Operación Bancaria *</label>
      <input type="text" id="transfCodigoOperacion" placeholder="Ej. 1290348" maxlength="15"/>
     </div>
    </div>

    <!-- Panel 4: Contra Entrega / En Tienda -->
    <div class="payment-box" id="panelMetodoContra" style="display:none;">
     <div class="store-pickup-box">
      <div class="pickup-icon">🏬</div>
      <h4>Pago y Retiro en Tienda Física / Contraentrega</h4>
      <p>Paga con efectivo, tarjeta o Yape al momento de recibir tu pedido o al retirarlo en nuestra sede.</p>
      <div class="pickup-location-card">
       <strong>📍 Sede Principal Jeremy Tech:</strong>
       <p>La Paz Soldán, frente al salón de eventos "Los Portales", La Joya, Arequipa.</p>
       <p class="pickup-hours">🕒 Horario de Atención: Lunes a Sábado de 8:30 AM a 8:00 PM</p>
      </div>
     </div>
    </div>

    <!-- Resumen Total y Botón de Pago -->
    <div class="checkout-total-banner">
     <div>
      <span>Total a Pagar:</span>
      <div id="checkoutDiscountNote" class="checkout-discount-note" style="<?= $cupon ? '' : 'display:none;' ?>"><?= $cupon ? 'Cupón aplicado: ' . htmlspecialchars($cupon['desc'], ENT_QUOTES) : '' ?></div>
     </div>
     <strong id="checkoutTotalMonto" class="price" data-precio="<?= $subtotal - $descuento + $envioDomicilio ?>"><?= $fmt($subtotal - $descuento + $envioDomicilio) ?></strong>
    </div>

    <div class="security-badges">
     <span>🔒 Cifrado SSL 256-bit</span>
     <span>🛡️ Garantía Oficial Jeremy Tech</span>
     <span>⚡ Despacho Inmediato</span>
    </div>

    <button type="submit" class="btn-pagar-ahora" id="btnPagarAhora">
     <span class="btn-text">🔒 Confirmar Pedido y Pagar</span>
     <span class="btn-spinner" style="display:none;"></span>
    </button>
   </form>
  </section>
  <?php else: ?>
  <section class="cart-page-section">
   <div class="auth-gate">
    <div class="gate-icon">🔒</div>
    <h4>Inicia sesión para completar tu compra</h4>
    <p>El checkout es exclusivo para clientes registrados. Inicia sesión o crea tu cuenta en menos de un minuto y tu pedido quedará asociado a tu cuenta.</p>
    <a class="btn-auth-gate" href="?ruta=login">Iniciar sesión / Crear cuenta</a>
   </div>
  </section>
  <?php endif; ?>
  <?php endif; ?>

  <!-- ================= HISTORIAL DE PEDIDOS / SEGUIMIENTO ================= -->
  <section id="historial" class="cart-page-section">
   <div class="cart-page-section-head">
    <h2>📦 Mis Pedidos y Seguimiento</h2>
    <p>Historial y estado de tus compras en Jeremy Tech</p>
   </div>

   <div id="ordersListContainer" class="orders-list-container">
    <?php if (!\Model\Cuenta::estaRegistrado()): ?>
    <div class="auth-gate">
     <div class="gate-icon">🔒</div>
     <h4>Inicia sesión para ver tus pedidos</h4>
     <p>Tu historial de compras y seguimiento quedará asociado a tu cuenta de cliente.</p>
     <a class="btn-auth-gate" href="?ruta=login">Iniciar sesión / Crear cuenta</a>
    </div>
    <?php elseif (!$historial): ?>
    <div class="orders-empty">
     <div class="empty-icon">📦</div>
     <h4>Aún no tienes pedidos</h4>
     <p>Cuando realices tu primera compra, aparecerá aquí el detalle y el estado del pedido.</p>
    </div>
    <?php else: ?>
     <?php foreach ($historial as $pedido): ?>
     <div class="order-card">
      <div class="order-card-header">
       <span class="order-id">#JT-<?= str_pad((string) $pedido['id'], 5, '0', STR_PAD_LEFT) ?></span>
       <span class="order-status status-<?= strtolower(htmlspecialchars((string) $pedido['estado'], ENT_QUOTES)) ?>"><?= htmlspecialchars((string) $pedido['estado'], ENT_QUOTES) ?></span>
      </div>
      <div class="order-card-meta">
       <span>📅 <?= htmlspecialchars((string) $pedido['creado_en'], ENT_QUOTES) ?></span>
       <span>💰 <?= $fmt((float) $pedido['total_pen']) ?></span>
       <span>🚚 <?= htmlspecialchars((string) ($pedido['envio_zona'] ?? 'Recojo en tienda'), ENT_QUOTES) ?> · <?= $fmt((float) ($pedido['envio_costo'] ?? 0)) ?></span>
      </div>
      <?php if ((string) ($pedido['direccion_entrega'] ?? '') !== ''): ?>
      <div class="order-card-meta">
       <span>📍 <?= htmlspecialchars((string) $pedido['direccion_entrega'], ENT_QUOTES) ?></span>
      </div>
      <?php endif; ?>
      <ul class="order-card-items">
       <?php foreach ($pedido['detalle'] as $item): ?>
       <li><span><?= htmlspecialchars((string) $item['producto_nombre'], ENT_QUOTES) ?></span><span>×<?= (int) $item['cantidad'] ?></span></li>
       <?php endforeach; ?>
      </ul>
      <button class="btn-order-whatsapp" onclick="contactarOrdenWhatsApp(<?= (int) $pedido['id'] ?>)">
       💬 Consultar por WhatsApp
      </button>
     </div>
     <?php endforeach; ?>
    <?php endif; ?>
   </div>
  </section>

</main>

<!-- ================= MODAL DE ÉXITO / TICKET DIGITAL ================= -->
<div id="successModal" class="modal-overlay" style="display:none;">
 <div class="modal-card success-card">
  <div class="success-icon-wrap">
   <img class="checkmark" src="img/checkmark.svg" alt="Pedido confirmado"/>
  </div>
  <h2>¡Pedido Confirmado con Éxito!</h2>
  <p class="success-subtitle">Tu orden ha sido registrada en nuestro sistema y está en preparación.</p>

  <div class="ticket-recibo" id="ticketRecibo">
   <div class="ticket-header">
    <span class="ticket-brand">JEREMY TECH</span>
    <span class="ticket-order-id" id="ticketOrderId">#JT-00000</span>
   </div>
   <div class="ticket-body">
    <div class="ticket-row"><span>Cliente:</span> <strong id="ticketCliente"></strong></div>
    <div class="ticket-row"><span>Documento:</span> <strong id="ticketDocumento"></strong></div>
    <div class="ticket-row"><span>Comprobante:</span> <strong id="ticketComprobante"></strong></div>
    <div class="ticket-row"><span>Método de Pago:</span> <strong id="ticketMetodoPago"></strong></div>
    <div class="ticket-row"><span>Entrega:</span> <strong id="ticketEntrega"></strong></div>
    <div class="ticket-row"><span>Fecha y Hora:</span> <strong id="ticketFecha"></strong></div>
    <div class="ticket-divider"></div>
    <div class="ticket-items" id="ticketItems"></div>
    <div class="ticket-divider"></div>
    <div class="ticket-row total-ticket"><span>Total Pagado:</span> <strong id="ticketTotal"></strong></div>
   </div>
  </div>

  <div class="success-actions">
   <button class="btn-whatsapp-recibo" onclick="enviarReciboWhatsApp()">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
     <use href="img/icons.svg#icon-whatsapp"/>
    </svg>
    Enviar Comprobante a WhatsApp
   </button>
   <button class="btn-ver-mis-pedidos" onclick="cerrarModalExito(); abrirHistorialPedidos();">
    📦 Ver en Mis Pedidos
   </button>
   <button class="btn-seguir-comprando" onclick="cerrarModalExito()">Seguir Comprando</button>
  </div>
 </div>
</div>