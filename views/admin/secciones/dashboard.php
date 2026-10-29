<?php
/** Vista: Resumen del panel (admin o vendedor — Etapa 6). */
$pdo = conexion();
$esVendedor = \Model\Administrador::esVendedor();
$vendedorId = $esVendedor ? \Model\Administrador::idSesion() : null;
$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$tarjetas = [];
$titulo   = 'Resumen general';
$nota     = 'Estado de la tienda en un vistazo.';

if ($esVendedor && $vendedorId !== null) {
    $titulo = 'Resumen del vendedor';
    $nota   = 'Tu actividad en el marketplace: productos publicados y ventas canalizadas.';

    $q = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE vendedor_id = ?');
    $q->execute([$vendedorId]);
    $productos = (int) $q->fetchColumn();

    $q = $pdo->prepare(
        "SELECT COUNT(DISTINCT p.id),
                COALESCE(SUM(CASE WHEN p.estado = 'Pendiente' THEN 1 ELSE 0 END), 0)
           FROM (SELECT DISTINCT p2.id, p2.estado
                   FROM pedidos p2
                   JOIN detalle_pedido dp2 ON dp2.pedido_id = p2.id
                  WHERE dp2.vendedor_id = ?) p"
    );
    $q->execute([$vendedorId]);
    [$pedidos, $pendientes] = $q->fetch(\PDO::FETCH_NUM) ?: [0, 0];

    $q = $pdo->prepare(
        "SELECT IFNULL(SUM(c.monto_venta), 0)
           FROM comisiones c
           JOIN pedidos p ON p.id = c.pedido_id
          WHERE c.vendedor_id = ? AND p.estado NOT IN ('Cancelado', 'Pendiente')"
    );
    $q->execute([$vendedorId]);
    $ventas = (float) $q->fetchColumn();

    $ganancias = \Model\Vendedor::gananciasDe((int) $vendedorId);
    $saldo     = \Model\Vendedor::saldoPendiente((int) $vendedorId);

    $tarjetas = [
        ['tarjeta-cyan', $productos, 'Productos'],
        ['tarjeta-azul', (int) $pedidos, 'Pedidos'],
        ['tarjeta-ambar', (int) $pendientes, 'Pendientes'],
        ['tarjeta-verde', number_format($ventas, 2), 'Ventas (S/)'],
        ['tarjeta-verde', number_format($ganancias, 2), 'Ganancias (S/)'],
        ['tarjeta-rojo', number_format($saldo, 2), 'Por liquidar (S/)'],
    ];

    $q = $pdo->prepare(
        'SELECT p.id, p.estado, p.total_pen, p.creado_en,
                c.name AS nombre, c.apellidos
           FROM pedidos p
           JOIN users c ON c.id = p.cliente_id
           JOIN detalle_pedido dp ON dp.pedido_id = p.id AND dp.vendedor_id = ?
          GROUP BY p.id
          ORDER BY p.id DESC LIMIT 8'
    );
    $q->execute([$vendedorId]);
    $ultimos = $q->fetchAll();
} else {
    $totales = [
        'productos'  => (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
        'pedidos'    => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
        'pendientes' => (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn(),
        'usuarios'   => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn(),
        'cupones'    => (int) $pdo->query('SELECT COUNT(*) FROM cupones')->fetchColumn(),
        'vendedores' => (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE rol = 'vendedor' AND deleted_at IS NULL"
        )->fetchColumn(),
    ];
    $tarjetas = [
        ['tarjeta-cyan', $totales['productos'], 'Productos'],
        ['tarjeta-azul', $totales['pedidos'], 'Pedidos'],
        ['tarjeta-ambar', $totales['pendientes'], 'Pendientes'],
        ['tarjeta-verde', $totales['usuarios'], 'Usuarios'],
        ['tarjeta-rojo', $totales['cupones'], 'Cupones'],
        ['tarjeta-azul', $totales['vendedores'], 'Vendedores'],
    ];

    $ultimos = $pdo->query(
        'SELECT p.id, p.estado, p.total_pen, p.creado_en, c.name AS nombre, c.apellidos
         FROM pedidos p JOIN users c ON c.id = p.cliente_id
         ORDER BY p.id DESC LIMIT 8'
    )->fetchAll();
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
    <div class="admin-tabla-wrap">
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
