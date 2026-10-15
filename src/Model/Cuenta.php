<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Cuenta (Jeremy Tech MVC).
 * Autenticación de la tienda sobre la tabla `users` (Etapa 4): una sola fila
 * por persona con `rol`, `activo` y soft-delete (`deleted_at`). La tienda
 * identifica al usuario por `email`; el panel lo hace por `usuario`.
 * Recuperación de contraseña por pregunta de seguridad (campos
 * `pregunta_secreta` + `respuesta_secreta_hash`, sin token ni correo).
 */
class Cuenta
{
    /** Clave de sesión que guarda el id del cliente autenticado. */
    private const SESION_CLAVE = 'cliente_id';

    /** Preguntas de seguridad permitidas (se guarda el texto exacto). */
    public const PREGUNTAS_PERMITIDAS = [
        '¿Cuál es el nombre de tu primera mascota?',
        '¿En qué ciudad naciste?',
        '¿Cómo se llamaba tu colegio de primaria?',
        '¿Cuál es tu plato favorito?',
        '¿Cuál es el apellido de soltera de tu madre?',
    ];

    /**
     * Dispara la sesión PHP si aún no esta activa.
     */
    private static function arrancarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Id del usuario autenticado en la tienda (o null si es invitado).
     */
    public static function idSesion(): ?int
    {
        self::arrancarSesion();
        $id = $_SESSION[self::SESION_CLAVE] ?? null;
        return $id !== null ? (int) $id : null;
    }

    public static function estaRegistrado(): bool
    {
        return self::idSesion() !== null;
    }

    /**
     * Datos del cliente autenticado (o null si es invitado).
     * @return array<string, mixed>|null
     */
    public static function usuarioActual(): ?array
    {
        $id = self::idSesion();
        if ($id === null) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT id, name AS nombre, apellidos, email, telefono, direccion, dni, ruc, empresa, tipo
             FROM users WHERE id = ? AND activo = 1 AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Busca un usuario por su correo (en minúsculas), ignorando los dados de
     * baja (`deleted_at`).
     * @return array<string, mixed>|null
     */
    public static function porEmail(string $email): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([strtolower(trim($email))]);
        $cliente = $stmt->fetch();

        return $cliente ?: null;
    }

    /**
     * Crea una cuenta de cliente y deja la sesión iniciada.
     * Validate email único y formato, nombre/apellidos y contraseña.
     *
     * @param array<string, mixed> $datos
     * @return string|null null si todo salió bien, o un mensaje de error.
     */
    public static function registrar(array $datos): ?string
    {
        $nombre     = trim((string) ($datos['nombre'] ?? ''));
        $apellidos  = trim((string) ($datos['apellidos'] ?? ''));
        $email      = strtolower(trim((string) ($datos['email'] ?? '')));
        $telefono   = trim((string) ($datos['telefono'] ?? ''));
        $password   = (string) ($datos['password'] ?? '');
        $confirmar  = (string) ($datos['confirmar'] ?? '');
        $pregunta   = trim((string) ($datos['pregunta_secreta'] ?? ''));
        $respuesta  = trim((string) ($datos['respuesta_secreta'] ?? ''));

        if ($nombre === '' || $apellidos === '') {
            return 'Ingresa tu nombre y apellidos';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido';
        }
        if (self::porEmail($email)) {
            return 'Ya existe una cuenta con ese correo';
        }
        if (strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($password !== $confirmar) {
            return 'Las contraseñas no coinciden';
        }
        if (!in_array($pregunta, self::PREGUNTAS_PERMITIDAS, true)) {
            return 'Elige una pregunta de seguridad válida';
        }
        if (strlen($respuesta) < 3) {
            return 'La respuesta de seguridad debe tener al menos 3 caracteres';
        }

        $stmt = conexion()->prepare(
            'INSERT INTO users (name, apellidos, email, telefono, password,
                                pregunta_secreta, respuesta_secreta_hash, rol, tipo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $nombre,
            $apellidos,
            $email,
            $telefono,
            password_hash($password, PASSWORD_DEFAULT),
            $pregunta,
            password_hash(strtolower($respuesta), PASSWORD_DEFAULT),
            'cliente',
            'B2C',
        ]);

        self::arrancarSesion();
        session_regenerate_id(true);
        $_SESSION[self::SESION_CLAVE] = (int) conexion()->lastInsertId();

        return null;
    }

    /**
     * Autentica por email + contraseña y deja la sesión iniciada.
     * Cualquier rol (cliente, vendedor o admin) puede comprar en la tienda.
     */
    public static function iniciarSesion(string $email, string $password): bool
    {
        $cuenta = self::porEmail($email);
        if (!$cuenta || (int) $cuenta['activo'] !== 1) {
            return false;
        }

        $hash = (string) ($cuenta['password'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        self::arrancarSesion();
        session_regenerate_id(true);
        $_SESSION[self::SESION_CLAVE] = (int) $cuenta['id'];

        return true;
    }

    /**
     * Cierra la sesión de la cuenta (sin tocar el carrito ni el cupón).
     */
    public static function cerrarSesion(): void
    {
        self::arrancarSesion();
        unset($_SESSION[self::SESION_CLAVE]);
        session_regenerate_id(true);
    }

    /**
     * Devuelve la pregunta de seguridad de una cuenta (o null si el correo
     * no existe o no tiene pregunta configurada).
     */
    public static function preguntaDe(string $email): ?string
    {
        $stmt = conexion()->prepare(
            'SELECT pregunta_secreta FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([strtolower(trim($email))]);
        $pregunta = $stmt->fetchColumn();

        return ($pregunta && $pregunta !== '') ? (string) $pregunta : null;
    }

    /**
     * Cambia la contraseña respondiendo correctamente la pregunta de seguridad.
     *
     * @return string|null null si todo salió bien, o un mensaje de error.
     */
    public static function cambiarPorPregunta(
        string $email,
        string $respuesta,
        string $nueva,
        string $confirma
    ): ?string {
        $email = strtolower(trim($email));

        if (strlen($nueva) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($nueva !== $confirma) {
            return 'Las contraseñas no coinciden';
        }
        if (strlen(trim($respuesta)) < 3) {
            return 'La respuesta de seguridad debe tener al menos 3 caracteres';
        }

        $stmt = conexion()->prepare(
            'SELECT id, respuesta_secreta_hash FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$email]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            return 'El correo no existe o no tiene pregunta de seguridad configurada';
        }

        $hash = (string) ($cliente['respuesta_secreta_hash'] ?? '');
        if ($hash === '' || !password_verify(strtolower(trim($respuesta)), $hash)) {
            return 'La respuesta de seguridad no es correcta';
        }

        conexion()->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($nueva, PASSWORD_DEFAULT), (int) $cliente['id']]);

        return null;
    }
}