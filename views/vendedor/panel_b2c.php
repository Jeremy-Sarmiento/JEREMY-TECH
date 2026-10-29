<?php
/**
 * VISTA: Panel de vendedor — Persona (B2C).
 *
 * Vista simple para quien vende al consumidor final: publicaciones, stock y
 * ganancias. Sin datos de empresa porque el tipo B2C no los necesita.
 */
require __DIR__ . '/_comun.php';
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bag"/></svg>Panel de vendedor · Persona (B2C)</h1>
  <p>Vendes al consumidor final. Publica, controla el stock y revisa tus ganancias.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <?php require __DIR__ . '/_gestion.php'; ?>
 <?php require __DIR__ . '/_ganancias.php'; ?>
</main>
