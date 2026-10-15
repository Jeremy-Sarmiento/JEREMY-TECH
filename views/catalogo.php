<section id="inicio">
   <div class="carousel">
    <div class="carousel-inner">
     <div class="carousel-item">
      <img alt="Tecnologia" src="img/Banners/banner_tecnologia.jpg"/>
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
      <img alt="Celulares" loading="lazy" src="img/Banners/banner_celulares.jpg"/>
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
      <img alt="Ofertas" loading="lazy" src="img/Banners/banner_ofertas.jpg"/>
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
     <button class="carousel-btn" onclick="previousSlide()">
      ‹
     </button>
     <button class="carousel-btn" onclick="nextSlide()">
      ›
     </button>
    </div>
    <div class="carousel-indicators">
     <span class="indicator active" onclick="goToSlide(0)">
     </span>
     <span class="indicator" onclick="goToSlide(1)">
     </span>
     <span class="indicator" onclick="goToSlide(2)">
     </span>
    </div>
   </div>
  </section>
  <div class="productos">
   <?php if (!empty($vendedorFiltro)): ?>
   <div class="vendedor-filtro">
    <div>
     <strong>Vendido por <?= htmlspecialchars((string) $vendedorFiltro['name'], ENT_QUOTES, 'UTF-8') ?></strong>
     <?php if ((int) ($vendedorFiltro['verificado'] ?? 0) === 1): ?>
      <span class="vendedor-filtro-badge">Verificado</span>
     <?php endif; ?>
     <p>Solo los productos publicados por esta cuenta.</p>
    </div>
    <a class="btn-reset-search" href="?ruta=catalogo">Ver todo el catálogo</a>
   </div>
   <?php endif; ?>

   <!-- Mensaje de búsqueda sin resultados -->
   <div id="noSearchResults" class="no-search-results" style="display: none;">
    <div class="no-results-icon">🔍</div>
    <h3>No se encontraron productos</h3>
    <p>No encontramos resultados para "<span id="searchQueryText"></span>". Intenta con otra palabra clave o marca.</p>
    <button class="btn-reset-search" onclick="limpiarBusqueda()">Ver todos los productos</button>
   </div>

   <?php foreach ($grids as $categoria => $gridId): ?>
   <?php if (!empty($vendedorFiltro) && empty($gruposPorCategoria[$categoria])) continue; ?>
   <section id="<?= strtolower($categoria) ?>">
    <h2><?= $categoria ?></h2>
    <div class="grid" id="<?= $gridId ?>">
     <?php foreach (($gruposPorCategoria[$categoria] ?? []) as $grupo): ?>
      <?php require BASE_PATH . '/views/partials/card.php'; ?>
     <?php endforeach; ?>
    </div>
   </section>
   <?php endforeach; ?>

  </div>