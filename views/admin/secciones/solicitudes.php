<?php
/**
 * Vista: Solicitudes de rol de vendedor — cola del panel.
 *
 * `renderizar()` no hace extract(): la vista consulta sus propios datos.
 */
use Model\SolicitudVendedor;

$estadoFiltro = trim((string) ($_GET['estado'] ?? ''));
if (!in_array($estadoFiltro, SolicitudVendedor::ESTADOS, true)) {
    $estadoFiltro = '';
}

$solicitudes = SolicitudVendedor::listar($estadoFiltro);
$resumen = SolicitudVendedor::resumen();

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
        SolicitudVendedor::APROBADA  => 'etiqueta etiqueta-ok',
        SolicitudVendedor::RECHAZADA => 'etiqueta etiqueta-neutro',
        default                      => 'etiqueta etiqueta-aviso',
    };
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Solicitudes de vendedor</h2>
            <p class="admin-nota">
                Toda cuenta nueva se registra como cliente. Aprobar aquí cambia
                <code>users.rol</code> a <code>vendedor</code> y conserva el resto de la
                ficha (tipo B2C/B2B/C2C, RUC, empresa, dirección y claves). El panel del
                vendedor se adapta a su tipo.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/usuarios">Ver usuarios</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-ambar">
            <span class="tarjeta-numero"><?= (int) $resumen['pendientes'] ?></span>
            <span class="tarjeta-etiqueta">Pendientes</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= (int) $resumen['aprobadas'] ?></span>
            <span class="tarjeta-etiqueta">Aprobadas</span>
        </div>
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= (int) $resumen['rechazadas'] ?></span>
            <span class="tarjeta-etiqueta">Rechazadas</span>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/solicitudes">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (SolicitudVendedor::ESTADOS as $estado): ?>
                <option value="<?= $e($estado) ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>>
                    <?= $e(SolicitudVendedor::etiquetaEstado($estado)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-claro" href="?ruta=admin/solicitudes">Limpiar</a>
    </form>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Solicitud</th><th>Cuenta</th><th>Tipo</th>
                    <th>Mensaje</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$solicitudes): ?>
                <tr><td colspan="6" class="admin-vacio">
                    No hay solicitudes para este filtro. Los usuarios las envían desde
                    «Mi perfil» → «Vender en Jeremy Tech».
                </td></tr>
            <?php endif; ?>
            <?php foreach ($solicitudes as $s): ?>
                <?php
                $id       = (int) $s['id'];
                $estado   = (string) $s['estado'];
                $cuenta   = trim((string) ($s['name'] ?? '') . ' ' . (string) ($s['apellidos'] ?? ''));
                $mensaje  = trim((string) ($s['mensaje'] ?? ''));
                $motivo   = trim((string) ($s['motivo_rechazo'] ?? ''));
                ?>
                <tr id="solicitud-<?= $id ?>">
                    <td>
                        <strong>#<?= $id ?></strong>
                        <small class="admin-secundario"><?= $e($fecha((string) $s['creado_en'])) ?></small>
                    </td>
                    <td>
                        <?= $e($cuenta !== '' ? $cuenta : 'Cuenta #' . (int) $s['usuario_id']) ?>
                        <small class="admin-secundario"><?= $e((string) ($s['email'] ?? '')) ?></small>
                        <small class="admin-secundario">
                            <?= $e(trim((string) ($s['telefono'] ?? '')) !== '' ? (string) $s['telefono'] : 'Sin teléfono') ?>
                            · rol actual: <?= $e((string) ($s['rol'] ?? 'cliente')) ?>
                        </small>
                    </td>
                    <td>
                        <span class="etiqueta etiqueta-info"><?= $e((string) ($s['tipo'] ?? 'B2C')) ?></span>
                        <?php if ((string) ($s['empresa'] ?? '') !== '' || (string) ($s['ruc'] ?? '') !== ''): ?>
                        <small class="admin-secundario">
                            <?= $e((string) ($s['empresa'] ?? '')) ?>
                            <?= (string) ($s['ruc'] ?? '') !== '' ? ' · RUC ' . $e((string) $s['ruc']) : '' ?>
                        </small>
                        <?php endif; ?>
                        <?php if ((string) ($s['plataforma_reventa'] ?? '') !== ''): ?>
                        <small class="admin-secundario">Revende en <?= $e((string) $s['plataforma_reventa']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="admin-detalle">
                        <?= $mensaje !== '' ? $e($mensaje) : '<span class="admin-secundario">Sin mensaje</span>' ?>
                    </td>
                    <td>
                        <span class="<?= $claseEstado($estado) ?>"><?= $e(SolicitudVendedor::etiquetaEstado($estado)) ?></span>
                        <?php if ($motivo !== ''): ?>
                        <small class="admin-secundario"><?= $e($motivo) ?></small>
                        <?php endif; ?>
                        <?php if ((string) ($s['resuelto_por'] ?? '') !== ''): ?>
                        <small class="admin-secundario">
                            por <?= $e((string) $s['resuelto_por']) ?> · <?= $e($fecha((string) ($s['resuelto_en'] ?? ''))) ?>
                        </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($estado === SolicitudVendedor::PENDIENTE): ?>
                        <form method="post" action="?ruta=admin/solicitudes/aprobar" class="admin-form-inline">
                            <?= \Security\Proteccion::campo() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-primario">Aprobar</button>
                        </form>
                        <details style="margin-top:8px;">
                            <summary style="cursor:pointer;font-size:12px;color:var(--jt-texto-3);">Rechazar</summary>
                            <form method="post" action="?ruta=admin/solicitudes/rechazar" class="admin-form-inline admin-form-columna">
                                <?= \Security\Proteccion::campo() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <textarea name="motivo" rows="2" maxlength="<?= SolicitudVendedor::MOTIVO_MAX ?>"
                                          placeholder="Motivo que verá el usuario (opcional)"></textarea>
                                <button type="submit" class="btn-mini btn-mini-peligro">Confirmar rechazo</button>
                            </form>
                        </details>
                        <?php else: ?>
                        <span class="admin-secundario">Revisada</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
