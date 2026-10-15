<?php
declare(strict_types=1);

namespace Controller;

use Model\Carrito;
use Model\Cuenta;

/**
 * Controlador del Carrito (Jeremy Tech MVC).
 * Capa de Negocio: procesa las acciones del carrito (formularios, sesiones)
 * y renderiza la página de resumen/checkout.
 */
class CarritoController
{
    /**
     * Página del carrito: resumen, checkout e historial de pedidos.
     */
    public function ver(): void
    {
        $clienteLogeado = Cuenta::usuarioActual();
        $lineas = Carrito::lineas();
        $subtotal = Carrito::subtotalPEN();
        $cupon = Carrito::cuponActivo();
        $descuento = Carrito::montoDescuentoPEN();
        $totalItems = Carrito::totalItems();
        $historial = $clienteLogeado ? Carrito::historial((int) $clienteLogeado['id']) : [];

        $rutaVista = 'carrito';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/carrito.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Acción AJAX: agregar producto al carrito.
     */
    public function agregar(): void
    {
        $id = (string) ($_POST['id'] ?? '');
        $cantidad = (int) ($_POST['cantidad'] ?? 1);
        // (Etapa 7) La ficha envía la variante elegida; el catálogo no.
        $variante = (int) ($_POST['variante'] ?? 0);

        $this->respuestaJson(function () use ($id, $cantidad, $variante): array {
            if ($id === '') {
                return ['ok' => false, 'error' => 'Producto no especificado'];
            }
            Carrito::agregar($id, $cantidad, $variante > 0 ? $variante : null);
            return ['ok' => true, 'totalItems' => Carrito::totalItems()];
        });
    }

    /**
     * Acción AJAX: eliminar producto del carrito.
     */
    public function eliminar(): void
    {
        $id = (string) ($_POST['id'] ?? '');

        $this->respuestaJson(function () use ($id): array {
            if ($id === '') {
                return ['ok' => false, 'error' => 'Producto no especificado'];
            }
            Carrito::eliminar($id);
            return [
                'ok'         => true,
                'totalItems' => Carrito::totalItems(),
                'subtotal'   => Carrito::subtotalPEN(),
            ];
        });
    }

    /**
     * Acción AJAX: cambiar la cantidad de un producto.
     */
    public function cambiarCantidad(): void
    {
        $id = (string) ($_POST['id'] ?? '');
        $cantidad = (int) ($_POST['cantidad'] ?? 1);

        $this->respuestaJson(function () use ($id, $cantidad): array {
            if ($id === '') {
                return ['ok' => false, 'error' => 'Producto no especificado'];
            }
            Carrito::cambiarCantidad($id, $cantidad);
            return [
                'ok'         => true,
                'totalItems' => Carrito::totalItems(),
                'subtotal'   => Carrito::subtotalPEN(),
            ];
        });
    }

    /**
     * Acción AJAX: aplicar un cupón de descuento.
     */
    public function aplicarCupon(): void
    {
        $codigo = (string) ($_POST['codigo'] ?? '');

        $this->respuestaJson(function () use ($codigo): array {
            if ($codigo === '') {
                return ['ok' => false, 'error' => 'Ingresa un código'];
            }
            $ok = Carrito::aplicarCupon($codigo);
            if (!$ok) {
                return ['ok' => false, 'error' => 'Cupón inválido'];
            }
            return [
                'ok'         => true,
                'descuento'  => Carrito::montoDescuentoPEN(),
                'totalItems' => Carrito::totalItems(),
            ];
        });
    }

    /**
     * procesar pedido: crea cliente + pedido + detalle + pago (checkout completo).
     */
    public function procesar(): void
    {
        if (!Cuenta::estaRegistrado()) {
            $this->respuestaJson(function (): array {
                return ['ok' => false, 'error' => 'Debes iniciar sesión para completar la compra'];
            });
            return;
        }

        $cliente = [
            'tipo'      => ($_POST['tipo'] ?? '') === 'B2B' ? 'B2B' : 'B2C',
            'nombre'    => trim((string) ($_POST['nombre'] ?? '')),
            'empresa'   => trim((string) ($_POST['empresa'] ?? '')),
            'ruc'       => trim((string) ($_POST['ruc'] ?? '')),
            'dni'       => trim((string) ($_POST['dni'] ?? '')),
            'email'     => trim((string) ($_POST['email'] ?? '')),
            'telefono'  => trim((string) ($_POST['telefono'] ?? '')),
            'direccion' => trim((string) ($_POST['direccion'] ?? '')),
        ];

        // Destino de entrega (Etapa 5): la zona de la dirección fija el costo.
        $modalidad = (string) ($_POST['modalidad'] ?? 'tienda');
        $pago = [
            'metodo'          => (string) ($_POST['metodo'] ?? ''),
            'modalidad'       => $modalidad,
            'comprobante'     => (string) ($_POST['comprobante'] ?? 'boleta'),
            'direccion_id'    => (int) ($_POST['direccion_id'] ?? 0),
            'tarifa_envio_id' => $modalidad === 'tienda'
                ? 0
                : (int) ($_POST['tarifa_envio_id'] ?? 0),
            'nueva_etiqueta'  => (string) ($_POST['nueva_etiqueta'] ?? ''),
            'nueva_direccion' => (string) ($_POST['nueva_direccion'] ?? ''),
            'nueva_referencia' => (string) ($_POST['nueva_referencia'] ?? ''),
        ];

        // El JSON del recibo viaja como texto plano para conservar espacios.
        $this->respuestaJson(function () use ($cliente, $pago): array {
            if ($cliente['nombre'] === '') {
                return ['ok' => false, 'error' => 'El nombre es obligatorio'];
            }
            try {
                $pedidoId = Carrito::checkout($cliente, $pago);
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => 'Error al registrar el pedido: ' . $e->getMessage()];
            }
            if ($pedidoId === null) {
                return ['ok' => false, 'error' => 'El carrito está vacío'];
            }
            return ['ok' => true, 'pedidoId' => $pedidoId];
        });
    }

    /**
     * Envía una respuesta JSON y corta la ejecución.
     */
    private function respuestaJson(callable $callback): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos = $callback();
        } catch (\Throwable $error) {
            $datos = ['ok' => false, 'error' => 'Error del servidor'];
        }
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}