<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Controller\CuentaController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecuperacionEnlaceTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testElEnlaceDemoSigueSiendoClicableAlRecuperarElMensajeDeLaSesion(): void
    {
        $enlace = APP_URL . '/index.php?ruta=login/restablecer&token=' . str_repeat('a', 64);
        $dom = $this->renderizar([
            'tipo' => 'ok',
            'mensaje' => 'Enlace de recuperación generado (modo demo):<br>'
                . '<a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
                . 'Restablecer contraseña</a>',
            'html' => true,
        ]);
        $enlaces = (new DOMXPath($dom))->query('//div[contains(@class, "auth-flash")]//a');

        $this->assertCount(1, $enlaces);
        $this->assertSame($enlace, $enlaces->item(0)->getAttribute('href'));
        $this->assertSame('Restablecer contraseña', $enlaces->item(0)->textContent);
        $this->assertSame('noopener', $enlaces->item(0)->getAttribute('rel'));
        $this->assertArrayNotHasKey('flash_login', $_SESSION);
    }

    #[DataProvider('mensajesSinHtmlAutorizado')]
    public function testLosMensajesNormalesNoInterpretanHtml(array $flash): void
    {
        $dom = $this->renderizar($flash);
        $xpath = new DOMXPath($dom);
        $aviso = $xpath->query('//div[contains(@class, "auth-flash")]')->item(0);

        $this->assertSame($flash['mensaje'], trim($aviso->textContent));
        $this->assertCount(0, $xpath->query('.//*', $aviso));
    }

    public static function mensajesSinHtmlAutorizado(): array
    {
        $mensaje = '<script>alert("xss")</script><a href="javascript:alert(1)">Enlace falso</a>';
        return [
            'sin marca' => [['tipo' => 'error', 'mensaje' => $mensaje]],
            'texto normal' => [['tipo' => 'error', 'mensaje' => $mensaje, 'html' => false]],
            'marca no booleana' => [['tipo' => 'error', 'mensaje' => $mensaje, 'html' => 'true']],
        ];
    }

    private function renderizar(array $flash): DOMDocument
    {
        $_SESSION['flash_login'] = $flash;
        $servidor = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        ob_start();
        try {
            (new CuentaController())->recuperarToken();
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $_SERVER = $servidor;
        }
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
