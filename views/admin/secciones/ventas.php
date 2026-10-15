<?php
/**
 * Vista: Mis ventas (vendedor) — Etapa 6.
 * `renderizar()` no hace extract(), así que la vista calcula sus propios
 * datos a partir del vendedor con la sesión abierta.
 */
// `renderizar()` no hace extract(): la vista calcula sus propios datos.
$vendedorId    = \Model\Administrador::idSesion() ?? 0;
$ventas        = \Model\Vendedor::ventasDe($vendedorId);
$liquidaciones = \Model\Vendedor::liquidacionesDe($vendedorId);
$ganancias     = \Model\Vendedor::gananciasDe($vendedorId);
$liquidado     = \Model\Vendedor::liquidadoDe($vendedorId);
$saldo         = \Model\Vendedor::saldoPendiente($vendedorId);

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$s = static fn (float $m): string => number_format($m, 2);

$totalVentas = 0.0;
foreach ($ventas as $v) {
    $totalVentas += (float) $v['monto_venta'];
}
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Mis ventas</h2>
            <p class="admin-nota">
                Reparto de cada pedido que incluye tus productos. El porcentaje se aplica
                sobre la base sin IGV y queda congelado en el momento de la compra.
            </p>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-azul">
            <span class="tarjeta-numero"><?= count($ventas) ?></span>
            <span class="tarjeta-etiqueta">Ventas</span>
        </div>
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= $s($totalVentas) ?></span>
            <span class="tarjeta-etiqueta">Facturado (S/)</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= $s((float) $ganancias) ?></span>
            <span class="tarjeta-etiqueta">Ganancias (S/)</span>
        </div>
        <div class="tarjeta tarjeta-rojo">
            <span class="tarjeta-numero"><?= $s((float) $saldo) ?></span>
            <span class="tarjeta-etiqueta">Por liquidar (S/)</span>
        </div>
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero"><?= $s((float) $liquidado) ?></span>
            <span class="tarjeta-etiqueta">Liquidado (S/)</span>
        </div>
    </div>

    <h3 class="admin-subtitulo">Historial de ventas</h3>
    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th># Pedido</th><th>Fecha</th><th>Estado</th>
                    <th>Venta (S/)</th><th>Comisión</th><th>Comisión (S/)</th><th>Me corresponde (S/)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$ventas): ?>
                <tr><td colspan="7" class="admin-vacio">Todavía no has vendido nada.</td></tr>
            <?php endif; ?>
            <?php foreach ($ventas as $v): ?>
                <?php $claseEstado = match ($v['estado'] ?? '') {
                    'Pendiente' => 'etiqueta etiqueta-aviso',
                    'Pagado'    => 'etiqueta etiqueta-info',
                    'Enviado'   => 'etiqueta etiqueta-info',
                    'Entregado' => 'etiqueta etiqueta-ok',
                    'Cancelado' => 'etiqueta etiqueta-off',
                    default     => 'etiqueta',
                }; ?>
                <tr>
                    <td><strong>#<?= (int) $v['pedido_id'] ?></strong></td>
                    <td><?= $e($v['creado_en']) ?></td>
                    <td><span class="<?= $claseEstado ?>"><?= $e($v['estado']) ?></span></td>
                    <td><?= $s((float) $v['monto_venta']) ?></td>
                    <td><?= $e($v['porcentaje_comision']) ?> %</td>
                    <td><?= $s((float) $v['monto_comision']) ?></td>
                    <td><strong><?= $s((float) $v['monto_vendedor']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3 class="admin-subtitulo">Liquidaciones recibidas</h3>
    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr><th>Fecha</th><th>Monto (S/)</th><th>Método</th><th>Referencia</th></tr>
            </thead>
            <tbody>
            <?php if (!$liquidaciones): ?>
                <tr><td colspan="4" class="admin-vacio">Aún no se te ha liquidado nada.</td></tr>
            <?php endif; ?>
            <?php foreach ($liquidaciones as $l): ?>
                <tr>
                    <td><?= $e($l['creado_en']) ?></td>
                    <td><?= $s((float) $l['monto']) ?></td>
                    <td><?= $e($l['metodo']) ?></td>
                    <td><?= $l['referencia'] !== '' ? $e($l['referencia']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
