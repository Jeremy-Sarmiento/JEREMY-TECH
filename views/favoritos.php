<?php
/**
 * VISTA: Mis favoritos (Jeremy Tech MVC).
 * Datos de entrada: $productos, $flash.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="cart-page cuenta-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-heart"/></svg>Mis Favoritos</h1>
  <p>Guarda los productos que te interesan y vuelve a ellos cuando quieras.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <template id="favoritosVacioTpl">
  <div class="orders-empty favoritos-vacio">
   <div class="empty-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-heart"/></svg></div>
   <h4>Aún no tienes favoritos</h4>
   <p>Toca el corazón de un producto para guardarlo aquí y encontrarlo más rápido.</p>
   <a class="btn-auth-gate" href="?ruta=catalogo">Explorar catálogo</a>
  </div>
 </template>

 <?php if (!$productos): ?>
 <div class="orders-empty favoritos-vacio">
  <div class="empty-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-heart"/></svg></div>
  <h4>Aún no tienes favoritos</h4>
  <p>Toca el corazón de un producto para guardarlo aquí y encontrarlo más rápido.</p>
  <a class="btn-auth-gate" href="?ruta=catalogo">Explorar catálogo</a>
 </div>
 <?php else: ?>
 <div class="favoritos-grid">
  <?php foreach ($productos as $p): ?>
   <?php
     $productoId = (int) ($p['id'] ?? 0);
     $activo = (int) ($p['activo'] ?? 0) === 1;
     $stock = (int) ($p['stock'] ?? 0);
     $slug = (string) ($p['slug'] ?? '');
     $urlFicha = '?ruta=producto&slug=' . urlencode($slug);
     $imagen = trim((string) ($p['imagen'] ?? ''));
     $imagenHTML = $imagen !== ''
         ? '<img src="' . $e(\Model\Producto::imagenOptimizada($imagen)) . '" alt="' . $e($p['nombre'] ?? 'Producto') . '" loading="lazy">'
         : '<span class="favorito-imagen-vacia" aria-hidden="true"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bag"/></svg></span>';
     $vendedor = trim((string) ($p['vendedor_usuario'] ?? ''));
     if ($vendedor === '') {
         $vendedor = trim((string) ($p['vendedor_nombre'] ?? ''));
     }
     $vendedor = $vendedor !== '' ? $vendedor : 'Jeremy Tech';
     $estado = !$activo ? 'No disponible' : ($stock > 0 ? 'Disponible' : 'Agotado');
     $estadoClase = !$activo ? 'no-disponible' : ($stock > 0 ? 'disponible' : 'agotado');
   ?>
   <article class="favorito-card" data-favorito-card="<?= $productoId ?>">
    <?php if ($activo): ?>
    <a class="favorito-imagen" href="<?= $urlFicha ?>" aria-label="Ver ficha de <?= $e($p['nombre'] ?? 'producto') ?>">
     <?= $imagenHTML ?>
    </a>
    <?php else: ?>
    <div class="favorito-imagen">
     <?= $imagenHTML ?>
    </div>
    <?php endif; ?>

    <div class="favorito-info">
     <span class="favorito-categoria"><?= $e($p['categoria'] ?? '') ?></span>
     <h3>
      <?php if ($activo): ?>
      <a href="<?= $urlFicha ?>"><?= $e($p['nombre'] ?? 'Producto') ?></a>
      <?php else: ?>
      <?= $e($p['nombre'] ?? 'Producto') ?>
      <?php endif; ?>
     </h3>
     <p class="favorito-vendedor">Vendido por <strong><?= $e($vendedor) ?></strong>
      <?php if ((int) ($p['vendedor_verificado'] ?? 0) === 1): ?>
      <span class="card-vendedor-check" title="Vendedor verificado">✔</span>
      <?php endif; ?>
     </p>
     <p class="favorito-precio">PEN <?= number_format((float) ($p['precio_pen'] ?? 0), 2) ?></p>
     <p class="favorito-estado favorito-estado-<?= $estadoClase ?>"><?= $e($estado) ?></p>

     <div class="favorito-acciones">
      <?php if ($activo): ?>
      <a class="btn-mini" href="<?= $urlFicha ?>">Ver ficha</a>
      <?php endif; ?>
      <form method="post" action="?ruta=favoritos/alternar" class="favorito-form">
       <?= \Security\Proteccion::campo() ?>
       <input type="hidden" name="producto_id" value="<?= $productoId ?>">
       <button type="submit" class="btn-mini btn-mini-peligro favorito-quitar"
               aria-label="Quitar <?= $e($p['nombre'] ?? 'producto') ?> de favoritos">
        <span class="favorito-icon" aria-hidden="true">♥</span> Quitar
       </button>
      </form>
     </div>
    </div>
   </article>
  <?php endforeach; ?>
 </div>
 <?php endif; ?>
</main>
