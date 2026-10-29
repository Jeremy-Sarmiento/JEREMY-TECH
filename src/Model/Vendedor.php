<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Vendedor (Jeremy Tech) — Etapa 6 (marketplace).
 *
 * No hay tabla propia: un vendedor es una fila de `users` con
 * `rol = 'vendedor'` y su ficha comercial
 * (`comision_porcentaje`, `verificado`) en las mismas columnas. Coincide
 * con el diseño de la semilla original, cuyas FK de `comisiones`,
 * `pagos_vendedor` y `calificaciones_vendedor` ya apuntan a `users(id)`.
 *
 * El reparto de dinero se congela en `comisiones` (una fila por pedido y
 * vendedor) al momento de la compra, igual que en la referencia `techmarket`.
 */
class Vendedor
{
    /** Rol que habilita el panel de vendedor. */
    public const ROL = 'vendedor';

    /**
     * Condición SQL de las cuentas que pueden vender. Se aplica sobre
     * `users.tipo` no interviene: el rol es la única llave.
     */
    private const CONDICION_VENDEDOR = "rol = 'vendedor'";

    /**
     * Fichas de vendedor con sus métricas de venta.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todos(): array
    {
        return conexion()->query(
            "SELECT u.id, u.name, u.apellidos, u.email, u.usuario, u.tipo, u.activo,
                    u.verificado, u.comision_porcentaje,
                    (SELECT COUNT(*) FROM productos p
                      WHERE p.vendedor_id = u.id) AS productos,
                    (SELECT IFNULL(SUM(c.monto_venta), 0) FROM comisiones c
                      JOIN pedidos pd ON pd.id = c.pedido_id
                     WHERE c.vendedor_id = u.id
                       AND pd.estado NOT IN ('Cancelado', 'Pendiente')) AS ventas,
                    (SELECT IFNULL(SUM(c.monto_comision), 0) FROM comisiones c
                      JOIN pedidos pd ON pd.id = c.pedido_id
                     WHERE c.vendedor_id = u.id
                       AND pd.estado NOT IN ('Cancelado', 'Pendiente')) AS comisiones,
                    (SELECT IFNULL(SUM(c.monto_vendedor), 0) FROM comisiones c
                      JOIN pedidos pd ON pd.id = c.pedido_id
                     WHERE c.vendedor_id = u.id
                       AND pd.estado NOT IN ('Cancelado', 'Pendiente')) AS ganancias,
                    (SELECT IFNULL(SUM(pv.monto), 0) FROM pagos_vendedor pv
                     WHERE pv.vendedor_id = u.id) AS liquidado
               FROM users u
              WHERE " . self::CONDICION_VENDEDOR . " AND u.deleted_at IS NULL
              ORDER BY u.name, u.apellidos"
        )->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function porId(int $id): ?array
    {
        $stmt = conexion()->prepare(
            "SELECT * FROM users
              WHERE id = ? AND " . self::CONDICION_VENDEDOR . " AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Porcentaje de comisión GLOBAL de respaldo (clave de `configuracion`).
     * Se usa cuando el vendedor no tiene porcentaje propio.
     */
    public static function comisionGlobal(): float
    {
        $stmt = conexion()->prepare(
            "SELECT valor FROM configuracion WHERE clave = 'comision_marketplace_porcentaje' LIMIT 1"
        );
        $stmt->execute();
        $valor = $stmt->fetchColumn();

        $pct = is_numeric($valor) ? (float) $valor : 15.0;
        return max(0.0, min(100.0, $pct));
    }

    /**
     * Porcentaje efectivo de un vendedor. `null` (producto de la
     * plataforma) devuelve la comisión global, que el caller descarta.
     */
    public static function comisionDe(?int $vendedorId): float
    {
        if ($vendedorId === null || $vendedorId <= 0) {
            return self::comisionGlobal();
        }

        $stmt = conexion()->prepare(
            'SELECT comision_porcentaje FROM users WHERE id = ? AND ' . self::CONDICION_VENDEDOR . ' LIMIT 1'
        );
        $stmt->execute([$vendedorId]);
        $valor = $stmt->fetchColumn();

        if ($valor === false || $valor === null || $valor === '') {
            return self::comisionGlobal();
        }

        return max(0.0, min(100.0, (float) $valor));
    }

