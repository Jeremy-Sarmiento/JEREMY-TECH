<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Inventario (Jeremy Tech) — Etapa 8.
 *
 * Antes de esta etapa el checkout escribía el pedido pero nunca tocaba el
 * stock: se podía vender por encima de lo disponible y no quedaba rastro de
 * dónde salió cada unidad. Este modelo es el único punto por el que pasa el
 * descuento y la devolución de inventario, de modo que las dos operaciones
 * (venta y cancelación) no pueden divergir.
 *
 * Reglas de stock:
 *   - El stock del PRODUCTO es la unidad que se muestra en la ficha y en el
 *     catálogo.
 *   - El stock de la VARIANTE (Etapa 7) es el que manda cuando la línea
 *     trae variante. Se descuentan los dos, y la disponibilidad se limita
 *     por el menor de los dos: así no se puede agotar el producto por
 *     accidente contando solo un color.
 *
 * Concurrencia: todas las lecturas de stock que conducen a una escritura se
 * hacen con `SELECT ... FOR UPDATE` dentro de la transacción del pedido. Sin
 * eso, dos compras simultáneas de la última unidad podrían ambas superar la
 * comprobación y dejar el stock en negativo.
 */
class Inventario
{
    /**
     * Descuenta el stock de las líneas de un pedido YA DENTRO de su
     * transacción y registra el movimiento de salida.
     *
     * Lanza RuntimeException (que el llamador debe dejar propagar para que la
     * transacción haga rollback) si alguna línea no tiene stock suficiente.
     *
     * @param array<int, array<string, mixed>> $lineas Salida de Carrito::lineas()
     */
    public static function descontar(\PDO $pdo, array $lineas, int $pedidoId): void
    {
        foreach ($lineas as $linea) {
            $productoId = (int) $linea['producto']['id'];
            $varianteId = $linea['varianteId'];
            $cantidad   = (int) $linea['cantidad'];
            $nombre     = self::etiqueta($linea);

            $stockProducto = self::leerStock($pdo, 'productos', $productoId);
            $stockVariante = $varianteId !== null ? self::leerStock($pdo, 'producto_variantes', $varianteId) : null;

            // La disponibilidad la fija el recurso más escaso de la línea.
            $disponible = $stockVariante !== null
                ? min($stockProducto, $stockVariante)
                : $stockProducto;

            if ($cantidad > $disponible) {
                // \RuntimeException (raíz): si se escribe sin la barra, PHP
                // resuelve Model\RuntimeException y el error vuelve genérico.
                throw new \RuntimeException(
                    'Stock insuficiente para "' . $nombre . '": quedan ' . $disponible
                    . ' y se pidieron ' . $cantidad . '.'
                );
            }

            self::bajarStock($pdo, 'productos', $productoId, $cantidad);
            if ($varianteId !== null) {
                self::bajarStock($pdo, 'producto_variantes', $varianteId, $cantidad);
            }

            self::registrar($pdo, 'salida', $productoId, $varianteId, $pedidoId, $cantidad,
                'Venta del pedido #' . $pedidoId . ' · ' . $nombre);
        }
    }

