<?php
/**
 * Vista: Chatbot FAQ — CRUD de la base de conocimiento administrable.
 * `renderizar()` no hace extract(): la vista calcula sus propios datos.
 */
$faqs = \Model\ChatbotFaq::todas();
$faqEditar = null;
$idEditar = (int) ($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $faqEditar = \Model\ChatbotFaq::porId($idEditar);
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$esEdicion = $faqEditar !== null;
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">FAQ del chatbot</h2>
            <p class="admin-nota">
                Base de conocimiento administrada en <code>chatbot_faq</code>. El chatbot la consulta
                antes de la IA y guarda la pregunta con su origen de respuesta.
            </p>
        </div>
        <div class="admin-encabezado-acciones">
            <button type="button" class="btn btn-primario" data-modal-abrir="modalFaq" data-limpiar="1">
                Nueva pregunta
            </button>
        </div>
    </div>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>ID</th><th>Pregunta</th><th>Palabras clave</th><th>Respuesta</th>
                    <th>Orden</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$faqs): ?>
                <tr><td colspan="7" class="admin-vacio">Todavía no hay preguntas frecuentes registradas.</td></tr>
            <?php endif; ?>
            <?php foreach ($faqs as $faq): ?>
                <tr>
                    <td><?= (int) $faq['id'] ?></td>
                    <td><?= $e($faq['pregunta']) ?></td>
                    <td><?= $e($faq['palabras_clave']) ?></td>
                    <td class="admin-detalle"><?= $e(mb_substr((string) $faq['respuesta'], 0, 120)) ?>...</td>
                    <td><?= (int) $faq['orden'] ?></td>
                    <td>
                        <span class="etiqueta <?= (int) $faq['activo'] === 1 ? 'etiqueta-ok' : 'etiqueta-off' ?>">
                            <?= (int) $faq['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="admin-acciones">
                        <a class="btn-mini" href="?ruta=admin/chatbot&editar=<?= (int) $faq['id'] ?>">Editar</a>
                        <?= \Security\Proteccion::botonPost(
                            'admin/chatbot/estado',
                            ['id' => (string) $faq['id']],
                            (int) $faq['activo'] === 1 ? 'Desactivar' : 'Activar'
                        ) ?>
                        <?= \Security\Proteccion::botonPost(
                            'admin/chatbot/eliminar',
                            ['id' => (string) $faq['id']],
                            'Eliminar',
                            'btn-mini',
                            '¿Eliminar esta pregunta frecuente?'
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <dialog class="admin-modal" id="modalFaq"<?= $esEdicion ? ' data-modal-auto="1"' : '' ?>>
        <form method="post" action="?ruta=admin/chatbot/guardar" class="admin-formulario">
            <?= \Security\Proteccion::campo() ?>
            <input type="hidden" name="id" value="<?= $esEdicion ? (int) $faqEditar['id'] : 0 ?>">

            <div class="admin-modal-cabecera">
                <h3><?= $esEdicion ? 'Editar FAQ' : 'Nueva FAQ' ?></h3>
                <button type="button" class="admin-modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
            </div>
            <div class="admin-modal-cuerpo">
                <label>Pregunta
                    <input type="text" name="pregunta" value="<?= $esEdicion ? $e($faqEditar['pregunta']) : '' ?>" maxlength="255" required>
                </label>
                <label>Palabras clave
                    <input type="text" name="palabras_clave" value="<?= $esEdicion ? $e($faqEditar['palabras_clave']) : '' ?>" maxlength="255" placeholder="garantia, garantias, cambios, devolucion">
                </label>
                <label>Respuesta
                    <textarea name="respuesta" rows="6" maxlength="4000" required><?= $esEdicion ? $e($faqEditar['respuesta']) : '' ?></textarea>
                </label>
                <label>Orden
                    <input type="number" name="orden" value="<?= $esEdicion ? (int) $faqEditar['orden'] : 0 ?>" min="0">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="activo" value="1" <?= $esEdicion ? ((int) $faqEditar['activo'] === 1) ? 'checked' : '' : 'checked' ?>>
                    Activo (responde el chatbot)
                </label>
            </div>
            <div class="admin-modal-pie">
                <button type="submit" class="btn btn-primario"><?= $esEdicion ? 'Guardar cambios' : 'Crear FAQ' ?></button>
                <button type="button" class="btn btn-claro" data-modal-cerrar data-volver="?ruta=admin/chatbot">Cancelar</button>
            </div>
        </form>
    </dialog>
</section>
