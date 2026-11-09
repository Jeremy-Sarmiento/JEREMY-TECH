<?php
/** Vista: Resumen del panel (admin o vendedor — Etapa 6). */
$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Los datos ya se consultaron en AdminController::dashboard().
$totales = $datos['totales'];
$ultimos = $datos['ultimos'];
$esVendedor = $datos['vendedor'];
$titulo = $esVendedor ? 'Resumen del vendedor' : 'Resumen general';
$nota = $esVendedor
    ? 'Tu actividad en el marketplace: productos publicados y ventas canalizadas.'
    : 'Estado de la tienda en un vistazo.';
$tarjetas = [
    ['tarjeta-cyan', $totales['productos'], 'Productos'],
    ['tarjeta-azul', $totales['pedidos'], 'Pedidos'],
    ['tarjeta-ambar', $totales['pendientes'], 'Pendientes'],
];
if ($esVendedor) {
    $tarjetas[] = ['tarjeta-verde', number_format($totales['ventas'], 2), 'Ventas (S/)'];
    $tarjetas[] = ['tarjeta-verde', number_format($totales['ganancias'], 2), 'Ganancias (S/)'];
    $tarjetas[] = ['tarjeta-rojo', number_format($totales['saldo'], 2), 'Por liquidar (S/)'];
} else {
    $tarjetas[] = ['tarjeta-verde', $totales['usuarios'], 'Usuarios'];
    $tarjetas[] = ['tarjeta-rojo', $totales['cupones'], 'Cupones'];
    $tarjetas[] = ['tarjeta-azul', $totales['vendedores'], 'Vendedores'];
}
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo"><?= $e($titulo) ?></h2>
            <p class="admin-nota"><?= $e($nota) ?></p>
        </div>
    </div>

    <div class="tarjetas">
        <?php foreach ($tarjetas as [$clase, $numero, $etiqueta]): ?>
        <div class="tarjeta <?= $e($clase) ?>">
            <span class="tarjeta-numero"><?= $e($numero) ?></span>
            <span class="tarjeta-etiqueta"><?= $e($etiqueta) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <h3 class="admin-subtitulo"><?= $esVendedor ? 'Pedidos con tus productos' : 'Últimos pedidos' ?></h3>
    <div class="admin-tabla-wrap" tabindex="0">
        <table class="tabla-admin">
            <thead>
                <tr><th>#</th><th>Cliente</th><th>Total (S/)</th><th>Estado</th><th>Fecha</th></tr>
            </thead>
            <tbody>
            <?php if (!$ultimos): ?>
                <tr><td colspan="5">Todavía no hay pedidos que mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($ultimos as $p): ?>
                <?php $claseEstado = match ($p['estado'] ?? '') {
                    'Pendiente' => 'etiqueta etiqueta-aviso',
                    'Pagado'    => 'etiqueta etiqueta-info',
                    'Enviado'   => 'etiqueta etiqueta-info',
                    'Entregado' => 'etiqueta etiqueta-ok',
                    'Cancelado' => 'etiqueta etiqueta-off',
                    default     => 'etiqueta',
                }; ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= $e($p['nombre'] ?? '') ?> <?= $e($p['apellidos'] ?? '') ?></td>
                    <td><?= number_format((float) ($p['total_pen'] ?? 0), 2) ?></td>
                    <td><span class="<?= $claseEstado ?>"><?= $e($p['estado'] ?? '') ?></span></td>
                    <td><?= $e($p['creado_en'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
