<?php
/**
 * Vista: Notificaciones — visor de solo lectura de la cola simulada.
 *
 * La cola la generan los flujos de pedidos (pago, envío) y el reembolso.
 * Esta sección no envía, no marca como enviada y no edita registros.
 */
use Model\Notificacion;

$buscar = trim((string) ($_GET['q'] ?? ''));
$tipo   = (string) ($_GET['tipo'] ?? '');
$estado = (string) ($_GET['estado'] ?? '');

$notificaciones = Notificacion::cola($buscar, $tipo, $estado, 300);
$tipos          = Notificacion::tiposCola();
$resumen        = Notificacion::resumenCola();

$etiquetasTipo = [
    Notificacion::PAGO_CONFIRMADO => 'Pago confirmado',
    Notificacion::PEDIDO_ENVIADO  => 'Pedido enviado / recojo',
    Notificacion::REEMBOLSO       => 'Reembolso',
    'recuperacion_cliente'        => 'Recuperación (tienda)',
    'recuperacion_admin'          => 'Recuperación (panel)',
    'soporte_respuesta'           => 'Respuesta de soporte',
];

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static function (string $valor): string {
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Notificaciones</h2>
            <p class="admin-nota">
                Cola de correos simulados generados por los pedidos y por las
                solicitudes de recuperación de contraseña. Es un visor de solo
                lectura: no se envía nada por SMTP ni se modifican los registros.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/pedidos">Ver pedidos</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= (int) $resumen['total'] ?></span>
            <span class="tarjeta-etiqueta">En la cola</span>
        </div>
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero"><?= (int) $resumen['pendientes'] ?></span>
            <span class="tarjeta-etiqueta">Pendientes</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= (int) $resumen['enviadas'] ?></span>
            <span class="tarjeta-etiqueta">Enviadas</span>
        </div>
        <div class="tarjeta tarjeta-rojo">
            <span class="tarjeta-numero"><?= (int) $resumen['errores'] ?></span>
            <span class="tarjeta-etiqueta">Con error</span>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/notificaciones">
        <input type="text" name="q" value="<?= $e($buscar) ?>"
               placeholder="Buscar destinatario, asunto o pedido...">
        <select name="tipo">
            <option value="">Todos los tipos</option>
            <?php foreach ($tipos as $t): ?>
                <option value="<?= $e($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>>
                    <?= $e($etiquetasTipo[$t] ?? $t) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
            <option value="enviado" <?= $estado === 'enviado' ? 'selected' : '' ?>>Enviadas</option>
            <option value="error" <?= $estado === 'error' ? 'selected' : '' ?>>Con error</option>
        </select>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-claro" href="?ruta=admin/notificaciones">Limpiar</a>
    </form>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>ID</th><th>Fecha</th><th>Pedido</th><th>Destinatario</th>
                    <th>Tipo</th><th>Asunto / resumen</th><th>Estado</th><th>Archivo</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$notificaciones): ?>
                <tr><td colspan="8" class="admin-vacio">
                    No hay notificaciones para este filtro. Se generan al confirmar un
                    pago, marcar un pedido como enviado, reembolsarlo o solicitar la
                    recuperación de una contraseña.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($notificaciones as $n): ?>
                <?php
                $error = trim((string) ($n['error'] ?? ''));
                if ($error !== '') {
                    $claseEstado = 'etiqueta etiqueta-off';
                    $textoEstado = 'Error';
                } elseif ((int) $n['enviado'] === 1) {
                    $claseEstado = 'etiqueta etiqueta-ok';
                    $textoEstado = 'Enviada';
                } else {
                    $claseEstado = 'etiqueta etiqueta-aviso';
                    $textoEstado = 'Pendiente';
                }
                ?>
                <tr>
                    <td><?= (int) $n['id'] ?></td>
                    <td><?= $e($fecha((string) ($n['creado_en'] ?? ''))) ?></td>
                    <td>
                        <?php if ((int) ($n['pedido_id'] ?? 0) > 0): ?>
                            <a href="?ruta=admin/pedidos">#<?= (int) $n['pedido_id'] ?></a>
                        <?php else: ?>
                            <span class="admin-secundario">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $e($n['destinatario']) ?>
                        <?php if (trim((string) ($n['cliente_nombre'] ?? '')) !== ''): ?>
                            <small class="admin-secundario">
                                <?= $e(trim((string) $n['cliente_nombre'] . ' ' . (string) ($n['cliente_apellidos'] ?? ''))) ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="etiqueta etiqueta-neutro">
                            <?= $e($etiquetasTipo[(string) $n['tipo']] ?? (string) $n['tipo']) ?>
                        </span>
                    </td>
                    <td class="admin-detalle">
                        <?= $e($n['asunto']) ?>
                        <?php if (trim((string) ($n['cuerpo'] ?? '')) !== ''): ?>
                            <details style="margin-top:4px;">
                                <summary style="cursor:pointer;font-size:12px;color:var(--jt-texto-3);">Ver resumen</summary>
                                <div style="white-space:pre-wrap;margin-top:6px;color:var(--jt-texto-3);font-size:12.5px;"><?= $e($n['cuerpo']) ?></div>
                            </details>
                        <?php endif; ?>
                        <?php if ($error !== ''): ?>
                            <div style="color:var(--jt-peligro);font-size:12px;margin-top:4px;"><?= $e($error) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="<?= $claseEstado ?>"><?= $textoEstado ?></span>
                        <?php if (trim((string) ($n['enviado_en'] ?? '')) !== ''): ?>
                            <small class="admin-secundario"><?= $e($fecha((string) $n['enviado_en'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (trim((string) ($n['archivo'] ?? '')) !== ''): ?>
                            <code class="admin-codigo-ruta"><?= $e($n['archivo']) ?></code>
                        <?php else: ?>
                            <span class="admin-secundario">Sin archivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
