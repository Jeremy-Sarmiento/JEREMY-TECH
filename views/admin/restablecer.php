<?php
/**
 * VISTA: elegir nueva contraseña del panel con un token (Jeremy Tech).
 * Variables preparadas por src/Controller/AdminController.php:
 *   $flash  array{tipo: '', 'ok'|'error', mensaje: string}
 *   $token  string
 *   $valido bool
 */
$flash  = $flash ?? ['tipo' => '', 'mensaje' => ''];
$token  = $token ?? '';
$valido = $valido ?? false;
$e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="body-admin">
    <div class="login-caja">
        <h1 class="login-titulo">Nueva contraseña</h1>
        <p class="login-subtitulo">
            <?= $valido ? 'Elige una contraseña nueva para tu acceso al panel.' : 'El enlace no es válido.' ?>
        </p>

        <?php if ($flash['tipo'] === 'error' && $flash['mensaje'] !== ''): ?>
            <p class="flash flash-error"><?= $e($flash['mensaje']) ?></p>
        <?php elseif ($flash['tipo'] === 'ok' && $flash['mensaje'] !== ''): ?>
            <p class="flash flash-ok"><?= $e($flash['mensaje']) ?></p>
        <?php endif; ?>

        <?php if ($valido): ?>
            <form method="post" action="?ruta=admin/recuperar/restablecer" class="login-forma">
                <?= \Security\Proteccion::campo() ?>
                <input type="hidden" name="token" value="<?= $e($token) ?>">

                <label for="clave">Nueva contraseña (mín. 6)</label>
                <input type="password" id="clave" name="password" minlength="6" required autocomplete="new-password">

                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar" minlength="6" required autocomplete="new-password">

                <button type="submit" class="btn btn-primario">Cambiar contraseña</button>
            </form>
        <?php else: ?>
            <p class="flash flash-error">
                El enlace pudo haber vencido (dura 30 minutos) o ya fue utilizado.
            </p>
            <p class="login-volver">
                <a href="?ruta=admin/recuperar">Solicitar un enlace nuevo</a>
            </p>
        <?php endif; ?>

        <p class="login-volver">
            <a href="?ruta=admin/login">&larr; Volver al acceso</a>
        </p>
    </div>
</main>
