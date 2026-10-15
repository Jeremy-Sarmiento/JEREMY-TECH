<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Envio (Jeremy Tech).
 * Tarifas planas por zona (tabla `tarifas_envio`). El costo depende SOLO de
 * la zona de la dirección de entrega elegida; nunca del peso del carrito ni
 * del subtotal (mismo modelo que `techmarket`).
 */
class Envio
{
    /** Zona fija de recojo en la sede (sin costo). */
    public const ZONA_RECOJO = 'Recojo en tienda (Cercado, Arequipa)';

    /**
     * Tarifas activas, de la más barata a la más cara.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function zonas(): array
    {
        return conexion()->query(
            'SELECT id, zona, costo, dias_estimados
               FROM tarifas_envio WHERE activo = 1 ORDER BY costo, zona'
        )->fetchAll();
    }

    /**
     * @return array<string, mixed>|null  Tarifa activa, o null si no existe
     *                                     o está desactivada.
     */
    public static function tarifa(int $id): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT id, zona, costo, dias_estimados
               FROM tarifas_envio WHERE id = ? AND activo = 1 LIMIT 1'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Cotiza por id de tarifa.
     *
     * @return array{zona: string, costo: float, conocido: bool}
     */
    public static function cotizar(?int $tarifaId): array
    {
        if ($tarifaId === null || $tarifaId <= 0) {
            return ['zona' => '', 'costo' => 0.0, 'conocido' => false];
        }

        $tarifa = self::tarifa($tarifaId);
        if ($tarifa === null) {
            return ['zona' => '', 'costo' => 0.0, 'conocido' => false];
        }

        return [
            'zona'     => (string) $tarifa['zona'],
            'costo'    => (float) $tarifa['costo'],
            'conocido' => true,
        ];
    }

    /**
     * Cotiza la dirección guardada de un cliente.
     *
     * @return array{zona: string, costo: float, conocido: bool}
     */
    public static function cotizarDireccion(int $direccionId, int $clienteId): array
    {
        $stmt = conexion()->prepare(
            'SELECT tarifa_envio_id FROM direcciones WHERE id = ? AND cliente_id = ? LIMIT 1'
        );
        $stmt->execute([$direccionId, $clienteId]);
        $tarifaId = $stmt->fetchColumn();

        if ($tarifaId === false) {
            return ['zona' => '', 'costo' => 0.0, 'conocido' => false];
        }

        return self::cotizar($tarifaId === null ? null : (int) $tarifaId);
    }

    /**
     * Costo de la zona de recojo en la sede (0 si no está configurada).
     */
    public static function costoRecojo(): float
    {
        $stmt = conexion()->prepare(
            'SELECT costo FROM tarifas_envio WHERE zona = ? AND activo = 1 LIMIT 1'
        );
        $stmt->execute([self::ZONA_RECOJO]);
        $costo = $stmt->fetchColumn();

        return $costo === false ? 0.0 : (float) $costo;
    }
}
