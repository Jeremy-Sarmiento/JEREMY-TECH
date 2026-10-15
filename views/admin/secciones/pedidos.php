<?php
$pdo = conexion();

// (Etapa 6) El vendedor solo ve los pedidos que llevan sus líneas y, dentro
// de ellos, solo las líneas suyas.
$esVendedor = \Model\Administrador::esVendedor();
[$joinPedidos, $wherePedidos, $whereDetalle, $paramsDetalle] = \Model\Vendedor::alcancePanelPedidos();

$filtro = (string) ($_GET['estado'] ?? '');
$estados = ['Pendiente', 'Pagado', 'Enviado', 'Entregado', 'Cancelado'];

$sqlPedidos = 'SELECT ' . ($joinPedidos !== '' ? 'DISTINCT ' : '')
    . 'p.*, c.name AS nombre, c.apellidos
       FROM pedidos p
       JOIN users c ON c.id = p.cliente_id'
    . $joinPedidos
    . ' WHERE ' . $wherePedidos;
$paramsPedidos = $paramsDetalle;
if ($filtro !== '') {
    $sqlPedidos .= ' AND p.estado = ?';
    $paramsPedidos[] = $filtro;
}
$sqlPedidos .= ' ORDER BY p.id DESC';
$stmt = $pdo->prepare($sqlPedidos);
$stmt->execute($paramsPedidos);
$pedidos = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT pedido_id, cantidad, variante_nombre, producto_nombre, precio_unitario_pen
       FROM detalle_pedido dp
      WHERE ' . $whereDetalle
);
$stmt->execute($paramsDetalle);
$det = $stmt->fetchAll();
$mapa = [];
foreach ($det as $d) {
    $mapa[(int) $d['pedido_id']][] = $d;
}

// (Etapa 6) El total del pedido incluye líneas de otros vendedores: para un
// vendedor se muestra solo lo que él facturó en ese pedido.
$miParte = [];
if ($esVendedor) {
    $vendedorId = \Model\Administrador::idSesion();
    if ($vendedorId !== null) {
        $q = $pdo->prepare('SELECT pedido_id, monto_venta FROM comisiones WHERE vendedor_id = ?');
        $q->execute([$vendedorId]);
        foreach ($q->fetchAll() as $c) {
            $miParte[(int) $c['pedido_id']] = (float) $c['monto_venta'];
        }
    }
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Pedidos</h2>
            <p class="admin-nota"><?= $esVendedor
                ? 'Pedidos que incluyen al menos un producto tuyo.'
                : 'Historial de compras y su estado.' ?></p>
        </div>
        <div class="admin-encabezado-acciones">
            <form method="get" class="admin-filtro" style="margin-bottom:0;">
                <input type="hidden" name="ruta" value="admin/pedidos">
                <select name="estado">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= $e($est) ?>" <?= $filtro === $est ? 'selected' : '' ?>><?= $e($est) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Filtrar</button>
            </form>
        </div>
    </div>

    <?php $clasesEstado = [
        'Pendiente' => 'etiqueta etiqueta-aviso',
        'Pagado'    => 'etiqueta etiqueta-info',
        'Enviado'   => 'etiqueta etiqueta-info',
        'Entregado' => 'etiqueta etiqueta-ok',
        'Cancelado' => 'etiqueta etiqueta-off',
    ]; ?>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead><tr><th>ID</th><th>Cliente</th><th>Detalle</th>
                <th><?= $esVendedor ? 'Tu parte (S/)' : 'Total (S/)' ?></th>
                <th>Estado</th><th>Cambiar estado</th></tr></thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= $e($p['nombre']) ?> <?= $e($p['apellidos']) ?></td>
                    <td class="admin-detalle">
                        <?php foreach ($mapa[(int) $p['id']] ?? [] as $d): ?>
                            <div><?= (int) $d['cantidad'] ?>x <?= $e($d['producto_nombre']) ?><?= $d['variante_nombre'] ? ' (' . $e($d['variante_nombre']) . ')' : '' ?></div>
                        <?php endforeach; ?>
                        <?php if (empty($mapa[(int) $p['id']])): ?><em>sin detalle</em><?php endif; ?>
                    </td>
                    <td><?= number_format(
                        $esVendedor
                            ? (float) ($miParte[(int) $p['id']] ?? 0)
                            : (float) $p['total_pen'],
                        2
                    ) ?></td>
                    <td><span class="<?= \Model\Pedido::claseEstado((string) $p['estado']) ?>"><?= $e($p['estado']) ?></span></td>
                    <td>
                        <?php $destinos = \Model\Pedido::destinos((string) $p['estado']); ?>
                        <?php if ($destinos === []): ?>
                            <span class="admin-nota">Estado final</span>
                        <?php else: ?>
                        <form method="post" action="?ruta=admin/pedidos/estado" class="admin-inline-form">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <select name="estado">
                                <?php foreach ($destinos as $est): ?>
                                    <option value="<?= $e($est) ?>"><?= $e($est) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-mini">OK</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pedidos): ?>
                <tr><td colspan="6" class="admin-vacio">No hay pedidos para este filtro.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
