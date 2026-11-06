<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CheckoutConcurrenteTest extends TestCase
{
    public function testDosComprasDelMismoProductoNoSeBloqueanPorLasFk(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'concurrente', 'Celulares', 'Concurrente', '', 118, 10)");
        // Después de comprobar las FK, mantener la fila abierta para solapar ambas compras.
        $pdo->exec('CREATE TRIGGER zzzz_jt_test_checkout_pausa AFTER INSERT ON detalle_pedido
            FOR EACH ROW SET @pausa = SLEEP(0.4)');
        $procesos = [];
        try {
            foreach (['cliente@example.com', 'cliente_b2b@example.com'] as $email) {
                $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
                    . '\Model\Cuenta::iniciarSesion(' . var_export($email, true) . ', "cliente");'
                    . '\Model\Carrito::vaciar(); \Model\Carrito::agregar("1", 1);'
                    . 'try { $id = \Model\Carrito::checkout(["modalidad" => "tienda", "metodo" => "Tarjeta"]);'
                    . 'echo json_encode(["ok" => $id !== null]);'
                    . '} catch (\Throwable $e) { echo json_encode(["ok" => false, "error" => $e->getCode()]); }';
                $proceso = proc_open([PHP_BINARY, '-r', $codigo], [
                    1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
                ], $pipes);
                $this->assertIsResource($proceso);
                $procesos[] = [$proceso, $pipes];
            }
            $resultados = [];
            foreach ($procesos as [$proceso, $pipes]) {
                $salida = stream_get_contents($pipes[1]);
                $errores = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($proceso), $errores);
                $resultados[] = json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
            }
            $this->assertSame([['ok' => true], ['ok' => true]], $resultados);
            $this->assertEquals(8, $pdo->query('SELECT stock FROM productos WHERE id = 1')->fetchColumn());
            $this->assertEquals(2, $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn());
        } finally {
            $pdo->exec('DROP TRIGGER IF EXISTS zzzz_jt_test_checkout_pausa');
        }
    }
}
