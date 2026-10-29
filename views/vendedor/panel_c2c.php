<?php
/**
 * VISTA: Panel de vendedor — Segunda mano (C2C).
 *
 * Vista para quien revende artículos usados entre particulares: plataforma
 * de reventa, publicaciones, historial de stock y reputación.
 */
require __DIR__ . '/_comun.php';
$plataforma = trim((string) ($vendedor['plataforma_reventa'] ?? ''));
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-star"/></svg>Panel de vendedor · Segunda mano (C2C)</h1>
  <p>Revendes artículos entre particulares. Cuida la foto, el estado y tu reputación.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <section class="pv-panel" id="ficha-reventa" aria-labelledby="pv-reventa-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-reventa-titulo"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-star"/></svg>Mi perfil de revendedor</h2>
    <p class="pv-panel-nota">
     Las ventas C2C se pagan con la comisión general de la plataforma. Describe el
     estado real de cada artículo: es lo que sostiene tu reputación.
    </p>
   </div>
   <a class="btn-mini" href="?ruta=perfil">Editar en mi perfil</a>
  </div>
  <div class="pv-ganancias">
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $e($plataforma !== '' ? $plataforma : 'No registrada') ?></span>
    <span class="pv-ganancia-etiqueta">Dónde revendes</span>
   </div>
   <div class="pv-ganancia pv-ganancia-neto">
    <span class="pv-ganancia-valor"><?= number_format((float) ($reputacion['promedio'] ?? 0), 1) ?></span>
    <span class="pv-ganancia-etiqueta">Calificación promedio</span>
   </div>
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= (int) ($reputacion['total'] ?? 0) ?></span>
    <span class="pv-ganancia-etiqueta">Calificaciones recibidas</span>
   </div>
  </div>
 </section>

 <?php require __DIR__ . '/_gestion.php'; ?>
 <?php require __DIR__ . '/_movimientos.php'; ?>
 <?php require __DIR__ . '/_reputacion.php'; ?>
</main>
