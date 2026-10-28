<?php
declare(strict_types=1);

namespace Controller;

/**
 * Endpoint público de analítica: recibe los eventos generados por el storefront.
 *
 * El token CSRF lo valida el router global (`public/index.php`) antes de llegar
 * aquí, así que este endpoint solo admite POST con token válido. El body es JSON:
 *   { "tipo_evento": "busqueda", "datos": { "consulta": "iphone" }, "pagina": "catalogo" }
 */
final class AnaliticaController
{
    private const TIPOS_PERMITIDOS = [
        'visita',
        'producto_visto',
        'busqueda',
        'agregar_carrito',
        'iniciar_checkout',
        'compra',
        'clic_whatsapp',
        'pregunta_chatbot',
    ];

    /** Campos aceptados por cada tipo de evento (evita payloads arbitrarios). */
    private const CAMPOS_PER_TIPO = [
        'visita' => [],
        'producto_visto' => ['producto_id', 'slug'],
        'busqueda' => ['consulta'],
        'agregar_carrito' => ['producto_id'],
        'iniciar_checkout' => ['producto_id'],
        'compra' => ['pedido_id', 'total_pen'],
        'clic_whatsapp' => ['producto'],
        'pregunta_chatbot' => ['faq_id', 'origen'],
    ];


    public function evento(): void
    {
        \Security\Proteccion::exigirPost();

        $cuerpo = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($cuerpo)) {
            $this->json(['ok' => false, 'error' => 'Cuerpo JSON inválido.'], 422);
        }

        $tipoEvento = trim((string) ($cuerpo['tipo_evento'] ?? ''));
        $datos = is_array($cuerpo['datos'] ?? null) ? $cuerpo['datos'] : [];
        $pagina = trim((string) ($cuerpo['pagina'] ?? ''));

        if (!in_array($tipoEvento, self::TIPOS_PERMITIDOS, true)) {
            $this->json(['ok' => false, 'error' => 'Tipo de evento no permitido.'], 422);
        }
        if (mb_strlen($tipoEvento) > 60) {
            $this->json(['ok' => false, 'error' => 'Tipo de evento demasiado largo.'], 422);
        }

        $datosPermitidos = [];
        foreach (self::CAMPOS_PER_TIPO[$tipoEvento] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $datosPermitidos[$campo] = $datos[$campo];
            }
        }
        $datos = $datosPermitidos;

        $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE) ?: '{}';
        if (mb_strlen($datosJson) > 1000) {
            $this->json(['ok' => false, 'error' => 'Payload demasiado grande.'], 422);
        }
        if (mb_strlen($pagina) > 80) {
            $this->json(['ok' => false, 'error' => 'Página demasiado larga.'], 422);
        }

        $ok = \Model\Analitica::registrar($tipoEvento, $datos, $pagina, null);

        $this->json(['ok' => true, 'tipo_evento' => $tipoEvento]);
    }

    private function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
