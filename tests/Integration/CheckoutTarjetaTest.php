<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Carrito;
use Model\PagoSimulado;
use Model\PedidoEvento;
use PHPUnit\Framework\TestCase;

final class CheckoutTarjetaTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        $this->assertTrue(\Model\Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'tarjeta-demo', 'Celulares', 'Teléfono de prueba', '', 100, 10)");
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testLaTarjetaAprobadaGuardaElPedidoEntregadoConFechaEnElHistorial(): void
    {
        Carrito::agregar('1', 1);
        $pago = PagoSimulado::procesar('tarjeta', [
            'tarjeta_numero' => PagoSimulado::TARJETA_APROBADA,
            'tarjeta_titular' => 'Cliente Demo',
            'tarjeta_expiracion' => '12/40',
            'tarjeta_cvv' => '123',
        ]);
        $pedidoId = Carrito::checkout($pago + ['modalidad' => 'tienda']);

        $this->assertNotNull($pedidoId);
        $historial = Carrito::historial(3);
        $this->assertCount(1, $historial);
        $this->assertSame('Entregado', $historial[0]['estado']);
        $this->assertSame('Pagado', $historial[0]['pago']['estado']);
        $this->assertCount(1, Carrito::historial(3, 50, 'Entregado'));
        $this->assertSame(1, Carrito::resumenPedidos(3)['entregados']);
        $pasos = array_column(PedidoEvento::lineaTiempo($historial[0]), null, 'clave');
        $this->assertSame('current', $pasos[PedidoEvento::ENTREGADO]['estado']);
        $this->assertNotEmpty($pasos[PedidoEvento::ENTREGADO]['fecha']);
        $eventos = array_column($historial[0]['eventos'], null, 'evento');
        $this->assertSame('Entregado', $eventos[PedidoEvento::ENTREGADO]['estado']);
        $this->assertStringContainsString('simulación', $eventos[PedidoEvento::ENTREGADO]['detalle']);
    }
}
