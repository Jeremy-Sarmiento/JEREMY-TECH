<?php

declare(strict_types=1);

namespace Model;

/**
 * Modelo Carrito (Jeremy Tech MVC).
 * Capa de Datos / Negocio: sesión para invitados, BD por cuenta y registro
 * transaccional de pedidos en las tablas relacionales (users, pedidos,
 * detalle_pedido, pagos).
 */
class Carrito
{
    /**
     * Modalidades de entrega que NO usan una dirección (recojo en la sede).
     * El envío a domicilio ya no cotiza por constante: lo decide la ZONA de
     * la dirección elegida en el checkout (Etapa 5).
     */
    private const MODALIDAD_RECOJO = 'tienda';
    private const MAX_CANTIDAD = 2147483647;

    /**
     * Dispara la sesión PHP si aún no esta activa.
     */
    private static function iniciarSesion(): void
    {
        \Security\Proteccion::iniciarSesion();
    }

    /**
     * Clave de una línea del carrito (Etapa 7).
     *
     * Una variante es un SKU distinto, así que "Sony (Negro)" y "Sony (Azul)"
     * son dos líneas independientes. Sin variante la clave es solo el id, lo
     * que mantiene compatibles las sesiones antiguas.
     */
    private static function clave(string $productoId, ?int $varianteId): string
    {
        return ($varianteId !== null && $varianteId > 0) ? $productoId . ':' . $varianteId : $productoId;
    }

    /**
     * @return array{0: int, 1: int|null} [productoId, varianteId]
     */
    public static function parsearClave(string $clave): array
    {
        $partes = explode(':', $clave, 2);
        $productoId = (int) $partes[0];
        $varianteId = isset($partes[1]) && (int) $partes[1] > 0 ? (int) $partes[1] : null;

        return [$productoId, $varianteId];
    }

    /**
     * @return array<string, int> mapa clave => cantidad
     */
    public static function items(): array
    {
        self::iniciarSesion();
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            return $_SESSION['carrito'] ?? [];
        }
        // Migrar también las sesiones autenticadas que existían antes del cambio.
        if (!empty($_SESSION['carrito']) || !empty($_SESSION['carritos_pendientes'][$clienteId])) {
            self::fusionarInvitado($clienteId);
            unset($_SESSION['carrito'], $_SESSION['carritos_pendientes'][$clienteId]);
        }

