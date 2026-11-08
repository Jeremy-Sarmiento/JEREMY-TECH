<?php
use Model\Producto;

/**
 * @var array<int, array<string, mixed>> $grupo Productos que comparten tarjeta:
 *      mismo vendedor, categoría y nombre comercial. La tarjeta muestra una
 *      sola opción (producto y variante activa); las demás se eligen en la
 *      ficha del producto.
 * @var array<int, float> $precioDesde Mapa productoId => precio más barato de sus
 *                                     tramos de volumen (Etapa 7). Se pasa desde
 *                                     `ProductoController::catalogo()` con una sola
 *                                     consulta para todo el catálogo.
 * Genera el mismo marcado que producía `generarCard()` en script.js.
 */
$producto = $grupo[0] ?? [];
$variante = $producto['variantes'][0] ?? null;
$urlFicha = '?ruta=producto&slug=' . urlencode((string) ($producto['slug'] ?? ''));

// Favoritos: el controlador entrega un mapa id => id. Si la tarjeta se
// reutiliza desde otra vista, simplemente no habrá corazón activo.
$favoritosIds = $favoritosIds ?? [];
$esFavorito = isset($favoritosIds[(int) ($producto['id'] ?? 0)]);

// (Fase 2) Valoración media por producto (mapa id => total/promedio).
$resenasResumen = $resenasResumen ?? [];

// Etiqueta corta de la capacidad o modelo que distingue esta publicación del
// resto del grupo ("256GB", "16GB RAM · 512GB SSD"...).
$etiquetaProducto = '';
if (count($grupo) > 1) {
    $partesSpecs = array_map(
        static fn (array $v): array => array_map('trim', explode('|', (string) ($v['especificaciones'] ?? ''))),
        $grupo
    );
    $maxPartes = max(array_map('count', $partesSpecs));
    $trozos = [];
    for ($posicion = 0; $posicion < $maxPartes; $posicion++) {
        $valores = [];
        foreach ($partesSpecs as $partes) {
            $valor = (string) ($partes[$posicion] ?? '');
            if ($valor !== '') {
                $valores[$valor] = true;
            }
        }
        if (count($valores) > 1 && ($partesSpecs[0][$posicion] ?? '') !== '') {
            $trozos[] = (string) $partesSpecs[0][$posicion];
        }
    }
    $etiquetaProducto = implode(' · ', $trozos);
}

// Un color se combina con la capacidad del producto; una variante que ya
// describe la configuración completa (sin color) se muestra sola.
$partesEtiqueta = [];
if ($variante !== null) {
    if ((string) ($variante['color'] ?? '') !== '' && $etiquetaProducto !== '') {
        $partesEtiqueta[] = $etiquetaProducto;
    }
    $partesEtiqueta[] = (string) $variante['nombreVariante'];
} elseif ($etiquetaProducto !== '') {
    $partesEtiqueta[] = $etiquetaProducto;
}
$etiqueta = implode(' · ', $partesEtiqueta);

$extra = $variante !== null ? (float) $variante['precioExtra'] : 0.0;
$precio = (float) $producto['precioPEN'] + $extra;
$desde = isset($precioDesde[$producto['id']])
    ? (float) $precioDesde[$producto['id']] + $extra
    : null;
$stock = (int) $producto['stock'];
if ($variante !== null) {
    $stock = min($stock, (int) $variante['stock']);
}

// (Etapa 6) Atribución: quién publica este producto en el marketplace.
$duenoNombre = trim((string) ($producto['vendedorUsuario'] ?? ''));
if ($duenoNombre === '') {
    $duenoNombre = trim((string) ($producto['vendedorNombre'] ?? ''));
}
$duenoId = $producto['vendedorId'] ?? null;
// La tienda es de un solo vendedor: repetir "Vendido por Jeremy Tech" en las
// 14 tarjetas es ruido. Solo se atribuye cuando publica un tercero.
$vendedorHTML = $duenoNombre !== ''
    ? '<p class="card-vendedor">Vendido por <a href="?ruta=catalogo'
        . ($duenoId !== null ? '&amp;vendedor=' . (int) $duenoId : '')
        . '"><strong>' . htmlspecialchars($duenoNombre, ENT_QUOTES) . '</strong></a>'
        . ((int) ($producto['vendedorVerificado'] ?? 0) === 1
            ? '<span class="card-vendedor-check" title="Vendedor verificado">✔</span>' : '')
        . '</p>'
    : '';

