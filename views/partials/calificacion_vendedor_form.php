<?php
/**
 * Formulario de calificación de un vendedor (Bloque 3).
 *
 * Variables esperadas:
 *   $califVendedorId     int    Id del vendedor.
 *   $califVendedorNombre string Nombre visible del vendedor.
 *   $califVendedorActual ?array Calificación existente (calificacion, comentario).
 *   $califVendedorSufijo string Sufijo único para los ids de los radios.
 *
 * Funciona sin JavaScript: son radios nativos que se envían por POST a
 * ?ruta=calificaciones-vendedor/guardar. `cuenta.js` lo convierte en una
 * llamada JSON.
 */
$califVendedorActual = $califVendedorActual ?? null;
$califVendedorId = (int) ($califVendedorId ?? 0);
$califVendedorNombre = (string) ($califVendedorNombre ?? 'este vendedor');
$califVendedorSufijo = (string) ($califVendedorSufijo ?? (string) $califVendedorId);
$calificacionActual = (int) ($califVendedorActual['calificacion'] ?? 0);
$comentarioActual = (string) ($califVendedorActual['comentario'] ?? '');
$idBase = 'calif-vendedor-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $califVendedorSufijo);
$nombreEscapado = htmlspecialchars($califVendedorNombre, ENT_QUOTES, 'UTF-8');
?>
<form method="post"
      action="?ruta=calificaciones-vendedor/guardar"
      class="resena-form calificacion-vendedor-form"
      data-vendedor="<?= $califVendedorId ?>"
      data-vendedor-nombre="<?= $nombreEscapado ?>">
 <?= \Security\Proteccion::campo() ?>
 <input type="hidden" name="vendedor_id" value="<?= $califVendedorId ?>">

 <p class="resena-titulo">
  <?= $calificacionActual > 0 ? 'Tu calificación de ' : 'Califica a ' ?>
  <strong><?= $nombreEscapado ?></strong><?= $calificacionActual > 0 ? ' (puedes cambiarla)' : '' ?>
 </p>

 <div class="resena-estrellas" role="radiogroup" aria-label="Calificación del vendedor de 1 a 5 estrellas">
  <?php for ($estrella = 5; $estrella >= 1; $estrella--): ?>
  <input type="radio"
         id="<?= $idBase ?>-e<?= $estrella ?>"
         name="calificacion"
         value="<?= $estrella ?>"
         <?= $calificacionActual === $estrella ? 'checked' : '' ?>
         required>
  <label for="<?= $idBase ?>-e<?= $estrella ?>"
         title="<?= $estrella ?> estrella<?= $estrella === 1 ? '' : 's' ?>">
   <span aria-hidden="true">★</span>
   <span class="sr-only"><?= $estrella ?> estrella<?= $estrella === 1 ? '' : 's' ?></span>
  </label>
  <?php endfor; ?>
 </div>

 <label class="resena-comentario-label" for="<?= $idBase ?>-comentario">
  Comentario <span>(opcional)</span>
 </label>
 <textarea id="<?= $idBase ?>-comentario"
           name="comentario"
           class="resena-comentario"
           maxlength="500"
           rows="3"
           placeholder="¿Cómo fue tu experiencia con este vendedor?"><?= htmlspecialchars($comentarioActual, ENT_QUOTES) ?></textarea>

 <div class="resena-acciones">
  <button type="submit" class="btn-mini resena-btn">
   <?= $calificacionActual > 0 ? 'Actualizar calificación' : 'Publicar calificación' ?>
  </button>
  <span class="resena-estado" role="status" aria-live="polite"></span>
 </div>
</form>
