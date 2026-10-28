<?php
/**
 * VISTA: elegir nueva contraseña con un token de recuperación (Jeremy Tech).
 * Variables preparadas por src/Controller/CuentaController.php:
 *   $flash  array{tipo: '', 'ok'|'error', mensaje: string}
 *   $token  string  token recibido por la URL
 *   $valido bool    true si el token existe, está vigente y sin usar
 */
$flash  = $flash ?? ['tipo' => '', 'mensaje' => ''];
$token  = $token ?? '';
$valido = $valido ?? false;
?>
<main class="auth-page">
 <div class="auth-wrapper">
  <div class="auth-card">
   <div class="auth-head">
    <h1>Nueva contraseña</h1>
    <p><?= $valido ? 'Elige una contraseña nueva para tu cuenta.' : 'El enlace de recuperación no es válido.' ?></p>
   </div>

   <?php if ($flash['mensaje']): ?>
   <div class="auth-flash auth-flash-<?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($flash['mensaje']) ?></div>
   <?php endif; ?>

   <?php if ($valido): ?>

   <form class="auth-form" action="?ruta=login/restablecer" method="post">
    <?= \Security\Proteccion::campo() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"/>
    <div class="form-row">
     <div class="form-group">
      <label for="resPassword">Nueva contraseña (mín. 6) *</label>
      <input type="password" id="resPassword" name="password" minlength="6" required autocomplete="new-password"/>
     </div>
     <div class="form-group">
      <label for="resConfirmar">Confirmar contraseña *</label>
      <input type="password" id="resConfirmar" name="confirmar" minlength="6" required autocomplete="new-password"/>
     </div>
    </div>
    <button type="submit" class="btn-auth">Cambiar mi contraseña</button>
    <a class="auth-link" href="?ruta=login">&larr; Volver a iniciar sesión</a>
   </form>

   <?php else: ?>

   <p class="auth-flash auth-flash-error">
    El enlace pudo haber vencido (dura 30 minutos) o ya fue utilizado.
   </p>
   <div class="auth-form">
    <a class="auth-link" href="?ruta=login/recuperar">Solicitar un enlace nuevo</a>
    <a class="auth-link" href="?ruta=login">&larr; Volver a iniciar sesión</a>
   </div>

   <?php endif; ?>
  </div>
 </div>
</main>
