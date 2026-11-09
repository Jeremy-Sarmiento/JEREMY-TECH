<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnrutadorTest extends TestCase
{
    #[DataProvider('rutas')]
    public function testRespondeSegunLaRuta(string $uri, string $scriptName, int $estado, string $contenido): void
    {
        // Ejecuta el router real con un catálogo simulado, sin cargar .env ni conectar a PostgreSQL.
        $directorio = sys_get_temp_dir() . '/jt-router-' . bin2hex(random_bytes(6));
        mkdir($directorio . '/public', 0700, true);
        mkdir($directorio . '/config', 0700);
        copy(__DIR__ . '/../../public/index.php', $directorio . '/public/index.php');
        $basePath = var_export(dirname(__DIR__, 2), true);
        file_put_contents($directorio . '/config/conexion.php', <<<PHP
<?php
namespace {
    define('BASE_PATH', $basePath);
    require BASE_PATH . '/src/Security/Proteccion.php';
}
namespace Controller {
    class ProductoController {
        public function catalogo(): void { echo 'CATALOGO'; }
        public function sugerencias(): void { echo 'SUGERENCIAS'; }
    }
}
PHP);
        $codigo = '$_SERVER = ' . var_export([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => $uri,
            'SCRIPT_NAME' => $scriptName,
        ], true) . ';'
            . 'parse_str(' . var_export((string) parse_url($uri, PHP_URL_QUERY), true) . ', $_GET);'
            . 'http_response_code(200); ob_start();'
            . 'register_shutdown_function(static function (): void {'
            . 'echo json_encode(["estado" => http_response_code(), "html" => ob_get_clean()]); });'
            . 'require ' . var_export($directorio . '/public/index.php', true) . ';';

        try {
            $proceso = proc_open(
                [PHP_BINARY, '-d', 'session.save_path=' . $directorio, '-r', $codigo],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            $this->assertIsResource($proceso);
            $salida = stream_get_contents($pipes[1]);
            $errores = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($proceso), $errores);
            $this->assertSame('', $errores);
            $respuesta = json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame($estado, $respuesta['estado']);
            $this->assertStringContainsString($contenido, $respuesta['html']);
            if ($estado === 404) {
                $this->assertStringContainsString('<html lang="es">', $respuesta['html']);
                $this->assertStringContainsString('<main>', $respuesta['html']);
                $this->assertStringNotContainsString('<script>', $respuesta['html']);
                $this->assertStringContainsString(
                    'href="' . rtrim(dirname($scriptName), '/') . '/index.php?ruta=catalogo"',
                    $respuesta['html']
                );
            }
        } finally {
            foreach (glob($directorio . '/sess_*') as $archivo) {
                unlink($archivo);
            }
            unlink($directorio . '/public/index.php');
            unlink($directorio . '/config/conexion.php');
            rmdir($directorio . '/public');
            rmdir($directorio . '/config');
            rmdir($directorio);
        }
    }

    public static function rutas(): array
    {
        return [
            'portada' => ['/', '/index.php', 200, 'CATALOGO'],
            'index' => ['/index.php', '/index.php', 200, 'CATALOGO'],
            'catalogo explicito' => ['/?ruta=catalogo&q=demo', '/index.php', 200, 'CATALOGO'],
            'ruta conocida' => ['/index.php?ruta=catalogo/sugerencias', '/index.php', 200, 'SUGERENCIAS'],
            'ruta desconocida' => ['/?ruta=inexistente', '/index.php', 404, 'Página no encontrada'],
            'path desconocido' => ['/inexistente', '/index.php', 404, 'Página no encontrada'],
            'path con ruta conocida' => ['/inexistente?ruta=catalogo', '/index.php', 404, 'Página no encontrada'],
            'path info' => ['/index.php/inexistente', '/index.php', 404, 'Página no encontrada'],
            'archivo php' => ['/inexistente.php', '/index.php', 404, 'Página no encontrada'],
            'archivo html' => ['/inexistente.html', '/index.php', 404, 'Página no encontrada'],
            'path sin reflejar' => ['/<script>alert(1)</script>', '/index.php', 404, 'Página no encontrada'],
            'ruta sin reflejar' => ['/?ruta=%3Cscript%3Ealert(1)%3C/script%3E', '/index.php', 404, 'Página no encontrada'],
            'portada subdirectorio' => ['/tienda/', '/tienda/index.php', 200, 'CATALOGO'],
            'index subdirectorio' => ['/tienda/index.php?ruta=catalogo', '/tienda/index.php', 200, 'CATALOGO'],
            'path subdirectorio' => ['/tienda/inexistente', '/tienda/index.php', 404, 'Página no encontrada'],
            'otro index' => ['/tienda/otro/index.php', '/tienda/index.php', 404, 'Página no encontrada'],
        ];
    }
}
