<?php
/**
 * VISTA: Acceso restringido a "Mis productos" (Jeremy Tech) — Fase 3.
 *
 * Se muestra con HTTP 403 cuando la cuenta autenticada no tiene
 * `users.rol = 'vendedor'`. El controlador ya cargó el header. Desde aquí
 * se puede pedir el rol al administrador.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pendiente = \Model\SolicitudVendedor::pendienteDe((int) (\Model\Cuenta::idSesion() ?? 0));
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-lock"/></svg>Mis productos</h1>
  <p>Esta sección está reservada a cuentas con rol de vendedor.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <div class="orders-empty pv-vacio">
  <div class="empty-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-lock"/></svg></div>
  <h4>Tu cuenta todavía no puede publicar</h4>
  <?php if ($pendiente !== null): ?>
  <p>Tu solicitud de rol de vendedor está pendiente. Un administrador la revisará
     y, cuando la apruebe, podrás publicar sin volver a registrarte.</p>
  <?php else: ?>
  <p>Para vender en Jeremy Tech necesitas el rol de vendedor. Puedes solicitarlo
     ahora: un administrador revisará tu cuenta y conservarás todos tus datos.</p>
  <form method="post" action="?ruta=perfil/solicitar-vendedor" class="pv-sin-acceso-acciones">
   <?= \Security\Proteccion::campo() ?>
   <button type="submit" class="btn-auth-gate">Solicitar rol de vendedor</button>
   <a class="btn-mini" href="?ruta=catalogo">Volver al catálogo</a>
  </form>
  <?php endif; ?>
  <div class="pv-sin-acceso-acciones">
   <a class="btn-mini" href="?ruta=perfil">Ver mi perfil</a>
  </div>
 </div>
</main>
