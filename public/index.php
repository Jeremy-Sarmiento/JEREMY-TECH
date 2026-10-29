<?php
declare(strict_types=1);

/**
 * public/index.php — EL ENRUTADOR (Jeremy Tech MVC).
 * Único archivo accesible públicamente. Despacha la ruta a los controladores.
 *
 * Uso de desarrollo: php -S localhost:8000 -t public
 * Rutas:
 *   ?ruta=catalogo              -> catálogo de productos (por defecto)
 *   ?ruta=catalogo/sugerencias  -> sugerencias JSON del buscador
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
 *   ?ruta=login/recuperar             -> solicitar enlace de recuperación por correo
 *   ?ruta=login/restablecer&token=... -> elegir nueva contraseña con el token
 *   ?ruta=login/recuperar-pregunta      -> recuperar con pregunta de seguridad (GET/POST, 2 pasos)
 *   ?ruta=direcciones                   -> mis direcciones de envío (Etapa 5)
 *   POST ?ruta=direcciones/guardar      -> crear / editar dirección
 *   POST ?ruta=direcciones/eliminar     -> eliminar dirección
 *   POST ?ruta=direcciones/predeterminada -> marcar como predeterminada
 *   ?ruta=pagina&p=privacidad|terminos|envios|faq|contacto -> páginas informativas
 *   ?ruta=perfil                        -> perfil del cliente
 *   POST ?ruta=perfil/guardar           -> guardar datos del perfil
 *   ?ruta=favoritos                     -> productos favoritos del cliente
 *   POST ?ruta=favoritos/alternar       -> agregar/quitar un favorito
 *   ?ruta=notificaciones                -> bandeja de avisos del cliente
 *   ?ruta=mis-pedidos                   -> historial y seguimiento de pedidos
 *   ?ruta=soporte                       -> tickets de soporte postventa (Fase 6)
 *   POST ?ruta=soporte/crear            -> abrir un ticket
 *   POST ?ruta=pagos/webhook            -> webhook firmado de la pasarela (sin CSRF)
 *   POST ?ruta=resenas/guardar          -> crear/actualizar una reseña
 *   POST ?ruta=calificaciones-vendedor/guardar -> calificar al vendedor
 *   ?ruta=mis-productos                 -> panel de publicaciones del vendedor
 *   ?ruta=mis-productos/nuevo           -> formulario de nueva publicación
 *   ?ruta=mis-productos/editar&slug=... -> formulario de edición
 *   POST ?ruta=mis-productos/guardar    -> crear/actualizar publicación
 *   POST ?ruta=mis-productos/estado     -> activar/ocultar publicación
 *   POST ?ruta=mis-productos/stock      -> actualizar stock
 *   POST ?ruta=mis-productos/archivar   -> archivar publicación (conserva historial)
 *   POST ?ruta=mis-productos/restaurar  -> restaurar publicación archivada
 *   POST ?ruta=mis-productos/eliminar   -> borrar publicación sin historial
 *   POST ?ruta=mis-productos/variantes/guardar  -> crear/editar variante
 *   POST ?ruta=mis-productos/variantes/estado   -> activar/ocultar variante
 *   POST ?ruta=mis-productos/variantes/eliminar -> eliminar variante
 *   ?ruta=chat/token                    -> token CSRF para el widget
 *   POST ?ruta=chat/message             -> mensaje del chatbot
 */

require __DIR__ . '/../config/conexion.php';

\Security\Proteccion::iniciarSesion();
\Security\Proteccion::cabeceras();

// Todos los POST de la app (tienda, login y panel) exigen token CSRF.
// Se valida aquí para no depender de que cada controlador lo recuerde.
// El webhook de la pasarela es la única excepción: lo autentica su propia
// firma HMAC (cabecera X-JT-Signature), no una sesión de navegador.
$ruta = isset($_GET['ruta']) && is_string($_GET['ruta']) ? $_GET['ruta'] : 'catalogo';
if ($ruta !== 'pagos/webhook') {
    \Security\Proteccion::exigirCsrf();
}

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

    case 'catalogo/sugerencias':
        (new Controller\ProductoController())->sugerencias();
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

    case 'login/recuperar':
        (new Controller\CuentaController())->recuperarToken();
        break;

    case 'login/restablecer':
        (new Controller\CuentaController())->restablecerToken();
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

    case 'perfil':
        (new Controller\CuentaController())->perfil();
        break;

    case 'perfil/guardar':
        (new Controller\CuentaController())->guardarPerfil();
        break;

    case 'perfil/solicitar-vendedor':
        (new Controller\CuentaController())->solicitarVendedor();
        break;

    case 'favoritos':
        (new Controller\FavoritoController())->ver();
        break;

    case 'favoritos/alternar':
        (new Controller\FavoritoController())->alternar();
        break;

    case 'notificaciones':
        (new Controller\NotificacionController())->ver();
        break;

    case 'mis-pedidos':
        (new Controller\PedidoController())->index();
        break;

    case 'mis-productos':
        (new Controller\VendedorController())->productos();
        break;

    case 'mis-productos/nuevo':
        (new Controller\VendedorController())->formulario();
        break;

    case 'mis-productos/editar':
        (new Controller\VendedorController())->formulario();
        break;

    case 'mis-productos/guardar':
        (new Controller\VendedorController())->guardar();
        break;

    case 'mis-productos/estado':
        (new Controller\VendedorController())->estado();
        break;

    case 'mis-productos/stock':
        (new Controller\VendedorController())->stock();
        break;

    case 'mis-productos/archivar':
        (new Controller\VendedorController())->archivar();
        break;

    case 'mis-productos/restaurar':
        (new Controller\VendedorController())->restaurar();
        break;

    case 'mis-productos/eliminar':
        (new Controller\VendedorController())->eliminar();
        break;

    case 'mis-productos/variantes/guardar':
        (new Controller\VendedorController())->varianteGuardar();
        break;

    case 'mis-productos/variantes/estado':
        (new Controller\VendedorController())->varianteEstado();
        break;

    case 'mis-productos/variantes/eliminar':
        (new Controller\VendedorController())->varianteEliminar();
        break;

    case 'resenas/guardar':
        (new Controller\ResenaController())->guardar();
        break;

    case 'calificaciones-vendedor/guardar':
        (new Controller\CalificacionVendedorController())->guardar();
        break;

    case 'soporte':
        (new Controller\SoporteController())->index();
        break;

    case 'soporte/crear':
        (new Controller\SoporteController())->crear();
        break;

    case 'pagos/webhook':
        (new Controller\PagoController())->webhook();
        break;

    case 'pagina':
        (new Controller\PaginaController())->ver();
        break;

    case 'chat/token':
        (new Controller\ChatController())->token();
        break;

    case 'chat/message':
        (new Controller\ChatController())->message();
        break;

    case 'analitica/evento':
        (new Controller\AnaliticaController())->evento();
        break;

    default:
        (new Controller\ProductoController())->catalogo();
        break;
}