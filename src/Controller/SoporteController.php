<?php

declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\SoporteTicket;

/**
 * Controlador de tickets de soporte de la tienda (Jeremy Tech).
 *
 * El cliente autenticado abre tickets y consulta las respuestas del equipo.
 * El panel los atiende en `?ruta=admin/soporte`.
 */
class SoporteController
{
    /**
     * Página "Mis tickets": formulario de alta y listado con respuestas.
     */
    public function index(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $flash = self::tomarFlash();
        $tickets = SoporteTicket::deCliente($clienteId);
        $resumen = SoporteTicket::resumenDeCliente($clienteId);
        $pedidos = SoporteTicket::pedidosDeCliente($clienteId);
        $categorias = SoporteTicket::CATEGORIAS;

        // Precarga opcional desde "Necesito ayuda con este pedido".
        $pedidoId = (int) ($_GET['pedido'] ?? 0);
        $pedidoPre = $pedidoId > 0
            ? SoporteTicket::pedidoDeCliente($pedidoId, $clienteId)
            : null;

        $rutaVista = 'soporte';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/soporte.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * POST: crea un ticket y vuelve al listado.
     */
    public function crear(): void
    {
        \Security\Proteccion::exigirPost();

        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $resultado = SoporteTicket::crear(
            $clienteId,
            (int) ($_POST['pedido_id'] ?? 0),
            (string) ($_POST['asunto'] ?? ''),
            (string) ($_POST['categoria'] ?? 'General'),
            (string) ($_POST['descripcion'] ?? '')
        );

        if (!($resultado['ok'] ?? false)) {
            self::guardarFlash((string) ($resultado['error'] ?? 'No se pudo crear el ticket.'), true);
            header('Location: ?ruta=soporte#nuevo');
            exit;
        }

        self::guardarFlash(
            'Ticket ' . SoporteTicket::codigo((int) ($resultado['id'] ?? 0))
            . ' creado. Te responderemos aquí y en tus notificaciones.',
            false
        );
        header('Location: ?ruta=soporte#tickets');
        exit;
    }

    /** @return array{tipo: string, mensaje: string} */
    private static function tomarFlash(): array
    {
        \Security\Proteccion::iniciarSesion();
        $flash = $_SESSION['flash_soporte'] ?? null;
        unset($_SESSION['flash_soporte']);

        if (is_array($flash)) {
            return [
                'tipo'    => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        return ['tipo' => '', 'mensaje' => ''];
    }

    private static function guardarFlash(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION['flash_soporte'] = [
            'tipo'    => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }
}
