<?php
declare(strict_types=1);

namespace Model;

/**
 * Límite de intentos persistente (rate limiting) para login y recuperación.
 *
 * La app no tenía ningún control de fuerza bruta: un atacante podía probar
 * contraseñas o solicitar enlaces de recuperación sin fin. Este modelo guarda
 * los intentos en `intentos_acceso`, una fila por ámbito + identificador
 * (correo o usuario, normalizado). Tras superar `MAX_INTENTOS` dentro de la
 * ventana, el identificador queda bloqueado durante `BLOQUEO_SEGUNDOS`.
 *
 * Se usa la base de datos (y no la sesión) para que el límite sobreviva a
 * cookies nuevas y sea compartido por todos los procesos PHP.
 */
final class LimiteIntentos
{
    /** Intentos permitidos antes de bloquear. */
    private const MAX_INTENTOS = 5;

    /** Ventana en la que se cuentan los intentos (15 minutos). */
    private const VENTANA_SEGUNDOS = 900;

    /** Duración del bloqueo una vez superado el máximo (15 minutos). */
    private const BLOQUEO_SEGUNDOS = 900;

    /**
     * Segundos que faltan para que el identificador vuelva a estar disponible.
     * Devuelve 0 si no está bloqueado.
     */
    public static function restante(string $ambito, string $clave): int
    {
        $clave = self::normalizar($clave);
        if ($clave === '') {
            return 0;
        }

        $stmt = conexion()->prepare(
            'SELECT bloqueado_hasta FROM intentos_acceso WHERE ambito = ? AND clave = ? LIMIT 1'
        );
        $stmt->execute([$ambito, $clave]);
        $bloqueado = $stmt->fetchColumn();

        if ($bloqueado === false || $bloqueado === null || $bloqueado === '') {
            return 0;
        }

        $restante = strtotime((string) $bloqueado) - time();
        return $restante > 0 ? $restante : 0;
    }

    /**
     * Registra un intento fallido (o una solicitud) y bloquea si corresponde.
     * La operación es atómica: un INSERT ... ON DUPLICATE KEY UPDATE evita
     * que dos peticiones simultáneas pierdan la cuenta.
     */
    public static function registrar(
        string $ambito,
        string $clave,
        string $ip = '',
        int $max = self::MAX_INTENTOS,
        int $ventana = self::VENTANA_SEGUNDOS,
        int $bloqueo = self::BLOQUEO_SEGUNDOS
    ): void {
        $clave = self::normalizar($clave);
        if ($clave === '') {
            return;
        }

        $sql = 'INSERT INTO intentos_acceso (ambito, clave, ip, intentos, bloqueado_hasta, ultimo_intento)
                VALUES (?, ?, ?, 1, NULL, NOW())
                ON DUPLICATE KEY UPDATE
                    intentos = IF(
                        ultimo_intento < (NOW() - INTERVAL ' . $ventana . ' SECOND)
                        OR (bloqueado_hasta IS NOT NULL AND bloqueado_hasta <= NOW()),
                        1,
                        intentos + 1
                    ),
                    bloqueado_hasta = IF(
                        intentos >= ' . $max . ',
                        DATE_ADD(NOW(), INTERVAL ' . $bloqueo . ' SECOND),
                        NULL
                    ),
                    ultimo_intento = NOW()';

        conexion()->prepare($sql)->execute([$ambito, $clave, mb_substr($ip, 0, 45)]);
    }

    /** Borra el contador tras un acceso correcto. */
    public static function limpiar(string $ambito, string $clave): void
    {
        $clave = self::normalizar($clave);
        if ($clave === '') {
            return;
        }

        conexion()->prepare('DELETE FROM intentos_acceso WHERE ambito = ? AND clave = ?')
            ->execute([$ambito, $clave]);
    }

    /** Mensaje homogéneo para las vistas cuando aún hay bloqueo. */
    public static function mensajeBloqueo(int $segundos): string
    {
        $minutos = max(1, (int) ceil($segundos / 60));
        return 'Demasiados intentos. Espera ' . $minutos . ' minuto'
            . ($minutos === 1 ? '' : 's') . ' antes de volver a intentarlo.';
    }

    /** IP del cliente (solo REMOTE_ADDR; no se confía en cabeceras reenviadas). */
    public static function ipActual(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '';
    }

    private static function normalizar(string $clave): string
    {
        return mb_strtolower(trim($clave));
    }
}
