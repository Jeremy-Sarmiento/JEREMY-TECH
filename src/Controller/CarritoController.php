<?php

declare(strict_types=1);

namespace Controller;

use Model\Carrito;
use Model\Cuenta;
use Model\Notificacion;
use Model\PagoSimulado;

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
        \Security\Proteccion::exigirPost();
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
        \Security\Proteccion::exigirPost();
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
        \Security\Proteccion::exigirPost();
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
        \Security\Proteccion::exigirPost();
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
        \Security\Proteccion::exigirPost();
        if (!Cuenta::estaRegistrado()) {
            $this->respuestaJson(function (): array {
                return ['ok' => false, 'error' => 'Debes iniciar sesión para completar la compra'];
            });
            return;
        }

        // Los datos del paso 1 (contacto) y del paso 2 (SUNAT) son del pedido,
        // NO del perfil: el checkout no sobrescribe `users`. El perfil se
        // edita en "Mi perfil" y el registro. Aquí solo se resuelve el destino.

        // Destino de entrega (Etapa 5): la zona de la dirección fija el costo.
        $modalidad = (string) ($_POST['modalidad'] ?? 'tienda');
        // El radio `tipoComprobante` es la fuente; el hidden `comprobante` solo
        // existe para el ticket del navegador. Se normaliza aquí para que el
        // pedido guarde lo que el cliente eligió aunque el JS no corra.
        $comprobante = ($_POST['tipoComprobante'] ?? $_POST['comprobante'] ?? 'boleta') === 'factura'
            ? 'factura'
            : 'boleta';
        $pago = [
            'metodo'          => (string) ($_POST['metodo'] ?? ''),
            'modalidad'       => $modalidad,
            'comprobante'     => $comprobante,
            'documento'       => $comprobante === 'factura'
                ? preg_replace('/\D+/', '', (string) ($_POST['ruc'] ?? ''))
                : preg_replace('/\D+/', '', (string) ($_POST['dni'] ?? '')),
            'razon_social'    => $comprobante === 'factura'
                ? mb_substr(trim((string) ($_POST['empresa'] ?? '')), 0, 150)
                : '',
            'direccion_id'    => (int) ($_POST['direccion_id'] ?? 0),
            'tarifa_envio_id' => $modalidad === 'tienda'
                ? 0
                : (int) ($_POST['tarifa_envio_id'] ?? 0),
            'nueva_etiqueta'  => (string) ($_POST['nueva_etiqueta'] ?? ''),
            'nueva_direccion' => (string) ($_POST['nueva_direccion'] ?? ''),
            'nueva_referencia' => (string) ($_POST['nueva_referencia'] ?? ''),
        ];

        // El JSON del recibo viaja como texto plano para conservar espacios.
        $this->respuestaJson(function () use ($pago): array {
            // Validación fiscal en el servidor: la factura necesita RUC y
            // razón social; la boleta, si trae DNI, debe tener 8 dígitos.
            if ($pago['comprobante'] === 'factura') {
                if (strlen((string) $pago['documento']) !== 11) {
                    return ['ok' => false, 'error' => 'El RUC de la factura debe tener 11 dígitos.'];
                }
                if ((string) $pago['razon_social'] === '') {
                    return ['ok' => false, 'error' => 'La razón social es obligatoria para la factura.'];
                }
            } elseif ((string) $pago['documento'] !== '' && strlen((string) $pago['documento']) !== 8) {
                return ['ok' => false, 'error' => 'El DNI de la boleta debe tener 8 dígitos.'];
            }

            // Pago simulado: valida y decide el estado antes de crear el
            // pedido. Si la tarjeta/Yape se rechaza, no se descuenta stock ni
            // se registra una orden que parezca pagada.
            $resultadoPago = PagoSimulado::procesar((string) $pago['metodo'], $_POST);
            if (!($resultadoPago['ok'] ?? false)) {
                return ['ok' => false, 'error' => $resultadoPago['error'] ?? 'No se pudo procesar el pago.'];
            }

            $pago['metodo']         = (string) ($resultadoPago['metodo'] ?? $pago['metodo']);
            $pago['estado_pedido']  = (string) ($resultadoPago['estado_pedido'] ?? 'Pendiente');
            $pago['estado_pago']    = (string) ($resultadoPago['estado_pago'] ?? 'Pendiente');
            $pago['transaccion_id'] = (string) ($resultadoPago['transaccion_id'] ?? '');

            try {
                $pedidoId = Carrito::checkout($pago);
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => 'Error al registrar el pedido: ' . $e->getMessage()];
            }
            if ($pedidoId === null) {
                return ['ok' => false, 'error' => 'El carrito está vacío'];
            }

            // Correo simulado de pago confirmado: solo para tarjeta/Yape
            // aprobados en el checkout. Transferencia y contraentrega esperan
            // a que el administrador confirme el pago.
            if ((string) $pago['estado_pago'] === 'Pagado') {
                try {
                    Notificacion::pagoConfirmado($pedidoId);
                } catch (\Throwable $e) {
                    error_log('No se pudo registrar el correo de pago del pedido #' . $pedidoId . ': ' . $e->getMessage());
                }
            }

            return [
                'ok'           => true,
                'pedidoId'     => $pedidoId,
                'estadoPedido' => $pago['estado_pedido'],
                'estadoPago'   => $pago['estado_pago'],
                'transaccionId' => $pago['transaccion_id'],
                'mensaje'      => (string) ($resultadoPago['mensaje'] ?? ''),
            ];
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
