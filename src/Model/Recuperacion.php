<?php

declare(strict_types=1);

namespace Model;

/**
 * Recuperación de contraseña mediante token de un solo uso.
 *
 * Usa la tabla `tokens_recuperacion` (hasta ahora sin uso):
 *   - el token que viaja por correo se guarda HASHEADO (SHA-256), nunca en claro;
 *   - vence a los 30 minutos;
 *   - es de un solo uso y, al emitir uno nuevo, se invalidan los anteriores;
 *   - distingue `cliente` (acceso por correo) de `admin` (acceso por usuario).
 *
 * El correo se materializa con `CorreoSimulado` y se registra en
 * `notificaciones` (pedido_id NULL) para que el panel pueda auditar el envío.
 */
final class Recuperacion
{
    /** Minutos de validez del enlace. */
    private const MINUTOS_VALIDEZ = 30;

    /** Tipos admitidos por el ENUM de `tokens_recuperacion`. */
    public const CLIENTE = 'cliente';
    public const ADMIN = 'admin';

    /**
     * Emite un token para una cuenta de la tienda (por correo).
     * Devuelve true si la cuenta existe, está activa y se intentó el envío.
     * El llamador debe responder siempre lo mismo para no revelar si el
     * correo está registrado.
     */
    public static function solicitarCliente(string $email): bool
    {
        $usuario = Cuenta::porEmail($email);
        if ($usuario === null || (int) ($usuario['activo'] ?? 0) !== 1) {
            return false;
        }

        return self::emitir(self::CLIENTE, $usuario);
    }

    /**
     * Emite un token para una cuenta del panel (por usuario).
     */
    public static function solicitarAdmin(string $usuario): bool
    {
        $cuenta = Administrador::porUsuario($usuario);
        if ($cuenta === null || (int) ($cuenta['activo'] ?? 0) !== 1) {
            return false;
        }

        return self::emitir(self::ADMIN, $cuenta);
    }

    /**
     * ¿El token existe, pertenece al tipo indicado y sigue vigente?
     */
    public static function tokenValido(string $tipo, string $token): bool
    {
        return self::filaPorToken($tipo, $token) !== null;
    }

