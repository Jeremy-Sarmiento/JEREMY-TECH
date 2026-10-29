<main id="contenido">
<?php
// Título de la página para lectores de pantalla: el catálogo y la búsqueda
// compartían vista y se quedaban sin <h1>.
$qTitulo = trim((string) ($filtros['q'] ?? ''));
$catTitulo = trim((string) ($filtros['categoria'] ?? ''));
$h1Catalogo = $qTitulo !== ''
    ? 'Resultados de búsqueda para «' . $qTitulo . '»'
    : ($catTitulo !== '' ? 'Catálogo de ' . $catTitulo : 'Catálogo de tecnología');
?>
<h1 class="sr-only"><?= htmlspecialchars($h1Catalogo, ENT_QUOTES, 'UTF-8') ?></h1>
<section id="inicio">
   <div class="carousel" role="region" aria-label="Promociones destacadas" aria-roledescription="carrusel">
    <div class="carousel-inner">
     <div class="carousel-item">
      <img alt="Tecnologia" src="img/Banners/banner_tecnologia.webp" width="1920" height="720"/>
      <div class="carousel-overlay">
       <h2>
        Bienvenidos a Jeremy Tech
       </h2>
       <p>
        Tu tienda de tecnologia de confianza. Los mejores dispositivos al mejor precio con garantia y soporte.
       </p>
      </div>
     </div>
     <div class="carousel-item">
      <img alt="Celulares" loading="lazy" src="img/Banners/banner_celulares.webp" width="1920" height="720"/>
      <div class="carousel-overlay">
       <h2>
        Los Mejores Dispositivos
       </h2>
       <p>
        Celulares, laptops, audifonos y mas. Encuentra la tecnologia que necesitas en un solo lugar.
       </p>
      </div>
     </div>
     <div class="carousel-item">
      <img alt="Ofertas" loading="lazy" src="img/Banners/banner_ofertas.webp" width="1920" height="720"/>
      <div class="carousel-overlay">
       <h2>
        Ofertas Especiales
       </h2>
       <p>
        Tecnologia de ultima generacion a precios imbatibles. No te pierdas nuestras promociones.
       </p>
      </div>
     </div>
    </div>
    <div class="carousel-controls">
     <button class="carousel-btn" type="button" onclick="previousSlide()" aria-label="Promoción anterior">
      <span aria-hidden="true">‹</span>
     </button>
     <button class="carousel-btn" type="button" onclick="nextSlide()" aria-label="Siguiente promoción">
      <span aria-hidden="true">›</span>
     </button>
    </div>
    <div class="carousel-indicators" role="group" aria-label="Seleccionar promoción">
     <button class="indicator active" type="button" onclick="goToSlide(0)" aria-label="Promoción 1" aria-current="true">
     </button>
     <button class="indicator" type="button" onclick="goToSlide(1)" aria-label="Promoción 2" aria-current="false">
     </button>
     <button class="indicator" type="button" onclick="goToSlide(2)" aria-label="Promoción 3" aria-current="false">
     </button>
    </div>
   </div>
  </section>
  <div class="productos">
   <?php
   $e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
   $qActiva = trim((string) ($filtros['q'] ?? ''));
   $categoriaActiva = (string) ($filtros['categoria'] ?? '');
   $marcaActiva = (string) ($filtros['marca'] ?? '');
   $precioMinActivo = $filtros['precio_min'] ?? null;
   $precioMaxActivo = $filtros['precio_max'] ?? null;
   $ordenActiva = (string) ($filtros['orden'] ?? 'relevancia');
   $vendedorActivo = $filtros['vendedor'] ?? null;
   $textoProductos = $totalProductos === 1 ? 'producto' : 'productos';
   $textoPublicaciones = $totalGrupos === 1 ? 'publicación' : 'publicaciones';

   /**
    * URL del catálogo conservando los filtros actuales. `$cambios` permite
    * quitar un filtro (valor vacío/null) o cambiar de página. Cualquier
    * cambio de filtro vuelve a la página 1 salvo que se indique otra.
    */
   $urlCatalogo = static function (array $cambios = [], bool $resetPagina = true) use ($filtros, $pagina): string {
       $params = [
           'ruta' => 'catalogo',
           'q' => trim((string) ($filtros['q'] ?? '')),
           'vendedor' => $filtros['vendedor'] ?? null,
           'categoria' => (string) ($filtros['categoria'] ?? ''),
           'marca' => (string) ($filtros['marca'] ?? ''),
           'precio_min' => $filtros['precio_min'] ?? null,
           'precio_max' => $filtros['precio_max'] ?? null,
           'orden' => (string) ($filtros['orden'] ?? 'relevancia'),
           'pagina' => $pagina,
       ];

       foreach ($cambios as $clave => $valor) {
           $params[$clave] = $valor;
       }

       if ($resetPagina && !array_key_exists('pagina', $cambios)) {
           $params['pagina'] = 1;
       }

       if ($params['q'] === '') {
           unset($params['q']);
       }
       if ($params['vendedor'] === null || (int) $params['vendedor'] <= 0) {
           unset($params['vendedor']);
       }
       if ($params['categoria'] === '') {
           unset($params['categoria']);
       }
       if ($params['marca'] === '') {
           unset($params['marca']);
       }
       if ($params['precio_min'] === null || $params['precio_min'] === '') {
           unset($params['precio_min']);
       }
       if ($params['precio_max'] === null || $params['precio_max'] === '') {
           unset($params['precio_max']);
       }
       if ($params['orden'] === '' || $params['orden'] === 'relevancia') {
           unset($params['orden']);
       }
       if ((int) ($params['pagina'] ?? 0) <= 1) {
           unset($params['pagina']);
       }

       return 'index.php?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986)
           . '#catalogo-resultados';
   };

   $precioTexto = '';
   if ($precioMinActivo !== null && $precioMaxActivo !== null) {
       $precioTexto = 'S/ ' . number_format((float) $precioMinActivo, 2) . ' – S/ '
           . number_format((float) $precioMaxActivo, 2);
   } elseif ($precioMinActivo !== null) {
       $precioTexto = 'Desde S/ ' . number_format((float) $precioMinActivo, 2);
   } elseif ($precioMaxActivo !== null) {
       $precioTexto = 'Hasta S/ ' . number_format((float) $precioMaxActivo, 2);
   }
   ?>

   <form class="catalogo-filtros" method="get" action="index.php#catalogo-resultados" aria-label="Filtros del catálogo">
    <input type="hidden" name="ruta" value="catalogo">
    <?php if ($qActiva !== ''): ?>
    <input type="hidden" name="q" value="<?= $e($qActiva) ?>">
    <?php endif; ?>
    <?php if ($vendedorActivo !== null): ?>
    <input type="hidden" name="vendedor" value="<?= (int) $vendedorActivo ?>">
    <?php endif; ?>

    <div class="filtro-grupo">
     <label for="filtro-categoria">Categoría</label>
     <select id="filtro-categoria" name="categoria">
      <option value="">Todas las categorías</option>
      <?php foreach ($categoriasDisponibles as $categoriaOpcion): ?>
      <option value="<?= $e($categoriaOpcion) ?>"<?= $categoriaOpcion === $categoriaActiva ? ' selected' : '' ?>>
       <?= $e($categoriaOpcion) ?>
      </option>
      <?php endforeach; ?>
     </select>
    </div>

    <div class="filtro-grupo">
     <label for="filtro-marca">Marca</label>
     <select id="filtro-marca" name="marca">
      <option value="">Todas las marcas</option>
      <?php foreach ($marcasDisponibles as $marcaOpcion): ?>
      <option value="<?= $e($marcaOpcion['nombre']) ?>"<?= $marcaOpcion['nombre'] === $marcaActiva ? ' selected' : '' ?>>
       <?= $e($marcaOpcion['nombre']) ?> (<?= (int) $marcaOpcion['total'] ?>)
      </option>
      <?php endforeach; ?>
     </select>
    </div>

    <div class="filtro-grupo filtro-grupo-precio">
     <span class="filtro-etiqueta">Precio (S/)</span>
     <div class="filtro-precio">
      <label class="sr-only" for="filtro-precio-min">Precio mínimo</label>
      <input id="filtro-precio-min"
             type="number"
             name="precio_min"
             min="0"
             step="0.01"
             inputmode="decimal"
             placeholder="Mín."
             value="<?= $precioMinActivo !== null ? $e((string) $precioMinActivo) : '' ?>">
      <span aria-hidden="true">—</span>
      <label class="sr-only" for="filtro-precio-max">Precio máximo</label>
      <input id="filtro-precio-max"
             type="number"
             name="precio_max"
             min="0"
             step="0.01"
             inputmode="decimal"
             placeholder="Máx."
             value="<?= $precioMaxActivo !== null ? $e((string) $precioMaxActivo) : '' ?>">
     </div>
    </div>

    <div class="filtro-grupo">
     <label for="filtro-orden">Ordenar por</label>
     <select id="filtro-orden" name="orden">
      <?php foreach ($ordenesDisponibles as $valorOrden => $textoOrden): ?>
      <option value="<?= $e($valorOrden) ?>"<?= $valorOrden === $ordenActiva ? ' selected' : '' ?>>
       <?= $e($textoOrden) ?>
      </option>
      <?php endforeach; ?>
     </select>
    </div>

    <div class="filtro-acciones">
     <button type="submit" class="btn-aplicar-filtros">Aplicar filtros</button>
     <a class="btn-limpiar-filtros"
        href="<?= $e($urlCatalogo(['categoria' => '', 'marca' => '', 'precio_min' => null, 'precio_max' => null, 'orden' => 'relevancia'])) ?>">
      Limpiar filtros
     </a>
    </div>
   </form>

   <?php
     // El vendedor se muestra por su usuario; las cuentas antiguas sin
     // usuario (columna nullable) caen al nombre.
     $vendedorFiltroNombre = '';
     if (!empty($vendedorFiltro)) {
         $vendedorFiltroNombre = trim((string) ($vendedorFiltro['usuario'] ?? ''));
         if ($vendedorFiltroNombre === '') {
             $vendedorFiltroNombre = (string) ($vendedorFiltro['name'] ?? '');
         }
     }
   ?>
   <?php if (!empty($vendedorFiltro)): ?>
   <div class="vendedor-filtro">
    <div>
     <strong>Vendido por <?= htmlspecialchars($vendedorFiltroNombre, ENT_QUOTES, 'UTF-8') ?></strong>
     <?php if ((int) ($vendedorFiltro['verificado'] ?? 0) === 1): ?>
      <span class="vendedor-filtro-badge">Verificado</span>
     <?php endif; ?>
     <p>Solo los productos publicados por esta cuenta.</p>
    </div>
    <a class="btn-reset-search" href="?ruta=catalogo">Ver todo el catálogo</a>
   </div>
   <?php endif; ?>

   <div class="catalogo-resultados" id="catalogo-resultados">
    <p class="catalogo-total">
     <strong><?= $totalProductos ?></strong> <?= $textoProductos ?>
     <?php if ($totalGrupos !== $totalProductos): ?>
     <span class="catalogo-total-nota">en <?= $totalGrupos ?> <?= $textoPublicaciones ?></span>
     <?php endif; ?>
    </p>
    <?php if ($totalPaginas > 1): ?>
    <p class="catalogo-pagina-actual">
     Mostrando <?= $desdeGrupo ?>–<?= $hastaGrupo ?> de <?= $totalGrupos ?> <?= $textoPublicaciones ?>
     · Página <?= $pagina ?> de <?= $totalPaginas ?>
    </p>
    <?php endif; ?>
   </div>

   <?php if ($hayFiltros): ?>
   <div class="filtros-activos" aria-label="Filtros activos">
    <span class="filtros-activos-label">Filtros y orden:</span>
    <?php if ($qActiva !== ''): ?>
    <a class="filtro-chip" href="<?= $e($urlCatalogo(['q' => ''])) ?>" title="Quitar la búsqueda">
     “<?= $e($qActiva) ?>” <span aria-hidden="true">×</span>
    </a>
    <?php endif; ?>
    <?php if ($vendedorActivo !== null && !empty($vendedorFiltro)): ?>
    <a class="filtro-chip" href="<?= $e($urlCatalogo(['vendedor' => null])) ?>" title="Quitar el filtro de vendedor">
     Vendedor: <?= $e($vendedorFiltroNombre) ?> <span aria-hidden="true">×</span>
    </a>
    <?php endif; ?>
    <?php if ($categoriaActiva !== ''): ?>
    <a class="filtro-chip" href="<?= $e($urlCatalogo(['categoria' => ''])) ?>" title="Quitar el filtro de categoría">
     <?= $e($categoriaActiva) ?> <span aria-hidden="true">×</span>
    </a>
    <?php endif; ?>
    <?php if ($marcaActiva !== ''): ?>
    <a class="filtro-chip" href="<?= $e($urlCatalogo(['marca' => ''])) ?>" title="Quitar el filtro de marca">
     <?= $e($marcaActiva) ?> <span aria-hidden="true">×</span>
    </a>
    <?php endif; ?>
    <?php if ($precioTexto !== ''): ?>
    <a class="filtro-chip" href="<?= $e($urlCatalogo(['precio_min' => null, 'precio_max' => null])) ?>" title="Quitar el filtro de precio">
     <?= $e($precioTexto) ?> <span aria-hidden="true">×</span>
    </a>
    <?php endif; ?>
    <?php if ($ordenActiva !== 'relevancia'): ?>
    <a class="filtro-chip" href="<?= $e($urlCatalogo(['orden' => 'relevancia'])) ?>" title="Volver al orden por relevancia">
     <?= $e($ordenesDisponibles[$ordenActiva] ?? $ordenActiva) ?> <span aria-hidden="true">×</span>
    </a>
    <?php endif; ?>
   </div>
   <?php endif; ?>

   <?php if ($totalProductos === 0): ?>
   <div class="no-search-results">
    <div class="no-results-icon"><svg class="inline-icon" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-search"/></svg></div>
    <h3>No se encontraron productos</h3>
    <p>
     <?php if ($qActiva !== ''): ?>
      No encontramos resultados para "<strong><?= $e($qActiva) ?></strong>"<?= $hayFiltrosSinBusqueda ? ' con los filtros seleccionados' : '' ?>. Intenta con otra palabra clave.
     <?php elseif ($hayFiltrosSinBusqueda): ?>
      No hay productos que coincidan con los filtros seleccionados.
     <?php else: ?>
      No hay productos disponibles en este momento.
     <?php endif; ?>
    </p>
    <a class="btn-reset-search" href="<?= $e($urlCatalogo(['q' => '', 'vendedor' => null, 'categoria' => '', 'marca' => '', 'precio_min' => null, 'precio_max' => null, 'orden' => 'relevancia'])) ?>">
     Limpiar búsqueda y filtros
    </a>
   </div>
   <?php elseif ($ordenActiva !== 'relevancia'): ?>
   <?php
     // Con un orden explícito (precio o nombre) el catálogo se muestra en una
     // sola grilla: si siguiéramos separando por categoría, el orden elegido
     // solo se aplicaría dentro de cada sección.
     $gruposPlano = [];
     $vistosPlano = [];
     foreach ($productos as $productoPlano) {
         $clavePlano = ($productoPlano['categoria'] ?? '') . "\0" . ($productoPlano['nombre'] ?? '');
         if (isset($vistosPlano[$clavePlano])) {
             continue;
         }
         $vistosPlano[$clavePlano] = true;
         $gruposPlano[] = array_values(array_filter(
             $productos,
             static fn (array $item): bool => ($item['categoria'] ?? '') === ($productoPlano['categoria'] ?? '')
                 && ($item['nombre'] ?? '') === ($productoPlano['nombre'] ?? '')
         ));
     }
   ?>
   <section id="resultados">
    <h2>Resultados</h2>
    <div class="grid" id="grid-resultados">
     <?php foreach ($gruposPlano as $grupo): ?>
      <?php require BASE_PATH . '/views/partials/card.php'; ?>
     <?php endforeach; ?>
    </div>
   </section>
   <?php else: ?>
   <?php foreach ($grids as $categoria => $gridId): ?>
   <?php
     $gruposCategoria = $gruposPorCategoria[$categoria] ?? [];
     if ($gruposCategoria === []) {
         continue;
     }
     // Cuatro por fila: recortar evita la tarjeta huérfana cuando hay 5.
     $visiblesCategoria = array_slice($gruposCategoria, 0, 4);
   ?>
   <section id="<?= strtolower($categoria) ?>">
    <h2><?= $categoria ?></h2>
    <div class="grid" id="<?= $gridId ?>">
     <?php foreach ($visiblesCategoria as $grupo): ?>
      <?php require BASE_PATH . '/views/partials/card.php'; ?>
     <?php endforeach; ?>
    </div>
    <?php if (count($gruposCategoria) > count($visiblesCategoria)): ?>
    <a class="catalogo-ver-todos" href="<?= $e($urlCatalogo(['categoria' => $categoria])) ?>">
     Ver los <?= count($gruposCategoria) ?> productos de <?= $e($categoria) ?>
    </a>
    <?php endif; ?>
   </section>
   <?php endforeach; ?>
   <?php endif; ?>

   <?php if ($totalPaginas > 1): ?>
   <nav class="catalogo-paginacion" aria-label="Paginación del catálogo">
    <?php if ($pagina > 1): ?>
    <a class="paginacion-btn paginacion-anterior"
       href="<?= $e($urlCatalogo(['pagina' => $pagina - 1], false)) ?>"
       rel="prev">‹ Anterior</a>
    <?php else: ?>
    <span class="paginacion-btn paginacion-anterior deshabilitada" aria-disabled="true">‹ Anterior</span>
    <?php endif; ?>

    <div class="paginacion-numeros">
     <?php
       $inicioPaginas = max(1, $pagina - 2);
       $finPaginas = min($totalPaginas, $pagina + 2);

       if ($inicioPaginas > 1) {
           echo '<a class="paginacion-num" href="' . $e($urlCatalogo(['pagina' => 1], false)) . '">1</a>';
           if ($inicioPaginas > 2) {
               echo '<span class="paginacion-puntos" aria-hidden="true">…</span>';
           }
       }

       for ($numeroPagina = $inicioPaginas; $numeroPagina <= $finPaginas; $numeroPagina++) {
           if ($numeroPagina === $pagina) {
               echo '<span class="paginacion-num actual" aria-current="page">' . $numeroPagina . '</span>';
           } else {
               echo '<a class="paginacion-num" href="'
                   . $e($urlCatalogo(['pagina' => $numeroPagina], false)) . '">'
                   . $numeroPagina . '</a>';
           }
       }

       if ($finPaginas < $totalPaginas) {
           if ($finPaginas < $totalPaginas - 1) {
               echo '<span class="paginacion-puntos" aria-hidden="true">…</span>';
           }
           echo '<a class="paginacion-num" href="'
               . $e($urlCatalogo(['pagina' => $totalPaginas], false)) . '">'
               . $totalPaginas . '</a>';
       }
     ?>
    </div>

    <?php if ($pagina < $totalPaginas): ?>
    <a class="paginacion-btn paginacion-siguiente"
       href="<?= $e($urlCatalogo(['pagina' => $pagina + 1], false)) ?>"
       rel="next">Siguiente ›</a>
    <?php else: ?>
    <span class="paginacion-btn paginacion-siguiente deshabilitada" aria-disabled="true">Siguiente ›</span>
    <?php endif; ?>
   </nav>
   <?php endif; ?>

  </div>
</main>
