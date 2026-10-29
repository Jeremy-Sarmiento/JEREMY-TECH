<?php

declare(strict_types=1);

namespace Model;

/**
 * Modelo CalificacionVendedor (Jeremy Tech) — Bloque 3.
 *
 * Es distinto de `Resena` (que califica el PRODUCTO): aquí el cliente valora
 * al vendedor C2C como tal — su trato, si envió a tiempo, si el producto
 * llegó como se describió. La UNIQUE `calificacion_vendedor_unica`
 * (vendedor_id, cliente_id) permite una sola calificación por comprador y
 * vendedor, que se puede actualizar.
 *
 * Solo puede calificar quien de verdad le compró algo en un pedido cobrado.
 * La comprobación se hace siempre en la base de datos, nunca con el
 * `vendedor_id` que envía el navegador.
 */
final class CalificacionVendedor
{
    public const CALIFICACION_MIN = 1;
    public const CALIFICACION_MAX = 5;
    public const COMENTARIO_MAX = 500;

    /**
     * Estados de pedido que cuentan como compra efectiva. "Pendiente" (pago
     * sin confirmar) y "Cancelado" no habilitan la calificación.
     *
     * @var array<int, string>
     */
    private const ESTADOS_COMPRA = ['Pagado', 'Enviado', 'Entregado'];

    /**
     * Calificaciones que el cliente ya dejó, indexadas por vendedor.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT vendedor_id, calificacion, comentario, creado_en
               FROM calificaciones_vendedor
              WHERE cliente_id = ?'
        );
        $stmt->execute([$clienteId]);

        $calificaciones = [];
        foreach ($stmt->fetchAll() as $fila) {
            $calificaciones[(int) $fila['vendedor_id']] = $fila;
        }

        return $calificaciones;
    }

    /**
     * ¿El cliente compró algo a ese vendedor en un pedido cobrado?
     */
    public static function puedeCalificar(int $clienteId, int $vendedorId): bool
    {
        if ($clienteId <= 0 || $vendedorId <= 0 || $clienteId === $vendedorId) {
            return false;
        }

        $marcas = implode(',', array_fill(0, count(self::ESTADOS_COMPRA), '?'));
        $stmt = conexion()->prepare(
            "SELECT 1
               FROM detalle_pedido dp
               JOIN pedidos p ON p.id = dp.pedido_id
              WHERE p.cliente_id = ? AND dp.vendedor_id = ?
                AND p.estado IN ($marcas)
              LIMIT 1"
        );
        $stmt->execute(array_merge([$clienteId, $vendedorId], self::ESTADOS_COMPRA));

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Crea o actualiza la calificación del cliente para un vendedor.
     *
     * @return string|null null si se guardó, o el mensaje de error.
     */
    public static function guardar(int $clienteId, int $vendedorId, int $calificacion, string $comentario): ?string
    {
        if ($clienteId <= 0) {
            return 'Inicia sesión para calificar al vendedor.';
        }
        if ($vendedorId <= 0) {
            return 'Vendedor no válido.';
        }
        if ($clienteId === $vendedorId) {
            return 'No puedes calificarte a ti mismo.';
        }
        if ($calificacion < self::CALIFICACION_MIN || $calificacion > self::CALIFICACION_MAX) {
            return 'Elige una calificación de 1 a 5 estrellas.';
        }

        $comentario = trim($comentario);
        if (mb_strlen($comentario) > self::COMENTARIO_MAX) {
            return 'El comentario no puede superar los ' . self::COMENTARIO_MAX . ' caracteres.';
        }

        if (!self::puedeCalificar($clienteId, $vendedorId)) {
            return 'Solo puedes calificar a un vendedor si le compraste y pagaste algo.';
        }

        // ON DUPLICATE KEY UPDATE usa la UNIQUE (vendedor_id, cliente_id):
        // volver a calificar actualiza la fila en vez de duplicarla. La
        // fecha original se conserva para que editarla no la haga parecer
        // una compra nueva.
        $stmt = conexion()->prepare(
            'INSERT INTO calificaciones_vendedor (vendedor_id, cliente_id, calificacion, comentario)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                calificacion = VALUES(calificacion),
                comentario = VALUES(comentario)'
        );
        $stmt->execute([$vendedorId, $clienteId, $calificacion, $comentario]);

        return null;
    }

    /**
     * Promedio y total de calificaciones recibidas por un vendedor.
     *
     * @return array{promedio: float, total: int}
     */
    public static function resumen(int $vendedorId): array
    {
        if ($vendedorId <= 0) {
            return ['promedio' => 0.0, 'total' => 0];
        }

        $stmt = conexion()->prepare(
            'SELECT COALESCE(AVG(calificacion), 0) AS promedio, COUNT(*) AS total
               FROM calificaciones_vendedor
              WHERE vendedor_id = ?'
        );
        $stmt->execute([$vendedorId]);
        $fila = $stmt->fetch() ?: [];

        return [
            'promedio' => round((float) ($fila['promedio'] ?? 0), 1),
            'total'    => (int) ($fila['total'] ?? 0),
        ];
    }
}
