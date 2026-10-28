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

        if (self::esHttps()) {
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

    private static function esHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private static function html(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
}
