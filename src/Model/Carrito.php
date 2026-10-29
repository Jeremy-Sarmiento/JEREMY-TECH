<?php

declare(strict_types=1);

namespace Model;

/**
 * Modelo Carrito (Jeremy Tech MVC).
 * Capa de Datos / Negocio: carrito persistido en sesión PHP y registro
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
        return $_SESSION['carrito'] ?? [];
    }

    /**
     * @param string   $productoId id numérico del producto publicado
     * @param int|null $varianteId variante elegida (null = producto base)
     */
    public static function agregar(string $productoId, int $cantidad = 1, ?int $varianteId = null): void
    {
        self::iniciarSesion();
        $producto = Producto::porId((int) $productoId);
        if ($producto === null || !empty($producto['archivado']) || empty($producto['activo'])) {
            // Ignora identificadores que no existen, publicaciones archivadas
            // o retiradas: si entraran, la sesión quedaría "no vacía" y el
            // checkout crearía un pedido en 0 (o vendería algo retirado).
            return;
        }
        if ($varianteId !== null) {
            $variante = ProductoVariante::porId($varianteId);
            // Una variante de otro producto no debe poder entrar en la línea.
            if ($variante === null || (int) $variante['productoId'] !== (int) $productoId) {
                $varianteId = null;
            } elseif (empty($variante['activo'])) {
                return; // la variante está retirada: no se vende
            }
        }

        $clave = self::clave($productoId, $varianteId);
        $cantidad = max(1, $cantidad);
        $_SESSION['carrito'][$clave] = (int) ($_SESSION['carrito'][$clave] ?? 0) + $cantidad;
    }

    public static function eliminar(string $clave): void
    {
        self::iniciarSesion();
        unset($_SESSION['carrito'][$clave]);
    }

    public static function cambiarCantidad(string $clave, int $cantidad): void
    {
        self::iniciarSesion();
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
        unset($_SESSION['carrito']);
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
        $subtotal = self::subtotalPEN();
        $cupon = self::cuponActivo();
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
        $lineas = self::lineas();
        if ($lineas === []) {
            // Sesión con referencias a productos que ya no existen: no crear
            // un pedido vacío con total 0.
            return null;
        }
        $modalidad = (string) ($pago['modalidad'] ?? self::MODALIDAD_RECOJO);
        $subtotal = self::subtotalPEN();
        $cupon = self::cuponActivo();
        $descuento = self::montoDescuentoPEN();
        $igvPct = (float) self::configuracion('igv_porcentaje', '18');
        // Los precios de la tienda ya incluyen IGV: se extrae del bruto, no se
        // suma encima (misma fórmula que Vendedor::repartir()).
        $igvMonto = round(($subtotal - $descuento) * ($igvPct / (100 + $igvPct)), 2);

        // Destino de entrega (Etapa 5): la ZONA de la dirección elegida es lo
        // único que fija el costo. Sin una zona vigente no se confirma.
        $clienteId = (int) $clienteSesion['id'];
        $destino = self::resolverDestino($clienteId, $modalidad, $pago);
        if ($destino === null) {
            throw new \RuntimeException('La dirección de entrega no tiene una zona de envío vigente.');
        }
        $envioZona   = $destino['zona'];
        $envioCosto  = $destino['costo'];
        $direccionId = $destino['direccion_id'];
        $total = round($subtotal - $descuento + $envioCosto, 2);

        // El controlador decide el resultado del pago simulado. Se aceptan solo
        // estados conocidos para que un valor externo no pueda falsear el flujo.
        $estadoPedido = (string) ($pago['estado_pedido'] ?? 'Pagado');
        if (!in_array($estadoPedido, ['Pendiente', 'Pagado'], true)) {
            $estadoPedido = 'Pendiente';
        }
        $estadoPago = (string) ($pago['estado_pago'] ?? 'Pagado');
        if (!in_array($estadoPago, ['Pendiente', 'Pagado', 'En verificación', 'Rechazado'], true)) {
            $estadoPago = 'Pendiente';
        }
        $transaccionId = mb_substr((string) ($pago['transaccion_id'] ?? ''), 0, 60);

        $pdo->beginTransaction();
        try {
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

            // (Etapa 8) Se descuenta el stock REAL de producto y variante con
            // bloqueo de fila (SELECT ... FOR UPDATE). Si no alcanza, lanza y
            // toda la transacción hace rollback: ni el pedido, ni las
            // comisiones, ni el stock quedan a medias. Es lo único que impide
            // que dos compras simultáneas vendan la misma última unidad.
            Inventario::descontar($pdo, $lineas, $pedidoId);

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
                    round($ventaVendedor, 2),
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

            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }

        self::vaciar();
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