$mensaje = (string) $producto['nombre'] . ' - ' . (string) $producto['especificaciones'];
if ($etiqueta !== '') {
    $mensaje .= ' - ' . $etiqueta;
}
$whatsapp = htmlspecialchars(
    str_replace(['\\', "'"], ['\\\\', "\\'"], $mensaje),
    ENT_QUOTES
);

$resumenTarjeta = $resenasResumen[(int) $producto['id']] ?? null;
$ratingHTML = '';
if (is_array($resumenTarjeta) && (int) $resumenTarjeta['total'] > 0) {
    $ratingHTML = '<p class="card-rating">' .
        '<span class="card-rating-star" aria-hidden="true">★</span> ' .
        number_format((float) $resumenTarjeta['promedio'], 1) .
        ' <small>(' . (int) $resumenTarjeta['total'] . ')</small>' .
        '<span class="sr-only"> de 5 según ' . (int) $resumenTarjeta['total'] .
        ' opinión' . ((int) $resumenTarjeta['total'] === 1 ? '' : 'es') . '</span>' .
        '</p>';
}

$stockHTML = '<p class="card-stock' . ($stock <= 0 ? ' agotado' : '') . '">'
    . ($stock > 0 ? 'Disponible: ' . $stock : 'Agotado')
    . '</p>';

$botonCarrito = $stock > 0
    ? '<button class="btn-cart-add" onclick="agregarAlCarrito(\'' . (int) $producto['id'] . '\', 1, '
        . ($variante !== null ? (int) $variante['id'] : 'null') . ')">' .
        '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><use href="img/icons.svg#icon-cart"/></svg>' .
        'Agregar' .
        '</button>'
    : '<button class="btn-cart-add" type="button" disabled>Agotado</button>';

$alt = (string) $producto['nombre'] . ($etiqueta !== '' ? ', ' . $etiqueta : '');

?>
<div class="card" data-search="<?= Producto::textoBusqueda($grupo) ?>">
  <div class="card-media">
    <a class="card-media-item active" href="<?= $urlFicha ?>">
      <img alt="<?= htmlspecialchars($alt, ENT_QUOTES) ?>" loading="lazy"
           src="<?= htmlspecialchars(Producto::urlImagen($producto), ENT_QUOTES) ?>"/>
    </a>
  </div>
  <?php if ($etiqueta !== ''): ?>
    <p class="card-variante-activa"><?= htmlspecialchars($etiqueta, ENT_QUOTES) ?></p>
  <?php endif; ?>
  <form method="post" action="?ruta=favoritos/alternar" class="favorito-form favorito-card-form">
    <?= \Security\Proteccion::campo() ?>
    <input type="hidden" name="producto_id" value="<?= (int) ($producto['id'] ?? 0) ?>">
    <button type="submit"
            class="favorito-btn<?= $esFavorito ? ' activo' : '' ?>"
            aria-pressed="<?= $esFavorito ? 'true' : 'false' ?>"
            aria-label="<?= $esFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos' ?>"
            title="<?= $esFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos' ?>">
      <span class="favorito-icon" aria-hidden="true"><?= $esFavorito ? '♥' : '♡' ?></span>
    </button>
  </form>
  <div class="info">
    <div class="card-info-item active">
      <h3><a class="card-titulo" href="<?= $urlFicha ?>"><?= htmlspecialchars((string) $producto['nombre'], ENT_QUOTES) ?></a></h3>
      <p><?= htmlspecialchars((string) $producto['especificaciones'], ENT_QUOTES) ?></p>
      <?= $vendedorHTML ?>
      <p class="price" data-precio="<?= htmlspecialchars((string) $precio, ENT_QUOTES) ?>">PEN <?= number_format($precio, 2) ?></p>
      <?= $ratingHTML ?>
      <?php /* (Etapa 7) Precio más barato entre los tramos de volumen del producto. */ ?>
      <?php if ($desde !== null && $desde < $precio): ?>
        <p class="card-desde">desde <strong>PEN <?= number_format($desde, 2) ?></strong> por volumen</p>
      <?php endif; ?>
      <?= $stockHTML ?>
      <div class="card-actions">
        <?php /* (Etapa 7) "Ver ficha" para ver las demás variantes y precios por volumen. */ ?>
        <a class="btn-ver-ficha" href="<?= $urlFicha ?>">Ver ficha</a>
        <?= $botonCarrito ?>
        <a class="btn-whatsapp" href="#" onclick="enviarWhatsApp(event, '<?= $whatsapp ?>')">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <use href="img/icons.svg#icon-whatsapp"/>
          </svg>
          Consultar
        </a>
      </div>
    </div>
  </div>
</div>
