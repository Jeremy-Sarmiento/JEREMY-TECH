<?php
/**
 * Formulario de reseña de un producto (Fase 2).
 *
 * Variables esperadas:
 *   $resenaProductoId     int    Id del producto.
 *   $resenaProductoNombre string Nombre visible (para el texto accesible).
 *   $resenaActual         ?array Reseña existente (calificacion, comentario).
 *   $resenaSlug           string Slug del producto (vacío desde Mis pedidos).
 *   $resenaSufijo         string Sufijo único para los ids de los radios.
 *
 * Funciona sin JavaScript: son radios nativos que se envían por POST a
 * ?ruta=resenas/guardar. `cuenta.js` lo convierte en una llamada JSON.
 */
$resenaActual = $resenaActual ?? null;
$resenaProductoId = (int) ($resenaProductoId ?? 0);
$resenaSlug = $resenaSlug ?? '';
$resenaSufijo = $resenaSufijo ?? (string) $resenaProductoId;
$calificacionActual = (int) ($resenaActual['calificacion'] ?? 0);
$comentarioActual = (string) ($resenaActual['comentario'] ?? '');
$idBase = 'resena-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $resenaSufijo);
?>
<form method="post"
      action="?ruta=resenas/guardar"
      class="resena-form"
      data-producto="<?= (int) $resenaProductoId ?>">
 <?= \Security\Proteccion::campo() ?>
 <input type="hidden" name="producto_id" value="<?= (int) $resenaProductoId ?>">
 <?php if ($resenaSlug !== ''): ?>
 <input type="hidden" name="slug" value="<?= htmlspecialchars($resenaSlug, ENT_QUOTES) ?>">
 <?php endif; ?>

 <?php if ((string) ($resenaActual['estado'] ?? 'visible') === 'oculta'): ?>
 <p class="resena-aviso-oculta">
  Esta opinión está oculta del catálogo. Puedes editarla, pero seguirá oculta
  hasta que el equipo la publique de nuevo.
 </p>
 <?php endif; ?>

 <p class="resena-titulo">
  <?= $calificacionActual > 0 ? 'Tu calificación (puedes cambiarla)' : 'Califica este producto' ?>
 </p>
 <div class="resena-estrellas" role="radiogroup" aria-label="Calificación de 1 a 5 estrellas">
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
           placeholder="Cuéntanos qué te pareció <?= htmlspecialchars($resenaProductoNombre, ENT_QUOTES) ?>"><?= htmlspecialchars($comentarioActual, ENT_QUOTES) ?></textarea>

 <div class="resena-acciones">
  <button type="submit" class="btn-mini resena-btn">
   <?= $calificacionActual > 0 ? 'Actualizar opinión' : 'Publicar opinión' ?>
  </button>
  <span class="resena-estado" role="status" aria-live="polite"></span>
 </div>
</form>
