<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Administrador;
use PHPUnit\Framework\TestCase;

class PedidosPanelTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        Administrador::iniciarSesion('admin', 'admin');
        testPdo()->exec("INSERT INTO pedidos (id, cliente_id, total_pen, estado)
            SELECT n, 3, 118, 'Pagado' FROM generate_series(1, 51) n");
    }

    protected function tearDown(): void
    {
        $_GET = [];
        Administrador::cerrarSesion();
    }

    public function testPaginaAcotadaConAccesoAlHistorial(): void
    {
        $html = $this->renderizar([]);
        $this->assertSame(50, substr_count($html, '<td class="admin-detalle">'));
        $this->assertStringContainsString('Siguiente', $html);
        $this->assertStringNotContainsString('<td>1</td>', $html);

        $segunda = $this->renderizar(['pagina' => '2']);
        $this->assertSame(1, substr_count($segunda, '<td class="admin-detalle">'));
        $this->assertStringContainsString('<td>1</td>', $segunda);
        $this->assertStringContainsString('Anterior', $segunda);
        $this->assertStringNotContainsString('rel="next"', $segunda);
    }

    public function testFiltroSeConservaAlPaginar(): void
    {
        $html = $this->renderizar(['estado' => 'Pagado']);
        $this->assertStringContainsString('estado=Pagado', $html);
        $this->assertStringContainsString('pagina=2', $html);
    }

    private function renderizar(array $query): string
    {
        $_GET = $query;
        ob_start();
        try {
            require BASE_PATH . '/views/admin/secciones/pedidos.php';
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
