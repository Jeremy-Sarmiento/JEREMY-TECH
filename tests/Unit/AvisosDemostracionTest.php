<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\PagoSimulado;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AvisosDemostracionTest extends TestCase
{
    public function testLaCabeceraAnunciaLaDemostracionDebajoDelMenu(): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        $categoriasDisponibles = [];
        $marcasDisponibles = [];
        ob_start();
        require BASE_PATH . '/views/layout/header.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Tienda de demostración.', $html);
        $this->assertStringContainsString('Las compras y los pagos son simulados; no se realizan cobros reales.', $html);
        $this->assertGreaterThan(strpos($html, '</nav>'), strpos($html, 'Tienda de demostración.'));
    }

    public function testLaCuentaPideDatosFicticiosAntesDeLosFormularios(): void
    {
        ob_start();
        require BASE_PATH . '/views/login.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('Usa datos ficticios y una contraseña que no utilices en otros servicios.', $html);
        $this->assertLessThan(strpos($html, '<form'), strpos($html, 'Usa datos ficticios'));
    }

    #[DataProvider('metodos')]
    public function testTodosLosMetodosAclaranQueNoHuboCobro(string $metodo, array $datos, string $estadoPedido): void
    {
        $resultado = PagoSimulado::procesar($metodo, $datos);

        $this->assertTrue($resultado['ok']);
        $this->assertSame($estadoPedido, $resultado['estado_pedido']);
        $this->assertStringContainsString('No se realizó ningún cobro real.', $resultado['mensaje']);
        $this->assertStringNotContainsString('hasta la entrega', $resultado['mensaje']);
        $this->assertStringNotContainsString('confirmará el pago', $resultado['mensaje']);
    }

    public static function metodos(): array
    {
        return [
            'tarjeta' => ['tarjeta', [
                'tarjeta_numero' => PagoSimulado::TARJETA_APROBADA,
                'tarjeta_titular' => 'Cliente Demo',
                'tarjeta_expiracion' => '12/40',
                'tarjeta_cvv' => '123',
            ], 'Entregado'],
            'yape' => ['yape', ['yape_telefono' => '999000002', 'yape_codigo' => '849201'], 'Pagado'],
            'transferencia' => ['transferencia', ['transf_codigo' => 'DEMO1234'], 'Pendiente'],
            'contraentrega' => ['contraentrega', [], 'Pendiente'],
        ];
    }

    public function testLaTarjetaRechazadaNoGeneraUnEstadoDePedido(): void
    {
        $resultado = PagoSimulado::procesar('tarjeta', [
            'tarjeta_numero' => PagoSimulado::TARJETA_RECHAZADA,
            'tarjeta_titular' => 'Cliente Demo',
            'tarjeta_expiracion' => '12/40',
            'tarjeta_cvv' => '123',
        ]);

        $this->assertFalse($resultado['ok']);
        $this->assertArrayNotHasKey('estado_pedido', $resultado);
    }
}
