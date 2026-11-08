<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EstilosCargaTest extends TestCase
{
    private const ORDEN = [
        'base' => [
            'base/documento.css', 'componentes/catalogo.css', 'paginas/compra.css',
            'paginas/pedidos.css', 'base/responsive.css', 'componentes/catalogo-producto.css',
            'componentes/busqueda.css', 'paginas/contenido.css',
        ],
        'carrito' => ['paginas/carrito.css'],
        'auth' => ['paginas/auth.css'],
        'cuenta' => [
            'paginas/cuenta/perfil.css', 'paginas/cuenta/favoritos-notificaciones.css',
            'paginas/cuenta/pedidos-resenas.css', 'paginas/cuenta/vendedor.css',
            'paginas/cuenta/soporte.css',
        ],
        'chat' => ['componentes/chat.css'],
        'admin' => ['paginas/admin.css'],
        'tema' => [
            'tema/base.css', 'tema/navegacion.css', 'tema/catalogo.css', 'tema/producto.css',
            'tema/compra.css', 'tema/auth.css', 'tema/cuenta.css', 'tema/contenido.css',
            'tema/chat.css', 'tema/admin.css', 'tema/responsive.css',
        ],
    ];

    public function testTodasLasHojasEstanEnElOrdenDeCargaSinDuplicados(): void
    {
        $this->assertFileExists(BASE_PATH . '/config/estilos.php');
        $grupos = require BASE_PATH . '/config/estilos.php';
        $this->assertSame(self::ORDEN, $grupos);
        $hojas = array_merge(...array_values($grupos));
        $this->assertSame($hojas, array_values(array_unique($hojas)));

        $archivos = [];
        $directorio = BASE_PATH . '/public/css/';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio)) as $archivo) {
            if ($archivo->isFile() && $archivo->getExtension() === 'css') {
                $archivos[] = substr($archivo->getPathname(), strlen($directorio));
            }
        }
        sort($archivos);
        sort($hojas);
        $this->assertSame($archivos, $hojas, 'Cada CSS debe estar registrado una sola vez.');
    }

    #[DataProvider('layouts')]
    public function testEnlacesVersionadosConservanElOrdenYLeafletSoloEnLogin(array $gruposEsperados, string $rutaVista): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        $categoriasDisponibles = [];
        $marcasDisponibles = [];
        $paginaAdmin = in_array('admin', $gruposEsperados, true);
        $cabecera = $gruposEsperados === ['admin', 'tema'] ? 'admin/header.php' : 'layout/header.php';
        ob_start();
        require BASE_PATH . '/views/' . $cabecera;
        $html = (string) ob_get_clean();
        $dom = new DOMDocument();
        $erroresPrevios = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($erroresPrevios);
        }
        $enlaces = [];
        foreach ($dom->getElementsByTagName('link') as $enlace) {
            $href = $enlace->getAttribute('href');
            if (str_starts_with($href, 'css/') || str_contains($href, 'leaflet.css')) {
                $enlaces[] = $href;
            }
        }

        $esperados = [];
        foreach ($gruposEsperados as $grupo) {
            foreach (self::ORDEN[$grupo] as $hoja) {
                $ruta = BASE_PATH . '/public/css/' . $hoja;
                $this->assertFileExists($ruta);
                $esperados[] = 'css/' . $hoja . '?v=' . filemtime($ruta);
            }
            if ($grupo === 'auth' && $rutaVista === 'login') {
                $esperados[] = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            }
        }
        $this->assertSame($esperados, $enlaces);
        $this->assertStringNotContainsString('?v=0', $html);
    }

    public static function layouts(): array
    {
        return [
            'tienda' => [['base', 'carrito', 'auth', 'cuenta', 'chat', 'tema'], 'catalogo'],
            'login' => [['base', 'carrito', 'auth', 'cuenta', 'chat', 'tema'], 'login'],
            'tienda con admin' => [['base', 'carrito', 'auth', 'cuenta', 'chat', 'admin', 'tema'], 'catalogo'],
            'panel admin' => [['admin', 'tema'], ''],
        ];
    }
}