        $stmt = conexion()->prepare(
            'SELECT i.producto_id, i.variante_id, i.cantidad
               FROM carrito_items i
               JOIN carritos c ON c.id = i.carrito_id
               JOIN users u ON u.id = c.cliente_id AND u.activo = 1 AND u.deleted_at IS NULL
               JOIN productos p ON p.id = i.producto_id AND p.activo = 1 AND p.archivado_en IS NULL
          LEFT JOIN producto_variantes v ON v.id = i.variante_id AND v.producto_id = p.id AND v.activo = 1
              WHERE c.cliente_id = ? AND (i.variante_id IS NULL OR v.id IS NOT NULL)
              ORDER BY i.id'
        );
        $stmt->execute([$clienteId]);
        $items = [];
        foreach ($stmt->fetchAll() as $fila) {
            $varianteId = $fila['variante_id'] !== null ? (int) $fila['variante_id'] : null;
            $items[self::clave((string) $fila['producto_id'], $varianteId)] = (int) $fila['cantidad'];
        }
        return $items;
    }

    /**
     * @param string   $productoId id numérico del producto publicado
     * @param int|null $varianteId variante elegida (null = producto base)
     */
    public static function agregar(string $productoId, int $cantidad = 1, ?int $varianteId = null): void
    {
        self::iniciarSesion();
        $productoId = (string) (int) $productoId;
        if (!self::lineaDisponible((int) $productoId, $varianteId)) {
            return;
        }
        $clave = self::clave($productoId, $varianteId);
        $cantidad = max(1, min(self::MAX_CANTIDAD, $cantidad));
        if (($clienteId = Cuenta::idSesion()) !== null) {
            self::items();
            self::modificarGuardado($clienteId, $clave, $cantidad, true);
            return;
        }
        $_SESSION['carrito'][$clave] = min(self::MAX_CANTIDAD, (int) ($_SESSION['carrito'][$clave] ?? 0) + $cantidad);
    }

    public static function eliminar(string $clave): void
    {
        self::cambiarCantidad($clave, 0);
    }

    public static function cambiarCantidad(string $clave, int $cantidad): void
    {
        self::iniciarSesion();
        if (!preg_match('/^[1-9][0-9]*(?::[1-9][0-9]*)?$/D', $clave)) {
            return;
        }
        if (($clienteId = Cuenta::idSesion()) !== null) {
            self::items();
            self::modificarGuardado($clienteId, $clave, min(self::MAX_CANTIDAD, $cantidad));
            return;
        }
        if ($cantidad <= 0) {
            unset($_SESSION['carrito'][$clave]);
            return;
        }
        if (isset($_SESSION['carrito'][$clave])) {
            $_SESSION['carrito'][$clave] = $cantidad;
        }
    }

    public static function vaciar(): void
    {
        self::iniciarSesion();
        if (($clienteId = Cuenta::idSesion()) !== null) {
            self::modificarGuardado($clienteId, null, 0);
            unset($_SESSION['carritos_pendientes'][$clienteId]);
        }
        unset($_SESSION['carrito']);
    }

    /** Se llama tras validar las credenciales, antes de cambiar la identidad de sesión. */
    public static function fusionarInvitado(int $clienteId): void
    {
        self::iniciarSesion();
        // Si se cambia de cuenta sin salir, cualquier sesión antigua pertenece a la anterior.
        $propietario = Cuenta::idSesion() ?? $clienteId;
        $items = $_SESSION['carritos_pendientes'][$propietario] ?? [];
        foreach ($_SESSION['carrito'] ?? [] as $clave => $cantidad) {
            $items[$clave] = min(self::MAX_CANTIDAD, (int) ($items[$clave] ?? 0) + (int) $cantidad);
        }
        self::fusionarGuardado($propietario, $items);
    }

    private static function lineaDisponible(int $productoId, ?int $varianteId): bool
    {
        $producto = Producto::porId($productoId);
        if ($producto === null || !empty($producto['archivado']) || empty($producto['activo'])) {
            return false;
        }
        if ($varianteId === null) {
            return true;
        }
        $variante = ProductoVariante::porId($varianteId);
        return $variante !== null && (int) $variante['productoId'] === $productoId && !empty($variante['activo']);
    }

    /** Una fila por cuenta; serializa cambios entre dispositivos sin reescribir otras líneas. */
    private static function bloquearGuardado(\PDO $pdo, int $clienteId): int
    {
        $cuenta = $pdo->prepare('SELECT id FROM users WHERE id = ? AND activo = 1 AND deleted_at IS NULL FOR UPDATE');
        $cuenta->execute([$clienteId]);
        if ($cuenta->fetchColumn() === false) {
            throw new \RuntimeException('Tu sesión ya no está activa. Vuelve a iniciar sesión.');
        }
        $pdo->prepare(
            'INSERT INTO carritos (cliente_id) VALUES (?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), actualizado_en = NOW()'
        )->execute([$clienteId]);
        $id = (int) $pdo->lastInsertId();
        if ($id <= 0) {
            throw new \RuntimeException('Tu sesión ya no está activa. Vuelve a iniciar sesión.');
        }
        return $id;
    }

    private static function sumarGuardado(\PDO $pdo, int $carritoId, int $productoId, ?int $varianteId, int $cantidad): void
    {
        $pdo->prepare(
            'INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE cantidad = LEAST(2147483647, cantidad + VALUES(cantidad))'
        )->execute([$carritoId, $productoId, $varianteId, $cantidad]);
    }

    /** No cambia la sesión: el llamador la limpia solo cuando su transacción termina. */
    private static function fusionarGuardado(int $clienteId, array $items): void
    {
        if ($items === []) {
            return;
        }
        $pdo = conexion();
        $propia = !$pdo->inTransaction();
        if ($propia) {
            $pdo->beginTransaction();
        }
        try {
            $carritoId = self::bloquearGuardado($pdo, $clienteId);
            foreach ($items as $clave => $cantidad) {
                if (!preg_match('/^[1-9][0-9]*(?::[1-9][0-9]*)?$/D', (string) $clave) || (int) $cantidad <= 0) {
                    continue;
                }
                [$productoId, $varianteId] = self::parsearClave((string) $clave);
                if (self::lineaDisponible($productoId, $varianteId)) {
                    self::sumarGuardado($pdo, $carritoId, $productoId, $varianteId, min(self::MAX_CANTIDAD, (int) $cantidad));
                }
            }
            if ($propia) {
                $pdo->commit();
            }
        } catch (\Throwable $error) {
            if ($propia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /** null vacía el carrito; sumar añade unidades; de lo contrario cambia o elimina una línea. */
    private static function modificarGuardado(int $clienteId, ?string $clave, int $cantidad, bool $sumar = false): void
    {
        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $carritoId = self::bloquearGuardado($pdo, $clienteId);
            if ($clave === null) {
                $pdo->prepare('DELETE FROM carrito_items WHERE carrito_id = ?')->execute([$carritoId]);
            } else {
                [$productoId, $varianteId] = self::parsearClave($clave);
                if ($sumar) {
                    self::sumarGuardado($pdo, $carritoId, $productoId, $varianteId, $cantidad);
                } elseif ($cantidad <= 0) {
                    $pdo->prepare('DELETE FROM carrito_items WHERE carrito_id = ? AND producto_id = ? AND variante_id <=> ?')
                        ->execute([$carritoId, $productoId, $varianteId]);
                } else {
                    $pdo->prepare('UPDATE carrito_items SET cantidad = ? WHERE carrito_id = ? AND producto_id = ? AND variante_id <=> ?')
                        ->execute([$cantidad, $carritoId, $productoId, $varianteId]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    public static function estaVacio(): bool
    {
        return count(self::items()) === 0;
    }

    public static function totalItems(): int
    {
        return array_sum(self::items());
    }

    /**
     * Detalle del carrito uniendo la sesión con el catálogo.
     *
     * El precio unitario sale SIEMPRE de `Producto::precioDe()`: variante +
     * tramo de volumen según la cantidad de la propia línea. Es la misma
     * función que calcula la ficha, así que lo que se ve y lo que se factura
     * no pueden divergir.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function lineas(): array
    {
        $lineas = [];
        foreach (self::items() as $clave => $cantidad) {
            [$productoId, $varianteId] = self::parsearClave((string) $clave);
            $calculado = Producto::precioDe($productoId, $varianteId, (int) $cantidad);
            if ($calculado === null) {
                continue; // producto borrado: la línea se ignora
            }
            $lineas[] = [
                'clave'          => (string) $clave,
                'producto'       => $calculado['producto'],
                'variante'       => $calculado['variante'],
                'varianteId'     => $varianteId,
                'varianteNombre' => $calculado['variante'] !== null
                    ? (string) $calculado['variante']['nombreVariante'] : '',
                'precioUnitario' => $calculado['precio'],
                'tramo'          => $calculado['tramo'],
                'cantidad'       => $cantidad,
                'subtotal'       => round($calculado['precio'] * $cantidad, 2),
            ];
        }
        return $lineas;
    }

    public static function subtotalPEN(): float
    {
        $total = 0.0;
        foreach (self::lineas() as $linea) {
            $total += $linea['subtotal'];
        }
        return (float) $total;
    }

    /**
     * Retorna el cupón aplicado en sesión (o null), validándolo contra la BD:
     * debe existir, estar activo, dentro de las fechas y con usos disponibles.
     * @return array<string, mixed>|null
     */
    public static function cuponActivo(): ?array
    {
        self::iniciarSesion();
        $codigo = $_SESSION['cupon'] ?? null;
        return $codigo ? self::obtenerCuponValido((string) $codigo) : null;
    }

    /**
     * Aplica (o re-aplica) un cupón leído desde la tabla `cupones` de la BD.
     */
    public static function aplicarCupon(string $codigo): bool
    {
        self::iniciarSesion();
        $cupon = self::obtenerCuponValido($codigo);
        if (!$cupon) {
            return false;
        }
        $_SESSION['cupon'] = $cupon['codigo'];
        return true;
    }

    /**
     * Valida un código de cupón contra la tabla `cupones` y lo devuelve
     * con el mismo shape que usaba el array estático (pct o fijoPEN + desc),
     * agregando el id para poder referenciarlo en el pedido.
     * @return array<string, mixed>|null
     */
    private static function obtenerCuponValido(string $codigo): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT id, codigo, tipo, valor, fecha_inicio, fecha_fin, usos_maximos, usos_actuales, activo
             FROM cupones WHERE codigo = ? LIMIT 1'
        );
        $stmt->execute([strtoupper(trim($codigo))]);
        $cupon = $stmt->fetch();

        if (!$cupon || !(int) $cupon['activo']) {
            return null;
        }

        $hoy = date('Y-m-d');
        if (($cupon['fecha_inicio'] && $cupon['fecha_inicio'] > $hoy)
            || ($cupon['fecha_fin'] && $cupon['fecha_fin'] < $hoy)) {
            return null;
        }

        $maximos = $cupon['usos_maximos'] !== null ? (int) $cupon['usos_maximos'] : 0;
        if ($maximos > 0 && (int) $cupon['usos_actuales'] >= $maximos) {
            return null;
        }

        $valor = (float) $cupon['valor'];
        $resultado = [
            'id'     => (int) $cupon['id'],
            'codigo' => $cupon['codigo'],
        ];

        if ($cupon['tipo'] === 'fijo') {
            $resultado['nombre']  = $cupon['codigo'];
            $resultado['desc']    = 'S/ ' . number_format($valor, 2) . ' OFF';
            $resultado['fijoPEN'] = $valor;
        } else {
            $pct = $valor / 100;
            $pctTexto = ($valor == (int) $valor) ? (string) (int) $valor : number_format($valor, 2);
            $resultado['nombre']  = $cupon['codigo'];
            $resultado['desc']    = $pctTexto . '% de Descuento';
            $resultado['pct']     = number_format($pct, 4);
        }

        return $resultado;
    }

    public static function quitarCupon(): void
    {
        self::iniciarSesion();
        unset($_SESSION['cupon']);
    }

    public static function montoDescuentoPEN(): float
    {
        return self::descuentoPara(self::subtotalPEN(), self::cuponActivo());
    }

    private static function descuentoPara(float $subtotal, ?array $cupon): float
    {
        if (!$cupon) {
            return 0.0;
        }
        if (isset($cupon['fijoPEN'])) {
            return min((float) $cupon['fijoPEN'], $subtotal);
        }
        // Un porcentaje mal cargado (>100) no puede dejar el total en negativo.
        $pct = min(1.0, max(0.0, (float) ($cupon['pct'] ?? 0)));
        return round($subtotal * $pct, 2);
    }

    /**
     * Costo de envío (Etapa 5).
     *  - modalidad 'tienda'  -> recojo en la sede, sale de `tarifas_envio`;
     *  - cualquier otra      -> zona de la dirección elegida (`$tarifaId`).
     */
    public static function costoEnvio(string $modalidad, ?int $tarifaId = null): float
    {
        if ($modalidad === self::MODALIDAD_RECOJO) {
            return Envio::costoRecojo();
        }

        return Envio::cotizar($tarifaId)['costo'];
    }

    /**
     * Lee un valor de la tabla `configuracion` (con respaldo por defecto).
     */
    public static function configuracion(string $clave, string $porDefecto = ''): string
    {
        $stmt = conexion()->prepare('SELECT valor FROM configuracion WHERE clave = ? LIMIT 1');
        $stmt->execute([$clave]);
        $valor = $stmt->fetchColumn();

        return $valor === false ? $porDefecto : (string) $valor;
    }

    /**
     * Total a pagar en PEN (subtotal - descuento + envío).
     */
    public static function totalPEN(
        string $modalidad = 'tienda',
        string $comprobante = 'boleta',
        ?int $tarifaId = null
    ): float {
        $subtotal  = self::subtotalPEN();
        $descuento = self::montoDescuentoPEN();
        $envio     = self::costoEnvio($modalidad, $tarifaId);

        $monto = $subtotal - $descuento + $envio;

        // Boleta no incluye IGV visible; factura sí lo desglosa en la vista.
        return round($monto, 2);
    }

    /**
     * Registra el pedido completo de forma atómica:
     * pedido -> detalle_pedido -> pago. Exige una cuenta iniciada: si la sesión
     * es de invitado lanza una excepción (el checkout es obligatorio con
     * sesión). No escribe en `users`: los datos de contacto y de SUNAT son del
     * pedido, no del perfil.
     *
     * @param array<string, mixed> $pago     metodo, comprobante, documento,
     *                                        razon_social, modalidad,
     *                                        direccion_id, tarifa_envio_id,
     *                                        nueva_etiqueta, nueva_direccion, nueva_referencia
     * @return int|null ID del pedido creado, o null si el carrito está vacío.
     */
    public static function checkout(array $pago): ?int
    {
        if (self::estaVacio()) {
            return null;
        }

        $clienteSesion = Cuenta::usuarioActual();
        if (!$clienteSesion) {
            throw new \RuntimeException('Debes iniciar sesión para completar la compra.');
        }

        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $clienteId = (int) $clienteSesion['id'];
            // Una sola compra/cambio por carrito: no duplicar pedidos entre dispositivos.
            $carritoId = self::bloquearGuardado($pdo, $clienteId);
            $lineas = self::lineas();
            if ($lineas === []) {
                $pdo->rollBack();
                return null;
            }
            $modalidad = (string) ($pago['modalidad'] ?? self::MODALIDAD_RECOJO);
            $subtotal = (float) array_sum(array_column($lineas, 'subtotal'));
            $cupon = self::cuponActivo();
            $descuento = self::descuentoPara($subtotal, $cupon);
            $igvPct = (float) self::configuracion('igv_porcentaje', '18');
            // Los precios ya incluyen IGV: extraerlo, no sumarlo de nuevo.
            $igvMonto = round(($subtotal - $descuento) * ($igvPct / (100 + $igvPct)), 2);

            $destino = self::resolverDestino($clienteId, $modalidad, $pago);
            if ($destino === null) {
                throw new \RuntimeException('La dirección de entrega no tiene una zona de envío vigente.');
            }
            $envioZona   = $destino['zona'];
            $envioCosto  = $destino['costo'];
            $direccionId = $destino['direccion_id'];
            $total = round($subtotal - $descuento + $envioCosto, 2);

            // Solo estados conocidos del pago simulado.
            $estadoPedido = (string) ($pago['estado_pedido'] ?? 'Pagado');
            if (!in_array($estadoPedido, ['Pendiente', 'Pagado'], true)) {
                $estadoPedido = 'Pendiente';
            }
            $estadoPago = (string) ($pago['estado_pago'] ?? 'Pagado');
            if (!in_array($estadoPago, ['Pendiente', 'Pagado', 'En verificación', 'Rechazado'], true)) {
                $estadoPago = 'Pendiente';
            }
            $transaccionId = mb_substr((string) ($pago['transaccion_id'] ?? ''), 0, 60);

            // 1. Si el destino era una dirección nueva, se persiste aquí mismo
            //    (misma transacción que el pedido). La primera del cliente
            //    queda como predeterminada.
            if ($destino['nueva'] !== null) {
                $nueva = $destino['nueva'];
                $esPrimera = Direccion::total($clienteId) === 0;
                if ($esPrimera) {
                    $pdo->prepare('UPDATE direcciones SET predeterminada = 0 WHERE cliente_id = ?')
                        ->execute([$clienteId]);
                }
                $pdo->prepare(
                    'INSERT INTO direcciones (cliente_id, etiqueta, direccion, referencia, tarifa_envio_id, predeterminada)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([
                    $clienteId,
                    mb_substr($nueva['etiqueta'], 0, 60),
                    mb_substr($nueva['direccion'], 0, 255),
                    $nueva['referencia'] !== '' ? mb_substr($nueva['referencia'], 0, 180) : null,
                    $nueva['tarifa_envio_id'],
                    $esPrimera ? 1 : 0,
                ]);
                $direccionId = (int) $pdo->lastInsertId();
            }

            // 2. Pedido (desglose de subtotal/IGV/envío/descuento + snapshot
            //    inmutable del destino, Etapa 5).
            $stmtPedido = $pdo->prepare(
                'INSERT INTO pedidos (cliente_id, total_pen, subtotal, igv_monto, envio_costo, envio_zona,
                                      descuento_aplicado, cupon_id, direccion_id, direccion_entrega,
                                      comprobante_tipo, comprobante_documento, comprobante_razon_social, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtPedido->execute([
                $clienteId,
                $total,
                $subtotal,
                $igvMonto,
                $envioCosto,
                $envioZona,
                $descuento,
                $cupon ? $cupon['id'] : null,
                $direccionId,
                $destino['texto'] !== '' ? $destino['texto'] : null,
                ($pago['comprobante'] ?? 'boleta') === 'factura' ? 'factura' : 'boleta',
                mb_substr((string) ($pago['documento'] ?? ''), 0, 11),
                mb_substr((string) ($pago['razon_social'] ?? ''), 0, 150),
                $estadoPedido,
            ]);
            $pedidoId = (int) $pdo->lastInsertId();

            // Registrar el uso del cupón. La condición repite el límite dentro
            // de la transacción: dos checkouts simultáneos no pueden superar
            // `usos_maximos` (el SELECT previo a la transacción no basta).
            if ($cupon) {
                $uso = $pdo->prepare(
                    'UPDATE cupones SET usos_actuales = usos_actuales + 1
                      WHERE id = ? AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)'
                );
                $uso->execute([$cupon['id']]);
                if ($uso->rowCount() === 0) {
                    throw new \RuntimeException('El cupón ya alcanzó su límite de usos.');
                }
            }

            // Bloquear inventario antes del detalle: las FK toman KEY SHARE;
            // invertir este orden provoca deadlocks entre compras del mismo producto.
            Inventario::descontar($pdo, $lineas, $pedidoId);

            // 3. Detalle del pedido. `vendedor_id` se copia del producto en
            //    el momento de la compra: una línea histórica no cambia de
            //    dueño aunque el producto se reasigne después.
            $stmtDetalle = $pdo->prepare(
                'INSERT INTO detalle_pedido
                    (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario_pen,
                     variante_id, variante_nombre, vendedor_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $reparto = [];
            foreach ($lineas as $linea) {
                $vendedorId = $linea['producto']['vendedorId'] ?? null;
                $stmtDetalle->execute([
                    $pedidoId,
                    $linea['producto']['id'],
                    $linea['producto']['nombre'],
                    $linea['cantidad'],
                    // Precio efectivo de la línea (variante + volumen), no el
                    // de catálogo: es lo que el cliente pagó por esa unidad.
                    $linea['precioUnitario'],
                    $linea['varianteId'],
                    (string) $linea['varianteNombre'],
                    $vendedorId,
                ]);

                if ($vendedorId === null) {
                    continue; // producto de la plataforma: no hay reparto
                }
                if (!isset($reparto[$vendedorId])) {
                    $reparto[$vendedorId] = 0.0;
                }
                $reparto[$vendedorId] += (float) $linea['subtotal'];
            }

            // 4. Reparto de comisiones (Etapa 6): UNA fila por pedido y
            //    vendedor, con el porcentaje congelado al comprar. El reparto
            //    se hace sobre la base SIN IGV.
            $stmtComision = $pdo->prepare(
                'INSERT INTO comisiones
                    (pedido_id, vendedor_id, monto_venta, porcentaje_comision, monto_comision, monto_vendedor)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($reparto as $vendedorId => $ventaVendedor) {
                if ($ventaVendedor <= 0) {
                    continue;
                }
                $repartido = \Model\Vendedor::repartir($ventaVendedor, (int) $vendedorId, $igvPct);
                $stmtComision->execute([
                    $pedidoId,
                    (int) $vendedorId,
                    $repartido['base'],
                    $repartido['porcentaje'],
                    $repartido['comision'],
                    $repartido['vendedor'],
                ]);
            }

            // 5. Pago (simulado: se guarda el estado decidido por PagoSimulado)
            $stmtPago = $pdo->prepare(
                'INSERT INTO pagos (pedido_id, metodo, monto_pen, transaccion_id, estado)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmtPago->execute([
                $pedidoId,
                $pago['metodo'] ?? '',
                $total,
                $transaccionId,
                $estadoPago,
            ]);

            // 6. Línea de tiempo del pedido. Se escribe con la misma
            //    transacción: si el pedido hace rollback, no queda un hito
            //    suelto en el historial.
            PedidoEvento::registrar(
                $pdo,
                $pedidoId,
                PedidoEvento::CREADO,
                $estadoPedido,
                'Pedido registrado en la tienda.'
            );
            if ($estadoPago === 'Pagado') {
                PedidoEvento::registrar(
                    $pdo,
                    $pedidoId,
                    PedidoEvento::PAGO_CONFIRMADO,
                    'Pagado',
                    'Pago aprobado en el checkout simulado.'
                );
            }

            $pdo->prepare('DELETE FROM carrito_items WHERE carrito_id = ?')->execute([$carritoId]);
            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        unset($_SESSION['carrito']);
        self::quitarCupon();

        return $pedidoId;
    }

    /**
     * Resuelve el destino de entrega y su costo (Etapa 5):
     *  - modalidad 'tienda'  -> recojo en la sede, sin dirección;
     *  - con `direccion_id`  -> dirección ya guardada del cliente (su zona);
     *  - con `tarifa_envio_id` -> dirección nueva que se persistirá.
     * Devuelve null si no hay una zona vigente (el pedido no se confirma).
     *
     * @param array<string, mixed> $pago
     * @return array{zona: string, costo: float, direccion_id: ?int, nueva: ?array, texto: string}|null
     */
    private static function resolverDestino(int $clienteId, string $modalidad, array $pago): ?array
    {
        if ($modalidad === self::MODALIDAD_RECOJO) {
            $zona = Envio::ZONA_RECOJO;
            return [
                'zona'         => $zona,
                'costo'        => Envio::costoRecojo(),
                'direccion_id' => null,
                'nueva'        => null,
                'texto'        => $zona,
            ];
        }

        $direccionId = isset($pago['direccion_id']) ? (int) $pago['direccion_id'] : 0;
        if ($direccionId > 0) {
            $direccion = Direccion::porId($direccionId, $clienteId);
            if ($direccion === null) {
                return null;
            }
            $tarifaId = $direccion['tarifa_envio_id'] !== null ? (int) $direccion['tarifa_envio_id'] : null;
            $cotizacion = Envio::cotizar($tarifaId);
            if (!$cotizacion['conocido']) {
                return null;
            }

            return [
                'zona'         => $cotizacion['zona'],
                'costo'        => $cotizacion['costo'],
                'direccion_id' => $direccionId,
                'nueva'        => null,
                'texto'        => Direccion::texto($direccion),
            ];
        }

        // Dirección nueva introducida directamente en el checkout.
        $nueva = [
            'etiqueta'        => trim((string) ($pago['nueva_etiqueta'] ?? '')),
            'direccion'       => trim((string) ($pago['nueva_direccion'] ?? '')),
            'referencia'      => trim((string) ($pago['nueva_referencia'] ?? '')),
            'tarifa_envio_id' => isset($pago['tarifa_envio_id']) ? (int) $pago['tarifa_envio_id'] : 0,
        ];
        if ($nueva['direccion'] === '') {
            return null;
        }

        $cotizacion = Envio::cotizar($nueva['tarifa_envio_id'] > 0 ? $nueva['tarifa_envio_id'] : null);
        if (!$cotizacion['conocido']) {
            return null;
        }
        $nueva['etiqueta'] = $nueva['etiqueta'] !== '' ? $nueva['etiqueta'] : 'Entrega';

        return [
            'zona'         => $cotizacion['zona'],
            'costo'        => $cotizacion['costo'],
            'direccion_id' => null,
            'nueva'        => $nueva,
            'texto'        => Direccion::texto($nueva),
        ];
    }

    /**
     * Historial de pedidos de un cliente (los más recientes primero) con su
     * detalle, pago y eventos de seguimiento.
     *
     * El filtro `$estado` se valida contra los estados reales del pedido:
     * cualquier otro valor se ignora en lugar de llegar al SQL.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function historial(int $clienteId, int $limite = 50, ?string $estado = null): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $pdo = conexion();
        $limite = max(1, min(200, $limite));

        $where = 'cliente_id = ?';
        $parametros = [$clienteId];

        $estadosValidos = array_merge(Pedido::ESTADOS, [Pedido::CANCELADO]);
        if ($estado !== null && in_array($estado, $estadosValidos, true)) {
            $where .= ' AND estado = ?';
            $parametros[] = $estado;
        }

        $stmtPedidos = $pdo->prepare(
            "SELECT id, cliente_id, total_pen, subtotal, igv_monto, envio_costo,
                    envio_zona, descuento_aplicado, direccion_entrega, courier,
                    guia_numero, fecha_envio, estado, creado_en
               FROM pedidos
              WHERE $where
              ORDER BY id DESC
              LIMIT $limite"
        );
        $stmtPedidos->execute($parametros);
        $pedidos = $stmtPedidos->fetchAll();

        if ($pedidos === []) {
            return [];
        }

        // Solo se consultan los pedidos de esta página: antes se traían todos
        // los pagos y detalles de la base y se filtraban en PHP.
        $ids = array_values(array_unique(array_map(
            static fn (array $pedido): int => (int) $pedido['id'],
            $pedidos
        )));
        $marcas = implode(',', array_fill(0, count($ids), '?'));

        $stmtPagos = $pdo->prepare(
            "SELECT pedido_id, metodo, estado, transaccion_id, creado_en
               FROM pagos
              WHERE pedido_id IN ($marcas)
              ORDER BY id"
        );
        $stmtPagos->execute($ids);
        $porPedidoPago = [];
        foreach ($stmtPagos->fetchAll() as $pago) {
            $porPedidoPago[(int) $pago['pedido_id']] = $pago;
        }

        $stmtDetalles = $pdo->prepare(
            "SELECT dp.pedido_id, dp.producto_id, dp.producto_nombre, dp.cantidad,
                    dp.precio_unitario_pen, dp.variante_id, dp.variante_nombre,
                    dp.vendedor_id, vu.name AS vendedor_nombre
               FROM detalle_pedido dp
          LEFT JOIN users vu ON vu.id = dp.vendedor_id
              WHERE dp.pedido_id IN ($marcas)
              ORDER BY dp.id"
        );
        $stmtDetalles->execute($ids);
        $porPedido = [];
        foreach ($stmtDetalles->fetchAll() as $detalle) {
            $porPedido[(int) $detalle['pedido_id']][] = $detalle;
        }

        $eventos = PedidoEvento::paraPedidos($ids);

        foreach ($pedidos as &$pedido) {
            $pedidoId = (int) $pedido['id'];
            $pedido['detalle'] = $porPedido[$pedidoId] ?? [];
            $pedido['pago'] = $porPedidoPago[$pedidoId] ?? null;
            $pedido['eventos'] = $eventos[$pedidoId] ?? [];
        }
        unset($pedido);

        return $pedidos;
    }

    /**
     * Conteo de pedidos por estado y monto acumulado del cliente.
     *
     * Los pedidos cancelados se cuentan, pero no suman al gasto total.
     *
     * @return array{porEstado: array<string, int>, total: int, gastado: float, enCurso: int, entregados: int}
     */
    public static function resumenPedidos(int $clienteId): array
    {
        $resumen = [
            'porEstado'  => [],
            'total'      => 0,
            'gastado'    => 0.0,
            'enCurso'    => 0,
            'entregados' => 0,
        ];
        if ($clienteId <= 0) {
            return $resumen;
        }

        $stmt = conexion()->prepare(
            'SELECT estado, COUNT(*) AS total, COALESCE(SUM(total_pen), 0) AS monto
               FROM pedidos
              WHERE cliente_id = ?
              GROUP BY estado'
        );
        $stmt->execute([$clienteId]);

        $enCurso = ['Pendiente', 'Pagado', 'Enviado'];
        foreach ($stmt->fetchAll() as $fila) {
            $estado = (string) $fila['estado'];
            $cantidad = (int) $fila['total'];

            $resumen['porEstado'][$estado] = $cantidad;
            $resumen['total'] += $cantidad;

            if ($estado !== Pedido::CANCELADO) {
                $resumen['gastado'] += (float) $fila['monto'];
            }
            if (in_array($estado, $enCurso, true)) {
                $resumen['enCurso'] += $cantidad;
            }
            if ($estado === 'Entregado') {
                $resumen['entregados'] += $cantidad;
            }
        }

        $resumen['gastado'] = round($resumen['gastado'], 2);

        return $resumen;
    }

    /**
     * Busca metadatos de un método de pago (envío al checkout).
     */
    public static function enviarInfo(string $modalidad): array
    {
        return ['costo' => self::costoEnvio($modalidad)];
    }
}
