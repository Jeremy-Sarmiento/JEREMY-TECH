<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DetalleSeguroTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $sesionAnterior;

    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
        \Security\Proteccion::iniciarSesion();
        $this->sesionAnterior = $_SESSION;
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sesionAnterior;
    }

    #[DataProvider('nombres')]
    public function testLaConsultaConservaElTextoSinRomperElAtributoHtml(string $nombre, string $especificaciones): void
    {
        $dom = $this->renderDetalle($nombre, $especificaciones);
        $xpath = new DOMXPath($dom);
        $enlaces = $xpath->query('//a[@class="btn-whatsapp"]');
        $this->assertInstanceOf(DOMNodeList::class, $enlaces);
        $enlace = $enlaces->item(0);
        $this->assertInstanceOf(DOMElement::class, $enlace);
        $handler = $enlace->getAttribute('onclick');
        $this->assertMatchesRegularExpression('/^enviarWhatsApp\(event, (.+)\)$/s', $handler);
        $json = substr($handler, strlen('enviarWhatsApp(event, '), -1);
        $this->assertSame($nombre . ' - ' . $especificaciones, json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(3, $enlace->attributes->length, 'Solo class, href y onclick deben llegar al DOM.');
        $inyectados = $xpath->query('//*[@onmouseover or @onfocus or @onerror]');
        $this->assertInstanceOf(DOMNodeList::class, $inyectados);
        $this->assertSame(0, $inyectados->length);
    }

    /** @return array<string, array{string, string}> */
    public static function nombres(): array
    {
        return [
            'normal' => ['Portátil de prueba', '16 GB / 512 GB'],
            'comillas y entidades' => ['Equipo "Pro" & O\'Brien', '&quot; onfocus=alert(1)'],
            'intento de inyección' => ['" onmouseover="alert(1)', '\"><img src=x onerror=alert(1)>'],
        ];
    }

    public function testLaFichaTieneUnUnicoDestinoPrincipalParaElSalto(): void
    {
        $dom = $this->renderDetalle('Portátil de prueba', '16 GB');
        $this->assertSame(1, $dom->getElementsByTagName('main')->length);
        $main = $dom->getElementById('contenido');
        $this->assertInstanceOf(DOMElement::class, $main);
        $this->assertSame('main', $main->tagName);
        $this->assertSame('-1', $main->getAttribute('tabindex'));
    }

    public function testLaCabeceraEscapaElTituloDescriptivoDeLaFicha(): void
    {
        $tituloPagina = 'Equipo "Pro" & <img src=x onerror=alert(1)> - Jeremy Tech';
        $categoriasDisponibles = $marcasDisponibles = [];
        ob_start();
        try {
            require dirname(__DIR__, 2) . '/views/layout/header.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $dom = $this->parseHtml($html);
        $titulo = $dom->getElementsByTagName('title')->item(0);
        $this->assertInstanceOf(DOMElement::class, $titulo);
        $this->assertSame($tituloPagina, $titulo->textContent);
        $inyectados = (new DOMXPath($dom))->query('//*[@onerror]');
        $this->assertInstanceOf(DOMNodeList::class, $inyectados);
        $this->assertSame(0, $inyectados->length);
    }

    private function renderDetalle(string $nombre, string $especificaciones): DOMDocument
    {
        $producto = [
            'id' => 1, 'nombre' => $nombre, 'especificaciones' => $especificaciones,
            'precioPEN' => 1200.0, 'stock' => 5, 'vendedorId' => null,
            'colores' => [], 'modelo' => null, 'codigoSKU' => null,
            'pesoKg' => null, 'dimensiones' => null, 'categoria' => 'Prueba',
            'slug' => 'prueba', 'imagen' => 'img/prueba.png', 'imagenDb' => false,
        ];
        $extras = $variantes = $tramos = $recomendados = $resenasProducto = [];
        $varianteInicial = null;
        $precioInicial = ['precio' => 1200.0];
        $clienteId = null;
        $puedeOpinar = false;
        ob_start();
        try {
            require dirname(__DIR__, 2) . '/views/detalle.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $this->parseHtml($html);
    }

    private function parseHtml(string $html): DOMDocument
    {
        $anterior = libxml_use_internal_errors(true);
        try {
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            return $dom;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
    }
}