    /**
     * Cambia la contraseña usando un token válido y lo consume.
     *
     * @return string|null null si se actualizó, o el mensaje de error.
     */
    public static function restablecer(
        string $tipo,
        string $token,
        string $nueva,
        string $confirmar
    ): ?string {
        if (strlen($nueva) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($nueva !== $confirmar) {
            return 'Las contraseñas no coinciden';
        }

        $fila = self::filaPorToken($tipo, $token);
        if ($fila === null) {
            return 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.';
        }

        $pdo = conexion();
        $pdo->beginTransaction();

        try {
            // Reclamo atómico del token: si dos peticiones llegan a la vez,
            // solo una consigue marcarlo como usado.
            $reclamar = $pdo->prepare(
                'UPDATE tokens_recuperacion SET usado = 1 WHERE id = ? AND usado = 0'
            );
            $reclamar->execute([(int) $fila['id']]);

            if ($reclamar->rowCount() !== 1) {
                $pdo->rollBack();
                return 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.';
            }

            $actualizar = $pdo->prepare(
                'UPDATE users SET password = ?
                  WHERE id = ? AND activo = 1 AND deleted_at IS NULL'
            );
            $actualizar->execute([password_hash($nueva, PASSWORD_DEFAULT), (int) $fila['usuario_id']]);

            if ($actualizar->rowCount() !== 1) {
                $pdo->rollBack();
                return 'No se pudo actualizar la contraseña. Inténtalo de nuevo.';
            }

            // Un solo uso: se anulan todos los tokens pendientes de la cuenta,
            // sin importar el flujo con el que se emitieron.
            $pdo->prepare(
                'UPDATE tokens_recuperacion SET usado = 1
                  WHERE usuario_id = ? AND usado = 0'
            )->execute([(int) $fila['usuario_id']]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('No se pudo restablecer la contraseña: ' . $e->getMessage());
            return 'No se pudo actualizar la contraseña. Inténtalo de nuevo.';
        }

        return null;
    }

    /**
     * Crea el token, invalida los anteriores y envía el correo simulado.
     *
     * @param array<string, mixed> $usuario
     */
    private static function emitir(string $tipo, array $usuario): bool
    {
        $usuarioId = (int) ($usuario['id'] ?? 0);
        if ($usuarioId <= 0) {
            return false;
        }

        $token = bin2hex(random_bytes(32)); // 64 caracteres hex
        $expira = date('Y-m-d H:i:s', time() + self::MINUTOS_VALIDEZ * 60);
        $pdo = conexion();
        $pdo->beginTransaction();

        try {
            $pdo->prepare(
                'UPDATE tokens_recuperacion SET usado = 1
                  WHERE usuario_id = ? AND usado = 0'
            )->execute([$usuarioId]);

            $pdo->prepare(
                'INSERT INTO tokens_recuperacion (tipo, usuario_id, token, expira_en, usado)
                 VALUES (?, ?, ?, ?, 0)'
            )->execute([$tipo, $usuarioId, hash('sha256', $token), $expira]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('No se pudo crear el token de recuperación: ' . $e->getMessage());
            return false;
        }

        self::enviarCorreo($tipo, $usuario, $token);
        return true;
    }

    /**
     * @param array<string, mixed> $usuario
     */
    private static function enviarCorreo(string $tipo, array $usuario, string $token): void
    {
        $email = trim((string) ($usuario['email'] ?? ''));
        $nombre = trim((string) ($usuario['name'] ?? '') . ' ' . (string) ($usuario['apellidos'] ?? ''));
        $esAdmin = $tipo === self::ADMIN;
        $ruta = $esAdmin ? 'admin/recuperar/restablecer' : 'login/restablecer';
        $enlace = APP_URL . '/index.php?ruta=' . $ruta . '&token=' . urlencode($token);
        $asunto = 'Recuperación de contraseña — Jeremy Tech';

        $h = static fn ($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
        $saludo = $nombre !== '' ? $nombre : ($esAdmin ? 'usuario' : 'cliente');

        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $h($asunto) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">'
            . '<tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<tr><td style="background:#0a0f1a;padding:22px 26px;color:#ffffff;">'
            . '<div style="font-size:20px;font-weight:700;letter-spacing:1px;color:#00d4ff;">JEREMY-TECH</div>'
            . '<div style="font-size:13px;color:#9ca3af;margin-top:4px;">Tecnología y accesorios</div>'
            . '</td></tr>'
            . '<tr><td style="padding:26px;">'
            . '<h1 style="margin:0 0 8px;font-size:24px;color:#111827;">Recupera tu contraseña</h1>'
            . '<p style="margin:0 0 18px;color:#4b5563;line-height:1.6;">Hola ' . $h($saludo)
            . '. Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 18px;"><tr><td style="background:#00d4ff;border-radius:8px;">'
            . '<a href="' . $h($enlace) . '" style="display:inline-block;padding:12px 22px;color:#0a0f1a;text-decoration:none;font-weight:700;font-size:14px;">Elegir nueva contraseña</a>'
            . '</td></tr></table>'
            . '<p style="margin:0 0 10px;color:#4b5563;line-height:1.6;">El enlace vence en '
            . self::MINUTOS_VALIDEZ . ' minutos y solo puede usarse una vez.</p>'
            . '<p style="margin:0;color:#6b7280;font-size:12px;line-height:1.6;">Si no solicitaste este cambio, ignora este mensaje: tu contraseña actual seguirá funcionando.</p>'
            . '</td></tr>'
            . '<tr><td style="background:#f9fafb;padding:16px 26px;color:#6b7280;font-size:12px;text-align:center;">'
            . 'Jeremy-Tech · Este mensaje fue generado en modo de demostración.'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';

        $texto = "JEREMY-TECH\n"
            . "Recuperación de contraseña\n\n"
            . "Hola {$saludo}.\n"
            . "Recibimos una solicitud para restablecer la contraseña de tu cuenta.\n\n"
            . "Abre este enlace para elegir una nueva contraseña:\n{$enlace}\n\n"
            . 'El enlace vence en ' . self::MINUTOS_VALIDEZ . " minutos y solo puede usarse una vez.\n"
            . "Si no solicitaste este cambio, ignora este mensaje.\n\n"
            . "Este mensaje fue generado en modo de demostración. No se envió por correo.\n";

        $archivo = '';
        $error = '';

        try {
            $archivo = CorreoSimulado::guardar(
                'recuperacion_' . $tipo,
                0,
                $email,
                $asunto,
                $html,
                $texto
            );
        } catch (\Throwable $e) {
            $error = mb_substr($e->getMessage(), 0, 255);
            error_log('Correo de recuperación (' . $tipo . '): ' . $error);
        }

        try {
            conexion()->prepare(
                'INSERT INTO notificaciones
                    (cliente_id, pedido_id, destinatario, tipo, asunto, cuerpo, archivo, error, enviado, enviado_en)
                 VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 0, NULL)'
            )->execute([
                (int) ($usuario['id'] ?? 0),
                mb_substr($email, 0, 190),
                'recuperacion_' . $tipo,
                mb_substr($asunto, 0, 180),
                // Resumen sin el token: el enlace completo solo vive en el
                // correo simulado y el token en la BD está hasheado.
                'Solicitud de recuperación de contraseña. El enlace vence en '
                    . self::MINUTOS_VALIDEZ . ' minutos y solo puede usarse una vez.',
                $archivo,
                $error,
            ]);
        } catch (\Throwable $e) {
            error_log('No se pudo registrar la notificación de recuperación: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function filaPorToken(string $tipo, string $token): ?array
    {
        $token = trim($token);
        if (!in_array($tipo, [self::CLIENTE, self::ADMIN], true)
            || preg_match('/^[a-f0-9]{64}$/', $token) !== 1
        ) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT t.id, t.usuario_id, u.name, u.apellidos, u.email, u.usuario, u.rol
               FROM tokens_recuperacion t
               JOIN users u ON u.id = t.usuario_id
              WHERE t.tipo = ? AND t.token = ? AND t.usado = 0
                AND t.expira_en > NOW()
                AND u.activo = 1 AND u.deleted_at IS NULL
              LIMIT 1'
        );
        $stmt->execute([$tipo, hash('sha256', $token)]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }
}
