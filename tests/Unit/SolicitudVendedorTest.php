<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\SolicitudVendedor;
use PHPUnit\Framework\TestCase;

class SolicitudVendedorTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
    }

    public function testCrearSolicitud(): void
    {
        $clienteId = 3; // cliente@example.com
        $r = SolicitudVendedor::crear($clienteId, 'Quiero vender accesorios');
        $this->assertTrue($r['ok']);
        $this->assertIsInt($r['id']);
        $this->assertGreaterThan(0, $r['id']);
    }

    public function testNoDuplicaPendiente(): void
    {
        $clienteId = 3;
        $r1 = SolicitudVendedor::crear($clienteId, 'Primera');
        $this->assertTrue($r1['ok']);
        $r2 = SolicitudVendedor::crear($clienteId, 'Segunda');
        $this->assertFalse($r2['ok']);
        $this->assertStringContainsString('pendiente', $r2['error'] ?? '');
    }

    public function testListarYPendientesCount(): void
    {
        $clienteId = 3;
        SolicitudVendedor::crear($clienteId, 'Test listar');
        $lista = SolicitudVendedor::listar('pendiente');
        $this->assertGreaterThanOrEqual(1, count($lista));
        $this->assertEquals('pendiente', $lista[0]['estado'] ?? '');

        $count = SolicitudVendedor::pendientesCount();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testResumen(): void
    {
        $resumen = SolicitudVendedor::resumen();
        $this->assertArrayHasKey('total', $resumen);
        $this->assertArrayHasKey('pendientes', $resumen);
        $this->assertArrayHasKey('aprobadas', $resumen);
        $this->assertArrayHasKey('rechazadas', $resumen);
        $this->assertIsInt($resumen['total']);
    }

    public function testAprobarCambiaRol(): void
    {
        $clienteId = 3;
        $r = SolicitudVendedor::crear($clienteId, 'Aprobarme');
        $this->assertTrue($r['ok']);
        $sid = $r['id'];

        $error = SolicitudVendedor::aprobar($sid, 'testadmin');
        $this->assertNull($error);

        $pdo = testPdo();
        $rol = $pdo->prepare('SELECT rol FROM users WHERE id = ?');
        $rol->execute([$clienteId]);
        $this->assertEquals('vendedor', $rol->fetchColumn());
    }

    public function testAprobarConservaDatos(): void
    {
        $clienteId = 3;
        $pdo = testPdo();
        $stmt = $pdo->prepare('SELECT name, apellidos, email, tipo, empresa, ruc, dni, telefono, direccion FROM users WHERE id = ?');
        $stmt->execute([$clienteId]);
        $datosAntes = $stmt->fetch(PDO::FETCH_ASSOC);

        $r = SolicitudVendedor::crear($clienteId, 'Conservar datos');
        $sid = $r['id'];
        SolicitudVendedor::aprobar($sid, 'testadmin');

        $stmt = $pdo->prepare('SELECT name, apellidos, email, tipo, empresa, ruc, dni, telefono, direccion, rol FROM users WHERE id = ?');
        $stmt->execute([$clienteId]);
        $datosDespues = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('vendedor', $datosDespues['rol']);
        unset($datosDespues['rol']);
        $this->assertEquals($datosAntes, $datosDespues);
    }

    public function testRechazarNoCambiaRol(): void
    {
        $clienteId = 4; // cliente_b2b
        $r = SolicitudVendedor::crear($clienteId, 'Rechazarme');
        $sid = $r['id'];

        $error = SolicitudVendedor::rechazar($sid, 'testadmin', 'No cumple requisitos');
        $this->assertNull($error);

        $pdo = testPdo();
        $rol = $pdo->prepare('SELECT rol FROM users WHERE id = ?');
        $rol->execute([$clienteId]);
        $this->assertEquals('cliente', $rol->fetchColumn());
    }

    public function testEtiquetasEstado(): void
    {
        $this->assertEquals('Pendiente', SolicitudVendedor::etiquetaEstado('pendiente'));
        $this->assertEquals('Aprobada', SolicitudVendedor::etiquetaEstado('aprobada'));
        $this->assertEquals('Rechazada', SolicitudVendedor::etiquetaEstado('rechazada'));
    }
}
