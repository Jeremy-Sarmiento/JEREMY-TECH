<?php
/**
 * VISTA: Mis productos (Jeremy Tech) — Fase 3.
 *
 * Datos de entrada (src/Controller/VendedorController::productos()):
 *   $productos, $resumen, $busqueda, $flash, $vendedor,
 *   $ganancias, $compradores, $movimientos, $reputacion, $calificaciones,
 *   $verArchivados.
 *
 * Solo se listan publicaciones cuyo `vendedor_id` coincide con la sesión.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $monto): string => 'S/ ' . number_format($monto, 2);
$imagenPredeterminada = 'img/producto-sin-imagen.svg';
$vendedorId = (int) ($vendedor['id'] ?? 0);
$fecha = static function (?string $valor): string {
    return $valor !== null && $valor !== '' ? date('d/m/Y', strtotime($valor)) : '—';
};
$fechaHora = static function (?string $valor): string {
    return $valor !== null && $valor !== '' ? date('d/m/Y H:i', strtotime($valor)) : '—';
};
$claseEstado = static function (string $estado): string {
    return match ($estado) {
        'Pagado', 'Entregado' => 'pv-estado-ok',
        'Enviado' => 'pv-estado-info',
        'Pendiente', 'En verificación' => 'pv-estado-pendiente',
        'Cancelado', 'Reembolsado' => 'pv-estado-off',
        default => '',
    };
};
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1>🏪 Mis productos</h1>
  <p>Publica, edita y controla el stock de lo que vendes en Jeremy Tech.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <div class="pv-resumen" aria-label="Resumen de mis productos">
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true">📦</span>
   <span class="pv-stat-valor"><?= (int) ($resumen['total'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Publicaciones</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true">✅</span>
   <span class="pv-stat-valor"><?= (int) ($resumen['activos'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Activas en tienda</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true">🙈</span>
   <span class="pv-stat-valor"><?= (int) ($resumen['ocultos'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Ocultas</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true">⚠️</span>
   <span class="pv-stat-valor"><?= (int) ($resumen['stock_bajo'] ?? 0) ?></span>
   <span class="pv-stat-etiqueta">Con stock bajo</span>
  </div>
  <div class="pv-stat">
   <span class="pv-stat-icon" aria-hidden="true">💰</span>
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
   📦 Publicaciones <span class="pv-filtro-num"><?= (int) ($resumen['total'] ?? 0) ?></span>
  </a>
  <a class="pv-filtro<?= !empty($verArchivados) ? ' pv-filtro-activo' : '' ?>"
     href="<?= $e($enlaceVista(true)) ?>">
   🗄️ Archivados <span class="pv-filtro-num"><?= (int) ($resumen['archivados'] ?? 0) ?></span>
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
  <div class="empty-icon"><?= !empty($verArchivados) ? '🗄️' : ((($busqueda ?? '') !== '') ? '🔎' : '🏪') ?></div>
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
     <img src="<?= $e($imagen) ?>" alt="<?= $e($p['nombre'] ?? 'Producto') ?>" loading="lazy"/>
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
     </p>

     <div class="pv-acciones">
      <?php if ($archivada): ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/restaurar',
          ['slug' => $slug, 'vista' => 'archivados'],
          '♻️ Restaurar',
          'btn-mini',
          '¿Restaurar esta publicación? Volverá como oculta.'
      ) ?>
      <?php if ($eliminable): ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/eliminar',
          ['slug' => $slug, 'vista' => 'archivados'],
          '🗑️ Eliminar',
          'btn-mini btn-mini-peligro',
          '¿Eliminar definitivamente esta publicación? No se puede deshacer.'
      ) ?>
      <?php endif; ?>
      <?php else: ?>
      <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slug) ?>">✏️ Editar</a>
      <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slug) ?>#variantes">🧩 Variantes</a>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/estado',
          ['slug' => $slug],
          $activo ? '🙈 Ocultar' : '✅ Activar'
      ) ?>
      <?php if ($activo): ?>
      <a class="btn-mini" href="?ruta=producto&amp;slug=<?= urlencode($slug) ?>" target="_blank" rel="noopener">👁️ Ver ficha</a>
      <?php endif; ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/archivar',
          ['slug' => $slug],
          '🗄️ Archivar',
          'btn-mini',
          '¿Archivar esta publicación? Saldrá de la tienda pero conservará su historial.'
      ) ?>
      <?php if ($eliminable): ?>
      <?= \Security\Proteccion::botonPost(
          'mis-productos/eliminar',
          ['slug' => $slug],
          '🗑️ Eliminar',
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

 <section class="pv-panel" id="mis-ganancias" aria-labelledby="pv-ganancias-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-ganancias-titulo">💰 Mis ganancias</h2>
    <p class="pv-panel-nota">
     Solo cuenta pedidos cobrados: un pedido pendiente o cancelado todavía no genera
     ganancia. Los importes son la base sin IGV congelada al momento de la compra.
    </p>
   </div>
  </div>
  <div class="pv-ganancias">
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['vendido'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Vendido (sin IGV)</span>
   </div>
   <div class="pv-ganancia pv-ganancia-comision">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['comision'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Comisión de la plataforma</span>
   </div>
   <div class="pv-ganancia pv-ganancia-neto">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['neto'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Neto a recibir</span>
   </div>
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['liquidado'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Ya liquidado</span>
   </div>
   <div class="pv-ganancia pv-ganancia-saldo">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['saldo'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Saldo pendiente</span>
   </div>
  </div>
 </section>

 <section class="pv-panel" id="mis-compradores" aria-labelledby="pv-compradores-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-compradores-titulo">🛒 Quién me compró</h2>
    <p class="pv-panel-nota">
     Solo ves tus ventas. Si un pedido mezcla productos de varios vendedores, aquí
     aparece únicamente la parte que te corresponde. Se muestran las últimas 50
     líneas, incluidos los pedidos pendientes o cancelados con su estado.
    </p>
   </div>
   <?php
     $pedidosComprados = count(array_unique(array_map(
         static fn (array $compra): int => (int) ($compra['pedido_id'] ?? 0),
         $compradores ?? []
     )));
   ?>
   <span class="pv-panel-contador">
    <?= $pedidosComprados ?> pedido<?= $pedidosComprados === 1 ? '' : 's' ?>
   </span>
  </div>
  <?php if (empty($compradores)): ?>
  <p class="pv-vacio-mini">Todavía nadie te compró. Cuando se confirme una venta, verás aquí al comprador y el estado del pago.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Fecha</th>
      <th>Comprador</th>
      <th>Producto</th>
      <th>Cant.</th>
      <th>Subtotal</th>
      <th>Pedido</th>
      <th>Pago</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($compradores as $compra): ?>
      <?php
        $estadoPago = trim((string) ($compra['pago_estado'] ?? ''));
        if ($estadoPago === '') {
            $estadoPago = trim((string) ($compra['pedido_estado'] ?? ''));
        }
        $nombreComprador = trim(
            (string) ($compra['comprador_nombre'] ?? '') . ' ' . (string) ($compra['comprador_apellidos'] ?? '')
        );
      ?>
      <tr>
       <td><?= $e($fecha($compra['creado_en'] ?? null)) ?></td>
       <td>
        <strong><?= $e($nombreComprador !== '' ? $nombreComprador : 'Cliente') ?></strong>
        <?php if ((string) ($compra['comprador_email'] ?? '') !== ''): ?>
        <span class="pv-sub"><?= $e($compra['comprador_email']) ?></span>
        <?php endif; ?>
       </td>
       <td>
        <?= $e($compra['producto_nombre'] ?? 'Producto') ?>
        <?php if ((string) ($compra['variante_nombre'] ?? '') !== ''): ?>
        <span class="pv-sub">Variante: <?= $e($compra['variante_nombre']) ?></span>
        <?php endif; ?>
       </td>
       <td><?= (int) ($compra['cantidad'] ?? 0) ?></td>
       <td><?= $fmt((float) ($compra['subtotal'] ?? 0)) ?></td>
       <td>#JT-<?= str_pad((string) (int) ($compra['pedido_id'] ?? 0), 5, '0', STR_PAD_LEFT) ?></td>
       <td>
        <span class="pv-estado <?= $claseEstado($estadoPago) ?>"><?= $e($estadoPago !== '' ? $estadoPago : 'Sin pago') ?></span>
        <span class="pv-sub">Pedido: <?= $e((string) ($compra['pedido_estado'] ?? '—')) ?></span>
       </td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>
 </section>

 <section class="pv-panel" id="mi-stock" aria-labelledby="pv-stock-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-stock-titulo">📦 Historial de mi stock</h2>
    <p class="pv-panel-nota">
     Últimos 20 movimientos de tus publicaciones: ventas, reembolsos y ajustes
     manuales que entraron o salieron del inventario.
    </p>
   </div>
  </div>
  <?php if (empty($movimientos)): ?>
  <p class="pv-vacio-mini">Sin movimientos todavía. Aquí se registrarán las ventas, devoluciones y ajustes de stock de tus productos.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Fecha</th>
      <th>Producto</th>
      <th>Tipo</th>
      <th>Cantidad</th>
      <th>Motivo</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($movimientos as $movimiento): ?>
      <?php $esEntrada = (string) ($movimiento['tipo'] ?? '') === 'entrada'; ?>
      <tr>
       <td><?= $e($fechaHora($movimiento['creado_en'] ?? null)) ?></td>
       <td>
        <?= $e($movimiento['producto_nombre'] ?? 'Producto') ?>
        <?php if ((string) ($movimiento['codigo_sku'] ?? '') !== ''): ?>
        <span class="pv-sub">SKU <?= $e($movimiento['codigo_sku']) ?></span>
        <?php endif; ?>
        <?php if ((string) ($movimiento['nombre_variante'] ?? '') !== ''): ?>
        <span class="pv-sub">Variante: <?= $e($movimiento['nombre_variante']) ?></span>
        <?php endif; ?>
       </td>
       <td>
        <span class="pv-mov <?= $esEntrada ? 'pv-mov-entrada' : 'pv-mov-salida' ?>">
         <?= $esEntrada ? '⬆ Entrada' : '⬇ Salida' ?>
        </span>
       </td>
       <td class="<?= $esEntrada ? 'pv-cantidad-entrada' : 'pv-cantidad-salida' ?>">
        <?= $esEntrada ? '+' : '−' ?><?= (int) ($movimiento['cantidad'] ?? 0) ?>
       </td>
       <td><?= $e($movimiento['motivo'] ?? '') ?></td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>
 </section>

 <section class="pv-panel" id="mi-reputacion" aria-labelledby="pv-reputacion-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-reputacion-titulo">⭐ Mi reputación</h2>
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
</main>
