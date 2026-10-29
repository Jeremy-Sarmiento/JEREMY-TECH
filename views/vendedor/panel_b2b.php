<?php
/**
 * VISTA: Panel de vendedor — Empresa (B2B).
 *
 * Vista para cuentas de empresa: ficha fiscal (empresa y RUC) a la vista,
 * publicaciones, ganancias y quién compró (pedidos corporativos).
 */
require __DIR__ . '/_comun.php';
$empresa = trim((string) ($vendedor['empresa'] ?? ''));
$ruc = trim((string) ($vendedor['ruc'] ?? ''));
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-briefcase"/></svg>Panel de vendedor · Empresa (B2B)</h1>
  <p>Vendes a otras empresas. Tu catálogo sale con los datos fiscales de la cuenta.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <section class="pv-panel" id="ficha-empresa" aria-labelledby="pv-empresa-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-empresa-titulo"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-briefcase"/></svg>Ficha de empresa</h2>
    <p class="pv-panel-nota">
     Estos datos identifican tus ventas ante tus clientes corporativos. Si falta
     el RUC, complétalo en tu perfil: es obligatorio para facturar como empresa.
    </p>
   </div>
   <a class="btn-mini" href="?ruta=perfil">Editar en mi perfil</a>
  </div>
  <div class="pv-ganancias">
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $e($empresa !== '' ? $empresa : 'Sin razón social') ?></span>
    <span class="pv-ganancia-etiqueta">Empresa / razón social</span>
   </div>
   <div class="pv-ganancia<?= $ruc === '' ? ' pv-ganancia-comision' : '' ?>">
    <span class="pv-ganancia-valor"><?= $e($ruc !== '' ? $ruc : 'Falta el RUC') ?></span>
    <span class="pv-ganancia-etiqueta">RUC</span>
   </div>
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= (int) ($vendedor['verificado'] ?? 0) === 1 ? 'Verificada' : 'En revisión' ?></span>
    <span class="pv-ganancia-etiqueta">Cuenta</span>
   </div>
  </div>
 </section>

 <?php require __DIR__ . '/_gestion.php'; ?>
 <?php require __DIR__ . '/_ganancias.php'; ?>
 <?php require __DIR__ . '/_compradores.php'; ?>
</main>
