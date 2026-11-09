<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CheckoutConcurrenteTest extends TestCase
{
    public function testDosCarritosEnOrdenInversoPuedenComprarSinDeadlock(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'primero', 'Celulares', 'Primero', '', 100, 10),
                   (2, 'segundo', 'Celulares', 'Segundo', '', 100, 10)");
        $pdo->exec('CREATE FUNCTION jt_test_stock_orden() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN PERFORM pg_sleep(0.2); RETURN NEW; END; $$');
        $pdo->exec('CREATE TRIGGER jt_test_stock_orden AFTER UPDATE OF stock ON productos
            FOR EACH ROW EXECUTE FUNCTION jt_test_stock_orden()');
        $procesos = [];
        try {
            foreach ([['cliente@example.com', [1, 2]], ['cliente_b2b@example.com', [2, 1]]] as [$email, $ids]) {
                $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
                    . '\Model\Cuenta::iniciarSesion(' . var_export($email, true) . ', "cliente");'
                    . '\Model\Carrito::vaciar();'
                    . 'foreach (' . var_export($ids, true) . ' as $id) { \Model\Carrito::agregar((string) $id, 1); }'
                    . 'echo "listo\n"; fflush(STDOUT); fgets(STDIN);'
                    . 'try { $id = \Model\Carrito::checkout(["modalidad" => "tienda", "metodo" => "Tarjeta"]);'
                    . 'echo json_encode(["ok" => $id !== null]); }'
                    . 'catch (\Throwable $e) { echo json_encode(["ok" => false, "error" => $e->getCode()]); }';
                $proceso = proc_open([PHP_BINARY, '-r', $codigo], [
                    0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
                ], $pipes);
                $this->assertIsResource($proceso);
                $procesos[] = [$proceso, $pipes];
            }
            foreach ($procesos as [$proceso, $pipes]) {
                $this->assertSame("listo\n", fgets($pipes[1]));
            }
            foreach ($procesos as [$proceso, $pipes]) {
                fwrite($pipes[0], "comprar\n");
                fclose($pipes[0]);
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
            $this->assertSame([8, 8], $pdo->query('SELECT stock FROM productos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
            $this->assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn());
        } finally {
            $pdo->exec('DROP TRIGGER jt_test_stock_orden ON productos');
            $pdo->exec('DROP FUNCTION jt_test_stock_orden()');
        }
    }

    public function testDescuentoYDevolucionUsanElMismoOrdenDeProductosYVariantes(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'primero', 'Celulares', 'Primero', '', 100, 10),
                   (2, 'segundo', 'Celulares', 'Segundo', '', 100, 10)");
        $pdo->exec("INSERT INTO producto_variantes (id, producto_id, nombre_variante, stock)
            VALUES (1, 1, 'Azul', 10), (2, 1, 'Rojo', 10), (3, 2, 'Verde', 10)");
        $this->assertTrue(\Model\Cuenta::iniciarSesion('cliente@example.com', 'cliente'));
        \Model\Carrito::vaciar();
        \Model\Carrito::agregar('2', 1, 3);
        \Model\Carrito::agregar('1', 1, 2);
        \Model\Carrito::agregar('1', 1, 1);
        $pedido = \Model\Carrito::checkout(['modalidad' => 'tienda', 'metodo' => 'Tarjeta']);
        $this->assertNotNull($pedido);
        $this->assertSame([1, 2, 3], $pdo->query("SELECT variante_id FROM movimientos_inventario WHERE tipo = 'salida' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));

        $pdo->beginTransaction();
        try {
            $this->assertTrue(\Model\Inventario::devolver($pdo, $pedido));
            $this->assertSame([1, 2, 3], $pdo->query("SELECT variante_id FROM movimientos_inventario WHERE tipo = 'entrada' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));
            $this->assertSame([10, 10], $pdo->query('SELECT stock FROM productos ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
        } finally {
            $pdo->rollBack();
            $_SESSION = [];
        }
    }

    public function testDosComprasDelMismoProductoNoSeBloqueanPorLasFk(): void
    {
        resetTestDb();
        $pdo = testPdo();
        $pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, precio_pen, stock)
            VALUES (1, 'concurrente', 'Celulares', 'Concurrente', '', 118, 10)");
        // Después de comprobar las FK, mantener la fila abierta para solapar ambas compras.
        $pdo->exec('CREATE FUNCTION jt_test_checkout_pausa() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN PERFORM pg_sleep(0.4); RETURN NEW; END; $$');
        $pdo->exec('CREATE TRIGGER zzzz_jt_test_checkout_pausa AFTER INSERT ON detalle_pedido
            FOR EACH ROW EXECUTE FUNCTION jt_test_checkout_pausa()');
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
            $pdo->exec('DROP TRIGGER zzzz_jt_test_checkout_pausa ON detalle_pedido');
            $pdo->exec('DROP FUNCTION jt_test_checkout_pausa()');
        }
    }
}
