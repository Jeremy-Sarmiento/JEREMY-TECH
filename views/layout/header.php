<!DOCTYPE html>
<html lang="es">
 <head>
  <meta charset="utf-8"/>
  <meta name="csrf-token" content="<?= htmlspecialchars(\Security\Proteccion::token(), ENT_QUOTES, 'UTF-8') ?>"/>
  <meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
  <meta content="#070A0E" name="theme-color"/>
  <link href="favicon.svg" rel="icon" type="image/svg+xml"/>
  <link href="favicon.ico" rel="icon" sizes="any"/>
  <link href="apple-touch-icon.png" rel="apple-touch-icon"/>
  <title><?= htmlspecialchars($tituloPagina ?? 'Jeremy Tech - Tienda de Tecnologia', ENT_QUOTES, 'UTF-8') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
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
   <!-- Capa de rediseño: se carga al final para tomar precedencia. -->
   <link href="css/rediseno.css?v=<?= (int) @filemtime(BASE_PATH . '/public/css/rediseno.css') ?>" rel="stylesheet"/>
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
    <a class="logo-link" href="index.php" aria-label="JEREMY TECH, ir al inicio">
     <img class="logo-svg" src="img/logo.svg" alt="JEREMY TECH"/>
    </a>
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
    <?php
     // Filtros del catálogo en la cabecera (sustituyen a los enlaces de categorías).
     // En la vista del catálogo el controlador ya dejó listas las opciones y el
     // filtro aplicado; en el resto de rutas se usan los valores por defecto.
     $navFiltro = (isset($filtros) && is_array($filtros)) ? $filtros : $_GET;
     $navCategoria = (string) ($navFiltro['categoria'] ?? '');
     $navMarca = (string) ($navFiltro['marca'] ?? '');
     $navOrden = (string) ($navFiltro['orden'] ?? 'relevancia');
     $navBusqueda = trim((string) ($navFiltro['q'] ?? ''));
     $navVendedor = $navFiltro['vendedor'] ?? null;

     $navCategorias = $categoriasDisponibles ?? array_keys(\Model\Producto::GRIDS);
     $navMarcas = $marcasDisponibles ?? \Model\Producto::marcasDisponibles();
     $navOrdenes = $ordenesDisponibles ?? \Controller\ProductoController::ORDENES;

     $navEsc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    ?>
     <form class="filtros-nav" action="index.php" method="get" role="search" aria-label="Buscar y filtrar el catálogo" data-suggest-url="?ruta=catalogo/sugerencias">
      <input type="hidden" name="ruta" value="catalogo">
      <?php if (!empty($navVendedor)): ?>
      <input type="hidden" name="vendedor" value="<?= (int) $navVendedor ?>">
      <?php endif; ?>

      <div class="nav-search-field">
       <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <use href="img/icons.svg#icon-search"/>
       </svg>
       <input type="search" id="busquedaInput" name="q" value="<?= $navEsc($navBusqueda) ?>" placeholder="Buscar productos" autocomplete="off" enterkeyhint="search" aria-label="Buscar productos"/>
       <button type="button" id="btnLimpiarBusqueda" class="btn-clear-search" style="display:none;" title="Limpiar búsqueda" aria-label="Limpiar búsqueda" onclick="limpiarBusqueda()">&times;</button>
      </div>

      <div class="filtro-grupo">
       <label class="sr-only" for="filtro-categoria">Categoría</label>
       <select id="filtro-categoria" name="categoria">
        <option value="">Categoría</option>
        <?php foreach ($navCategorias as $categoriaOpcion): ?>
        <option value="<?= $navEsc($categoriaOpcion) ?>"<?= $categoriaOpcion === $navCategoria ? ' selected' : '' ?>>
         <?= $navEsc($categoriaOpcion) ?>
        </option>
        <?php endforeach; ?>
       </select>
      </div>

      <div class="filtro-grupo">
       <label class="sr-only" for="filtro-marca">Marca</label>
       <select id="filtro-marca" name="marca">
        <option value="">Marca</option>
        <?php foreach ($navMarcas as $marcaOpcion): ?>
        <option value="<?= $navEsc($marcaOpcion['nombre']) ?>"<?= $marcaOpcion['nombre'] === $navMarca ? ' selected' : '' ?>>
         <?= $navEsc($marcaOpcion['nombre']) ?>
        </option>
        <?php endforeach; ?>
       </select>
      </div>

      <div class="filtro-grupo filtro-grupo-orden">
       <label class="sr-only" for="filtro-orden">Ordenar por</label>
       <select id="filtro-orden" name="orden">
        <?php foreach ($navOrdenes as $valorOrden => $textoOrden): ?>
        <option value="<?= $navEsc($valorOrden) ?>"<?= $valorOrden === $navOrden ? ' selected' : '' ?>>
         <?= $navEsc($textoOrden) ?>
        </option>
        <?php endforeach; ?>
       </select>
      </div>

      <button type="submit" class="btn-aplicar-filtros">Buscar</button>

      <div id="searchSuggest" class="search-suggest" aria-live="polite" hidden></div>
     </form>
   </div>
   <div class="nav-actions">
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
      <?php if (\Model\Administrador::estaAutenticado()): ?>
      <a href="?ruta=admin" class="nav-account-panel"><svg class="nav-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-settings"/></svg>Panel de administración</a>
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
