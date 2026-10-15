<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo ProductoVariante (Jeremy Tech) — Etapa 7.
 *
 * Una variante es un SKU físico distinto del mismo producto publicado: otro
 * color, otra capacidad. Tiene su propio `precio_extra` y su propio `stock`.
 *
 * La semilla base ya creaba esta tabla, pero nadie la leía. Ahora la consume
 * la ficha del producto (`?ruta=producto&slug=...`) y el carrito.
 */
class ProductoVariante
{
    /**
     * Variantes ACTIVAS de un producto, ordenadas como se muestran en la ficha.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deProducto(int $productoId): array
    {
        if ($productoId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT id, producto_id, nombre_variante, precio_extra, stock, color
               FROM producto_variantes
              WHERE producto_id = ? AND activo = 1
              ORDER BY nombre_variante'
        );
        $stmt->execute([$productoId]);

        return array_map(self::hidratar(...), $stmt->fetchAll());
    }

    /**
     * Todas las variantes de un producto (para el panel: incluye inactivas).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todasDeProducto(int $productoId): array
    {
        if ($productoId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT id, producto_id, nombre_variante, precio_extra, stock, color, activo
               FROM producto_variantes
              WHERE producto_id = ?
              ORDER BY nombre_variante'
        );
        $stmt->execute([$productoId]);

        return array_map(self::hidratar(...), $stmt->fetchAll());
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function porId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT id, producto_id, nombre_variante, precio_extra, stock, color, activo
               FROM producto_variantes WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ? self::hidratar($fila) : null;
    }

    /**
     * ¿Existe ya una variante con ese nombre en ese producto?
     * Se usa antes de INSERTAR para no depender de un UNIQUE que la tabla
     * heredada no tiene.
     */
    public static function existe(int $productoId, string $nombre, int $exceptoId = 0): bool
    {
        $stmt = conexion()->prepare(
            'SELECT COUNT(*) FROM producto_variantes
              WHERE producto_id = ? AND nombre_variante = ? AND id <> ?'
        );
        $stmt->execute([$productoId, $nombre, $exceptoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Etiqueta visible de la variante (para el carrito y el pedido). */
    public static function nombreDe(?int $varianteId): string
    {
        $variante = $varianteId !== null ? self::porId($varianteId) : null;
        return $variante !== null ? (string) $variante['nombreVariante'] : '';
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private static function hidratar(array $fila): array
    {
        $color = (string) ($fila['color'] ?? '');

        return [
            'id'             => (int) $fila['id'],
            'productoId'     => (int) $fila['producto_id'],
            'nombreVariante' => (string) $fila['nombre_variante'],
            'precioExtra'    => (float) $fila['precio_extra'],
            'stock'          => (int) $fila['stock'],
            'color'          => $color,
            'activo'         => (int) ($fila['activo'] ?? 1),
        ];
    }
}
