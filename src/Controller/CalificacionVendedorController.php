<?php
declare(strict_types=1);

namespace Controller;

use Model\CalificacionVendedor;
use Model\Cuenta;
use Model\Resena;

/**
 * Controlador de calificaciones al vendedor (Jeremy Tech) — Bloque 3.
 *
 * "Mis pedidos" envía un formulario POST normal. `cuenta.js` lo intercepta
 * para responder por JSON sin recargar; si el JavaScript no está disponible,
 * el flujo clásico redirige con un aviso.
 *
 * Es independiente de `ResenaController`: las reseñas califican el producto
 * y estas calificaciones valoran al vendedor como tal.
 */
class CalificacionVendedorController
{
    /** Clave de sesión del aviso de la siguiente petición. */
    private const FLASH = 'flash_calificacion_vendedor';

    /**
     * POST: crea o actualiza la calificación del vendedor.
     */
    public function guardar(): void
    {
        \Security\Proteccion::exigirPost();

        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            if (self::esPeticionJson()) {
                self::responderJson(
                    ['ok' => false, 'error' => 'Inicia sesión para calificar al vendedor.'],
                    401
                );
            }
            header('Location: ?ruta=login');
            exit;
        }

        $vendedorId   = (int) ($_POST['vendedor_id'] ?? 0);
        $calificacion = (int) ($_POST['calificacion'] ?? 0);
        $comentario   = is_scalar($_POST['comentario'] ?? null)
            ? (string) $_POST['comentario']
            : '';

        $error = CalificacionVendedor::guardar($clienteId, $vendedorId, $calificacion, $comentario);

        if ($error !== null) {
            if (self::esPeticionJson()) {
                self::responderJson(['ok' => false, 'error' => $error], 422);
            }
            self::guardarAviso($error, true);
            header('Location: ?ruta=mis-pedidos#opiniones');
            exit;
        }

        $resumen = CalificacionVendedor::resumen($vendedorId);

        if (self::esPeticionJson()) {
            self::responderJson([
                'ok'           => true,
                'mensaje'      => '¡Gracias! Tu calificación del vendedor se guardó.',
                'vendedorId'   => $vendedorId,
                'calificacion' => $calificacion,
                'comentario'   => trim($comentario),
                'resumen'      => $resumen,
                'estrellas'    => Resena::estrellas($resumen['promedio']),
            ]);
        }

        self::guardarAviso('¡Gracias! Tu calificación del vendedor se guardó.', false);
        header('Location: ?ruta=mis-pedidos#opiniones');
        exit;
    }

    /**
     * Aviso pendiente de la última calificación (para "Mis pedidos").
     *
     * @return array{tipo: string, mensaje: string}
     */
    public static function tomarAviso(): array
    {
        \Security\Proteccion::iniciarSesion();
        $flash = $_SESSION[self::FLASH] ?? null;
        unset($_SESSION[self::FLASH]);

        if (is_array($flash)) {
            return [
                'tipo'    => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        return ['tipo' => '', 'mensaje' => ''];
    }

    private static function guardarAviso(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION[self::FLASH] = [
            'tipo'    => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }

    private static function esPeticionJson(): bool
    {
        $solicitado = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $acepta = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return $solicitado === 'fetch'
            || $solicitado === 'xmlhttprequest'
            || str_contains($acepta, 'application/json');
    }

    /**
     * @param array<string, mixed> $datos
     */
    private static function responderJson(array $datos, int $estado = 200): void
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE) ?: '{}';
        exit;
    }
}
