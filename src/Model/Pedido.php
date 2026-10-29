<?php

declare(strict_types=1);

namespace Model;

/**
 * Modelo Pedido (Jeremy Tech) — Etapa 8.
 *
 * El ciclo de vida del pedido deja de ser un `<select>` libre en el panel.
 * Cada estado solo puede alcanzarse desde los que lo preceden, de modo que
 * un pedido "Entregado" no pueda volver a "Pendiente" ni saltarse el envío.
 *
 * "Cancelado" es el único estado que se puede entrar desde varios orígenes
 * (cualquier estado anterior a la entrega) porque una cancelación puede
 * ocurrir mientras el paquete aún no salió del almacén.
 */
class Pedido
{
    /** Estado inicial de todo pedido recién pagado en la tienda. */
    public const ESTADO_INICIAL = 'Pagado';

    /** Recorrido lineal normal del pedido. */
    public const ESTADOS = ['Pendiente', 'Pagado', 'Enviado', 'Entregado'];

    /** Estado terminal: ya no vuelve al circuito normal. */
    public const CANCELADO = 'Cancelado';

    /**
     * Estados desde los que todavía se puede cancelar. Si el pedido ya salió
     * hacia el cliente, cancelarlo obligaría a un reembolso logístico, así
     * que se bloquea desde el panel.
     */
    public const CANCELABLES_DESDE = ['Pendiente', 'Pagado'];

    /**
     * ¿El pago de un pedido se puede reembolsar?
     *
     * La condición real es el estado del PAGO, no el del pedido: un pedido
     * enviado o entregado ya no se puede "cancelar" logísticamente, pero sí
     * se puede reembolsar al cliente. También cubre el caso de un pedido
     * cancelado cuyo pago quedó confirmado sin marcar como reembolsado.
     * El stock se devuelve con `Inventario::devolver()`, que es idempotente
     * y no duplica la entrada si ya se había devuelto al cancelar.
     */
    public static function pagoReembolsable(string $pagoEstado): bool
    {
        return trim($pagoEstado) === 'Pagado';
    }

    /**
     * Código visible del pedido. El panel y el historial del cliente usan el
     * mismo formato: `#JT-00006`.
     */
    public static function codigo(int $pedidoId): string
    {
        return '#JT-' . str_pad((string) max(0, $pedidoId), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Extrae el id numérico de un código `#JT-00006` o `JT-00006`.
     * Devuelve null si el texto no tiene el formato esperado.
     */
    public static function idDesdeCodigo(string $codigo): ?int
    {
        if (!preg_match('/^\s*#?\s*JT\s*-\s*(\d{1,10})\s*$/iu', $codigo, $coincidencia)) {
            return null;
        }

        $id = (int) $coincidencia[1];
        return $id > 0 ? $id : null;
    }

    /**
     * Pedido de un cliente concreto a partir del código visible.
     *
     * El chatbot NUNCA debe usar una búsqueda global por código: el código es
     * derivado del id y sería posible enumerar pedidos ajenos. El `cliente_id`
     * limita el resultado a la cuenta autenticada.
     *
     * @return array<string, mixed>|null
     */
    public static function porCodigoDeCliente(string $codigo, int $clienteId): ?array
    {
        $id = self::idDesdeCodigo($codigo);
        if ($id === null || $clienteId <= 0) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT id, cliente_id, total_pen, estado, envio_zona, courier,
                    guia_numero, fecha_envio, creado_en
               FROM pedidos
              WHERE id = ? AND cliente_id = ?
              LIMIT 1'
        );
        $stmt->execute([$id, $clienteId]);
        $pedido = $stmt->fetch();

        return $pedido ?: null;
    }

    /**
     * ¿Se permite cambiar un pedido de $de a $a?
     *
     * Reglas:
     *  - Mantener el mismo estado es un no-op inofensivo: se permite.
     *  - El recorrido normal solo avanza un paso hacia adelante.
     *  - "Cancelado" solo se entra desde estados cancelables.
     *  - Un pedido cancelado ya no se mueve (ni se reabre).
     */
    public static function transicionPermitida(string $de, string $a): bool
    {
        $de = trim($de);
        $a  = trim($a);

        if ($de === '' || $a === '') {
            return false;
        }
        if ($de === $a) {
            return true;
        }

        // Un cancelado es terminal: no se reabre desde el panel.
        if ($de === self::CANCELADO) {
            return false;
        }

        if ($a === self::CANCELADO) {
            return in_array($de, self::CANCELABLES_DESDE, true);
        }

        $iDe = array_search($de, self::ESTADOS, true);
        $iA  = array_search($a, self::ESTADOS, true);
        if ($iDe === false || $iA === false) {
            return false;
        }

        // Solo un paso adelante. "Pendiente" se usa como estado manual de
        // revisión y no forma parte del flujo de pago automático.
        return $iA === $iDe + 1;
    }

    /**
     * Estados a los que un pedido en $de puede pasar ahora mismo.
     * La vista del panel usa esto para limitar el `<select>` a lo legal.
     *
     * @return array<int, string>
     */
    public static function destinos(string $de): array
    {
        $de = trim($de);
        $destinos = [];
        foreach (array_merge(self::ESTADOS, [self::CANCELADO]) as $candidato) {
            if (self::transicionPermitida($de, $candidato)) {
                $destinos[] = $candidato;
            }
        }
        return $destinos;
    }

    /** Clase CSS de la etiqueta de estado (coherente con la vista admin). */
    public static function claseEstado(string $estado): string
    {
        return match ($estado) {
            'Pagado'    => 'etiqueta-ok',
            'Enviado'   => 'etiqueta-info',
            'Entregado' => 'etiqueta-ok',
            'Cancelado' => 'etiqueta-off',
            default     => 'etiqueta',
        };
    }
}
