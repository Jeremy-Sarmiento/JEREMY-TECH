<?php
use Model\PrecioVolumen;
use Model\Producto;

/**
 * Vista: Ficha de producto (Etapa 7).
 * Variables del controlador: $producto, $extras (marca/garantía),
 * $variantes, $tramos, $varianteInicial, $precioInicial.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $m): string => number_format($m, 2);

$productoId   = (int) $producto['id'];
$nombre       = (string) $producto['nombre'];
$especifica   = trim((string) $producto['especificaciones']);
$precioBase   = (float) $producto['precioPEN'];
$stockProducto = (int) $producto['stock'];
$duenoNombre  = trim(Producto::vendedorDe($producto));
$duenoId      = $producto['vendedorId'] ?? null;
$tieneTramos  = $tramos !== [];
$tieneVariantes = $variantes !== [];

// Colores del producto publicado (no confundir con los de las variantes).
$coloresHTML = '';
foreach ($producto['colores'] as $color) {
    $coloresHTML .= '<div class="color-dot"' . Producto::colorStyle((string) $color) . '></div>';
}

$especificacionesHTML = '';
foreach (array_filter([
    'Modelo'        => $producto['modelo'],
    'SKU'           => $producto['codigoSKU'],
    'Peso'          => $producto['pesoKg'] !== null ? $producto['pesoKg'] . ' kg' : null,
    'Dimensiones'   => $producto['dimensiones'],
], static fn ($v) => $v !== null && $v !== '') as $etiqueta => $valor) {
    $especificacionesHTML .= '<tr><th>' . $e($etiqueta) . '</th><td>' . $e($valor) . '</td></tr>';
}

// Datos que el JS de la página necesita para recalcular el precio en vivo.
$datosJS = [
    'id'          => $productoId,
    'nombre'      => $nombre,
    'precioBase'  => $precioBase,
    'varianteId'  => $varianteInicial !== null ? (int) $varianteInicial['id'] : null,
    'stockBase'   => $stockProducto,
    'variantes'   => array_map(static fn (array $v): array => [
        'id'          => (int) $v['id'],
        'nombre'      => (string) $v['nombreVariante'],
        'precioExtra' => (float) $v['precioExtra'],
        'stock'       => (int) $v['stock'],
        'color'       => (string) $v['color'],
    ], $variantes),
    'tramos'      => array_map(static fn (array $t): array => [
        'id'             => (int) $t['id'],
        'cantidadMinima' => (int) $t['cantidadMinima'],
        'cantidadMaxima' => $t['cantidadMaxima'] !== null ? (int) $t['cantidadMaxima'] : null,
        'precioUnitario' => (float) $t['precioUnitario'],
    ], $tramos),
    'precioUnitario' => (float) $precioInicial['precio'],
];
?>
<section class="producto-detalle">
    <nav class="migas" aria-label="Ruta de navegación">
        <a href="?ruta=catalogo">Catálogo</a>
        <span>/</span>
        <a href="?ruta=catalogo#<?= strtolower((string) $producto['categoria']) ?>"><?= $e($producto['categoria']) ?></a>
        <span>/</span>
        <strong><?= $e($nombre) ?></strong>
    </nav>

    <div class="producto-cuerpo">
        <div class="producto-galeria">
            <img id="prodImagen" alt="<?= $e($nombre) ?>" src="<?= $e((string) $producto['imagen']) ?>">
        </div>

        <div class="producto-info">
            <h1 class="producto-nombre"><?= $e($nombre) ?></h1>

            <p class="producto-meta">
                <?php if (!empty($extras['marca'])): ?><span><?= $e($extras['marca']) ?></span> ·<?php endif; ?>
                <?php if (!empty($extras['garantia'])): ?><span><?= $e($extras['garantia']) ?></span> ·<?php endif; ?>
                <?php if ($producto['codigoSKU'] !== null): ?><span>SKU <?= $e($producto['codigoSKU']) ?></span><?php endif; ?>
            </p>

            <p class="producto-vendedor">
                <?php if ($duenoNombre !== ''): ?>
                    Vendido por
                    <a href="?ruta=catalogo<?= $duenoId !== null ? '&amp;vendedor=' . (int) $duenoId : '' ?>">
                        <strong><?= $e($duenoNombre) ?></strong>
                    </a>
                    <?php if ((int) $producto['vendedorVerificado'] === 1): ?>
                        <span class="card-vendedor-check" title="Vendedor verificado">✔</span>
                    <?php endif; ?>
                <?php else: ?>
                    Vendido por Jeremy Tech
                <?php endif; ?>
            </p>

            <div class="producto-precio">
                <span class="price" id="prodPrecio" data-precio="<?= $fmt((float) $precioInicial['precio']) ?>">
                    PEN <?= $fmt((float) $precioInicial['precio']) ?>
                </span>
                <span class="producto-ahorro" id="prodAhorro" hidden></span>
            </div>

            <p class="producto-stock" id="prodStock"></p>

            <?php if ($tieneVariantes): ?>
            <div class="producto-bloque">
                <h2 class="producto-subtitulo">Variante</h2>
                <div class="producto-variantes" id="prodVariantes">
                    <?php foreach ($variantes as $v): ?>
                        <button type="button"
                                class="variante-opcion<?= $varianteInicial !== null && (int) $v['id'] === (int) $varianteInicial['id'] ? ' activa' : '' ?>"
                                data-variante="<?= (int) $v['id'] ?>"
                                data-stock="<?= (int) $v['stock'] ?>">
                            <?php if ($v['color'] !== ''): ?>
                                <span class="color-dot variante-color"<?php echo Producto::colorStyle((string) $v['color']); ?>></span>
                            <?php endif; ?>
                            <span><?= $e($v['nombreVariante']) ?></span>
                            <?php if ((float) $v['precioExtra'] > 0): ?>
                                <small>+ S/ <?= $fmt((float) $v['precioExtra']) ?></small>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($tieneTramos): ?>
            <div class="producto-bloque">
                <h2 class="producto-subtitulo">Precio por volumen</h2>
                <p class="producto-nota">El descuento se aplica automáticamente al elegir la cantidad.</p>
                <table class="tabla-volumen" id="prodVolumen">
                    <thead>
                        <tr><th>Cantidad</th><th>Precio unitario</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tramos as $t): ?>
                            <tr data-tramo="<?= (int) $t['id'] ?>">
                                <td><?= $e(PrecioVolumen::rangoTexto($t)) ?></td>
                                <td>S/ <?= $fmt((float) $t['precioUnitario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="producto-bloque">
                <h2 class="producto-subtitulo">Cantidad</h2>
                <div class="producto-cantidad">
                    <button type="button" class="qty-btn" id="prodMenos" aria-label="Disminuir cantidad">−</button>
                    <input type="number" id="prodCantidad" class="qty-val" value="1" min="1" max="99" aria-label="Cantidad">
                    <button type="button" class="qty-btn" id="prodMas" aria-label="Aumentar cantidad">+</button>
                </div>
            </div>

            <div class="producto-acciones">
                <button type="button" class="btn-cart-add producto-agregar" id="prodAgregar">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><use href="img/icons.svg#icon-cart"/></svg>
                    Agregar al carrito
                </button>
                <a class="btn-whatsapp" href="#"
                   onclick="enviarWhatsApp(event, <?= json_encode($nombre . ' - ' . $especifica, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><use href="img/icons.svg#icon-whatsapp"/></svg>
                    Consultar
                </a>
            </div>

            <?php if ($especifica !== ''): ?>
            <div class="producto-bloque">
                <h2 class="producto-subtitulo">Descripción</h2>
                <p class="producto-descripcion"><?= $e($especifica) ?></p>
            </div>
            <?php endif; ?>

            <div class="producto-bloque">
                <h2 class="producto-subtitulo">Ficha técnica</h2>
                <?php if ($coloresHTML !== ''): ?>
                <div class="producto-colores">
                    <span>Colores disponibles</span>
                    <div class="colors"><?= $coloresHTML ?></div>
                </div>
                <?php endif; ?>
                <?php if ($especificacionesHTML !== ''): ?>
                <table class="tabla-ficha">
                    <tbody><?= $especificacionesHTML ?></tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
window.JT_PRODUCTO = <?= json_encode($datosJS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="js/producto.js"></script>
