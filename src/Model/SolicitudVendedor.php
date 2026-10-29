<?php

declare(strict_types=1);

namespace Model;

/**
 * Solicitudes de rol de vendedor (Jeremy Tech).
 *
 * Toda cuenta se registra como cliente. Para publicar en el marketplace hay
 * que pedir el rol desde el perfil; el administrador aprueba o rechaza en
 * `?ruta=admin/solicitudes`. Aprobar solo cambia `users.rol`: el resto de la
 * ficha (tipo B2C/B2B/C2C, RUC, empresa, dirección, claves) se conserva.
 */
final class SolicitudVendedor
{
    public const PENDIENTE = 'pendiente';
    public const APROBADA = 'aprobada';
    public const RECHAZADA = 'rechazada';

    /** @var array<int, string> */
    public const ESTADOS = [self::PENDIENTE, self::APROBADA, self::RECHAZADA];

    public const MENSAJE_MAX = 500;
    public const MOTIVO_MAX = 500;

    public static function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            self::APROBADA => 'Aprobada',
            self::RECHAZADA => 'Rechazada',
            default => 'Pendiente',
        };
    }

    /**
     * Crea la solicitud si la cuenta no puede publicar todavía y no tiene
     * ya una pendiente.
     *
     * @return array{ok: bool, error?: string, id?: int}
     */
    public static function crear(int $usuarioId, string $mensaje): array
    {
        if ($usuarioId <= 0) {
            return ['ok' => false, 'error' => 'Inicia sesión para solicitar el rol de vendedor.'];
        }

        $mensaje = trim($mensaje);
        if (mb_strlen($mensaje) > self::MENSAJE_MAX) {
            return ['ok' => false, 'error' => 'El mensaje no puede superar los ' . self::MENSAJE_MAX . ' caracteres.'];
        }
        if (self::pendienteDe($usuarioId) !== null) {
            return ['ok' => false, 'error' => 'Ya tienes una solicitud pendiente. El administrador la revisará pronto.'];
        }

        $stmt = conexion()->prepare(
            'INSERT INTO solicitudes_vendedor (usuario_id, tipo, mensaje)
             SELECT id, tipo, ? FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$mensaje, $usuarioId]);
        if ($stmt->rowCount() === 0) {
            return ['ok' => false, 'error' => 'No encontramos tu cuenta. Vuelve a iniciar sesión.'];
        }

        return ['ok' => true, 'id' => (int) conexion()->lastInsertId()];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function pendienteDe(int $usuarioId): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT * FROM solicitudes_vendedor
              WHERE usuario_id = ? AND estado = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$usuarioId, self::PENDIENTE]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Historial de solicitudes de una cuenta (la más reciente primero).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deUsuario(int $usuarioId, int $limite = 5): array
    {
        $stmt = conexion()->prepare(
            'SELECT * FROM solicitudes_vendedor
              WHERE usuario_id = ? ORDER BY id DESC LIMIT ' . max(1, $limite)
        );
        $stmt->execute([$usuarioId]);

        return $stmt->fetchAll();
    }

    /**
     * Cola de solicitudes para el panel, con la ficha del solicitante.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listar(string $estado = '', int $limite = 200): array
    {
        $sql = 'SELECT s.*, u.name, u.apellidos, u.email, u.telefono, u.empresa, u.ruc,
                       u.plataforma_reventa, u.tipo AS tipo_cuenta, u.rol
                  FROM solicitudes_vendedor s
                  JOIN users u ON u.id = s.usuario_id
                 WHERE u.deleted_at IS NULL';
        $parametros = [];
        if (in_array($estado, self::ESTADOS, true)) {
            $sql .= ' AND s.estado = ?';
            $parametros[] = $estado;
        }
        // Las pendientes primero: es la cola de trabajo del administrador.
        $sql .= " ORDER BY (s.estado = 'pendiente') DESC, s.id DESC LIMIT " . max(1, $limite);

        $stmt = conexion()->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * @return array{total: int, pendientes: int, aprobadas: int, rechazadas: int}
     */
    public static function resumen(): array
    {
        $filas = conexion()->query(
            'SELECT estado, COUNT(*) AS total FROM solicitudes_vendedor GROUP BY estado'
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        return [
            'total'      => array_sum(array_map('intval', $filas)),
            'pendientes' => (int) ($filas[self::PENDIENTE] ?? 0),
            'aprobadas'  => (int) ($filas[self::APROBADA] ?? 0),
            'rechazadas' => (int) ($filas[self::RECHAZADA] ?? 0),
        ];
    }

    /** Número de solicitudes pendientes (badge del panel). */
    public static function pendientesCount(): int
    {
        return (int) conexion()
            ->query("SELECT COUNT(*) FROM solicitudes_vendedor WHERE estado = 'pendiente'")
            ->fetchColumn();
    }

    /**
     * Aprueba la solicitud y concede el rol. Solo toca `rol`.
     *
     * @return string|null null si todo salió bien, o el error.
     */
    public static function aprobar(int $id, string $admin): ?string
    {
        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT id, usuario_id, estado FROM solicitudes_vendedor WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$id]);
            $solicitud = $stmt->fetch();
            if (!$solicitud) {
                $pdo->rollBack();
                return 'La solicitud ya no existe.';
            }
            if ((string) $solicitud['estado'] !== self::PENDIENTE) {
                $pdo->rollBack();
                return 'Esa solicitud ya fue revisada.';
            }

            $usuarioId = (int) $solicitud['usuario_id'];
            $pdo->prepare("UPDATE users SET rol = 'vendedor' WHERE id = ?")->execute([$usuarioId]);
            $pdo->prepare(
                "UPDATE solicitudes_vendedor
                    SET estado = 'aprobada', resuelto_por = ?, resuelto_en = NOW()
                  WHERE id = ?"
            )->execute([$admin, $id]);

            $pdo->commit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return 'No se pudo aprobar la solicitud. Inténtalo de nuevo.';
        }

        return null;
    }

    /**
     * @return string|null null si todo salió bien, o el error.
     */
    public static function rechazar(int $id, string $admin, string $motivo): ?string
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) > self::MOTIVO_MAX) {
            return 'El motivo no puede superar los ' . self::MOTIVO_MAX . ' caracteres.';
        }

        $stmt = conexion()->prepare(
            "UPDATE solicitudes_vendedor
                SET estado = 'rechazada', motivo_rechazo = ?, resuelto_por = ?, resuelto_en = NOW()
              WHERE id = ? AND estado = 'pendiente'"
        );
        $stmt->execute([$motivo, $admin, $id]);

        return $stmt->rowCount() === 0 ? 'Esa solicitud ya fue revisada.' : null;
    }
}
