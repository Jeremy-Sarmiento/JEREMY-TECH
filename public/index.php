<?php
declare(strict_types=1);

/**
 * public/index.php — EL ENRUTADOR (Jeremy Tech MVC).
 * Único archivo accesible públicamente. Despacha la ruta a los controladores.
 *
 * Uso de desarrollo: php -S localhost:8000 -t public
 * Rutas:
 *   ?ruta=catalogo              -> catálogo de productos (por defecto)
 *   ?ruta=producto&slug=...     -> ficha de un producto (Etapa 7)
 *   ?ruta=carrito               -> página del carrito / checkout / historial
 *   POST ?ruta=carrito/agregar  -> agregar producto
 *   POST ?ruta=carrito/eliminar -> eliminar producto
 *   POST ?ruta=carrito/cambiar-cantidad -> actualizar cantidad
 *   POST ?ruta=carrito/aplicar-cupon    -> aplicar cupón
 *   POST ?ruta=carrito/procesar         -> registrar pedido completo
 *   ?ruta=login                         -> iniciar sesión / crear cuenta
 *   POST ?ruta=login/iniciar            -> autenticar cliente
 *   POST ?ruta=login/registrar          -> crear cuenta (auto-login)
 *   POST ?ruta=login/cerrar             -> cerrar sesión
 *   ?ruta=login/recuperar-pregunta      -> recuperar con pregunta de seguridad (GET/POST, 2 pasos)
 *   ?ruta=direcciones                   -> mis direcciones de envío (Etapa 5)
 *   POST ?ruta=direcciones/guardar      -> crear / editar dirección
 *   GET  ?ruta=direcciones/eliminar     -> eliminar dirección
 *   GET  ?ruta=direcciones/predeterminada -> marcar como predeterminada
 */

require __DIR__ . '/../config/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$ruta = $_GET['ruta'] ?? 'catalogo';

// Panel de administración: TODO el bloque admin (y la raíz admin) se despacha
// al AdminController, que atiende internamente cada sub-ruta (productos,
// categorias, marcas, garantias, cupones, tarifas envio, configuracion,
// pedidos, usuarios, auditoria). La sección antigua "clientes" redirige a
// "usuarios" (Etapa 4: identidad unificada en `users`).
if ($ruta === 'admin' || str_starts_with($ruta, 'admin/')) {
    $subRuta = $ruta === 'admin' ? '' : substr($ruta, 6);
    (new Controller\AdminController())->atender($subRuta);
    exit;
}

switch ($ruta) {
    case 'carrito':
        (new Controller\CarritoController())->ver();
        break;

    case 'carrito/agregar':
        (new Controller\CarritoController())->agregar();
        break;

    case 'carrito/eliminar':
        (new Controller\CarritoController())->eliminar();
        break;

    case 'carrito/cambiar-cantidad':
        (new Controller\CarritoController())->cambiarCantidad();
        break;

    case 'carrito/aplicar-cupon':
        (new Controller\CarritoController())->aplicarCupon();
        break;

    case 'carrito/procesar':
        (new Controller\CarritoController())->procesar();
        break;

    case 'catalogo':
        (new Controller\ProductoController())->catalogo();
        break;

    case 'producto':
        (new Controller\ProductoController())->detalle();
        break;

    case 'login':
        (new Controller\CuentaController())->ver();
        break;

    case 'login/registrar':
        (new Controller\CuentaController())->registrar();
        break;

    case 'login/iniciar':
        (new Controller\CuentaController())->iniciarSesion();
        break;

    case 'login/cerrar':
        (new Controller\CuentaController())->cerrar();
        break;

    case 'login/recuperar-pregunta':
        (new Controller\CuentaController())->recuperarPregunta();
        break;

    case 'direcciones':
        (new Controller\DireccionController())->ver();
        break;

    case 'direcciones/guardar':
        (new Controller\DireccionController())->guardar();
        break;

    case 'direcciones/eliminar':
        (new Controller\DireccionController())->eliminar();
        break;

    case 'direcciones/predeterminada':
        (new Controller\DireccionController())->predeterminada();
        break;

    default:
        (new Controller\ProductoController())->catalogo();
        break;
}