<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\ImagenProducto;
use Model\Producto;
use PHPUnit\Framework\TestCase;

class ImagenProductoTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function testAceptaUnaImagenReal(): void
    {
        $ruta = (string) tempnam(sys_get_temp_dir(), 'jt-img');
        file_put_contents($ruta, (string) base64_decode(self::PNG, true));

        try {
            $resultado = ImagenProducto::validarArchivo($ruta, (int) filesize($ruta));
        } finally {
            unlink($ruta);
        }

        $this->assertSame('ok', $resultado['estado']);
        $this->assertSame('image/png', $resultado['mime']);
        $this->assertNotEmpty($resultado['datos']);
    }

    public function testRechazaTextoQueNoEsImagen(): void
    {
        $ruta = (string) tempnam(sys_get_temp_dir(), 'jt-img');
        file_put_contents($ruta, 'no soy una imagen');

        try {
            $resultado = ImagenProducto::validarArchivo($ruta, (int) filesize($ruta));
        } finally {
            unlink($ruta);
        }

        $this->assertSame('error', $resultado['estado']);
    }

    public function testRechazaSvgYArchivosDemasiadoGrandes(): void
    {
        $ruta = (string) tempnam(sys_get_temp_dir(), 'jt-img');
        file_put_contents($ruta, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        try {
            $this->assertSame('error', ImagenProducto::validarArchivo($ruta, (int) filesize($ruta))['estado']);
        } finally {
            unlink($ruta);
        }

        $this->assertSame(
            'error',
            ImagenProducto::validarArchivo($ruta, ImagenProducto::MAX_BYTES + 1)['estado']
        );
    }

    public function testUrlImagenPriorizaLaDeLaBase(): void
    {
        $this->assertSame(
            '?ruta=producto/imagen&slug=mi%20producto',
            Producto::urlImagen(['slug' => 'mi producto', 'imagen' => 'img/x.png', 'imagenDb' => true])
        );
        $this->assertSame(
            'img/x.png',
            Producto::urlImagen(['slug' => 'x', 'imagen' => 'img/x.png', 'imagenDb' => false])
        );
    }

    public function testConviertePngAWebp(): void
    {
        $png = (string) base64_decode(self::PNG, true);
        $webp = ImagenProducto::aWebp($png, 'image/png');

        $this->assertNotNull($webp);
        $this->assertStringStartsWith('RIFF', $webp);
        $this->assertSame('WEBP', substr($webp, 8, 4));
    }

    public function testConservaWebp(): void
    {
        $webpOriginal = "RIFF\x20\x00\x00\x00WEBPVP8 ";
        $resultado = ImagenProducto::aWebp($webpOriginal, 'image/webp');
        $this->assertSame($webpOriginal, $resultado);
    }
}
