<?php
use Model\Producto;

/**
 * @var array<int, array<string, mixed>> $grupo Variantes que comparten la misma tarjeta.
 * @var array<int, float> $precioDesde Mapa productoId => precio más barato de sus
 *                                     tramos de volumen (Etapa 7). Se pasa desde
 *                                     `ProductoController::catalogo()` con una sola
 *                                     consulta para todo el catálogo.
 * Genera el mismo marcado que producía `generarCard()` en script.js.
 */
$tieneMulti = count($grupo) > 1;

// (Etapa 7) Enlace a la ficha: el producto publicado que abre la tarjeta.
// Se define antes del carrusel porque las miniaturas también lo usan.
$ficha = $grupo[0] ?? [];
$urlFicha = '?ruta=producto&slug=' . urlencode((string) $ficha['slug']);

// Favoritos: el controlador entrega un mapa id => id. Si la tarjeta se
// reutiliza desde otra vista, simplemente no habrá corazón activo.
$favoritosIds = $favoritosIds ?? [];
$esFavorito = isset($favoritosIds[(int) ($ficha['id'] ?? 0)]);

// (Fase 2) Valoración media por producto (mapa id => total/promedio).
$resenasResumen = $resenasResumen ?? [];

$carouselItems = '';
foreach ($grupo as $variante) {
    $carouselItems .=         '<a class="card-carousel-item" href="' . $urlFicha . '">' .
        '<img alt="' . htmlspecialchars($variante['nombre'], ENT_QUOTES) . '" loading="lazy" src="' .
        htmlspecialchars($variante['imagen'], ENT_QUOTES) . '"/>' .
        '</a>';
}

$dots = '';
foreach ($grupo as $indice => $varianteUnused) {
    $dots .= '<button type="button" class="card-carousel-dot' . ($indice === 0 ? ' active' : '') . '"'
        . ' aria-label="Ver variante ' . ($indice + 1) . '"'
        . ' aria-current="' . ($indice === 0 ? 'true' : 'false') . '"></button>';
}

// (Etapa 6) Atribución: quién publica este producto en el marketplace.
$dueno = $grupo[0] ?? [];
$duenoNombre = trim((string) ($dueno['vendedorNombre'] ?? ''));
$duenoId = $dueno['vendedorId'] ?? null;
$vendedorHTML = $duenoNombre !== ''
    ? '<p class="card-vendedor">Vendido por <a href="?ruta=catalogo'
        . ($duenoId !== null ? '&amp;vendedor=' . (int) $duenoId : '')
        . '"><strong>' . htmlspecialchars($duenoNombre, ENT_QUOTES) . '</strong></a>'
        . ((int) ($dueno['vendedorVerificado'] ?? 0) === 1
            ? '<span class="card-vendedor-check" title="Vendedor verificado">✔</span>' : '')
        . '</p>'
    : '<p class="card-vendedor card-vendedor-tienda">Vendido por Jeremy Tech</p>';

$infoItems = '';
foreach ($grupo as $indice => $variante) {
    $colores = '';
    foreach ($variante['colores'] as $color) {
        $colores .= '<div class="color-dot"' . Producto::colorStyle($color) . '></div>';
    }
    $whatsapp = htmlspecialchars(
        str_replace(['\\', "'"], ['\\\\', "\\'"], $variante['nombre'] . ' - ' . $variante['especificaciones']),
        ENT_QUOTES
    );
    $resumenTarjeta = $resenasResumen[(int) $variante['id']] ?? null;
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
    $infoItems .= '<div class="card-info-item' . ($indice === 0 ? ' active' : '') . '" data-variant-id="' .
        htmlspecialchars($variante['id'], ENT_QUOTES) . '">' .
        '<h3><a class="card-titulo" href="' . $urlFicha . '">' .
        htmlspecialchars($variante['nombre'], ENT_QUOTES) . '</a></h3>' .
        '<p>' . htmlspecialchars($variante['especificaciones'], ENT_QUOTES) . '</p>' .
        $vendedorHTML .
        '<p class="price" data-precio="' . htmlspecialchars((string) (float) $variante['precioPEN'], ENT_QUOTES) . '">PEN ' .
        number_format((float) $variante['precioPEN'], 2) . '</p>' .
        $ratingHTML .
        // (Etapa 7) Precio más barato entre los tramos de volumen del producto.
        (($precioDesde[$variante['id']] ?? null) !== null
            && (float) $precioDesde[$variante['id']] < (float) $variante['precioPEN']
            ? '<p class="card-desde">desde <strong>PEN ' .
                number_format((float) $precioDesde[$variante['id']], 2) . '</strong> por volumen</p>'
            : '') .
        '<div class="colors">' . $colores . '</div>' .
        '<div class="card-actions">' .
        // (Etapa 7) "Ver ficha" para ver variantes y precios por volumen.
        '<a class="btn-ver-ficha" href="' . $urlFicha . '">Ver ficha</a>' .
        '<button class="btn-cart-add" onclick="agregarAlCarrito(\'' .
        htmlspecialchars($variante['id'], ENT_QUOTES) . '\')">' .
        '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><use href="img/icons.svg#icon-cart"/></svg>' .
        'Agregar' .
        '</button>' .
        '<a class="btn-whatsapp" href="#" onclick="enviarWhatsApp(event, \'' . $whatsapp . '\')">' .
        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <use href="img/icons.svg#icon-whatsapp"/>
       </svg>' .
        'Consultar' .
        '</a>' .
        '</div>' .
        '</div>';
}

$btnsHTML = $tieneMulti
    ? '<button type="button" class="card-carousel-btn prev" onclick="changeCardSlide(this, -1)" aria-label="Variante anterior"><span aria-hidden="true">‹</span></button>' .
      '<button type="button" class="card-carousel-btn next" onclick="changeCardSlide(this, 1)" aria-label="Siguiente variante"><span aria-hidden="true">›</span></button>'
    : '';
$dotsClass = $tieneMulti ? '' : ' style="display:none"';
?>
<div class="card" data-search="<?= Producto::textoBusqueda($grupo) ?>">
  <div class="card-carousel" role="group" aria-label="Imágenes del producto" aria-roledescription="carrusel">
    <div class="card-carousel-inner"><?= $carouselItems ?></div>
    <?= $btnsHTML ?>
    <div class="card-carousel-dots" role="group" aria-label="Seleccionar variante"<?= $dotsClass ?>><?= $dots ?></div>
  </div>
  <form method="post" action="?ruta=favoritos/alternar" class="favorito-form favorito-card-form">
    <?= \Security\Proteccion::campo() ?>
    <input type="hidden" name="producto_id" value="<?= (int) ($ficha['id'] ?? 0) ?>">
    <button type="submit"
            class="favorito-btn<?= $esFavorito ? ' activo' : '' ?>"
            aria-pressed="<?= $esFavorito ? 'true' : 'false' ?>"
            aria-label="<?= $esFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos' ?>"
            title="<?= $esFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos' ?>">
      <span class="favorito-icon" aria-hidden="true"><?= $esFavorito ? '♥' : '♡' ?></span>
    </button>
  </form>
  <div class="info"><?= $infoItems ?></div>
</div>