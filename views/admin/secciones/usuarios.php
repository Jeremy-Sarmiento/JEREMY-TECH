<?php
$pdo = conexion();
$yoId = (int) (\Model\Administrador::usuarioActual()['id'] ?? 0);

$usuarios = $pdo->query(
    'SELECT id, name, apellidos, email, usuario, rol, activo, ultimo_ingreso, created_at
     FROM users WHERE deleted_at IS NULL
     ORDER BY FIELD(rol, "admin", "vendedor", "cliente"), id'
)->fetchAll();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $q = $pdo->prepare('SELECT id, name, apellidos, email, usuario, rol, activo FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $q->execute([$idEditar]);
    $editando = $q->fetch() ?: null;
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$etiquetaRol = static function (string $rol): string {
    return match ($rol) {
        'admin'    => 'etiqueta etiqueta-ok',
        'vendedor' => 'etiqueta etiqueta-info',
        default    => 'etiqueta',
    };
};
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Usuarios</h2>
            <p class="admin-nota">Todas las cuentas: clientes, vendedores y administradores (Etapa 4).</p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nuevo usuario</button>
            <?php endif; ?>
        </div>
    </div>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/usuarios/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['id'] ?? 0) ?>">
                <label>Nombre
                    <input type="text" name="nombre" required value="<?= $e($editando['name'] ?? '') ?>">
                </label>
                <label>Apellidos
                    <input type="text" name="apellidos" value="<?= $e($editando['apellidos'] ?? '') ?>">
                </label>
                <label>Correo electrónico
                    <input type="email" name="email" required value="<?= $e($editando['email'] ?? '') ?>">
                </label>
                <label>Usuario de acceso (para el panel)
                    <input type="text" name="usuario" value="<?= $e($editando['usuario'] ?? '') ?>"
                           placeholder="<?= $editando && ($editando['rol'] ?? '') === 'cliente' ? 'opcional para clientes' : 'obligatorio' ?>">
                </label>
                <label>Contraseña <?= $editando ? '(dejar vacío = no cambiar)' : '' ?>
                    <input type="password" name="clave" <?= $editando ? '' : 'required' ?>>
                </label>
                <label>Rol
                    <select name="rol">
                        <?php foreach (['cliente', 'vendedor', 'admin'] as $rol): ?>
                            <option value="<?= $rol ?>" <?= ($editando['rol'] ?? 'cliente') === $rol ? 'selected' : '' ?>><?= ucfirst($rol) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= !isset($editando['activo']) || $editando['activo'] ? 'checked' : '' ?>>
                    Activo
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/usuarios">Cancelar</button>
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
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Último ingreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <?php $esYo = (int) $u['id'] === $yoId; ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td>
                            <strong><?= $e($u['name']) ?> <?= $e($u['apellidos']) ?></strong>
                            <div class="admin-secundario"><?= $e($u['email']) ?></div>
                        </td>
                        <td><?= $u['usuario'] !== null && $u['usuario'] !== '' ? $e($u['usuario']) : '—' ?></td>
                        <td><span class="<?= $etiquetaRol((string) $u['rol']) ?>"><?= $e($u['rol']) ?></span></td>
                        <td><span class="etiqueta <?= (int) $u['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= (int) $u['activo'] ? 'Sí' : 'No' ?></span></td>
                        <td><?= $e($u['ultimo_ingreso'] ?: 'nunca') ?></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/usuarios&editar=<?= (int) $u['id'] ?>">Editar</a>
                            <?php if (!$esYo): ?>
                                <a class="btn-mini" href="?ruta=admin/usuarios/estado&id=<?= (int) $u['id'] ?>"><?= (int) $u['activo'] ? 'Desactivar' : 'Activar' ?></a>
                                <a class="btn-mini" href="?ruta=admin/usuarios/eliminar&id=<?= (int) $u['id'] ?>"
                                   onclick="return confirm('¿Dar de baja a este usuario?')">Baja</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$usuarios): ?>
                    <tr><td colspan="7" class="admin-vacio">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
