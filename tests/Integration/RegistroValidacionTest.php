<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Cuenta;
use PHPUnit\Framework\TestCase;

class RegistroValidacionTest extends TestCase
{
    protected function setUp(): void
    {
        resetTestDb();
        \Security\Proteccion::iniciarSesion();
        $_SESSION = [];
    }

    public function testEmpresaDemasiadoLargaDevuelveValidacionSinCrearLaCuenta(): void
    {
        $datos = $this->datos();
        $datos['tipo_cliente'] = 'B2B';
        $datos['ruc'] = '20123456789';
        $datos['nombre'] = str_repeat('a', 121);
        $error = Cuenta::registrar($datos);
        $this->assertNotNull($error);
        $this->assertStringContainsString('120', $error);
        $this->assertNull(Cuenta::porEmail($datos['email']));
        $datos['nombre'] = str_repeat('a', 120);
        $this->assertNull(Cuenta::registrar($datos));
        $this->assertSame($datos['nombre'], Cuenta::porEmail($datos['email'])['empresa']);
    }

    public function testRespuestaAcentuadaSeRecuperaSinDistinguirMayusculas(): void
    {
        $datos = $this->datos();
        $datos['respuesta_secreta'] = 'ÁNCASH';
        $this->assertNull(Cuenta::registrar($datos));
        $this->assertNull(Cuenta::cambiarPorPregunta($datos['email'], 'áncash', 'Nueva-Clave-Segura-2026!', 'Nueva-Clave-Segura-2026!'));
    }

    public function testRespuestasGuardadasConLaNormalizacionAntiguaSiguenFuncionando(): void
    {
        testPdo()->prepare('UPDATE usuarios SET respuesta_secreta_hash = ? WHERE id = 3')
            ->execute([password_hash(strtolower('ÁNCASH'), PASSWORD_DEFAULT)]);
        $this->assertNull(Cuenta::cambiarPorPregunta('cliente@example.com', 'ÁNCASH', 'Nueva-Clave-Segura-2026!', 'Nueva-Clave-Segura-2026!'));
    }

    public function testVisitarPerfilDesactivadoLimpiaLaSesionSinExigirUnPostDeLogout(): void
    {
        $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
            . '\Model\Cuenta::iniciarSesion("cliente@example.com", "cliente");'
            . 'testPdo()->exec("UPDATE usuarios SET activo = 0 WHERE id = 3");'
            . 'register_shutdown_function(function () { echo json_encode(["cliente_id" => \Model\Cuenta::idSesion()]); });'
            . '(new \Controller\CuentaController())->perfil();';
        $proceso = proc_open([PHP_BINARY, '-r', $codigo], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($proceso);
        $salida = stream_get_contents($pipes[1]);
        $errores = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($proceso), $errores);
        $this->assertSame(['cliente_id' => null], json_decode($salida, true, 512, JSON_THROW_ON_ERROR));
    }

    private function datos(): array
    {
        return [
            'nombre' => 'Nuevo', 'apellidos' => 'Cliente', 'email' => 'validacion@example.test',
            'usuario' => 'validacion', 'telefono' => '999123456', 'dni' => '12345678',
            'direccion' => 'Calle Test 123', 'tipo_cliente' => 'B2C', 'tarifa_envio_id' => 1,
            'password' => 'Ejemplo-Pruebas-2026!', 'confirmar' => 'Ejemplo-Pruebas-2026!',
            'pregunta_secreta' => Cuenta::PREGUNTAS_PERMITIDAS[0], 'respuesta_secreta' => 'firulais',
        ];
    }
}
