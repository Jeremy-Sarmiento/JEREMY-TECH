<?php
/**
 * Parcial del panel de vendedor: historial de stock de las publicaciones.
 * Requiere `views/vendedor/_comun.php` y los datos del controlador.
 */
?>
 <section class="pv-panel" id="mi-stock" aria-labelledby="pv-stock-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-stock-titulo"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg>Historial de mi stock</h2>
    <p class="pv-panel-nota">
     Últimos 20 movimientos de tus publicaciones: ventas, reembolsos y ajustes
     manuales que entraron o salieron del inventario.
    </p>
   </div>
  </div>
  <?php if (empty($movimientos)): ?>
  <p class="pv-vacio-mini">Sin movimientos todavía. Aquí se registrarán las ventas, devoluciones y ajustes de stock de tus productos.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Fecha</th>
      <th>Producto</th>
      <th>Tipo</th>
      <th>Cantidad</th>
      <th>Motivo</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($movimientos as $movimiento): ?>
      <?php $esEntrada = (string) ($movimiento['tipo'] ?? '') === 'entrada'; ?>
      <tr>
       <td><?= $e($fechaHora($movimiento['creado_en'] ?? null)) ?></td>
       <td>
        <?= $e($movimiento['producto_nombre'] ?? 'Producto') ?>
        <?php if ((string) ($movimiento['codigo_sku'] ?? '') !== ''): ?>
        <span class="pv-sub">SKU <?= $e($movimiento['codigo_sku']) ?></span>
        <?php endif; ?>
        <?php if ((string) ($movimiento['nombre_variante'] ?? '') !== ''): ?>
        <span class="pv-sub">Variante: <?= $e($movimiento['nombre_variante']) ?></span>
        <?php endif; ?>
       </td>
       <td>
        <span class="pv-mov <?= $esEntrada ? 'pv-mov-entrada' : 'pv-mov-salida' ?>">
         <?= $esEntrada ? 'Entrada' : 'Salida' ?>
        </span>
       </td>
       <td class="<?= $esEntrada ? 'pv-cantidad-entrada' : 'pv-cantidad-salida' ?>">
        <?= $esEntrada ? '+' : '−' ?><?= (int) ($movimiento['cantidad'] ?? 0) ?>
       </td>
       <td><?= $e($movimiento['motivo'] ?? '') ?></td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>
 </section>
