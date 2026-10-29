<?php
/**
 * VISTA: Mis direcciones de envío (Jeremy Tech MVC) — Etapa 5.
 * Datos de entrada (los prepara src/Controller/DireccionController::ver()):
 *   $direcciones, $zonas, $editando, $flash.
 */

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $monto): string => 'PEN ' . number_format($monto, 2);

$zonaIdEditando = $editando !== null && $editando['tarifa_envio_id'] !== null
    ? (int) $editando['tarifa_envio_id']
    : 0;
?>
<main class="cart-page">

 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pin"/></svg>Mis Direcciones de Envío</h1>
  <p>Guarda tus direcciones y asígnales una zona: la zona es lo que define el costo y los días de entrega de tu pedido.</p>
 </div>

 <?php if ($flash['mensaje']): ?>
 <div class="auth-flash auth-flash-<?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>"><?= $e($flash['mensaje']) ?></div>
 <?php endif; ?>

 <!-- ================= LISTADO ================= -->
 <section class="cart-page-section">
  <div class="cart-page-section-head">
   <h2>Tus direcciones guardadas</h2>
   <p>La predeterminada se preselecciona en el checkout.</p>
  </div>

  <?php if (!$direcciones): ?>
  <div class="orders-empty">
   <div class="empty-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pin"/></svg></div>
   <h4>Aún no tienes direcciones</h4>
   <p>Agrega tu primera dirección más abajo para poder pedir envío a domicilio.</p>
  </div>
  <?php else: ?>
  <div class="addr-grid">
   <?php foreach ($direcciones as $d): ?>
   <article class="addr-card<?= (int) $d['predeterminada'] === 1 ? ' addr-predeterminada' : '' ?>">
    <header class="addr-cabecera">
     <span class="addr-etiqueta"><?= $e($d['etiqueta']) ?></span>
     <?php if ((int) $d['predeterminada'] === 1): ?>
     <span class="addr-badge">Predeterminada</span>
     <?php endif; ?>
    </header>
    <p class="addr-texto"><?= $e($d['direccion']) ?></p>
    <?php if ((string) ($d['referencia'] ?? '') !== ''): ?>
    <p class="addr-referencia">Ref. <?= $e($d['referencia']) ?></p>
    <?php endif; ?>
    <p class="addr-zona">
     <?= $e($d['zona'] ?? 'Sin zona asignada') ?>
     <?php if ($d['zona'] !== null): ?>
      — <?= $fmt((float) $d['costo']) ?> (<?= $e($d['dias_estimados'] ?? '') ?>)
     <?php endif; ?>
    </p>
    <footer class="addr-acciones">
     <a class="btn-mini" href="?ruta=direcciones&editar=<?= (int) $d['id'] ?>">Editar</a>
     <?php if ((int) $d['predeterminada'] !== 1): ?>
     <?= \Security\Proteccion::botonPost('direcciones/predeterminada', ['id' => (int) $d['id']], 'Usar por defecto') ?>
     <?php endif; ?>
     <?= \Security\Proteccion::botonPost('direcciones/eliminar', ['id' => (int) $d['id']], 'Eliminar', 'btn-mini btn-mini-peligro', '¿Eliminar esta dirección?') ?>
    </footer>
   </article>
   <?php endforeach; ?>
  </div>
  <?php endif; ?>
 </section>

 <!-- ================= ALTA / EDICIÓN ================= -->
 <section class="cart-page-section">
  <div class="cart-page-section-head">
   <h2><?= $editando ? 'Editar dirección' : 'Nueva dirección' ?></h2>
   <p>Elige la zona que corresponde al destino: de ahí sale el costo de envío.</p>
  </div>

  <?php if (!$zonas): ?>
  <div class="orders-empty">
   <div class="empty-icon"><svg class="inline-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bell"/></svg></div>
   <h4>No hay zonas de envío disponibles</h4>
   <p>Pregunta al administrador de la tienda: sin zonas activas no se pueden crear direcciones ni pedir envío a domicilio.</p>
  </div>
  <?php else: ?>
  <form method="post" action="?ruta=direcciones/guardar" class="addr-form">
      <?= \Security\Proteccion::campo() ?>
   <input type="hidden" name="id" value="<?= (int) ($editando['id'] ?? 0) ?>">

   <div class="form-row">
    <div class="form-group">
     <label for="dirEtiqueta">Etiqueta</label>
     <input type="text" id="dirEtiqueta" name="etiqueta" maxlength="60"
            placeholder="Casa, Oficina, Trabajo…" value="<?= $e($editando['etiqueta'] ?? '') ?>">
    </div>
    <div class="form-group">
     <label for="dirZona">Zona de envío *</label>
     <select id="dirZona" name="tarifa_envio_id" required>
      <?php foreach ($zonas as $z): ?>
      <option value="<?= (int) $z['id'] ?>" <?= $zonaIdEditando === (int) $z['id'] ? 'selected' : '' ?>>
       <?= $e($z['zona']) ?> — <?= $fmt((float) $z['costo']) ?> (<?= $e($z['dias_estimados']) ?>)
      </option>
      <?php endforeach; ?>
     </select>
    </div>
   </div>

   <div class="form-group">
    <label for="dirDireccion">Dirección completa *</label>
    <input type="text" id="dirDireccion" name="direccion" maxlength="255" required
           placeholder="Ej. Urb. Los Álamos Mz. B Lt. 12, La Joya" value="<?= $e($editando['direccion'] ?? '') ?>">
   </div>

   <div class="form-group">
    <label for="dirReferencia">Referencia</label>
    <input type="text" id="dirReferencia" name="referencia" maxlength="180"
           placeholder="Ej. Frente al parque, puerta verde" value="<?= $e($editando['referencia'] ?? '') ?>">
   </div>

   <label class="form-check addr-check">
    <input type="checkbox" name="predeterminada" value="1"
           <?= (int) ($editando['predeterminada'] ?? 0) === 1 || $direcciones === [] ? 'checked' : '' ?>>
    Usar como dirección predeterminada
   </label>

   <div class="addr-form-acciones">
    <button type="submit" class="btn-pagar-ahora addr-btn"><?= $editando ? 'Guardar cambios' : 'Guardar dirección' ?></button>
    <?php if ($editando): ?>
    <a class="btn-mini" href="?ruta=direcciones">Cancelar edición</a>
    <?php endif; ?>
   </div>
  </form>
  <?php endif; ?>
 </section>

</main>
