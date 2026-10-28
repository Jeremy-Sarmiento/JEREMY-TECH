<?php
/**
 * VISTA: Acceso restringido a "Mis productos" (Jeremy Tech) — Fase 3.
 *
 * Se muestra con HTTP 403 cuando la cuenta autenticada no puede publicar:
 * ni `users.rol = 'vendedor'` ni `users.tipo = 'C2C'`. El controlador ya
 * cargó el header.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1>🔒 Mis productos</h1>
  <p>Esta sección está reservada a vendedores y revendedores C2C del marketplace.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <div class="orders-empty pv-vacio">
  <div class="empty-icon">🔐</div>
  <h4>Tu cuenta todavía no puede publicar</h4>
  <p>Para vender en Jeremy Tech necesitas una cuenta de vendedor o cambiar tu
     tipo de cliente a <strong>Revendedor (C2C)</strong> desde tu perfil.
     Si quieres empezar a vender, escríbenos y activamos tu perfil.</p>
  <div class="pv-sin-acceso-acciones">
   <a class="btn-auth-gate" href="?ruta=perfil">Cambiar a C2C en mi perfil</a>
   <a class="btn-mini" href="?ruta=catalogo">Volver al catálogo</a>
  </div>
 </div>
</main>
