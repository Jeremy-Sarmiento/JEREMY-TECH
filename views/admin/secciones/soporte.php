<?php
/**
 * Vista: Soporte postventa — cola de tickets del panel.
 *
 * `renderizar()` no hace extract(): la vista consulta sus propios datos.
 */
use Model\SoporteTicket;

$estadoFiltro = trim((string) ($_GET['estado'] ?? ''));
if (!in_array($estadoFiltro, SoporteTicket::ESTADOS, true)) {
    $estadoFiltro = '';
}
$buscar = trim((string) ($_GET['q'] ?? ''));

$tickets = SoporteTicket::todos($estadoFiltro, $buscar, 250);
$resumen = SoporteTicket::resumen();

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};

$claseEstado = static function (string $estado): string {
    return match ($estado) {
        SoporteTicket::ABIERTO    => 'etiqueta etiqueta-aviso',
        SoporteTicket::EN_PROCESO => 'etiqueta etiqueta-info',
        SoporteTicket::RESUELTO   => 'etiqueta etiqueta-ok',
        SoporteTicket::CERRADO    => 'etiqueta etiqueta-neutro',
        default                   => 'etiqueta etiqueta-neutro',
    };
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Soporte postventa</h2>
            <p class="admin-nota">
                Tickets que abren los clientes desde <code>?ruta=soporte</code>. La respuesta
                se guarda en el ticket y genera un aviso en la bandeja del cliente.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/notificaciones">Ver notificaciones</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= (int) $resumen['total'] ?></span>
            <span class="tarjeta-etiqueta">Tickets</span>
        </div>
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero"><?= (int) $resumen['abiertos'] ?></span>
            <span class="tarjeta-etiqueta">Abiertos</span>
        </div>
        <div class="tarjeta tarjeta-azul">
            <span class="tarjeta-numero"><?= (int) $resumen['en_proceso'] ?></span>
            <span class="tarjeta-etiqueta">En proceso</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= (int) $resumen['resueltos'] ?></span>
            <span class="tarjeta-etiqueta">Resueltos</span>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/soporte">
        <input type="text" name="q" value="<?= $e($buscar) ?>"
               placeholder="Buscar asunto, mensaje, cliente o correo...">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (SoporteTicket::ESTADOS as $estado): ?>
                <option value="<?= $e($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>>
                    <?= $e(SoporteTicket::etiquetaEstado($estado)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-claro" href="?ruta=admin/soporte">Limpiar</a>
    </form>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Ticket</th><th>Cliente</th><th>Asunto / mensaje</th>
                    <th>Pedido</th><th>Estado</th><th>Respuesta</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$tickets): ?>
                <tr><td colspan="6" class="admin-vacio">
                    No hay tickets para este filtro. Los clientes los abren desde
                    «Soporte postventa» en su cuenta.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($tickets as $t): ?>
                <?php
                $id = (int) $t['id'];
                $estado = (string) $t['estado'];
                $respuesta = trim((string) ($t['respuesta'] ?? ''));
                $cliente = trim((string) ($t['cliente_nombre'] ?? '') . ' ' . (string) ($t['cliente_apellidos'] ?? ''));
                if ($cliente === '') {
                    $cliente = 'Cliente eliminado';
                }
                ?>
                <tr id="ticket-<?= $id ?>">
                    <td>
                        <strong><?= $e(SoporteTicket::codigo($id)) ?></strong>
                        <small class="admin-secundario"><?= $e($fecha((string) $t['creado_en'])) ?></small>
                    </td>
                    <td>
                        <?= $e($cliente) ?>
                        <small class="admin-secundario"><?= $e((string) ($t['cliente_email'] ?? '')) ?></small>
                    </td>
                    <td class="admin-detalle">
                        <strong><?= $e((string) $t['asunto']) ?></strong>
                        <span class="etiqueta etiqueta-neutro"><?= $e((string) $t['categoria']) ?></span>
                        <details style="margin-top:6px;">
                            <summary style="cursor:pointer;font-size:12px;color:var(--jt-texto-3);">Ver descripción</summary>
                            <div style="white-space:pre-wrap;margin-top:6px;color:var(--jt-texto-3);font-size:12.5px;"><?= $e((string) $t['descripcion']) ?></div>
                        </details>
                    </td>
                    <td>
                        <?php if ((int) ($t['pedido_id'] ?? 0) > 0): ?>
                            <a href="?ruta=admin/pedidos">#<?= (int) $t['pedido_id'] ?></a>
                        <?php else: ?>
                            <span class="admin-secundario">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="<?= $claseEstado($estado) ?>"><?= $e(SoporteTicket::etiquetaEstado($estado)) ?></span>
                        <form method="post" action="?ruta=admin/soporte/estado" class="admin-form-inline">
                            <?= \Security\Proteccion::campo() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <select name="estado" aria-label="Cambiar estado del ticket <?= $e(SoporteTicket::codigo($id)) ?>">
                                <?php foreach (SoporteTicket::ESTADOS as $opcion): ?>
                                    <option value="<?= $e($opcion) ?>" <?= $opcion === $estado ? 'selected' : '' ?>>
                                        <?= $e(SoporteTicket::etiquetaEstado($opcion)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-mini">Cambiar</button>
                        </form>
                    </td>
                    <td class="admin-detalle">
                        <?php if ($respuesta !== ''): ?>
                            <div style="white-space:pre-wrap;font-size:12.5px;"><?= $e($respuesta) ?></div>
                            <small class="admin-secundario">
                                <?= $e((string) ($t['respondido_por'] ?? '')) ?>
                                · <?= $e($fecha((string) ($t['respondido_en'] ?? ''))) ?>
                            </small>
                        <?php else: ?>
                            <span class="admin-secundario">Sin respuesta todavía</span>
                        <?php endif; ?>

                        <?php if ($estado !== SoporteTicket::CERRADO): ?>
                        <details style="margin-top:8px;"<?= $respuesta === '' ? ' open' : '' ?>>
                            <summary style="cursor:pointer;font-size:12px;color:var(--jt-texto-3);">
                                <?= $respuesta === '' ? 'Responder al cliente' : 'Editar respuesta' ?>
                            </summary>
                            <form method="post" action="?ruta=admin/soporte/responder" class="admin-form-inline admin-form-columna">
                                <?= \Security\Proteccion::campo() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <textarea name="respuesta" rows="4" maxlength="<?= SoporteTicket::RESPUESTA_MAX ?>"
                                          required placeholder="Escribe la respuesta que verá el cliente..."><?= $e($respuesta) ?></textarea>
                                <button type="submit" class="btn btn-primario">Guardar respuesta</button>
                            </form>
                        </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
