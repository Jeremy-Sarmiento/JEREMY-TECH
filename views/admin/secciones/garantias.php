<?php
$pdo = conexion();

$garantias = $pdo->query(
    'SELECT g.*, (SELECT COUNT(*) FROM productos p WHERE p.garantia_id = g.id) AS usos
     FROM garantias g ORDER BY g.nombre'
)->fetchAll();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $q = $pdo->prepare('SELECT * FROM garantias WHERE id = ? LIMIT 1');
    $q->execute([$idEditar]);
    $editando = $q->fetch() ?: null;
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Garantías</h2>
            <p class="admin-nota">Catálogo de garantías que se asocian a cada producto.</p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nueva garantía</button>
            <?php endif; ?>
        </div>
    </div>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/garantias/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar garantía' : 'Nueva garantía' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['id'] ?? 0) ?>">
                <label>Nombre
                    <input type="text" name="nombre" required value="<?= $e($editando['nombre'] ?? '') ?>">
                </label>
                <label>Duración (meses)
                    <input type="number" name="duracion_meses" min="1" max="60" required value="<?= $e($editando['duracion_meses'] ?? 6) ?>">
                </label>
                <label>Cobertura
                    <input type="text" name="cobertura" value="<?= $e($editando['cobertura'] ?? '') ?>">
                </label>
                <label>Precio (S/)
                    <input type="number" step="0.01" min="0" name="precio_pen" value="<?= $e($editando['precio_pen'] ?? '0') ?>">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= !isset($editando['activo']) || $editando['activo'] ? 'checked' : '' ?>>
                    Activa (disponible para asignar)
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/garantias">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Duración</th>
                        <th>Cobertura</th>
                        <th>Precio</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($garantias as $g): ?>
                    <tr>
                        <td><?= (int) $g['id'] ?></td>
                        <td><?= $e($g['nombre']) ?></td>
                        <td><?= (int) $g['duracion_meses'] ?> meses</td>
                        <td><?= $e($g['cobertura']) ?: '—' ?></td>
                        <td>S/ <?= number_format((float) $g['precio_pen'], 2) ?></td>
                        <td><?= (int) $g['usos'] ?></td>
                        <td><span class="etiqueta <?= (int) $g['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= (int) $g['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/garantias&editar=<?= (int) $g['id'] ?>">Editar</a>
                            <a class="btn-mini" href="?ruta=admin/garantias/estado&id=<?= (int) $g['id'] ?>"><?= (int) $g['activo'] ? 'Desactivar' : 'Activar' ?></a>
                            <a class="btn-mini btn-mini-peligro" href="?ruta=admin/garantias/eliminar&id=<?= (int) $g['id'] ?>" onclick="return confirm('¿Eliminar esta garantía?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$garantias): ?>
                    <tr><td colspan="8" class="admin-vacio">Aún no hay garantías.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
