<?php
$pdo = conexion();
$admin = \Model\Administrador::usuarioActual();

$categorias = $pdo->query('SELECT id, nombre, slug, descripcion FROM categorias ORDER BY nombre')->fetchAll();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $q = $pdo->prepare('SELECT * FROM categorias WHERE id = ? LIMIT 1');
    $q->execute([$idEditar]);
    $editando = $q->fetch() ?: null;
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Categorías</h2>
            <p class="admin-nota">Agrupaciones del catálogo.</p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nueva categoría</button>
            <?php endif; ?>
        </div>
    </div>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/categorias/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['id'] ?? 0) ?>">
                <label>Nombre
                    <input type="text" name="nombre" required value="<?= $e($editando['nombre'] ?? '') ?>">
                </label>
                <label>Descripción
                    <input type="text" name="descripcion" value="<?= $e($editando['descripcion'] ?? '') ?>">
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/categorias">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead><tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Descripción</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($categorias as $c): ?>
                    <tr>
                        <td><?= (int) $c['id'] ?></td>
                        <td><?= $e($c['nombre']) ?></td>
                        <td><code><?= $e($c['slug']) ?></code></td>
                        <td><?= $e($c['descripcion']) ?></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/categorias&editar=<?= (int) $c['id'] ?>">Editar</a>
                            <a class="btn-mini btn-mini-peligro" href="?ruta=admin/categorias/eliminar&id=<?= (int) $c['id'] ?>" onclick="return confirm('¿Eliminar esta categoría?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$categorias): ?>
                    <tr><td colspan="5" class="admin-vacio">Aún no hay categorías.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