    /**
     * Devuelve al stock las unidades de un pedido cancelado.
     *
     * Idempotente: si el pedido ya tiene un movimiento de entrada registrado,
     * no se vuelve a devolver (así un doble clic en "Cancelar" del panel no
     * duplica inventario). Devuelve true si se devolvió en esta llamada.
     */
    public static function devolver(\PDO $pdo, int $pedidoId): bool
    {
        if ($pedidoId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM movimientos_inventario
              WHERE pedido_id = ? AND tipo = \'entrada\''
        );
        $stmt->execute([$pedidoId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false; // ya se devolvió antes
        }

        $det = $pdo->prepare(
            'SELECT producto_id, variante_id, cantidad, producto_nombre
               FROM detalle_pedido WHERE pedido_id = ?'
        );
        $det->execute([$pedidoId]);
        $lineas = $det->fetchAll();
        if ($lineas === []) {
            return false;
        }

        foreach ($lineas as $linea) {
            $productoId = (int) $linea['producto_id'];
            $varianteId = $linea['variante_id'] !== null ? (int) $linea['variante_id'] : null;
            $cantidad   = (int) $linea['cantidad'];

            self::subirStock($pdo, 'productos', $productoId, $cantidad);
            if ($varianteId !== null) {
                self::subirStock($pdo, 'producto_variantes', $varianteId, $cantidad);
            }

            self::registrar($pdo, 'entrada', $productoId, $varianteId, $pedidoId, $cantidad,
                'Cancelación del pedido #' . $pedidoId . ' · ' . (string) $linea['producto_nombre']);
        }

        return true;
    }

    /**
     * Stock realmente disponible de una línea (para la ficha y el carrito).
     * Si la línea lleva variante, la fija el menor entre producto y variante.
     */
    public static function disponible(int $productoId, ?int $varianteId = null): int
    {
        $pdo = conexion();
        $stockProducto = self::leerStock($pdo, 'productos', $productoId);
        if ($varianteId === null) {
            return max(0, $stockProducto);
        }
        $stockVariante = self::leerStock($pdo, 'producto_variantes', $varianteId);
        return max(0, min($stockProducto, $stockVariante));
    }

    /**
     * Productos por debajo de su `stock_minimo` (alerta del dashboard).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function bajos(int $limite = 20): array
    {
        $stmt = conexion()->query(
            'SELECT p.id, p.slug, p.nombre, p.stock, p.stock_minimo, p.imagen
               FROM productos p
              WHERE p.activo = 1 AND p.stock <= p.stock_minimo
              ORDER BY (p.stock - p.stock_minimo) ASC, p.stock ASC
              LIMIT ' . max(1, $limite)
        );
        return $stmt->fetchAll();
    }

    /**
     * Movimientos del inventario, más recientes primero.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function movimientos(int $limite = 100): array
    {
        $stmt = conexion()->query(
            'SELECT m.*, p.nombre AS producto_nombre, pv.nombre_variante
               FROM movimientos_inventario m
               JOIN productos p ON p.id = m.producto_id
          LEFT JOIN producto_variantes pv ON pv.id = m.variante_id
              ORDER BY m.id DESC
              LIMIT ' . max(1, $limite)
        );
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------ //
    //  Internos
    // ------------------------------------------------------------ //

    /**
     * Lee el stock de una fila CON BLOQUEO (FOR UPDATE). Solo debe usarse
     * dentro de una transacción: el bloqueo se libera al confirmar o
     * cancelar, y evita que dos checkouts lean la misma última unidad.
     */
    private static function leerStock(\PDO $pdo, string $tabla, int $id): int
    {
        $stmt = $pdo->prepare("SELECT stock FROM {$tabla} WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $fila = $stmt->fetchColumn();
        return $fila === false ? 0 : (int) $fila;
    }

    private static function bajarStock(\PDO $pdo, string $tabla, int $id, int $cantidad): void
    {
        // GREATEST evita dejar el stock en negativo si alguien ajusta a mano
        // por fuera de este modelo mientras la transacción estaba abierta.
        $pdo->prepare("UPDATE {$tabla} SET stock = GREATEST(stock - ?, 0) WHERE id = ?")
            ->execute([$cantidad, $id]);
    }

    private static function subirStock(\PDO $pdo, string $tabla, int $id, int $cantidad): void
    {
        $pdo->prepare("UPDATE {$tabla} SET stock = stock + ? WHERE id = ?")
            ->execute([$cantidad, $id]);
    }

    private static function registrar(
        \PDO $pdo,
        string $tipo,
        int $productoId,
        ?int $varianteId,
        ?int $pedidoId,
        int $cantidad,
        string $motivo
    ): void {
        $pdo->prepare(
            'INSERT INTO movimientos_inventario (tipo, cantidad, motivo, producto_id, variante_id, pedido_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$tipo, $cantidad, $motivo, $productoId, $varianteId, $pedidoId]);
    }

    /** Nombre legible de una línea de Carrito::lineas(). */
    private static function etiqueta(array $linea): string
    {
        $nombre = (string) $linea['producto']['nombre'];
        $variante = trim((string) $linea['varianteNombre']);
        return $variante !== '' ? $nombre . ' (' . $variante . ')' : $nombre;
    }
}
