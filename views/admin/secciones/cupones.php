<?php
$pdo = conexion();
$admin = \Model\Administrador::usuarioActual();

$cupones = $pdo->query('SELECT id, codigo, tipo, valor, fecha_inicio, fecha_fin, usos_maximos, usos_actuales, activo FROM cupones ORDER BY id DESC')->fetchAll();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $q = $pdo->prepare('SELECT * FROM cupones WHERE id = ? LIMIT 1');
    $q->execute([$idEditar]);
    $editando = $q->fetch() ?: null;
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Cupones</h2>
            <p class="admin-nota">Descuentos del carrito.</p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nuevo cupón</button>
            <?php endif; ?>
        </div>
    </div>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/cupones/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar cupón' : 'Nuevo cupón' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['id'] ?? 0) ?>">
                <label>Código
                    <input type="text" name="codigo" required value="<?= $e($editando['codigo'] ?? '') ?>" placeholder="EJEMPLO20">
                </label>
                <label>Tipo
                    <select name="tipo">
                        <option value="porcentaje" <?= ($editando['tipo'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                        <option value="fijo" <?= ($editando['tipo'] ?? '') === 'fijo' ? 'selected' : '' ?>>Monto fijo (S/)</option>
                    </select>
                </label>
                <label>Valor
                    <input type="number" step="0.01" min="0" name="valor" required value="<?= $e($editando['valor'] ?? '0') ?>">
                </label>
                <label>Fecha inicio
                    <input type="date" name="fecha_inicio" value="<?= $e($editando['fecha_inicio'] ?? '') ?>">
                </label>
                <label>Fecha fin
                    <input type="date" name="fecha_fin" value="<?= $e($editando['fecha_fin'] ?? '') ?>">
                </label>
                <label>Usos máximos (vacío = ilimitado)
                    <input type="number" min="0" name="usos_maximos" value="<?= $e($editando['usos_maximos'] ?? '') ?>">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= !isset($editando['activo']) || $editando['activo'] ? 'checked' : '' ?>>
                    Activo
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/cupones">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead><tr><th>ID</th><th>Código</th><th>Tipo</th><th>Valor</th><th>Vigencia</th><th>Usos</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($cupones as $c): ?>
                    <tr>
                        <td><?= (int) $c['id'] ?></td>
                        <td><strong><?= $e($c['codigo']) ?></strong></td>
                        <td><?= $e($c['tipo']) ?></td>
                        <td><?= $c['tipo'] === 'porcentaje' ? (float) $c['valor'] . '%' : 'S/ ' . number_format((float) $c['valor'], 2) ?></td>
                        <td class="admin-detalle">
                            <?= $e($c['fecha_inicio'] ?: '—') ?>
                            <?= $c['fecha_fin'] ? ' → ' . $e($c['fecha_fin']) : ' → (sin fin)' ?>
                        </td>
                        <td><?= (int) ($c['usos_actuales'] ?? 0) ?><?= $c['usos_maximos'] !== null ? ' / ' . (int) $c['usos_maximos'] : ' / ∞' ?></td>
                        <td><span class="etiqueta <?= (int) $c['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= (int) $c['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/cupones&editar=<?= (int) $c['id'] ?>">Editar</a>
                            <a class="btn-mini" href="?ruta=admin/cupones/estado&id=<?= (int) $c['id'] ?>"><?= (int) $c['activo'] ? 'Desactivar' : 'Activar' ?></a>
                            <a class="btn-mini btn-mini-peligro" href="?ruta=admin/cupones/eliminar&id=<?= (int) $c['id'] ?>" onclick="return confirm('¿Eliminar este cupón?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$cupones): ?>
                    <tr><td colspan="8" class="admin-vacio">Aún no hay cupones.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
