<?php
$pdo = conexion();

// (Etapa 6) Un vendedor solo ve y edita SUS productos.
$esVendedor = \Model\Administrador::esVendedor();
$vendedorActual = $esVendedor ? \Model\Administrador::idSesion() : null;
[$alcance, $paramsAlcance] = \Model\Producto::alcancePanel();

$palabra = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT p.*, m.nombre AS marca_nombre, g.nombre AS garantia_nombre,
               v.name AS vendedor_nombre, v.verificado AS vendedor_verificado
        FROM productos p
        LEFT JOIN marcas m ON m.id = p.marca_id
        LEFT JOIN garantias g ON g.id = p.garantia_id
        LEFT JOIN users v ON v.id = p.vendedor_id AND v.deleted_at IS NULL
       WHERE 1=1 ' . $alcance;
$params = $paramsAlcance;
if ($palabra !== '') {
    $sql .= ' AND p.nombre LIKE ?';
    $params[] = '%' . $palabra . '%';
}
$sql .= ' ORDER BY p.nombre ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT slug, nombre FROM categorias ORDER BY nombre')->fetchAll();
$marcas = $pdo->query('SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre')->fetchAll();
$garantias = $pdo->query('SELECT id, nombre FROM garantias WHERE activo = 1 ORDER BY nombre')->fetchAll();

// Precarga para edición (?editar=SLUG)
$editando = null;
$idEditar = (string) ($_GET['editar'] ?? '');
if ($idEditar !== '') {
    // Alias `p` porque el alcance del vendedor se escribe con `p.vendedor_id`.
    $q = $pdo->prepare('SELECT * FROM productos p WHERE p.slug = ?' . $alcance . ' LIMIT 1');
    $q->execute(array_merge([$idEditar], $paramsAlcance));
    $editando = $q->fetch() ?: null;
}

