<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Inventario;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminStockTest extends TestCase
{
    private const SLUG = 'admin-stock-test';

    private int $productoId;

    protected function setUp(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec("INSERT INTO categorias (nombre, slug) VALUES ('Celulares', 'celulares') ON CONFLICT (nombre) DO NOTHING");
        $this->productoId = (int) $pdo->query(
            "INSERT INTO productos
                (vendedor_id, slug, categoria, nombre, especificaciones, precio_pen, stock)
             VALUES (2, 'admin-stock-test', 'Celulares', 'Producto admin', '', 100, 10)
             RETURNING id"
        )->fetchColumn();
    }

    public function testFormulariosDeEdicionEnvianElStockOriginalMostrado(): void
    {
        $producto = $this->solicitar('productos', [], ['editar' => self::SLUG]);
        $this->assertSame('10', $this->input($producto['html'], 'stock_original'));

        $id = $this->crearVariante();
        $variante = $this->solicitar('variantes', [], ['p' => self::SLUG, 'editar-variante' => $id]);
        $this->assertSame('10', $this->input($variante['html'], 'stock_original'));
    }

    public function testFormularioDeProductoPrevioALaVentaNoReponeStockNiGuardaMetadatos(): void
    {
        $pagina = $this->solicitar('productos', [], ['editar' => self::SLUG]);
        $mostrado = $this->input($pagina['html'], 'stock');
        $this->assertSame('10', $mostrado);
        $datos = $this->datosProducto(['stock_original' => $mostrado, 'nombre' => 'No guardar', 'precio_pen' => 250]);
        $this->vender();
        $antes = $this->producto();
        $historial = $this->movimientos();

        $respuesta = $this->solicitar('productos/guardar', $datos);

        $this->assertSame(9, (int) $this->producto()['stock'], 'Un formulario antiguo no debe reponer la unidad vendida.');
        $this->assertSame($antes, $this->producto());
        $this->assertSame($historial, $this->movimientos());
        $this->assertSame([], $this->auditoria());
        $this->assertRecargar($respuesta);
    }

    public function testFormularioDeVariantePrevioALaVentaNoReponeStockNiGuardaMetadatos(): void
    {
        $id = $this->crearVariante();
        $pagina = $this->solicitar('variantes', [], ['p' => self::SLUG, 'editar-variante' => $id]);
        $mostrado = $this->input($pagina['html'], 'stock');
        $this->assertSame('10', $mostrado);
        $datos = $this->datosVariante($id, ['stock_original' => $mostrado, 'nombre_variante' => 'No guardar', 'precio_extra' => 50]);
        $this->vender($id);
        $antes = $this->variante($id);
        $historial = $this->movimientos();

        $respuesta = $this->solicitar('variantes/variantes/guardar', $datos);

        $this->assertSame(9, (int) $this->variante($id)['stock']);
        $this->assertSame(9, (int) $this->producto()['stock']);
        $this->assertSame($antes, $this->variante($id));
        $this->assertSame($historial, $this->movimientos());
        $this->assertSame([], $this->auditoria());
        $this->assertRecargar($respuesta);
    }

    #[DataProvider('originalesInvalidos')]
    public function testProductoYVarianteExigenUnStockOriginalEnteroValido(mixed $original): void
    {
        $id = $this->crearVariante();
        $producto = $this->producto();
        $variante = $this->variante($id);
        $datos = $this->datosProducto(['nombre' => 'No guardar', 'stock_original' => $original]);
        $datosVariante = $this->datosVariante($id, ['nombre_variante' => 'No guardar', 'stock_original' => $original]);
        if ($original === null) {
            unset($datos['stock_original'], $datosVariante['stock_original']);
        }

        $this->assertRecargar($this->solicitar('productos/guardar', $datos));
        $this->assertRecargar($this->solicitar('variantes/variantes/guardar', $datosVariante));
        $this->assertSame($producto, $this->producto());
        $this->assertSame($variante, $this->variante($id));
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
    }

    public static function originalesInvalidos(): array
    {
        return ['ausente' => [null], 'texto' => ['diez'], 'decimal' => ['10.5'], 'negativo' => ['-1'], 'array' => [[10]], 'excesivo' => ['10000000']];
    }

    public function testAjustesDeProductoRegistranEntradaSalidaYNoDuplicanElNoop(): void
    {
        $this->solicitar('productos/guardar', $this->datosProducto(['stock' => 12]));
        $this->assertSame(12, (int) $this->producto()['stock']);
        $this->solicitar('productos/guardar', $this->datosProducto(['stock' => 9, 'stock_original' => 12]));
        $this->solicitar('productos/guardar', $this->datosProducto(['stock' => 9, 'stock_original' => 9, 'nombre' => 'Edición válida']));

        $this->assertSame(9, (int) $this->producto()['stock']);
        $this->assertSame('Edición válida', $this->producto()['nombre']);
        $movimientos = $this->movimientos();
        $this->assertCount(2, $movimientos);
        $this->assertMovimiento($movimientos[0], 'entrada', 2);
        $this->assertMovimiento($movimientos[1], 'salida', 3);
    }

    public function testAjustesDeVarianteMuevenAmbosStocksConUnMovimientoPorCambio(): void
    {
        $id = $this->crearVariante();
        $this->solicitar('variantes/variantes/guardar', $this->datosVariante($id, ['stock' => 13]));
        $this->assertSame(13, (int) $this->producto()['stock']);
        $this->assertSame(13, (int) $this->variante($id)['stock']);
        $this->solicitar('variantes/variantes/guardar', $this->datosVariante($id, ['stock' => 8, 'stock_original' => 13]));
        $this->solicitar('variantes/variantes/guardar', $this->datosVariante($id, ['stock' => 8, 'stock_original' => 8, 'nombre_variante' => 'Edición válida']));

        $this->assertSame(8, (int) $this->producto()['stock']);
        $this->assertSame(8, (int) $this->variante($id)['stock']);
        $this->assertSame('Edición válida', $this->variante($id)['nombre_variante']);
        $movimientos = $this->movimientos();
        $this->assertCount(2, $movimientos);
        $this->assertMovimiento($movimientos[0], 'entrada', 3, $id);
        $this->assertMovimiento($movimientos[1], 'salida', 5, $id);
    }

    public function testNoAjustaStockGlobalConVariantesNiGuardaOtrosCampos(): void
    {
        $id = $this->crearVariante();
        $antes = $this->producto();
        $respuesta = $this->solicitar('productos/guardar', $this->datosProducto(['stock' => 12, 'nombre' => 'No guardar']));

        $this->assertSame($antes, $this->producto());
        $this->assertSame(10, (int) $this->variante($id)['stock']);
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
    }

    public function testAltaDeProductoRegistraElStockInicialComoEntrada(): void
    {
        $datos = $this->datosProducto(['id' => '', 'nombre' => 'Alta admin', 'stock' => 4]);
        unset($datos['stock_original']);
        $this->solicitar('productos/guardar', $datos);

        $nueva = $this->producto('alta-admin');
        $this->assertSame(4, (int) $nueva['stock']);
        $this->assertSame('JT-' . str_pad((string) $nueva['id'], 5, '0', STR_PAD_LEFT), $nueva['codigo_sku']);
        $movimientos = $this->movimientos((int) $nueva['id']);
        $this->assertCount(1, $movimientos);
        $this->assertMovimiento($movimientos[0], 'entrada', 4);
    }

    public function testEditarProductoIgnoraElSkuForjadoYElFormularioLoMuestraReadonly(): void
    {
        testPdo()->exec("UPDATE productos SET codigo_sku = 'SKU-HISTORICO' WHERE slug = 'admin-stock-test'");
        $pagina = $this->solicitar('productos', [], ['editar' => self::SLUG]);
        $dom = new DOMDocument();
        $dom->loadHTML($pagina['html'], LIBXML_NOERROR | LIBXML_NOWARNING);
        $sku = (new DOMXPath($dom))->query('//input[@name="codigo_sku"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $sku);
        $this->assertTrue($sku->hasAttribute('readonly'));
        $this->assertSame('SKU-HISTORICO', $sku->getAttribute('value'));

        $this->solicitar('productos/guardar', $this->datosProducto(['codigo_sku' => 'SKU-FORJADO', 'stock' => 12]));

        $this->assertSame('SKU-HISTORICO', $this->producto()['codigo_sku']);
        $this->assertSame(12, (int) $this->producto()['stock']);
        $movimientos = $this->movimientos();
        $this->assertCount(1, $movimientos);
        $this->assertMovimiento($movimientos[0], 'entrada', 2);
    }

    public function testAltaDeVarianteSumaElStockInicialAlProductoYRegistraEntrada(): void
    {
        $datos = $this->datosVariante(0, ['stock' => 4]);
        unset($datos['stock_original']);
        $this->solicitar('variantes/variantes/guardar', $datos);

        $variantes = $this->variantes();
        $this->assertCount(1, $variantes);
        $this->assertSame(4, (int) $variantes[0]['stock']);
        $this->assertSame(14, (int) $this->producto()['stock']);
        $movimientos = $this->movimientos();
        $this->assertCount(1, $movimientos);
        $this->assertMovimiento($movimientos[0], 'entrada', 4, (int) $variantes[0]['id']);
    }

    public function testAltasConAjusteRechazadoReviertenFilasYAuditoria(): void
    {
        // El límite de Inventario::ajustar es 100 000 unidades por movimiento.
        $producto = $this->solicitar('productos/guardar', $this->datosProducto(['id' => '', 'nombre' => 'Alta inválida', 'stock' => 100001]));
        $variante = $this->solicitar('variantes/variantes/guardar', $this->datosVariante(0, ['stock' => 100001]));

        $this->assertSame('error', $producto['flash']['tipo'] ?? null);
        $this->assertSame('error', $variante['flash']['tipo'] ?? null);
        $this->assertSame(1, (int) testPdo()->query('SELECT COUNT(*) FROM productos')->fetchColumn());
        $this->assertSame([], $this->variantes());
        $this->assertSame(10, (int) $this->producto()['stock']);
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
    }

    public function testSalidaDeVarianteSinStockGlobalRevierteMetadatosYMovimiento(): void
    {
        $id = $this->crearVariante(20);
        $antes = $this->variante($id);
        $respuesta = $this->solicitar('variantes/variantes/guardar', $this->datosVariante($id, [
            'stock' => 0, 'stock_original' => 20, 'nombre_variante' => 'No guardar',
        ]));

        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertSame($antes, $this->variante($id));
        $this->assertSame(10, (int) $this->producto()['stock']);
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
    }

    #[DataProvider('stocksInvalidos')]
    public function testStockInvalidoNoSeTruncaNiGuardaMetadatos(mixed $stock): void
    {
        $id = $this->crearVariante();
        $producto = $this->producto();
        $variante = $this->variante($id);
        $respuestaProducto = $this->solicitar('productos/guardar', $this->datosProducto(['stock' => $stock, 'nombre' => 'No guardar']));
        $respuestaVariante = $this->solicitar('variantes/variantes/guardar', $this->datosVariante($id, ['stock' => $stock, 'nombre_variante' => 'No guardar']));

        $this->assertSame('error', $respuestaProducto['flash']['tipo'] ?? null);
        $this->assertSame('error', $respuestaVariante['flash']['tipo'] ?? null);
        $this->assertSame($producto, $this->producto());
        $this->assertSame($variante, $this->variante($id));
        $this->assertSame([], $this->movimientos());
    }

    public static function stocksInvalidos(): array
    {
        return ['texto' => ['diez'], 'decimal' => ['1.5'], 'negativo' => ['-1'], 'array' => [[1]], 'excesivo' => ['10000000']];
    }

    public function testVendedorNoPuedeEditarProductoNiVarianteAjenos(): void
    {
        testPdo()->exec('UPDATE productos SET vendedor_id = NULL');
        $id = $this->crearVariante();
        $producto = $this->producto();
        $variante = $this->variante($id);
        $this->solicitar('productos/guardar', $this->datosProducto(['stock' => 12]), [], 2);
        $this->solicitar('variantes/variantes/guardar', $this->datosVariante($id, ['stock' => 12]), [], 2);

        $this->assertSame($producto, $this->producto());
        $this->assertSame($variante, $this->variante($id));
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
    }

    #[DataProvider('vendedoresInvalidos')]
    public function testAsignacionDeVendedorInvalidoNoGuardaProductoNiStock(int $vendedor, string $cambio): void
    {
        if ($cambio !== '') {
            testPdo()->exec($cambio);
        }
        $antes = $this->producto();
        $respuesta = $this->solicitar('productos/guardar', $this->datosProducto([
            'vendedor_id' => $vendedor, 'stock' => 12, 'nombre' => 'No guardar',
        ]));

        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertSame($antes, $this->producto());
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
    }

    public static function vendedoresInvalidos(): array
    {
        return [
            'admin' => [1, ''], 'cliente' => [3, ''], 'inexistente' => [999999, ''],
            'inactivo' => [2, 'UPDATE users SET activo = 0 WHERE id = 2'],
            'eliminado' => [2, 'UPDATE users SET deleted_at = NOW() WHERE id = 2'],
        ];
    }

    public function testAdministradorPuedeAsignarLaPlataformaSinPerderElAjuste(): void
    {
        $this->solicitar('productos/guardar', $this->datosProducto(['vendedor_id' => '', 'stock' => 12]));

        $this->assertNull($this->producto()['vendedor_id']);
        $this->assertSame(12, (int) $this->producto()['stock']);
        $movimientos = $this->movimientos();
        $this->assertCount(1, $movimientos);
        $this->assertMovimiento($movimientos[0], 'entrada', 2);
    }

    public function testImagenInvalidaImpideGuardarMetadatosYStock(): void
    {
        $antes = $this->producto();
        $respuesta = $this->solicitar('productos/guardar', $this->datosProducto(['stock' => 12, 'nombre' => 'No guardar']), [], 1, [
            'imagen_archivo' => ['error' => UPLOAD_ERR_PARTIAL],
        ]);

        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertSame($antes, $this->producto());
        $this->assertSame([], $this->movimientos());
    }

    #[DataProvider('referenciasInvalidas')]
    public function testReferenciasInvalidasDevuelvenErrorSinGuardarMetadatosNiStock(array $referencia): void
    {
        $antes = $this->producto();
        $respuesta = $this->solicitar('productos/guardar', $this->datosProducto(array_merge([
            'nombre' => 'No guardar', 'stock' => 12,
        ], $referencia)));

        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertSame($antes, $this->producto());
        $this->assertSame([], $this->movimientos());
        $this->assertSame([], $this->auditoria());
    }

    public static function referenciasInvalidas(): array
    {
        return [
            'categoria inexistente' => [['categoria' => 'inexistente']],
            'marca inexistente' => [['marca_id' => 999999]],
            'marca mal formada' => [['marca_id' => '1xyz']],
            'garantia inexistente' => [['garantia_id' => 999999]],
        ];
    }

    #[DataProvider('nombresCategoriaInvalidos')]
    public function testCategoriaExigeNombreNoVacioDeHasta80Caracteres(mixed $nombre): void
    {
        $antes = testPdo()->query('SELECT * FROM categorias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $respuesta = $this->solicitar('categorias/guardar', ['id' => 0, 'nombre' => $nombre]);

        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertSame($antes, testPdo()->query('SELECT * FROM categorias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
        $this->assertSame([], $this->auditoria());
    }

    public static function nombresCategoriaInvalidos(): array
    {
        return ['vacio' => [''], 'espacios' => ['   '], 'excesivo' => [str_repeat('a', 81)], 'array' => [['Celulares']]];
    }

    public function testCategoriaDuplicadaDevuelveFlashSinErrorFatalNiAuditoria(): void
    {
        $respuesta = $this->solicitar('categorias/guardar', ['id' => 0, 'nombre' => 'Celulares']);

        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertSame(1, (int) testPdo()->query("SELECT COUNT(*) FROM categorias WHERE nombre = 'Celulares'")->fetchColumn());
        $this->assertSame([], $this->auditoria());
    }

    public function testCategoriaDe80CaracteresSeCreaYSeGuardaPorSuNombreCanonico(): void
    {
        $nombre = str_repeat('a', 80);
        $respuesta = $this->solicitar('categorias/guardar', ['id' => 0, 'nombre' => $nombre]);
        $this->assertNotSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->solicitar('productos/guardar', $this->datosProducto(['categoria' => $nombre]));

        $this->assertSame($nombre, $this->producto()['categoria']);
        $this->assertSame(10, (int) $this->producto()['stock']);
        $this->assertSame([], $this->movimientos());
    }

    private function datosProducto(array $cambios = []): array
    {
        return array_replace([
            'id' => self::SLUG, 'nombre' => 'Producto admin', 'categoria' => 'celulares',
            'precio_pen' => 100, 'stock' => 10, 'stock_original' => 10, 'stock_minimo' => 0,
            'activo' => 1, 'imagen' => 'img/producto-default.png', 'vendedor_id' => 2,
        ], $cambios);
    }

    private function datosVariante(int $id, array $cambios = []): array
    {
        return array_replace([
            'p' => self::SLUG, 'id' => $id, 'nombre_variante' => 'Azul', 'precio_extra' => 0,
            'stock' => 10, 'stock_original' => 10, 'color' => '#0000FF', 'activo' => 1,
        ], $cambios);
    }

    private function solicitar(string $ruta, array $post = [], array $get = [], int $adminId = 1, array $files = []): array
    {
        // El controlador redirige con exit: cada petición usa un proceso real sin servidor ni mocks.
        $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
            . '\Security\Proteccion::iniciarSesion(); $_SESSION = [];'
            . '\Model\Administrador::iniciarSesion(' . var_export($adminId === 1 ? 'admin' : 'vendedor', true)
            . ', ' . var_export($adminId === 1 ? 'admin' : 'vendedor', true) . ');'
            . '$_SERVER["REQUEST_METHOD"] = ' . var_export($post === [] ? 'GET' : 'POST', true) . ';'
            . '$_GET = ' . var_export($get, true) . '; $_POST = ' . var_export($post, true) . ';'
            . '$_FILES = ' . var_export($files, true) . '; ob_start();'
            . 'register_shutdown_function(function () { echo json_encode(['
            . '"html" => ob_get_clean(), "flash" => $_SESSION["admin_flash"] ?? null,'
            . '"transaccion" => conexion()->inTransaction()]); });'
            . '(new \Controller\AdminController())->atender(' . var_export($ruta, true) . ');';
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proceso);
        $salida = stream_get_contents($pipes[1]);
        $errores = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proceso), $errores . $salida);
        $respuesta = json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($respuesta['transaccion'], 'La petición dejó una transacción abierta.');
        return $respuesta;
    }

    private function input(string $html, string $nombre): string
    {
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $nodos = (new DOMXPath($dom))->query('//input[@name="' . $nombre . '"]');
        $this->assertCount(1, $nodos, 'Falta el campo ' . $nombre . ' en el formulario.');
        return $nodos->item(0)->getAttribute('value');
    }

    private function assertRecargar(array $respuesta): void
    {
        $this->assertSame('error', $respuesta['flash']['tipo'] ?? null);
        $this->assertStringContainsString('Recarga', $respuesta['flash']['mensaje'] ?? '');
    }

    private function assertMovimiento(array $movimiento, string $tipo, int $cantidad, ?int $varianteId = null): void
    {
        $this->assertSame($tipo, $movimiento['tipo']);
        $this->assertSame($cantidad, (int) $movimiento['cantidad']);
        $this->assertSame($varianteId, $movimiento['variante_id'] === null ? null : (int) $movimiento['variante_id']);
        $this->assertNull($movimiento['pedido_id']);
        $this->assertStringContainsString('Ajuste manual', $movimiento['motivo']);
    }

    private function crearVariante(int $stock = 10): int
    {
        $stmt = testPdo()->prepare(
            "INSERT INTO producto_variantes (producto_id, nombre_variante, stock, color) VALUES (?, 'Azul', ?, '#0000FF') RETURNING id"
        );
        $stmt->execute([$this->productoId, $stock]);
        return (int) $stmt->fetchColumn();
    }

    private function vender(?int $varianteId = null): void
    {
        $pdo = testPdo();
        $pedidoId = (int) $pdo->query('INSERT INTO pedidos (cliente_id) VALUES (3) RETURNING id')->fetchColumn();
        $pdo->prepare('INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id, vendedor_id, cantidad) VALUES (?, ?, ?, 2, 1)')
            ->execute([$pedidoId, $this->productoId, $varianteId]);
        $pdo->beginTransaction();
        try {
            Inventario::descontar($pdo, [[
                'producto' => ['id' => $this->productoId, 'nombre' => 'Producto admin', 'vendedorId' => 2],
                'varianteId' => $varianteId, 'varianteNombre' => 'Azul', 'cantidad' => 1,
            ]], $pedidoId);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function producto(string $slug = self::SLUG): array
    {
        $stmt = testPdo()->prepare('SELECT * FROM productos WHERE slug = ?');
        $stmt->execute([$slug]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($fila);
        return $fila;
    }

    private function variante(int $id): array
    {
        $stmt = testPdo()->prepare('SELECT * FROM producto_variantes WHERE id = ?');
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($fila);
        return $fila;
    }

    private function variantes(): array
    {
        $stmt = testPdo()->prepare('SELECT * FROM producto_variantes WHERE producto_id = ? ORDER BY id');
        $stmt->execute([$this->productoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function movimientos(?int $productoId = null): array
    {
        $stmt = testPdo()->prepare('SELECT * FROM movimientos_inventario WHERE producto_id = ? ORDER BY id');
        $stmt->execute([$productoId ?? $this->productoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function auditoria(): array
    {
        return testPdo()->query('SELECT * FROM auditoria_admin ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    }
}
