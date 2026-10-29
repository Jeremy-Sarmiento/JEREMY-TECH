<?php
/**
 * VISTA: inicio de sesión y registro (Jeremy Tech).
 * Variables preparadas por src/Controller/CuentaController.php:
 *   $flash           array{tipo: '', 'ok'|'error', mensaje: string}
 *   $tabVisual       'iniciar' | 'registro'  (pestaña activa por defecto)
 *   $registroPrevio  datos conservados tras un error de validación
 *   $zonas           tarifas de envío activas (sin recojo en tienda)
 *
 * El registro pide los mismos datos que la Sesión 8: nombre/razón social,
 * apellidos, DNI, teléfono, dirección, usuario, correo, contraseña y
 * confirmación, tipo de cliente B2C/B2B/C2C, RUC para B2B, plataforma de
 * reventa para C2C y coordenadas opcionales. Además pide la zona de envío
 * (la necesita el checkout) y mantiene la pregunta de seguridad.
 */

$flash          = $flash          ?? ['tipo' => '', 'mensaje' => ''];
$tabVisual      = $tabVisual      ?? 'iniciar';
$registroPrevio = $registroPrevio ?? [];
$zonas          = $zonas          ?? [];
$preguntas      = \Model\Cuenta::PREGUNTAS_PERMITIDAS;

$v = static fn (string $campo, string $defecto = ''): string => htmlspecialchars(
    (string) ($registroPrevio[$campo] ?? $defecto),
    ENT_QUOTES,
    'UTF-8'
);

