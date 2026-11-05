<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Cuenta;
use Model\ProductoVendedor;
use Model\SolicitudVendedor;
use Model\Vendedor;
use PHPUnit\Framework\TestCase;

class FullFlowTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
    }

    public function testFlujoCompletoRegistroSolicitudAprobacionPanel(): void
    {
        // 1. Registrar nuevo cliente
        $datos = [
            'nombre' => 'Nuevo',
            'apellidos' => 'Vendedor',
            'email' => 'nuevovendedor@example.com',
            'usuario' => 'nuevovendedor',
            'telefono' => '999000010',
            'direccion' => 'Calle Nueva 123',
            'dni' => '00000010',
            'tipo_cliente' => 'B2B',
            'ruc' => '20111111111',
            'plataforma_reventa' => '',
            'tarifa_envio_id' => 1,
            'latitud' => '',
            'longitud' => '',
            'password' => 'Ejemplo-Pruebas-2026!',
            'confirmar' => 'Ejemplo-Pruebas-2026!',
            'pregunta_secreta' => '¿Cuál es el nombre de tu primera mascota?',
            'respuesta_secreta' => 'firulais',
        ];

        $error = Cuenta::registrar($datos);
        $this->assertNull($error);

        $usuario = Cuenta::porEmail('nuevovendedor@example.com');
        $this->assertNotNull($usuario);
        $this->assertEquals('cliente', $usuario['rol']);
        $this->assertEquals('B2B', $usuario['tipo']);
        $clienteId = (int) $usuario['id'];

        // 2. El cliente no puede publicar aún
        $this->assertFalse(Cuenta::puedePublicar($usuario));

        // 3. Solicita rol de vendedor
        $r = SolicitudVendedor::crear($clienteId, 'Quiero vender equipos industriales');
        $this->assertTrue($r['ok']);
        $sid = $r['id'];

        // 4. Admin aprueba
        $error = SolicitudVendedor::aprobar($sid, 'admin_test');
        $this->assertNull($error);

        // 5. Verificar que el rol cambió
        $usuarioActualizado = Cuenta::porEmail('nuevovendedor@example.com');
        $this->assertEquals('vendedor', $usuarioActualizado['rol']);
        $this->assertEquals('B2B', $usuarioActualizado['tipo']); // tipo se conserva
        $this->assertEquals('20111111111', $usuarioActualizado['ruc']); // datos se conservan

        // 6. Ahora SÍ puede publicar
        $this->assertTrue(Cuenta::puedePublicar($usuarioActualizado));

        // 7. Panel vendedor detecta tipo B2B y muestra ficha empresa
        $vendedorId = $usuarioActualizado['id'];
        $productos = ProductoVendedor::listar($vendedorId, '', false);
        $resumen = ProductoVendedor::resumen($vendedorId);
        $this->assertIsArray($productos);
        $this->assertIsArray($resumen);

        $ganancias = Vendedor::resumenGanancias($vendedorId);
        $this->assertArrayHasKey('vendido', $ganancias);
        $this->assertArrayHasKey('neto', $ganancias);
        $this->assertArrayHasKey('saldo', $ganancias);

        // 8. Vendedor B2B: comisionDe funciona
        $comision = Vendedor::comisionDe($vendedorId);
        $this->assertIsFloat($comision);
    }

    public function testC2CClienteSolicitaYAprobadoVePanelC2C(): void
    {
        $datos = [
            'nombre' => 'Revendedor',
            'apellidos' => 'Demo',
            'email' => 'revendedor@example.com',
            'usuario' => 'revendedor',
            'telefono' => '999000011',
            'direccion' => 'Calle C2C 456',
            'dni' => '00000011',
            'tipo_cliente' => 'C2C',
            'ruc' => '',
            'plataforma_reventa' => 'Facebook Marketplace',
            'tarifa_envio_id' => 1,
            'latitud' => '',
            'longitud' => '',
            'password' => 'Ejemplo-Pruebas-2026!',
            'confirmar' => 'Ejemplo-Pruebas-2026!',
            'pregunta_secreta' => '¿En qué ciudad naciste?',
            'respuesta_secreta' => 'Arequipa',
        ];

        $error = Cuenta::registrar($datos);
        $this->assertNull($error);

        $usuario = Cuenta::porEmail('revendedor@example.com');
        $clienteId = (int) $usuario['id'];

        // Solicita y aprueba
        $sid = SolicitudVendedor::crear($clienteId, 'Vendo cosas usadas')['id'];
        SolicitudVendedor::aprobar($sid, 'admin_test');

        $usuarioActualizado = Cuenta::porEmail('revendedor@example.com');
        $this->assertEquals('vendedor', $usuarioActualizado['rol']);
        $this->assertEquals('C2C', $usuarioActualizado['tipo']);
        $this->assertEquals('Facebook Marketplace', $usuarioActualizado['plataforma_reventa']);

        // Panel C2C: ve reputación y movimientos
        $vendedorId = $usuarioActualizado['id'];
        $reputacion = Vendedor::reputacionDe($vendedorId);
        $movimientos = \Model\Inventario::historialDeVendedor($vendedorId, 20);
        $this->assertArrayHasKey('promedio', $reputacion);
        $this->assertIsArray($movimientos);
    }

    public function testB2CClienteSolicitaYAprobadoVePanelSimple(): void
    {
        $datos = [
            'nombre' => 'Vendedor',
            'apellidos' => 'Simple',
            'email' => 'simple@example.com',
            'usuario' => 'simple',
            'telefono' => '999000012',
            'direccion' => 'Av. Simple 789',
            'dni' => '00000012',
            'tipo_cliente' => 'B2C',
            'ruc' => '',
            'plataforma_reventa' => '',
            'tarifa_envio_id' => 1,
            'latitud' => '',
            'longitud' => '',
            'password' => 'Ejemplo-Pruebas-2026!',
            'confirmar' => 'Ejemplo-Pruebas-2026!',
            'pregunta_secreta' => '¿Cómo se llamaba tu colegio de primaria?',
            'respuesta_secreta' => 'Colegio 123',
        ];

        $error = Cuenta::registrar($datos);
        $this->assertNull($error);

        $usuario = Cuenta::porEmail('simple@example.com');
        $clienteId = (int) $usuario['id'];

        $sid = SolicitudVendedor::crear($clienteId, 'Vendo accesorios')['id'];
        SolicitudVendedor::aprobar($sid, 'admin_test');

        $usuarioActualizado = Cuenta::porEmail('simple@example.com');
        $this->assertEquals('vendedor', $usuarioActualizado['rol']);
        $this->assertEquals('B2C', $usuarioActualizado['tipo']);

        // Panel B2C: simple, sin ficha empresa
        $vendedorId = $usuarioActualizado['id'];
        $productos = ProductoVendedor::listar($vendedorId, '', false);
        $ganancias = Vendedor::resumenGanancias($vendedorId);
        $this->assertIsArray($productos);
        $this->assertArrayHasKey('neto', $ganancias);
    }

    public function testGuardaYLeeLaImagenDelProductoEnLaBase(): void
    {
        $pdo = testPdo();
        $productoId = (int) $pdo->query(
            "INSERT INTO productos (slug, categoria, nombre, especificaciones, precio_pen, stock, activo, colores, imagen, stock_minimo)
             VALUES ('con-imagen', 'Celulares', 'Con imagen', '', 10.00, 1, 1, '', '', 1)
             RETURNING id"
        )->fetchColumn();

        $datos = (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        $this->assertTrue(\Model\ImagenProducto::guardar($productoId, $datos, 'image/png'));

        $imagen = \Model\ImagenProducto::deSlug('con-imagen');
        $this->assertNotNull($imagen);
        $this->assertSame('image/png', $imagen['mime']);
        $this->assertSame($datos, $imagen['datos']);

        $producto = \Model\Producto::porSlug('con-imagen');
        $this->assertNotNull($producto);
        $this->assertTrue($producto['imagenDb']);
        $this->assertSame('?ruta=producto/imagen&slug=con-imagen', \Model\Producto::urlImagen($producto));
    }

    public function testElLoginDeLaTiendaAbreElPanelSoloParaAdminOVendedor(): void
    {
        $this->assertTrue(Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        $this->assertFalse(\Model\Administrador::estaAutenticado());
        Cuenta::cerrarSesion();

        $this->assertTrue(Cuenta::iniciarSesion('admin@example.com', 'admin'));
        $this->assertTrue(\Model\Administrador::estaAutenticado());
        $this->assertSame(1, \Model\Administrador::idSesion());

        Cuenta::cerrarSesion();
        $this->assertFalse(\Model\Administrador::estaAutenticado());
    }
}
