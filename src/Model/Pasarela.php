<?php

declare(strict_types=1);

namespace Model;

/**
 * Conciliación de pagos con una pasarela externa mediante webhook.
 *
 * El checkout de la tienda sigue siendo simulado por defecto. Cuando se
 * configura `PAGOS_WEBHOOK_SECRET`, este modelo acepta eventos firmados de
 * una pasarela real y actualiza `pagos` / `pedidos` de forma atómica:
 *
 *   - firma HMAC-SHA256 sobre `{timestamp}.{cuerpo_crudo}`;
 *   - tolerancia de 5 minutos contra replay;
 *   - idempotencia por `(pasarela, evento_id)` en `pagos_webhooks`;
 *   - el pago se localiza por `pago_id`, `pedido_id` o referencia externa;
 *   - un pago aprobado confirma el pedido; uno rechazado lo cancela si aún
 *     estaba pendiente; un reembolso devuelve stock y registra el hito.
 *
 * El contrato es agnóstico al proveedor: cualquier pasarela que pueda enviar
 * un POST JSON firmado puede conectarse cambiando `PAGOS_PROVEEDOR` y el
 * secreto compartido. Para Culqi/Mercado Pago/Stripe se puede añadir un
 * adaptador que traduzca su firma a este mismo formato.
 */
final class Pasarela
{
    /** Tolerancia máxima entre el timestamp del evento y el servidor. */
    private const TOLERANCIA_SEGUNDOS = 300;

    /** Estados internos que entiende la tienda. */
    public const PAGADO = 'Pagado';
    public const RECHAZADO = 'Rechazado';
    public const REEMBOLSADO = 'Reembolsado';
    public const PENDIENTE = 'Pendiente';

    /** URL pública que la pasarela debe llamar. */
    public static function webhookUrl(): string
    {
        return APP_URL . '/index.php?ruta=pagos/webhook';
    }

    public static function secreto(): string
    {
        return trim(env('PAGOS_WEBHOOK_SECRET'));
    }

    public static function configurada(): bool
    {
        return self::secreto() !== '';
    }

    public static function modo(): string
    {
        return trim(env('PAGOS_MODO', 'simulado'));
    }

    public static function proveedor(): string
    {
        return trim(env('PAGOS_PROVEEDOR', 'externa'));
    }

    public static function moneda(): string
    {
        return trim(env('PAGOS_MONEDA', 'PEN'));
    }

