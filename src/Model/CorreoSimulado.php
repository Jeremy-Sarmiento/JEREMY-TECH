<?php
declare(strict_types=1);

namespace Model;

/**
 * Transporte de correo SIMULADO.
 *
 * No abre conexiones SMTP, no usa `mail()` y no envía nada. Su única tarea es
 * materializar el correo como archivo `.eml` (y una copia `.html` para
 * revisarlo en el navegador) dentro de `MAIL_STORAGE_PATH/AAAA/MM`.
 *
 * `MAIL_STORAGE_PATH` debe quedar fuera del DocumentRoot: los `.htaccess` no
 * protegen nada cuando Apache tiene `AllowOverride None`. Los archivos se
 * crean con permisos restringidos. El contenido completo nunca se guarda en
 * `notificaciones.cuerpo` (VARCHAR 500); allí solo va un resumen.
 */
final class CorreoSimulado
{
    /**
     * Guarda el correo simulado y devuelve la ruta relativa al proyecto.
     *
     * @throws \RuntimeException si no se puede crear el directorio o escribir.
     */
    public static function guardar(
        string $tipo,
        int $pedidoId,
        string $destinatario,
        string $asunto,
        string $html,
        string $texto
    ): string {
        $fromEmail = env('MAIL_FROM', self::configuracion('email', 'no-reply@jeremy-tech.local'));
        $fromName = env('MAIL_FROM_NAME', self::configuracion('nombre_tienda', 'JEREMY-TECH'));

        $destinatario = trim($destinatario);
        if (filter_var($destinatario, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('El cliente no tiene un correo válido para el registro simulado.');
        }

        $directorio = MAIL_STORAGE_PATH . '/' . date('Y') . '/' . date('m');

        // Defensa en profundidad: aunque el fallback esté dentro del proyecto,
        // nunca se escribe un correo en una ruta servible por Apache.
        $documentRoot = trim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
        if ($documentRoot !== '') {
            $docRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
            $rutaDirectorio = str_replace('\\', '/', $directorio);
            if ($docRoot !== ''
                && ($rutaDirectorio === $docRoot
                    || str_starts_with($rutaDirectorio . '/', $docRoot . '/'))
            ) {
                throw new \RuntimeException(
                    'MAIL_STORAGE_PATH no puede estar dentro del DocumentRoot. '
                    . 'Define una ruta privada en .env.'
                );
            }
        }

        if (!is_dir($directorio)
            && !mkdir($directorio, 0770, true)
            && !is_dir($directorio)
        ) {
            throw new \RuntimeException('No se pudo crear el directorio de correos simulados.');
        }

        $base = date('Ymd_His')
            . '_' . preg_replace('/[^a-z0-9_-]+/i', '-', $tipo)
            . '_pedido-' . $pedidoId
            . '_' . bin2hex(random_bytes(4));

        $emlPath = $directorio . '/' . $base . '.eml';
        $htmlPath = $directorio . '/' . $base . '.html';
        $boundary = '=_JT_' . bin2hex(random_bytes(12));

        $asuntoCodificado = mb_encode_mimeheader($asunto, 'UTF-8', 'B');
        $from = self::direccion($fromName, $fromEmail);
        $to = self::direccion('', $destinatario);

        $cabeceras = [
            'Date: ' . date('r'),
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . $asuntoCodificado,
            'Message-ID: <' . $base . '@jeremy-tech.local>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'X-JeremyTech-Transport: simulado',
            'X-JeremyTech-Pedido: #JT-' . str_pad((string) $pedidoId, 5, '0', STR_PAD_LEFT),
        ];

        $cuerpo = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $texto . "\r\n\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . $html . "\r\n\r\n"
            . "--{$boundary}--\r\n";

        $eml = implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpo;

        if (file_put_contents($emlPath, $eml, LOCK_EX) === false
            || file_put_contents($htmlPath, $html, LOCK_EX) === false
        ) {
            throw new \RuntimeException('No se pudo escribir el correo simulado.');
        }

        @chmod($emlPath, 0640);
        @chmod($htmlPath, 0640);

        return self::rutaRelativa($emlPath);
    }

    /**
     * Convierte una ruta absoluta en una ruta relativa al proyecto cuando es
     * posible; si el almacenamiento está fuera del proyecto, conserva la ruta
     * absoluta para no falsear la ubicación real.
     */
    public static function rutaRelativa(string $ruta): string
    {
        $prefijo = BASE_PATH . '/';
        return str_starts_with($ruta, $prefijo) ? substr($ruta, strlen($prefijo)) : $ruta;
    }

    private static function configuracion(string $clave, string $porDefecto = ''): string
    {
        static $cache = [];

        if (!array_key_exists($clave, $cache)) {
            $stmt = conexion()->prepare('SELECT valor FROM configuracion WHERE clave = ? LIMIT 1');
            $stmt->execute([$clave]);
            $valor = $stmt->fetchColumn();
            $cache[$clave] = $valor === false ? '' : trim((string) $valor);
        }

        return $cache[$clave] !== '' ? $cache[$clave] : $porDefecto;
    }

    private static function direccion(string $nombre, string $email): string
    {
        $email = trim(str_replace(["\r", "\n"], '', $email));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $email = 'no-reply@jeremy-tech.local';
        }

        $nombre = trim(str_replace(["\r", "\n"], '', $nombre));
        if ($nombre === '') {
            return $email;
        }

        return mb_encode_mimeheader($nombre, 'UTF-8', 'B') . ' <' . $email . '>';
    }
}
