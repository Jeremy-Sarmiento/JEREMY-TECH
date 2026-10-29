<?php
/**
 * Parcial del panel de vendedor: últimas líneas vendidas con su estado de pago.
 * Requiere `views/vendedor/_comun.php` y los datos del controlador.
 */
?>
 <section class="pv-panel" id="mis-compradores" aria-labelledby="pv-compradores-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-compradores-titulo"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-cart"/></svg>Quién me compró</h2>
    <p class="pv-panel-nota">
     Solo ves tus ventas. Si un pedido mezcla productos de varios vendedores, aquí
     aparece únicamente la parte que te corresponde. Se muestran las últimas 50
     líneas, incluidos los pedidos pendientes o cancelados con su estado.
    </p>
   </div>
   <?php
     $pedidosComprados = count(array_unique(array_map(
         static fn (array $compra): int => (int) ($compra['pedido_id'] ?? 0),
         $compradores ?? []
     )));
   ?>
   <span class="pv-panel-contador">
    <?= $pedidosComprados ?> pedido<?= $pedidosComprados === 1 ? '' : 's' ?>
   </span>
  </div>
  <?php if (empty($compradores)): ?>
  <p class="pv-vacio-mini">Todavía nadie te compró. Cuando se confirme una venta, verás aquí al comprador y el estado del pago.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Fecha</th>
      <th>Comprador</th>
      <th>Producto</th>
      <th>Cant.</th>
      <th>Subtotal</th>
      <th>Pedido</th>
      <th>Pago</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($compradores as $compra): ?>
      <?php
        $estadoPago = trim((string) ($compra['pago_estado'] ?? ''));
        if ($estadoPago === '') {
            $estadoPago = trim((string) ($compra['pedido_estado'] ?? ''));
        }
        $nombreComprador = trim(
            (string) ($compra['comprador_nombre'] ?? '') . ' ' . (string) ($compra['comprador_apellidos'] ?? '')
        );
      ?>
      <tr>
       <td><?= $e($fecha($compra['creado_en'] ?? null)) ?></td>
       <td>
        <strong><?= $e($nombreComprador !== '' ? $nombreComprador : 'Cliente') ?></strong>
        <?php if ((string) ($compra['comprador_email'] ?? '') !== ''): ?>
        <span class="pv-sub"><?= $e($compra['comprador_email']) ?></span>
        <?php endif; ?>
       </td>
       <td>
        <?= $e($compra['producto_nombre'] ?? 'Producto') ?>
        <?php if ((string) ($compra['variante_nombre'] ?? '') !== ''): ?>
        <span class="pv-sub">Variante: <?= $e($compra['variante_nombre']) ?></span>
        <?php endif; ?>
       </td>
       <td><?= (int) ($compra['cantidad'] ?? 0) ?></td>
       <td><?= $fmt((float) ($compra['subtotal'] ?? 0)) ?></td>
       <td>#JT-<?= str_pad((string) (int) ($compra['pedido_id'] ?? 0), 5, '0', STR_PAD_LEFT) ?></td>
       <td>
        <span class="pv-estado <?= $claseEstado($estadoPago) ?>"><?= $e($estadoPago !== '' ? $estadoPago : 'Sin pago') ?></span>
        <span class="pv-sub">Pedido: <?= $e((string) ($compra['pedido_estado'] ?? '—')) ?></span>
       </td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>
 </section>
