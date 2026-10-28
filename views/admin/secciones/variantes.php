<?php
use Model\Producto;
use Model\ProductoVariante;
use Model\PrecioVolumen;

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $m): string => number_format($m, 2);

/**
 * Fila del producto sobre el que se trabaja (?p=SLUG, o el primero del
 * alcance del rol). Un vendedor no debe poder gestionar un producto ajeno
 * ni siquiera por URL.
 */
$pdo = conexion();
[$alcance, $params] = Producto::alcancePanel();

$slug = trim((string) ($_GET['p'] ?? ''));
$producto = null;
if ($slug !== '') {
    $q = $pdo->prepare('SELECT * FROM productos WHERE slug = ? LIMIT 1');
    $q->execute([$slug]);
    $candidato = $q->fetch() ?: null;
    if ($candidato !== null && Producto::puedeGestionar($slug)) {
        $producto = $candidato;
    }
}
if ($producto === null) {
    $q = $pdo->prepare('SELECT p.* FROM productos p WHERE 1=1 ' . $alcance . ' ORDER BY p.nombre ASC LIMIT 1');
    $q->execute($params);
    $producto = $q->fetch() ?: null;
}

$catalogo = [];
$qCat = $pdo->prepare('SELECT p.id, p.slug, p.nombre, p.precio_pen, p.activo
                         FROM productos p WHERE 1=1 ' . $alcance . ' ORDER BY p.nombre ASC');
$qCat->execute($params);
$catalogo = $qCat->fetchAll();

$variantes = [];
$tramos = [];
if ($producto !== null) {
    $variantes = ProductoVariante::todasDeProducto((int) $producto['id']);
    $tramos    = PrecioVolumen::deProducto((int) $producto['id']);
}

// Ediciones en curso (?editar-variante=ID / ?editar-tramo=ID).
$editandoVariante = null;
$idVariante = (int) ($_GET['editar-variante'] ?? 0);
if ($idVariante > 0) {
    $editandoVariante = ProductoVariante::porId($idVariante);
    // Una variante de otro producto no se puede editar por URL.
    if ($editandoVariante !== null && $producto !== null
        && (int) $editandoVariante['productoId'] !== (int) $producto['id']) {
        $editandoVariante = null;
    }
}
$editandoTramo = null;
$idTramo = (int) ($_GET['editar-tramo'] ?? 0);
if ($idTramo > 0) {
    $editandoTramo = PrecioVolumen::porId($idTramo);
    if ($editandoTramo !== null && $producto !== null
        && (int) $editandoTramo['productoId'] !== (int) $producto['id']) {
        $editandoTramo = null;
    }
}
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Variantes y precios por volumen</h2>
            <p class="admin-nota">
                El precio que paga el cliente se calcula así:
                <strong>precio_pen + precio_extra</strong> de la variante elegida.
                Si la cantidad cae dentro de un tramo, el tramo sustituye el
                precio base y el extra se suma encima.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <button type="button" class="btn btn-primario" data-modal-abrir="modalVariante">Nueva variante</button>
            <button type="button" class="btn btn-claro" data-modal-abrir="modalTramo">Nuevo tramo</button>
        </div>
    </div>

    <form method="get" action="" class="admin-filtro-producto">
        <input type="hidden" name="ruta" value="admin/variantes">
        <label>Producto
            <select name="p" onchange="this.form.submit()">
                <?php foreach ($catalogo as $c): ?>
                    <option value="<?= $e($c['slug']) ?>"<?= $producto !== null && (int) $c['id'] === (int) $producto['id'] ? ' selected' : '' ?>>
                        <?= $e($c['nombre']) ?><?= (int) $c['activo'] ? '' : ' (inactivo)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <?php if ($producto === null): ?>
        <div class="admin-tarjetas-grid">
            <p class="admin-vacio">No hay productos en tu alcance para gestionar variantes.</p>
        </div>
    <?php else: ?>

    <dialog class="admin-modal" id="modalVariante"<?= $editandoVariante ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/variantes/variantes/guardar" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <div class="admin-modal-cabecera">
                <h3><?= $editandoVariante ? 'Editar variante' : 'Nueva variante' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="p" value="<?= $e($producto['slug']) ?>">
                <input type="hidden" name="id" value="<?= (int) ($editandoVariante['id'] ?? 0) ?>">
                <label>Nombre
                    <input type="text" name="nombre_variante" required
                           value="<?= $e($editandoVariante['nombreVariante'] ?? '') ?>"
                           placeholder="Ej. Titanio Azul">
                </label>
                <label>Precio extra (S/)
                    <input type="number" step="0.01" min="0" name="precio_extra" required
                           value="<?= $e($editandoVariante['precioExtra'] ?? '0') ?>">
                </label>
                <label>Stock
                    <input type="number" min="0" name="stock" required
                           value="<?= $e($editandoVariante['stock'] ?? 0) ?>">
                </label>
                <label>Color (hex, opcional)
                    <input type="text" name="color" value="<?= $e($editandoVariante['color'] ?? '') ?>" placeholder="#1F3A5F">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1"
                        <?= !isset($editandoVariante['activo']) || (int) ($editandoVariante['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
                    Visible en la ficha
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editandoVariante ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar
                        data-volver="?ruta=admin/variantes&p=<?= urlencode((string) $producto['slug']) ?>">Cancelar</button>
            </div>
        </form>
    </dialog>

    <dialog class="admin-modal" id="modalTramo"<?= $editandoTramo ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/variantes/volumen/guardar" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <div class="admin-modal-cabecera">
                <h3><?= $editandoTramo ? 'Editar tramo' : 'Nuevo tramo de volumen' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="p" value="<?= $e($producto['slug']) ?>">
                <input type="hidden" name="id" value="<?= (int) ($editandoTramo['id'] ?? 0) ?>">
                <label>Cantidad mínima
                    <input type="number" min="1" name="cantidad_minima" required
                           value="<?= $e($editandoTramo['cantidadMinima'] ?? 2) ?>">
                </label>
                <label>Cantidad máxima (vacío = "desde N en adelante")
                    <input type="number" min="1" name="cantidad_maxima"
                           value="<?= $e($editandoTramo['cantidadMaxima'] ?? '') ?>">
                </label>
                <label>Precio unitario (S/)
                    <input type="number" step="0.01" min="0" name="precio_unitario" required
                           value="<?= $e($editandoTramo['precioUnitario'] ?? '') ?>">
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editandoTramo ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar
                        data-volver="?ruta=admin/variantes&p=<?= urlencode((string) $producto['slug']) ?>">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <h3 class="admin-subtitulo">Variantes de <?= $e($producto['nombre']) ?></h3>
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Extra</th>
                        <th>Stock</th>
                        <th>Color</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($variantes as $v): ?>
                    <tr>
                        <td><?= (int) $v['id'] ?></td>
                        <td><?= $e($v['nombreVariante']) ?></td>
                        <td><?= (float) $v['precioExtra'] > 0 ? '+ S/ ' . $fmt((float) $v['precioExtra']) : '—' ?></td>
                        <td><?= (int) $v['stock'] ?></td>
                        <td>
                            <?php if ($v['color'] !== ''): ?>
                                <span class="color-dot" style="background:<?= $e($v['color']) ?>"></span>
                                <?= $e($v['color']) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><span class="etiqueta <?= (int) $v['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= (int) $v['activo'] ? 'Visible' : 'Oculta' ?></span></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/variantes&p=<?= urlencode((string) $producto['slug']) ?>&editar-variante=<?= (int) $v['id'] ?>">Editar</a>
                            <?= \Security\Proteccion::botonPost('admin/variantes/variantes/estado', ['id' => (int) $v['id'], 'p' => (string) $producto['slug']], (int) $v['activo'] ? 'Ocultar' : 'Mostrar') ?>
                            <?= \Security\Proteccion::botonPost('admin/variantes/variantes/eliminar', ['id' => (int) $v['id'], 'p' => (string) $producto['slug']], 'Eliminar', 'btn-mini btn-mini-peligro', '¿Eliminar esta variante?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$variantes): ?>
                    <tr><td colspan="7" class="admin-vacio">Este producto no tiene variantes: se vende tal cual está publicado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-tarjetas-grid">
        <h3 class="admin-subtitulo">Precios por volumen de <?= $e($producto['nombre']) ?></h3>
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Ahorro vs. base</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tramos as $t): $ahorro = (float) $producto['precio_pen'] - (float) $t['precioUnitario']; ?>
                    <tr>
                        <td><?= (int) $t['id'] ?></td>
                        <td><?= $e(PrecioVolumen::rangoTexto($t)) ?></td>
                        <td>S/ <?= $fmt((float) $t['precioUnitario']) ?></td>
                        <td><?= $ahorro > 0 ? 'S/ ' . $fmt($ahorro) : '—' ?></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/variantes&p=<?= urlencode((string) $producto['slug']) ?>&editar-tramo=<?= (int) $t['id'] ?>">Editar</a>
                            <?= \Security\Proteccion::botonPost('admin/variantes/volumen/eliminar', ['id' => (int) $t['id'], 'p' => (string) $producto['slug']], 'Eliminar', 'btn-mini btn-mini-peligro', '¿Eliminar este tramo?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tramos): ?>
                    <tr><td colspan="5" class="admin-vacio">Sin tramos: se cobra el precio publicado para cualquier cantidad.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</section>
