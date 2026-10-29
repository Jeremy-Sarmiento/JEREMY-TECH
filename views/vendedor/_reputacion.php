<?php
/**
 * Parcial del panel de vendedor: calificaciones de compradores.
 * Requiere `views/vendedor/_comun.php` y los datos del controlador.
 */
?>
 <section class="pv-panel" id="mi-reputacion" aria-labelledby="pv-reputacion-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-reputacion-titulo"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-star"/></svg>Mi reputación</h2>
    <p class="pv-panel-nota">
     Promedio de las calificaciones que te dejaron los compradores después de pagar.
     Es independiente de las reseñas de cada producto.
    </p>
   </div>
  </div>
  <?php if ((int) ($reputacion['total'] ?? 0) === 0): ?>
  <p class="pv-vacio-mini">Aún no tienes calificaciones como vendedor. Cuando un cliente te califique, verás aquí su promedio y sus comentarios.</p>
  <?php else: ?>
  <div class="pv-reputacion">
   <span class="pv-reputacion-numero"><?= number_format((float) ($reputacion['promedio'] ?? 0), 1) ?></span>
   <span class="pv-estrellas" aria-hidden="true"><?= $e(\Model\Resena::estrellas((float) ($reputacion['promedio'] ?? 0))) ?></span>
   <span class="pv-reputacion-total">
    <?= (int) ($reputacion['total'] ?? 0) ?> calificación<?= (int) ($reputacion['total'] ?? 0) === 1 ? '' : 'es' ?>
   </span>
  </div>
  <?php if (!empty($calificaciones)): ?>
  <ul class="pv-calificaciones">
   <?php foreach ($calificaciones as $calificacion): ?>
   <li class="pv-calificacion">
    <div class="pv-calificacion-cabecera">
     <span class="pv-estrellas" aria-hidden="true"><?= $e(\Model\Resena::estrellas((float) ($calificacion['calificacion'] ?? 0))) ?></span>
     <span class="pv-sub">
      <?= $e($calificacion['cliente_nombre'] ?? 'Cliente') ?> · <?= $e($fecha($calificacion['creado_en'] ?? null)) ?>
     </span>
    </div>
    <?php if (trim((string) ($calificacion['comentario'] ?? '')) !== ''): ?>
    <p><?= $e($calificacion['comentario']) ?></p>
    <?php endif; ?>
   </li>
   <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php endif; ?>
 </section>
