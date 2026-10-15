<?php
/**
 * VISTA: inicio de sesión y registro (Jeremy Tech).
 * Variables preparadas por src/Controller/CuentaController.php:
 *   $flash        array{tipo: '', 'ok'|'error', mensaje: string}
 *   $tabVisual    'iniciar' | 'registro'  (pestaña activa por defecto)
 * El registro pide una pregunta de seguridad (Model\Cuenta::PREGUNTAS_PERMITIDAS).
 */

$flash     = $flash     ?? ['tipo' => '', 'mensaje' => ''];
$tabVisual = $tabVisual ?? 'iniciar';
$preguntas = \Model\Cuenta::PREGUNTAS_PERMITIDAS;
?>
<main class="auth-page">
 <div class="auth-wrapper">

  <div class="auth-card">
   <div class="auth-head">
    <h1>Mi Cuenta</h1>
    <p>Accede para realizar tu compra y seguir tus pedidos.</p>
   </div>

   <?php if ($flash['mensaje']): ?>
   <div class="auth-flash auth-flash-<?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($flash['mensaje']) ?></div>
   <?php endif; ?>

   <div class="auth-tabs">
    <button type="button" class="auth-tab<?= $tabVisual !== 'registro' ? ' active' : '' ?>" id="tabIniciar" onclick="mostrarForm('iniciar')">Iniciar sesión</button>
    <button type="button" class="auth-tab<?= $tabVisual === 'registro' ? ' active' : '' ?>" id="tabRegistro" onclick="mostrarForm('registro')">Crear cuenta</button>
   </div>

   <!-- Iniciar sesión -->
   <form id="formIniciar" class="auth-form" action="?ruta=login/iniciar" method="post" style="<?= $tabVisual === 'registro' ? 'display:none;' : '' ?>">
    <div class="form-group">
     <label for="loginEmail">Correo electrónico</label>
     <input type="email" id="loginEmail" name="email" placeholder="correo@ejemplo.com" required/>
    </div>
    <div class="form-group">
     <label for="loginPassword">Contraseña</label>
     <input type="password" id="loginPassword" name="password" required/>
    </div>
    <button type="submit" class="btn-auth">Iniciar sesión</button>
    <a class="auth-link" href="?ruta=login/recuperar-pregunta">¿Olvidaste tu contraseña?</a>
   </form>

   <!-- Crear cuenta -->
   <form id="formRegistro" class="auth-form" action="?ruta=login/registrar" method="post" style="<?= $tabVisual === 'registro' ? '' : 'display:none;' ?>">
    <div class="form-row">
     <div class="form-group">
      <label for="regNombre">Nombre *</label>
      <input type="text" id="regNombre" name="nombre" placeholder="Ej. Juan" required/>
     </div>
     <div class="form-group">
      <label for="regApellidos">Apellidos *</label>
      <input type="text" id="regApellidos" name="apellidos" placeholder="Ej. Pérez Gutiérrez" required/>
     </div>
    </div>
    <div class="form-group">
     <label for="regTelefono">Teléfono / WhatsApp</label>
     <input type="tel" id="regTelefono" name="telefono" placeholder="Ej. 997833866"/>
    </div>
    <div class="form-group">
     <label for="regEmail">Correo electrónico *</label>
     <input type="email" id="regEmail" name="email" placeholder="correo@ejemplo.com" required/>
    </div>
    <div class="form-row">
     <div class="form-group">
      <label for="regPassword">Contraseña (mín. 6) *</label>
      <input type="password" id="regPassword" name="password" required/>
     </div>
     <div class="form-group">
      <label for="regConfirmar">Confirmar contraseña *</label>
      <input type="password" id="regConfirmar" name="confirmar" required/>
     </div>
    </div>
    <div class="form-group">
     <label for="regPregunta">Pregunta de seguridad *</label>
     <select id="regPregunta" name="pregunta_secreta" required>
      <option value="" selected disabled>Selecciona una pregunta…</option>
      <?php foreach ($preguntas as $pregunta): ?>
      <option value="<?= htmlspecialchars($pregunta) ?>"><?= htmlspecialchars($pregunta) ?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="form-group">
     <label for="regRespuesta">Respuesta de seguridad (mín. 3) *</label>
     <input type="text" id="regRespuesta" name="respuesta_secreta" placeholder="Tu respuesta en minúsculas" minlength="3" required/>
    </div>
    <button type="submit" class="btn-auth">Crear cuenta</button>
   </form>

  </div>

 </div>
</main>

<script>
 function mostrarForm(nombre) {
  document.getElementById('formIniciar').style.display = (nombre === 'iniciar') ? '' : 'none';
  document.getElementById('formRegistro').style.display = (nombre === 'registro') ? '' : 'none';
  document.getElementById('tabIniciar').classList.toggle('active', nombre === 'iniciar');
  document.getElementById('tabRegistro').classList.toggle('active', nombre === 'registro');
 }
</script>