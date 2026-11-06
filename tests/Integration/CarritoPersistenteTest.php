<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Carrito;
use Model\Cuenta;
use PHPUnit\Framework\TestCase;

class CarritoPersistenteTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'persistente', 'Celulares', 'Persistente', '', 118, 100),
                   (2, 'otro', 'Celulares', 'Otro', '', 50, 100)");
        testPdo()->exec("INSERT INTO producto_variantes (id, producto_id, nombre_variante, stock)
            VALUES (1, 1, 'Azul', 50), (2, 1, 'Negro', 50), (3, 2, 'Otro', 50)");
    }

    protected function tearDown(): void
    {
        Cuenta::cerrarSesion();
        $_SESSION = [];
    }

    public function testInvitadoSeConservaAlEntrarYAlSalirSoloSeLimpiaLaSesion(): void
    {
        Carrito::agregar('1', 2);
        $_SESSION['cupon'] = 'DEMO';
        $this->assertEquals(0, testPdo()->query('SELECT COUNT(*) FROM carritos')->fetchColumn());
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        $this->assertSame(['1' => 2], Carrito::items());
        $this->assertEquals(2, $this->cantidadGuardada(3));

        Cuenta::cerrarSesion();
        $this->assertSame([], Carrito::items());
        $this->assertArrayNotHasKey('cupon', $_SESSION);
        $this->assertEquals(2, $this->cantidadGuardada(3));
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 2], Carrito::items());
    }

    public function testFusionSumaSoloUnaVezYNoMezclaCuentas(): void
    {
        testPdo()->exec('INSERT INTO carritos (id, cliente_id) VALUES (10, 3)');
        testPdo()->exec('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (10, 1, 5)');
        Carrito::agregar('1', 2);
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 7], Carrito::items());
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 7], Carrito::items());

        Cuenta::iniciarSesion('cliente_b2b@example.com', 'cliente');
        $this->assertSame([], Carrito::items());
        Carrito::agregar('2', 3);
        $this->assertEquals(7, $this->cantidadGuardada(3));
        $this->assertEquals(3, $this->cantidadGuardada(4));
        Cuenta::cerrarSesion();
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 7], Carrito::items());
    }

    public function testVariantesSeGuardanSeparadasYSeEditaSoloLaLineaElegida(): void
    {
        Carrito::agregar('1', 2, 1);
        Carrito::agregar('1', 3, 2);
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1:1' => 2, '1:2' => 3], Carrito::items());
        Carrito::cambiarCantidad('1:1', 4);
        Carrito::eliminar('1:2');

        $_SESSION = []; // Otra sesión/dispositivo: recuperar desde BD, no desde una copia PHP.
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1:1' => 4], Carrito::items());
        Carrito::cambiarCantidad('1:1', 0);
        $this->assertSame([], Carrito::items());
        $this->assertEquals(0, $this->cantidadGuardada(3));
    }

    public function testVarianteAjenaOEliminadaNoSeConvierteEnProductoBase(): void
    {
        Carrito::agregar('1', 1, 3);
        $this->assertSame([], Carrito::items());
        Carrito::agregar('1', 1, 1);
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        testPdo()->exec('DELETE FROM producto_variantes WHERE id = 1');
        $this->assertSame([], Carrito::items());
        $this->assertEquals(0, $this->cantidadGuardada(3));
    }

    public function testCompraLimpiaCarritoGuardadoYUnaCompraFallidaLoConserva(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        Carrito::agregar('1', 101);
        try {
            Carrito::checkout(['modalidad' => 'tienda', 'metodo' => 'Tarjeta']);
            $this->fail('No debe comprar más unidades que el stock.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stock insuficiente', $e->getMessage());
        }
        $this->assertEquals(101, $this->cantidadGuardada(3));
        $this->assertEquals(0, testPdo()->query('SELECT COUNT(*) FROM pedidos')->fetchColumn());
        Carrito::cambiarCantidad('1', 2);
        $id = Carrito::checkout(['modalidad' => 'tienda', 'metodo' => 'Tarjeta']);
        $this->assertNotNull($id);
        $this->assertEquals(0, $this->cantidadGuardada(3));
        $this->assertSame([], Carrito::items());
        $this->assertSame('236.00', testPdo()->query('SELECT total_pen FROM pedidos')->fetchColumn());
        $this->assertEquals(98, testPdo()->query('SELECT stock FROM productos WHERE id = 1')->fetchColumn());
    }

    public function testSesionAnteriorAlCambioSeMigraSinDuplicarSusProductos(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $_SESSION['carrito'] = ['1' => 2];
        $this->assertSame(['1' => 2], Carrito::items());
        $this->assertSame(['1' => 2], Carrito::items());
        $this->assertArrayNotHasKey('carrito', $_SESSION);
        $this->assertEquals(2, $this->cantidadGuardada(3));
    }

    public function testSalirComoPrimeraAccionDeUnaSesionAntiguaConservaSuCarrito(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $_SESSION['carrito'] = ['1' => 2];
        Cuenta::cerrarSesion();
        $this->assertSame([], Carrito::items());
        $this->assertEquals(2, $this->cantidadGuardada(3));
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 2], Carrito::items());
    }

    public function testFalloAlGuardarUnaSesionAntiguaNoImpideSalirNiExponeSuCarrito(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $_SESSION['carrito'] = ['1' => 2];
        $pdo = testPdo();
        $pdo->exec("CREATE TRIGGER jt_test_legacy_fallo BEFORE INSERT ON carrito_items
            FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fallo simulado'");
        try {
            Cuenta::cerrarSesion();
            $this->assertNull(Cuenta::idSesion());
            $this->assertSame([], Carrito::items());
            $this->assertSame(['1' => 2], $_SESSION['carritos_pendientes'][3]);
        } finally {
            $pdo->exec('DROP TRIGGER IF EXISTS jt_test_legacy_fallo');
        }
        Cuenta::iniciarSesion('cliente_b2b@example.com', 'cliente');
        $this->assertSame([], Carrito::items());
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 2], Carrito::items());
        $this->assertEquals(2, $this->cantidadGuardada(3));
        $this->assertArrayNotHasKey(3, $_SESSION['carritos_pendientes']);
    }

    public function testProductoOcultoNoSePuedeComprarDesdeUnCarritoGuardado(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        Carrito::agregar('1', 1);
        testPdo()->exec('UPDATE productos SET activo = 0 WHERE id = 1');
        $this->assertSame([], Carrito::lineas());
        $this->assertNull(Carrito::checkout(['modalidad' => 'tienda', 'metodo' => 'Tarjeta']));
        $this->assertEquals(0, testPdo()->query('SELECT COUNT(*) FROM pedidos')->fetchColumn());
    }

    public function testSiFallaVaciarElCarritoTambienSeRevierteElPedido(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        Carrito::agregar('1', 1);
        $pdo = testPdo();
        $pdo->exec("CREATE TRIGGER jt_test_carrito_fallo BEFORE DELETE ON carrito_items
            FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fallo simulado'");
        try {
            try {
                Carrito::checkout(['modalidad' => 'tienda', 'metodo' => 'Tarjeta']);
                $this->fail('La compra debe revertirse si el carrito no puede limpiarse.');
            } catch (PDOException $e) {
                $this->assertSame('45000', $e->getCode());
            }
            $this->assertEquals(0, $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn());
            $this->assertEquals(100, $pdo->query('SELECT stock FROM productos WHERE id = 1')->fetchColumn());
            $this->assertEquals(1, $this->cantidadGuardada(3));
        } finally {
            $pdo->exec('DROP TRIGGER IF EXISTS jt_test_carrito_fallo');
        }
    }

    public function testFalloAlFusionarNoPierdeElInvitadoNiAbreSesion(): void
    {
        Carrito::agregar('1', 2);
        Carrito::agregar('2', 1);
        $pdo = testPdo();
        $pdo->exec("CREATE TRIGGER jt_test_fusion_fallo BEFORE INSERT ON carrito_items
            FOR EACH ROW BEGIN
                IF NEW.producto_id = 2 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'fallo simulado';
                END IF;
            END");
        try {
            $this->assertFalse(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
            $this->assertNull(Cuenta::idSesion());
            $this->assertSame(['1' => 2, '2' => 1], Carrito::items());
            $this->assertEquals(0, $this->cantidadGuardada(3));
        } finally {
            $pdo->exec('DROP TRIGGER IF EXISTS jt_test_fusion_fallo');
        }
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        $this->assertEquals(3, $this->cantidadGuardada(3));
    }

    public function testRegistroTambienConservaElCarritoDelInvitado(): void
    {
        Carrito::agregar('1', 2, 1);
        $error = Cuenta::registrar([
            'nombre' => 'Nuevo', 'apellidos' => 'Cliente', 'email' => 'carrito-nuevo@example.com',
            'usuario' => 'carrito-nuevo', 'telefono' => '999000010', 'direccion' => 'Calle Nueva 123',
            'dni' => '00000010', 'tipo_cliente' => 'B2C', 'tarifa_envio_id' => 1,
            'password' => 'Ejemplo-Pruebas-2026!', 'confirmar' => 'Ejemplo-Pruebas-2026!',
            'pregunta_secreta' => Cuenta::PREGUNTAS_PERMITIDAS[0], 'respuesta_secreta' => 'firulais',
        ]);
        $this->assertNull($error);
        $this->assertNotNull(Cuenta::idSesion());
        $this->assertSame(['1:1' => 2], Carrito::items());
        $this->assertEquals(2, $this->cantidadGuardada(Cuenta::idSesion()));
    }

    private function cantidadGuardada(int $clienteId): int
    {
        $stmt = testPdo()->prepare('SELECT COALESCE(SUM(i.cantidad), 0) FROM carrito_items i
            JOIN carritos c ON c.id = i.carrito_id WHERE c.cliente_id = ?');
        $stmt->execute([$clienteId]);
        return (int) $stmt->fetchColumn();
    }
}
