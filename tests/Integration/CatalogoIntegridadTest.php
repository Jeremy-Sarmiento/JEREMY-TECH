<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Carrito;
use Model\Cuenta;
use Model\Inventario;
use Model\Producto;
use PHPUnit\Framework\TestCase;

class CatalogoIntegridadTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        testPdo()->exec("INSERT INTO categorias (nombre, slug) VALUES
            ('Celulares', 'celulares'), ('Monitores', 'monitores') ON CONFLICT (nombre) DO NOTHING");
        testPdo()->exec("INSERT INTO productos
            (id, slug, categoria, nombre, especificaciones, precio_pen, stock, vendedor_id)
            VALUES (1, 'plataforma', 'Celulares', 'Plataforma', '', 100, 10, NULL),
                   (2, 'monitor', 'Monitores', 'Monitor', '', 200, 10, 2),
                   (3, 'sin-permiso', 'Monitores', 'Sin permiso', '', 300, 10, 3)");
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCategoriaNuevaSeAgrupaYSeFiltraSinCambiarCodigo(): void
    {
        $resultado = Producto::catalogoPaginado();
        $this->assertArrayHasKey('Monitores', $resultado['gruposPorCategoria']);
        $this->assertSame([2], array_column(Producto::catalogoFiltrado(['categoria' => 'Monitores']), 'id'));
    }

    public function testUnaCategoriaNumericaTambienSeAgrupaYSeFiltra(): void
    {
        testPdo()->exec("INSERT INTO categorias (nombre, slug) VALUES ('123', '123')");
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones)
            VALUES (4, 'numerico', '123', 'Producto numerico', '')");
        $resultado = Producto::catalogoPaginado(['categoria' => '123']);
        $this->assertSame(4, $resultado['gruposPorCategoria']['123'][0][0]['id'] ?? null);
        $this->assertStringContainsString('Producto numerico', $this->renderizar('catalogo', ['categoria' => '123']));
    }

    public function testCatalogoNoMezclaOfertasDeVendedoresDistintos(): void
    {
        testPdo()->exec("INSERT INTO users (id, name, email, usuario, password, rol, activo, created_at)
            VALUES (18, 'Empresa', 'empresa@example.com', 'empresa', '!', 'vendedor', 1, NOW())");
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock, vendedor_id)
            VALUES (10, 'mismo-2', 'Celulares', 'Mismo', '', 100, 5, 2),
                   (11, 'mismo-18', 'Celulares', 'Mismo', '', 120, 7, 18)");

        $grupos = Producto::catalogoPaginado(['categoria' => 'Celulares'])['gruposPorCategoria']['Celulares'] ?? [];
        $mismos = array_values(array_filter(
            $grupos,
            static fn (array $grupo): bool => ($grupo[0]['nombre'] ?? '') === 'Mismo'
        ));

        $this->assertCount(2, $mismos);
        $this->assertNotSame($mismos[0][0]['vendedorId'], $mismos[1][0]['vendedorId']);
    }

    public function testTarjetaMuestraSoloLaOpcionActivaConStockYPrecio(): void
    {
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (10, 'telefono-256', 'Celulares', 'Telefono', 'Base | 256GB', 1000, 10),
                   (11, 'telefono-512', 'Celulares', 'Telefono', 'Base | 512GB', 1500, 8)");
        testPdo()->exec("INSERT INTO producto_variantes
                (id, producto_id, nombre_variante, precio_extra, stock, color, activo)
            VALUES (20, 10, 'Negro', 100, 4, '#111111', 1),
                   (21, 10, 'Azul', 0, 6, '#1F3A5F', 1)");

        $html = $this->renderizar('catalogo', ['categoria' => 'Celulares']);

        // Solo se pinta la primera variante activa (Azul, por orden alfabético).
        $this->assertStringContainsString('256GB · Azul', $html);
        $this->assertStringContainsString("agregarAlCarrito('10', 1, 21)", $html);
        $this->assertStringContainsString('PEN 1,000.00', $html);
        $this->assertStringContainsString('Disponible: 6', $html);
        $this->assertStringNotContainsString('256GB · Negro', $html);
        $this->assertStringNotContainsString("agregarAlCarrito('11'", $html);
        $this->assertStringNotContainsString('data-indice', $html);
    }

    public function testCategoriaAdministrableSeRenderizaEscapadaYSePuedeFiltrar(): void
    {
        $categoria = 'Pantallas <img src=x onerror=alert(1)>';
        testPdo()->prepare('INSERT INTO categorias (nombre, slug) VALUES (?, ?)')->execute([$categoria, 'pantallas']);
        testPdo()->prepare("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (4, 'pantalla', ?, 'Pantalla segura', '', 100, 1)")->execute([$categoria]);

        $html = $this->renderizar('catalogo', ['categoria' => mb_strtolower($categoria)]);
        $this->assertStringContainsString('<h2>' . htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') . '</h2>', $html);
        $this->assertStringContainsString('Pantalla segura', $html);
        $this->assertStringNotContainsString($categoria, $html);
        $this->assertStringNotContainsString('Plataforma', $html);
    }

    public function testFichaPublicaNoSeRenderizaParaUnPropietarioCliente(): void
    {
        $this->assertSame('', $this->renderizar('detalle', ['slug' => 'sin-permiso']));
        $this->assertStringContainsString('Monitor', $this->renderizar('detalle', ['slug' => 'monitor']));
    }

    public function testPublicacionesDeClienteNoSeVendenNiSeRecomiendan(): void
    {
        $this->assertSame([1, 2], array_column(Producto::catalogoFiltrado(), 'id'));
        $this->assertSame([], Producto::buscar('Sin permiso'));
        $this->assertNull(Producto::precioDe(3, null, 1));
        $this->assertSame([], Producto::recomendados(2));
        Carrito::agregar('3', 1);
        $this->assertSame([], Carrito::items());
    }

    public function testCambioDeRolRetiraLaPublicacionPeroConservaElProducto(): void
    {
        testPdo()->exec("UPDATE users SET rol = 'cliente' WHERE id = 2");
        $this->assertSame([1], array_column(Producto::catalogoFiltrado(), 'id'));
        $this->assertNull(Producto::precioDe(2, null, 1));
        $this->assertNotNull(Producto::porId(2));
    }

    public function testVendedorInactivoODadoDeBajaTampocoPuedeVender(): void
    {
        testPdo()->exec('UPDATE users SET activo = 0 WHERE id = 2');
        $this->assertNull(Producto::precioDe(2, null, 1));
        testPdo()->exec('UPDATE users SET activo = 1, deleted_at = NOW() WHERE id = 2');
        $this->assertSame([1], array_column(Producto::catalogoFiltrado(), 'id'));
    }

    public function testCheckoutNoCompraUnCarritoGuardadoTrasRevocarElRol(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        Carrito::agregar('2', 1);
        testPdo()->exec("UPDATE users SET rol = 'cliente' WHERE id = 2");
        $this->assertNull(Carrito::checkout(['modalidad' => 'tienda', 'metodo' => 'Tarjeta']));
        $this->assertSame(0, (int) testPdo()->query('SELECT count(*) FROM pedidos')->fetchColumn());
        $this->assertSame(10, (int) testPdo()->query('SELECT stock FROM productos WHERE id = 2')->fetchColumn());
    }

    public function testCarritoInvitadoNoCuentaLineasIndisponiblesNiBorraLaSesion(): void
    {
        Carrito::agregar('2', 2);
        testPdo()->exec('UPDATE users SET activo = 0 WHERE id = 2');
        $this->assertSame([], Carrito::items());
        $this->assertSame(0, Carrito::totalItems());
        $this->assertTrue(Carrito::estaVacio());
        $this->assertSame(['2' => 2], $_SESSION['carrito']);
        testPdo()->exec('UPDATE users SET activo = 1 WHERE id = 2');
        $this->assertSame(['2' => 2], Carrito::items());
    }

    public function testCarritoGuardadoNoCuentaLineasIndisponiblesPeroLasConserva(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        Carrito::agregar('2', 2);
        testPdo()->exec("UPDATE users SET rol = 'cliente' WHERE id = 2");
        $this->assertSame([], Carrito::items());
        $this->assertSame(0, Carrito::totalItems());
        $this->assertTrue(Carrito::estaVacio());
        $this->assertSame(2, (int) testPdo()->query('SELECT cantidad FROM carrito_items')->fetchColumn());
        testPdo()->exec("UPDATE users SET rol = 'vendedor' WHERE id = 2");
        $this->assertSame(['2' => 2], Carrito::items());
    }

    public function testInventarioRevalidaElVendedorAunqueLasLineasSeanAnteriores(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        Carrito::agregar('2', 1);
        $lineas = Carrito::lineas();
        testPdo()->exec("UPDATE users SET rol = 'cliente' WHERE id = 2");
        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $pdo->exec('INSERT INTO pedidos (id, cliente_id) VALUES (1, 3)');
            try {
                Inventario::descontar($pdo, $lineas, 1);
                $this->fail('No debe descontar stock a un vendedor sin permiso.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('disponible', $e->getMessage());
            }
        } finally {
            $pdo->rollBack();
        }
        $this->assertSame(10, (int) testPdo()->query('SELECT stock FROM productos WHERE id = 2')->fetchColumn());
    }

    private function renderizar(string $metodo, array $get): string
    {
        $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
            . '$_GET = ' . var_export($get, true) . ';'
            . '$_SERVER["REQUEST_METHOD"] = "GET";'
            . '(new \\Controller\\ProductoController())->' . $metodo . '();';
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [
            0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes);
        $this->assertIsResource($proceso);
        $html = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proceso), $error);
        $this->assertSame('', $error);
        return $html;
    }
}
