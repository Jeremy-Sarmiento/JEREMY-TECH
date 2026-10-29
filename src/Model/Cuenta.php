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
        \Security\Proteccion::iniciarSesion();
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
     * ¿La cuenta autenticada puede publicar en el marketplace?
     *
     * Solo publican las cuentas con `rol = 'vendedor'`. Toda cuenta nueva
     * nace como cliente; el rol se concede desde `admin/solicitudes` tras
     * una solicitud del propio usuario. El `tipo` (B2C/B2B/C2C) no habilita
     * la venta, solo define qué panel ve el vendedor.
     *
     * Se consulta en cada petición para que un cambio de rol surta efecto
     * sin reabrir la sesión.
     *
     * @param array<string, mixed>|null $usuario Fila ya cargada (evita una
     *        consulta extra en el header); null = leer de la sesión.
     */
    public static function puedePublicar(?array $usuario = null): bool
    {
        $usuario ??= self::usuarioActual();
        if ($usuario === null) {
            return false;
        }

        return (string) ($usuario['rol'] ?? '') === 'vendedor';
    }

    /** Alias histórico usado por la navegación y el panel de publicaciones. */
    public static function esVendedor(): bool
    {
        return self::puedePublicar();
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
            'SELECT id, name AS nombre, apellidos, email, usuario, telefono, direccion, dni, ruc, empresa,
                    plataforma_reventa, tipo, rol, verificado
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
     *
     * El registro pide los mismos datos que la Sesión 8: nombre/razón social,
     * apellidos, DNI, teléfono, dirección de envío, correo (usuario),
     * contraseña + confirmación, tipo de cliente (B2C/B2B/C2C), RUC para
     * empresas, plataforma de reventa para C2C y coordenadas opcionales.
     * La primera dirección se guarda en `direcciones` para que el checkout
     * ya tenga un destino preseleccionado.
     *
     * @param array<string, mixed> $datos
     * @return string|null null si todo salió bien, o un mensaje de error.
     */
    public static function registrar(array $datos): ?string
    {
        $nombre        = trim((string) ($datos['nombre'] ?? ''));
        $apellidos     = trim((string) ($datos['apellidos'] ?? ''));
        $email         = strtolower(trim((string) ($datos['email'] ?? '')));
        $usuario       = trim((string) ($datos['usuario'] ?? ''));
        $telefono      = trim((string) ($datos['telefono'] ?? ''));
        $dni           = trim((string) ($datos['dni'] ?? ''));
        $direccion     = trim((string) ($datos['direccion'] ?? ''));
        $latitud       = trim((string) ($datos['latitud'] ?? ''));
        $longitud      = trim((string) ($datos['longitud'] ?? ''));
        $tipo          = strtoupper(trim((string) ($datos['tipo_cliente'] ?? $datos['tipo'] ?? 'B2C')));
        $ruc           = trim((string) ($datos['ruc'] ?? ''));
        $plataforma    = trim((string) ($datos['plataforma_reventa'] ?? ''));
        $tarifaId      = (int) ($datos['tarifa_envio_id'] ?? 0);
        $password      = (string) ($datos['password'] ?? '');
        $confirmar     = (string) ($datos['confirmar'] ?? '');
        $pregunta      = trim((string) ($datos['pregunta_secreta'] ?? ''));
        $respuesta     = trim((string) ($datos['respuesta_secreta'] ?? ''));

        if ($nombre === '') {
            return 'Ingresa tu nombre o razón social';
        }
        if (mb_strlen($nombre) > 255) {
            return 'El nombre o razón social es demasiado largo';
        }
        if ($apellidos !== '' && mb_strlen($apellidos) > 180) {
            return 'Los apellidos son demasiado largos';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido';
        }
        if (self::porEmail($email)) {
            return 'Ya existe una cuenta con ese correo';
        }
        if ($usuario === '') {
            return 'Elige un nombre de usuario';
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $usuario)) {
            return 'El usuario debe tener entre 3 y 30 caracteres (letras, números, punto, guion o guion bajo)';
        }
        if (in_array(strtolower($usuario), ['admin', 'administrador', 'root', 'soporte', 'jeremytech'], true)) {
            return 'Ese nombre de usuario no está disponible';
        }
        // El índice UNIQUE incluye cuentas dadas de baja: se comprueba sin
        // filtrar `deleted_at` para no chocar con la restricción al insertar.
        $stmtUsuario = conexion()->prepare('SELECT 1 FROM users WHERE usuario = ? LIMIT 1');
        $stmtUsuario->execute([$usuario]);
        if ($stmtUsuario->fetchColumn()) {
            return 'Ese nombre de usuario ya está en uso';
        }
        if ($telefono === '') {
            return 'Ingresa tu teléfono';
        }
        if (mb_strlen($telefono) > 20) {
            return 'El teléfono no puede superar los 20 caracteres';
        }
        if ($dni === '') {
            return 'Ingresa tu DNI';
        }
        if (!preg_match('/^\d{8}$/', $dni)) {
            return 'El DNI debe tener exactamente 8 dígitos';
        }
        if ($direccion === '') {
            return 'Ingresa tu dirección de envío';
        }
        if (mb_strlen($direccion) > 255) {
            return 'La dirección no puede superar los 255 caracteres';
        }
        if (!in_array($tipo, ['B2C', 'B2B', 'C2C'], true)) {
            return 'Elige un tipo de cliente válido';
        }
        if ($tipo === 'B2B' && $ruc !== '' && !preg_match('/^\d{11}$/', $ruc)) {
            return 'El RUC debe tener exactamente 11 dígitos';
        }
        if (mb_strlen($plataforma) > 120) {
            return 'La plataforma de reventa no puede superar los 120 caracteres';
        }
        if ($latitud !== '' && (!is_numeric($latitud) || (float) $latitud < -90 || (float) $latitud > 90)) {
            return 'La latitud debe ser un número entre -90 y 90';
        }
        if ($longitud !== '' && (!is_numeric($longitud) || (float) $longitud < -180 || (float) $longitud > 180)) {
            return 'La longitud debe ser un número entre -180 y 180';
        }
        if (($latitud === '') !== ($longitud === '')) {
            return 'Marca la ubicación completa (latitud y longitud) o deja ambas vacías';
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

        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users
                    (name, apellidos, email, usuario, telefono, direccion, dni, tipo, empresa,
                     ruc, plataforma_reventa, password, pregunta_secreta,
                     respuesta_secreta_hash, rol)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $nombre,
                $apellidos,
                $email,
                $usuario,
                $telefono,
                $direccion,
                $dni,
                $tipo,
                $tipo === 'B2B' ? $nombre : '',
                $tipo === 'B2B' ? $ruc : '',
                $tipo === 'C2C' ? $plataforma : '',
                password_hash($password, PASSWORD_DEFAULT),
                $pregunta,
                password_hash(strtolower($respuesta), PASSWORD_DEFAULT),
                'cliente',
            ]);

            $clienteId = (int) $pdo->lastInsertId();
            $errorDireccion = Direccion::registrarInicial($clienteId, [
                'direccion'       => $direccion,
                'tarifa_envio_id' => $tarifaId,
                'latitud'         => $latitud !== '' ? (float) $latitud : null,
                'longitud'        => $longitud !== '' ? (float) $longitud : null,
            ]);
            if ($errorDireccion !== null) {
                $pdo->rollBack();
                return $errorDireccion;
            }

            $pdo->commit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ((string) $e->getCode() === '23000') {
                return 'Ya existe una cuenta con ese correo o usuario';
            }
            return 'No se pudo crear la cuenta. Inténtalo de nuevo.';
        }

        self::arrancarSesion();
        session_regenerate_id(true);
        \Security\Proteccion::rotarToken();
        $_SESSION[self::SESION_CLAVE] = $clienteId;

        return null;
    }

    /**
     * Actualiza los datos de contacto/facturación del cliente autenticado.
     *
     * @param array<string, mixed> $datos
     * @return string|null null si todo salió bien, o un mensaje de error.
     */
    public static function actualizarPerfil(int $clienteId, array $datos): ?string
    {
        if ($clienteId <= 0) {
            return 'La sesión no es válida. Vuelve a iniciar sesión.';
        }

        $nombre     = trim((string) ($datos['nombre'] ?? ''));
        $apellidos  = trim((string) ($datos['apellidos'] ?? ''));
        $email      = strtolower(trim((string) ($datos['email'] ?? '')));
        $telefono   = trim((string) ($datos['telefono'] ?? ''));
        $direccion  = trim((string) ($datos['direccion'] ?? ''));
        $dni        = trim((string) ($datos['dni'] ?? ''));
        $ruc        = trim((string) ($datos['ruc'] ?? ''));
        $empresa    = trim((string) ($datos['empresa'] ?? ''));
        $plataforma = trim((string) ($datos['plataforma_reventa'] ?? ''));

        // El tipo de cuenta solo lo cambia el administrador: se lee de la BD
        // y se ignora cualquier `tipo` que llegue en el POST.
        $stmtTipo = conexion()->prepare(
            'SELECT tipo FROM users WHERE id = ? AND activo = 1 AND deleted_at IS NULL LIMIT 1'
        );
        $stmtTipo->execute([$clienteId]);
        $tipoActual = $stmtTipo->fetchColumn();
        if ($tipoActual === false) {
            return 'La sesión no es válida. Vuelve a iniciar sesión.';
        }
        $tipoActual = strtoupper((string) $tipoActual);

        if ($nombre === '') {
            return 'Ingresa tu nombre o razón social.';
        }
        if (mb_strlen($nombre) > 255 || mb_strlen($apellidos) > 180) {
            return 'El nombre o los apellidos son demasiado largos.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return 'Ingresa un correo electrónico válido.';
        }
        if (mb_strlen($telefono) > 20) {
            return 'El teléfono no puede superar los 20 caracteres.';
        }
        if (mb_strlen($direccion) > 255) {
            return 'La dirección no puede superar los 255 caracteres.';
        }
        if ($dni !== '' && !preg_match('/^\d{8}$/', $dni)) {
            return 'El DNI debe tener exactamente 8 dígitos.';
        }
        if ($ruc !== '' && !preg_match('/^\d{11}$/', $ruc)) {
            return 'El RUC debe tener exactamente 11 dígitos.';
        }
        if (mb_strlen($empresa) > 120) {
            return 'La razón social no puede superar los 120 caracteres.';
        }
        if (mb_strlen($plataforma) > 120) {
            return 'La plataforma de reventa no puede superar los 120 caracteres.';
        }

        // El correo es el identificador de acceso: debe seguir siendo único,
        // incluso frente a cuentas dadas de baja (el índice UNIQUE no las
        // excluye).
        $stmt = conexion()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $clienteId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return 'Ya existe otra cuenta con ese correo electrónico.';
        }

        $stmt = conexion()->prepare(
            'UPDATE users
                SET name = ?, apellidos = ?, email = ?, telefono = ?, direccion = ?,
                    dni = ?, ruc = ?, empresa = ?, plataforma_reventa = ?
              WHERE id = ? AND activo = 1 AND deleted_at IS NULL'
        );
        $stmt->execute([
            $nombre, $apellidos, $email, $telefono, $direccion,
            $dni, $ruc, $empresa, $tipoActual === 'C2C' ? $plataforma : '', $clienteId,
        ]);

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
        \Security\Proteccion::rotarToken();
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
        \Security\Proteccion::rotarToken();
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

        // Cambiar la contraseña invalida cualquier enlace de recuperación
        // pendiente: si el token estaba en manos de un tercero, deja de servir.
        conexion()->prepare('UPDATE tokens_recuperacion SET usado = 1 WHERE usuario_id = ? AND usado = 0')
            ->execute([(int) $cliente['id']]);

        return null;
    }
}
