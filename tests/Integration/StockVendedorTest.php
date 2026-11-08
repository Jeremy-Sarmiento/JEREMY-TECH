<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Inventario;
use Model\ProductoVendedor;
use PHPUnit\Framework\TestCase;

class StockVendedorTest extends TestCase
{
    private const VENDEDOR = 2;
    private const OTRO = 3;
    private const SLUG = 'stock-vendedor-test';

    private int $productoId;

    protected function setUp(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec("INSERT INTO categorias (nombre, slug) VALUES ('Celulares', 'celulares') ON CONFLICT (nombre) DO NOTHING");
        $this->productoId = (int) $pdo->query(
            "INSERT INTO productos
                (vendedor_id, slug, categoria, nombre, especificaciones, precio_pen, stock)
             VALUES (2, 'stock-vendedor-test', 'Celulares', 'Producto stock', '', 100, 10)
             RETURNING id"
        )->fetchColumn();
    }

    public function testStockRapidoRechazaFormularioPrevioALaVentaSinRevivirUnidades(): void
    {
        $mostrado = (int) ProductoVendedor::porSlug(self::VENDEDOR, self::SLUG)['stock'];
        $this->vender();
        $historial = $this->movimientos();

        $this->assertFalse(ProductoVendedor::actualizarStock(self::VENDEDOR, self::SLUG, 10, $mostrado, $error));
        $this->assertStringContainsString('Recarga', (string) $error);
        $this->assertSame(9, $this->stockProducto());
        $this->assertSame($historial, $this->movimientos());
    }

    public function testEditarProductoRechazaStockAnteriorYNoGuardaOtrosCampos(): void
    {
        $datos = $this->datosProducto();
        $datos['nombre'] = 'No debe guardarse';
        $this->vender();
        $historial = $this->movimientos();

        $resultado = ProductoVendedor::guardar(self::VENDEDOR, $datos, self::SLUG);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Recarga', $resultado['error']);
        $this->assertSame(9, $this->stockProducto());
        $this->assertSame('Producto stock', ProductoVendedor::porSlug(self::VENDEDOR, self::SLUG)['nombre']);
        $this->assertSame($historial, $this->movimientos());
    }

    public function testStockOriginalEsObligatorioEnLasDosRutasDeProducto(): void
    {
        $this->assertFalse(ProductoVendedor::actualizarStock(self::VENDEDOR, self::SLUG, 11));
        $datos = $this->datosProducto();
        unset($datos['stock_original']);
        $this->assertFalse(ProductoVendedor::guardar(self::VENDEDOR, $datos, self::SLUG)['ok']);
        $this->assertSame(10, $this->stockProducto());
        $this->assertSame([], $this->movimientos());
    }

    public function testAjustesLegitimosDeAmbasRutasConservanHistorialYNoDuplicanElNoop(): void
    {
        $this->assertTrue(ProductoVendedor::actualizarStock(self::VENDEDOR, self::SLUG, 12, 10));
        $datos = $this->datosProducto();
        $datos['stock'] = 9;
        $this->assertTrue(ProductoVendedor::guardar(self::VENDEDOR, $datos, self::SLUG)['ok']);
        $this->assertTrue(ProductoVendedor::actualizarStock(self::VENDEDOR, self::SLUG, 9, 9));

        $this->assertSame(9, $this->stockProducto());
        $movimientos = $this->movimientos();
        $this->assertCount(2, $movimientos);
        $this->assertSame('entrada', $movimientos[0]['tipo']);
        $this->assertSame(2, (int) $movimientos[0]['cantidad']);
        $this->assertSame('salida', $movimientos[1]['tipo']);
        $this->assertSame(3, (int) $movimientos[1]['cantidad']);
    }

    public function testNoAjustaTotalConVariantesSinElegirVariante(): void
    {
        $id = $this->crearVariante();
        $this->assertFalse(ProductoVendedor::actualizarStock(self::VENDEDOR, self::SLUG, 12, 10));
        $datos = $this->datosProducto();
        $datos['stock'] = 12;
        $this->assertFalse(ProductoVendedor::guardar(self::VENDEDOR, $datos, self::SLUG)['ok']);
        $this->assertSame(10, $this->stockProducto());
        $this->assertSame(10, $this->stockVariante($id));
        $this->assertSame([], $this->movimientos());
    }

