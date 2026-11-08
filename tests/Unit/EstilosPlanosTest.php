<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EstilosPlanosTest extends TestCase
{
    /**
     * Degradados permitidos en rediseno.css (intencionales, con propósito UX/marca):
     * - .carousel-overlay: fade para legibilidad del texto sobre imagen hero
     * - .card-media / .pv-card-media: profundidad sutil tras imagen de producto
     * - .producto-galeria: profundidad sutil en ficha de producto
     * - .virtual-card: identidad visual de tarjeta virtual
     * - .auth-wrapper::before: panel de marca en login/registro
     */
    private static array $degradadosPermitidosRediseno = [
        '.carousel-overlay'       => 'linear-gradient',
        '.card-media'             => 'radial-gradient',
        '.producto-galeria'       => 'radial-gradient',
        '.virtual-card'           => 'linear-gradient',
        '.auth-wrapper::before'   => 'linear-gradient',
        '.pv-card-media'          => 'radial-gradient',
    ];

    #[DataProvider('hojasDeEstilo')]
    public function testCssPropioNoContieneDegradadosInesperados(string $archivo): void
    {
        $css = file_get_contents(__DIR__ . '/../../public/css/' . $archivo);
        $this->assertNotFalse($css);

        if ($archivo === 'rediseno.css') {
            $this->assertSoloDegradadosPermitidos($css);
        } else {
            $this->assertDoesNotMatchRegularExpression(
                '/(?:repeating-)?(?:linear|radial|conic)-gradient\s*\(/i',
                $css,
                'Quedan degradados inesperados en ' . $archivo
            );
        }
    }

    private function assertSoloDegradadosPermitidos(string $css): void
    {
        $lineas = explode("\n", $css);
        $selectoresPorLinea = [];
        $enRegla = '';

        // Primera pasada: para cada linea, guardar el selector de regla CSS activa
        foreach ($lineas as $num => $linea) {
            if (preg_match('/^\s*([.#][\w:.-]+[^{]*)\s*\{/', $linea, $m)) {
                $enRegla = trim($m[1]);
            }
            if ($enRegla !== '' && trim($linea) === '}') {
                $enRegla = '';
            }
            $selectoresPorLinea[$num] = $enRegla;
        }

        // Segunda pasada: verificar cada degradado
        $inesperados = [];
        foreach ($lineas as $num => $linea) {
            if (!preg_match('/(?:repeating-)?(?:linear|radial|conic)-gradient\s*\(/i', $linea)) {
                continue;
            }
            $selector = $selectoresPorLinea[$num] ?: '(linea ' . ($num + 1) . ')';
            $permitido = self::$degradadosPermitidosRediseno[$selector] ?? null;
            if ($permitido === null || !str_contains(strtolower($linea), strtolower($permitido))) {
                $inesperados[$selector][] = trim($linea);
            }
        }

        $this->assertEmpty(
            $inesperados,
            'Degradados no permitidos en rediseno.css: ' . json_encode($inesperados, JSON_UNESCAPED_UNICODE)
        );
    }

    public static function hojasDeEstilo(): array
    {
        return [
            ['styles.css'],
            ['auth.css'],
            ['cuenta.css'],
            ['chat.css'],
            ['admin.css'],
            ['rediseno.css'],
        ];
    }
}
