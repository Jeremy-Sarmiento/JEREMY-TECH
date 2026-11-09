<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Controller\AdminController;
use Model\Administrador;
use PHPUnit\Framework\TestCase;

final class DashboardPanelTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testResumenVendedorCargaYCuentaCadaPedidoUnaSolaVez(): void
    {
        $pdo = testPdo();
        $pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, vendedor_id)
            VALUES (1, 'propio', 'Celulares', 'Propio', '', 2), (2, 'plataforma', 'Celulares', 'Otro', '', NULL)");
        $pdo->exec("INSERT INTO pedidos (id, cliente_id, estado) VALUES (1, 3, 'Pendiente'), (2, 4, 'Pendiente')");
        $pdo->exec('INSERT INTO detalle_pedido (pedido_id, producto_id, vendedor_id)
            VALUES (1, 1, 2), (1, 1, 2), (2, 2, NULL)');
        $this->assertTrue(Administrador::iniciarSesion('vendedor', 'vendedor'));

        $html = $this->pagina();

        $this->assertStringContainsString('Resumen del vendedor', $html);
        $this->assertStringNotContainsString('No se pudo cargar', $html);
        $this->assertStringNotContainsString('SQLSTATE', $html);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        foreach (['Productos', 'Pedidos', 'Pendientes'] as $etiqueta) {
            $numero = $xpath->evaluate('string(//div[contains(@class,"tarjeta ")][span[@class="tarjeta-etiqueta" and text()="'
                . $etiqueta . '"]]/span[@class="tarjeta-numero"])');
            $this->assertSame('1', $numero, $etiqueta);
        }
        $this->assertCount(1, $xpath->query('//table/tbody/tr'));
        $this->assertStringNotContainsString('Cliente B2B', $html);
    }

    public function testResumenVacioMuestraTarjetasYMensajeSinPedidos(): void
    {
        $this->assertTrue(Administrador::iniciarSesion('vendedor', 'vendedor'));
        $html = $this->pagina();
        $this->assertStringContainsString('Resumen del vendedor', $html);
        $this->assertStringContainsString('Todavía no hay pedidos que mostrar.', $html);
        $this->assertSame(6, substr_count($html, 'class="tarjeta-numero"'));
        $this->assertStringNotContainsString('SQLSTATE', $html);
    }

    public function testResumenAdministradorSigueMostrandoTotalesGenerales(): void
    {
        $this->assertTrue(Administrador::iniciarSesion('admin', 'admin'));
        $html = $this->pagina();
        $this->assertStringContainsString('Resumen general', $html);
        $this->assertStringContainsString('Usuarios', $html);
        $this->assertStringNotContainsString('No se pudo cargar', $html);
    }

    private function pagina(): string
    {
        ob_start();
        try {
            (new AdminController())->atender('dashboard');
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
