<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EstilosPlanosTest extends TestCase
{
    // Solo se permiten los degradados conservados en la capa de tema.
    private const DEGRADADOS_PERMITIDOS = [
        'tema/catalogo.css' => [
            '.carousel-overlay' => 'linear-gradient',
            '.card-media' => 'radial-gradient',
        ],
        'tema/producto.css' => ['.producto-galeria' => 'radial-gradient'],
        'tema/compra.css' => ['.virtual-card' => 'linear-gradient'],
        'tema/cuenta.css' => ['.pv-card-media' => 'radial-gradient'],
    ];

    #[DataProvider('hojasDeEstilo')]
    public function testCssPropioNoContieneDegradadosInesperados(string $archivo): void
    {
        $css = file_get_contents(__DIR__ . '/../../public/css/' . $archivo);
        $this->assertNotFalse($css);

        if (isset(self::DEGRADADOS_PERMITIDOS[$archivo])) {
            $this->assertSoloDegradadosPermitidos($css, $archivo);
        } else {
            $this->assertDoesNotMatchRegularExpression(
                '/(?:repeating-)?(?:linear|radial|conic)-gradient\s*\(/i',
                $css,
                'Quedan degradados inesperados en ' . $archivo
            );
        }
    }

    private function assertSoloDegradadosPermitidos(string $css, string $archivo): void
    {
        $lineas = explode("\n", $css);
        $enRegla = '';
        $inesperados = [];

        foreach ($lineas as $num => $linea) {
            if (preg_match('/^\s*([.#][\w:.-]+[^{]*)\s*\{/', $linea, $m)) {
                $enRegla = trim($m[1]);
            }
            if (preg_match_all('/(?:repeating-)?(?:linear|radial|conic)-gradient\s*\(/i', $linea, $coincidencias)) {
                foreach ($coincidencias[0] as $degradado) {
                    $selector = $enRegla ?: '(linea ' . ($num + 1) . ')';
                    $tipo = strtolower(trim(str_replace('(', '', $degradado)));
                    if ((self::DEGRADADOS_PERMITIDOS[$archivo][$selector] ?? null) !== $tipo) {
                        $inesperados[] = $selector . ': ' . $tipo;
                    }
                }
            }
            if ($enRegla !== '' && trim($linea) === '}') {
                $enRegla = '';
            }
        }

        $this->assertEmpty(
            $inesperados,
            'Degradados no permitidos en ' . $archivo . ': ' . json_encode($inesperados, JSON_UNESCAPED_UNICODE)
        );
    }

    #[DataProvider('degradadosNoPermitidos')]
    public function testComprobacionRechazaDegradadosFueraDelContrato(string $css): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->assertSoloDegradadosPermitidos($css, 'tema/catalogo.css');
    }

    public static function degradadosNoPermitidos(): array
    {
        return [
            ['.otro { background: linear-gradient(red, blue); }'],
            ['.card-media { background: repeating-radial-gradient(red, blue); }'],
            ['.card-media { background: radial-gradient(red, blue), conic-gradient(red, blue); }'],
        ];
    }

    public static function hojasDeEstilo(): array
    {
        $directorio = __DIR__ . '/../../public/css/';
        $hojas = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio)) as $archivo) {
            if ($archivo->isFile() && $archivo->getExtension() === 'css') {
                $hojas[] = [substr($archivo->getPathname(), strlen($directorio))];
            }
        }
        sort($hojas);
        return $hojas;
    }
}
