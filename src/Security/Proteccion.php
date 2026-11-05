<?php
declare(strict_types=1);

namespace Security;

/**
 * Utilidades de seguridad transversales (sesión, cabeceras y CSRF).
 *
 * La app tiene un único punto de entrada (`public/index.php`), así que la
 * validación CSRF se hace allí para TODAS las peticiones POST. Las vistas
 * solo tienen que imprimir el campo oculto con `campo()` y el JavaScript
 * enviar la cabecera `X-CSRF-Token` (o el campo `_csrf`).
 */
final class Proteccion
{
    private const CLAVE_TOKEN = '_csrf_token';
    private static ?string $nonce = null;

    /** Arranca la sesión con cookies endurecidas (idempotente). */
    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = self::esHttps();

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /** Genera y devuelve el nonce CSP de la petición actual. */
    public static function nonce(): string
    {
        return self::$nonce ??= bin2hex(random_bytes(16));
    }

    /** Cabeceras de seguridad comunes. */
    public static function cabeceras(): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-Permitted-Cross-Domain-Policies: none');

        header("Content-Security-Policy: base-uri 'self'; object-src 'none'; frame-ancestors 'self'");

        // shortcut: CSP de scripts solo report-only; aplicar tras migrar onclick/onsubmit e inventariar imágenes de vendedores.
        // Google Fonts, Leaflet/OSM y el cambio PEN son los únicos proveedores de recursos fijos de las vistas/JS.
        $https = self::esHttps();
        $nonce = self::nonce();
        $csp = "default-src 'self'; script-src 'self' 'nonce-" . $nonce . "' https://unpkg.com/leaflet@1.9.4/dist/leaflet.js; "
            . "script-src-attr 'none'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com/leaflet@1.9.4/dist/leaflet.css; "
            . "img-src 'self' data: https://unpkg.com/leaflet@1.9.4/dist/images/ https://*.tile.openstreetmap.org; "
            . "font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://open.er-api.com/v6/latest/PEN; "
            . "object-src 'none'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
        header('Content-Security-Policy-Report-Only: ' . $csp);

        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $ruta = (string) ($_GET['ruta'] ?? '');
        if ($ruta === 'admin' || str_starts_with($ruta, 'admin/')
            || $ruta === 'login' || str_starts_with($ruta, 'login/')
            || $ruta === 'direcciones' || str_starts_with($ruta, 'direcciones/')
            || $ruta === 'perfil' || str_starts_with($ruta, 'perfil/')
            || $ruta === 'favoritos' || str_starts_with($ruta, 'favoritos/')
            || $ruta === 'notificaciones'
            || $ruta === 'mis-pedidos' || str_starts_with($ruta, 'resenas/')
            || $ruta === 'mis-productos' || str_starts_with($ruta, 'mis-productos/')) {
            header('Cache-Control: no-store, private');
            header('Pragma: no-cache');
        }
    }

    /** Devuelve el token CSRF de la sesión, creándolo si aún no existe. */
    public static function token(): string
    {
        self::iniciarSesion();

        $token = $_SESSION[self::CLAVE_TOKEN] ?? null;
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::CLAVE_TOKEN] = $token;
        }

        return $token;
    }

    /** Campo oculto listo para insertar en un formulario POST. */
    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::html(self::token()) . '">';
    }

    /** Valida un token recibido por formulario o por cabecera. */
    public static function validar(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals(self::token(), $token);
    }

    /** Exige POST; para acciones que modifican datos. */
    public static function exigirPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return;
        }

        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
        exit('Método no permitido. Esta acción requiere POST.');
    }

    /**
     * Valida el CSRF de cualquier POST y corta la petición si no es válido.
     * Se llama una sola vez desde public/index.php.
     */
    public static function exigirCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
        if (is_string($enviado) && self::validar($enviado)) {
            return;
        }

        http_response_code(403);

        if (self::peticionJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'    => false,
                'error' => 'Sesión expirada o token de seguridad inválido. Recarga la página e inténtalo de nuevo.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguridad · Jeremy Tech</title>
  <style>
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0b1020;color:#e8ecff;font-family:system-ui,-apple-system,"Segoe UI",sans-serif}
    .caja{max-width:520px;padding:32px;border:1px solid rgba(255,255,255,.14);border-radius:16px;background:#121a30;box-shadow:0 20px 60px rgba(0,0,0,.35)}
    h1{margin:0 0 10px;font-size:24px}p{line-height:1.6;color:#b9c4e6}
    a{display:inline-block;margin-top:12px;padding:10px 16px;border-radius:9px;background:#f59e0b;color:#111827;text-decoration:none;font-weight:700}
  </style>
</head>
<body>
  <main class="caja">
    <h1>Tu sesión expiró</h1>
    <p>No pudimos verificar el token de seguridad. Recarga la página y vuelve a intentarlo.</p>
    <a href="index.php">Volver a la tienda</a>
  </main>
</body>
</html>
        <?php
        exit;
    }

    /** Rota el token tras iniciar o cerrar sesión. */
    public static function rotarToken(): void
    {
        self::iniciarSesion();
        unset($_SESSION[self::CLAVE_TOKEN]);
        self::token();
    }

    /**
     * Genera un formulario POST para una acción (sustituye enlaces GET que
     * modificaban datos). Se usa en el panel y en "Mis direcciones".
     *
     * @param array<string, int|string> $campos
     */
    public static function botonPost(
        string $ruta,
        array $campos,
        string $texto,
        string $clase = 'btn-mini',
        ?string $confirmar = null
    ): string {
        $html = '<form method="post" action="?ruta=' . self::html($ruta) . '" class="accion-post">';
        $html .= self::campo();

        foreach ($campos as $nombre => $valor) {
            $html .= '<input type="hidden" name="' . self::html((string) $nombre)
                . '" value="' . self::html((string) $valor) . '">';
        }

        $onclick = '';
        if ($confirmar !== null) {
            $json = json_encode($confirmar, JSON_UNESCAPED_UNICODE);
            $onclick = ' onclick="return confirm(' . self::html((string) $json) . ')"';
        }

        $html .= '<button type="submit" class="' . self::html($clase) . '"' . $onclick . '>'
            . self::html($texto) . '</button></form>';

        return $html;
    }

    private static function peticionJson(): bool
    {
        $solicitado = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $acepta     = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return $solicitado === 'fetch'
            || $solicitado === 'xmlhttprequest'
            || str_contains($acepta, 'application/json');
    }

    /** IP real: solo se retiran saltos de proxies IP explícitos en TRUSTED_PROXIES (separados por comas). */
    public static function ipCliente(): string
    {
        $remoto = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!is_string($remoto) || filter_var($remoto, FILTER_VALIDATE_IP) === false) {
            return '';
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if (!self::esProxyConfiable($remoto) || !is_string($forwarded) || trim($forwarded) === '') {
            return $remoto;
        }

        $actual = $remoto;
        foreach (array_reverse(explode(',', $forwarded)) as $ip) {
            if (!self::esProxyConfiable($actual)) {
                break;
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                return $remoto;
            }
            $actual = $ip;
        }

        return $actual;
    }

    /** El proxy configurado debe sobrescribir X-Forwarded-Proto y añadir su peer a X-Forwarded-For. */
    public static function esHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $remoto = $_SERVER['REMOTE_ADDR'] ?? '';
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        return is_string($remoto) && self::esProxyConfiable($remoto)
            && is_string($proto) && strtolower($proto) === 'https';
    }

    private static function esProxyConfiable(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        foreach (explode(',', (string) getenv('TRUSTED_PROXIES')) as $proxy) {
            $proxy = trim($proxy);
            if (filter_var($proxy, FILTER_VALIDATE_IP) !== false && inet_pton($ip) === inet_pton($proxy)) {
                return true;
            }
        }

        return false;
    }

    private static function html(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}
