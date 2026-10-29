<!DOCTYPE html>
<html lang="es">
 <head>
  <meta charset="utf-8"/>
  <meta name="csrf-token" content="<?= htmlspecialchars(\Security\Proteccion::token(), ENT_QUOTES, 'UTF-8') ?>"/>
  <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
  <meta content="#0A0F1A" name="theme-color"/>
  <title><?= htmlspecialchars($tituloPagina ?? 'Jeremy Tech - Tienda de Tecnologia', ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;family=Share+Tech+Mono&amp;display=swap" rel="stylesheet"/>
<link href="css/styles.css?v=<?= (int) @filemtime(BASE_PATH . '/public/css/styles.css') ?>" rel="stylesheet"/>
   <link href="css/carrito.css?v=<?= (int) @filemtime(BASE_PATH . '/public/css/carrito.css') ?>" rel="stylesheet"/>
   <link href="css/auth.css?v=<?= (int) @filemtime(BASE_PATH . '/public/css/auth.css') ?>" rel="stylesheet"/>
   <?php if (($rutaVista ?? '') === 'login'): ?>
    <!-- Mapa opcional del registro (Leaflet + OpenStreetMap). -->
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" rel="stylesheet"/>
   <?php endif; ?>
   <link href="css/cuenta.css?v=<?= (int) @filemtime(BASE_PATH . '/public/css/cuenta.css') ?>" rel="stylesheet"/>
   <link href="css/chat.css?v=<?= (int) @filemtime(BASE_PATH . '/public/css/chat.css') ?>" rel="stylesheet"/>
   <?php if (!empty($paginaAdmin)): ?>
    <link href="css/admin.css" rel="stylesheet"/>
   <?php endif; ?>
  <script>
   const JT_SESSION = {
    rutaVista: '<?= htmlspecialchars($rutaVista ?? 'catalogo', ENT_QUOTES) ?>',
    totalItems: <?= (int) \Model\Carrito::totalItems() ?>
   };
   <?php if (isset($catalogoJson)): ?>
   const productos = <?= $catalogoJson ?>;
   <?php endif; ?>
  </script>
 </head>
 <body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>
  <header>
   <div class="logo-text">
    <img class="logo-svg" src="img/logo.svg" alt="JEREMY TECH"/>
   </div>
   <p>
    La Paz Soldan, frente al salon de eventos "los portales" |
    <a href="mailto:jeresarmientotarojo@gmail.com">jeresarmientotarojo@gmail.com</a> |
    <a href="tel:+51997833866">997833866</a>
   </p>
  </header>
  <nav aria-label="Navegación principal">
   <button class="nav-hamburger"
           id="hamburgerBtn"
           type="button"
           onclick="toggleMenu()"
           aria-label="Abrir menú de categorías"
           aria-controls="navMainLinks"
           aria-expanded="false">
    <span aria-hidden="true"></span>
    <span aria-hidden="true"></span>
    <span aria-hidden="true"></span>
   </button>
   <div class="nav-main-links" id="navMainLinks">
    <?php $rutaCatalogo = in_array($rutaVista ?? '', ['carrito', 'login', 'producto', 'direcciones', 'pagina', 'perfil', 'favoritos', 'notificaciones', 'mis-pedidos', 'mis-productos'], true) ? '?ruta=catalogo' : ''; ?>
    <a href="<?= $rutaCatalogo ?>#inicio">
     INICIO
    </a>
    <a href="<?= $rutaCatalogo ?>#celulares">
     CELULARES
    </a>
    <a href="<?= $rutaCatalogo ?>#laptops">
     LAPTOPS
    </a>
    <a href="<?= $rutaCatalogo ?>#audifonos">
     AUDIFONOS
    </a>
    <a href="<?= $rutaCatalogo ?>#accesorios">
     ACCESORIOS
    </a>
    <a href="<?= $rutaCatalogo ?>#smartwatches">
     SMARTWATCHES
    </a>
    <a href="<?= $rutaCatalogo ?>#tablets">
     TABLETS
    </a>
    <a href="<?= $rutaCatalogo ?>#gaming">
     GAMING
    </a>
   </div>
   <div class="nav-actions">
    <?php
     $busquedaActual = isset($_GET['q']) && is_scalar($_GET['q']) ? (string) $_GET['q'] : '';
     $vendedorBusqueda = !empty($vendedorFiltro) && isset($idVendedor) ? (int) $idVendedor : 0;
    ?>
    <form class="nav-search-box" action="index.php" method="get" role="search" data-suggest-url="?ruta=catalogo/sugerencias">
     <input type="hidden" name="ruta" value="catalogo"/>
     <?php if ($vendedorBusqueda > 0): ?>
     <input type="hidden" name="vendedor" value="<?= $vendedorBusqueda ?>"/>
     <?php endif; ?>
     <?php
      // (Bloque 5) Al enviar una búsqueda desde la navegación se conservan los
      // filtros activos del catálogo (categoría, marca, rango de precio y
      // orden). El controlador ignora los valores inválidos, así que basta con
      // reenviar lo que haya en la URL actual.
      $navCategoria = isset($_GET['categoria']) && is_scalar($_GET['categoria']) ? trim((string) $_GET['categoria']) : '';
      $navMarca = isset($_GET['marca']) && is_scalar($_GET['marca']) ? trim((string) $_GET['marca']) : '';
      $navPrecioMin = isset($_GET['precio_min']) && is_scalar($_GET['precio_min']) ? trim((string) $_GET['precio_min']) : '';
      $navPrecioMax = isset($_GET['precio_max']) && is_scalar($_GET['precio_max']) ? trim((string) $_GET['precio_max']) : '';
      $navOrden = isset($_GET['orden']) && is_scalar($_GET['orden']) ? trim((string) $_GET['orden']) : '';
     ?>
     <input type="hidden" name="categoria" value="<?= htmlspecialchars($navCategoria, ENT_QUOTES, 'UTF-8') ?>"/>
     <input type="hidden" name="marca" value="<?= htmlspecialchars($navMarca, ENT_QUOTES, 'UTF-8') ?>"/>
     <input type="hidden" name="precio_min" value="<?= htmlspecialchars($navPrecioMin, ENT_QUOTES, 'UTF-8') ?>"/>
     <input type="hidden" name="precio_max" value="<?= htmlspecialchars($navPrecioMax, ENT_QUOTES, 'UTF-8') ?>"/>
     <input type="hidden" name="orden" value="<?= htmlspecialchars($navOrden, ENT_QUOTES, 'UTF-8') ?>"/>
     <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <use href="img/icons.svg#icon-search"/>
     </svg>
     <input type="search" id="busquedaInput" name="q" value="<?= htmlspecialchars($busquedaActual, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre, modelo, SKU o categoría" autocomplete="off" enterkeyhint="search" aria-label="Buscar productos"/>
     <button type="button" id="btnLimpiarBusqueda" class="btn-clear-search" style="display:none;" title="Limpiar búsqueda" aria-label="Limpiar búsqueda" onclick="limpiarBusqueda()">&times;</button>
     <div id="searchSuggest" class="search-suggest" aria-live="polite" hidden></div>
    </form>
    <div class="nav-moneda">
     <button class="nav-moneda-btn"
             id="monedaBtn"
             type="button"
             aria-label="Cambiar moneda"
             aria-haspopup="true"
             aria-expanded="false"
             aria-controls="monedaDropdown">
      PEN
     </button>
     <div class="nav-moneda-dropdown" id="monedaDropdown">
      <button type="button" class="moneda-option active" data-moneda="PEN" aria-pressed="true">PEN - Sol peruano</button>
      <button type="button" class="moneda-option" data-moneda="USD" aria-pressed="false">USD - Dolar</button>
      <button type="button" class="moneda-option" data-moneda="EUR" aria-pressed="false">EUR - Euro</button>
      <button type="button" class="moneda-option" data-moneda="GBP" aria-pressed="false">GBP - Libra</button>
      <button type="button" class="moneda-option" data-moneda="BRL" aria-pressed="false">BRL - Real</button>
      <button type="button" class="moneda-option" data-moneda="ARS" aria-pressed="false">ARS - Peso argentino</button>
      <button type="button" class="moneda-option" data-moneda="CLP" aria-pressed="false">CLP - Peso chileno</button>
      <button type="button" class="moneda-option" data-moneda="COP" aria-pressed="false">COP - Peso colombiano</button>
     </div>
    </div>
    <?php $usuarioCuenta = \Model\Cuenta::usuarioActual(); ?>
    <?php $puedePublicarCuenta = $usuarioCuenta !== null && \Model\Cuenta::puedePublicar($usuarioCuenta); ?>
    <?php if ($usuarioCuenta): ?>
    <?php
      // El nombre público es el usuario; las cuentas antiguas sin usuario
      // (columna nullable) siguen mostrando su nombre.
      $nombreCuenta = trim((string) ($usuarioCuenta['usuario'] ?? ''));
      if ($nombreCuenta === '') {
          $nombreCuenta = trim((string) ($usuarioCuenta['nombre'] ?? '')) ?: 'Mi cuenta';
      }
    ?>
    <div class="nav-account" id="navAccountBox">
     <button class="nav-account-btn"
             type="button"
             title="Mi cuenta"
             aria-label="Mi cuenta"
             aria-haspopup="true"
             aria-expanded="false"
             aria-controls="navAccountDropdown">
      <svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-user"/></svg>
      <span class="nav-account-label"><?= htmlspecialchars($nombreCuenta, ENT_QUOTES) ?></span>
     </button>
     <div class="nav-account-dropdown" id="navAccountDropdown">
      <div class="nav-account-name"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-user"/></svg> <?= htmlspecialchars($nombreCuenta, ENT_QUOTES) ?></div>
      <?php if ($puedePublicarCuenta): ?>
      <div class="nav-account-rol"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-briefcase"/></svg> Vendedor<?= (int) ($usuarioCuenta['verificado'] ?? 0) === 1 ? ' verificado' : '' ?></div>
      <?php endif; ?>
      <a href="?ruta=perfil"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-user"/></svg>Mi Perfil</a>
      <a href="?ruta=mis-pedidos"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg>Mis Pedidos</a>
      <a href="?ruta=favoritos"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-heart"/></svg>Mis Favoritos</a>
      <?php if ($puedePublicarCuenta): ?>
      <a href="?ruta=mis-productos"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-briefcase"/></svg>Mis Productos</a>
      <?php endif; ?>
      <a href="?ruta=direcciones"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pin"/></svg>Mis Direcciones</a>
      <a href="?ruta=notificaciones"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bell"/></svg>Notificaciones</a>
      <a href="?ruta=catalogo"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-bag"/></svg>Explorar Catálogo</a>
      <form action="?ruta=login/cerrar" method="post">
          <?= \Security\Proteccion::campo() ?>
       <button type="submit"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-logout"/></svg>Cerrar sesión</button>
      </form>
     </div>
    </div>
    <?php else: ?>
    <a class="nav-login-btn" href="?ruta=login" title="Iniciar sesión" aria-label="Iniciar sesión">
     <svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-user"/></svg><span class="nav-login-label">Iniciar sesión</span>
    </a>
    <?php endif; ?>
    <?php if (\Model\Administrador::estaAutenticado()): ?>
    <a class="nav-admin-btn" href="?ruta=admin" title="Panel de administración" aria-label="Panel de administración"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-settings"/></svg><span class="nav-admin-label">Panel</span></a>
    <?php endif; ?>
    <a class="nav-orders-btn" id="ordersNavBtn" href="?ruta=mis-pedidos" title="Ver mis pedidos" aria-label="Ver mis pedidos">
     <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <use href="img/icons.svg#icon-pedidos"/>
     </svg>
     <span class="nav-orders-label">Mis Pedidos</span>
    </a>
    <button class="nav-cart-btn" id="cartNavBtn" type="button" onclick="abrirCarrito()" title="Ver carrito de compras" aria-label="Ver carrito de compras">
     <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <use href="img/icons.svg#icon-cart"/>
     </svg>
     <span class="nav-cart-label">Carrito</span>
     <span class="cart-badge" id="cartBadgeNav">0</span>
    </button>
   </div>
  </nav>
  <button class="btn-inicio-flotante" id="btnInicio" type="button" onclick="irAlInicio()" aria-label="Volver arriba">
  </button>