    /** Firma un cuerpo para pruebas o para un adaptador de proveedor. */
    public static function firmar(string $cuerpo, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp . '.' . $cuerpo, self::secreto());
    }

    /**
     * Procesa un webhook ya recibido por el controlador.
     *
     * @param array<string, string> $headers Cabeceras normalizadas en minúsculas.
     * @return array{ok: bool, codigo: int, mensaje: string}
     */
    public static function procesarWebhook(string $cuerpo, array $headers): array
    {
        if (!self::configurada()) {
            return [
                'ok' => false,
                'codigo' => 503,
                'mensaje' => 'La pasarela no está configurada (falta PAGOS_WEBHOOK_SECRET).',
            ];
        }

        $timestamp = trim((string) ($headers['x-jt-timestamp'] ?? ''));
        $firma = trim((string) ($headers['x-jt-signature'] ?? ''));

        if (!preg_match('/^\d{10}$/', $timestamp) || $firma === '') {
            return ['ok' => false, 'codigo' => 401, 'mensaje' => 'Faltan las cabeceras de firma.'];
        }
        if (abs(time() - (int) $timestamp) > self::TOLERANCIA_SEGUNDOS) {
            return ['ok' => false, 'codigo' => 401, 'mensaje' => 'El evento está fuera de la ventana de tolerancia.'];
        }
        if (!hash_equals(self::firmar($cuerpo, (int) $timestamp), $firma)) {
            return ['ok' => false, 'codigo' => 401, 'mensaje' => 'Firma del webhook inválida.'];
        }

        $evento = json_decode($cuerpo, true);
        if (!is_array($evento)) {
            return ['ok' => false, 'codigo' => 400, 'mensaje' => 'El cuerpo del webhook no es JSON válido.'];
        }

        $eventoId = trim((string) ($evento['id'] ?? ($headers['x-jt-event-id'] ?? '')));
        $tipo = trim((string) ($evento['type'] ?? 'payment.updated'));
        $datos = is_array($evento['data'] ?? null) ? $evento['data'] : [];

        if ($eventoId === '' || mb_strlen($eventoId) > 120) {
            return ['ok' => false, 'codigo' => 422, 'mensaje' => 'El evento no trae un id válido.'];
        }

        $estado = self::normalizarEstado((string) ($datos['estado'] ?? $datos['status'] ?? ''));
        if ($estado === null) {
            return ['ok' => false, 'codigo' => 422, 'mensaje' => 'Estado de pago no reconocido.'];
        }

        $pagoId = (int) ($datos['pago_id'] ?? 0);
        $pedidoId = (int) ($datos['pedido_id'] ?? 0);
        $referencia = trim((string) ($datos['referencia'] ?? $datos['reference'] ?? ''));
        $metodo = trim((string) ($datos['metodo'] ?? $datos['method'] ?? ''));
        $pasarela = mb_substr(
            trim((string) ($headers['x-jt-gateway'] ?? self::proveedor())),
            0,
            40
        );

        $pdo = conexion();
        $pdo->beginTransaction();

        try {
            // Idempotencia: la UNIQUE (pasarela, evento_id) evita aplicar dos
            // veces el mismo evento si el proveedor reintenta.
            $pdo->prepare(
                'INSERT INTO pagos_webhooks
                    (pasarela, evento_id, tipo, estado, payload, resultado)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $pasarela,
                $eventoId,
                mb_substr($tipo, 0, 60),
                mb_substr($estado, 0, 30),
                substr($cuerpo, 0, 65000),
                'procesando',
            ]);
        } catch (\PDOException $e) {
            $pdo->rollBack();
            if (($e->errorInfo[1] ?? 0) === 1062) {
                return ['ok' => true, 'codigo' => 200, 'mensaje' => 'Evento ya procesado (idempotencia).'];
            }
            error_log('No se pudo registrar el webhook: ' . $e->getMessage());
            return ['ok' => false, 'codigo' => 500, 'mensaje' => 'No se pudo registrar el evento.'];
        }

        try {
            $pago = self::buscarPago($pdo, $pagoId, $pedidoId, $referencia, true);
            if ($pago === null) {
                throw new \RuntimeException('No se encontró el pago para este evento.', 404);
            }

            $pagoIdReal = (int) $pago['id'];
            $pedidoIdReal = (int) $pago['pedido_id'];
            $estadoPagoActual = (string) $pago['estado'];

            $sqlPago = 'UPDATE pagos
                           SET estado = ?, pasarela = ?, actualizado_en = NOW()';
            $paramsPago = [$estado, $pasarela];

            if ($referencia !== '') {
                $sqlPago .= ', referencia_externa = ?';
                $paramsPago[] = mb_substr($referencia, 0, 120);
            }
            if ($metodo !== '') {
                $sqlPago .= ', metodo = ?';
                $paramsPago[] = mb_substr($metodo, 0, 40);
            }
            $sqlPago .= ' WHERE id = ?';
            $paramsPago[] = $pagoIdReal;
            $pdo->prepare($sqlPago)->execute($paramsPago);

            $stmtPedido = $pdo->prepare('SELECT estado FROM pedidos WHERE id = ? FOR UPDATE');
            $stmtPedido->execute([$pedidoIdReal]);
            $pedido = $stmtPedido->fetch();
            if (!$pedido) {
                throw new \RuntimeException('El pedido del pago ya no existe.', 404);
            }

            $estadoPedido = (string) $pedido['estado'];
            $resultado = 'Pago ' . $estado . ' conciliado.';
            $confirmarCorreo = false;
            $reembolsoCorreo = false;

            if ($estado === self::PAGADO) {
                if ($estadoPedido === Pedido::ESTADOS[0]) { // Pendiente
                    $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')
                        ->execute([self::PAGADO, $pedidoIdReal]);
                    PedidoEvento::registrar(
                        $pdo,
                        $pedidoIdReal,
                        PedidoEvento::PAGO_CONFIRMADO,
                        self::PAGADO,
                        'Pago confirmado por webhook de la pasarela.'
                    );
                    $resultado = 'Pago aprobado: pedido confirmado.';
                    $confirmarCorreo = true;
                } elseif ($estadoPedido === Pedido::CANCELADO) {
                    $resultado = 'Pago aprobado sobre un pedido cancelado; requiere reembolso manual.';
                } else {
                    $resultado = 'Pago aprobado: el pedido ya estaba en "' . $estadoPedido . '".';
                }
            } elseif ($estado === self::RECHAZADO) {
                if ($estadoPedido === Pedido::ESTADOS[0]) {
                    $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')
                        ->execute([Pedido::CANCELADO, $pedidoIdReal]);
                    PedidoEvento::registrar(
                        $pdo,
                        $pedidoIdReal,
                        PedidoEvento::CANCELADO,
                        Pedido::CANCELADO,
                        'Pago rechazado por la pasarela.'
                    );
                    Inventario::devolver($pdo, $pedidoIdReal);
                    $resultado = 'Pago rechazado: pedido cancelado y stock devuelto.';
                } else {
                    $resultado = 'Pago rechazado, pero el pedido ya estaba en "' . $estadoPedido . '"; no se canceló.';
                }
            } elseif ($estado === self::REEMBOLSADO) {
                if ($estadoPagoActual === self::REEMBOLSADO) {
                    $resultado = 'El pago ya estaba reembolsado (sin cambios).';
                } else {
                    if ($estadoPedido !== Pedido::CANCELADO) {
                        $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')
                            ->execute([Pedido::CANCELADO, $pedidoIdReal]);
                    }
                    PedidoEvento::registrar(
                        $pdo,
                        $pedidoIdReal,
                        PedidoEvento::REEMBOLSADO,
                        Pedido::CANCELADO,
                        'Reembolso confirmado por webhook de la pasarela.'
                    );
                    Inventario::devolver($pdo, $pedidoIdReal);
                    $resultado = 'Reembolso confirmado: pedido cancelado y stock devuelto.';
                    $reembolsoCorreo = true;
                }
            } else {
                $resultado = 'Pago pendiente: se mantiene el estado del pedido.';
            }

            $pdo->prepare(
                'UPDATE pagos_webhooks
                    SET pedido_id = ?, pago_id = ?, resultado = ?
                  WHERE pasarela = ? AND evento_id = ?'
            )->execute([
                $pedidoIdReal,
                $pagoIdReal,
                mb_substr($resultado, 0, 255),
                $pasarela,
                $eventoId,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $codigo = (int) $e->getCode();
            if ($codigo < 400 || $codigo > 599) {
                $codigo = 500;
            }
            error_log('Webhook de pago rechazado: ' . $e->getMessage());

            // El evento no se aplicó: se borra para que el proveedor pueda
            // reintentarlo con el mismo id.
            try {
                $pdo->prepare('DELETE FROM pagos_webhooks WHERE pasarela = ? AND evento_id = ?')
                    ->execute([$pasarela, $eventoId]);
            } catch (\Throwable $limpieza) {
                error_log('No se pudo limpiar el webhook fallido: ' . $limpieza->getMessage());
            }

            return [
                'ok' => false,
                'codigo' => $codigo,
                'mensaje' => $codigo === 404
                    ? 'No se encontró el pago o pedido del evento.'
                    : 'No se pudo procesar el evento.',
            ];
        }

        // Los correos simulados se generan después del commit: si algo falla,
        // no se anuncia un cambio que no se guardó.
        if ($confirmarCorreo) {
            try {
                Notificacion::pagoConfirmado($pedidoIdReal);
            } catch (\Throwable $e) {
                error_log('Correo de pago (webhook) del pedido #' . $pedidoIdReal . ': ' . $e->getMessage());
            }
        }
        if ($reembolsoCorreo) {
            try {
                Notificacion::reembolso($pedidoIdReal);
            } catch (\Throwable $e) {
                error_log('Correo de reembolso (webhook) del pedido #' . $pedidoIdReal . ': ' . $e->getMessage());
            }
        }

        return ['ok' => true, 'codigo' => 200, 'mensaje' => $resultado];
    }

    /**
     * Pagos recientes con el pedido y el cliente, para el panel.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pagos(int $limite = 200): array
    {
        $limite = max(1, min(500, $limite));
        return conexion()->query(
            'SELECT pa.*, p.estado AS pedido_estado, p.total_pen,
                    u.name AS cliente_nombre, u.apellidos AS cliente_apellidos,
                    u.email AS cliente_email
               FROM pagos pa
          LEFT JOIN pedidos p ON p.id = pa.pedido_id
          LEFT JOIN users u ON u.id = p.cliente_id
           ORDER BY pa.id DESC
              LIMIT ' . $limite
        )->fetchAll();
    }

    /**
     * Bitácora de webhooks recibidos.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function eventos(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));
        return conexion()->query(
            'SELECT * FROM pagos_webhooks ORDER BY id DESC LIMIT ' . $limite
        )->fetchAll();
    }

    /**
     * @return array{total: int, pagados: int, pendientes: int, reembolsados: int, webhooks: int}
     */
    public static function resumen(): array
    {
        $fila = conexion()->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'Pagado' THEN 1 ELSE 0 END) AS pagados,
                    SUM(CASE WHEN estado IN ('Pendiente', 'En verificación') THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'Reembolsado' THEN 1 ELSE 0 END) AS reembolsados
               FROM pagos"
        )->fetch() ?: [];

        return [
            'total'        => (int) ($fila['total'] ?? 0),
            'pagados'      => (int) ($fila['pagados'] ?? 0),
            'pendientes'   => (int) ($fila['pendientes'] ?? 0),
            'reembolsados' => (int) ($fila['reembolsados'] ?? 0),
            'webhooks'     => (int) conexion()->query('SELECT COUNT(*) FROM pagos_webhooks')->fetchColumn(),
        ];
    }

    /**
     * Busca el pago del evento. `FOR UPDATE` se aplica dentro de la
     * transacción del webhook.
     *
     * @return array<string, mixed>|null
     */
    private static function buscarPago(
        \PDO $pdo,
        int $pagoId,
        int $pedidoId,
        string $referencia,
        bool $bloquear
    ): ?array {
        $sufijo = $bloquear ? ' FOR UPDATE' : '';

        if ($pagoId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM pagos WHERE id = ?' . $sufijo);
            $stmt->execute([$pagoId]);
            return $stmt->fetch() ?: null;
        }

        if ($pedidoId > 0) {
            $stmt = $pdo->prepare(
                'SELECT * FROM pagos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1' . $sufijo
            );
            $stmt->execute([$pedidoId]);
            return $stmt->fetch() ?: null;
        }

        if ($referencia !== '') {
            $stmt = $pdo->prepare(
                'SELECT * FROM pagos
                  WHERE transaccion_id = ? OR referencia_externa = ?
               ORDER BY id DESC LIMIT 1' . $sufijo
            );
            $stmt->execute([$referencia, $referencia]);
            return $stmt->fetch() ?: null;
        }

        return null;
    }

    /** Normaliza los estados que suelen enviar las pasarelas. */
    private static function normalizarEstado(string $estado): ?string
    {
        $estado = mb_strtolower(trim($estado));

        return match ($estado) {
            'pagado', 'paid', 'approved', 'succeeded', 'success', 'completed', 'confirmado'
                => self::PAGADO,
            'rechazado', 'rejected', 'failed', 'declined', 'canceled', 'cancelled', 'denied'
                => self::RECHAZADO,
            'reembolsado', 'refunded', 'refund'
                => self::REEMBOLSADO,
            'pendiente', 'pending', 'in_process', 'processing', 'en_verificacion', 'in_verification'
                => self::PENDIENTE,
            default => null,
        };
    }
}