$tipoCliente = strtoupper((string) ($registroPrevio['tipo_cliente'] ?? 'B2C'));
if (!in_array($tipoCliente, ['B2C', 'B2B', 'C2C'], true)) {
    $tipoCliente = 'B2C';
}
$preguntaSeleccionada = (string) ($registroPrevio['pregunta_secreta'] ?? '');
$zonaSeleccionada = (int) ($registroPrevio['tarifa_envio_id'] ?? 0);
if ($zonaSeleccionada === 0 && $zonas !== []) {
    $zonaSeleccionada = (int) $zonas[0]['id'];
}
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
       <?= \Security\Proteccion::campo() ?>
    <div class="form-group">
     <label for="loginEmail">Correo electrónico</label>
     <input type="email" id="loginEmail" name="email" placeholder="correo@ejemplo.com" required/>
    </div>
    <div class="form-group">
     <label for="loginPassword">Contraseña</label>
     <input type="password" id="loginPassword" name="password" required/>
    </div>
    <button type="submit" class="btn-auth">Iniciar sesión</button>
    <a class="auth-link" href="?ruta=login/recuperar">¿Olvidaste tu contraseña?</a>
    <a class="auth-link" href="?ruta=login/recuperar-pregunta">Recuperar con pregunta de seguridad</a>
   </form>

   <!-- Crear cuenta -->
   <form id="formRegistro" class="auth-form" action="?ruta=login/registrar" method="post" style="<?= $tabVisual === 'registro' ? '' : 'display:none;' ?>">
       <?= \Security\Proteccion::campo() ?>

    <p class="auth-section-title">Datos personales o de la empresa</p>

    <div class="form-row">
     <div class="form-group">
      <label for="regNombre">Nombres / Razón social *</label>
      <input type="text" id="regNombre" name="nombre" maxlength="255" autocomplete="name"
             placeholder="Ej. Juan / Minera Sur S.A.C." value="<?= $v('nombre') ?>" required/>
     </div>
     <div class="form-group">
      <label for="regApellidos">Apellidos <span class="auth-opcional">(opcional para empresas)</span></label>
      <input type="text" id="regApellidos" name="apellidos" maxlength="180" autocomplete="family-name"
             placeholder="Ej. Pérez Gutiérrez" value="<?= $v('apellidos') ?>"/>
     </div>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="regDni">DNI *</label>
      <input type="text" id="regDni" name="dni" maxlength="8" pattern="[0-9]{8}"
             inputmode="numeric" autocomplete="off" placeholder="Ej. 45678912"
             value="<?= $v('dni') ?>" required/>
     </div>
     <div class="form-group">
      <label for="regTelefono">Teléfono / WhatsApp *</label>
      <input type="tel" id="regTelefono" name="telefono" maxlength="20" autocomplete="tel"
             placeholder="Ej. 987654321" value="<?= $v('telefono') ?>" required/>
     </div>
    </div>

    <div class="form-group">
     <label for="regDireccion">Dirección de envío *</label>
     <input type="text" id="regDireccion" name="direccion" maxlength="255" autocomplete="street-address"
            placeholder="Ej. Calle Los Álamos 123, Cercado, Arequipa"
            value="<?= $v('direccion') ?>" required/>
     <p class="auth-hint">Es donde llegará tu pedido. Podrás guardar más direcciones desde tu cuenta.</p>
    </div>

    <?php if ($zonas !== []): ?>
    <div class="form-group">
     <label for="regZona">Zona de envío *</label>
     <select id="regZona" name="tarifa_envio_id" required>
      <?php foreach ($zonas as $zona): ?>
      <option value="<?= (int) $zona['id'] ?>"<?= $zonaSeleccionada === (int) $zona['id'] ? ' selected' : '' ?>>
       <?= htmlspecialchars((string) $zona['zona'], ENT_QUOTES, 'UTF-8') ?>
       — PEN <?= number_format((float) $zona['costo'], 2) ?>
       (<?= htmlspecialchars((string) $zona['dias_estimados'], ENT_QUOTES, 'UTF-8') ?>)
      </option>
      <?php endforeach; ?>
     </select>
     <p class="auth-hint">La zona define el costo y el tiempo de entrega. Podrás cambiarla después.</p>
    </div>
    <?php endif; ?>

    <details class="auth-map" id="regUbicacion">
     <summary>Ubicación exacta de entrega (opcional)</summary>
     <p class="auth-hint">Haz clic en el mapa para marcar el punto exacto. Si no lo marcas, no pasa nada.</p>
     <div id="regMapa" class="auth-map-canvas" role="application" aria-label="Mapa para marcar la ubicación de entrega"></div>
     <div class="auth-map-acciones">
      <span id="regMapaCoords">Sin ubicación marcada.</span>
      <button type="button" class="auth-map-quitar" id="regMapaQuitar">Quitar ubicación</button>
     </div>
    </details>
    <input type="hidden" id="regLatitud" name="latitud" value="<?= $v('latitud') ?>"/>
    <input type="hidden" id="regLongitud" name="longitud" value="<?= $v('longitud') ?>"/>

    <p class="auth-section-title">Acceso a tu cuenta</p>

    <div class="form-group">
     <label for="regUsuario">Nombre de usuario *</label>
     <input type="text" id="regUsuario" name="usuario" maxlength="30" minlength="3"
            pattern="[A-Za-z0-9._-]{3,30}" autocomplete="username"
            placeholder="Ej. juanperez" value="<?= $v('usuario') ?>" required/>
     <p class="auth-hint">Así te verán en la tienda. Letras, números, punto, guion y guion bajo.</p>
    </div>

    <div class="form-group">
     <label for="regEmail">Correo electrónico *</label>
     <input type="email" id="regEmail" name="email" maxlength="190" autocomplete="email"
            placeholder="correo@ejemplo.com" value="<?= $v('email') ?>" required/>
     <p class="auth-hint">Con este correo iniciarás sesión y recibirás los avisos de tus pedidos.</p>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="regPassword">Contraseña (mín. 6) *</label>
      <input type="password" id="regPassword" name="password" minlength="6"
             autocomplete="new-password" required/>
     </div>
     <div class="form-group">
      <label for="regConfirmar">Confirmar contraseña *</label>
      <input type="password" id="regConfirmar" name="confirmar" minlength="6"
             autocomplete="new-password" required/>
     </div>
    </div>
    <p class="auth-hint" id="regClaveAviso"></p>

    <p class="auth-section-title">Tipo de cliente</p>

    <div class="form-group">
     <label for="regTipoCliente">¿Cómo compras? *</label>
     <select id="regTipoCliente" name="tipo_cliente" onchange="regAlternarTipoCliente()">
      <option value="B2C"<?= $tipoCliente === 'B2C' ? ' selected' : '' ?>>Persona individual (B2C)</option>
      <option value="B2B"<?= $tipoCliente === 'B2B' ? ' selected' : '' ?>>Empresa — compra al por mayor (B2B)</option>
      <option value="C2C"<?= $tipoCliente === 'C2C' ? ' selected' : '' ?>>Revendedor — compra para revender (C2C)</option>
     </select>
    </div>

    <div class="form-group" id="regCampoRuc"<?= $tipoCliente === 'B2B' ? '' : ' hidden' ?>>
     <label for="regRuc">RUC de la empresa</label>
     <input type="text" id="regRuc" name="ruc" maxlength="11" pattern="[0-9]{11}"
            inputmode="numeric" autocomplete="off" placeholder="Ej. 20123456789"
            value="<?= $v('ruc') ?>"/>
     <p class="auth-hint">11 dígitos. Lo usaremos si pides factura.</p>
    </div>

    <div class="form-group" id="regCampoReventa"<?= $tipoCliente === 'C2C' ? '' : ' hidden' ?>>
     <label for="regPlataforma">¿Dónde revendes? <span class="auth-opcional">(opcional)</span></label>
     <input type="text" id="regPlataforma" name="plataforma_reventa" maxlength="120"
            placeholder="Ej. Facebook Marketplace, feria de emprendedores"
            value="<?= $v('plataforma_reventa') ?>"/>
     <p class="auth-hint">Toda cuenta se crea como cliente. Si quieres vender, solicita el rol de vendedor desde Mi perfil; un administrador lo revisa.</p>
    </div>

    <p class="auth-section-title">Recuperación de contraseña</p>

    <div class="form-group">
     <label for="regPregunta">Pregunta de seguridad *</label>
     <select id="regPregunta" name="pregunta_secreta" required>
      <option value=""<?= $preguntaSeleccionada === '' ? ' selected' : '' ?> disabled>Selecciona una pregunta…</option>
      <?php foreach ($preguntas as $pregunta): ?>
      <option value="<?= htmlspecialchars($pregunta, ENT_QUOTES, 'UTF-8') ?>"<?= $preguntaSeleccionada === $pregunta ? ' selected' : '' ?>><?= htmlspecialchars($pregunta, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
     </select>
    </div>
    <div class="form-group">
     <label for="regRespuesta">Respuesta de seguridad (mín. 3) *</label>
     <input type="text" id="regRespuesta" name="respuesta_secreta" minlength="3"
            placeholder="Tu respuesta en minúsculas" required/>
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
  if (nombre === 'registro' && typeof regMapaInvalidar === 'function') {
   regMapaInvalidar();
  }
 }

 /** Muestra RUC (B2B) o plataforma de reventa (C2C). */
 function regAlternarTipoCliente() {
  const tipo = document.getElementById('regTipoCliente').value;
  const campoRuc = document.getElementById('regCampoRuc');
  const campoReventa = document.getElementById('regCampoReventa');
  if (campoRuc) campoRuc.hidden = tipo !== 'B2B';
  if (campoReventa) campoReventa.hidden = tipo !== 'C2C';
 }

 /** Aviso inmediato si las contraseñas no coinciden. */
 function regVerificarClaves() {
  const clave = document.getElementById('regPassword');
  const confirmar = document.getElementById('regConfirmar');
  const aviso = document.getElementById('regClaveAviso');
  if (!clave || !confirmar || !aviso) return;
  if (confirmar.value === '') {
   aviso.textContent = '';
   return;
  }
  const coinciden = clave.value === confirmar.value;
  aviso.textContent = coinciden ? 'Las contraseñas coinciden.' : 'Las contraseñas no coinciden.';
  aviso.classList.toggle('auth-hint-ok', coinciden);
  aviso.classList.toggle('auth-hint-error', !coinciden);
 }

 document.addEventListener('DOMContentLoaded', function () {
  regAlternarTipoCliente();

  const clave = document.getElementById('regPassword');
  const confirmar = document.getElementById('regConfirmar');
  if (clave) clave.addEventListener('input', regVerificarClaves);
  if (confirmar) confirmar.addEventListener('input', regVerificarClaves);

  const formRegistro = document.getElementById('formRegistro');
  if (formRegistro) {
   formRegistro.addEventListener('submit', function (event) {
    if (clave && confirmar && clave.value !== confirmar.value) {
     event.preventDefault();
     alert('Las contraseñas no coinciden.');
    }
   });
  }

  const detalleMapa = document.getElementById('regUbicacion');
  if (detalleMapa) {
   detalleMapa.addEventListener('toggle', function () {
    if (detalleMapa.open) {
     regMapaInicializar();
     regMapaInvalidar();
    }
   });
  }

  const botonQuitar = document.getElementById('regMapaQuitar');
  if (botonQuitar) botonQuitar.addEventListener('click', regMapaQuitar);
 });
