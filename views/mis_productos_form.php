<?php
/**
 * VISTA: Formulario de publicación (Jeremy Tech) — Fase 3.
 *
 * Datos de entrada (src/Controller/VendedorController::formulario()):
 *   $formulario, $esEdicion, $slugEditar, $categorias, $marcas,
 *   $garantias, $flash, $variantes, $tramos, $editandoTramo.
 *
 * El formulario es una página dedicada (no el modal del panel admin) para
 * conservar la estética de la tienda. Si una validación falla, el
 * controlador devuelve aquí el borrador del POST.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $monto): string => 'S/ ' . number_format($monto, 2);
$imagenPredeterminada = 'img/producto-sin-imagen.svg';
$valor = static fn (string $clave, string $porDefecto = ''): string => (string) ($formulario[$clave] ?? $porDefecto);
$categoriaActual = $valor('categoria');
$imagenActual = !empty($formulario['imagen_db']) && $slugEditar !== ''
    ? '?ruta=producto/imagen&slug=' . rawurlencode($slugEditar)
    : (trim($valor('imagen')) !== '' ? $valor('imagen') : $imagenPredeterminada);
?>
<main class="cart-page cuenta-page mis-productos-page mis-productos-form-page">
 <div class="cart-page-header pv-form-header">
  <div>
   <h1><?= $esEdicion ? 'Editar publicación' : '＋ Publicar producto' ?></h1>
   <p><?= $esEdicion
       ? 'Actualiza los datos, el stock o la visibilidad de tu publicación.'
       : 'Completa la ficha para que tu producto aparezca en el catálogo.' ?></p>
  </div>
  <a class="btn-mini" href="?ruta=mis-productos">← Volver a mis productos</a>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <form method="post" action="?ruta=mis-productos/guardar" class="pv-form" id="productoForm"
       enctype="multipart/form-data" data-imagen-default="<?= $e($imagenPredeterminada) ?>">
  <?= \Security\Proteccion::campo() ?>
  <input type="hidden" name="slug_editar" value="<?= $e($slugEditar) ?>"/>
  <?php if ($esEdicion): ?>
  <input type="hidden" name="stock_original" value="<?= $e($valor('stock_original')) ?>"/>
  <?php endif; ?>

  <div class="pv-form-grid">
   <section class="cart-page-section pv-form-card">
    <div class="cart-page-section-head">
     <h2>Información básica</h2>
     <p>Lo que verá el comprador en el catálogo y en la ficha.</p>
    </div>

    <div class="form-group">
     <label for="productoNombre">Nombre del producto *</label>
     <input id="productoNombre" type="text" name="nombre" maxlength="190" required
            value="<?= $e($valor('nombre')) ?>" placeholder="Ej. Audífonos Bluetooth SoundCore"/>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="productoModelo">Modelo</label>
      <input id="productoModelo" type="text" name="modelo" maxlength="120"
             value="<?= $e($valor('modelo')) ?>" placeholder="Ej. Life Q30"/>
     </div>
     <div class="form-group">
       <label for="productoSku">SKU automático</label>
       <input id="productoSku" type="text" readonly
              value="<?= $e($producto['codigo_sku'] ?? '') ?>" placeholder="Se asigna al guardar (JT-...)"/>
     </div>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="productoCategoria">Categoría *</label>
      <select id="productoCategoria" name="categoria" required>
       <option value="">Selecciona una categoría...</option>
       <?php foreach ($categorias as $c): ?>
       <option value="<?= $e($c['slug']) ?>"
        <?= ($categoriaActual === (string) $c['slug'] || $categoriaActual === (string) $c['nombre']) ? 'selected' : '' ?>>
        <?= $e($c['nombre']) ?>
       </option>
       <?php endforeach; ?>
      </select>
     </div>
     <div class="form-group">
      <label for="productoMarca">Marca</label>
      <select id="productoMarca" name="marca_id">
       <option value="">Sin marca</option>
       <?php foreach ($marcas as $m): ?>
       <option value="<?= (int) $m['id'] ?>" <?= $valor('marca_id') === (string) $m['id'] ? 'selected' : '' ?>>
        <?= $e($m['nombre']) ?>
       </option>
       <?php endforeach; ?>
      </select>
     </div>
    </div>

    <div class="form-group">
     <label for="productoGarantia">Garantía</label>
     <select id="productoGarantia" name="garantia_id">
      <option value="">Sin garantía</option>
      <?php foreach ($garantias as $g): ?>
      <option value="<?= (int) $g['id'] ?>" <?= $valor('garantia_id') === (string) $g['id'] ? 'selected' : '' ?>>
       <?= $e($g['nombre']) ?>
      </option>
      <?php endforeach; ?>
     </select>
    </div>
   </section>

   <section class="cart-page-section pv-form-card">
    <div class="cart-page-section-head">
     <h2>Precio e inventario</h2>
     <p>El stock bajo se marca con el mínimo que definas aquí.</p>
    </div>

    <div class="form-group">
     <label for="productoPrecio">Precio (S/) *</label>
     <input id="productoPrecio" type="number" name="precio_pen" min="0" step="0.01" required
            value="<?= $e($valor('precio_pen')) ?>" placeholder="0.00" inputmode="decimal"/>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="productoStock">Stock disponible *</label>
      <input id="productoStock" type="number" name="stock" min="0" max="9999999" step="1"
             value="<?= $e($valor('stock', '0')) ?>" inputmode="numeric"<?= !empty($variantes) ? ' readonly aria-describedby="stock-por-variante"' : '' ?>/>
      <?php if (!empty($variantes)): ?>
      <small class="pv-ayuda" id="stock-por-variante">Ajusta el stock desde <a href="#variantes">cada variante</a>; el total se actualiza junto con ella.</small>
      <?php endif; ?>
     </div>
     <div class="form-group">
      <label for="productoStockMinimo">Stock mínimo</label>
      <input id="productoStockMinimo" type="number" name="stock_minimo" min="0" max="9999999" step="1"
             value="<?= $e($valor('stock_minimo', '5')) ?>" inputmode="numeric"/>
     </div>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="productoPeso">Peso (kg)</label>
      <input id="productoPeso" type="number" name="peso_kg" min="0" step="0.001"
             value="<?= $e($valor('peso_kg')) ?>" placeholder="Opcional" inputmode="decimal"/>
     </div>
     <div class="form-group">
      <label for="productoDimensiones">Dimensiones</label>
      <input id="productoDimensiones" type="text" name="dimensiones" maxlength="80"
             value="<?= $e($valor('dimensiones')) ?>" placeholder="Ej. 15.5 x 7.6 x 0.8 cm"/>
     </div>
    </div>
   </section>

   <section class="cart-page-section pv-form-card">
    <div class="cart-page-section-head">
     <h2>Detalles del producto</h2>
     <p>Ayuda al comprador a decidir con información clara.</p>
    </div>

    <div class="form-group">
     <label for="productoEspecificaciones">Especificaciones</label>
     <textarea id="productoEspecificaciones" name="especificaciones" rows="4"
               maxlength="5000" placeholder="Ej. Bluetooth 5.0, autonomía 40 h, cancelación de ruido..."><?= $e($valor('especificaciones')) ?></textarea>
    </div>

    <div class="form-group">
     <label for="productoColores">Colores disponibles</label>
     <input id="productoColores" type="text" name="colores" maxlength="255"
            value="<?= $e($valor('colores')) ?>" placeholder="#1A1A2E, #FFFFFF"/>
     <small class="pv-ayuda">Códigos hex separados por coma. Se mostrarán como muestras de color.</small>
     <div class="pv-colores-preview" id="productoColoresPreview" aria-live="polite"></div>
    </div>
   </section>

   <section class="cart-page-section pv-form-card">
    <div class="cart-page-section-head">
     <h2>Imagen y publicación</h2>
     <p>Sube una foto (JPEG, PNG, WebP o GIF, máximo 3 MB). Se guarda en la base de datos.</p>
    </div>

    <div class="pv-imagen-campo">
     <div class="pv-imagen-preview">
      <img id="productoImagenPreview" src="<?= $e($imagenActual) ?>"
           alt="Vista previa de la imagen del producto"/>
     </div>
     <div class="form-group">
      <label for="productoImagenArchivo">Imagen</label>
      <input id="productoImagenArchivo" type="file" name="imagen_archivo"
             accept="image/jpeg,image/png,image/webp,image/gif"/>
      <small class="pv-ayuda">Si no subes una, se usa la ruta de abajo o la imagen genérica.</small>
     </div>
     <div class="form-group">
      <label for="productoImagen">O una ruta o URL</label>
      <input id="productoImagen" type="text" name="imagen" maxlength="255"
             value="<?= $e($valor('imagen')) ?>" placeholder="img/Celulares/... o https://..."/>
      <small class="pv-ayuda">Solo se usa si no subes un archivo.</small>
     </div>
    </div>

    <label class="pv-check" for="productoActivo">
     <input id="productoActivo" type="checkbox" name="activo" value="1"
            <?= !empty($formulario['activo']) ? 'checked' : '' ?>/>
     <span>Publicación activa (visible en la tienda)</span>
    </label>
   </section>
  </div>

  <div class="pv-form-acciones">
   <button type="submit" class="pv-btn-guardar"><?= $esEdicion ? 'Guardar cambios' : 'Publicar producto' ?></button>
   <a class="btn-mini" href="?ruta=mis-productos">Cancelar</a>
  </div>
 </form>

 <?php if ($esEdicion): ?>
 <section class="pv-panel pv-variantes" id="variantes" aria-labelledby="pv-variantes-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-variantes-titulo">Variantes</h2>
    <p class="pv-panel-nota">
     Cada variante es un SKU con su propio precio extra y stock. La ficha limita
     la compra al menor entre el stock del producto y el de la variante.
     Los cambios de stock, incluida una variante nueva, ajustan también el total del producto.
    </p>
   </div>
   <span class="pv-panel-contador">
    <?= count($variantes ?? []) ?> variante<?= count($variantes ?? []) === 1 ? '' : 's' ?>
   </span>
  </div>

  <?php if (empty($variantes)): ?>
  <p class="pv-vacio-mini">Este producto todavía no tiene variantes. Agrega la primera abajo.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap" tabindex="0">
   <table class="pv-tabla pv-tabla-variantes">
    <thead>
     <tr>
      <th>Variante</th>
      <th>Precio extra</th>
      <th>Stock</th>
      <th>Estado</th>
      <th>Acciones</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($variantes as $v): ?>
      <?php
        $vid = (int) ($v['id'] ?? 0);
        $vActiva = (int) ($v['activo'] ?? 0) === 1;
      ?>
      <tr class="<?= $vActiva ? '' : 'pv-variante-inactiva' ?>">
       <td>
        <strong><?= $e($v['nombre_variante'] ?? '') ?></strong>
        <?php if ((string) ($v['color'] ?? '') !== ''): ?>
        <span class="pv-sub">Color: <?= $e($v['color']) ?></span>
        <?php endif; ?>
       </td>
       <td><?= (float) ($v['precio_extra'] ?? 0) > 0 ? '+ ' . $fmt((float) $v['precio_extra']) : '—' ?></td>
       <td><?= (int) ($v['stock'] ?? 0) ?></td>
       <td>
        <span class="pv-estado <?= $vActiva ? 'pv-estado-ok' : 'pv-estado-off' ?>">
         <?= $vActiva ? 'Activa' : 'Oculta' ?>
        </span>
       </td>
       <td>
        <div class="pv-variante-acciones">
         <details class="pv-variante-editar">
          <summary class="btn-mini">Editar</summary>
          <form method="post" action="?ruta=mis-productos/variantes/guardar" class="pv-variante-form">
           <?= \Security\Proteccion::campo() ?>
           <input type="hidden" name="slug" value="<?= $e($slugEditar) ?>"/>
           <input type="hidden" name="id" value="<?= $vid ?>"/>
           <input type="hidden" name="stock_original" value="<?= (int) ($v['stock'] ?? 0) ?>"/>
           <div class="pv-variante-campos">
            <div class="form-group">
             <label for="variante-nombre-<?= $vid ?>">Nombre *</label>
             <input id="variante-nombre-<?= $vid ?>" type="text" name="nombre_variante"
                    maxlength="120" required value="<?= $e($v['nombre_variante'] ?? '') ?>"/>
            </div>
            <div class="form-group">
             <label for="variante-extra-<?= $vid ?>">Precio extra (S/)</label>
             <input id="variante-extra-<?= $vid ?>" type="number" name="precio_extra"
                    min="0" step="0.01" value="<?= $e((string) (float) ($v['precio_extra'] ?? 0)) ?>"/>
            </div>
            <div class="form-group">
             <label for="variante-stock-<?= $vid ?>">Stock</label>
             <input id="variante-stock-<?= $vid ?>" type="number" name="stock"
                    min="0" step="1" value="<?= (int) ($v['stock'] ?? 0) ?>"/>
            </div>
            <div class="form-group">
             <label for="variante-color-<?= $vid ?>">Color</label>
             <input id="variante-color-<?= $vid ?>" type="text" name="color"
                    maxlength="30" value="<?= $e($v['color'] ?? '') ?>"/>
            </div>
           </div>
           <label class="pv-check" for="variante-activo-<?= $vid ?>">
            <input id="variante-activo-<?= $vid ?>" type="checkbox" name="activo" value="1"
                   <?= $vActiva ? 'checked' : '' ?>/>
            <span>Variante activa (visible en la ficha)</span>
           </label>
           <button type="submit" class="pv-btn-guardar">Guardar variante</button>
          </form>
         </details>
         <?= \Security\Proteccion::botonPost(
             'mis-productos/variantes/estado',
             ['slug' => $slugEditar, 'id' => $vid],
             $vActiva ? 'Ocultar' : 'Activar'
         ) ?>
         <?= \Security\Proteccion::botonPost(
             'mis-productos/variantes/eliminar',
             ['slug' => $slugEditar, 'id' => $vid],
             'Eliminar',
             'btn-mini btn-mini-peligro',
             '¿Eliminar esta variante? Si tiene ventas o movimientos, usa Ocultar para conservar el historial.'
         ) ?>
        </div>
       </td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>

  <h3 class="pv-variante-subtitulo">Agregar variante</h3>
  <form method="post" action="?ruta=mis-productos/variantes/guardar" class="pv-variante-form pv-variante-nueva">
   <?= \Security\Proteccion::campo() ?>
   <input type="hidden" name="slug" value="<?= $e($slugEditar) ?>"/>
   <div class="pv-variante-campos">
    <div class="form-group">
     <label for="variante-nombre-nueva">Nombre *</label>
     <input id="variante-nombre-nueva" type="text" name="nombre_variante"
            maxlength="120" required placeholder="Ej. Talla M · Azul"/>
    </div>
    <div class="form-group">
     <label for="variante-extra-nueva">Precio extra (S/)</label>
     <input id="variante-extra-nueva" type="number" name="precio_extra"
            min="0" step="0.01" value="0"/>
    </div>
    <div class="form-group">
     <label for="variante-stock-nueva">Stock</label>
     <input id="variante-stock-nueva" type="number" name="stock"
            min="0" step="1" value="0"/>
    </div>
    <div class="form-group">
     <label for="variante-color-nueva">Color</label>
     <input id="variante-color-nueva" type="text" name="color"
            maxlength="30" placeholder="Ej. Negro o #1A1A2E"/>
    </div>
   </div>
   <label class="pv-check" for="variante-activo-nueva">
    <input id="variante-activo-nueva" type="checkbox" name="activo" value="1" checked/>
    <span>Variante activa (visible en la ficha)</span>
   </label>
   <button type="submit" class="pv-btn-guardar">Agregar variante</button>
  </form>
 </section>

 <section class="pv-panel pv-variantes" id="precios-volumen" aria-labelledby="pv-volumen-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-volumen-titulo">Precios por volumen</h2>
    <p class="pv-panel-nota">
     Descuenta por cantidad comprada. El tramo reemplaza el precio base del
     producto; el extra de la variante se sigue sumando. Deja la cantidad
     máxima vacía para un tramo abierto («desde N en adelante»).
    </p>
   </div>
   <span class="pv-panel-contador">
    <?= count($tramos ?? []) ?> tramo<?= count($tramos ?? []) === 1 ? '' : 's' ?>
   </span>
  </div>

  <?php if (empty($tramos)): ?>
  <p class="pv-vacio-mini">Sin tramos: se cobra el precio publicado para cualquier cantidad.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap" tabindex="0">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Cantidad</th>
      <th>Precio unitario</th>
      <th>Ahorro vs. base</th>
      <th>Acciones</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($tramos as $t): ?>
      <?php $ahorro = (float) ($formulario['precio_pen'] ?? 0) - (float) $t['precioUnitario']; ?>
      <tr>
       <td><strong><?= $e(\Model\PrecioVolumen::rangoTexto($t)) ?></strong></td>
       <td><?= $fmt((float) $t['precioUnitario']) ?></td>
       <td><?= $ahorro > 0 ? $fmt($ahorro) : '—' ?></td>
       <td>
        <div class="pv-variante-acciones">
         <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slugEditar) ?>&amp;tramo=<?= (int) $t['id'] ?>#precios-volumen">Editar</a>
         <?= \Security\Proteccion::botonPost(
             'mis-productos/volumen/eliminar',
             ['slug' => $slugEditar, 'id' => (int) $t['id']],
             'Eliminar',
             'btn-mini btn-mini-peligro',
             '¿Eliminar este tramo de precio?'
         ) ?>
        </div>
       </td>
      </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>

  <h3 class="pv-variante-subtitulo"><?= $editandoTramo !== null ? 'Editar tramo' : 'Agregar tramo' ?></h3>
  <form method="post" action="?ruta=mis-productos/volumen/guardar" class="pv-variante-form pv-variante-nueva">
   <?= \Security\Proteccion::campo() ?>
   <input type="hidden" name="slug" value="<?= $e($slugEditar) ?>"/>
   <input type="hidden" name="id" value="<?= (int) ($editandoTramo['id'] ?? 0) ?>"/>
   <div class="pv-variante-campos">
    <div class="form-group">
     <label for="tramo-minimo">Cantidad mínima *</label>
     <input id="tramo-minimo" type="number" name="cantidad_minima" min="1" step="1" required
            value="<?= (int) ($editandoTramo['cantidadMinima'] ?? 1) ?>"/>
    </div>
    <div class="form-group">
     <label for="tramo-maximo">Cantidad máxima</label>
     <input id="tramo-maximo" type="number" name="cantidad_maxima" min="1" step="1"
            placeholder="Vacío = sin límite"
            value="<?= $editandoTramo !== null && $editandoTramo['cantidadMaxima'] !== null ? (int) $editandoTramo['cantidadMaxima'] : '' ?>"/>
    </div>
    <div class="form-group">
     <label for="tramo-precio">Precio unitario (S/) *</label>
     <input id="tramo-precio" type="number" name="precio_unitario" min="0" step="0.01" required
            value="<?= $e((string) (float) ($editandoTramo['precioUnitario'] ?? 0)) ?>"/>
    </div>
   </div>
   <button type="submit" class="pv-btn-guardar"><?= $editandoTramo !== null ? 'Guardar tramo' : 'Agregar tramo' ?></button>
   <?php if ($editandoTramo !== null): ?>
   <a class="btn-mini" href="?ruta=mis-productos/editar&amp;slug=<?= urlencode($slugEditar) ?>#precios-volumen">Cancelar</a>
   <?php endif; ?>
  </form>
 </section>
 <?php endif; ?>
</main>
