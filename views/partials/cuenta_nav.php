<?php
/**
 * Navegación compartida de la cuenta del cliente.
 * Espera `$rutaVista` (perfil, mis-pedidos, favoritos o notificaciones).
 */
$rutaCuentaActual = $rutaVista ?? '';
$enlacesCuenta = [
    'perfil' => ['👤 Mi perfil', '?ruta=perfil'],
    'mis-pedidos' => ['📦 Mis pedidos', '?ruta=mis-pedidos'],
    'soporte' => ['🎧 Soporte', '?ruta=soporte'],
    'favoritos' => ['❤️ Mis favoritos', '?ruta=favoritos'],
    'notificaciones' => ['📨 Notificaciones', '?ruta=notificaciones'],
];

// (Fase 3) El panel de publicaciones está disponible para vendedores y
// para los clientes con tipo C2C.
if (\Model\Cuenta::esVendedor()) {
    $enlacesCuenta = array_slice($enlacesCuenta, 0, 2, true)
        + ['mis-productos' => ['🏪 Mis productos', '?ruta=mis-productos']]
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
 <a class="cuenta-nav-link" href="?ruta=direcciones">📍 Direcciones</a>
</nav>