$vendedores = $esVendedor ? [] : \Model\Producto::vendedores();

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo"><?= $esVendedor ? 'Mis productos' : 'Productos' ?></h2>
            <p class="admin-nota"><?= $esVendedor
                ? 'Los productos que tú publicas en el marketplace.'
                : 'Catálogo visible en la tienda.' ?></p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nuevo producto</button>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/productos">
        <input type="text" name="q" value="<?= $e($palabra) ?>" placeholder="Buscar producto...">
        <button type="submit" class="btn">Buscar</button>
        <a class="btn btn-claro" href="?ruta=admin/productos">Limpiar</a>
    </form>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/productos/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar producto' : 'Nuevo producto' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['slug'] ?? '') ?>">

                <label>Nombre
                    <input type="text" name="nombre" required value="<?= $e($editando['nombre'] ?? '') ?>">
                </label>
                <label>Modelo
                    <input type="text" name="modelo" value="<?= $e($editando['modelo'] ?? '') ?>" placeholder="Opcional">
                </label>
                <label>Categoría
                    <select name="categoria" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($categorias as $c): $catActual = (string) ($editando['categoria'] ?? ''); ?>
                            <option value="<?= $e($c['slug']) ?>" <?= $catActual === $c['slug'] || $catActual === $c['nombre'] ? 'selected' : '' ?>>
                                <?= $e($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Marca
                    <select name="marca_id">
                        <option value="">Sin marca</option>
                        <?php foreach ($marcas as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" <?= (int)($editando['marca_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                                <?= $e($m['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Garantía
                    <select name="garantia_id">
                        <option value="">Sin garantía</option>
                        <?php foreach ($garantias as $g): ?>
                            <option value="<?= (int) $g['id'] ?>" <?= (int)($editando['garantia_id'] ?? 0) === (int)$g['id'] ? 'selected' : '' ?>>
                                <?= $e($g['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if (!$esVendedor): ?>
                <label>Vendedor (dueño de la publicación)
                    <select name="vendedor_id">
                        <option value="">La tienda (plataforma)</option>
                        <?php foreach ($vendedores as $v): ?>
                            <option value="<?= (int) $v['id'] ?>" <?= (int) ($editando['vendedor_id'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>>
                                <?= $e($v['name']) ?> — <?= $e($v['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <label>SKU (código único)
                    <input type="text" name="codigo_sku" value="<?= $e($editando['codigo_sku'] ?? '') ?>" placeholder="Opcional">
                </label>
                <label>Precio (S/)
                    <input type="number" step="0.01" min="0" name="precio_pen" required value="<?= $e($editando['precio_pen'] ?? '0') ?>">
                </label>
                <label>Stock
                    <input type="number" min="0" name="stock" value="<?= $e($editando['stock'] ?? '0') ?>">
                </label>
                <label>Stock mínimo (alerta de reposición)
                    <input type="number" min="0" name="stock_minimo" value="<?= $e($editando['stock_minimo'] ?? '5') ?>">
                </label>
                <label>Peso (kg)
                    <input type="number" step="0.001" min="0" name="peso_kg" value="<?= $e($editando['peso_kg'] ?? '') ?>" placeholder="Opcional">
                </label>
                <label>Dimensiones
                    <input type="text" name="dimensiones" value="<?= $e($editando['dimensiones'] ?? '') ?>" placeholder="Ej. 15.5 x 7.6 x 0.8 cm">
                </label>
                <label>Especificaciones
                    <input type="text" name="especificaciones" value="<?= $e($editando['especificaciones'] ?? '') ?>">
                </label>
                <label>Colores (separados por coma)
                    <input type="text" name="colores" value="<?= $e($editando['colores'] ?? '') ?>">
                </label>
                <label>Imagen (ruta)
                    <input type="text" name="imagen" value="<?= $e($editando['imagen'] ?? '') ?>">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= !isset($editando['activo']) || $editando['activo'] ? 'checked' : '' ?>>
                    Activo (visible en tienda)
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar cambios' : 'Crear producto' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/productos">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <!-- Listado -->
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr><th>ID (slug)</th><th>Nombre</th><th>Categoría</th><th>Vendedor</th><th>Marca</th><th>Garantía</th><th>SKU</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($productos as $p): $stockBajo = (int) $p['stock_minimo'] > 0 && (int) $p['stock'] <= (int) $p['stock_minimo']; ?>
                    <tr>
                        <td><code><?= $e($p['slug']) ?></code></td>
                        <td>
                            <?= $e($p['nombre']) ?>
                            <?php if (!empty($p['modelo'])): ?><br><small class="admin-secundario"><?= $e($p['modelo']) ?></small><?php endif; ?>
                        </td>
                        <td><?= $e($p['categoria']) ?></td>
                        <td>
                            <?php if (!empty($p['vendedor_nombre'])): ?>
                                <?= $e($p['vendedor_nombre']) ?>
                                <?php if ((int) ($p['vendedor_verificado'] ?? 0) === 1): ?>
                                    <br><span class="etiqueta etiqueta-ok">Verificado</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="admin-secundario">La tienda</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $e($p['marca_nombre'] ?? '—') ?></td>
                        <td><?= $e($p['garantia_nombre'] ?? '—') ?></td>
                        <td><?= $p['codigo_sku'] ? '<code>' . $e($p['codigo_sku']) . '</code>' : '—' ?></td>
                        <td>S/ <?= number_format((float) $p['precio_pen'], 2) ?></td>
                        <td>
                            <?= (int) $p['stock'] ?>
                            <?php if ($stockBajo): ?><br><span class="etiqueta etiqueta-aviso">Stock bajo (<?= (int) $p['stock_minimo'] ?>)</span><?php endif; ?>
                        </td>
                        <td>
                            <span class="etiqueta <?= (int) $p['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>">
                                <?= (int) $p['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/productos&editar=<?= urlencode((string) $p['slug']) ?>">Editar</a>
                            <a class="btn-mini" href="?ruta=admin/productos/estado&id=<?= urlencode((string) $p['slug']) ?>"><?= (int) $p['activo'] ? 'Ocultar' : 'Mostrar' ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$productos): ?>
                    <tr><td colspan="11" class="admin-vacio">No hay productos que coincidan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
