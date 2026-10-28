<?php
/**
 * VISTA: solicitud de enlace de recuperación por correo (Jeremy Tech).
 * Variables preparadas por src/Controller/CuentaController.php:
 *   $flash  array{tipo: '', 'ok'|'error', mensaje: string}
 *   $email  string  correo (vacío en GET; el POST redirige con flash)
 */
$flash = $flash ?? ['tipo' => '', 'mensaje' => ''];
$email = $email ?? '';
?>
<main class="auth-page">
 <div class="auth-wrapper">
  <div class="auth-card">
   <div class="auth-head">
    <h1>Recuperar Contraseña</h1>
    <p>Te enviaremos un enlace de un solo uso al correo de tu cuenta.</p>
   </div>

   <?php if ($flash['mensaje']): ?>
   <div class="auth-flash auth-flash-<?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($flash['mensaje']) ?></div>
   <?php endif; ?>

   <form class="auth-form" action="?ruta=login/recuperar" method="post">
    <?= \Security\Proteccion::campo() ?>
    <div class="form-group">
     <label for="recEmail">Correo electrónico *</label>
     <input type="email" id="recEmail" name="email" value="<?= htmlspecialchars($email) ?>"
            placeholder="correo@ejemplo.com" required autocomplete="email"/>
    </div>
    <button type="submit" class="btn-auth">Enviar enlace</button>
    <a class="auth-link" href="?ruta=login/recuperar-pregunta">Recuperar con mi pregunta de seguridad</a>
    <a class="auth-link" href="?ruta=login">&larr; Volver a iniciar sesión</a>
   </form>
  </div>
 </div>
</main>
