<?php
/**
 * VISTA: Mis notificaciones (Jeremy Tech MVC).
 * Datos de entrada: $notificaciones.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static function (string $valor): string {
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};
$iconos = [
    'pago_confirmado' => '✅',
    'pedido_enviado' => '🚚',
    'pedido_creado' => '🧾',
    'pedido_cancelado' => '✖',
    'pedido_reembolsado' => '↩',
    'reembolso' => '↩',
    'venta_recibida' => '💰',
    'soporte_respuesta' => '🎧',
];
?>
<main class="cart-page cuenta-page">
 <div class="cart-page-header">
  <h1>📨 Mis Notificaciones</h1>
  <p>Avisos sobre tus pagos, envíos y pedidos. Los correos se registran en modo demostración.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (!$notificaciones): ?>
 <div class="orders-empty">
  <div class="empty-icon">📭</div>
  <h4>No tienes notificaciones todavía</h4>
  <p>Cuando se confirme un pago o salga un pedido, el aviso aparecerá aquí.</p>
  <a class="btn-auth-gate" href="?ruta=carrito#historial">Ver mis pedidos</a>
 </div>
 <?php else: ?>
 <div class="notificaciones-list">
  <?php foreach ($notificaciones as $n): ?>
   <?php
     $tipo = (string) ($n['tipo'] ?? '');
     $icono = $iconos[$tipo] ?? '📨';
     $pedidoId = (int) ($n['pedido_id'] ?? 0);
     $enviado = (int) ($n['enviado'] ?? 0) === 1;
   ?>
   <article class="notificacion-card">
    <div class="notificacion-icon" aria-hidden="true"><?= $icono ?></div>
    <div class="notificacion-contenido">
     <div class="notificacion-cabecera">
      <h2><?= $e($n['asunto'] ?? 'Aviso') ?></h2>
      <time datetime="<?= $e($n['creado_en'] ?? '') ?>"><?= $e($fecha((string) ($n['creado_en'] ?? ''))) ?></time>
     </div>
     <p class="notificacion-cuerpo"><?= nl2br($e($n['cuerpo'] ?? '')) ?></p>
     <div class="notificacion-pie">
      <?php if ($pedidoId > 0): ?>
      <a class="btn-mini" href="?ruta=mis-pedidos#pedido-<?= $pedidoId ?>">Ver pedido #JT-<?= str_pad((string) $pedidoId, 5, '0', STR_PAD_LEFT) ?></a>
      <?php endif; ?>
      <span class="notificacion-estado"><?= $enviado ? 'Correo enviado' : 'Aviso interno · correo simulado' ?></span>
     </div>
    </div>
   </article>
  <?php endforeach; ?>
 </div>
 <?php endif; ?>
</main>
