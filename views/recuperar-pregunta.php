<?php
/**
 * VISTA: recuperación de contraseña por pregunta de seguridad (Jeremy Tech).
 * Variables preparadas por src/Controller/CuentaController.php:
 *   $flash     array{tipo: '', 'ok'|'error', mensaje: string}  (mensaje flash)
 *   $email     string  correo ingresado en el paso 1 (para prellenar)
 *   $pregunta  string|null  si está definida => paso 2 (mostrar pregunta y nueva clave)
 */

$flash    = $flash    ?? ['tipo' => '', 'mensaje' => ''];
$email    = $email    ?? '';
$pregunta = $pregunta ?? null;
?>
<main class="auth-page">
 <div class="auth-wrapper">
  <div class="auth-card">
   <div class="auth-head">
    <h1><?= $pregunta !== null ? 'Responder pregunta de seguridad' : 'Recuperar Contraseña' ?></h1>
    <p><?= $pregunta !== null ? 'Responde tu pregunta para elegir una nueva contraseña.' : 'Verificaremos tu identidad con tu pregunta de seguridad.' ?></p>
   </div>

   <?php if ($flash['mensaje']): ?>
   <div class="auth-flash auth-flash-<?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($flash['mensaje']) ?></div>
   <?php endif; ?>

   <?php if ($pregunta !== null): ?>

   <form class="auth-form" action="?ruta=login/recuperar-pregunta" method="post">
       <?= \Security\Proteccion::campo() ?>
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>"/>
    <div class="form-group">
     <label>Tu pregunta de seguridad</label>
     <p class="pregunta-texto"><?= htmlspecialchars($pregunta) ?></p>
    </div>
    <div class="form-group">
     <label for="reRespuesta">Respuesta (mín. 3) *</label>
     <input type="text" id="reRespuesta" name="respuesta_secreta" placeholder="Tu respuesta en minúsculas" minlength="3" required/>
    </div>
    <div class="form-row">
     <div class="form-group">
      <label for="rePassword">Nueva contraseña (mín. 6) *</label>
      <input type="password" id="rePassword" name="password" required/>
     </div>
     <div class="form-group">
      <label for="reConfirmar">Confirmar contraseña *</label>
      <input type="password" id="reConfirmar" name="confirmar" required/>
     </div>
    </div>
    <button type="submit" class="btn-auth">Cambiar mi contraseña</button>
    <a class="auth-link" href="?ruta=login/recuperar">Prefiero recibir un enlace por correo</a>
    <a class="auth-link" href="?ruta=login">← Volver a iniciar sesión</a>
   </form>

   <?php else: ?>

   <form class="auth-form" action="?ruta=login/recuperar-pregunta" method="post">
       <?= \Security\Proteccion::campo() ?>
    <div class="form-group">
     <label for="reEmail">Correo electrónico *</label>
     <input type="email" id="reEmail" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="correo@ejemplo.com" required/>
    </div>
    <button type="submit" class="btn-auth">Continuar</button>
    <a class="auth-link" href="?ruta=login/recuperar">Prefiero recibir un enlace por correo</a>
    <a class="auth-link" href="?ruta=login">← Volver a iniciar sesión</a>
   </form>

   <?php endif; ?>

  </div>
 </div>
</main>