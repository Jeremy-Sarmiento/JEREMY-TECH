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
    public function testTodosLosMetodosAclaranQueNoHuboCobro(string $metodo, array $datos): void
    {
        $resultado = PagoSimulado::procesar($metodo, $datos);

        $this->assertTrue($resultado['ok']);
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
            ]],
            'yape' => ['yape', ['yape_telefono' => '999000002', 'yape_codigo' => '849201']],
            'transferencia' => ['transferencia', ['transf_codigo' => 'DEMO1234']],
            'contraentrega' => ['contraentrega', []],
        ];
    }
}
