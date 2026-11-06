<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormularioRegistroTest extends TestCase
{
    #[DataProvider('tipos')]
    public function testRucSoloEsObligatorioParaEmpresas(string $tipo): void
    {
        $registroPrevio = ['tipo_cliente' => $tipo];
        ob_start();
        require BASE_PATH . '/views/login.php';
        $html = (string) ob_get_clean();
        $anterior = libxml_use_internal_errors(true);
        try {
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $ruc = $dom->getElementById('regRuc');
            $this->assertNotNull($ruc);
            $this->assertSame($tipo === 'B2B', $ruc->hasAttribute('required'));
            $this->assertSame($tipo !== 'B2B', $ruc->hasAttribute('disabled'));
            $this->assertSame($tipo === 'B2B' ? '120' : '255', $dom->getElementById('regNombre')->getAttribute('maxlength'));
            $etiqueta = (new DOMXPath($dom))->query('//label[@for="regPassword"]')->item(0);
            $this->assertStringContainsString('mín. 8', $etiqueta->textContent);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
    }

    public static function tipos(): array
    {
        return [['B2C'], ['B2B'], ['C2C']];
    }
}
