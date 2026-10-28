<?php
/**
 * Vista: Inventario — historial de movimientos y ajustes manuales.
 *
 * El stock se modifica SOLO a través de `Inventario::ajustar()`, que escribe
 * el movimiento en la misma transacción. La vista no hace UPDATE directos.
 */
use Model\Inventario;

$buscar     = trim((string) ($_GET['q'] ?? ''));
$tipoFiltro = (string) ($_GET['tipo'] ?? '');
$productoId = (int) ($_GET['producto'] ?? 0);

$movimientos    = Inventario::historial($buscar, $tipoFiltro, 200);
$productos      = Inventario::buscarProductos($buscar, 40);
$productoAjuste = $productoId > 0 ? Inventario::productoConVariantes($productoId) : null;
$variantes      = $productoAjuste['variantes'] ?? [];

$entradas = 0;
$salidas  = 0;
foreach ($movimientos as $m) {
    if ($m['tipo'] === 'entrada') {
        $entradas += (int) $m['cantidad'];
    } else {
        $salidas += (int) $m['cantidad'];
    }
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static function (string $valor): string {
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Inventario</h2>
            <p class="admin-nota">
                Historial de entradas y salidas generadas por ventas, cancelaciones y
                ajustes manuales. Cada ajuste actualiza el stock y deja un movimiento
                auditable en la misma transacción.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/productos">Ver productos</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= count($movimientos) ?></span>
            <span class="tarjeta-etiqueta">Movimientos listados</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero">+<?= $entradas ?></span>
            <span class="tarjeta-etiqueta">Unidades de entrada</span>
        </div>
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero">−<?= $salidas ?></span>
            <span class="tarjeta-etiqueta">Unidades de salida</span>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/inventario">
        <input type="text" name="q" value="<?= $e($buscar) ?>"
               placeholder="Buscar producto, slug o motivo...">
        <select name="tipo">
            <option value="">Todos los movimientos</option>
            <option value="entrada" <?= $tipoFiltro === 'entrada' ? 'selected' : '' ?>>Solo entradas</option>
            <option value="salida" <?= $tipoFiltro === 'salida' ? 'selected' : '' ?>>Solo salidas</option>
        </select>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-claro" href="?ruta=admin/inventario">Limpiar</a>
        <span class="admin-nota" style="margin:0;">La búsqueda también filtra la lista de productos para ajustar.</span>
    </form>

    <div class="admin-grid-inventario">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>Fecha</th><th>Producto</th><th>Tipo</th>
                        <th>Cantidad</th><th>Motivo</th><th>Pedido</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="6" class="admin-vacio">
                        No hay movimientos para este filtro. Los ajustes manuales que
                        registres aparecerán aquí.
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($movimientos as $m): ?>
                    <tr>
                        <td><?= $e($fecha((string) ($m['creado_en'] ?? ''))) ?></td>
                        <td>
                            <?= $e($m['producto_nombre']) ?>
                            <small class="admin-secundario"><?= $e($m['producto_slug'] ?? '') ?></small>
                            <?php if ((string) ($m['nombre_variante'] ?? '') !== ''): ?>
                                <small class="admin-secundario">Variante: <?= $e($m['nombre_variante']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="etiqueta <?= $m['tipo'] === 'entrada' ? 'etiqueta-ok' : 'etiqueta-aviso' ?>">
                                <?= $e(ucfirst((string) $m['tipo'])) ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $m['tipo'] === 'entrada' ? '+' : '−' ?><?= (int) $m['cantidad'] ?></strong>
                        </td>
                        <td class="admin-detalle"><?= $e($m['motivo']) ?></td>
                        <td>
                            <?php if ((int) ($m['pedido_id'] ?? 0) > 0): ?>
                                <a href="?ruta=admin/pedidos">#<?= (int) $m['pedido_id'] ?></a>
                            <?php else: ?>
                                <span class="admin-secundario">Ajuste manual</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <aside class="admin-formulario">
            <h3>Ajuste manual de stock</h3>

            <?php if ($productoAjuste === null): ?>
                <p class="admin-nota" style="margin:8px 0 12px;">
                    Elige un producto de la lista para registrar una entrada o salida.
                    Usa la búsqueda de arriba si no lo encuentras.
                </p>
                <div class="admin-tabla-wrap">
                    <table class="tabla-admin">
                        <thead><tr><th>Producto</th><th>Stock</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$productos): ?>
                            <tr><td colspan="3" class="admin-vacio">Sin productos para esa búsqueda.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <?= $e($p['nombre']) ?>
                                    <small class="admin-secundario"><?= $e($p['slug']) ?></small>
                                    <?php if (!(int) $p['activo']): ?>
                                        <small class="admin-secundario">Inactivo</small>
                                    <?php endif; ?>
                                    <?php if ((int) $p['stock_minimo'] > 0 && (int) $p['stock'] <= (int) $p['stock_minimo']): ?>
                                        <small class="admin-secundario" style="color:var(--jt-peligro);">
                                            Bajo mínimo (<?= (int) $p['stock_minimo'] ?>)
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $p['stock'] ?></td>
                                <td>
                                    <a class="btn-mini"
                                       href="?ruta=admin/inventario&amp;producto=<?= (int) $p['id'] ?>">Ajustar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-detalle" style="margin:8px 0 4px;">
                    <strong><?= $e($productoAjuste['nombre']) ?></strong>
                    <span class="admin-secundario"><?= $e($productoAjuste['slug']) ?></span>
                    <span class="admin-secundario">Stock actual: <?= (int) $productoAjuste['stock'] ?> unidad(es)</span>
                    <?php if ((int) $productoAjuste['stock_minimo'] > 0
                        && (int) $productoAjuste['stock'] <= (int) $productoAjuste['stock_minimo']): ?>
                        <span class="admin-secundario" style="color:var(--jt-peligro);">
                            Bajo el mínimo de <?= (int) $productoAjuste['stock_minimo'] ?> unidad(es)
                        </span>
                    <?php endif; ?>
                </div>

                <form method="post" action="?ruta=admin/inventario/ajustar">
                    <?= \Security\Proteccion::campo() ?>
                    <input type="hidden" name="producto_id" value="<?= (int) $productoAjuste['id'] ?>">

                    <?php if ($variantes !== []): ?>
                        <label>Variante
                            <select name="variante_id" required>
                                <option value="">Selecciona una variante...</option>
                                <?php foreach ($variantes as $v): ?>
                                    <option value="<?= (int) $v['id'] ?>">
                                        <?= $e($v['nombre_variante']) ?> · stock <?= (int) $v['stock'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <p class="admin-nota" style="margin:6px 0 0;">
                            Se ajustan el producto y la variante a la vez para que el
                            stock siga siendo coherente con la venta.
                        </p>
                    <?php else: ?>
                        <input type="hidden" name="variante_id" value="0">
                        <p class="admin-nota" style="margin:8px 0 0;">
                            Este producto no tiene variantes activas: el ajuste se aplica
                            directamente a su stock.
                        </p>
                    <?php endif; ?>

                    <label>Tipo de ajuste
                        <select name="tipo" required>
                            <option value="entrada">Entrada (+ stock)</option>
                            <option value="salida">Salida (− stock)</option>
                        </select>
                    </label>

                    <label>Cantidad
                        <input type="number" name="cantidad" min="1" max="100000" step="1"
                               value="1" required>
                    </label>

                    <label>Motivo
                        <input type="text" name="motivo" maxlength="160" required
                               placeholder="Ej. reposición de proveedor, merma, conteo físico">
                    </label>

                    <div style="display:flex;gap:8px;margin-top:18px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primario">Registrar ajuste</button>
                        <a class="btn btn-claro" href="?ruta=admin/inventario">Cancelar</a>
                    </div>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</section>
