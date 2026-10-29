<?php

declare(strict_types=1);

namespace Controller;

use Model\Pasarela;

/**
 * Controlador del webhook de la pasarela de pagos (Jeremy Tech).
 *
 * Recibe POST JSON firmados, delega la verificación y la conciliación en
 * `Model\Pasarela` y responde en JSON con el código HTTP que espera el
 * proveedor. No usa sesión ni CSRF: la autenticidad la da la firma HMAC.
 */
class PagoController
{
    /**
     * POST ?ruta=pagos/webhook
     */
    public function webhook(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->responder(405, [
                'ok' => false,
                'mensaje' => 'Método no permitido. El webhook requiere POST.',
            ]);
            return;
        }

        $cuerpo = file_get_contents('php://input');
        $cuerpo = is_string($cuerpo) ? $cuerpo : '';

        if (strlen($cuerpo) > 1048576) {
            $this->responder(413, [
                'ok' => false,
                'mensaje' => 'El cuerpo del evento es demasiado grande.',
            ]);
            return;
        }

        $resultado = Pasarela::procesarWebhook($cuerpo, $this->cabeceras());

        $this->responder((int) $resultado['codigo'], [
            'ok' => (bool) $resultado['ok'],
            'mensaje' => (string) $resultado['mensaje'],
        ]);
    }

    /**
     * Cabeceras HTTP normalizadas en minúsculas.
     *
     * @return array<string, string>
     */
    private function cabeceras(): array
    {
        $cabeceras = [];

        foreach ($_SERVER as $clave => $valor) {
            if (!is_string($valor) || !str_starts_with((string) $clave, 'HTTP_')) {
                continue;
            }
            $nombre = strtolower(str_replace('_', '-', substr((string) $clave, 5)));
            $cabeceras[$nombre] = $valor;
        }

        // Algunos SAPIs no exponen todas las cabeceras en $_SERVER.
        if (function_exists('getallheaders')) {
            $extra = getallheaders();
            if (is_array($extra)) {
                foreach ($extra as $nombre => $valor) {
                    $cabeceras[strtolower((string) $nombre)] = (string) $valor;
                }
            }
        }

        return $cabeceras;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function responder(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
