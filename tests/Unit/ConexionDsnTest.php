<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class ConexionDsnTest extends TestCase
{
    public function testTraduceLaUrlDeSupabase(): void
    {
        [$dsn, $usuario, $clave] = dsn_desde_url(
            'postgresql://postgres.abc:clave%40con%23simbolos@aws-0-us-east-1.pooler.supabase.com:6543/postgres'
        );

        $this->assertSame(
            'pgsql:host=aws-0-us-east-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require',
            $dsn
        );
        $this->assertSame('postgres.abc', $usuario);
        $this->assertSame('clave@con#simbolos', $clave);
    }

    public function testRespetaElSslmodeDeLaUrl(): void
    {
        [$dsn] = dsn_desde_url('postgres://u:p@127.0.0.1:5432/jeremy_tech_test?sslmode=disable');

        $this->assertSame(
            'pgsql:host=127.0.0.1;port=5432;dbname=jeremy_tech_test;sslmode=disable',
            $dsn
        );
    }

    public function testRechazaUnaUrlSinHost(): void
    {
        $this->expectException(RuntimeException::class);
        dsn_desde_url('no-es-una-url');
    }
}
