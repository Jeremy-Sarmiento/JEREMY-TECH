<?php
use Model\PrecioVolumen;
use Model\Producto;

/**
 * Vista: Ficha de producto (Etapa 7).
 * Variables del controlador: $producto, $extras (marca/garantía),
 * $variantes, $tramos, $varianteInicial, $precioInicial, $reputacionVendedor,
 * $recomendados (grupos de recomendaciones de la ficha).
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
$esFavorito   = isset(($favoritosIds ?? [])[$productoId]);

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
            <img id="prodImagen" alt="<?= $e($nombre) ?>" src="<?= $e(Producto::imagenOptimizada((string) $producto['imagen'])) ?>">
        </div>

        <div class="producto-info">
            <h1 class="producto-nombre"><?= $e($nombre) ?></h1>

            <?php
              $totalResenas = (int) ($resumenResenas['total'] ?? 0);
              $promedioResenas = (float) ($resumenResenas['promedio'] ?? 0);
            ?>
            <p class="producto-rating">
                <a href="#resenas">
                    <?php if ($totalResenas > 0): ?>
                        <span class="producto-rating-estrellas" aria-hidden="true"><?= $e(\Model\Resena::estrellas($promedioResenas)) ?></span>
                        <strong><?= number_format($promedioResenas, 1) ?></strong>
                        <span class="producto-rating-total"><?= $totalResenas ?> opinión<?= $totalResenas === 1 ? '' : 'es' ?></span>
                    <?php else: ?>
                        <span class="producto-rating-total">Sé el primero en opinar</span>
                    <?php endif; ?>
                </a>
            </p>

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
                    <?php if ((int) ($reputacionVendedor['total'] ?? 0) > 0): ?>
                        <?php
                          $totalReputacion = (int) $reputacionVendedor['total'];
                          $promedioReputacion = (float) $reputacionVendedor['promedio'];
                        ?>
                        <span class="producto-vendedor-rating"
                              title="Reputación del vendedor: <?= number_format($promedioReputacion, 1) ?> de 5">
                            <?= $e(\Model\Resena::estrellas($promedioReputacion)) ?>
                            <strong><?= number_format($promedioReputacion, 1) ?></strong>
                            <span class="producto-vendedor-rating-total">(<?= $totalReputacion ?>)</span>
                        </span>
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
                <form method="post" action="?ruta=favoritos/alternar" class="favorito-form favorito-detalle-form">
                    <?= \Security\Proteccion::campo() ?>
                    <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                    <button type="submit"
                            class="favorito-detalle-btn<?= $esFavorito ? ' activo' : '' ?>"
                            aria-pressed="<?= $esFavorito ? 'true' : 'false' ?>"
                            aria-label="<?= $esFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos' ?>">
                        <span class="favorito-icon" aria-hidden="true"><?= $esFavorito ? '♥' : '♡' ?></span>
                        <span class="favorito-texto"><?= $esFavorito ? 'En favoritos' : 'Guardar en favoritos' ?></span>
                    </button>
                </form>
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

    <?php if ($recomendados !== []): ?>
    <section class="producto-bloque recomendados" id="recomendados" aria-labelledby="recomendados-titulo">
        <h2 class="producto-subtitulo" id="recomendados-titulo">Recomendados para ti</h2>
        <div class="grid">
            <?php foreach ($recomendados as $grupo): ?>
                <?php require BASE_PATH . '/views/partials/card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="producto-bloque resenas-producto" id="resenas">
        <h2 class="producto-subtitulo">Opiniones de clientes</h2>

        <?php if (($avisoResena['mensaje'] ?? '') !== ''): ?>
        <div class="auth-flash auth-flash-<?= ($avisoResena['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
            <?= $e($avisoResena['mensaje']) ?>
        </div>
        <?php endif; ?>

        <?php if ($totalResenas > 0): ?>
        <div class="resenas-resumen">
            <div class="resenas-nota">
                <span class="resenas-nota-num"><?= number_format($promedioResenas, 1) ?></span>
                <span class="resenas-nota-estrellas" aria-hidden="true"><?= $e(\Model\Resena::estrellas($promedioResenas)) ?></span>
                <span class="resenas-nota-total"><?= $totalResenas ?> opinión<?= $totalResenas === 1 ? '' : 'es' ?></span>
            </div>
            <ul class="resenas-barras">
                <?php foreach ([5, 4, 3, 2, 1] as $estrella): ?>
                <?php
                  $cantidadEstrella = (int) ($resumenResenas['distribucion'][$estrella] ?? 0);
                  $porcentaje = $totalResenas > 0 ? round($cantidadEstrella * 100 / $totalResenas) : 0;
                ?>
                <li>
                    <span class="resenas-barra-etiqueta"><?= $estrella ?> ★</span>
                    <span class="resenas-barra"><span style="width:<?= $porcentaje ?>%"></span></span>
                    <span class="resenas-barra-cuenta"><?= $cantidadEstrella ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($puedeOpinar): ?>
        <div class="resena-caja">
            <h3 class="resena-caja-titulo"><?= $miResena !== null ? 'Actualiza tu opinión' : 'Opina sobre este producto' ?></h3>
            <?php
              $resenaProductoId = (int) $producto['id'];
              $resenaProductoNombre = $nombre;
              $resenaActual = $miResena;
              $resenaSlug = (string) $producto['slug'];
              $resenaSufijo = 'detalle-' . (int) $producto['id'];
              require BASE_PATH . '/views/partials/resena_form.php';
            ?>
        </div>
        <?php elseif (!\Model\Cuenta::estaRegistrado()): ?>
        <p class="resenas-aviso">
            <a href="?ruta=login">Inicia sesión</a> para opinar sobre este producto.
        </p>
        <?php else: ?>
        <p class="resenas-aviso">
            Solo los clientes que compraron este producto pueden dejar una opinión.
        </p>
        <?php endif; ?>

        <?php if ($resenasProducto !== []): ?>
        <ul class="resenas-lista">
            <?php foreach ($resenasProducto as $resena): ?>
            <?php
              $autor = trim((string) ($resena['cliente_nombre'] ?? 'Cliente'));
              $apellidosAutor = trim((string) ($resena['cliente_apellidos'] ?? ''));
              if ($apellidosAutor !== '') {
                  $autor .= ' ' . mb_strtoupper(mb_substr($apellidosAutor, 0, 1)) . '.';
              }
              $fechaResena = trim((string) ($resena['creado_en'] ?? ''));
              $fechaResenaTs = $fechaResena !== '' ? strtotime($fechaResena) : false;
              $fechaResena = $fechaResenaTs !== false ? date('d/m/Y', $fechaResenaTs) : '';
              $esPropia = \Model\Cuenta::idSesion() === (int) ($resena['cliente_id'] ?? 0);
            ?>
            <li class="resena-item"<?= $esPropia ? ' data-autor-propio="1"' : '' ?>>
                <div class="resena-item-cabecera">
                    <strong class="resena-item-autor"><?= $e($autor) ?></strong>
                    <span class="resena-item-estrellas" aria-label="<?= (int) $resena['calificacion'] ?> de 5 estrellas"><?= $e(\Model\Resena::estrellas((float) $resena['calificacion'])) ?></span>
                    <?php if ($fechaResena !== ''): ?>
                    <time class="resena-item-fecha" datetime="<?= $e(date('c', (int) $fechaResenaTs)) ?>"><?= $e($fechaResena) ?></time>
                    <?php endif; ?>
                </div>
                <?php if (trim((string) ($resena['comentario'] ?? '')) !== ''): ?>
                <p class="resena-item-texto"><?= nl2br($e((string) $resena['comentario'])) ?></p>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="resenas-vacio">Aún no hay opiniones. ¡Tu experiencia puede ayudar a otros clientes!</p>
        <?php endif; ?>
    </section>
</section>

<script>
window.JT_PRODUCTO = <?= json_encode($datosJS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="js/producto.js"></script>
