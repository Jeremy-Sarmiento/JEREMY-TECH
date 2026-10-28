<?php
declare(strict_types=1);

namespace Model;

/**
 * Eventos del ciclo de vida de un pedido.
 *
 * `pedidos.estado` guarda el estado ACTUAL; esta tabla guarda CUÁNDO ocurrió
 * cada hito. Así el cliente puede ver una línea de tiempo real en Mis Pedidos
 * sin depender de fechas inferidas ni de la auditoría del panel.
 *
 * Los eventos se escriben dentro de la misma transacción que cambia el
 * pedido. Si la transacción falla, no queda un evento huérfano.
 */
final class PedidoEvento
{
    public const CREADO = 'pedido_creado';
    public const PAGO_CONFIRMADO = 'pago_confirmado';
    public const ENVIADO = 'pedido_enviado';
    public const ENTREGADO = 'pedido_entregado';
    public const CANCELADO = 'pedido_cancelado';
    public const REEMBOLSADO = 'pedido_reembolsado';

    /** @var array<int, string> */
    private const VALIDOS = [
        self::CREADO,
        self::PAGO_CONFIRMADO,
        self::ENVIADO,
        self::ENTREGADO,
        self::CANCELADO,
        self::REEMBOLSADO,
    ];

    /**
     * Registra un evento. Debe llamarse con la conexión que ya tiene abierta
     * la transacción del pedido.
     */
    public static function registrar(
        \PDO $pdo,
        int $pedidoId,
        string $evento,
        string $estado,
        string $detalle = ''
    ): void {
        if ($pedidoId <= 0 || !in_array($evento, self::VALIDOS, true)) {
            throw new \InvalidArgumentException('Evento de pedido no válido.');
        }

        $pdo->prepare(
            'INSERT INTO pedido_eventos (pedido_id, evento, estado, detalle)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $pedidoId,
            $evento,
            mb_substr($estado, 0, 20),
            mb_substr(trim($detalle), 0, 255),
        ]);
    }

    /**
     * Eventos de varios pedidos, agrupados por pedido. Evita una consulta por
     * cada tarjeta del historial.
     *
     * @param array<int, int|string> $pedidoIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function paraPedidos(array $pedidoIds): array
    {
        $ids = [];
        foreach ($pedidoIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $ids = array_values($ids);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = conexion()->prepare(
            "SELECT id, pedido_id, evento, estado, detalle, creado_en
               FROM pedido_eventos
              WHERE pedido_id IN ($marcas)
              ORDER BY creado_en, id"
        );
        $stmt->execute($ids);

        $porPedido = [];
        foreach ($stmt->fetchAll() as $evento) {
            $porPedido[(int) $evento['pedido_id']][] = $evento;
        }

        return $porPedido;
    }

    /**
     * Construye los pasos visibles en Mis Pedidos.
     *
     * Los pasos no dependen solo de los eventos: un pedido pagado que todavía
     * no salió muestra "En preparación" como paso actual. Los pasos futuros
     * se devuelven en gris para que la línea de tiempo sea comprensible.
     *
     * @param array<string, mixed> $pedido Fila de Carrito::historial() + eventos + pago
     * @return array<int, array{clave:string, etiqueta:string, detalle:string, fecha:?string, estado:string}>
     */
    public static function lineaTiempo(array $pedido): array
    {
        $estado = trim((string) ($pedido['estado'] ?? 'Pendiente'));
        $pago = is_array($pedido['pago'] ?? null) ? $pedido['pago'] : [];
        $pagoEstado = trim((string) ($pago['estado'] ?? 'Pendiente'));
        $metodo = self::metodoEtiqueta((string) ($pago['metodo'] ?? ''));
        $recojo = (string) ($pedido['envio_zona'] ?? '') === Envio::ZONA_RECOJO;

        $eventos = [];
        foreach (($pedido['eventos'] ?? []) as $evento) {
            if (is_array($evento) && isset($evento['evento'])) {
                $eventos[(string) $evento['evento']] = $evento;
            }
        }

        $pasos = [[
            'clave'    => self::CREADO,
            'etiqueta' => 'Pedido recibido',
            'detalle'  => 'Registramos tu pedido en la tienda.',
            'fecha'    => $pedido['creado_en'] ?? null,
            'estado'   => 'completed',
        ]];

        $pasoPago = 'pending';
        if ($pagoEstado === 'Pagado') {
            $pasoPago = 'completed';
        } elseif ($estado !== Pedido::CANCELADO
            && in_array($pagoEstado, ['Pendiente', 'En verificación'], true)
        ) {
            $pasoPago = 'current';
        }

        $etiquetaPago = match ($pagoEstado) {
            'Pagado'          => 'Pago confirmado',
            'En verificación' => 'Pago en verificación',
            'Rechazado'       => 'Pago rechazado',
            default           => 'Pago pendiente',
        };
        $detallePago = match ($pagoEstado) {
            'Pagado'          => 'Pago confirmado' . ($metodo !== '' ? ' con ' . $metodo : '') . '.',
            'En verificación' => 'Estamos verificando el pago de tu pedido.',
            'Rechazado'       => 'El pago no fue aprobado.',
            default           => $metodo !== ''
                ? 'Pago pendiente (' . $metodo . ').'
                : 'Pago pendiente.',
        };
        $fechaPago = $eventos[self::PAGO_CONFIRMADO]['creado_en']
            ?? ($pagoEstado === 'Pagado' ? ($pago['creado_en'] ?? null) : null);

        $pasos[] = [
            'clave'    => self::PAGO_CONFIRMADO,
            'etiqueta' => $etiquetaPago,
            'detalle'  => $detallePago,
            'fecha'    => $fechaPago,
            'estado'   => $pasoPago,
        ];

        $pasoPreparacion = 'pending';
        if (in_array($estado, ['Enviado', 'Entregado'], true)) {
            $pasoPreparacion = 'completed';
        } elseif ($estado === 'Pagado' && $pagoEstado === 'Pagado') {
            $pasoPreparacion = 'current';
        }

        $pasos[] = [
            'clave'    => 'preparacion',
            'etiqueta' => 'En preparación',
            'detalle'  => 'Estamos alistando tu pedido.',
            'fecha'    => null,
            'estado'   => $pasoPreparacion,
        ];

        $pasoEnvio = 'pending';
        if ($estado === 'Enviado') {
            $pasoEnvio = 'current';
        } elseif ($estado === 'Entregado') {
            $pasoEnvio = 'completed';
        }

        $pasos[] = [
            'clave'    => self::ENVIADO,
            'etiqueta' => $recojo ? 'Listo para recojo' : 'Enviado',
            'detalle'  => $recojo
                ? 'Tu pedido está listo para recoger en tienda.'
                : self::detalleEnvio($pedido),
            'fecha'    => $eventos[self::ENVIADO]['creado_en'] ?? ($pedido['fecha_envio'] ?? null),
            'estado'   => $pasoEnvio,
        ];

        $pasos[] = [
            'clave'    => self::ENTREGADO,
            'etiqueta' => 'Entregado',
            'detalle'  => 'Pedido entregado al cliente.',
            'fecha'    => $eventos[self::ENTREGADO]['creado_en'] ?? null,
            'estado'   => $estado === 'Entregado' ? 'current' : 'pending',
        ];

        if ($estado === Pedido::CANCELADO) {
            // Un pedido cancelado no debe mostrar pasos futuros como si
            // todavía pudieran ocurrir; se conserva lo que ya sucedió.
            $pasos = array_slice($pasos, 0, 2);
            if (isset($eventos[self::REEMBOLSADO])) {
                $pasos[] = [
                    'clave'    => self::REEMBOLSADO,
                    'etiqueta' => 'Reembolsado',
                    'detalle'  => 'El pedido fue cancelado y el pago reembolsado.',
                    'fecha'    => $eventos[self::REEMBOLSADO]['creado_en'] ?? null,
                    'estado'   => 'current',
                ];
            } else {
                $pasos[] = [
                    'clave'    => self::CANCELADO,
                    'etiqueta' => 'Cancelado',
                    'detalle'  => 'El pedido fue cancelado.',
                    'fecha'    => $eventos[self::CANCELADO]['creado_en'] ?? null,
                    'estado'   => 'current',
                ];
            }
        }

        return $pasos;
    }

    /** @param array<string, mixed> $pedido */
    private static function detalleEnvio(array $pedido): string
    {
        $courier = trim((string) ($pedido['courier'] ?? ''));
        $guia = trim((string) ($pedido['guia_numero'] ?? ''));
        $partes = [];

        if ($courier !== '') {
            $partes[] = $courier;
        }
        if ($guia !== '') {
            $partes[] = 'Guía ' . $guia;
        }

        return $partes !== [] ? implode(' · ', $partes) : 'Tu pedido está en camino.';
    }

    private static function metodoEtiqueta(string $metodo): string
    {
        $metodo = trim($metodo);

        return match (strtolower($metodo)) {
            'tarjeta'       => 'Tarjeta',
            'yape'          => 'Yape',
            'transferencia' => 'Transferencia',
            'contraentrega' => 'Pago contra entrega',
            'efectivo'      => 'Efectivo',
            default         => $metodo !== '' ? ucfirst($metodo) : '',
        };
    }
}
