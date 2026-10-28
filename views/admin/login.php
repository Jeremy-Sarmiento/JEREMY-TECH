<?php
/** @var array $flash */
$flash = $flash ?? ['tipo' => '', 'mensaje' => ''];
$usuario = isset($_POST['usuario']) ? htmlspecialchars((string) $_POST['usuario'], ENT_QUOTES, 'UTF-8') : '';
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="body-admin">
    <div class="login-caja">
        <h1 class="login-titulo">Panel de Administración</h1>
        <p class="login-subtitulo">Jeremy Tech · acceso restringido</p>
        <?php if ($flash['tipo'] === 'error' && $flash['mensaje'] !== ''): ?>
            <p class="flash flash-error"><?= $e($flash['mensaje']) ?></p>
        <?php elseif ($flash['tipo'] === 'ok' && $flash['mensaje'] !== ''): ?>
            <p class="flash flash-ok"><?= $e($flash['mensaje']) ?></p>
        <?php endif; ?>

        <form method="post" action="?ruta=admin/login/autenticar" class="login-forma">
            <?= \Security\Proteccion::campo() ?>
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" value="<?= $usuario ?>"
                   required autofocus autocomplete="username">

            <label for="clave">Contraseña</label>
            <input type="password" id="clave" name="clave" required autocomplete="current-password">

            <button type="submit" class="btn btn-primario">Entrar</button>
        </form>

        <p class="login-volver">
            <a href="?ruta=admin/recuperar">¿Olvidaste tu contraseña?</a>
        </p>

        <p class="login-volver">
            <a href="?ruta=catalogo">&larr; Volver a la tienda</a>
        </p>
    </div>
</main>
