<?php
$pdo = conexion();

// (Etapa 6) El vendedor solo ve los pedidos que llevan sus líneas y, dentro
// de ellos, solo las líneas suyas.
$esVendedor = \Model\Administrador::esVendedor();
[$joinPedidos, $wherePedidos, $whereDetalle, $paramsDetalle] = \Model\Vendedor::alcancePanelPedidos();

$filtro = (string) ($_GET['estado'] ?? '');
$estados = ['Pendiente', 'Pagado', 'Enviado', 'Entregado', 'Cancelado'];

$sqlPedidos = 'SELECT ' . ($joinPedidos !== '' ? 'DISTINCT ' : '')
    . 'p.*, c.name AS nombre, c.apellidos,
       (SELECT pa.estado FROM pagos pa
         WHERE pa.pedido_id = p.id ORDER BY pa.id DESC LIMIT 1) AS pago_estado,
       (SELECT pa.monto_pen FROM pagos pa
         WHERE pa.pedido_id = p.id ORDER BY pa.id DESC LIMIT 1) AS pago_monto
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

// La elegibilidad depende del último pago: mientras siga confirmado se puede
// reembolsar, sin importar si el pedido está Pagado, Enviado, Entregado o
// incluso Cancelado. El controlador vuelve a validarlo dentro de la
// transacción, así que un POST manipulado no salta la regla.
$puedeReembolsar = static function (array $p): bool {
    return \Model\Pedido::pagoReembolsable((string) ($p['pago_estado'] ?? ''));
};
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
                <th>Estado</th><th>Cambiar estado</th>
                <?php if (!$esVendedor): ?><th>Reembolso</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($pedidos as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= $e($p['nombre']) ?> <?= $e($p['apellidos']) ?>
                        <?php if ((string) ($p['comprobante_tipo'] ?? '') !== ''): ?>
                            <div class="admin-secundario">
                                <?= ($p['comprobante_tipo'] ?? '') === 'factura' ? 'Factura' : 'Boleta' ?>
                                <?= (string) ($p['comprobante_documento'] ?? '') !== '' ? ' · ' . $e($p['comprobante_documento']) : '' ?>
                                <?= (string) ($p['comprobante_razon_social'] ?? '') !== '' ? ' · ' . $e($p['comprobante_razon_social']) : '' ?>
                            </div>
                        <?php endif; ?>
                    </td>
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
                    <td>
                        <span class="<?= \Model\Pedido::claseEstado((string) $p['estado']) ?>"><?= $e($p['estado']) ?></span>
                        <?php if ((string) ($p['courier'] ?? '') !== '' || (string) ($p['guia_numero'] ?? '') !== ''): ?>
                            <div class="admin-secundario">
                                <?= $e($p['courier'] ?? '') ?><?= (string) ($p['guia_numero'] ?? '') !== '' ? ' · Guía ' . $e($p['guia_numero']) : '' ?>
                            </div>
                        <?php endif; ?>
                        <?php if ((string) ($p['fecha_envio'] ?? '') !== ''): ?>
                            <div class="admin-secundario">Enviado: <?= $e($p['fecha_envio']) ?></div>
                        <?php endif; ?>
                        <?php if ((string) ($p['pago_estado'] ?? '') !== ''): ?>
                            <div class="admin-secundario">Pago: <?= $e($p['pago_estado']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $destinos = \Model\Pedido::destinos((string) $p['estado']);
                        $esRecojo = (string) ($p['envio_zona'] ?? '') === \Model\Envio::ZONA_RECOJO;
                        ?>
                        <?php if ($destinos === []): ?>
                            <span class="admin-nota">Estado final</span>
                        <?php else: ?>
                        <form method="post" action="?ruta=admin/pedidos/estado" class="admin-inline-form">
                            <?= \Security\Proteccion::campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <select name="estado">
                                <?php foreach ($destinos as $est): ?>
                                    <option value="<?= $e($est) ?>"><?= $e($est) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (in_array('Enviado', $destinos, true) && !$esRecojo): ?>
                                <input type="text" name="courier" maxlength="80" placeholder="Courier"
                                       title="Obligatorio al marcar Enviado" aria-label="Courier del pedido <?= (int) $p['id'] ?>">
                                <input type="text" name="guia_numero" maxlength="80" placeholder="N° de guía"
                                       title="Obligatorio al marcar Enviado" aria-label="Número de guía del pedido <?= (int) $p['id'] ?>">
                            <?php elseif (in_array('Enviado', $destinos, true)): ?>
                                <span class="admin-nota" style="margin:0;">Recojo en tienda: sin guía.</span>
                            <?php endif; ?>
                            <button type="submit" class="btn-mini">OK</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <?php if (!$esVendedor): ?>
                    <td>
                        <?php if ($puedeReembolsar($p)): ?>
                            <button type="button" class="btn-mini btn-mini-peligro"
                                    data-reembolso="<?= (int) $p['id'] ?>"
                                    data-codigo="<?= $e(\Model\Pedido::codigo((int) $p['id'])) ?>"
                                    data-monto="<?= $e(number_format((float) ($p['pago_monto'] ?? 0), 2)) ?>">
                                Reembolsar
                            </button>
                        <?php elseif ((string) ($p['pago_estado'] ?? '') === 'Reembolsado'): ?>
                            <span class="etiqueta etiqueta-off">Reembolsado</span>
                        <?php else: ?>
                            <span class="admin-secundario">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pedidos): ?>
                <tr><td colspan="<?= $esVendedor ? 6 : 7 ?>" class="admin-vacio">No hay pedidos para este filtro.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$esVendedor): ?>
    <dialog class="admin-modal" id="modalReembolso">
        <form method="post" action="?ruta=admin/pedidos/reembolsar" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <div class="admin-modal-cabecera">
                <h3>Reembolsar <span data-reembolso-codigo></span></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="0">
                <p class="admin-nota" style="margin:0 0 10px;">
                    Se marcará el pago como <strong>Reembolsado</strong>, el pedido pasará a
                    <strong>Cancelado</strong> y se devolverán las unidades al inventario.
                    La comisión deja de contar para el saldo del vendedor; si ya se
                    liquidó, su saldo pendiente puede quedar negativo.
                </p>
                <p class="admin-detalle">
                    Monto a reembolsar: <strong data-reembolso-monto></strong>
                </p>
                <label>Motivo (opcional)
                    <input type="text" name="motivo" maxlength="160"
                           placeholder="Ej. devolución solicitada por el cliente">
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario">Confirmar reembolso</button>
                <button type="button" class="btn btn-claro" data-modal-cerrar>Cancelar</button>
            </div>
        </form>
    </dialog>

    <script>
    // Un solo modal de reembolso: cada botón de fila inyecta su pedido y monto.
    (function () {
        var dlg = document.getElementById('modalReembolso');
        if (!dlg) { return; }
        var form = dlg.querySelector('form');
        var idInput = form.querySelector('input[name="id"]');
        var codigo = dlg.querySelector('[data-reembolso-codigo]');
        var monto = dlg.querySelector('[data-reembolso-monto]');
        var motivo = form.querySelector('input[name="motivo"]');

        document.querySelectorAll('[data-reembolso]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                idInput.value = btn.getAttribute('data-reembolso');
                codigo.textContent = btn.getAttribute('data-codigo') || '';
                monto.textContent = 'S/ ' + (btn.getAttribute('data-monto') || '0.00');
                if (motivo) { motivo.value = ''; }
                dlg.showModal();
            });
        });
    })();
    </script>
    <?php endif; ?>
</section>
