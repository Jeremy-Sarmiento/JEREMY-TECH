<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Pasarela;
use PHPUnit\Framework\TestCase;

class PasarelaTest extends TestCase
{
    private string|false $secreto;

    protected function setUp(): void
    {
        resetTestDb();
        $this->secreto = getenv('PAGOS_WEBHOOK_SECRET');
        putenv('PAGOS_WEBHOOK_SECRET=secreto-solo-para-tests');
        testPdo()->exec("INSERT INTO pedidos (id, cliente_id, total_pen, estado) VALUES (1, 3, 118, 'Pendiente')");
        testPdo()->exec("INSERT INTO pagos (id, pedido_id, metodo, monto_pen, estado) VALUES (1, 1, 'Tarjeta', 118, 'Pendiente')");
    }

    protected function tearDown(): void
    {
        putenv($this->secreto === false ? 'PAGOS_WEBHOOK_SECRET' : 'PAGOS_WEBHOOK_SECRET=' . $this->secreto);
    }

    public function testRepetirUnEventoDevuelve200SinDuplicarHitos(): void
    {
        $this->assertSame(200, $this->evento('evt-1', 'approved')['codigo']);
        $this->assertSame(200, $this->evento('evt-1', 'approved')['codigo']);
        $this->assertSame('Pagado', testPdo()->query('SELECT estado FROM pagos WHERE id = 1')->fetchColumn());
        $this->assertEquals(1, testPdo()->query('SELECT COUNT(*) FROM pagos_webhooks')->fetchColumn());
        $this->assertEquals(1, testPdo()->query('SELECT COUNT(*) FROM pedido_eventos')->fetchColumn());
    }

    public function testEventoAtrasadoNoDegradaUnPagoConfirmado(): void
    {
        $this->evento('aprobado', 'approved');
        foreach (['rejected', 'pending'] as $estado) {
            $this->assertSame(200, $this->evento('atrasado-' . $estado, $estado)['codigo']);
            $this->assertSame('Pagado', testPdo()->query('SELECT estado FROM pagos WHERE id = 1')->fetchColumn());
            $this->assertSame('Pagado', testPdo()->query('SELECT estado FROM pedidos WHERE id = 1')->fetchColumn());
        }
    }

    public function testUnPagoReembolsadoNoSeReabrePorUnEventoAtrasado(): void
    {
        $this->evento('aprobado', 'approved');
        $this->evento('reembolso', 'refunded');
        $this->assertSame(200, $this->evento('aprobado-atrasado', 'approved')['codigo']);
        $this->assertSame('Reembolsado', testPdo()->query('SELECT estado FROM pagos WHERE id = 1')->fetchColumn());
        $this->assertSame('Cancelado', testPdo()->query('SELECT estado FROM pedidos WHERE id = 1')->fetchColumn());
    }

    public function testUnaFirmaFalsaNoModificaElPedido(): void
    {
        $resultado = Pasarela::procesarWebhook('{}', ['x-jt-timestamp' => (string) time(), 'x-jt-signature' => 'falsa']);
        $this->assertSame(401, $resultado['codigo']);
        $this->assertSame('Pendiente', testPdo()->query('SELECT estado FROM pedidos WHERE id = 1')->fetchColumn());
        $this->assertEquals(0, testPdo()->query('SELECT COUNT(*) FROM pagos_webhooks')->fetchColumn());
    }

    private function evento(string $id, string $estado): array
    {
        $cuerpo = json_encode(['id' => $id, 'data' => ['pago_id' => 1, 'status' => $estado]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        return Pasarela::procesarWebhook($cuerpo, [
            'x-jt-timestamp' => (string) $timestamp,
            'x-jt-signature' => Pasarela::firmar($cuerpo, $timestamp),
        ]);
    }
}
