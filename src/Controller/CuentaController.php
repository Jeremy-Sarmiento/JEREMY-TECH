<?php
declare(strict_types=1);

namespace Controller;

use Model\Cuenta;

/**
 * Controlador de cuentas de clientes (Jeremy Tech MVC).
 * Registro, inicio/cierre de sesión y recuperación de contraseña por
 * pregunta de seguridad.
 */
class CuentaController
{
    /**
     * Página de inicio de sesión / registro.
     */
    public function ver(): void
    {
        if (Cuenta::estaRegistrado()) {
            header('Location: ?ruta=carrito#historial');
            exit;
        }

        $tabVisual = ($_GET['tab'] ?? '') === 'registro' ? 'registro' : 'iniciar';
        $flash = self::tomarFlash();
        $rutaVista = 'login';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/login.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * POST: crear cuenta nueva (auto-login).
     */
    public function registrar(): void
    {
        $error = Cuenta::registrar($_POST);
        if ($error === null) {
            header('Location: ?ruta=carrito');
            exit;
        }

        self::guardarFlash($error, true);
        header('Location: ?ruta=login&tab=registro');
        exit;
    }

    /**
     * POST: iniciar sesión con email + contraseña.
     */
    public function iniciarSesion(): void
    {
        if (Cuenta::estaRegistrado()) {
            header('Location: ?ruta=carrito');
            exit;
        }

        $email    = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (Cuenta::iniciarSesion($email, $password)) {
            header('Location: ?ruta=carrito');
            exit;
        }

        self::guardarFlash('Correo o contraseña incorrectos', true);
        header('Location: ?ruta=login');
        exit;
    }

    /**
     * POST: cerrar sesión.
     */
    public function cerrar(): void
    {
        Cuenta::cerrarSesion();
        header('Location: ?ruta=catalogo');
        exit;
    }

    /**
     * Recuperación de contraseña por pregunta de seguridad (GET/POST en 2 pasos):
     *   paso 1: se envía el correo y, si existe, se muestra su pregunta en pantalla;
     *   paso 2: se responde la pregunta y se fija la nueva contraseña.
     */
    public function recuperarPregunta(): void
    {
        if (Cuenta::estaRegistrado()) {
            header('Location: ?ruta=carrito');
            exit;
        }

        $flash = self::tomarFlash();
        $email = '';

        // Paso 2: respuesta + nueva clave
        if (($_POST['respuesta_secreta'] ?? '') !== '') {
            $email = (string) ($_POST['email'] ?? '');
            $error = Cuenta::cambiarPorPregunta(
                $email,
                (string) $_POST['respuesta_secreta'],
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['confirmar'] ?? '')
            );

            if ($error === null) {
                self::guardarFlash('Contraseña actualizada. Ya puedes iniciar sesión.', false);
                header('Location: ?ruta=login');
                exit;
            }

            $flash = ['tipo' => 'error', 'mensaje' => $error];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Paso 1: se pide el correo y se revela la pregunta (si aplica).
            $email = (string) ($_POST['email'] ?? '');
            $pregunta = Cuenta::preguntaDe($email);

            if ($pregunta === null) {
                self::guardarFlash(
                    'El correo no existe o no tiene pregunta de seguridad configurada. ' .
                    'Crea una cuenta para configurarla.',
                    true
                );
                header('Location: ?ruta=login/recuperar-pregunta');
                exit;
            }

            $flash = ['tipo' => '', 'mensaje' => ''];
            require BASE_PATH . '/views/layout/header.php';
            require BASE_PATH . '/views/recuperar-pregunta.php';
            require BASE_PATH . '/views/layout/footer.php';
            exit;
        }

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/recuperar-pregunta.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Guarda un mensaje flash en la sesión.
     */
    private static function guardarFlash(string $mensaje, bool $esError): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_login'] = [
            'tipo'    => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{tipo: string, mensaje: string}
     */
    private static function tomarFlash(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $flash = $_SESSION['flash_login'] ?? null;
        unset($_SESSION['flash_login']);

        if (is_array($flash)) {
            return [
                'tipo'    => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }
        return ['tipo' => '', 'mensaje' => ''];
    }
}