    /**
     * Reparte el importe de una línea (con IGV) entre la plataforma y el
     * vendedor. El reparto se hace sobre la BASE SIN IGV, igual que en la
     * referencia: el IGV es dinero del estado, no de nadie.
     *
     * @param float $montoConIgv subtotal de la línea ya con IGV
     * @return array{porcentaje: float, comision: float, vendedor: float}
     */
    public static function repartir(float $montoConIgv, ?int $vendedorId, float $igvPorcentaje): array
    {
        if ($vendedorId === null || $vendedorId <= 0) {
            return ['porcentaje' => 0.0, 'comision' => 0.0, 'vendedor' => 0.0];
        }

        $porcentaje = self::comisionDe($vendedorId);
        $base = $igvPorcentaje > 0
            ? $montoConIgv * 100 / (100 + $igvPorcentaje)
            : $montoConIgv;

        $comision = round($base * $porcentaje / 100, 2);
        $ganancia = round($base - $comision, 2);

        return ['porcentaje' => $porcentaje, 'comision' => $comision, 'vendedor' => $ganancia];
    }

    /**
     * Ganancia acumulada del vendedor en pedidos efectivamente cobrados.
     *
     * Un pedido reembolsado queda en estado `Cancelado`, así que deja de
     * sumar aquí y en `saldoPendiente()`. El reembolso se procesa antes de
     * cualquier liquidación: no hay que ajustar `pagos_vendedor` a mano.
     */
    public static function gananciasDe(int $vendedorId): float
    {
        $stmt = conexion()->prepare(
            "SELECT IFNULL(SUM(c.monto_vendedor), 0)
               FROM comisiones c
               JOIN pedidos p ON p.id = c.pedido_id
              WHERE c.vendedor_id = ?
                AND p.estado NOT IN ('Cancelado', 'Pendiente')"
        );
        $stmt->execute([$vendedorId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Total ya liquidado al vendedor (`pagos_vendedor`).
     */
    public static function liquidadoDe(int $vendedorId): float
    {
        $stmt = conexion()->prepare(
            'SELECT IFNULL(SUM(monto), 0) FROM pagos_vendedor WHERE vendedor_id = ?'
        );
        $stmt->execute([$vendedorId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Saldo todavía no pagado al vendedor: ganancias - liquidaciones.
     */
    public static function saldoPendiente(int $vendedorId): float
    {
        return round(self::gananciasDe($vendedorId) - self::liquidadoDe($vendedorId), 2);
    }

    /**
     * Resumen económico del panel "Mis productos".
     *
     * Solo suma pedidos cobrados: un pedido `Pendiente` o `Cancelado` no
     * genera ganancia. `vendido` y `comision` son la base SIN IGV que se
     * congeló en `comisiones` al comprar; `neto` es lo que le corresponde
     * al vendedor y `saldo` lo que aún no se le liquida.
     *
     * @return array{vendido:float, comision:float, neto:float, liquidado:float, saldo:float}
     */
    public static function resumenGanancias(int $vendedorId): array
    {
        if ($vendedorId <= 0) {
            return [
                'vendido' => 0.0,
                'comision' => 0.0,
                'neto' => 0.0,
                'liquidado' => 0.0,
                'saldo' => 0.0,
            ];
        }

        $stmt = conexion()->prepare(
            "SELECT IFNULL(SUM(c.monto_venta), 0)    AS vendido,
                    IFNULL(SUM(c.monto_comision), 0) AS comision,
                    IFNULL(SUM(c.monto_vendedor), 0) AS neto
               FROM comisiones c
               JOIN pedidos p ON p.id = c.pedido_id
              WHERE c.vendedor_id = ?
                AND p.estado NOT IN ('Cancelado', 'Pendiente')"
        );
        $stmt->execute([$vendedorId]);
        $fila = $stmt->fetch() ?: [];

        $neto = (float) ($fila['neto'] ?? 0);
        $liquidado = self::liquidadoDe($vendedorId);

        return [
            'vendido' => (float) ($fila['vendido'] ?? 0),
            'comision' => (float) ($fila['comision'] ?? 0),
            'neto' => $neto,
            'liquidado' => $liquidado,
            'saldo' => round($neto - $liquidado, 2),
        ];
    }

    /**
     * Ventas del vendedor con el comprador y el estado del pago.
     *
     * `detalle_pedido.vendedor_id` se congela al comprar, así que la fila
     * sigue mostrando la venta aunque el producto se edite u oculte después.
     * Se usa una subconsulta para el ÚLTIMO pago: un pedido puede tener más
     * de un registro en `pagos` (por ejemplo, un reintento) y no queremos
     * duplicar la línea.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function compradoresDe(int $vendedorId, int $limite = 50): array
    {
        if ($vendedorId <= 0) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $stmt = conexion()->prepare(
            'SELECT dp.pedido_id, dp.producto_nombre, dp.variante_nombre,
                    dp.cantidad, dp.precio_unitario_pen,
                    p.estado AS pedido_estado, p.creado_en,
                    u.name AS comprador_nombre, u.apellidos AS comprador_apellidos,
                    u.email AS comprador_email,
                    (SELECT pa.estado FROM pagos pa
                      WHERE pa.pedido_id = p.id
                      ORDER BY pa.id DESC LIMIT 1) AS pago_estado
               FROM detalle_pedido dp
               JOIN pedidos p ON p.id = dp.pedido_id
               JOIN users u ON u.id = p.cliente_id
              WHERE dp.vendedor_id = ?
              ORDER BY p.id DESC, dp.id DESC
              LIMIT ' . $limite
        );
        $stmt->execute([$vendedorId]);

        return array_map(static function (array $fila): array {
            $fila['pedido_id'] = (int) $fila['pedido_id'];
            $fila['cantidad'] = (int) $fila['cantidad'];
            $fila['precio_unitario_pen'] = (float) $fila['precio_unitario_pen'];
            $fila['subtotal'] = round($fila['cantidad'] * $fila['precio_unitario_pen'], 2);
            return $fila;
        }, $stmt->fetchAll());
    }

    /**
     * Promedio y total de calificaciones recibidas como vendedor.
     *
     * Es distinto de las reseñas de producto (`resenas`): aquí el cliente
     * valora el trato y la entrega del vendedor.
     *
     * @return array{promedio:float, total:int}
     */
    public static function reputacionDe(int $vendedorId): array
    {
        // Fuente única: el mismo cálculo que usa la ficha del producto.
        return CalificacionVendedor::resumen($vendedorId);
    }

    /**
     * Últimas calificaciones recibidas, con el nombre del comprador.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function ultimasCalificacionesDe(int $vendedorId, int $limite = 5): array
    {
        if ($vendedorId <= 0) {
            return [];
        }

        $limite = max(1, min(20, $limite));
        $stmt = conexion()->prepare(
            'SELECT c.calificacion, c.comentario, c.creado_en, u.name AS cliente_nombre
               FROM calificaciones_vendedor c
               JOIN users u ON u.id = c.cliente_id
              WHERE c.vendedor_id = ?
              ORDER BY c.id DESC
              LIMIT ' . $limite
        );
        $stmt->execute([$vendedorId]);

        return $stmt->fetchAll();
    }

    /**
     * Alcance del panel de PEDIDOS para el rol activo.
     *
     * Devuelve [join, wherePedidos, whereDetalle, parametros]:
     *   * admin   -> ['', '1 = 1', '1 = 1', []]  (todo el histórico)
     *   * vendedor-> [JOIN a detalle, 'dp.vendedor_id = ?', mismo, [id]]
     *
     * Los `where` son la condición COMPLETA que va justo después de
     * `WHERE` (sin `AND` inicial), para que el caller solo concatene.
     *
     * El `join` es obligatorio porque `pedidos` no tiene columna de vendedor:
     * el dueño de una venta vive en `detalle_pedido.vendedor_id`.
     *
     * @return array{0: string, 1: string, 2: string, 3: array<int, mixed>}
     */
    public static function alcancePanelPedidos(): array
    {
        if (!Administrador::esVendedor()) {
            return ['', '1 = 1', '1 = 1', []];
        }
        $vendedorId = Administrador::idSesion();
        if ($vendedorId === null) {
            return ['', '1 = 0', '1 = 0', []];
        }

        return [
            ' JOIN detalle_pedido dp ON dp.pedido_id = p.id ',
            'dp.vendedor_id = ?',
            'dp.vendedor_id = ?',
            [$vendedorId],
        ];
    }

    /**
     * Historial de ventas canalizadas por el vendedor (una fila por pedido).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function ventasDe(int $vendedorId): array
    {
        $stmt = conexion()->prepare(
            "SELECT c.id, c.pedido_id, c.monto_venta, c.porcentaje_comision,
                    c.monto_comision, c.monto_vendedor, c.creado_en,
                    p.estado, p.total_pen
               FROM comisiones c
               JOIN pedidos p ON p.id = c.pedido_id
              WHERE c.vendedor_id = ?
              ORDER BY c.id DESC"
        );
        $stmt->execute([$vendedorId]);
        return $stmt->fetchAll();
    }

    /**
     * Liquidaciones registradas al vendedor.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function liquidacionesDe(int $vendedorId): array
    {
        $stmt = conexion()->prepare(
            'SELECT id, monto, metodo, referencia, creado_en
               FROM pagos_vendedor WHERE vendedor_id = ? ORDER BY id DESC'
        );
        $stmt->execute([$vendedorId]);
        return $stmt->fetchAll();
    }

    /**
     * Registra una liquidación al vendedor (el saldo pendiente baja).
     *
     * @return string|null null si salió bien, o el motivo del error.
     */
    public static function liquidar(int $vendedorId, float $monto, string $metodo, string $referencia): ?string
    {
        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            // Bloquea la fila del vendedor: dos liquidaciones simultáneas
            // esperan turno y la segunda ya ve el saldo descontado. Sin esto,
            // ambas leen el mismo saldo y lo pagan dos veces.
            $lock = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$vendedorId]);
            if (!$lock->fetchColumn()) {
                $pdo->rollBack();
                return 'Ese vendedor ya no existe.';
            }
            if ($monto <= 0) {
                $pdo->rollBack();
                return 'El monto de la liquidación debe ser mayor a 0.';
            }
            if ($metodo === '') {
                $pdo->rollBack();
                return 'Indica el método de la liquidación.';
            }
            // Nunca se paga más de lo acumulado: el saldo se recalcula aquí,
            // dentro de la transacción.
            $saldo = self::saldoPendiente($vendedorId);
            if ($monto > $saldo + 0.009) {
                $pdo->rollBack();
                return 'El monto supera el saldo pendiente (S/ ' . number_format($saldo, 2) . ').';
            }

            $stmt = $pdo->prepare(
                'INSERT INTO pagos_vendedor (vendedor_id, monto, metodo, referencia)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$vendedorId, round($monto, 2), $metodo, $referencia]);
            $pdo->commit();

            return null;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('No se pudo registrar la liquidación: ' . $e->getMessage());
            return 'No se pudo registrar la liquidación.';
        }
    }

    /**
     * Nombre visible del vendedor para la tienda.
     */
    public static function nombreDe(?int $vendedorId): string
    {
        if ($vendedorId === null || $vendedorId <= 0) {
            return '';
        }
        $stmt = conexion()->prepare(
            "SELECT name FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$vendedorId]);
        $nombre = $stmt->fetchColumn();

        return $nombre === false ? '' : (string) $nombre;
    }
}
