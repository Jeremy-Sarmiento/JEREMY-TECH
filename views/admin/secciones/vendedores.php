<?php
/** Vista: Vendedores del marketplace (solo administrador — Etapa 6). */
// `renderizar()` no hace extract(): la vista calcula sus propios datos.
$vendedores = \Model\Vendedor::todos();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    foreach ($vendedores as $v) {
        if ((int) $v['id'] === $idEditar) {
            $editando = $v;
            break;
        }
    }
}

$liquidando = null;
$saldoLiquidar = 0.0;
$idLiquidar = (int) ($_GET['liquidar'] ?? 0);
if ($idLiquidar > 0) {
    foreach ($vendedores as $v) {
        if ((int) $v['id'] === $idLiquidar) {
            $liquidando = $v;
            $saldoLiquidar = round(
                max(0.0, (float) $v['ganancias'] - (float) $v['liquidado']),
                2
            );
            break;
        }
    }
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$s = static fn (float $m): string => number_format($m, 2);

// Lo que la plataforma se queda es `comisiones`; lo que se LE DEBE al
// vendedor es `ganancias - liquidado`. No confundir las dos cifras.
$totalComisiones = 0.0;
$totalGanancias  = 0.0;
$totalLiquidado  = 0.0;
foreach ($vendedores as $v) {
    $totalComisiones += (float) $v['comisiones'];
    $totalGanancias  += (float) $v['ganancias'];
    $totalLiquidado  += (float) $v['liquidado'];
}
$totalSaldo = round($totalGanancias - $totalLiquidado, 2);
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Vendedores</h2>
            <p class="admin-nota">
                Fichas comerciales del marketplace: comisión, verificación y saldo por liquidar.
                Incluye a los vendedores y a los clientes con tipo <strong>C2C</strong>.
                El alta y el rol se asignan en <a href="?ruta=admin/usuarios">Usuarios</a>.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/comisiones">Ver comisiones</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-azul">
            <span class="tarjeta-numero"><?= count($vendedores) ?></span>
            <span class="tarjeta-etiqueta">Vendedores</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= $s($totalComisiones) ?></span>
            <span class="tarjeta-etiqueta">Comisión generada (S/)</span>
        </div>
        <div class="tarjeta tarjeta-rojo">
            <span class="tarjeta-numero"><?= $s($totalSaldo) ?></span>
            <span class="tarjeta-etiqueta">Por liquidar al vendedor (S/)</span>
        </div>
    </div>

    <!-- Ficha comercial -->
    <dialog class="admin-modal" id="modalFicha"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/vendedores/guardar" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <div class="admin-modal-cabecera">
                <h3>Ficha del vendedor</h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= (int) ($editando['id'] ?? 0) ?>">
                <label>Vendedor
                    <input type="text" disabled value="<?= $e(($editando['name'] ?? '') . ' ' . ($editando['apellidos'] ?? '')) ?>">
                </label>
                <label>Comisión de la plataforma (%)
                    <input type="number" step="0.01" min="0" max="100" name="comision_porcentaje" required
                           value="<?= $e($editando['comision_porcentaje'] ?? '15') ?>">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="verificado" value="1" <?= (int) ($editando['verificado'] ?? 0) === 1 ? 'checked' : '' ?>>
                    Vendedor verificado
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= (int) ($editando['activo'] ?? 0) === 1 ? 'checked' : '' ?>>
                    Activo (puede entrar y vender)
                </label>
                <p class="admin-nota">Deja la comisión como está para conservarla; el reparto se calcula sobre la base sin IGV.</p>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario">Guardar</button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/vendedores">Cancelar</button>
            </div>
        </form>
    </dialog>

    <!-- Liquidación al saldo pendiente -->
    <dialog class="admin-modal" id="modalLiquidar"<?= $liquidando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/vendedores/liquidar" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <div class="admin-modal-cabecera">
                <h3>Registrar liquidación</h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= (int) ($liquidando['id'] ?? 0) ?>">
                <label>Vendedor
                    <input type="text" disabled value="<?= $e($liquidando['name'] ?? '') ?>">
                </label>
                <label>Saldo pendiente (S/)
                    <input type="text" disabled
                           value="<?= $s($saldoLiquidar) ?>">
                </label>
                <label>Monto a pagar (S/)
                    <input type="number" step="0.01" min="0.01" name="monto" required
                           value="<?= $s($saldoLiquidar) ?>">
                </label>
                <label>Método
                    <input type="text" name="metodo" required value="Transferencia" placeholder="Transferencia, Yape, Depósito...">
                </label>
                <label>Referencia
                    <input type="text" name="referencia" value="" placeholder="N° de operación (opcional)">
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario">Registrar</button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/vendedores">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>Vendedor</th><th>Comisión</th><th>Productos</th>
                        <th>Ventas (S/)</th><th>Plataforma (S/)</th><th>Vendedor (S/)</th>
                        <th>Liquidado (S/)</th><th>Saldo (S/)</th>
                        <th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vendedores as $v):
                    $saldo = round((float) $v['ganancias'] - (float) $v['liquidado'], 2); ?>
                    <tr>
                        <td>
                            <strong><?= $e($v['name']) ?> <?= $e($v['apellidos']) ?></strong>
                            <?php if ((string) ($v['tipo'] ?? '') === 'C2C'): ?>
                                <span class="etiqueta etiqueta-info">C2C</span>
                            <?php endif; ?>
                            <br>
                            <small class="admin-secundario">
                                <?php if (($v['usuario'] ?? '') !== null && $v['usuario'] !== ''): ?>@<?= $e($v['usuario']) ?> · <?php endif; ?><?= $e($v['email']) ?>
                            </small>
                        </td>
                        <td><?= $v['comision_porcentaje'] !== null ? $e($v['comision_porcentaje']) . ' %' : '<span class="admin-secundario">global</span>' ?></td>
                        <td><?= (int) $v['productos'] ?></td>
                        <td><?= $s((float) $v['ventas']) ?></td>
                        <td><?= $s((float) $v['comisiones']) ?></td>
                        <td><?= $s((float) $v['ganancias']) ?></td>
                        <td><?= $s((float) $v['liquidado']) ?></td>
                        <td><?= $saldo > 0 ? '<strong>' . $s($saldo) . '</strong>' : $s($saldo) ?></td>
                        <td>
                            <span class="etiqueta <?= (int) $v['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>">
                                <?= (int) $v['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                            <?php if ((int) $v['verificado'] === 1): ?>
                                <br><span class="etiqueta etiqueta-info">Verificado</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/vendedores&editar=<?= (int) $v['id'] ?>">Ficha</a>
                            <?php if ($saldo > 0): ?>
                                <a class="btn-mini" href="?ruta=admin/vendedores&liquidar=<?= (int) $v['id'] ?>">Liquidar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$vendedores): ?>
                    <tr><td colspan="10" class="admin-vacio">
                        Aún no hay vendedores. Asigna el rol <code>vendedor</code> o el tipo
                        <code>C2C</code> a un usuario en <a href="?ruta=admin/usuarios">Usuarios</a>
                        para empezar.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
