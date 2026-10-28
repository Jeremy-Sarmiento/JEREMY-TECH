<?php
/**
 * Vista: Analítica — dashboard de comportamiento en tienda (admin y vendedor).
 * `renderizar()` no hace extract(): la vista calcula sus propios datos.
 */
$desde = trim((string) ($_GET['desde'] ?? ''));
$hasta = trim((string) ($_GET['hasta'] ?? ''));
if ($desde === '') {
    $desde = date('Y-m-d', strtotime('-30 day'));
}
if ($hasta === '') {
    $hasta = date('Y-m-d');
}

$esVendedor = \Model\Administrador::esVendedor();
$vendedorId = $esVendedor ? (\Model\Administrador::idSesion() ?? 0) : null;

$metricas = \Model\Analitica::resumenEntre($desde, $hasta, $vendedorId);
$topProductos = \Model\Analitica::topProductosEntre($desde, $hasta, 10, $vendedorId);
$topPaginas = \Model\Analitica::topPaginasEntre($desde, $hasta, 10, $vendedorId);
$topBusquedas = \Model\Analitica::topBusquedasEntre($desde, $hasta, 10, $vendedorId);
$eventosPorTipo = \Model\Analitica::eventosPorTipoEntre($desde, $hasta, $vendedorId);
$chatbot = \Model\Analitica::chatbotEntre($desde, $hasta);

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $m): string => number_format($m, 2);

$tarjetas = [
    ['tarjeta tarjeta-cyan', (int) $metricas['visitas'], 'Visitas'],
    ['tarjeta tarjeta-azul', (int) $metricas['vistas_producto'], 'Vistas de producto'],
    ['tarjeta tarjeta-verde', (int) $metricas['busquedas'], 'Búsquedas'],
    ['tarjeta tarjeta-ambar', (int) $metricas['agregados_carrito'], 'Agregados al carrito'],
    ['tarjeta tarjeta-ambar', (int) $metricas['checkouts'], 'Checkouts'],
    ['tarjeta tarjeta-verde', (int) $metricas['compras'], 'Compras'],
    ['tarjeta tarjeta-rojo', (float) $metricas['ingresos_pen'], 'Ingresos (S/)'],
];
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Analítica</h2>
            <p class="admin-nota">
                <?= $esVendedor
                    ? 'Métricas de comportamiento para los productos del vendedor activo.'
                    : 'Comportamiento de los visitantes de la tienda: visitas, búsquedas, interacción, compras y preguntas al chatbot.' ?>
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/dashboard">Ver resumen</a>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/analitica">
        <label class="admin-secundario" for="analiticaDesde">Desde</label>
        <input id="analiticaDesde" type="date" name="desde" value="<?= $e($desde) ?>" required>
        <label class="admin-secundario" for="analiticaHasta">Hasta</label>
        <input id="analiticaHasta" type="date" name="hasta" value="<?= $e($hasta) ?>" required>
        <button type="submit" class="btn">Actualizar</button>
        <a class="btn btn-claro" href="?ruta=admin/analitica">Limpiar</a>
    </form>

    <div class="tarjetas">
        <?php foreach ($tarjetas as [$clase, $valor, $etiqueta]): ?>
            <div class="<?= $clase ?>">
                <span class="tarjeta-numero"><?= is_int($valor) ? $valor : $fmt((float) $valor) ?></span>
                <span class="tarjeta-etiqueta"><?= $e($etiqueta) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="tarjeta tarjeta-azul">
            <span class="tarjeta-numero"><?= (float) $metricas['tasa_conversion'] ?>%</span>
            <span class="tarjeta-etiqueta">Conversión</span>
        </div>
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= (int) array_sum($eventosPorTipo) ?></span>
            <span class="tarjeta-etiqueta">Eventos registrados</span>
        </div>
    </div>

    <div class="admin-grid-inventario">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr><th>Producto</th><th>Interacciones</th></tr>
                </thead>
                <tbody>
                <?php if (!$topProductos): ?>
                    <tr><td colspan="2" class="admin-vacio">Sin interacciones registradas en el periodo.</td></tr>
                <?php endif; ?>
                <?php foreach ($topProductos as $fila): ?>
                    <tr>
                        <td><?= $e($fila['etiqueta']) ?> <small class="admin-secundario"><?= $e($fila['clave']) ?></small></td>
                        <td><?= (int) $fila['interacciones'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr><th>Área</th><th>Preguntas</th></tr>
                </thead>
                <tbody>
                <tr><td>FAQ / respuesta administrada</td><td><?= (int) $chatbot['faq'] ?></td></tr>
                <tr><td>IA / function calling</td><td><?= (int) $chatbot['ia'] ?></td></tr>
                <tr><td>Fallback</td><td><?= (int) $chatbot['fallback'] ?></td></tr>
                <tr><td>Total</td><td><?= (int) $chatbot['total_preguntas'] ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-grid-listas">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr><th>Página</th><th>Visitas</th></tr>
                </thead>
                <tbody>
                <?php if (!$topPaginas): ?>
                    <tr><td colspan="2" class="admin-vacio">Sin visitas registradas en el periodo.</td></tr>
                <?php endif; ?>
                <?php foreach ($topPaginas as $fila): ?>
                    <tr>
                        <td><?= $e($fila['pagina']) ?></td>
                        <td><?= (int) $fila['visitas'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr><th>Término de búsqueda</th><th>Búsquedas</th></tr>
                </thead>
                <tbody>
                <?php if (!$topBusquedas): ?>
                    <tr><td colspan="2" class="admin-vacio">Sin búsquedas registradas en el periodo.</td></tr>
                <?php endif; ?>
                <?php foreach ($topBusquedas as $fila): ?>
                    <tr>
                        <td><?= $e($fila['consulta']) ?></td>
                        <td><?= (int) $fila['busquedas'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
