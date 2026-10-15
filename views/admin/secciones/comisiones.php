<?php
/**
 * Vista: Comisiones globales del marketplace (solo administrador) — Etapa 6.
 * Una fila por pedido y vendedor, con el reparto congelado al comprar.
 */
$pdo = conexion();

$filtroVendedor = (int) ($_GET['vendedor'] ?? 0);
$sql = 'SELECT c.*, p.estado, p.creado_en AS pedido_fecha,
               u.name AS vendedor_nombre, u.usuario AS vendedor_usuario
          FROM comisiones c
          JOIN pedidos p ON p.id = c.pedido_id
          JOIN users u ON u.id = c.vendedor_id
         WHERE 1=1';
$params = [];
if ($filtroVendedor > 0) {
    $sql .= ' AND c.vendedor_id = ?';
    $params[] = $filtroVendedor;
}
$sql .= ' ORDER BY c.id DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comisiones = $stmt->fetchAll();

$vendedores = \Model\Vendedor::todos();

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$s = static fn (float $m): string => number_format($m, 2);

$totalVenta    = 0.0;
$totalComision = 0.0;
$totalVendedor = 0.0;
foreach ($comisiones as $c) {
    if ($c['estado'] === 'Cancelado' || $c['estado'] === 'Pendiente') {
        continue;
    }
    $totalVenta    += (float) $c['monto_venta'];
    $totalComision += (float) $c['monto_comision'];
    $totalVendedor += (float) $c['monto_vendedor'];
}
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Comisiones</h2>
            <p class="admin-nota">
                Reparto por línea de pedido: la comisión se calcula sobre la base sin IGV
                con el porcentaje vigente del vendedor en el momento de la compra.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <form method="get" class="admin-filtro" style="margin-bottom:0;">
                <input type="hidden" name="ruta" value="admin/comisiones">
                <select name="vendedor">
                    <option value="0">Todos los vendedores</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?= (int) $v['id'] ?>" <?= $filtroVendedor === (int) $v['id'] ? 'selected' : '' ?>>
                            <?= $e($v['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Filtrar</button>
                <a class="btn btn-claro" href="?ruta=admin/comisiones">Limpiar</a>
            </form>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= count($comisiones) ?></span>
            <span class="tarjeta-etiqueta">Registros</span>
        </div>
        <div class="tarjeta tarjeta-azul">
            <span class="tarjeta-numero"><?= $s($totalVenta) ?></span>
            <span class="tarjeta-etiqueta">Ventas cobradas (S/)</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= $s($totalComision) ?></span>
            <span class="tarjeta-etiqueta">Para la plataforma (S/)</span>
        </div>
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero"><?= $s($totalVendedor) ?></span>
            <span class="tarjeta-etiqueta">Para vendedores (S/)</span>
        </div>
    </div>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th># Pedido</th><th>Fecha</th><th>Estado</th><th>Vendedor</th>
                        <th>Venta (S/)</th><th>%</th><th>Comisión (S/)</th><th>Vendedor recibe (S/)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$comisiones): ?>
                    <tr><td colspan="8" class="admin-vacio">
                        Todavía no hay comisiones registradas: aparecen automáticamente
                        cuando un pedido incluye productos de un vendedor.
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($comisiones as $c): ?>
                    <?php $claseEstado = match ($c['estado'] ?? '') {
                        'Pendiente' => 'etiqueta etiqueta-aviso',
                        'Pagado'    => 'etiqueta etiqueta-info',
                        'Enviado'   => 'etiqueta etiqueta-info',
                        'Entregado' => 'etiqueta etiqueta-ok',
                        'Cancelado' => 'etiqueta etiqueta-off',
                        default     => 'etiqueta',
                    }; ?>
                    <tr>
                        <td><strong>#<?= (int) $c['pedido_id'] ?></strong></td>
                        <td><?= $e($c['pedido_fecha']) ?></td>
                        <td><span class="<?= $claseEstado ?>"><?= $e($c['estado']) ?></span></td>
                        <td><?= $e($c['vendedor_nombre']) ?><br><small class="admin-secundario">@<?= $e($c['vendedor_usuario']) ?></small></td>
                        <td><?= $s((float) $c['monto_venta']) ?></td>
                        <td><?= $e($c['porcentaje_comision']) ?> %</td>
                        <td><?= $s((float) $c['monto_comision']) ?></td>
                        <td><?= $s((float) $c['monto_vendedor']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
