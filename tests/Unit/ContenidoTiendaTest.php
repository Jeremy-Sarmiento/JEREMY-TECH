<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContenidoTiendaTest extends TestCase
{
    #[DataProvider('vistas')]
    public function testCadaVistaDeclaraUnSoloDestinoPrincipalDeSalto(string $ruta): void
    {
        $plantilla = (string) file_get_contents($ruta);
        preg_match_all('/<main\b[^>]*>/i', $plantilla, $aperturas);
        $this->assertCount(1, $aperturas[0]);
        $dom = new DOMDocument();
        $anterior = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML($aperturas[0][0] . '</main>');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
        $main = $dom->getElementById('contenido');
        $this->assertInstanceOf(DOMElement::class, $main);
        $this->assertSame('-1', $main->getAttribute('tabindex'));
    }

    /** @return array<string, array{string}> */
    public static function vistas(): array
    {
        $base = dirname(__DIR__, 2) . '/views/';
        $rutas = array_merge(
            // La 404 es un documento independiente, sin la cabecera con skip-link.
            array_diff(glob($base . '*.php') ?: [], [$base . 'error404.php']),
            glob($base . 'vendedor/panel_*.php') ?: [],
            [$base . 'vendedor/reportes.php']
        );
        return array_combine(array_map('basename', $rutas), array_map(static fn (string $ruta): array => [$ruta], $rutas));
    }
}
