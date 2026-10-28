<?php
declare(strict_types=1);

/**
 * tools/webhook-prueba.php — envía un webhook firmado al endpoint local.
 *
 * Uso:
 *   php tools/webhook-prueba.php --pedido=42 --estado=approved
 *   php tools/webhook-prueba.php --pago=7 --estado=refunded --evento=evt_001
 *   php tools/webhook-prueba.php --referencia=TX-123 --estado=pending \
 *        --url=http://127.0.0.1:8099/index.php?ruta=pagos/webhook
 *
 * Lee `PAGOS_WEBHOOK_SECRET` y `APP_URL` de `.env`. No requiere sesión ni
 * token CSRF: el endpoint autentica la petición con la firma HMAC.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta por CLI.\n");
}

use Model\Pasarela;

require __DIR__ . '/../config/conexion.php';

$opciones = getopt('', [
    'pedido::',
    'pago::',
    'referencia::',
    'estado::',
    'evento::',
    'tipo::',
    'metodo::',
    'gateway::',
    'url::',
    'ayuda',
    'help',
]);

if (isset($opciones['ayuda']) || isset($opciones['help'])) {
    echo "Uso: php tools/webhook-prueba.php [opciones]\n\n"
        . "  --pedido=N       id del pedido a conciliar\n"
        . "  --pago=N         id del pago (alternativa a --pedido)\n"
        . "  --referencia=X   referencia externa / transacción\n"
        . "  --estado=X       approved|rejected|refunded|pending (def. approved)\n"
        . "  --evento=X       id único del evento (def. generado)\n"
        . "  --tipo=X         tipo de evento (def. payment.updated)\n"
        . "  --metodo=X       método de pago (def. Tarjeta)\n"
        . "  --gateway=X      cabecera X-JT-Gateway (def. PAGOS_PROVEEDOR)\n"
        . "  --url=URL        endpoint completo (def. APP_URL + ruta)\n";
    exit(0);
}

if (!Pasarela::configurada()) {
    fwrite(STDERR, "Falta PAGOS_WEBHOOK_SECRET en .env: el webhook está desactivado.\n");
    exit(1);
}

$pedidoId = (int) ($opciones['pedido'] ?? 0);
$pagoId = (int) ($opciones['pago'] ?? 0);
$referencia = trim((string) ($opciones['referencia'] ?? ''));
if ($pedidoId <= 0 && $pagoId <= 0 && $referencia === '') {
    fwrite(STDERR, "Indica --pedido, --pago o --referencia para localizar el pago.\n");
    exit(1);
}

$datos = [
    'estado' => (string) ($opciones['estado'] ?? 'approved'),
    'metodo' => (string) ($opciones['metodo'] ?? 'Tarjeta'),
];
if ($pedidoId > 0) {
    $datos['pedido_id'] = $pedidoId;
}
if ($pagoId > 0) {
    $datos['pago_id'] = $pagoId;
}
if ($referencia !== '') {
    $datos['referencia'] = $referencia;
}

$evento = [
    'id' => (string) ($opciones['evento'] ?? ('evt_prueba_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)))),
    'type' => (string) ($opciones['tipo'] ?? 'payment.updated'),
    'data' => $datos,
];

$cuerpo = json_encode($evento, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($cuerpo)) {
    fwrite(STDERR, "No se pudo serializar el evento.\n");
    exit(1);
}

$timestamp = time();
$firma = Pasarela::firmar($cuerpo, $timestamp);
$gateway = (string) ($opciones['gateway'] ?? Pasarela::proveedor());
$url = (string) ($opciones['url'] ?? Pasarela::webhookUrl());

$cabeceras = [
    'Content-Type: application/json',
    'X-JT-Timestamp: ' . $timestamp,
    'X-JT-Signature: ' . $firma,
    'X-JT-Gateway: ' . $gateway,
];

echo "POST {$url}\n";
echo "Evento {$evento['id']} · estado {$datos['estado']}\n\n";

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $cuerpo,
        CURLOPT_HTTPHEADER => $cabeceras,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $respuesta = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        fwrite(STDERR, "Error de conexión: {$error}\n");
        exit(1);
    }
} else {
    $contexto = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $cabeceras),
            'content' => $cuerpo,
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);
    $respuesta = @file_get_contents($url, false, $contexto);
    if ($respuesta === false) {
        fwrite(STDERR, "Error de conexión al endpoint.\n");
        exit(1);
    }

    $codigo = 0;
    foreach ($http_response_header ?? [] as $linea) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $linea, $m) === 1) {
            $codigo = (int) $m[1];
        }
    }
}

echo "HTTP {$codigo}\n";
echo $respuesta . "\n";

exit($codigo >= 200 && $codigo < 300 ? 0 : 1);
