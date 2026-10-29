<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Cuenta;
use PHPUnit\Framework\TestCase;

class CuentaTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
    }

    public function testRegistrarAsignaRolCliente(): void
    {
        $datos = [
            'nombre' => 'Nuevo',
            'apellidos' => 'Usuario',
            'email' => 'nuevo@example.com',
            'usuario' => 'nuevouser',
            'telefono' => '999000005',
            'direccion' => 'Calle Test 123',
            'dni' => '00000002',
            'tipo_cliente' => 'B2C',
            'ruc' => '',
            'plataforma_reventa' => '',
            'tarifa_envio_id' => 1,
            'latitud' => '',
            'longitud' => '',
            'password' => 'password123',
            'confirmar' => 'password123',
            'pregunta_secreta' => '¿Cuál es el nombre de tu primera mascota?',
            'respuesta_secreta' => 'firulais',
        ];

        $error = Cuenta::registrar($datos);
        $this->assertNull($error, 'El registro no debería fallar: ' . ($error ?? ''));

        $usuario = Cuenta::porEmail('nuevo@example.com');
        $this->assertNotNull($usuario);
        $this->assertEquals('cliente', $usuario['rol'], 'Toda cuenta nueva debe tener rol=cliente');
        $this->assertEquals('B2C', $usuario['tipo'], 'El tipo elegido debe respetarse');
    }

    public function testRegistrarRespetaTipoB2B(): void
    {
        $datos = [
            'nombre' => 'Empresa',
            'apellidos' => 'SAC',
            'email' => 'empresa_test@example.com',
            'usuario' => 'empresa',
            'telefono' => '999000006',
            'direccion' => 'Av. Empresarial 456',
            'dni' => '00000003',
            'tipo_cliente' => 'B2B',
            'ruc' => '20987654321',
            'plataforma_reventa' => '',
            'tarifa_envio_id' => 1,
            'latitud' => '',
            'longitud' => '',
            'password' => 'password123',
            'confirmar' => 'password123',
            'pregunta_secreta' => '¿En qué ciudad naciste?',
            'respuesta_secreta' => 'Lima',
        ];

        $error = Cuenta::registrar($datos);
        $this->assertNull($error);

        $usuario = Cuenta::porEmail('empresa_test@example.com');
        $this->assertEquals('B2B', $usuario['tipo']);
        $this->assertEquals('20987654321', $usuario['ruc']);
        // El modelo usa $nombre para empresa cuando es B2B
        $this->assertEquals('Empresa', $usuario['empresa']);
        $this->assertEquals('cliente', $usuario['rol'], 'Incluso B2B nace como cliente');
    }

    public function testPuedePublicarSoloVendedor(): void
    {
        $cliente = findTestUser('cliente@example.com');
        $this->assertFalse(Cuenta::puedePublicar($cliente), 'Cliente B2C no puede publicar');

        $b2b = findTestUser('cliente_b2b@example.com');
        $this->assertFalse(Cuenta::puedePublicar($b2b), 'Cliente B2B no puede publicar');

        $c2c = findTestUser('cliente_c2c@example.com');
        $this->assertFalse(Cuenta::puedePublicar($c2c), 'Cliente C2C no puede publicar con la nueva regla');

        $vendedor = findTestUser('vendedor@example.com');
        $this->assertTrue(Cuenta::puedePublicar($vendedor), 'Vendedor sí puede publicar');

        $admin = findTestUser('admin@example.com');
        $this->assertFalse(Cuenta::puedePublicar($admin), 'Admin sin rol vendedor no puede publicar');
    }

    public function testEsVendedorAlias(): void
    {
        $vendedor = findTestUser('vendedor@example.com');
        $cliente = findTestUser('cliente@example.com');

        $this->assertTrue(Cuenta::puedePublicar($vendedor));
        $this->assertFalse(Cuenta::puedePublicar($cliente));
    }
}
