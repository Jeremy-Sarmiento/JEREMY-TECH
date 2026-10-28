<?php
declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Envio;
use Model\LimiteIntentos;
use Model\Recuperacion;

/**
 * Controlador de cuentas de clientes (Jeremy Tech MVC).
 * Registro, inicio/cierre de sesión y recuperación de contraseña por
 * pregunta de seguridad o por token enviado al correo.
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
        $registroPrevio = self::tomarRegistroPrevio();
        // El registro pide la zona de envío porque el checkout de Jeremy Tech
        // calcula el costo con la zona de la dirección (no con el subtotal).
        $zonas = array_values(array_filter(
            Envio::zonas(),
            static fn (array $zona): bool => (string) $zona['zona'] !== Envio::ZONA_RECOJO
        ));
        $rutaVista = 'login';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/login.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Página "Mi perfil": datos de contacto y facturación del cliente.
     */
    public function perfil(): void
    {
        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $perfil = Cuenta::usuarioActual();
        if ($perfil === null) {
            header('Location: ?ruta=login/cerrar');
            exit;
        }

        // Si el último POST falló, el formulario conserva lo escrito; el
        // resumen lateral sigue mostrando lo que hay realmente en la BD.
        $formulario = $perfil;
        foreach (self::tomarPerfilPrevio() as $clave => $valor) {
            if (array_key_exists($clave, $formulario)) {
                $formulario[$clave] = $valor;
            }
        }

        $flash = self::tomarFlashPerfil();
        $rutaVista = 'perfil';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/perfil.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * POST: guarda los datos del perfil.
     */
    public function guardarPerfil(): void
    {
        \Security\Proteccion::exigirPost();

        $clienteId = Cuenta::idSesion();
        if ($clienteId === null) {
            header('Location: ?ruta=login');
            exit;
        }

        $error = Cuenta::actualizarPerfil($clienteId, $_POST);
        if ($error !== null) {
            self::guardarPerfilPrevio($_POST);
        } else {
            self::limpiarPerfilPrevio();
        }
        self::guardarFlashPerfil($error ?? 'Perfil actualizado correctamente.', $error !== null);
        header('Location: ?ruta=perfil');
        exit;
    }

    /**
     * POST: crear cuenta nueva (auto-login).
     */
    public function registrar(): void
    {
        \Security\Proteccion::exigirPost();
        $error = Cuenta::registrar($_POST);
        if ($error === null) {
            self::limpiarDatosCuenta();
            header('Location: ?ruta=carrito');
            exit;
        }

        self::guardarRegistroPrevio($_POST);
        self::guardarFlash($error, true);
        header('Location: ?ruta=login&tab=registro');
        exit;
    }

    /**
     * POST: iniciar sesión con email + contraseña.
     */
    public function iniciarSesion(): void
    {
        \Security\Proteccion::exigirPost();
        if (Cuenta::estaRegistrado()) {
            header('Location: ?ruta=carrito');
            exit;
        }

        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $clave    = $email !== '' ? $email : 'ip:' . LimiteIntentos::ipActual();

        $restante = LimiteIntentos::restante('cliente_login', $clave);
        if ($restante > 0) {
            self::guardarFlash(LimiteIntentos::mensajeBloqueo($restante), true);
            header('Location: ?ruta=login');
            exit;
        }

        if (Cuenta::iniciarSesion($email, $password)) {
            LimiteIntentos::limpiar('cliente_login', $clave);
            self::limpiarDatosCuenta();
            header('Location: ?ruta=carrito');
            exit;
        }

        LimiteIntentos::registrar('cliente_login', $clave, LimiteIntentos::ipActual());
        self::guardarFlash('Correo o contraseña incorrectos', true);
        header('Location: ?ruta=login');
        exit;
    }

    /**
     * POST: cerrar sesión.
     */
    public function cerrar(): void
    {
        \Security\Proteccion::exigirPost();
        Cuenta::cerrarSesion();
        self::limpiarDatosCuenta();
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
        $clave = '';
        $rutaVista = 'login';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $esPaso2 = ($_POST['respuesta_secreta'] ?? '') !== '';
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $clave = $email !== '' ? $email : 'ip:' . LimiteIntentos::ipActual();
            $ambito = $esPaso2 ? 'cliente_pregunta_respuesta' : 'cliente_pregunta_solicitud';

            $restante = LimiteIntentos::restante($ambito, $clave);
            if ($restante > 0) {
                self::guardarFlash(LimiteIntentos::mensajeBloqueo($restante), true);
                header('Location: ?ruta=login/recuperar-pregunta');
                exit;
            }

            if ($esPaso2) {
                // Paso 2: respuesta + nueva clave
                $error = Cuenta::cambiarPorPregunta(
                    $email,
                    (string) $_POST['respuesta_secreta'],
                    (string) ($_POST['password'] ?? ''),
                    (string) ($_POST['confirmar'] ?? '')
                );

                if ($error === null) {
                    LimiteIntentos::limpiar('cliente_pregunta_respuesta', $clave);
                    self::guardarFlash('Contraseña actualizada. Ya puedes iniciar sesión.', false);
                    header('Location: ?ruta=login');
                    exit;
                }

                LimiteIntentos::registrar('cliente_pregunta_respuesta', $clave, LimiteIntentos::ipActual());
                // Se vuelve a cargar la pregunta para que el formulario del
                // paso 2 siga visible tras una respuesta incorrecta.
                $pregunta = Cuenta::preguntaDe($email);
                $flash = ['tipo' => 'error', 'mensaje' => $error];
            } else {
                // Paso 1: se pide el correo y se revela la pregunta (si aplica).
                LimiteIntentos::registrar('cliente_pregunta_solicitud', $clave, LimiteIntentos::ipActual());
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
        }

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/recuperar-pregunta.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Recuperación por token enviado al correo (GET/POST).
     *
     * La respuesta es siempre la misma, exista o no la cuenta, para no
     * revelar qué correos están registrados.
     */
    public function recuperarToken(): void
    {
        if (Cuenta::estaRegistrado()) {
            header('Location: ?ruta=carrito');
            exit;
        }

        $flash = self::tomarFlash();
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $clave = $email !== '' ? $email : 'ip:' . LimiteIntentos::ipActual();

            $restante = LimiteIntentos::restante('cliente_recuperar', $clave);
            if ($restante > 0) {
                self::guardarFlash(LimiteIntentos::mensajeBloqueo($restante), true);
                header('Location: ?ruta=login/recuperar');
                exit;
            }

            // Cada solicitud cuenta, incluso si el correo no existe: así no se
            // puede usar este formulario para enumerar cuentas ni para spam.
            LimiteIntentos::registrar('cliente_recuperar', $clave, LimiteIntentos::ipActual());

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                Recuperacion::solicitarCliente($email);
            }

            self::guardarFlash(
                'Si el correo está registrado, enviamos un enlace de recuperación. Revisa tu bandeja.',
                false
            );
            header('Location: ?ruta=login/recuperar');
            exit;
        }

        $rutaVista = 'login';
        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/recuperar-token.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Formulario para elegir la nueva contraseña con el token del correo.
     */
    public function restablecerToken(): void
    {
        if (Cuenta::estaRegistrado()) {
            header('Location: ?ruta=carrito');
            exit;
        }

        $token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
        $flash = self::tomarFlash();
        $valido = Recuperacion::tokenValido(Recuperacion::CLIENTE, $token);
        $claveReset = $token !== '' ? $token : 'ip:' . LimiteIntentos::ipActual();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $restante = LimiteIntentos::restante('cliente_restablecer', $claveReset);
            if ($restante > 0) {
                self::guardarFlash(LimiteIntentos::mensajeBloqueo($restante), true);
                header('Location: ?ruta=login/recuperar');
                exit;
            }

            if (!$valido) {
                LimiteIntentos::registrar('cliente_restablecer', $claveReset, LimiteIntentos::ipActual());
                self::guardarFlash(
                    'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.',
                    true
                );
                header('Location: ?ruta=login/recuperar');
                exit;
            }

            $error = Recuperacion::restablecer(
                Recuperacion::CLIENTE,
                $token,
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['confirmar'] ?? '')
            );

            if ($error === null) {
                LimiteIntentos::limpiar('cliente_restablecer', $claveReset);
                self::guardarFlash('Contraseña actualizada. Ya puedes iniciar sesión.', false);
                header('Location: ?ruta=login');
                exit;
            }

            $flash = ['tipo' => 'error', 'mensaje' => $error];
        }

        $rutaVista = 'login';
        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/restablecer-token.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Guarda un mensaje flash en la sesión.
     */
    private static function guardarFlash(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
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
        \Security\Proteccion::iniciarSesion();
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

    /**
     * Conserva lo escrito en el formulario de perfil tras un error de
     * validación (solo los campos del formulario, nunca el token CSRF).
     *
     * @param array<string, mixed> $datos
     */
    private static function guardarPerfilPrevio(array $datos): void
    {
        \Security\Proteccion::iniciarSesion();

        $permitidas = [
            'nombre', 'apellidos', 'email', 'telefono', 'direccion',
            'dni', 'ruc', 'empresa', 'plataforma_reventa', 'tipo',
        ];
        $previo = [];
        foreach ($permitidas as $clave) {
            if (isset($datos[$clave]) && is_scalar($datos[$clave])) {
                $previo[$clave] = (string) $datos[$clave];
            }
        }

        $_SESSION['perfil_previo'] = $previo;
    }

    /**
     * @return array<string, string>
     */
    private static function tomarPerfilPrevio(): array
    {
        \Security\Proteccion::iniciarSesion();
        $previo = $_SESSION['perfil_previo'] ?? [];
        unset($_SESSION['perfil_previo']);

        return is_array($previo) ? $previo : [];
    }

    /** Descarta cualquier borrador anterior tras un guardado correcto. */
    private static function limpiarPerfilPrevio(): void
    {
        \Security\Proteccion::iniciarSesion();
        unset($_SESSION['perfil_previo']);
    }

    /**
     * Conserva los datos del formulario de registro tras un error, sin
     * guardar contraseñas ni la respuesta de seguridad.
     *
     * @param array<string, mixed> $datos
     */
    private static function guardarRegistroPrevio(array $datos): void
    {
        \Security\Proteccion::iniciarSesion();

        $permitidas = [
            'nombre', 'apellidos', 'email', 'telefono', 'dni', 'direccion',
            'latitud', 'longitud', 'tipo_cliente', 'ruc', 'plataforma_reventa',
            'tarifa_envio_id', 'pregunta_secreta',
        ];
        $previo = [];
        foreach ($permitidas as $clave) {
            if (isset($datos[$clave]) && is_scalar($datos[$clave])) {
                $previo[$clave] = (string) $datos[$clave];
            }
        }

        $_SESSION['registro_previo'] = $previo;
    }

    /**
     * @return array<string, string>
     */
    private static function tomarRegistroPrevio(): array
    {
        \Security\Proteccion::iniciarSesion();
        $previo = $_SESSION['registro_previo'] ?? [];
        unset($_SESSION['registro_previo']);

        return is_array($previo) ? $previo : [];
    }

    /**
     * Limpia borradores y avisos de la cuenta al iniciar/cerrar sesión, para
     * que un usuario no vea datos del anterior en el mismo navegador.
     */
    private static function limpiarDatosCuenta(): void
    {
        \Security\Proteccion::iniciarSesion();
        unset(
            $_SESSION['registro_previo'],
            $_SESSION['perfil_previo'],
            $_SESSION['flash_perfil'],
            $_SESSION['flash_favoritos'],
            $_SESSION['flash_resena']
        );
    }

    /**
     * Guarda el flash de la página de perfil (independiente del de login).
     */
    private static function guardarFlashPerfil(string $mensaje, bool $esError): void
    {
        \Security\Proteccion::iniciarSesion();
        $_SESSION['flash_perfil'] = [
            'tipo' => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{tipo: string, mensaje: string}
     */
    private static function tomarFlashPerfil(): array
    {
        \Security\Proteccion::iniciarSesion();
        $flash = $_SESSION['flash_perfil'] ?? null;
        unset($_SESSION['flash_perfil']);

        if (is_array($flash)) {
            return [
                'tipo' => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
                'mensaje' => (string) ($flash['mensaje'] ?? ''),
            ];
        }

        return ['tipo' => '', 'mensaje' => ''];
    }
}