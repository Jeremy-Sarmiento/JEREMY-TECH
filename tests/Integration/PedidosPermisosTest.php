<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Administrador;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PedidosPermisosTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        testPdo()->exec("INSERT INTO users (id, name, email, rol, password)
            SELECT 6, 'Otro vendedor', 'otro@example.test', 'vendedor', password FROM users WHERE id = 2");
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[DataProvider('pedidosMixtos')]
    public function testVendedorNoPuedeCambiarUnPedidoMixto(?int $otroVendedor, string $actual, string $destino): void
    {
        $this->crearPedido($otroVendedor, $actual);
        $respuesta = $this->cambiarEstado('vendedor', $destino);

        $this->assertSame('error', $respuesta['tipo']);
        $this->assertSame($actual, testPdo()->query('SELECT estado FROM pedidos WHERE id = 1')->fetchColumn());
        $this->assertSame([9, 9], testPdo()->query('SELECT stock FROM productos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
        foreach (['movimientos_inventario', 'pedido_eventos', 'auditoria_admin'] as $tabla) {
            $this->assertSame(0, (int) testPdo()->query('SELECT COUNT(*) FROM ' . $tabla)->fetchColumn());
        }
    }

    public static function pedidosMixtos(): array
    {
        return [
            'plataforma' => [null, 'Pendiente', 'Cancelado'],
            'otro vendedor' => [6, 'Pendiente', 'Cancelado'],
            'pago' => [6, 'Pendiente', 'Pagado'],
            'envio' => [6, 'Pagado', 'Enviado'],
            'entrega' => [6, 'Enviado', 'Entregado'],
        ];
    }

    public function testVendedorPuedeCancelarUnPedidoSoloConSusProductos(): void
    {
        $this->crearPedido(2);
        $this->assertSame('ok', $this->cambiarEstado('vendedor', 'Cancelado')['tipo']);
        $this->assertSame('Cancelado', testPdo()->query('SELECT estado FROM pedidos WHERE id = 1')->fetchColumn());
        $this->assertSame([10, 10], testPdo()->query('SELECT stock FROM productos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    }

    public function testAdministradorPuedeCancelarUnPedidoMixto(): void
    {
        $this->crearPedido(null);
        $this->assertSame('ok', $this->cambiarEstado('admin', 'Cancelado')['tipo']);
        $this->assertSame('Cancelado', testPdo()->query('SELECT estado FROM pedidos WHERE id = 1')->fetchColumn());
        $this->assertSame([10, 10], testPdo()->query('SELECT stock FROM productos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    }

    #[DataProvider('alcanceFormulario')]
    public function testElPanelNoOfreceCambiarPedidosMixtos(?int $otroVendedor, bool $puedeCambiar): void
    {
        $this->crearPedido($otroVendedor);
        $this->assertTrue(Administrador::iniciarSesion('vendedor', 'vendedor'));
        $_GET = [];
        ob_start();
        try {
            require BASE_PATH . '/views/admin/secciones/pedidos.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertSame($puedeCambiar, str_contains($html, 'action="?ruta=admin/pedidos/estado"'));
    }

    public static function alcanceFormulario(): array
    {
        return [[null, false], [6, false], [2, true]];
    }

    private function crearPedido(?int $otroVendedor, string $estado = 'Pendiente'): void
    {
        $pdo = testPdo();
        $stmt = $pdo->prepare("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock, vendedor_id)
            VALUES (?, ?, 'Celulares', ?, '', 100, 9, ?)");
        $stmt->execute([1, 'propio', 'Propio', 2]);
        $stmt->execute([2, 'otro', 'Otro', $otroVendedor]);
        $pdo->prepare('INSERT INTO pedidos (id, cliente_id, total_pen, estado) VALUES (1, 3, 200, ?)')->execute([$estado]);
        $stmt = $pdo->prepare('INSERT INTO detalle_pedido (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario_pen, vendedor_id)
            VALUES (1, ?, ?, 1, 100, ?)');
        $stmt->execute([1, 'Propio', 2]);
        $stmt->execute([2, 'Otro', $otroVendedor]);
    }

    private function cambiarEstado(string $usuario, string $estado): array
    {
        $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
            . '\Model\Administrador::iniciarSesion(' . var_export($usuario, true) . ', ' . var_export($usuario, true) . ');'
            . '$_SERVER["REQUEST_METHOD"] = "POST";'
            . '$_POST = ' . var_export(['id' => 1, 'estado' => $estado, 'courier' => 'Prueba', 'guia_numero' => 'SIM-1'], true) . ';'
            . 'register_shutdown_function(function () { echo json_encode($_SESSION["admin_flash"] ?? null); });'
            . '(new \Controller\AdminController())->atender("pedidos/estado");';
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proceso);
        $salida = stream_get_contents($pipes[1]);
        $errores = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proceso), $errores);
        return json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
    }
}
