<?php

declare(strict_types=1);

use Model\LimiteIntentos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Security\Proteccion;

final class ProteccionTest extends TestCase
{
    private array $servidor;
    private mixed $proxies;

    protected function setUp(): void
    {
        $this->servidor = $_SERVER;
        $this->proxies = getenv('TRUSTED_PROXIES');
        putenv('TRUSTED_PROXIES=');
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->servidor;
        putenv($this->proxies === false ? 'TRUSTED_PROXIES' : 'TRUSTED_PROXIES=' . $this->proxies);
    }

    public function testNonceEsEstableDuranteLaPeticion(): void
    {
        $nonce = Proteccion::nonce();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $nonce);
        $this->assertSame($nonce, Proteccion::nonce());
    }

    public function testNonceNoSeReutilizaDeLaSesionEntrePeticiones(): void
    {
        $codigo = 'require ' . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ';'
            . '$_SESSION = ["_csp_nonce" => "nonce-de-sesion-anterior"];'
            . '$nonce = \\Security\\Proteccion::nonce();'
            . 'echo json_encode([$nonce, $_SESSION["_csp_nonce"], session_status()]);';
        $resultados = [];
        for ($i = 0; $i < 2; $i++) {
            $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $this->assertIsResource($proceso);
            $salida = stream_get_contents($pipes[1]);
            $errores = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($proceso), $errores);
            $resultados[] = json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->assertNotSame($resultados[0][0], $resultados[1][0]);
        $this->assertSame('nonce-de-sesion-anterior', $resultados[0][1]);
        $this->assertSame(PHP_SESSION_NONE, $resultados[0][2]);
    }

    public function testTodosLosScriptsInlineDeLasVistasTienenNonce(): void
    {
        $vistas = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/views'));
        foreach ($vistas as $vista) {
            if ($vista->getExtension() !== 'php') {
                continue;
            }
            preg_match_all('/<script\b[^>]*>/i', file_get_contents($vista->getPathname()), $scripts);
            foreach ($scripts[0] as $script) {
                if (!str_contains($script, 'src=')) {
                    $this->assertStringContainsString('Proteccion::nonce()', $script, $vista->getPathname());
                }
            }
        }
    }

    #[DataProvider('casosIp')]
    public function testIpSoloAceptaLaCadenaDeProxiesConfigurados(
        string $proxies,
        mixed $remoto,
        mixed $forwarded,
        string $esperado
    ): void {
        putenv('TRUSTED_PROXIES=' . $proxies);
        $_SERVER['REMOTE_ADDR'] = $remoto;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $forwarded;

        $this->assertSame($esperado, LimiteIntentos::ipActual());
    }

    public static function casosIp(): array
    {
        return [
            'sin proxies ignora spoof' => ['', '198.51.100.8', '203.0.113.9', '198.51.100.8'],
            'peer no confiable' => ['10.0.0.1', '198.51.100.8', '203.0.113.9', '198.51.100.8'],
            'privada no es confianza implicita' => ['', '10.0.0.1', '203.0.113.9', '10.0.0.1'],
            'proxy explicito' => ['10.0.0.1', '10.0.0.1', '203.0.113.9', '203.0.113.9'],
            'cadena de derecha a izquierda' => ['10.0.0.1, 10.0.0.2', '10.0.0.1', '192.0.2.99, 203.0.113.9, 10.0.0.2', '203.0.113.9'],
            'limite no confiable' => ['10.0.0.1', '10.0.0.1', '192.0.2.99, 198.51.100.8', '198.51.100.8'],
            'IPv6 equivalente' => ['2001:db8::1', '2001:0db8:0:0:0:0:0:1', '2001:db8::8', '2001:db8::8'],
            'lista vacia' => ['10.0.0.1', '10.0.0.1', '', '10.0.0.1'],
            'cadena invalida' => ['10.0.0.1', '10.0.0.1', '203.0.113.9, basura', '10.0.0.1'],
            'cadena con puerto' => ['10.0.0.1', '10.0.0.1', '203.0.113.9:1234', '10.0.0.1'],
            'eslabon vacio' => ['10.0.0.1', '10.0.0.1', '203.0.113.9,', '10.0.0.1'],
            'cabecera no string' => ['10.0.0.1', '10.0.0.1', [], '10.0.0.1'],
            'peer invalido' => ['10.0.0.1', 'invalido', '203.0.113.9', ''],
            'peer no string' => ['10.0.0.1', [], '203.0.113.9', ''],
            'config invalida no confia' => ['*', '10.0.0.1', '203.0.113.9', '10.0.0.1'],
        ];
    }

    #[DataProvider('casosHttps')]
    public function testHttpsSoloConfiaEnProtoDeUnProxyExplicito(
        string $proxies,
        string $remoto,
        string $https,
        string $proto,
        bool $esperado
    ): void {
        putenv('TRUSTED_PROXIES=' . $proxies);
        $_SERVER = ['REMOTE_ADDR' => $remoto, 'HTTPS' => $https, 'HTTP_X_FORWARDED_PROTO' => $proto];

        $this->assertSame($esperado, Proteccion::esHttps());
    }

    public static function casosHttps(): array
    {
        return [
            ['', '198.51.100.8', 'on', '', true],
            ['', '198.51.100.8', 'off', 'https', false],
            ['10.0.0.1', '198.51.100.8', '', 'https', false],
            ['10.0.0.1', '10.0.0.1', '', 'https', true],
            ['10.0.0.1', '10.0.0.1', '', 'http', false],
            ['10.0.0.1', '10.0.0.1', '', 'https,http', false],
        ];
    }
}