    public function testVarianteRechazaStockPrevioALaVentaYExigeOriginal(): void
    {
        $id = $this->crearVariante();
        $datos = $this->datosVariante($id);
        $this->vender($id);
        $historial = $this->movimientos();

        $resultado = ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos);
        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Recarga', $resultado['error']);
        unset($datos['stock_original']);
        $this->assertFalse(ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos)['ok']);
        $this->assertSame(9, $this->stockProducto());
        $this->assertSame(9, $this->stockVariante($id));
        $this->assertSame($historial, $this->movimientos());
    }

    public function testAjustarVarianteMueveAmbosStocksYRegistraUnSoloMovimiento(): void
    {
        $id = $this->crearVariante();
        $datos = $this->datosVariante($id);
        $datos['stock'] = 13;

        $this->assertTrue(ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos)['ok']);
        $this->assertSame(13, $this->stockProducto());
        $this->assertSame(13, $this->stockVariante($id));
        $movimientos = $this->movimientos();
        $this->assertCount(1, $movimientos);
        $this->assertSame('entrada', $movimientos[0]['tipo']);
        $this->assertSame(3, (int) $movimientos[0]['cantidad']);
        $this->assertSame($id, (int) $movimientos[0]['variante_id']);
    }

    public function testNuevaVarianteConStockRegistraEntradaEnAmbosStocks(): void
    {
        $datos = $this->datosVariante(0);
        unset($datos['stock_original']);
        $datos['stock'] = 4;

        $this->assertTrue(ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos)['ok']);
        $variantes = ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG);
        $this->assertCount(1, $variantes);
        $this->assertSame(4, $variantes[0]['stock']);
        $this->assertSame(14, $this->stockProducto());
        $this->assertCount(1, $this->movimientos());
    }

    public function testSalidaDeVarianteSinStockGlobalNoModificaCamposNiHistorial(): void
    {
        $id = $this->crearVariante(20);
        $datos = $this->datosVariante($id);
        $datos['stock_original'] = 20;
        $datos['stock'] = 0;
        $datos['nombre_variante'] = 'No debe guardarse';

        $this->assertFalse(ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos)['ok']);
        $this->assertSame(10, $this->stockProducto());
        $this->assertSame(20, $this->stockVariante($id));
        $this->assertSame('Azul', ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG)[0]['nombre_variante']);
        $this->assertSame([], $this->movimientos());
    }

    public function testAltaDeVarianteConAjusteInvalidoRevierteLaFilaNueva(): void
    {
        $datos = $this->datosVariante(0);
        $datos['stock'] = 100001; // Inventario limita cada ajuste a 100 000 unidades.

        $this->assertFalse(ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos)['ok']);
        $this->assertSame([], ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG));
        $this->assertSame(10, $this->stockProducto());
        $this->assertSame([], $this->movimientos());
        $this->assertFalse(conexion()->inTransaction());
    }

    public function testAislamientoDelDuenoEnTodasLasEscrituras(): void
    {
        $id = $this->crearVariante();
        $this->assertFalse(ProductoVendedor::actualizarStock(self::OTRO, self::SLUG, 12, 10));
        $this->assertFalse(ProductoVendedor::guardar(self::OTRO, $this->datosProducto(), self::SLUG)['ok']);
        $this->assertFalse(ProductoVendedor::guardarVariante(self::OTRO, self::SLUG, $this->datosVariante($id))['ok']);
        $this->assertFalse(ProductoVendedor::cambiarEstadoVariante(self::OTRO, self::SLUG, $id));
        $this->assertFalse(ProductoVendedor::eliminarVariante(self::OTRO, self::SLUG, $id));
        $this->assertSame(10, $this->stockProducto());
        $this->assertSame(10, $this->stockVariante($id));
        $this->assertSame([], $this->movimientos());
    }

    public function testToggleVarianteYBorradoSinHistorialFuncionanEnPostgresql(): void
    {
        $id = $this->crearVariante();
        $this->assertTrue(ProductoVendedor::cambiarEstadoVariante(self::VENDEDOR, self::SLUG, $id));
        $this->assertSame(0, ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG)[0]['activo']);
        $this->assertTrue(ProductoVendedor::cambiarEstadoVariante(self::VENDEDOR, self::SLUG, $id));
        $this->assertSame(1, ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG)[0]['activo']);
        $this->assertTrue(ProductoVendedor::eliminarVariante(self::VENDEDOR, self::SLUG, $id));
        $this->assertSame([], ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG));
    }

    public function testNoBorraVarianteVendidaNiVarianteConSoloDetalleHistorico(): void
    {
        $id = $this->crearVariante();
        $this->vender($id);
        $historial = $this->movimientos();
        $this->assertFalse(ProductoVendedor::eliminarVariante(self::VENDEDOR, self::SLUG, $id, $error));
        $this->assertStringContainsString('Oculta', (string) $error);
        $this->assertSame($historial, $this->movimientos());

        $otroId = $this->crearVariante(0, 'Detalle antiguo');
        $this->crearDetalle($otroId);
        $this->assertFalse(ProductoVendedor::eliminarVariante(self::VENDEDOR, self::SLUG, $otroId, $error));
        $this->assertStringContainsString('Oculta', (string) $error);
        $this->assertCount(2, ProductoVendedor::variantesDe(self::VENDEDOR, self::SLUG));
        $this->assertFalse(conexion()->inTransaction());
    }

    public function testNoBorraVarianteConSoloMovimientoDeAjuste(): void
    {
        $id = $this->crearVariante(0);
        $datos = $this->datosVariante($id);
        $datos['stock_original'] = 0;
        $datos['stock'] = 2;
        $this->assertTrue(ProductoVendedor::guardarVariante(self::VENDEDOR, self::SLUG, $datos)['ok']);
        $historial = $this->movimientos();

        $this->assertFalse(ProductoVendedor::eliminarVariante(self::VENDEDOR, self::SLUG, $id, $error));
        $this->assertStringContainsString('Oculta', (string) $error);
        $this->assertSame($historial, $this->movimientos());
        $this->assertSame(2, $this->stockVariante($id));
    }

    public function testAltaGeneraSkuJTAutomaticoAunqueElPostIntenteElegirlo(): void
    {
        $datos = $this->datosProducto();
        $datos['nombre'] = 'Alta vendedor';
        $datos['codigo_sku'] = 'MANUAL';
        $resultado = ProductoVendedor::guardar(self::VENDEDOR, $datos);
        $this->assertTrue($resultado['ok'], $resultado['error'] ?? '');
        $producto = ProductoVendedor::porSlug(self::VENDEDOR, $resultado['slug']);
        $this->assertSame(sprintf('JT-%05d', $producto['id']), $producto['codigo_sku']);
    }

    public function testEdicionConSkuForjadoConservaElCodigoActual(): void
    {
        testPdo()->exec("UPDATE productos SET codigo_sku = 'JT-01010'");
        $datos = $this->datosProducto();
        $datos['codigo_sku'] = 'MANUAL';
        $resultado = ProductoVendedor::guardar(self::VENDEDOR, $datos, self::SLUG);
        $this->assertTrue($resultado['ok'], $resultado['error'] ?? '');
        $this->assertSame('JT-01010', ProductoVendedor::porSlug(self::VENDEDOR, self::SLUG)['codigo_sku']);
    }

    public function testVendedorPuedeGuardarUnaCategoriaDe80Caracteres(): void
    {
        $categoria = str_repeat('ñ', 80);
        testPdo()->prepare('INSERT INTO categorias (nombre, slug) VALUES (?, ?)')->execute([$categoria, 'larga']);
        $datos = $this->datosProducto();
        $datos['categoria'] = $categoria;
        $resultado = ProductoVendedor::guardar(self::VENDEDOR, $datos, self::SLUG);
        $this->assertTrue($resultado['ok'], $resultado['error'] ?? '');
        $this->assertSame($categoria, ProductoVendedor::porSlug(self::VENDEDOR, self::SLUG)['categoria']);
    }

    private function datosProducto(): array
    {
        $producto = ProductoVendedor::porSlug(self::VENDEDOR, self::SLUG);
        return array_merge($producto, ['stock_original' => $producto['stock']]);
    }

    private function datosVariante(int $id): array
    {
        return [
            'id' => $id, 'nombre_variante' => 'Azul', 'precio_extra' => 0,
            'stock' => 10, 'stock_original' => 10, 'color' => '#0000FF', 'activo' => 1,
        ];
    }

    private function crearVariante(int $stock = 10, string $nombre = 'Azul'): int
    {
        $stmt = testPdo()->prepare(
            'INSERT INTO producto_variantes (producto_id, nombre_variante, stock) VALUES (?, ?, ?) RETURNING id'
        );
        $stmt->execute([$this->productoId, $nombre, $stock]);
        return (int) $stmt->fetchColumn();
    }

    private function crearDetalle(?int $varianteId): int
    {
        $pdo = testPdo();
        $pedidoId = (int) $pdo->query('INSERT INTO pedidos (cliente_id) VALUES (3) RETURNING id')->fetchColumn();
        $pdo->prepare(
            'INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id, vendedor_id, cantidad)
             VALUES (?, ?, ?, 2, 1)'
        )->execute([$pedidoId, $this->productoId, $varianteId]);
        return $pedidoId;
    }

    private function vender(?int $varianteId = null): void
    {
        $pedidoId = $this->crearDetalle($varianteId);
        $pdo = testPdo();
        $pdo->beginTransaction();
        try {
            Inventario::descontar($pdo, [[
                'producto' => ['id' => $this->productoId, 'nombre' => 'Producto stock', 'vendedorId' => self::VENDEDOR],
                'varianteId' => $varianteId, 'varianteNombre' => 'Azul', 'cantidad' => 1,
            ]], $pedidoId);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function stockProducto(): int
    {
        return (int) ProductoVendedor::porSlug(self::VENDEDOR, self::SLUG)['stock'];
    }

    private function stockVariante(int $id): int
    {
        $stmt = testPdo()->prepare('SELECT stock FROM producto_variantes WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    private function movimientos(): array
    {
        $stmt = testPdo()->prepare('SELECT * FROM movimientos_inventario WHERE producto_id = ? ORDER BY id');
        $stmt->execute([$this->productoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
