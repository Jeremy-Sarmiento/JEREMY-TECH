<?php
/**
 * Vista: Pagos y webhooks de la pasarela.
 *
 * Solo lectura: los estados los cambian el checkout o el webhook firmado.
 * La configuración se lee de `.env` (PAGOS_*), nunca se escribe desde aquí.
 */
use Model\Pasarela;

$pagos = Pasarela::pagos(200);
$eventos = Pasarela::eventos(100);
$resumen = Pasarela::resumen();

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $monto): string => 'S/ ' . number_format($monto, 2);
$fecha = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};

$configurada = Pasarela::configurada();
$webhookUrl = Pasarela::webhookUrl();
$secreto = Pasarela::secreto();
$secretoVisible = $secreto === ''
    ? ''
    : str_repeat('•', 10) . ' (' . strlen($secreto) . ' caracteres)';

$clasePago = static function (string $estado): string {
    return match (strtolower($estado)) {
        'pagado'      => 'etiqueta etiqueta-ok',
        'pendiente', 'en verificación', 'en verificacion' => 'etiqueta etiqueta-aviso',
        'reembolsado' => 'etiqueta etiqueta-neutro',
        'rechazado'   => 'etiqueta etiqueta-off',
        default       => 'etiqueta etiqueta-neutro',
    };
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Pagos y webhooks</h2>
            <p class="admin-nota">
                Conciliación del checkout con la pasarela. El webhook valida una firma
                HMAC-SHA256, tolera 5 minutos de desfase y es idempotente por evento.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/pedidos">Ver pedidos</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= (int) $resumen['total'] ?></span>
            <span class="tarjeta-etiqueta">Pagos</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= (int) $resumen['pagados'] ?></span>
            <span class="tarjeta-etiqueta">Pagados</span>
        </div>
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero"><?= (int) $resumen['pendientes'] ?></span>
            <span class="tarjeta-etiqueta">Pendientes</span>
        </div>
        <div class="tarjeta tarjeta-azul">
            <span class="tarjeta-numero"><?= (int) $resumen['webhooks'] ?></span>
            <span class="tarjeta-etiqueta">Webhooks recibidos</span>
        </div>
    </div>

    <div class="admin-tarjetas-grid">
        <div class="admin-tarjeta">
            <h3 class="admin-subtitulo">Configuración de la pasarela</h3>
            <dl class="admin-datos">
                <div>
                    <dt>Estado</dt>
                    <dd>
                        <span class="etiqueta <?= $configurada ? 'etiqueta-ok' : 'etiqueta-aviso' ?>">
                            <?= $configurada ? 'Webhook activo' : 'Webhook sin configurar' ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>Modo</dt>
                    <dd><code><?= $e(Pasarela::modo()) ?></code></dd>
                </div>
                <div>
                    <dt>Proveedor</dt>
                    <dd><code><?= $e(Pasarela::proveedor()) ?></code></dd>
                </div>
                <div>
                    <dt>Moneda</dt>
                    <dd><code><?= $e(Pasarela::moneda()) ?></code></dd>
                </div>
                <div>
                    <dt>Secreto</dt>
                    <dd>
                        <?php if ($secretoVisible !== ''): ?>
                            <code><?= $e($secretoVisible) ?></code>
                        <?php else: ?>
                            <span class="admin-secundario">Define PAGOS_WEBHOOK_SECRET en .env</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt>URL del webhook</dt>
                    <dd><code class="admin-codigo-ruta"><?= $e($webhookUrl) ?></code></dd>
                </div>
            </dl>
            <?php if (!$configurada): ?>
                <p class="admin-nota" style="margin-top:12px;">
                    Mientras no exista <code>PAGOS_WEBHOOK_SECRET</code>, el endpoint responde
                    503 y el checkout sigue funcionando en modo simulado.
                </p>
            <?php endif; ?>
        </div>

        <div class="admin-tarjeta">
            <h3 class="admin-subtitulo">Contrato del evento</h3>
            <p class="admin-nota">
                Cabeceras <code>X-JT-Timestamp</code>, <code>X-JT-Signature</code> y
                <code>X-JT-Gateway</code>. La firma es
                <code>HMAC-SHA256(timestamp . "." . cuerpo)</code>.
            </p>
            <pre class="admin-codigo">{
  "id": "evt_2026_0001",
  "type": "payment.updated",
  "data": {
    "pedido_id": 42,
    "referencia": "TX-123",
    "estado": "approved",
    "metodo": "Tarjeta"
  }
}</pre>
        </div>
    </div>

    <h3 class="admin-subtitulo" style="margin-top:26px;">Últimos pagos</h3>
    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>ID</th><th>Pedido</th><th>Cliente</th><th>Monto</th>
                    <th>Método</th><th>Pasarela / referencia</th><th>Estado</th><th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$pagos): ?>
                <tr><td colspan="8" class="admin-vacio">Todavía no hay pagos registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($pagos as $p): ?>
                <?php
                $cliente = trim((string) ($p['cliente_nombre'] ?? '') . ' ' . (string) ($p['cliente_apellidos'] ?? ''));
                $referencia = trim((string) ($p['referencia_externa'] ?? ''));
                if ($referencia === '') {
                    $referencia = trim((string) ($p['transaccion_id'] ?? ''));
                }
                ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td>
                        <a href="?ruta=admin/pedidos">#<?= (int) $p['pedido_id'] ?></a>
                        <small class="admin-secundario"><?= $e((string) ($p['pedido_estado'] ?? '')) ?></small>
                    </td>
                    <td>
                        <?= $e($cliente !== '' ? $cliente : '—') ?>
                        <small class="admin-secundario"><?= $e((string) ($p['cliente_email'] ?? '')) ?></small>
                    </td>
                    <td><?= $e($fmt((float) $p['monto_pen'])) ?></td>
                    <td><?= $e((string) $p['metodo']) ?></td>
                    <td>
                        <code><?= $e((string) ($p['pasarela'] ?? 'simulada')) ?></code>
                        <?php if ($referencia !== ''): ?>
                            <small class="admin-secundario"><?= $e($referencia) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= $clasePago((string) $p['estado']) ?>"><?= $e((string) $p['estado']) ?></span></td>
                    <td>
                        <?= $e($fecha((string) ($p['creado_en'] ?? ''))) ?>
                        <small class="admin-secundario">Act. <?= $e($fecha((string) ($p['actualizado_en'] ?? ''))) ?></small>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3 class="admin-subtitulo" style="margin-top:26px;">Bitácora de webhooks</h3>
    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>ID</th><th>Fecha</th><th>Pasarela</th><th>Evento</th>
                    <th>Tipo</th><th>Pedido / pago</th><th>Estado</th><th>Resultado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$eventos): ?>
                <tr><td colspan="8" class="admin-vacio">
                    Aún no se ha recibido ningún webhook. Cada evento aplicado queda aquí
                    para auditoría e idempotencia.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($eventos as $w): ?>
                <tr>
                    <td><?= (int) $w['id'] ?></td>
                    <td><?= $e($fecha((string) ($w['creado_en'] ?? ''))) ?></td>
                    <td><code><?= $e((string) $w['pasarela']) ?></code></td>
                    <td><code><?= $e((string) $w['evento_id']) ?></code></td>
                    <td><?= $e((string) $w['tipo']) ?></td>
                    <td>
                        <?php if ((int) ($w['pedido_id'] ?? 0) > 0): ?>
                            #<?= (int) $w['pedido_id'] ?>
                        <?php else: ?>
                            <span class="admin-secundario">—</span>
                        <?php endif; ?>
                        <?php if ((int) ($w['pago_id'] ?? 0) > 0): ?>
                            <small class="admin-secundario">Pago #<?= (int) $w['pago_id'] ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= $clasePago((string) $w['estado']) ?>"><?= $e((string) $w['estado']) ?></span></td>
                    <td class="admin-detalle"><?= $e((string) $w['resultado']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
