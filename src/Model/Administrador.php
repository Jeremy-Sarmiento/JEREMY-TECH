<?php

declare(strict_types=1);

namespace Model;

/**
 * Modelo Administrador (Jeremy Tech).
 *
 * Autenticación del panel sobre la tabla `users` (Etapa 4): misma fila que
 * usa la tienda, pero identificada por el campo `usuario` y restringida a
 * `rol IN ('admin', 'vendedor')`.
 *
 * Etapa 6: el panel es UNO solo y se filtra por rol. El administrador ve
 * todas las secciones; el vendedor ve las suyas (productos, pedidos y
 * ventas), acotadas a lo que él publicó. El catálogo de secciones vive en
 * `seccionesPermitidas()`, que es la única fuente de verdad: la usa el
 * router de `AdminController` Y el sidebar de `views/admin/panel.php`.
 * Usa una sesión INDEPENDIENTE de la de la tienda (clave `admin_id` en
 * $_SESSION) para poder tener el panel abierto sin interrumpir una cuenta
 * de cliente autenticada.
 */
class Administrador
{
    /** Clave de sesión que guarda el id del administrador autenticado. */
    private const SESION_CLAVE = 'admin_id';

    /** Duración de validez de un intento de sesión activado. */
    private const OPCION_ACTIVO = 1;

    /**
     * Arranca la sesión PHP si aún no está activa (coincide con la práctica
     * usada en el resto de la app).
     */
    private static function arrancarSesion(): void
    {
        \Security\Proteccion::iniciarSesion();
    }

    /**
     * Id del administrador autenticado (o null si es visitante).
     */
    public static function idSesion(): ?int
    {
        self::arrancarSesion();
        $id = $_SESSION[self::SESION_CLAVE] ?? null;
        return $id !== null ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>|null  Fila de `users` del panel o null.
     */
    public static function porId(int $id): ?array
    {
        $stmt = conexion()->prepare(
            "SELECT * FROM users
              WHERE id = ? AND rol IN ('admin', 'vendedor') AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * @return array<string, mixed>|null  Fila por nombre de usuario o null.
     */
    public static function porUsuario(string $usuario): ?array
    {
        $stmt = conexion()->prepare(
            "SELECT * FROM users
              WHERE usuario = ? AND rol IN ('admin', 'vendedor') AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([trim($usuario)]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function estaAutenticado(): bool
    {
        return self::usuarioActual() !== null;
    }

    /**
     * Rol del usuario del panel activo ('admin' | 'vendedor' | null).
     */
    public static function rol(): ?string
    {
        $usuario = self::usuarioActual();
        if ($usuario === null) {
            return null;
        }
        $rol = (string) ($usuario['rol'] ?? '');
        return in_array($rol, ['admin', 'vendedor'], true) ? $rol : null;
    }

    /** true si el panel lo está usando un administrador. */
    public static function esAdmin(): bool
    {
        return self::rol() === 'admin';
    }

    /** true si el panel lo está usando un vendedor (ámbito acotado). */
    public static function esVendedor(): bool
    {
        return self::rol() === 'vendedor';
    }

    /**
     * Secciones del panel a las que el rol activo tiene acceso. Única fuente
     * de verdad: la consultan el router y el sidebar.
     *
     * @return array<int, string>
     */
    public static function seccionesPermitidas(): array
    {
        if (self::esVendedor()) {
            return ['dashboard', 'productos', 'variantes', 'pedidos', 'ventas', 'analitica'];
        }
        if (self::esAdmin()) {
            return [
                'dashboard', 'productos', 'variantes', 'categorias', 'marcas', 'garantias',
                'cupones', 'envios', 'pedidos', 'usuarios', 'solicitudes', 'configuracion',
                'auditoria', 'vendedores', 'comisiones', 'inventario', 'notificaciones',
                'chatbot', 'analitica', 'soporte', 'resenas', 'pagos',
            ];
        }
        return [];
    }

    /**
     * Datos del administrador autenticado (o null).
     *
     * @return array<string, mixed>|null
     */
    public static function usuarioActual(): ?array
    {
        $id = self::idSesion();
        if ($id === null) {
            return null;
        }
        return self::porId($id);
    }

    /**
     * Verifica credenciales + estado activo y abre sesión de administrador.
     */
    public static function iniciarSesion(string $usuario, string $password): bool
    {
        $admin = self::porUsuario($usuario);
        if (!$admin || (int) $admin['activo'] !== 1) {
            return false;
        }
        if (!password_verify($password, (string) $admin['password'])) {
            return false;
        }

        self::arrancarSesion();
        session_regenerate_id(true);
        \Security\Proteccion::rotarToken();
        $_SESSION[self::SESION_CLAVE] = (int) $admin['id'];

        // Registro del último ingreso.
        conexion()->prepare('UPDATE users SET ultimo_ingreso = NOW() WHERE id = ?')
            ->execute([(int) $admin['id']]);

        return true;
    }

    public static function cerrarSesion(): void
    {
        self::arrancarSesion();
        unset($_SESSION[self::SESION_CLAVE]);
        session_regenerate_id(true);
        \Security\Proteccion::rotarToken();
    }

    /**
     * Registra una mutación del administrador en la tabla de auditoría.
     *
     * @param int|string|null $referenciaId
     */
    public static function auditar(
        string $usuario,
        string $accion,
        $referenciaId,
        string $detalle
    ): void {
        $stmt = conexion()->prepare(
            'INSERT INTO auditoria_admin (admin_usuario, accion, referencia_id, detalle)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$usuario, $accion, $referenciaId, $detalle]);
    }
}
