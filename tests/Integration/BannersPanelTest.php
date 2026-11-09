<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Banner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BannersPanelTest extends TestCase
{
    private int $id;

    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
        $_GET = [];
        $stmt = testPdo()->prepare("INSERT INTO banners (titulo, mime, datos) VALUES ('Oferta de prueba', 'image/png', ?) RETURNING id");
        $stmt->bindValue(1, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true), PDO::PARAM_LOB);
        $stmt->execute();
        $this->id = (int) $stmt->fetchColumn();
    }

    public function testAdminVeGestionYActualizaBannerSinReemplazarLaImagen(): void
    {
        $pagina = $this->solicitar('admin', 'banners');
        $this->assertStringContainsString('admin/banners/guardar', $pagina['html']);
        $this->assertStringContainsString('multipart/form-data', $pagina['html']);
        $this->assertStringContainsString('Oferta de prueba', $pagina['html']);
        $antes = Banner::obtener($this->id);
        $respuesta = $this->solicitar('admin', 'banners/guardar', [
            'id' => $this->id, 'titulo' => 'Nuevo título', 'enlace' => '?ruta=catalogo', 'orden' => 2, 'activo' => 1,
        ]);
        $this->assertSame('ok', $respuesta['flash']['tipo']);
        $despues = Banner::obtener($this->id);
        $this->assertSame('Nuevo título', $despues['titulo']);
        $this->assertSame($antes['datos'], $despues['datos']);
        $this->assertSame(1, (int) testPdo()->query("SELECT COUNT(*) FROM auditoria_admin WHERE accion = 'banners/guardar'")->fetchColumn());
    }

    #[DataProvider('rutasProtegidas')]
    public function testVendedorYClienteNoPuedenGestionarBanners(string $usuario, string $ruta): void
    {
        $antes = Banner::obtener($this->id);
        $respuesta = $this->solicitar($usuario, $ruta, [
            'id' => $this->id, 'titulo' => 'No autorizado', 'activo' => 0,
        ]);
        $this->assertSame($antes, Banner::obtener($this->id));
        $this->assertStringNotContainsString('admin/banners/guardar', $respuesta['html']);
        $this->assertSame(0, (int) testPdo()->query('SELECT COUNT(*) FROM auditoria_admin')->fetchColumn());
    }

    public static function rutasProtegidas(): array
    {
        $casos = [];
        foreach (['vendedor', 'cliente', 'visitante'] as $usuario) {
            foreach (['banners', 'banners/guardar', 'banners/estado', 'banners/eliminar'] as $ruta) {
                $casos[$usuario . ':' . $ruta] = [$usuario, $ruta];
            }
        }
        return $casos;
    }

    public function testAdminOcultaActivaYEliminaBanner(): void
    {
        $this->assertSame('ok', $this->solicitar('admin', 'banners/estado', ['id' => $this->id])['flash']['tipo']);
        $this->assertSame([], Banner::activos());
        $this->assertSame('ok', $this->solicitar('admin', 'banners/estado', ['id' => $this->id])['flash']['tipo']);
        $this->assertCount(1, Banner::activos());
        $this->assertSame('ok', $this->solicitar('admin', 'banners/eliminar', ['id' => $this->id])['flash']['tipo']);
        $this->assertNull(Banner::obtener($this->id));
    }

    #[DataProvider('datosInvalidos')]
    public function testRechazaDatosInvalidosSinModificarBanner(array $cambios): void
    {
        $antes = Banner::obtener($this->id);
        $resultado = Banner::guardar(array_replace(['id' => $this->id, 'titulo' => 'Válido'], $cambios), null);
        $this->assertFalse($resultado['ok']);
        $this->assertSame($antes, Banner::obtener($this->id));
    }

    public static function datosInvalidos(): array
    {
        return [
            'javascript' => [['enlace' => 'javascript:alert(1)']],
            'protocolo relativo' => [['enlace' => '//malicioso.example']],
            'titulo array' => [['titulo' => ['invalido']]],
            'orden invalido' => [['orden' => 'abc']],
            'id invalido' => [['id' => -1]],
        ];
    }

    public function testErrorDeSubidaNoGuardaCambiosYUnIdInexistenteNoDaExito(): void
    {
        $antes = Banner::obtener($this->id);
        $resultado = Banner::guardar(['id' => $this->id, 'titulo' => 'No guardar'], ['error' => UPLOAD_ERR_PARTIAL]);
        $this->assertFalse($resultado['ok']);
        $this->assertSame($antes, Banner::obtener($this->id));
        $this->assertFalse(Banner::guardar(['id' => 99999, 'titulo' => 'No existe'], null)['ok']);
    }

    public function testMutacionesExigenPostYCsrf(): void
    {
        $antes = Banner::obtener($this->id);
        $get = $this->solicitar('admin', 'banners/eliminar', [], true);
        $this->assertSame(405, $get['status']);
        $post = $this->solicitar('admin', 'banners/eliminar', ['id' => $this->id], false);
        $this->assertSame(403, $post['status']);
        $this->assertSame($antes, Banner::obtener($this->id));
    }

    public function testCatalogoMuestraEnlaceYUnIndicadorPorBannerActivo(): void
    {
        testPdo()->exec("UPDATE banners SET enlace = '?ruta=catalogo&categoria=Celulares'");
        ob_start();
        try {
            (new \Controller\ProductoController())->catalogo();
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertStringContainsString('href="?ruta=catalogo&amp;categoria=Celulares"', $html);
        $this->assertSame(1, substr_count($html, 'class="indicator'));
    }

    public function testImagenDeBannerOcultoSoloEstaDisponibleComoVistaPreviaAdmin(): void
    {
        Banner::cambiarEstado($this->id, 0);
        $_GET = ['id' => $this->id];
        ob_start();
        try {
            (new \Controller\ProductoController())->bannerImagen();
            $this->assertSame(404, http_response_code());
            $this->assertSame('Banner no encontrado', ob_get_contents());
        } finally {
            ob_end_clean();
            http_response_code(200);
        }
        \Model\Administrador::iniciarSesion('admin', 'admin');
        ob_start();
        try {
            (new \Controller\ProductoController())->bannerImagen();
            $this->assertSame(Banner::obtener($this->id)['datos'], ob_get_contents());
        } finally {
            ob_end_clean();
            $_SESSION = [];
        }
    }

    private function solicitar(string $usuario, string $ruta, array $post = [], bool $csrf = true): array
    {
        $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
            . '\Security\Proteccion::iniciarSesion(); $_SESSION = [];'
            . ($usuario === 'cliente' ? '\Model\Cuenta::iniciarSesion("cliente@example.com", "cliente");'
                : ($usuario === 'visitante' ? '' : '\Model\Administrador::iniciarSesion(' . var_export($usuario, true) . ', ' . var_export($usuario, true) . ');'))
            . '$_SERVER["REQUEST_METHOD"] = ' . var_export($post === [] ? 'GET' : 'POST', true) . ';'
            . '$_SERVER["SCRIPT_NAME"] = "/index.php"; $_SERVER["REQUEST_URI"] = "/index.php";'
            . '$_GET = ["ruta" => ' . var_export('admin/' . $ruta, true) . ']; $_POST = ' . var_export($post, true) . ';'
            . ($csrf && $post !== [] ? '$_POST["_csrf"] = \Security\Proteccion::token();' : '')
            . 'ob_start(); register_shutdown_function(function () { echo json_encode(['
            . '"html" => ob_get_clean(), "status" => http_response_code() ?: 200, "flash" => $_SESSION["admin_flash"] ?? null]); });'
            . '\Security\Proteccion::exigirCsrf();'
            . '(new \Controller\AdminController())->atender(' . var_export($ruta, true) . ');';
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proceso);
        $salida = stream_get_contents($pipes[1]);
        $errores = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proceso), $errores);
        return json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
    }
}
