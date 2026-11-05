<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../config/conexion.php';

$email = trim(env('ADMIN_EMAIL'));
$usuario = trim(env('ADMIN_USUARIO'));
$clave = env('ADMIN_INITIAL_PASSWORD');
$error = \Security\Contrasena::error($clave);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190
    || $usuario === '' || strlen($usuario) > 60 || $error !== null
) {
    fwrite(STDERR, 'Define ADMIN_EMAIL, ADMIN_USUARIO y ADMIN_INITIAL_PASSWORD (8+ caracteres, no común).' . PHP_EOL);
    exit(1);
}

$pdo = conexion();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, apellidos, email, usuario, password, rol, activo)
         VALUES ('Administrador', '', ?, ?, ?, 'admin', 1)
         ON CONFLICT (email) DO UPDATE SET password = EXCLUDED.password,
             usuario = EXCLUDED.usuario, activo = 1
         WHERE users.rol = 'admin' AND users.deleted_at IS NULL
         RETURNING id"
    );
    $stmt->execute([$email, $usuario, password_hash($clave, PASSWORD_DEFAULT)]);
    $id = $stmt->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('El correo pertenece a otra cuenta; no se cambió su rol.');
    }
    $pdo->prepare('UPDATE tokens_recuperacion SET usado = 1 WHERE usuario_id = ? AND usado = 0')->execute([$id]);
    $pdo->commit();
    fwrite(STDOUT, 'Administrador configurado. La contraseña no se imprime ni se guarda en archivos.' . PHP_EOL);
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'No se pudo configurar el administrador. Revisa el correo, el usuario y la base seleccionada.' . PHP_EOL);
    exit(1);
}
