<?php
declare(strict_types=1);

$banners = $datos['banners'];
$editando = $datos['editando'];
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Banners de la tienda</h2>
            <p class="admin-nota">Promociones del carrusel. Solo los administradores pueden modificarlas.</p>
        </div>
        <?php if (!$editando): ?>
        <button type="button" class="btn btn-primario" data-modal-abrir="modalBanner" data-limpiar="1">Nuevo banner</button>
        <?php endif; ?>
    </div>

    <dialog class="admin-modal" id="modalBanner" aria-labelledby="tituloBanner"<?= $editando ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/banners/guardar" enctype="multipart/form-data" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <input type="hidden" name="id" value="<?= (int) ($editando['id'] ?? 0) ?>">
            <div class="admin-modal-cabecera">
                <h3 id="tituloBanner"><?= $editando ? 'Editar banner' : 'Nuevo banner' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <label>Título
                    <input type="text" name="titulo" maxlength="120" required value="<?= $e($editando['titulo'] ?? '') ?>">
                </label>
                <label>Subtítulo
                    <input type="text" name="subtitulo" maxlength="255" value="<?= $e($editando['subtitulo'] ?? '') ?>">
                </label>
                <label>Enlace (opcional)
                    <input type="text" name="enlace" maxlength="255" placeholder="?ruta=catalogo" value="<?= $e($editando['enlace'] ?? '') ?>">
                </label>
                <label>Orden (menor primero)
                    <input type="number" name="orden" min="0" max="2147483647" step="1" required value="<?= (int) ($editando['orden'] ?? 0) ?>">
                </label>
                <label>Imagen<?= $editando ? ' (dejar vacía para conservar la actual)' : '' ?>
                    <input type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="ayudaImagenBanner"<?= $editando ? '' : ' required' ?>>
                </label>
                <p class="admin-nota" id="ayudaImagenBanner">JPEG, PNG, WebP o GIF, máximo 3 MB. Recomendado: 1920 × 720.</p>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1"<?= !$editando || $editando['activo'] ? ' checked' : '' ?>>
                    Visible en la tienda
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario">Guardar banner</button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/banners">Cancelar</button>
            </div>
        </form>
    </dialog>

    <div class="admin-tabla-wrap" tabindex="0" aria-label="Banners de la tienda">
        <table class="tabla-admin">
            <thead><tr><th scope="col">Imagen</th><th scope="col">Título</th><th scope="col">Orden</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($banners as $banner): ?>
                <tr>
                    <td><img src="?ruta=banner/imagen&amp;id=<?= (int) $banner['id'] ?>" alt="<?= $e($banner['titulo']) ?>" width="160" height="60" loading="lazy"></td>
                    <td><?= $e($banner['titulo']) ?></td>
                    <td><?= (int) $banner['orden'] ?></td>
                    <td><span class="etiqueta <?= $banner['activo'] ? 'etiqueta-ok' : 'etiqueta-off' ?>"><?= $banner['activo'] ? 'Activo' : 'Oculto' ?></span></td>
                    <td class="admin-acciones">
                        <a class="btn-mini" href="?ruta=admin/banners&amp;editar=<?= (int) $banner['id'] ?>">Editar</a>
                        <?= \Security\Proteccion::botonPost('admin/banners/estado', ['id' => $banner['id']], $banner['activo'] ? 'Ocultar' : 'Activar') ?>
                        <?= \Security\Proteccion::botonPost('admin/banners/eliminar', ['id' => $banner['id']], 'Eliminar', 'btn-mini btn-mini-peligro', '¿Eliminar este banner? No se puede deshacer.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$banners): ?>
                <tr><td colspan="5" class="admin-vacio">No hay banners. Usa «Nuevo banner» para publicar la primera promoción.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
