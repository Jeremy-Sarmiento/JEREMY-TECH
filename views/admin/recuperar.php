<?php
/**
 * VISTA: solicitar enlace de recuperación de acceso al panel (Jeremy Tech).
 * Variables preparadas por src/Controller/AdminController.php:
 *   $flash  array{tipo: '', 'ok'|'error', mensaje: string}
 */
$flash = $flash ?? ['tipo' => '', 'mensaje' => ''];
$usuario = isset($_POST['usuario']) ? (string) $_POST['usuario'] : '';
$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="body-admin">
    <div class="login-caja">
        <h1 class="login-titulo">Recuperar acceso</h1>
        <p class="login-subtitulo">Enviaremos un enlace al correo del usuario.</p>

        <?php if ($flash['tipo'] === 'error' && $flash['mensaje'] !== ''): ?>
            <p class="flash flash-error"><?= $e($flash['mensaje']) ?></p>
        <?php elseif ($flash['tipo'] === 'ok' && $flash['mensaje'] !== ''): ?>
            <p class="flash flash-ok"><?= $e($flash['mensaje']) ?></p>
        <?php endif; ?>

        <form method="post" action="?ruta=admin/recuperar" class="login-forma">
            <?= \Security\Proteccion::campo() ?>
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" value="<?= $e($usuario) ?>"
                   required autofocus autocomplete="username">

            <button type="submit" class="btn btn-primario">Enviar enlace</button>
        </form>

        <p class="login-volver">
            <a href="?ruta=admin/login">&larr; Volver al acceso</a>
        </p>
    </div>
</main>
