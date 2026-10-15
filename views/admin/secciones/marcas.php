<?php
$pdo = conexion();
$admin = \Model\Administrador::usuarioActual();

$marcas = $pdo->query('SELECT id, nombre, slug, activo FROM marcas ORDER BY nombre')->fetchAll();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $q = $pdo->prepare('SELECT * FROM marcas WHERE id = ? LIMIT 1');
    $q->execute([$idEditar]);
    $editando = $q->fetch() ?: null;
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Marcas</h2>
            <p class="admin-nota">Fabricantes del catálogo.</p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nueva marca</button>
            <?php endif; ?>
        </div>
    </div>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/marcas/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar marca' : 'Nueva marca' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['id'] ?? 0) ?>">
                <label>Nombre
                    <input type="text" name="nombre" required value="<?= $e($editando['nombre'] ?? '') ?>">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= !isset($editando['activo']) || $editando['activo'] ? 'checked' : '' ?>>
                    Activa
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/marcas">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead><tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($marcas as $m): ?>
                    <tr>
                        <td><?= (int) $m['id'] ?></td>
                        <td><?= $e($m['nombre']) ?></td>
                        <td><code><?= $e($m['slug']) ?></code></td>
                        <td><span class="etiqueta <?= (int) $m['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= (int) $m['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/marcas&editar=<?= (int) $m['id'] ?>">Editar</a>
                            <a class="btn-mini btn-mini-peligro" href="?ruta=admin/marcas/eliminar&id=<?= (int) $m['id'] ?>" onclick="return confirm('¿Eliminar esta marca?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$marcas): ?>
                    <tr><td colspan="5" class="admin-vacio">Aún no hay marcas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
