<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Direccion;
use Model\ProductoVendedor;
use PHPUnit\Framework\TestCase;

class IntegridadTest extends TestCase
{
    private PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        ensureTestDb();
        self::migrar();
    }

    protected function setUp(): void
    {
        resetTestDb();
        $this->pdo = testPdo();
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, vendedor_id)
            VALUES (1, 'integridad', 'Celulares', 'Producto de prueba', '', 2)");
        $this->pdo->exec("INSERT INTO producto_variantes (id, producto_id, nombre_variante) VALUES (1, 1, 'Azul')");
        $this->pdo->exec('INSERT INTO pedidos (id, cliente_id) VALUES (1, 3)');
        // Los IDs explícitos no avanzan las secuencias identity de PostgreSQL.
        foreach (['productos', 'producto_variantes', 'pedidos'] as $tabla) {
            $this->pdo->exec("SELECT setval(pg_get_serial_sequence('$tabla', 'id'), 1)");
        }
    }

    public function testRechazaValoresFueraDelDominio(): void
    {
        $this->pdo->exec('INSERT INTO detalle_pedido (pedido_id, producto_id) VALUES (1, 1)');
        $this->pdo->exec('INSERT INTO pagos (pedido_id) VALUES (1)');
        $this->pdo->exec('INSERT INTO carritos (id, cliente_id) VALUES (1, 3)');
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)');
        $this->pdo->exec("INSERT INTO movimientos_inventario (tipo, cantidad, producto_id) VALUES ('entrada', 1, 1)");
        $this->pdo->exec('INSERT INTO resenas (cliente_id, producto_id, calificacion) VALUES (3, 1, 1)');
        $this->pdo->exec('INSERT INTO calificaciones_vendedor (cliente_id, vendedor_id, calificacion) VALUES (3, 2, 5)');
        $this->pdo->exec('INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
            VALUES (1, 1, 2, 0)');
        $this->pdo->exec("INSERT INTO cupones (codigo, valor) VALUES ('PRUEBA', 10)");
        $this->pdo->exec("INSERT INTO garantias (nombre) VALUES ('Prueba')");
        $this->pdo->exec('INSERT INTO pagos_vendedor (vendedor_id, monto) VALUES (2, 0)');
        $this->pdo->exec('INSERT INTO comisiones (pedido_id, vendedor_id, monto_venta, porcentaje_comision,
            monto_comision, monto_vendedor) VALUES (1, 2, 0, 0, 0, 0)');

        foreach ([
            'UPDATE productos SET stock = -1',
            'UPDATE productos SET stock_minimo = -1',
            'UPDATE producto_variantes SET stock = -1',
            'UPDATE carrito_items SET cantidad = 0',
            'UPDATE detalle_pedido SET cantidad = 0',
            'UPDATE movimientos_inventario SET cantidad = 0',
            'UPDATE precios_volumen SET cantidad_minima = 0',
            'UPDATE precios_volumen SET cantidad_maxima = 0',
            'UPDATE resenas SET calificacion = 0',
            'UPDATE resenas SET calificacion = 6',
            'UPDATE calificaciones_vendedor SET calificacion = 0',
            'UPDATE calificaciones_vendedor SET calificacion = 6',
            'UPDATE cupones SET valor = 0',
            'UPDATE cupones SET valor = 101',
            "UPDATE cupones SET valor = 'NaN'",
            'UPDATE cupones SET usos_actuales = -1',
            'UPDATE cupones SET usos_maximos = -1',
            'UPDATE comisiones SET porcentaje_comision = 101',
            'UPDATE usuarios SET comision_porcentaje = -1 WHERE id = 2',
        ] as $sql) {
            $this->rechaza($sql, '23514');
        }

        foreach ([
            'productos' => ['precio_pen'],
            'producto_variantes' => ['precio_extra'],
            'detalle_pedido' => ['precio_unitario_pen'],
            'pagos' => ['monto_pen'],
            'pedidos' => ['total_pen', 'subtotal', 'igv_monto', 'envio_costo', 'descuento_aplicado'],
            'precios_volumen' => ['precio_unitario'],
            'garantias' => ['precio_pen'],
            'tarifas_envio' => ['costo'],
            'pagos_vendedor' => ['monto'],
            'comisiones' => ['monto_venta', 'monto_comision', 'monto_vendedor'],
        ] as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                foreach (['-0.01', "'NaN'"] as $valor) {
                    $this->rechaza("UPDATE $tabla SET $columna = $valor", '23514');
                }
            }
        }
        $this->pdo->exec("UPDATE cupones SET tipo = 'fijo', valor = 101, usos_maximos = NULL");
        $this->pdo->exec('UPDATE precios_volumen SET cantidad_maxima = NULL');
        $this->assertSame('101.00', $this->pdo->query('SELECT valor FROM cupones')->fetchColumn());
    }

    public function testIndicesCubrenListadosYReferencias(): void
    {
        $indices = $this->pdo->query("SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'public'")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ([
            'idx_pagos_pedido_id_desc' => '(pedido_id, id DESC)',
            'idx_pedidos_cliente_id_desc' => '(cliente_id, id DESC)',
            'idx_detalle_pedido_pedido' => '(pedido_id)',
            'idx_detalle_pedido_producto' => '(producto_id)',
            'idx_detalle_pedido_variante' => '(variante_id)',
            'idx_pedidos_cupon' => '(cupon_id)',
            'idx_pedidos_direccion' => '(direccion_id)',
            'idx_resenas_visibles_recientes' => '(producto_id, creado_en DESC, id DESC)',
        ] as $nombre => $columnas) {
            $this->assertArrayHasKey($nombre, $indices);
            $this->assertStringContainsString($columnas, $indices[$nombre]);
        }
        $this->assertMatchesRegularExpression("/WHERE .*estado.*'visible'/", $indices['idx_resenas_visibles_recientes']);
    }

    public function testRechazaReferenciasHuerfanas(): void
    {
        foreach ([
            'INSERT INTO pedidos (cliente_id) VALUES (999999)',
            'INSERT INTO pedidos (cliente_id, cupon_id) VALUES (3, 999999)',
            'INSERT INTO pedidos (cliente_id, direccion_id) VALUES (3, 999999)',
            'INSERT INTO pagos (pedido_id) VALUES (999999)',
            'INSERT INTO detalle_pedido (pedido_id, producto_id) VALUES (999999, 1)',
            'INSERT INTO detalle_pedido (pedido_id, producto_id) VALUES (1, 999999)',
            'INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id) VALUES (1, 1, 999999)',
        ] as $sql) {
            $this->rechaza($sql, '23503');
        }
    }

    public function testBorrarCuponYDireccionConservaPedidoYLosImportes(): void
    {
        $this->pdo->exec("INSERT INTO cupones (id, codigo, valor) VALUES (1, 'PRUEBA', 10)");
        $this->pdo->exec("INSERT INTO direcciones (id, cliente_id, direccion) VALUES (1, 3, 'Calle de prueba')");
        $this->pdo->exec("UPDATE pedidos SET cupon_id = 1, direccion_id = 1, total_pen = 90,
            subtotal = 100, descuento_aplicado = 10, direccion_entrega = 'Calle de prueba' WHERE id = 1");
        $this->pdo->exec('INSERT INTO pagos (pedido_id, monto_pen) VALUES (1, 90)');

        $this->pdo->exec('DELETE FROM cupones WHERE id = 1');
        $this->assertTrue(Direccion::eliminar(1, 3));

        $pedido = $this->pdo->query('SELECT cupon_id, direccion_id, total_pen, subtotal,
            descuento_aplicado, direccion_entrega FROM pedidos WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame([
            'cupon_id' => null, 'direccion_id' => null, 'total_pen' => '90.00',
            'subtotal' => '100.00', 'descuento_aplicado' => '10.00', 'direccion_entrega' => 'Calle de prueba',
        ], $pedido);
        $this->assertSame('90.00', $this->pdo->query('SELECT monto_pen FROM pagos WHERE pedido_id = 1')->fetchColumn());
    }

    public function testBorrarVarianteSinMovimientosConservaElDetalleHistorico(): void
    {
        $this->pdo->exec("INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id, variante_nombre,
            producto_nombre, cantidad, precio_unitario_pen) VALUES (1, 1, 1, 'Azul', 'Producto original', 2, 15)");

        $this->assertFalse(ProductoVendedor::eliminarVariante(2, 'integridad', 1));
        // El servicio conserva la variante vendida; un borrado SQL directo conserva los snapshots por FK.
        $this->pdo->exec('DELETE FROM producto_variantes WHERE id = 1');
        $detalle = $this->pdo->query('SELECT variante_id, variante_nombre, producto_nombre, cantidad,
            precio_unitario_pen FROM detalle_pedido WHERE pedido_id = 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame([
            'variante_id' => null, 'variante_nombre' => 'Azul', 'producto_nombre' => 'Producto original',
            'cantidad' => 2, 'precio_unitario_pen' => '15.00',
        ], $detalle);
    }

    public function testNoBorraProductosConVentasNiVarianteConMovimientos(): void
    {
        $this->pdo->exec('INSERT INTO detalle_pedido (pedido_id, producto_id) VALUES (1, 1)');
        $this->pdo->exec("INSERT INTO movimientos_inventario (tipo, cantidad, producto_id, variante_id)
            VALUES ('salida', 1, 1, 1)");

        $this->assertFalse(ProductoVendedor::eliminar(2, 'integridad')['ok']);
        $this->rechaza('DELETE FROM productos WHERE id = 1', '23503');
        $this->rechaza('DELETE FROM pedidos WHERE id = 1', '23503');
        $this->rechaza('DELETE FROM producto_variantes WHERE id = 1', '23503');
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM detalle_pedido')->fetchColumn());
    }

    public function testPermiteEliminarProductoSinHistorial(): void
    {
        $this->assertTrue(ProductoVendedor::eliminar(2, 'integridad')['ok']);
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM productos WHERE id = 1')->fetchColumn());
    }

    public function testNoBorraPedidoConPagoNiClienteConPedido(): void
    {
        $this->pdo->exec('INSERT INTO pagos (pedido_id, monto_pen) VALUES (1, 20)');
        $this->rechaza('DELETE FROM pedidos WHERE id = 1', '23503');
        $this->rechaza('DELETE FROM usuarios WHERE id = 3', '23503');
        $this->pdo->exec('UPDATE usuarios SET eliminado_en = NOW(), activo = 0 WHERE id = 3');
        $this->assertSame('20.00', $this->pdo->query('SELECT monto_pen FROM pagos WHERE pedido_id = 1')->fetchColumn());
    }

    public function testPermiteImportarHijosAntesQuePadresSoloAlDiferirLasFk(): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('SET CONSTRAINTS ALL DEFERRED');
            $this->pdo->exec('INSERT INTO pagos (pedido_id) VALUES (77)');
            $this->pdo->exec('INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id) VALUES (77, 77, 77)');
            $this->pdo->exec('INSERT INTO pedidos (id, cliente_id, cupon_id, direccion_id) VALUES (77, 77, 77, 77)');
            $this->pdo->exec("INSERT INTO usuarios (id, nombre, email, password) VALUES (77, 'Importado', 'importado@example.com', '!')");
            $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones)
                VALUES (77, 'importado', 'Celulares', 'Importado', '')");
            $this->pdo->exec("INSERT INTO producto_variantes (id, producto_id, nombre_variante) VALUES (77, 77, 'Azul')");
            $this->pdo->exec("INSERT INTO cupones (id, codigo, valor) VALUES (77, 'IMPORTADO', 10)");
            $this->pdo->exec("INSERT INTO direcciones (id, cliente_id, direccion) VALUES (77, 77, 'Importada')");
            $this->pdo->commit();
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM detalle_pedido WHERE pedido_id = 77')->fetchColumn());
    }

    public function testMigracionEsIdempotenteYNoReparaHistorialInvalido(): void
    {
        $this->pdo->exec('ALTER TABLE productos DROP CONSTRAINT ck_productos_stock');
        $this->pdo->exec('ALTER TABLE pagos DROP CONSTRAINT fk_pagos_pedido');
        $this->pdo->exec('UPDATE productos SET stock = -1 WHERE id = 1');
        $this->pdo->exec('INSERT INTO pagos (pedido_id, monto_pen) VALUES (999999, 12)');

        try {
            self::migrar();
            $antes = $this->pdo->query("SELECT indexname, indexdef FROM pg_indexes
                WHERE schemaname = 'public' ORDER BY indexname")->fetchAll(PDO::FETCH_ASSOC);
            self::migrar();
            $this->assertSame($antes, $this->pdo->query("SELECT indexname, indexdef FROM pg_indexes
                WHERE schemaname = 'public' ORDER BY indexname")->fetchAll(PDO::FETCH_ASSOC));
            $this->assertSame(-1, (int) $this->pdo->query('SELECT stock FROM productos WHERE id = 1')->fetchColumn());
            $this->assertSame('12.00', $this->pdo->query('SELECT monto_pen FROM pagos WHERE pedido_id = 999999')->fetchColumn());
            $this->assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM pg_constraint
                WHERE connamespace = 'public'::regnamespace AND NOT convalidated
                AND conname IN ('ck_productos_stock', 'fk_pagos_pedido')")->fetchColumn());
            $this->rechaza('UPDATE productos SET stock = -2 WHERE id = 1', '23514');
            $this->rechaza('INSERT INTO pagos (pedido_id) VALUES (999999)', '23503');
        } finally {
            // Solo la prueba fabrica estos datos; la migración nunca los corrige.
            $this->pdo->exec('UPDATE productos SET stock = 0 WHERE id = 1');
            $this->pdo->exec('DELETE FROM pagos WHERE pedido_id = 999999');
            self::migrar();
        }
    }

    private function rechaza(string $sql, string $sqlState): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            $this->assertSame($sqlState, $e->getCode(), $sql);
            return;
        }
        $this->fail('La base aceptó una operación inválida: ' . $sql);
    }

    private static function migrar(): void
    {
        // psql envía cada CREATE INDEX CONCURRENTLY fuera de una transacción.
        $proceso = proc_open([
            'psql', '-X', '-w', '-v', 'ON_ERROR_STOP=1', '-h', DB_HOST, '-p', DB_PORT,
            '-U', DB_USER, '-d', DB_NAME, '-f', __DIR__ . '/../../database/integridad-rendimiento.sql',
        ], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, [
            'PATH' => (string) getenv('PATH'), 'PGPASSWORD' => DB_PASS, 'PGCONNECT_TIMEOUT' => '5',
        ]);
        self::assertIsResource($proceso, 'No se pudo iniciar psql para la migración local.');
        $salida = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($proceso), $salida . $error);
    }
}
