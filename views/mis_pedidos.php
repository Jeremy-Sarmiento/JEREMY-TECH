<?php
/**
 * VISTA: Mis pedidos (Jeremy Tech MVC) — Fase 2.
 *
 * Datos de entrada (src/Controller/PedidoController::index()):
 *   $pedidos, $resumen, $resenas, $comprados, $avisoResena, $filtros, $estado,
 *   $clienteId, $calificacionesVendedor, $avisoCalificacionVendedor.
 *
 * Solo se muestran pedidos del cliente autenticado. Las reseñas y las
 * calificaciones al vendedor se envían por POST normal; `cuenta.js` las
 * convierte en llamadas JSON.
 */
use Model\Resena;

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $monto): string => 'S/ ' . number_format($monto, 2);
$fechaFmt = static function (?string $fecha): string {
    $fecha = trim((string) $fecha);
    if ($fecha === '') {
        return '';
    }
    $ts = strtotime($fecha);
    return $ts === false ? $fecha : date('d/m/Y H:i', $ts);
};

$estadoActual = (string) ($estado ?? '');
$porEstado = (array) ($resumen['porEstado'] ?? []);
$totalPedidos = (int) ($resumen['total'] ?? 0);
$filtrosPedido = ['Todos' => ''] + array_combine($filtros, $filtros);

