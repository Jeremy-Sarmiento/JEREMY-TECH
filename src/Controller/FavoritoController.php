<?php

declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Favorito;

/**
 * Controlador de favoritos del cliente (Jeremy Tech MVC).
 *
 * La vista usa formularios POST para que el corazón funcione incluso sin
 * JavaScript; `cuenta.js` intercepta el envío y lo convierte en una llamada
 * JSON para no recargar la página.
 */
class FavoritoController
{
    /**
     * Página "Mis favoritos".
     */
    public function ver(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $productos = Favorito::productosDeCliente($clienteId);
        $flash = self::tomarFlash();
        $rutaVista = 'favoritos';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/favoritos.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * POST: agrega o quita un producto de favoritos.
     *
     * Responde JSON para el flujo AJAX y redirige a "Mis favoritos" cuando
     * el formulario se envía de forma tradicional.
     */
    public function alternar(): void
    {
        \Security\Proteccion::exigirPost();

        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            if (self::esPeticionJson()) {
                self::responderJson(['ok' => false, 'error' => 'Inicia sesión para guardar tus favoritos.'], 401);
            }
            header('Location: ?ruta=login');
            exit;
        }

        $productoId = (int) ($_POST['producto_id'] ?? 0);
        $resultado = Favorito::alternar($clienteId, $productoId);

        if ($resultado === null) {
            if (self::esPeticionJson()) {
                self::responderJson(['ok' => false, 'error' => 'El producto no está disponible.'], 400);
            }
            self::guardarFlash('No se pudo guardar ese producto en favoritos.', true);
            header('Location: ?ruta=favoritos');
            exit;
        }

        if (self::esPeticionJson()) {
            self::responderJson([
                'ok' => true,
                'favorito' => $resultado,
                'total' => count(Favorito::idsDeCliente($clienteId)),
            ]);
        }

        self::guardarFlash(
            $resultado ? 'Producto añadido a favoritos.' : 'Producto eliminado de favoritos.',
            false
        );
        header('Location: ?ruta=favoritos');
        exit;
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

    private static function guardarFlash(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION['flash_favoritos'] = [
            'tipo' => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{tipo: string, mensaje: string}
     */
    private static function tomarFlash(): array
    {
        \Security\Proteccion::iniciarSesion();
        $flash = $_SESSION['flash_favoritos'] ?? null;
        unset($_SESSION['flash_favoritos']);

        if (is_array($flash)) {
            return [
                'tipo' => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        return ['tipo' => '', 'mensaje' => ''];
    }
}
