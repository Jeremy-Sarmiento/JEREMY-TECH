<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo PrecioVolumen (Jeremy Tech) — Etapa 7.
 *
 * Tramos de descuento por cantidad de un mismo producto publicado. La tabla
 * existe en la referencia `techmarket` pero no estaba modelada aquí: sin ella
 * el catálogo no podía ofrecer precio mayorista ni "desde S/ x".
 *
 * REGLA DE PRECIO (fuente única, también en Producto::precioDe()):
 *   unitario = productos.precio_pen + variante.precio_extra
 *   si la cantidad cae en un tramo, el tramo sustituye `precio_pen` base y el
 *   extra de la variante sigue sumándose encima.
 *
 * Un tramo con `cantidad_maxima = NULL` es abierto ("desde N en adelante").
 */
class PrecioVolumen
{
    /**
     * Tramos de un producto en orden de presentación (el más pequeño primero).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deProducto(int $productoId): array
    {
        if ($productoId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT id, producto_id, cantidad_minima, cantidad_maxima, precio_unitario
               FROM precios_volumen
              WHERE producto_id = ?
              ORDER BY cantidad_minima ASC'
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
            'SELECT id, producto_id, cantidad_minima, cantidad_maxima, precio_unitario
               FROM precios_volumen WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ? self::hidratar($fila) : null;
    }

    /**
     * Tramo que cubre una cantidad dada. Se recorre de mayor a menor
     * `cantidad_minima` para que, ante solapes mal configurados, gane siempre
     * el tramo más específico y el resultado no dependa del orden de lectura.
     *
     * @param array<int, array<string, mixed>> $tramos
     * @return array<string, mixed>|null
     */
    public static function precioPara(array $tramos, int $cantidad): ?array
    {
        if ($cantidad <= 0 || $tramos === []) {
            return null;
        }

        usort($tramos, static fn (array $a, array $b): int => $b['cantidadMinima'] <=> $a['cantidadMinima']);

        foreach ($tramos as $tramo) {
            $min = (int) $tramo['cantidadMinima'];
            $max = $tramo['cantidadMaxima'] !== null ? (int) $tramo['cantidadMaxima'] : null;

            if ($cantidad >= $min && ($max === null || $cantidad <= $max)) {
                return $tramo;
            }
        }

        return null;
    }

    /**
     * Precio unitario efectivo. Ver la regla de la cabecera de la clase.
     *
     * @param array<int, array<string, mixed>> $tramos
     * @return array{precio: float, tramoId: ?int}
     */
    public static function precioUnitario(
        float $precioBase,
        float $precioExtra,
        array $tramos,
        int $cantidad
    ): array {
        $tramo = self::precioPara($tramos, $cantidad);
        $base = $tramo !== null ? (float) $tramo['precioUnitario'] : $precioBase;

        return [
            'precio'  => round($base + $precioExtra, 2),
            'tramoId' => $tramo !== null ? (int) $tramo['id'] : null,
        ];
    }

    /**
     * Precio más barato del producto ("desde S/ x" en la tarjeta del catálogo).
     * null = no tiene tramos.
     *
     * @param array<int, array<string, mixed>> $tramos
     */
    public static function precioDesde(array $tramos): ?float
    {
        if ($tramos === []) {
            return null;
        }

        $precios = array_map(static fn (array $t): float => (float) $t['precioUnitario'], $tramos);

        return round(min($precios), 2);
    }

    /**
     * Texto legible del rango de un tramo ("2 a 4 unidades" / "5 o más").
     *
     * @param array<string, mixed>|null $tramo
     */
    public static function rangoTexto(?array $tramo): string
    {
        if ($tramo === null) {
            return '';
        }

        $min = (int) $tramo['cantidadMinima'];
        if ($tramo['cantidadMaxima'] === null) {
            return $min . ' o más unidades';
        }

        return $min . ' a ' . (int) $tramo['cantidadMaxima'] . ' unidades';
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private static function hidratar(array $fila): array
    {
        return [
            'id'             => (int) $fila['id'],
            'productoId'     => (int) $fila['producto_id'],
            'cantidadMinima' => (int) $fila['cantidad_minima'],
            'cantidadMaxima' => $fila['cantidad_maxima'] !== null ? (int) $fila['cantidad_maxima'] : null,
            'precioUnitario' => (float) $fila['precio_unitario'],
        ];
    }
}
