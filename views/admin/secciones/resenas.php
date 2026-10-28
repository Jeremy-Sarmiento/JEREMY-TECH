<?php
/**
 * Vista: Moderación de reseñas.
 *
 * Política de la tienda: la reseña se publica al instante y el equipo puede
 * ocultarla después (con motivo) o volver a publicarla. La vista consulta sus
 * propios datos porque `renderizar()` no hace extract().
 */
use Model\Resena;

$estadoFiltro = trim((string) ($_GET['estado'] ?? ''));
if (!in_array($estadoFiltro, ['visible', 'oculta'], true)) {
    $estadoFiltro = '';
}
$buscar = trim((string) ($_GET['q'] ?? ''));

$resenas = Resena::todas($estadoFiltro, $buscar, 300);
$resumen = Resena::resumenModeracion();

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '—';
    }
    $ts = strtotime($valor);
    return $ts === false ? $valor : date('d/m/Y H:i', $ts);
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Moderación de reseñas</h2>
            <p class="admin-nota">
                Las reseñas se publican al instante. Si una incumple las normas, ocúltala:
                dejará de aparecer en la ficha del producto y en el promedio de estrellas.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <a class="btn btn-claro" href="?ruta=admin/productos">Ver productos</a>
        </div>
    </div>

    <div class="tarjetas">
        <div class="tarjeta tarjeta-cyan">
            <span class="tarjeta-numero"><?= (int) $resumen['total'] ?></span>
            <span class="tarjeta-etiqueta">Reseñas</span>
        </div>
        <div class="tarjeta tarjeta-verde">
            <span class="tarjeta-numero"><?= (int) $resumen['visibles'] ?></span>
            <span class="tarjeta-etiqueta">Visibles</span>
        </div>
        <div class="tarjeta tarjeta-rojo">
            <span class="tarjeta-numero"><?= (int) $resumen['ocultas'] ?></span>
            <span class="tarjeta-etiqueta">Ocultas</span>
        </div>
    </div>

    <form method="get" class="admin-filtro">
        <input type="hidden" name="ruta" value="admin/resenas">
        <input type="text" name="q" value="<?= $e($buscar) ?>"
               placeholder="Buscar comentario, producto o cliente...">
        <select name="estado">
            <option value="">Todos los estados</option>
            <option value="visible" <?= $estadoFiltro === 'visible' ? 'selected' : '' ?>>Visibles</option>
            <option value="oculta" <?= $estadoFiltro === 'oculta' ? 'selected' : '' ?>>Ocultas</option>
        </select>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-claro" href="?ruta=admin/resenas">Limpiar</a>
    </form>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>ID</th><th>Producto</th><th>Cliente</th><th>Nota</th>
                    <th>Comentario</th><th>Estado</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$resenas): ?>
                <tr><td colspan="7" class="admin-vacio">
                    No hay reseñas para este filtro. Las publican los clientes que
                    compraron el producto desde «Mis pedidos».
                </td></tr>
            <?php endif; ?>
            <?php foreach ($resenas as $r): ?>
                <?php
                $id = (int) $r['id'];
                $estado = (string) ($r['estado'] ?? 'visible');
                $cliente = trim((string) ($r['cliente_nombre'] ?? '') . ' ' . (string) ($r['cliente_apellidos'] ?? ''));
                if ($cliente === '') {
                    $cliente = 'Cliente eliminado';
                }
                $producto = (string) ($r['producto_nombre'] ?? 'Producto #' . (int) $r['producto_id']);
                $calificacion = (int) $r['calificacion'];
                ?>
                <tr id="resena-<?= $id ?>">
                    <td><?= $id ?></td>
                    <td>
                        <?php if (trim((string) ($r['producto_slug'] ?? '')) !== ''): ?>
                            <a href="?ruta=producto&amp;slug=<?= $e((string) $r['producto_slug']) ?>" target="_blank" rel="noopener">
                                <?= $e($producto) ?>
                            </a>
                        <?php else: ?>
                            <?= $e($producto) ?>
                        <?php endif; ?>
                        <small class="admin-secundario"><?= $e($fecha((string) ($r['creado_en'] ?? ''))) ?></small>
                    </td>
                    <td>
                        <?= $e($cliente) ?>
                        <small class="admin-secundario"><?= $e((string) ($r['cliente_email'] ?? '')) ?></small>
                    </td>
                    <td>
                        <span class="resena-nota-admin" title="<?= $calificacion ?> de 5">
                            <?= $e(Resena::estrellas((float) $calificacion)) ?>
                        </span>
                        <small class="admin-secundario"><?= $calificacion ?>/5</small>
                    </td>
                    <td class="admin-detalle">
                        <?= $e((string) $r['comentario']) ?>
                        <?php if ($estado === 'oculta' && trim((string) ($r['motivo_oculto'] ?? '')) !== ''): ?>
                            <div class="resena-motivo-admin">
                                <strong>Motivo:</strong> <?= $e((string) $r['motivo_oculto']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (trim((string) ($r['moderado_por'] ?? '')) !== ''): ?>
                            <small class="admin-secundario">
                                Moderada por <?= $e((string) $r['moderado_por']) ?>
                                · <?= $e($fecha((string) ($r['moderado_en'] ?? ''))) ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="etiqueta <?= $estado === 'oculta' ? 'etiqueta-off' : 'etiqueta-ok' ?>">
                            <?= $e(Resena::etiquetaEstado($estado)) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($estado === 'oculta'): ?>
                            <form method="post" action="?ruta=admin/resenas/mostrar">
                                <?= \Security\Proteccion::campo() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn-mini">Publicar de nuevo</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="?ruta=admin/resenas/ocultar" class="admin-form-inline admin-form-columna">
                                <?= \Security\Proteccion::campo() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="text" name="motivo" maxlength="255"
                                       placeholder="Motivo (opcional)">
                                <button type="submit" class="btn-mini btn-mini-peligro">Ocultar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
