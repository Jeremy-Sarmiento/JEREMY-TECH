<?php
/**
 * VISTA: Mis tickets de soporte (Jeremy Tech).
 *
 * Datos de entrada (src/Controller/SoporteController::index()):
 *   $flash, $tickets, $resumen, $pedidos, $categorias, $pedidoPre.
 */
use Model\Pedido;
use Model\SoporteTicket;

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};
$fmt = static fn (float $monto): string => 'S/ ' . number_format($monto, 2);

$flash = $flash ?? ['tipo' => '', 'mensaje' => ''];
$tickets = $tickets ?? [];
$resumen = $resumen ?? ['total' => 0, 'abiertos' => 0, 'en_proceso' => 0, 'resueltos' => 0, 'cerrados' => 0];
$pedidos = $pedidos ?? [];
$categorias = $categorias ?? SoporteTicket::CATEGORIAS;
$pedidoPre = $pedidoPre ?? null;
$pedidoPreId = $pedidoPre !== null ? (int) $pedidoPre['id'] : 0;

$claseEstado = static function (string $estado): string {
    $clave = preg_replace('/[^a-z0-9_-]/', '', strtolower($estado));
    return 'soporte-estado soporte-estado-' . $clave;
};
?>
<main class="cart-page cuenta-page soporte-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-chat"/></svg>Soporte postventa</h1>
  <p>Cuéntanos qué pasó con tu pedido, pago, envío o producto. Te respondemos aquí y en tus notificaciones.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <div class="pedidos-resumen soporte-resumen">
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true"><svg class="inline-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-chat"/></svg></span>
   <span class="pedidos-stat-valor"><?= (int) $resumen['total'] ?></span>
   <span class="pedidos-stat-etiqueta">Ticket<?= (int) $resumen['total'] === 1 ? '' : 's' ?></span>
  </div>
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true"><svg class="inline-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bell"/></svg></span>
   <span class="pedidos-stat-valor"><?= (int) $resumen['abiertos'] ?></span>
   <span class="pedidos-stat-etiqueta">Abierto<?= (int) $resumen['abiertos'] === 1 ? '' : 's' ?></span>
  </div>
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true"><svg class="inline-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-settings"/></svg></span>
   <span class="pedidos-stat-valor"><?= (int) $resumen['en_proceso'] ?></span>
   <span class="pedidos-stat-etiqueta">En proceso</span>
  </div>
  <div class="pedidos-stat">
   <span class="pedidos-stat-icon" aria-hidden="true"><svg class="inline-icon" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-check"/></svg></span>
   <span class="pedidos-stat-valor"><?= (int) $resumen['resueltos'] ?></span>
   <span class="pedidos-stat-etiqueta">Resuelto<?= (int) $resumen['resueltos'] === 1 ? '' : 's' ?></span>
  </div>
 </div>

 <div class="soporte-grid">
  <section class="cart-page-section soporte-form-card" id="nuevo">
   <div class="cart-page-section-head">
    <h2>Nuevo ticket</h2>
    <p>Mientras más detalle nos des, más rápido podemos ayudarte.</p>
   </div>

   <form method="post" action="?ruta=soporte/crear" class="perfil-form soporte-form">
    <?= \Security\Proteccion::campo() ?>

    <?php if ($pedidoPre !== null): ?>
    <input type="hidden" name="pedido_id" value="<?= $pedidoPreId ?>">
    <div class="soporte-pedido-fijo">
     <strong>Pedido <?= $e(Pedido::codigo($pedidoPreId)) ?></strong>
     <span><?= $e((string) ($pedidoPre['estado'] ?? '')) ?> · <?= $fmt((float) ($pedidoPre['total_pen'] ?? 0)) ?></span>
     <a href="?ruta=soporte#nuevo">Quitar</a>
    </div>
    <?php elseif ($pedidos !== []): ?>
    <div class="form-group">
     <label for="soportePedido">¿Sobre qué pedido es? <span>(opcional)</span></label>
     <select id="soportePedido" name="pedido_id">
      <option value="0">No está relacionado con un pedido</option>
      <?php foreach ($pedidos as $pedido): ?>
      <option value="<?= (int) $pedido['id'] ?>">
       <?= $e(Pedido::codigo((int) $pedido['id'])) ?> · <?= $e((string) $pedido['estado']) ?> · <?= $fmt((float) $pedido['total_pen']) ?>
      </option>
      <?php endforeach; ?>
     </select>
    </div>
    <?php endif; ?>

    <div class="form-row">
     <div class="form-group">
      <label for="soporteCategoria">Categoría *</label>
      <select id="soporteCategoria" name="categoria" required>
       <?php foreach ($categorias as $categoria): ?>
       <option value="<?= $e($categoria) ?>"<?= $categoria === 'Pedido' && $pedidoPre !== null ? ' selected' : '' ?>><?= $e($categoria) ?></option>
       <?php endforeach; ?>
      </select>
     </div>
     <div class="form-group">
      <label for="soporteAsunto">Asunto *</label>
      <input type="text" id="soporteAsunto" name="asunto" minlength="<?= SoporteTicket::ASUNTO_MIN ?>"
             maxlength="<?= SoporteTicket::ASUNTO_MAX ?>" required
             placeholder="Ej.: Mi pedido llegó con un accesorio faltante">
     </div>
    </div>

    <div class="form-group">
     <label for="soporteDescripcion">Descripción *</label>
     <textarea id="soporteDescripcion" name="descripcion" rows="6"
               minlength="<?= SoporteTicket::DESCRIPCION_MIN ?>"
               maxlength="<?= SoporteTicket::DESCRIPCION_MAX ?>" required
               placeholder="Cuéntanos qué ocurrió, cuándo y qué necesitas. Incluye números de guía o referencias si los tienes."></textarea>
    </div>

    <div class="soporte-form-pie">
     <button type="submit" class="btn-auth">Abrir ticket</button>
     <small>También puedes escribirnos por WhatsApp desde la ficha de tu pedido.</small>
    </div>
   </form>
  </section>

  <section class="cart-page-section" id="tickets">
   <div class="cart-page-section-head">
    <h2>Tus tickets</h2>
    <p>El equipo responde en horario de atención. Recibirás un aviso en tu bandeja.</p>
   </div>

   <?php if ($tickets === []): ?>
   <div class="orders-empty">
    <div class="empty-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-chat"/></svg></div>
    <h4>Aún no tienes tickets</h4>
    <p>Si algo salió mal con una compra, abre tu primer ticket con el formulario.</p>
    <a class="btn-auth-gate" href="#nuevo">Abrir un ticket</a>
   </div>
   <?php else: ?>
   <div class="soporte-lista">
    <?php foreach ($tickets as $ticket): ?>
    <?php
      $ticketId = (int) $ticket['id'];
      $estado = (string) ($ticket['estado'] ?? SoporteTicket::ABIERTO);
      $pedidoId = (int) ($ticket['pedido_id'] ?? 0);
      $respuesta = trim((string) ($ticket['respuesta'] ?? ''));
    ?>
    <article class="soporte-card" id="ticket-<?= $ticketId ?>">
     <header class="soporte-card-cabecera">
      <div>
       <strong class="soporte-codigo"><?= $e(SoporteTicket::codigo($ticketId)) ?></strong>
       <span class="soporte-categoria"><?= $e((string) ($ticket['categoria'] ?? 'General')) ?></span>
       <?php if ($pedidoId > 0): ?>
       <a class="soporte-pedido" href="?ruta=mis-pedidos#pedido-<?= $pedidoId ?>"><?= $e(Pedido::codigo($pedidoId)) ?></a>
       <?php endif; ?>
      </div>
      <span class="<?= $e($claseEstado($estado)) ?>"><?= $e(SoporteTicket::etiquetaEstado($estado)) ?></span>
     </header>

     <h3 class="soporte-asunto"><?= $e((string) ($ticket['asunto'] ?? '')) ?></h3>
     <p class="soporte-fecha">Creado el <?= $e($fecha((string) ($ticket['creado_en'] ?? ''))) ?></p>

     <details class="soporte-detalle"<?= $respuesta === '' ? ' open' : '' ?>>
      <summary>Ver descripción</summary>
      <p><?= nl2br($e((string) ($ticket['descripcion'] ?? ''))) ?></p>
     </details>

     <?php if ($respuesta !== ''): ?>
     <div class="soporte-respuesta">
      <div class="soporte-respuesta-cabecera">
       <strong>Respuesta del equipo</strong>
       <span><?= $e($fecha((string) ($ticket['respondido_en'] ?? ''))) ?></span>
      </div>
      <p><?= nl2br($e($respuesta)) ?></p>
      <?php if ((string) ($ticket['respondido_por'] ?? '') !== ''): ?>
      <small>Atendido por <?= $e((string) $ticket['respondido_por']) ?></small>
      <?php endif; ?>
     </div>
     <?php else: ?>
     <p class="soporte-espera">⏳ Todavía no hay respuesta. Te avisaremos en cuanto el equipo revise tu caso.</p>
     <?php endif; ?>
    </article>
    <?php endforeach; ?>
   </div>
   <?php endif; ?>
  </section>
 </div>
</main>
