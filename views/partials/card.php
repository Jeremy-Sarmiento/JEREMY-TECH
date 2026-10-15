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

$carouselItems = '';
foreach ($grupo as $variante) {
    $carouselItems .=         '<a class="card-carousel-item" href="' . $urlFicha . '">' .
        '<img alt="' . htmlspecialchars($variante['nombre'], ENT_QUOTES) . '" loading="lazy" src="' .
        htmlspecialchars($variante['imagen'], ENT_QUOTES) . '"/>' .
        '</a>';
}

$dots = '';
foreach ($grupo as $indice => $varianteUnused) {
    $dots .= '<span class="card-carousel-dot' . ($indice === 0 ? ' active' : '') . '"></span>';
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
    $infoItems .= '<div class="card-info-item' . ($indice === 0 ? ' active' : '') . '" data-variant-id="' .
        htmlspecialchars($variante['id'], ENT_QUOTES) . '">' .
        '<h3><a class="card-titulo" href="' . $urlFicha . '">' .
        htmlspecialchars($variante['nombre'], ENT_QUOTES) . '</a></h3>' .
        '<p>' . htmlspecialchars($variante['especificaciones'], ENT_QUOTES) . '</p>' .
        $vendedorHTML .
        '<p class="price" data-precio="' . htmlspecialchars((string) (float) $variante['precioPEN'], ENT_QUOTES) . '">PEN ' .
        number_format((float) $variante['precioPEN'], 2) . '</p>' .
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
    ? '<button class="card-carousel-btn prev" onclick="changeCardSlide(this, -1)">‹</button>' .
      '<button class="card-carousel-btn next" onclick="changeCardSlide(this, 1)">›</button>'
    : '';
$dotsClass = $tieneMulti ? '' : ' style="display:none"';
?>
<div class="card" data-search="<?= Producto::textoBusqueda($grupo) ?>">
  <div class="card-carousel">
    <div class="card-carousel-inner"><?= $carouselItems ?></div>
    <?= $btnsHTML ?>
    <div class="card-carousel-dots"<?= $dotsClass ?>><?= $dots ?></div>
  </div>
  <div class="info"><?= $infoItems ?></div>
</div>