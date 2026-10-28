<?php
declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Resena;

/**
 * Controlador de reseñas de productos (Jeremy Tech MVC) — Fase 2.
 *
 * La ficha del producto y "Mis pedidos" envían un formulario POST normal.
 * `cuenta.js` lo intercepta para responder por JSON sin recargar; si el
 * JavaScript no está disponible, el flujo clásico redirige con un aviso.
 */
class ResenaController
{
    /**
     * POST: crea o actualiza la reseña del cliente autenticado.
     */
    public function guardar(): void
    {
        \Security\Proteccion::exigirPost();

        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            if (self::esPeticionJson()) {
                self::responderJson(['ok' => false, 'error' => 'Inicia sesión para dejar tu opinión.'], 401);
            }
            header('Location: ?ruta=login');
            exit;
        }

        $productoId   = (int) ($_POST['producto_id'] ?? 0);
        $calificacion = (int) ($_POST['calificacion'] ?? 0);
        $comentario   = is_scalar($_POST['comentario'] ?? null)
            ? (string) $_POST['comentario']
            : '';

        $error = Resena::guardar($clienteId, $productoId, $calificacion, $comentario);

        if ($error !== null) {
            if (self::esPeticionJson()) {
                self::responderJson(['ok' => false, 'error' => $error], 422);
            }
            self::guardarAviso($error, true);
            header('Location: ' . self::destino());
            exit;
        }

        if (self::esPeticionJson()) {
            $resumen = Resena::resumen($productoId);
            $resenas = Resena::deCliente($clienteId);
            $cuenta = Cuenta::usuarioActual();
            $autor = trim((string) ($cuenta['name'] ?? 'Cliente'));
            $apellidos = trim((string) ($cuenta['apellidos'] ?? ''));
            if ($apellidos !== '') {
                $autor .= ' ' . mb_strtoupper(mb_substr($apellidos, 0, 1)) . '.';
            }

            self::responderJson([
                'ok'         => true,
                'mensaje'    => '¡Gracias! Tu opinión se guardó correctamente.',
                'productoId' => $productoId,
                'resumen'    => $resumen,
                'estrellas'  => Resena::estrellas($resumen['promedio']),
                'resena'     => $resenas[$productoId] ?? null,
                'autor'      => $autor,
            ]);
        }

        self::guardarAviso('¡Gracias! Tu opinión se guardó correctamente.', false);
        header('Location: ' . self::destino());
        exit;
    }

    /**
     * Aviso pendiente de la última reseña (para las vistas que la muestran).
     *
     * @return array{tipo: string, mensaje: string}
     */
    public static function tomarAviso(): array
    {
        \Security\Proteccion::iniciarSesion();
        $flash = $_SESSION['flash_resena'] ?? null;
        unset($_SESSION['flash_resena']);

        if (is_array($flash)) {
            return [
                'tipo'    => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        return ['tipo' => '', 'mensaje' => ''];
    }

    /**
     * Vuelve a la ficha del producto si el formulario trae `slug`; si no,
     * a "Mis pedidos". Nunca se usa una URL arbitraria del navegador.
     */
    private static function destino(): string
    {
        $slug = is_scalar($_POST['slug'] ?? null) ? trim((string) $_POST['slug']) : '';

        if ($slug !== '' && preg_match('/^[a-z0-9][a-z0-9-]{0,180}$/i', $slug)) {
            return '?ruta=producto&slug=' . urlencode($slug) . '#resenas';
        }

        return '?ruta=mis-pedidos#opiniones';
    }

    private static function guardarAviso(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION['flash_resena'] = [
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
