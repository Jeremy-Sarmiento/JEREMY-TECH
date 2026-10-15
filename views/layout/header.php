<!DOCTYPE html>
<html lang="es">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>
   Jeremy Tech - Tienda de Tecnologia
  </title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;family=Share+Tech+Mono&amp;display=swap" rel="stylesheet"/>
<link href="css/styles.css" rel="stylesheet"/>
   <link href="css/carrito.css" rel="stylesheet"/>
   <link href="css/auth.css" rel="stylesheet"/>
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
  <header>
   <div class="logo-text">
    <img class="logo-svg" src="img/logo.svg" alt="JEREMY TECH"/>
   </div>
   <p>
    La Paz Soldan, frente al salon de eventos "los portales" | jeresarmientotarojo@gmail.com | 997833866
   </p>
  </header>
  <nav>
   <button class="nav-hamburger" id="hamburgerBtn" onclick="toggleMenu()" aria-label="Abrir menú de categorías">
    <span></span>
    <span></span>
    <span></span>
   </button>
   <div class="nav-main-links" id="navMainLinks">
    <?php $rutaCatalogo = in_array($rutaVista ?? '', ['carrito', 'login', 'producto', 'direcciones'], true) ? '?ruta=catalogo' : ''; ?>
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
    <div class="nav-search-box">
     <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2">
      <use href="img/icons.svg#icon-search"/>
     </svg>
     <input type="text" id="busquedaInput" placeholder="Buscar productos, specs..." autocomplete="off"/>
     <button id="btnLimpiarBusqueda" class="btn-clear-search" style="display:none;" title="Limpiar búsqueda" onclick="limpiarBusqueda()">&times;</button>
    </div>
    <div class="nav-moneda">
     <button class="nav-moneda-btn" id="monedaBtn">
      💱 PEN
     </button>
     <div class="nav-moneda-dropdown" id="monedaDropdown">
      <div class="moneda-option active" data-moneda="PEN">🇵🇪 PEN - Sol peruano</div>
      <div class="moneda-option" data-moneda="USD">🇺🇸 USD - Dolar</div>
      <div class="moneda-option" data-moneda="EUR">🇪🇺 EUR - Euro</div>
      <div class="moneda-option" data-moneda="GBP">🇬🇧 GBP - Libra</div>
      <div class="moneda-option" data-moneda="BRL">🇧🇷 BRL - Real</div>
      <div class="moneda-option" data-moneda="ARS">🇦🇷 ARS - Peso argentino</div>
      <div class="moneda-option" data-moneda="CLP">🇨🇱 CLP - Peso chileno</div>
      <div class="moneda-option" data-moneda="COP">🇨🇴 COP - Peso colombiano</div>
     </div>
    </div>
    <?php $usuarioCuenta = \Model\Cuenta::usuarioActual(); ?>
    <?php if ($usuarioCuenta): ?>
    <div class="nav-account" id="navAccountBox">
     <button class="nav-account-btn" title="Mi cuenta">
      <span>👤</span>
      <span><?= htmlspecialchars($usuarioCuenta['nombre'] ?? 'Mi cuenta', ENT_QUOTES) ?></span>
     </button>
     <div class="nav-account-dropdown">
      <div class="nav-account-name">👤 <?= htmlspecialchars(($usuarioCuenta['nombre'] ?? '') . ' ' . ($usuarioCuenta['apellidos'] ?? ''), ENT_QUOTES) ?></div>
      <a href="?ruta=carrito#historial">📦 Mis Pedidos</a>
      <a href="?ruta=direcciones">📍 Mis Direcciones</a>
      <a href="?ruta=catalogo">🛍️ Explorar Catálogo</a>
      <form action="?ruta=login/cerrar" method="post">
       <button type="submit">🚪 Cerrar sesión</button>
      </form>
     </div>
    </div>
    <?php else: ?>
    <a class="nav-login-btn" href="?ruta=login" title="Iniciar sesión">
     <span>👤</span><span>Iniciar sesión</span>
    </a>
    <?php endif; ?>
    <?php if (\Model\Administrador::estaAutenticado()): ?>
    <a class="nav-admin-btn" href="?ruta=admin" title="Panel de administración">⚙️ Panel</a>
    <?php endif; ?>
    <button class="nav-orders-btn" id="ordersNavBtn" onclick="abrirHistorialPedidos()" title="Ver historial de pedidos">
     <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
      <use href="img/icons.svg#icon-pedidos"/>
     </svg>
     <span>Mis Pedidos</span>
    </button>
    <button class="nav-cart-btn" id="cartNavBtn" onclick="abrirCarrito()" title="Ver carrito de compras">
     <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
      <use href="img/icons.svg#icon-cart"/>
     </svg>
     <span>Carrito</span>
     <span class="cart-badge" id="cartBadgeNav">0</span>
    </button>
   </div>
  </nav>
  <button class="btn-inicio-flotante" id="btnInicio" onclick="irAlInicio()">
  </button>
