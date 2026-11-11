<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

final class PwaInstalacionTest extends TestCase
{
    private function manifiesto(): array
    {
        $ruta = BASE_PATH . '/public/manifest.webmanifest';
        $this->assertFileExists($ruta);
        $datos = json_decode((string) file_get_contents($ruta), true);
        $this->assertIsArray($datos, 'El manifiesto debe ser JSON válido.');

        return $datos;
    }

    public function testElManifiestoTieneLoMinimoParaInstalar(): void
    {
        $datos = $this->manifiesto();

        foreach (['name', 'short_name', 'start_url', 'scope', 'display', 'background_color', 'theme_color', 'icons'] as $clave) {
            $this->assertArrayHasKey($clave, $datos, 'Falta ' . $clave . ' en el manifiesto.');
        }
        $this->assertSame('standalone', $datos['display']);
        $this->assertNotSame('', trim((string) $datos['name']));
        $this->assertNotSame('', trim((string) $datos['start_url']));
    }

    public function testLosIconosDeclaradosExistenConSuTamano(): void
    {
        $datos = $this->manifiesto();
        $tamanos = [];

        foreach ($datos['icons'] as $icono) {
            $ruta = BASE_PATH . '/public/' . ltrim((string) $icono['src'], './');
            $this->assertFileExists($ruta, 'Icono inexistente: ' . $ruta);
            $info = getimagesize($ruta);
            $this->assertIsArray($info);
            $this->assertSame($icono['sizes'], $info[0] . 'x' . $info[1]);
            $tamanos[$icono['sizes']] = true;
        }

        $this->assertArrayHasKey('192x192', $tamanos);
        $this->assertArrayHasKey('512x512', $tamanos);
    }

    public function testLaCabeceraDeclaraElManifiestoYLosMetadatosDeInstalacion(): void
    {
        $cabecera = (string) file_get_contents(BASE_PATH . '/views/layout/header.php');

        $this->assertStringContainsString('rel="manifest"', $cabecera);
        $this->assertStringContainsString('manifest.webmanifest', $cabecera);
        $this->assertStringContainsString('name="apple-mobile-web-app-capable"', $cabecera);
        $this->assertStringContainsString('name="mobile-web-app-capable"', $cabecera);
    }

    public function testElFooterRegistraElServiceWorker(): void
    {
        $footer = (string) file_get_contents(BASE_PATH . '/views/layout/footer.php');

        $this->assertStringContainsString("serviceWorker.register('sw.js')", $footer);
    }

    public function testElServiceWorkerSoloCacheaAssetsEstaticos(): void
    {
        $sw = (string) file_get_contents(BASE_PATH . '/public/sw.js');

        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        $this->assertStringContainsString("addEventListener('install'", $sw);
        $this->assertStringContainsString('caches.open', $sw);
        $this->assertStringNotContainsString('index.php', $sw, 'El HTML con CSRF no debe cachearse.');
    }
}
