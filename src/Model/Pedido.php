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
