<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\ProductoVendedor;
use PHPUnit\Framework\TestCase;

class PrecioVolumenVendedorTest extends TestCase
{
    private const VENDEDOR = 2;   // vendedor@example.com
    private const OTRO = 3;       // cliente@example.com
    private const SLUG = 'producto-volumen-test';

    protected function setUp(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec(
            'INSERT INTO productos (vendedor_id, slug, categoria, nombre, especificaciones, precio_pen, stock, activo, colores, imagen, stock_minimo)
             VALUES (' . self::VENDEDOR . ", '" . self::SLUG . "', 'Celulares', 'Producto volumen', '', 100.00, 10, 1, '', '', 1)"
        );
        // Producto de otro vendedor para comprobar el aislamiento.
        $pdo->exec(
            'INSERT INTO productos (vendedor_id, slug, categoria, nombre, especificaciones, precio_pen, stock, activo, colores, imagen, stock_minimo)
             VALUES (' . self::OTRO . ", 'ajeno', 'Celulares', 'Producto ajeno', '', 50.00, 5, 1, '', '', 1)"
        );
    }

    public function testCreaTramoYApareceEnLaFicha(): void
    {
        $r = ProductoVendedor::guardarTramo(self::VENDEDOR, self::SLUG, [
            'cantidad_minima' => 3,
            'cantidad_maxima' => 5,
            'precio_unitario' => 90,
        ]);

        $this->assertTrue($r['ok']);
        $tramos = ProductoVendedor::tramosDe(self::VENDEDOR, self::SLUG);
        $this->assertCount(1, $tramos);
        $this->assertEquals(3, $tramos[0]['cantidadMinima']);
        $this->assertEquals(5, $tramos[0]['cantidadMaxima']);
        $this->assertEquals(90.0, $tramos[0]['precioUnitario']);
    }

    public function testNoPermiteTocarProductoAjeno(): void
    {
        $r = ProductoVendedor::guardarTramo(self::OTRO, self::SLUG, [
            'cantidad_minima' => 2,
            'precio_unitario' => 10,
        ]);
        $this->assertFalse($r['ok']);
        $this->assertSame([], ProductoVendedor::tramosDe(self::OTRO, self::SLUG));
    }

    public function testRechazaRangoInvertido(): void
    {
        $r = ProductoVendedor::guardarTramo(self::VENDEDOR, self::SLUG, [
            'cantidad_minima' => 5,
            'cantidad_maxima' => 2,
            'precio_unitario' => 90,
        ]);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('máxima', (string) $r['error']);
    }

    public function testSoloUnTramoAbierto(): void
    {
        $primero = ProductoVendedor::guardarTramo(self::VENDEDOR, self::SLUG, [
            'cantidad_minima' => 10,
            'precio_unitario' => 80,
        ]);
        $segundo = ProductoVendedor::guardarTramo(self::VENDEDOR, self::SLUG, [
            'cantidad_minima' => 20,
            'precio_unitario' => 70,
        ]);

        $this->assertTrue($primero['ok']);
        $this->assertFalse($segundo['ok']);
        $this->assertStringContainsString('abierto', (string) $segundo['error']);
    }

    public function testEditaYEliminaTramoPropio(): void
    {
        ProductoVendedor::guardarTramo(self::VENDEDOR, self::SLUG, [
            'cantidad_minima' => 3,
            'precio_unitario' => 90,
        ]);
        $tramo = ProductoVendedor::tramosDe(self::VENDEDOR, self::SLUG)[0];

        $r = ProductoVendedor::guardarTramo(self::VENDEDOR, self::SLUG, [
            'id' => $tramo['id'],
            'cantidad_minima' => 4,
            'cantidad_maxima' => 9,
            'precio_unitario' => 85,
        ]);
        $this->assertTrue($r['ok']);
        $this->assertEquals(85.0, ProductoVendedor::tramosDe(self::VENDEDOR, self::SLUG)[0]['precioUnitario']);

        $this->assertFalse(ProductoVendedor::eliminarTramo(self::OTRO, self::SLUG, $tramo['id']));
        $this->assertTrue(ProductoVendedor::eliminarTramo(self::VENDEDOR, self::SLUG, $tramo['id']));
        $this->assertSame([], ProductoVendedor::tramosDe(self::VENDEDOR, self::SLUG));
    }
}
