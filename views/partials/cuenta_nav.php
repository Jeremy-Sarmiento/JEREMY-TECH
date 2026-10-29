<?php
/**
 * Navegación compartida de la cuenta del cliente.
 * Espera `$rutaVista` (perfil, mis-pedidos, favoritos o notificaciones).
 */
$rutaCuentaActual = $rutaVista ?? '';
$enlacesCuenta = [
    'perfil' => ['<svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-user"/></svg>Mi perfil', '?ruta=perfil'],
    'mis-pedidos' => ['<svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg>Mis pedidos', '?ruta=mis-pedidos'],
    'soporte' => ['<svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-chat"/></svg>Soporte', '?ruta=soporte'],
    'favoritos' => ['<svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-heart"/></svg>Mis favoritos', '?ruta=favoritos'],
    'notificaciones' => ['<svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bell"/></svg>Notificaciones', '?ruta=notificaciones'],
];

// El panel de publicaciones solo existe para cuentas con rol de vendedor.
if (\Model\Cuenta::esVendedor()) {
    $enlacesCuenta = array_slice($enlacesCuenta, 0, 2, true)
        + ['mis-productos' => ['<svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bag"/></svg>Mis productos', '?ruta=mis-productos']]
        + array_slice($enlacesCuenta, 2, null, true);
}
?>
<nav class="cuenta-nav" aria-label="Mi cuenta">
 <?php foreach ($enlacesCuenta as $clave => [$texto, $url]): ?>
  <?php $activo = $rutaCuentaActual === $clave; ?>
  <a class="cuenta-nav-link<?= $activo ? ' activo' : '' ?>"
     href="<?= $url ?>"
     <?= $activo ? 'aria-current="page"' : '' ?>><?= $texto ?></a>
 <?php endforeach; ?>
 <a class="cuenta-nav-link" href="?ruta=direcciones"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pin"/></svg>Direcciones</a>
</nav>
