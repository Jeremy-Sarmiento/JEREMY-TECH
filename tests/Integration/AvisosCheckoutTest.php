<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

final class AvisosCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = ['cliente_id' => 3];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testElCheckoutAdvierteAntesDelPagoSinDestinosReales(): void
    {
        $lineas = [];
        $subtotal = 100.0;
        $descuento = 0.0;
        $cupon = null;
        $totalItems = 1;
        $historial = [$this->pedido()];
        ob_start();
        require BASE_PATH . '/views/carrito.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('No introduzcas tarjetas reales ni envíes dinero por Yape o transferencia.', $html);
        $this->assertLessThan(strpos($html, 'id="tabTarjeta"'), strpos($html, 'Pago de prueba.'));
        $this->assertStringContainsString('Simular compra', $html);
        $this->assertStringContainsString('Total simulado:', $html);
        $this->assertStringContainsString('Pedido de prueba', $html);
        $this->assertStringContainsString('No se realizó ningún cobro real.', $html);
        foreach (['Total a Pagar', 'Total Pagado', 'Confirmar Pedido y Pagar', 'Despacho Inmediato',
            'Garantía Oficial', 'img/qr.svg', '997833866', '215-98765432', '898-3001234567',
            '0011-0234-0200987654', 'autocomplete="cc-', 'está en preparación'] as $textoReal) {
            $this->assertStringNotContainsString($textoReal, $html);
        }
    }

    public function testElHistorialIdentificaLosPedidosYLosImportesDePrueba(): void
    {
        $pedidos = [$this->pedido()];
        $resumen = ['total' => 1];
        $filtros = ['Pagado'];
        $resenas = $comprados = [];
        ob_start();
        require BASE_PATH . '/views/mis_pedidos.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Pedido de prueba #JT-00001', $html);
        $this->assertStringContainsString('No se realizó ningún cobro real.', $html);
        $this->assertStringContainsString('Total simulado', $html);
        $this->assertStringNotContainsString('Gastado (sin cancelados)', $html);
    }

    private function pedido(): array
    {
        return [
            'id' => 1,
            'estado' => 'Pagado',
            'total_pen' => 100,
            'detalle' => [],
        ];
    }
}
