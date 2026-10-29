<?php

declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Notificacion;

/**
 * Controlador de la bandeja de notificaciones del cliente.
 *
 * Los avisos son los mismos que genera `Model\Notificacion` al confirmar un
 * pago o marcar un pedido como enviado; aquí solo se muestran al comprador.
 */
class NotificacionController
{
    public function ver(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $notificaciones = Notificacion::deCliente($clienteId);
        $rutaVista = 'notificaciones';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/notificaciones.php';
        require BASE_PATH . '/views/layout/footer.php';
    }
}
