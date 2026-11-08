<?php

declare(strict_types=1);

// El orden de cada grupo y de sus archivos conserva la cascada existente.
return [
    'base' => [
        'base/documento.css',
        'componentes/catalogo.css',
        'paginas/compra.css',
        'paginas/pedidos.css',
        'base/responsive.css',
        'componentes/catalogo-producto.css',
        'componentes/busqueda.css',
        'paginas/contenido.css',
    ],
    'carrito' => ['paginas/carrito.css'],
    'auth' => ['paginas/auth.css'],
    'cuenta' => [
        'paginas/cuenta/perfil.css',
        'paginas/cuenta/favoritos-notificaciones.css',
        'paginas/cuenta/pedidos-resenas.css',
        'paginas/cuenta/vendedor.css',
        'paginas/cuenta/soporte.css',
    ],
    'chat' => ['componentes/chat.css'],
    'admin' => ['paginas/admin.css'],
    // El tema va completo al final, también en el panel administrativo.
    'tema' => [
        'tema/base.css',
        'tema/navegacion.css',
        'tema/catalogo.css',
        'tema/producto.css',
        'tema/compra.css',
        'tema/auth.css',
        'tema/cuenta.css',
        'tema/contenido.css',
        'tema/chat.css',
        'tema/admin.css',
        'tema/responsive.css',
    ],
];
