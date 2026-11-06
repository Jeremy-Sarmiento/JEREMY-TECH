<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class AdminInicialTest extends TestCase
{
    public function testConfiguraAdminSinImprimirClaveNiAscenderClientes(): void
    {
        resetTestDb();
        [$codigo, $salida] = $this->ejecutar('admin@example.com', 'Nueva-Clave-2026!');
        $this->assertSame(0, $codigo, $salida);
        $this->assertStringNotContainsString('Nueva-Clave-2026!', $salida);
        $hash = testPdo()->query('SELECT password FROM users WHERE id = 1')->fetchColumn();
        $this->assertTrue(password_verify('Nueva-Clave-2026!', $hash));
        $this->assertFalse(password_verify('admin', $hash));

        [$codigo] = $this->ejecutar('cliente@example.com', 'Nueva-Clave-2026!');
        $this->assertSame(1, $codigo);
        $this->assertSame('cliente', testPdo()->query('SELECT rol FROM users WHERE id = 3')->fetchColumn());

        [$codigo] = $this->ejecutar('nuevo-admin@example.com', '12345678');
        $this->assertSame(1, $codigo);
        $this->assertEquals(0, testPdo()->query("SELECT COUNT(*) FROM users WHERE email = 'nuevo-admin@example.com'")->fetchColumn());

        [$codigo, $salida] = $this->ejecutar('nuevo-admin@example.com', 'Nueva-Clave-2026!', 'nuevo-admin');
        $this->assertSame(0, $codigo, $salida);
        $this->assertSame('admin', testPdo()->query("SELECT rol FROM users WHERE email = 'nuevo-admin@example.com'")->fetchColumn());
    }

    private function ejecutar(string $email, string $clave, string $usuario = 'admin'): array
    {
        $entorno = array_merge(getenv(), [
            'DB_HOST' => DB_HOST,
            'DB_NAME' => DB_NAME, 'DB_USER' => DB_USER, 'DB_PASS' => DB_PASS,
            'ADMIN_EMAIL' => $email, 'ADMIN_USUARIO' => $usuario, 'ADMIN_INITIAL_PASSWORD' => $clave,
        ]);
        $proceso = proc_open([PHP_BINARY, BASE_PATH . '/tools/establecer-admin.php'], [
            1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, BASE_PATH, $entorno);
        $this->assertIsResource($proceso);
        $salida = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($proceso), $salida];
    }
}
