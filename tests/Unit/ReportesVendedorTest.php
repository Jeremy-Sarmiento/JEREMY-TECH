<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Vendedor;
use PHPUnit\Framework\TestCase;

class ReportesVendedorTest extends TestCase
{
    private const VENDEDOR = 2; // vendedor@example.com

    protected function setUp(): void
    {
        resetTestDb();
        $pdo = testPdo();

        $pdo->exec(
            "INSERT INTO productos (id, vendedor_id, slug, categoria, nombre, especificaciones, precio_pen, stock, activo, colores, imagen, stock_minimo)
             VALUES (1, 2, 'prod-a', 'Celulares', 'Producto A', '', 100.00, 10, 1, '', '', 1),
                    (2, 2, 'prod-b', 'Celulares', 'Producto B', '', 50.00, 10, 1, '', '', 1)"
        );

        // Pedido cobrado del mes actual: 2 unidades de A.
        $pdo->exec(
            "INSERT INTO pedidos (id, cliente_id, total_pen, subtotal, igv_monto, envio_costo, envio_zona, comprobante_documento, courier, estado, creado_en)
             VALUES (1, 3, 236.00, 200.00, 36.00, 0, 'Lima', '', '', 'Pagado', NOW())"
        );
        $pdo->exec(
            "INSERT INTO detalle_pedido (pedido_id, cantidad, precio_unitario_pen, variante_nombre, producto_id, producto_nombre, vendedor_id)
             VALUES (1, 2, 100.00, '', 1, 'Producto A', 2)"
        );
        $pdo->exec(
            'INSERT INTO comisiones (pedido_id, vendedor_id, monto_venta, porcentaje_comision, monto_comision, monto_vendedor)
             VALUES (1, 2, 200.00, 10.00, 20.00, 180.00)'
        );

        // Pedido cobrado de hace dos meses: 1 unidad de B.
        $pdo->exec(
            "INSERT INTO pedidos (id, cliente_id, total_pen, subtotal, igv_monto, envio_costo, envio_zona, comprobante_documento, courier, estado, creado_en)
             VALUES (2, 4, 59.00, 50.00, 9.00, 0, 'Lima', '', '', 'Pagado', DATE_SUB(NOW(), INTERVAL 2 MONTH))"
        );
        $pdo->exec(
            "INSERT INTO detalle_pedido (pedido_id, cantidad, precio_unitario_pen, variante_nombre, producto_id, producto_nombre, vendedor_id)
             VALUES (2, 1, 50.00, '', 2, 'Producto B', 2)"
        );
        $pdo->exec(
            'INSERT INTO comisiones (pedido_id, vendedor_id, monto_venta, porcentaje_comision, monto_comision, monto_vendedor)
             VALUES (2, 2, 50.00, 10.00, 5.00, 45.00)'
        );

        // Pedido cancelado: no debe aparecer en ningún reporte.
        $pdo->exec(
            "INSERT INTO pedidos (id, cliente_id, total_pen, subtotal, igv_monto, envio_costo, envio_zona, comprobante_documento, courier, estado, creado_en)
             VALUES (3, 3, 118.00, 100.00, 18.00, 0, 'Lima', '', '', 'Cancelado', NOW())"
        );
        $pdo->exec(
            "INSERT INTO detalle_pedido (pedido_id, cantidad, precio_unitario_pen, variante_nombre, producto_id, producto_nombre, vendedor_id)
             VALUES (3, 1, 100.00, '', 1, 'Producto A', 2)"
        );
        $pdo->exec(
            'INSERT INTO comisiones (pedido_id, vendedor_id, monto_venta, porcentaje_comision, monto_comision, monto_vendedor)
             VALUES (3, 2, 100.00, 10.00, 10.00, 90.00)'
        );
    }

    public function testReporteMensualIncluyeMesesEnCero(): void
    {
        $filas = Vendedor::reporteMensual(self::VENDEDOR, 3);

        $this->assertCount(3, $filas);
        $actual = $filas[2];
        $this->assertEquals(date('Y-m'), $actual['mes']);
        $this->assertEquals(1, $actual['pedidos']);
        $this->assertEquals(2, $actual['unidades']);
        $this->assertEquals(200.0, $actual['vendido']);
        $this->assertEquals(20.0, $actual['comision']);
        $this->assertEquals(180.0, $actual['neto']);

        $haceDos = $filas[0];
        $this->assertEquals(1, $haceDos['pedidos']);
        $this->assertEquals(1, $haceDos['unidades']);
        $this->assertEquals(50.0, $haceDos['vendido']);

        $this->assertEquals(0, $filas[1]['pedidos']);
        $this->assertEquals(0.0, $filas[1]['neto']);
    }

    public function testTopProductosExcluyeCancelados(): void
    {
        $top = Vendedor::topProductos(self::VENDEDOR, 5);

        $this->assertCount(2, $top);
        $this->assertSame('Producto A', $top[0]['nombre']);
        $this->assertEquals(2, $top[0]['unidades']);
        $this->assertEquals(200.0, $top[0]['ingresos']);
        $this->assertSame('Producto B', $top[1]['nombre']);
    }

    public function testTopCompradoresAgrupaPorCliente(): void
    {
        $top = Vendedor::topCompradores(self::VENDEDOR, 5);

        $this->assertCount(2, $top);
        $this->assertSame('Cliente Demo', $top[0]['nombre']);
        $this->assertEquals(1, $top[0]['pedidos']);
        $this->assertEquals(200.0, $top[0]['total']);
        $this->assertSame('Cliente B2B', $top[1]['nombre']);
        $this->assertEquals(50.0, $top[1]['total']);
    }

    public function testOtroVendedorNoVeNada(): void
    {
        // Los meses se devuelven en cero para dibujar la serie completa.
        $meses = Vendedor::reporteMensual(3, 3);
        $this->assertCount(3, $meses);
        foreach ($meses as $fila) {
            $this->assertSame(0, $fila['pedidos']);
            $this->assertSame(0.0, $fila['vendido']);
        }

        $this->assertSame([], Vendedor::topProductos(3, 5));
        $this->assertSame([], Vendedor::topCompradores(3, 5));
    }
}