</script>

<!-- Mapa opcional de precisión de entrega: Leaflet + OpenStreetMap (sin API key) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
 let regMapaLeaflet = null;
 let regMarcadorLeaflet = null;
 const REG_CENTRO = [-16.3988, -71.5369]; // Cercado, Arequipa

 function regMapaInicializar() {
  if (regMapaLeaflet || typeof L === 'undefined') return;
  const contenedor = document.getElementById('regMapa');
  if (!contenedor) return;

  const latInput = document.getElementById('regLatitud');
  const lngInput = document.getElementById('regLongitud');
  const lat = latInput ? parseFloat(latInput.value) : NaN;
  const lng = lngInput ? parseFloat(lngInput.value) : NaN;
  const hayPunto = !Number.isNaN(lat) && !Number.isNaN(lng);

  regMapaLeaflet = L.map(contenedor).setView(hayPunto ? [lat, lng] : REG_CENTRO, hayPunto ? 16 : 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
   attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
   maxZoom: 19
  }).addTo(regMapaLeaflet);

  if (hayPunto) {
   regMapaColocar(lat, lng);
  }
  regMapaLeaflet.on('click', function (evento) {
   regMapaColocar(evento.latlng.lat, evento.latlng.lng);
  });
 }

 function regMapaColocar(lat, lng) {
  if (!regMapaLeaflet) return;
  if (regMarcadorLeaflet) {
   regMarcadorLeaflet.setLatLng([lat, lng]);
  } else {
   regMarcadorLeaflet = L.marker([lat, lng], { draggable: true }).addTo(regMapaLeaflet);
   regMarcadorLeaflet.on('dragend', function () {
    const posicion = regMarcadorLeaflet.getLatLng();
    regMapaActualizar(posicion.lat, posicion.lng);
   });
  }
  regMapaActualizar(lat, lng);
 }

 function regMapaActualizar(lat, lng) {
  const latInput = document.getElementById('regLatitud');
  const lngInput = document.getElementById('regLongitud');
  const texto = document.getElementById('regMapaCoords');
  if (latInput) latInput.value = lat.toFixed(7);
  if (lngInput) lngInput.value = lng.toFixed(7);
  if (texto) texto.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
 }

 function regMapaQuitar() {
  const latInput = document.getElementById('regLatitud');
  const lngInput = document.getElementById('regLongitud');
  const texto = document.getElementById('regMapaCoords');
  if (regMarcadorLeaflet && regMapaLeaflet) {
   regMapaLeaflet.removeLayer(regMarcadorLeaflet);
   regMarcadorLeaflet = null;
  }
  if (latInput) latInput.value = '';
  if (lngInput) lngInput.value = '';
  if (texto) texto.textContent = 'Sin ubicación marcada.';
 }

 function regMapaInvalidar() {
  if (regMapaLeaflet) {
   window.setTimeout(function () { regMapaLeaflet.invalidateSize(); }, 120);
  }
 }
</script>
