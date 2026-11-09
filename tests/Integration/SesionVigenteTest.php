<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Administrador;
use Model\Cuenta;
use Model\Recuperacion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SesionVigenteTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[DataProvider('usuariosPanel')]
    public function testDesactivarRevocaElPanelAunqueSeReactiveDespues(string $usuario, int $id): void
    {
        $this->assertTrue(Administrador::iniciarSesion($usuario, $usuario));
        testPdo()->prepare('UPDATE users SET activo = 0 WHERE id = ?')->execute([$id]);

        $this->assertFalse(Administrador::estaAutenticado());
        $this->assertNull(Administrador::idSesion());
        testPdo()->prepare('UPDATE users SET activo = 1 WHERE id = ?')->execute([$id]);
        $this->assertFalse(Administrador::estaAutenticado());
    }

    public static function usuariosPanel(): array
    {
        return [['admin', 1], ['vendedor', 2]];
    }

    #[DataProvider('cambiosDeClave')]
    public function testCambiarClaveRevocaLaTiendaYElPanel(string $flujo): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('admin@example.com', 'admin'));
        $this->assertSame(1, Cuenta::idSesion());
        $this->assertSame(1, Administrador::idSesion());
        $nueva = 'Otra-Clave-Pruebas-2026!';

        if ($flujo === 'token') {
            $token = Recuperacion::solicitarCliente('admin@example.com');
            $this->assertNotNull($token);
            $this->assertNull(Recuperacion::restablecer($token, $nueva, $nueva));
        } elseif ($flujo === 'pregunta') {
            $this->assertNull(Cuenta::cambiarPorPregunta('admin@example.com', 'mascota', $nueva, $nueva));
        } else {
            testPdo()->prepare('UPDATE users SET password = ? WHERE id = 1')
                ->execute([password_hash($nueva, PASSWORD_DEFAULT)]);
        }

        $this->assertNull(Administrador::idSesion());
        $this->assertNull(Cuenta::idSesion());
        $this->assertNull(Cuenta::usuarioActual());
        $this->assertFalse(Administrador::estaAutenticado());
        $this->assertTrue(Cuenta::iniciarSesion('admin@example.com', $nueva));
        $this->assertSame(1, Cuenta::idSesion());
        $this->assertSame(1, Administrador::idSesion());
    }

    public static function cambiosDeClave(): array
    {
        return [['token'], ['pregunta'], ['administracion']];
    }

    public function testDesactivarClienteRevocaTambienLosAccesosQueSoloPidenElId(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        testPdo()->exec('UPDATE users SET activo = 0 WHERE id = 3');
        $this->assertNull(Cuenta::idSesion());
        $this->assertFalse(Cuenta::estaRegistrado());
    }

    public function testUnaSesionAntiguaSinHuellaExigeAutenticarse(): void
    {
        $_SESSION = ['cliente_id' => 3, 'admin_id' => 1];
        $this->assertNull(Cuenta::idSesion());
        $this->assertNull(Administrador::idSesion());
    }

    public function testCerrarLaTiendaConservaUnPanelDeOtraCuenta(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        $this->assertTrue(Administrador::iniciarSesion('admin', 'admin'));
        Cuenta::cerrarSesion();
        $this->assertNull(Cuenta::idSesion());
        $this->assertSame(1, Administrador::idSesion());
    }

    public function testUnaSesionAntiguaConservaElCarritoSoloParaSuPropietario(): void
    {
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'heredado', 'Celulares', 'Heredado', '', 100, 10)");
        $_SESSION = ['cliente_id' => 3, 'carrito' => ['1' => 2]];
        $this->assertNull(Cuenta::idSesion());
        $this->assertSame([1 => 2], $_SESSION['carritos_pendientes'][3] ?? []);
        $this->assertSame([], \Model\Carrito::items());

        $this->assertTrue(Cuenta::iniciarSesion('cliente_b2b@example.com', 'cliente'));
        $this->assertSame([], \Model\Carrito::items());
        Cuenta::cerrarSesion();
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        $this->assertSame([1 => 2], \Model\Carrito::items());
    }

    public function testUnaTiendaCaducadaNoRevocaUnPanelRecienAutenticado(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('admin@example.com', 'admin'));
        $nueva = 'Clave-Panel-Nueva-2026!';
        testPdo()->prepare('UPDATE users SET password = ? WHERE id = 1')
            ->execute([password_hash($nueva, PASSWORD_DEFAULT)]);
        $this->assertTrue(Administrador::iniciarSesion('admin', $nueva));

        $this->assertNull(Cuenta::idSesion());
        $this->assertSame(1, Administrador::idSesion());
    }

    public function testElPerfilAutenticadoNoExponeLaContrasena(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        $this->assertArrayNotHasKey('password', Cuenta::usuarioActual());
    }
}
