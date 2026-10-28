<?php
declare(strict_types=1);

namespace Controller;

use Model\CalificacionVendedor;
use Model\Carrito;
use Model\Cuenta;
use Model\Pedido;
use Model\Resena;

/**
 * Controlador de "Mis pedidos" (Jeremy Tech MVC) — Fase 2.
 *
 * Página dedicada con el historial completo del cliente autenticado:
 * filtros por estado, resumen de compras, detalle de cada línea, pago,
 * seguimiento y widgets de reseña. Solo consulta pedidos del propio
 * cliente: el id nunca llega por la URL.
 */
class PedidoController
{
    /**
     * Estados que se pueden filtrar, en el orden de las pestañas.
     *
     * @var array<int, string>
     */
    private const FILTROS = [
        'Pendiente',
        'Pagado',
        'Enviado',
        'Entregado',
        'Cancelado',
    ];

    /**
     * Página "Mis pedidos".
     */
    public function index(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $estado = is_scalar($_GET['estado'] ?? null)
            ? trim((string) $_GET['estado'])
            : '';
        if (!in_array($estado, self::FILTROS, true)) {
            $estado = '';
        }

        $pedidos = Carrito::historial($clienteId, 100, $estado !== '' ? $estado : null);
        $resumen = Carrito::resumenPedidos($clienteId);
        $resenas = Resena::deCliente($clienteId);
        $comprados = Resena::productosComprados($clienteId);
        $avisoResena = ResenaController::tomarAviso();
        $calificacionesVendedor = CalificacionVendedor::deCliente($clienteId);
        $avisoCalificacionVendedor = CalificacionVendedorController::tomarAviso();
        $filtros = self::FILTROS;

        $rutaVista = 'mis-pedidos';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/mis_pedidos.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Código visible de un pedido (`#JT-00001`), igual que en el resto de la
     * tienda. Se expone para no repetir el formato en la vista.
     */
    public static function codigo(int $pedidoId): string
    {
        return Pedido::codigo($pedidoId);
    }
}