/** Clases de color por estado (las de carrito.css + las nuevas). */
$claseEstado = static function (string $estado): string {
    $clave = strtolower($estado);
    return 'order-status status-' . preg_replace('/[^a-z0-9_-]/', '', $clave);
};
?>
<main class="cart-page cuenta-page mis-pedidos-page">
 <div class="cart-page-header">
  <h1>📦 Mis Pedidos</h1>
  <p>Historial, seguimiento y opiniones de tus compras en Jeremy Tech.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($avisoResena['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($avisoResena['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($avisoResena['mensaje']) ?>
 </div>
 <?php endif; ?>

 <?php if (($avisoCalificacionVendedor['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($avisoCalificacionVendedor['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($avisoCalificacionVendedor['mensaje']) ?>
 </div>
 <?php endif; ?>

 <?php if ($totalPedidos > 0): ?>
 <div class="pedidos-resumen">
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true">🧾</span>
   <span class="pedidos-stat-valor"><?= $totalPedidos ?></span>
   <span class="pedidos-stat-etiqueta">Pedido<?= $totalPedidos === 1 ? '' : 's' ?> en total</span>
  </div>
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true">🚚</span>
   <span class="pedidos-stat-valor"><?= (int) ($resumen['enCurso'] ?? 0) ?></span>
   <span class="pedidos-stat-etiqueta">En curso</span>
  </div>
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true">✅</span>
   <span class="pedidos-stat-valor"><?= (int) ($resumen['entregados'] ?? 0) ?></span>
   <span class="pedidos-stat-etiqueta">Entregado<?= (int) ($resumen['entregados'] ?? 0) === 1 ? '' : 's' ?></span>
  </div>
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true">💳</span>
   <span class="pedidos-stat-valor"><?= $fmt((float) ($resumen['gastado'] ?? 0)) ?></span>
   <span class="pedidos-stat-etiqueta">Gastado (sin cancelados)</span>
  </div>
 </div>

 <nav class="pedidos-filtros" aria-label="Filtrar pedidos por estado">
  <?php foreach ($filtrosPedido as $etiqueta => $valor): ?>
   <?php
     $cantidad = $valor === ''
         ? $totalPedidos
         : (int) ($porEstado[$valor] ?? 0);
     $activo = $estadoActual === $valor;
     $url = $valor === '' ? '?ruta=mis-pedidos' : '?ruta=mis-pedidos&estado=' . urlencode($valor);
   ?>
   <a class="pedidos-filtro<?= $activo ? ' activo' : '' ?>"
      href="<?= $e($url) ?>"
      <?= $activo ? 'aria-current="page"' : '' ?>>
    <?= $e($etiqueta) ?>
    <span class="pedidos-filtro-cuenta"><?= $cantidad ?></span>
   </a>
  <?php endforeach; ?>
 </nav>
 <?php endif; ?>

 <?php if (!$pedidos): ?>
 <div class="orders-empty">
  <div class="empty-icon"><?= $estadoActual === '' ? '📦' : '🔎' ?></div>
  <?php if ($estadoActual === ''): ?>
  <h4>Aún no tienes pedidos</h4>
  <p>Cuando realices tu primera compra, aparecerá aquí el detalle y el seguimiento.</p>
  <a class="btn-auth-gate" href="?ruta=catalogo">Explorar catálogo</a>
  <?php else: ?>
  <h4>No hay pedidos en estado «<?= $e($estadoActual) ?>»</h4>
  <p>Prueba con otro filtro para ver el resto de tu historial.</p>
  <a class="btn-auth-gate" href="?ruta=mis-pedidos">Ver todos los pedidos</a>
  <?php endif; ?>
 </div>
 <?php else: ?>
 <div id="opiniones"></div>
 <div class="pedidos-lista">
  <?php foreach ($pedidos as $pedido): ?>
   <?php
     $pedidoId = (int) $pedido['id'];
     $estadoPedido = (string) ($pedido['estado'] ?? 'Pendiente');
     $detalle = is_array($pedido['detalle'] ?? null) ? $pedido['detalle'] : [];
     $pago = is_array($pedido['pago'] ?? null) ? $pedido['pago'] : null;
     $envioZona = trim((string) ($pedido['envio_zona'] ?? ''));
     $envioCosto = (float) ($pedido['envio_costo'] ?? 0);
     $descuento = (float) ($pedido['descuento_aplicado'] ?? 0);
     $subtotal = (float) ($pedido['subtotal'] ?? 0);
     $igv = (float) ($pedido['igv_monto'] ?? 0);
     $puedeResenarPedido = in_array($estadoPedido, ['Pagado', 'Enviado', 'Entregado'], true);
     $pasos = \Model\PedidoEvento::lineaTiempo($pedido);
   ?>
   <article class="order-card pedido-card" id="pedido-<?= $pedidoId ?>">
    <header class="pedido-card-header">
     <div>
      <span class="order-id">#JT-<?= str_pad((string) $pedidoId, 5, '0', STR_PAD_LEFT) ?></span>
      <span class="pedido-card-fecha"><?= $e($fechaFmt((string) ($pedido['creado_en'] ?? ''))) ?></span>
     </div>
     <span class="<?= $e($claseEstado($estadoPedido)) ?>"><?= $e($estadoPedido) ?></span>
    </header>

    <div class="pedido-card-meta">
     <span><strong>Total</strong> <?= $fmt((float) ($pedido['total_pen'] ?? 0)) ?></span>
     <span><strong>Envío</strong> <?= $envioZona !== '' ? $e($envioZona) : 'Recojo en tienda' ?> · <?= $envioCosto > 0 ? $fmt($envioCosto) : 'Gratis' ?></span>
     <?php if ($pago !== null): ?>
     <span><strong>Pago</strong> <?= $e(ucfirst((string) ($pago['metodo'] ?? ''))) ?> · <?= $e((string) ($pago['estado'] ?? '')) ?></span>
     <?php if ((string) ($pago['transaccion_id'] ?? '') !== ''): ?>
     <span><strong>Ref.</strong> <?= $e((string) $pago['transaccion_id']) ?></span>
     <?php endif; ?>
     <?php endif; ?>
     <?php if ((string) ($pedido['direccion_entrega'] ?? '') !== ''): ?>
     <span class="pedido-card-direccion"><strong>Entrega</strong> <?= $e((string) $pedido['direccion_entrega']) ?></span>
     <?php endif; ?>
     <?php if ((string) ($pedido['guia_numero'] ?? '') !== ''): ?>
     <span><strong>Guía</strong> <?= $e((string) $pedido['guia_numero']) ?><?= (string) ($pedido['courier'] ?? '') !== '' ? ' · ' . $e((string) $pedido['courier']) : '' ?></span>
     <?php endif; ?>
    </div>

    <div class="order-timeline" aria-label="Seguimiento del pedido #JT-<?= str_pad((string) $pedidoId, 5, '0', STR_PAD_LEFT) ?>">
     <?php foreach ($pasos as $paso): ?>
     <div class="timeline-step <?= $e((string) $paso['estado']) ?> step-<?= $e((string) $paso['clave']) ?>">
      <span class="timeline-dot" aria-hidden="true"><?= $paso['estado'] === 'completed' ? '✓' : ($paso['estado'] === 'current' ? '●' : '○') ?></span>
      <div class="timeline-content">
       <span class="timeline-label"><?= $e((string) $paso['etiqueta']) ?></span>
       <?php if ((string) ($paso['detalle'] ?? '') !== ''): ?>
       <span class="timeline-detail"><?= $e((string) $paso['detalle']) ?></span>
       <?php endif; ?>
       <?php if ((string) ($paso['fecha'] ?? '') !== ''): ?>
       <span class="timeline-date"><?= $e($fechaFmt((string) $paso['fecha'])) ?></span>
       <?php endif; ?>
      </div>
     </div>
     <?php endforeach; ?>
    </div>

    <ul class="pedido-items">
     <?php foreach ($detalle as $item): ?>
      <?php
        $productoId = (int) ($item['producto_id'] ?? 0);
        $cantidad = (int) ($item['cantidad'] ?? 0);
        $precioUnitario = (float) ($item['precio_unitario_pen'] ?? 0);
        $miResena = $resenas[$productoId] ?? null;
        $opinable = $puedeResenarPedido && $productoId > 0 && isset($comprados[$productoId]);
      ?>
      <li class="pedido-item">
       <div class="pedido-item-linea">
        <span class="pedido-item-nombre">
         <?= $e((string) ($item['producto_nombre'] ?? 'Producto')) ?>
         <?php if ((string) ($item['variante_nombre'] ?? '') !== ''): ?>
         <small><?= $e((string) $item['variante_nombre']) ?></small>
         <?php endif; ?>
        </span>
        <span class="pedido-item-cantidad">×<?= $cantidad ?></span>
        <span class="pedido-item-precio"><?= $fmt($precioUnitario * $cantidad) ?></span>
       </div>

       <?php if ($opinable): ?>
       <details class="pedido-item-resena"<?= $miResena !== null ? ' open' : '' ?>>
        <summary>
         <?= $miResena !== null
             ? '✏️ Tu opinión: ' . Resena::estrellas((float) $miResena['calificacion'])
             : '⭐ Opinar sobre este producto' ?>
        </summary>
        <?php
          $resenaProductoId = $productoId;
          $resenaProductoNombre = (string) ($item['producto_nombre'] ?? 'este producto');
          $resenaActual = $miResena;
          $resenaSlug = '';
          $resenaSufijo = 'p' . $pedidoId . '-' . $productoId;
          require BASE_PATH . '/views/partials/resena_form.php';
        ?>
       </details>
       <?php elseif ($miResena !== null): ?>
       <p class="pedido-item-opinion">
        Tu opinión: <span class="resena-estrellas-texto"><?= $e(Resena::estrellas((float) $miResena['calificacion'])) ?></span>
        <?php if ((string) ($miResena['estado'] ?? 'visible') === 'oculta'): ?>
        <span class="resena-estado-oculta">· Oculta por moderación</span>
        <?php endif; ?>
       </p>
       <?php endif; ?>
      </li>
     <?php endforeach; ?>
    </ul>

    <?php
      // Vendedores distintos del pedido (una sola calificación por vendedor,
      // aunque el cliente le haya comprado varias líneas). Se excluye al
      // propio comprador y a las líneas de la plataforma (vendedor_id NULL).
      $vendedoresPedido = [];
      foreach ($detalle as $item) {
          $vendedorId = (int) ($item['vendedor_id'] ?? 0);
          if ($vendedorId <= 0 || $vendedorId === (int) ($clienteId ?? 0)) {
              continue;
          }
          if (!isset($vendedoresPedido[$vendedorId])) {
              $nombreVendedor = trim((string) ($item['vendedor_nombre'] ?? ''));
              $vendedoresPedido[$vendedorId] = $nombreVendedor !== ''
                  ? $nombreVendedor
                  : 'Vendedor #' . $vendedorId;
          }
      }
    ?>
    <?php if ($puedeResenarPedido && $vendedoresPedido !== []): ?>
    <div class="pedido-vendedores">
     <h3 class="pedido-vendedores-titulo">🤝 Califica a tus vendedores</h3>
     <?php foreach ($vendedoresPedido as $vendedorId => $vendedorNombre): ?>
      <?php
        $miCalificacionVendedor = $calificacionesVendedor[$vendedorId] ?? null;
        $califVendedorId = $vendedorId;
        $califVendedorNombre = $vendedorNombre;
        $califVendedorActual = $miCalificacionVendedor;
        $califVendedorSufijo = 'v' . $pedidoId . '-' . $vendedorId;
      ?>
      <details class="pedido-vendedor-calificacion"<?= $miCalificacionVendedor !== null ? ' open' : '' ?>>
       <summary>
        <?= $miCalificacionVendedor !== null
            ? '✏️ Tu calificación de ' . $e($vendedorNombre) . ': ' . Resena::estrellas((float) $miCalificacionVendedor['calificacion'])
            : '⭐ Calificar a ' . $e($vendedorNombre) ?>
       </summary>
       <?php require BASE_PATH . '/views/partials/calificacion_vendedor_form.php'; ?>
      </details>
     <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($subtotal > 0): ?>
    <div class="pedido-desglose">
     <span>Subtotal <small>(incluye IGV <?= $fmt($igv) ?>)</small></span>
     <span><?= $fmt($subtotal) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($descuento > 0): ?>
    <div class="pedido-desglose pedido-desglose-ok">
     <span>Descuento</span>
     <span>− <?= $fmt($descuento) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($envioZona !== ''): ?>
    <div class="pedido-desglose">
     <span>Envío <small>(<?= $e($envioZona) ?>)</small></span>
     <span><?= $envioCosto > 0 ? $fmt($envioCosto) : 'Gratis' ?></span>
    </div>
    <?php endif; ?>
    <div class="pedido-desglose pedido-desglose-total">
     <span>Total</span>
     <span><?= $fmt((float) ($pedido['total_pen'] ?? 0)) ?></span>
    </div>

    <div class="pedido-card-acciones">
     <button type="button" class="btn-order-whatsapp" onclick="contactarOrdenWhatsApp(<?= $pedidoId ?>)">
      💬 Consultar por WhatsApp
     </button>
     <a class="btn-mini" href="?ruta=soporte&amp;pedido=<?= $pedidoId ?>#nuevo">🎧 Necesito ayuda con este pedido</a>
     <a class="btn-mini" href="?ruta=carrito#pedido-<?= $pedidoId ?>">Ver en el carrito</a>
    </div>
   </article>
  <?php endforeach; ?>
 </div>
 <?php endif; ?>
</main>
