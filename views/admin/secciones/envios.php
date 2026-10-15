<?php
$pdo = conexion();
$admin = \Model\Administrador::usuarioActual();

$envios = $pdo->query('SELECT id, zona, costo, dias_estimados, activo FROM tarifas_envio ORDER BY zona')->fetchAll();

$editando = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $q = $pdo->prepare('SELECT * FROM tarifas_envio WHERE id = ? LIMIT 1');
    $q->execute([$idEditar]);
    $editando = $q->fetch() ?: null;
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Tarifas de envío</h2>
            <p class="admin-nota">Costos por zona de entrega.</p>
        </div>
        <div class="admin-encabezado-acciones">
            <?php if (!$editando): ?>
                <button type="button" class="btn btn-primario" data-modal-abrir="modalForm" data-limpiar="1">Nueva tarifa</button>
            <?php endif; ?>
        </div>
    </div>

    <dialog class="admin-modal" id="modalForm"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/envios/guardar" class="admin-formulario">
            <div class="admin-modal-cabecera">
                <h3><?= $editando ? 'Editar tarifa' : 'Nueva tarifa' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <input type="hidden" name="id" value="<?= $e($editando['id'] ?? 0) ?>">
                <label>Zona
                    <input type="text" name="zona" required value="<?= $e($editando['zona'] ?? '') ?>" placeholder="Lima centro">
                </label>
                <label>Costo (S/)
                    <input type="number" step="0.01" min="0" name="costo" required value="<?= $e($editando['costo'] ?? '0') ?>">
                </label>
                <label>Días estimados
                    <input type="number" min="0" name="dias_estimados" value="<?= $e($editando['dias_estimados'] ?? '0') ?>">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= !isset($editando['activo']) || $editando['activo'] ? 'checked' : '' ?>>
                    Activa
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $editando ? 'Guardar' : 'Crear' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/envios">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tarjetas-grid">
        <div class="admin-tabla-wrap">
            <table class="tabla-admin">
                <thead><tr><th>ID</th><th>Zona</th><th>Costo</th><th>Días</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($envios as $v): ?>
                    <tr>
                        <td><?= (int) $v['id'] ?></td>
                        <td><?= $e($v['zona']) ?></td>
                        <td><?= number_format((float) $v['costo'], 2) ?></td>
                        <td><?= (int) $v['dias_estimados'] ?></td>
                        <td><span class="etiqueta <?= (int) $v['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= (int) $v['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
                        <td class="admin-acciones">
                            <a class="btn-mini" href="?ruta=admin/envios&editar=<?= (int) $v['id'] ?>">Editar</a>
                            <a class="btn-mini" href="?ruta=admin/envios/estado&id=<?= (int) $v['id'] ?>"><?= (int) $v['activo'] ? 'Desactivar' : 'Activar' ?></a>
                            <a class="btn-mini btn-mini-peligro" href="?ruta=admin/envios/eliminar&id=<?= (int) $v['id'] ?>" onclick="return confirm('¿Eliminar esta tarifa?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$envios): ?>
                    <tr><td colspan="6" class="admin-vacio">Aún no hay tarifas de envío.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
