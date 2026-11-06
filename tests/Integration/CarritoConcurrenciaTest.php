<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Carrito;
use Model\Cuenta;
use PHPUnit\Framework\TestCase;

class CarritoConcurrenciaTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        testPdo()->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'carrito-concurrente', 'Celulares', 'Concurrente', '', 118, 20)");
    }

    protected function tearDown(): void
    {
        Cuenta::cerrarSesion();
        $_SESSION = [];
    }

    public function testDosDispositivosSumanSinPerderUnidades(): void
    {
        $this->paralelos('for ($n = 0; $n < 3; $n++) { \Model\Carrito::agregar("1", 1); } echo "ok";');
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        $this->assertSame(['1' => 6], Carrito::items());
        $this->assertEquals(1, testPdo()->query('SELECT COUNT(*) FROM carritos WHERE cliente_id = 3')->fetchColumn());
    }

    public function testDosDispositivosNoCompranDosVecesElMismoCarrito(): void
    {
        Cuenta::iniciarSesion('cliente@example.com', 'cliente');
        Carrito::agregar('1', 1);
        $pdo = testPdo();
        $pdo->exec('CREATE TRIGGER zzzz_jt_test_carrito_pausa AFTER INSERT ON detalle_pedido
            FOR EACH ROW SET @pausa = SLEEP(0.4)');
        try {
            $this->paralelos('\Model\Carrito::checkout(["modalidad" => "tienda", "metodo" => "Tarjeta"]); echo "ok";');
            $this->assertEquals(1, $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn());
            $this->assertEquals(19, $pdo->query('SELECT stock FROM productos WHERE id = 1')->fetchColumn());
            $this->assertSame([], Carrito::items());
        } finally {
            $pdo->exec('DROP TRIGGER IF EXISTS zzzz_jt_test_carrito_pausa');
        }
    }

    private function paralelos(string $accion): void
    {
        $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
            . 'if (!\Model\Cuenta::iniciarSesion("cliente@example.com", "cliente")) { exit(2); }' . $accion;
        $procesos = [];
        for ($n = 0; $n < 2; $n++) {
            $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $this->assertIsResource($proceso);
            $procesos[] = [$proceso, $pipes];
        }
        foreach ($procesos as [$proceso, $pipes]) {
            $salida = stream_get_contents($pipes[1]);
            $errores = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($proceso), $errores);
            $this->assertSame('ok', $salida);
        }
    }
}
