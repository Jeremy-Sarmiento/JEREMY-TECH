<?php
/**
 * Parcial del panel de vendedor: resumen, filtros y tarjetas de publicaciones (CRUD).
 * Requiere `views/vendedor/_comun.php` y los datos del controlador.
 */
?>
 <div class="pv-resumen" aria-label="Resumen de mis productos">
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true"><svg class="inline-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg></span>
   <span class="pv-stat-valor"><?= (int) ($resumen['total'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Publicaciones</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true"><svg class="inline-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-check"/></svg></span>
   <span class="pv-stat-valor"><?= (int) ($resumen['activos'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Activas en tienda</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true"><svg class="inline-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-lock"/></svg></span>
   <span class="pv-stat-valor"><?= (int) ($resumen['ocultos'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Ocultas</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true"><svg class="inline-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bell"/></svg></span>
   <span class="pv-stat-valor"><?= (int) ($resumen['stock_bajo'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Con stock bajo</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true"><svg class="inline-icon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-cash"/></svg></span>
   <span class="pv-stat-valor"><?= $fmt((float) ($resumen['valor'] ?? 0)) ?></span>
   <span class="pv-stat-etiqueta">Valor del inventario</span>
  </div>
 </div>

 <?php
   $enlaceVista = static function (bool $archivados) use ($busqueda): string {
       $url = '?ruta=mis-productos' . ($archivados ? '&vista=archivados' : '');
       if (($busqueda ?? '') !== '') {
           $url .= '&q=' . urlencode($busqueda);
       }
       return $url;
   };
 ?>
 <nav class="pv-filtros" aria-label="Filtro de publicaciones">
  <a class="pv-filtro<?= empty($verArchivados) ? ' pv-filtro-activo' : '' ?>"
     href="<?= $e($enlaceVista(false)) ?>">
   Publicaciones <span class="pv-filtro-num"><?= (int) ($resumen['total'] ?? 0) ?></span>
  </a>
  <a class="pv-filtro<?= !empty($verArchivados) ? ' pv-filtro-activo' : '' ?>"
     href="<?= $e($enlaceVista(true)) ?>">
   Archivados <span class="pv-filtro-num"><?= (int) ($resumen['archivados'] ?? 0) ?></span>
  </a>
 </nav>

 <div class="pv-barra">

<div class="pv-barra-acciones">
   <a class="pv-btn-nuevo" href="?ruta=mis-productos/nuevo">＋ Publicar producto</a>
   <?php if ($vendedorId > 0): ?>
   <a class="btn-mini" href="?ruta=catalogo&amp;vendedor=<?= $vendedorId ?>">Ver mi tienda</a>
   <?php endif; ?>
  </div>
 </div>

 <?php if (!$productos): ?>
 <div class="orders-empty pv-vacio">
  <div class="empty-icon"><?= !empty($verArchivados) ? '<svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg>' : ((($busqueda ?? '') !== '') ? '<svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-search"/></svg>' : '<svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bag"/></svg>') ?></div>
  <?php if (!empty($verArchivados)): ?>
  <h4><?= ($busqueda ?? '') !== '' ? 'No encontramos archivados con «' . $e($busqueda) . '»' : 'No tienes publicaciones archivadas' ?></h4>
  <p>Las publicaciones que archives aparecerán aquí para poder restaurarlas.</p>
  <a class="btn-auth-gate" href="?ruta=mis-productos">Ver mis publicaciones</a>
  <?php elseif (($busqueda ?? '') !== ''): ?>
  <h4>No encontramos publicaciones con «<?= $e($busqueda) ?>»</h4>
  <p>Prueba con otro nombre, modelo o SKU.</p>
  <a class="btn-auth-gate" href="?ruta=mis-productos">Ver todas mis publicaciones</a>
  <?php else: ?>
  <h4>Aún no has publicado nada</h4>
  <p>Crea tu primera publicación y empieza a vender en el marketplace.</p>
  <a class="btn-auth-gate" href="?ruta=mis-productos/nuevo">Publicar mi primer producto</a>
  <?php endif; ?>
 </div>
 <?php else: ?>
 <div class="pv-grid">
  <?php foreach ($productos as $p): ?>
   <?php
     $slug = (string) ($p['slug'] ?? '');
     $activo = (int) ($p['activo'] ?? 0) === 1;
     $stock = (int) ($p['stock'] ?? 0);
     $stockMinimo = (int) ($p['stock_minimo'] ?? 0);
     $stockBajo = $stock <= $stockMinimo;
     $imagen = trim((string) ($p['imagen'] ?? ''));
     if ($imagen === '') {
         $imagen = $imagenPredeterminada;
     }
     $unidades = (int) ($p['unidades_vendidas'] ?? 0);
     $archivada = ($p['archivado_en'] ?? null) !== null;
     $eliminable = (bool) ($p['eliminable'] ?? false);
     $claseCard = $archivada ? ' pv-card-archivada' : ($activo ? '' : ' pv-card-oculta');
   ?>
   <article class="pv-card<?= $claseCard ?>">
    <div class="pv-card-media">
     <img src="<?= $e(\Model\Producto::imagenOptimizada($imagen)) ?>" alt="<?= $e($p['nombre'] ?? 'Producto') ?>" loading="lazy"/>
     <span class="pv-badge <?= $archivada ? 'pv-badge-archivada' : ($activo ? 'pv-badge-ok' : 'pv-badge-off') ?>">
      <?= $archivada ? 'Archivado' : ($activo ? 'Activo' : 'Oculto') ?>
     </span>
    </div>

    <div class="pv-card-cuerpo">
     <span class="pv-categoria"><?= $e($p['categoria'] ?? '') ?></span>
     <h3 class="pv-nombre"><?= $e($p['nombre'] ?? 'Producto') ?></h3>
     <p class="pv-modelo">
      <?php if ((string) ($p['modelo'] ?? '') !== ''): ?><?= $e($p['modelo']) ?><?php endif; ?>
      <?php if ((string) ($p['codigo_sku'] ?? '') !== ''): ?>
       <?= (string) ($p['modelo'] ?? '') !== '' ? ' · ' : '' ?>SKU <?= $e($p['codigo_sku']) ?>
      <?php endif; ?>
     </p>

     <div class="pv-datos">
      <span class="pv-precio"><?= $fmt((float) ($p['precio_pen'] ?? 0)) ?></span>
      <span class="pv-stock<?= $stockBajo ? ' pv-stock-bajo' : '' ?>">
       <?= $stock ?> en stock<?= $stockBajo ? ' · mínimo ' . $stockMinimo : '' ?>
      </span>
     </div>

     <p class="pv-vendidos">
      <?= $unidades > 0 ? $unidades . ' unidad' . ($unidades === 1 ? '' : 'es') . ' vendida' . ($unidades === 1 ? '' : 's') : 'Sin ventas todavía' ?>
      · <?= (int) ($p['variantes_total'] ?? 0) ?> variante<?= (int) ($p['variantes_total'] ?? 0) === 1 ? '' : 's' ?>
      · <?= (int) ($p['tramos_total'] ?? 0) ?> tramo<?= (int) ($p['tramos_total'] ?? 0) === 1 ? '' : 's' ?> de volumen
     </p>

     <div class="pv-acciones">
      <?php if ($archivada): ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/restaurar',
          ['slug' => $slug, 'vista' => 'archivados'],
          'Restaurar',
          'btn-mini',
          '¿Restaurar esta publicación? Volverá como oculta.'
      ) ?>
      <?php if ($eliminable): ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/eliminar',
          ['slug' => $slug, 'vista' => 'archivados'],
          'Eliminar',
          'btn-mini btn-mini-peligro',
          '¿Eliminar definitivamente esta publicación? No se puede deshacer.'
      ) ?>
      <?php endif; ?>
      <?php else: ?>
      <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slug) ?>">Editar</a>
      <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slug) ?>#variantes">Variantes (<?= (int) ($p['variantes_total'] ?? 0) ?>)</a>
      <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slug) ?>#precios-volumen">Precios (<?= (int) ($p['tramos_total'] ?? 0) ?>)</a>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/estado',
          ['slug' => $slug],
          $activo ? 'Ocultar' : 'Activar'
      ) ?>
      <?php if ($activo): ?>
      <a class="btn-mini" href="?ruta=producto&amp;slug=<?= urlencode($slug) ?>" target="_blank" rel="noopener">Ver ficha</a>
      <?php endif; ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/archivar',
          ['slug' => $slug],
          'Archivar',
          'btn-mini',
          '¿Archivar esta publicación? Saldrá de la tienda pero conservará su historial.'
      ) ?>
      <?php if ($eliminable): ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/eliminar',
          ['slug' => $slug],
          'Eliminar',
          'btn-mini btn-mini-peligro',
          '¿Eliminar definitivamente esta publicación? No se puede deshacer.'
      ) ?>
      <?php endif; ?>
      <?php endif; ?>
     </div>

     <?php if ($archivada): ?>
     <p class="pv-sub pv-archivado-nota">
      Archivado el <?= $e($fecha($p['archivado_en'])) ?>. No aparece en la tienda.
     </p>
     <?php elseif (!$eliminable): ?>
     <p class="pv-sub pv-archivado-nota">
      Tiene ventas o historial: archívalo en lugar de eliminarlo.
     </p>
     <?php endif; ?>

     <?php if (!$archivada): ?>
     <form method="post" action="?ruta=mis-productos/stock" class="pv-stock-form">
      <?= \Security\Proteccion::campo() ?>
      <input type="hidden" name="slug" value="<?= $e($slug) ?>"/>
      <label for="stock-<?= (int) ($p['id'] ?? 0) ?>">Stock</label>
      <input id="stock-<?= (int) ($p['id'] ?? 0) ?>" type="number" name="stock"
             min="0" step="1" value="<?= $stock ?>" inputmode="numeric"/>
      <button type="submit" class="btn-mini">Actualizar</button>
     </form>
     <?php endif; ?>
    </div>
   </article>
  <?php endforeach; ?>
 </div>
 <?php endif; ?>
