<?php
/**
 * VISTA: Mi perfil (Jeremy Tech MVC).
 * Datos de entrada: $perfil (datos guardados), $formulario (borrador si el
 * último POST falló) y $flash.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$formulario = $formulario ?? $perfil;
$tipo = strtoupper((string) ($formulario['tipo'] ?? 'B2C'));
if (!in_array($tipo, ['B2C', 'B2B', 'C2C'], true)) {
    $tipo = 'B2C';
}
$tipoActual = strtoupper((string) ($perfil['tipo'] ?? 'B2C'));
if (!in_array($tipoActual, ['B2C', 'B2B', 'C2C'], true)) {
    $tipoActual = 'B2C';
}
$etiquetaTipo = static fn (string $valor): string => match ($valor) {
    'B2B' => 'Empresa (B2B)',
    'C2C' => 'Revendedor (C2C)',
    default => 'Persona (B2C)',
};
$nombreCompleto = trim((string) ($perfil['nombre'] ?? '') . ' ' . (string) ($perfil['apellidos'] ?? ''));
?>
<main class="cart-page cuenta-page">
 <div class="cart-page-header">
  <h1>👤 Mi Perfil</h1>
  <p>Mantén al día tus datos de contacto y facturación para agilizar tus compras.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <div class="perfil-grid">
  <section class="cart-page-section perfil-card">
   <div class="cart-page-section-head">
    <h2>Datos personales</h2>
    <p>El correo se usa para iniciar sesión y recibir los avisos de tus pedidos.</p>
   </div>

   <form method="post" action="?ruta=perfil/guardar" class="perfil-form" data-tipo="<?= $tipo ?>">
    <?= \Security\Proteccion::campo() ?>

    <div class="form-row">
     <div class="form-group">
      <label for="perfilNombre">Nombres / Razón social *</label>
      <input type="text" id="perfilNombre" name="nombre" maxlength="255" required
             autocomplete="name" value="<?= $e($formulario['nombre'] ?? '') ?>">
     </div>
     <div class="form-group">
      <label for="perfilApellidos">Apellidos <span class="perfil-opcional">(opcional para empresas)</span></label>
      <input type="text" id="perfilApellidos" name="apellidos" maxlength="180"
             autocomplete="family-name" value="<?= $e($formulario['apellidos'] ?? '') ?>">
     </div>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="perfilEmail">Correo electrónico *</label>
      <input type="email" id="perfilEmail" name="email" maxlength="190" required
             autocomplete="email" value="<?= $e($formulario['email'] ?? '') ?>">
     </div>
     <div class="form-group">
      <label for="perfilTelefono">Teléfono</label>
      <input type="tel" id="perfilTelefono" name="telefono" maxlength="20"
             autocomplete="tel" value="<?= $e($formulario['telefono'] ?? '') ?>">
     </div>
    </div>

    <div class="form-row">
     <div class="form-group">
      <label for="perfilDni">DNI</label>
      <input type="text" id="perfilDni" name="dni" maxlength="8" pattern="[0-9]{8}"
             inputmode="numeric" autocomplete="off" value="<?= $e($formulario['dni'] ?? '') ?>">
     </div>
     <div class="form-group">
      <label for="perfilTipo">Tipo de cuenta</label>
      <select id="perfilTipo" name="tipo" class="perfil-select">
       <option value="B2C"<?= $tipo === 'B2C' ? ' selected' : '' ?>>Persona (B2C)</option>
       <option value="B2B"<?= $tipo === 'B2B' ? ' selected' : '' ?>>Empresa (B2B)</option>
       <option value="C2C"<?= $tipo === 'C2C' ? ' selected' : '' ?>>Revendedor (C2C)</option>
      </select>
      <p class="perfil-ayuda">
       Las cuentas C2C pueden publicar productos en
       <a href="?ruta=mis-productos">Mis productos</a>.
      </p>
     </div>
    </div>

    <div class="form-group">
     <label for="perfilDireccion">Dirección de envío</label>
     <input type="text" id="perfilDireccion" name="direccion" maxlength="255"
            autocomplete="street-address" value="<?= $e($formulario['direccion'] ?? '') ?>">
    </div>

    <div class="form-row" data-b2b>
     <div class="form-group">
      <label for="perfilEmpresa">Empresa / Razón social</label>
      <input type="text" id="perfilEmpresa" name="empresa" maxlength="120"
             autocomplete="organization" value="<?= $e($formulario['empresa'] ?? '') ?>">
     </div>
     <div class="form-group">
      <label for="perfilRuc">RUC</label>
      <input type="text" id="perfilRuc" name="ruc" maxlength="11" pattern="[0-9]{11}"
             inputmode="numeric" autocomplete="off" value="<?= $e($formulario['ruc'] ?? '') ?>">
     </div>
    </div>

    <div class="form-group" data-c2c>
     <label for="perfilPlataforma">¿Dónde revendes? <span class="perfil-opcional">(opcional)</span></label>
     <input type="text" id="perfilPlataforma" name="plataforma_reventa" maxlength="120"
            placeholder="Ej. Facebook Marketplace, feria de emprendedores"
            value="<?= $e($formulario['plataforma_reventa'] ?? '') ?>">
    </div>

    <div class="perfil-acciones">
     <button type="submit" class="btn-pagar-ahora perfil-btn">Guardar cambios</button>
     <a class="btn-mini" href="?ruta=direcciones">Gestionar direcciones</a>
    </div>
   </form>
  </section>

  <aside class="perfil-aside">
   <section class="cart-page-section perfil-resumen">
    <h2>Resumen de tu cuenta</h2>
    <p class="perfil-resumen-nombre"><?= $e($nombreCompleto !== '' ? $nombreCompleto : 'Cliente') ?></p>
    <p class="perfil-resumen-email"><?= $e($perfil['email'] ?? '') ?></p>
    <dl class="perfil-datos">
     <div>
      <dt>Tipo</dt>
      <dd><?= $e($etiquetaTipo($tipoActual)) ?></dd>
     </div>
     <?php if ($tipoActual === 'C2C'): ?>
     <div>
      <dt>Plataforma de reventa</dt>
      <dd><?= $e(($perfil['plataforma_reventa'] ?? '') !== '' ? $perfil['plataforma_reventa'] : 'No registrada') ?></dd>
     </div>
     <?php endif; ?>
     <div>
      <dt>Teléfono</dt>
      <dd><?= $e(($perfil['telefono'] ?? '') !== '' ? $perfil['telefono'] : 'No registrado') ?></dd>
     </div>
     <div>
      <dt>DNI</dt>
      <dd><?= $e(($perfil['dni'] ?? '') !== '' ? $perfil['dni'] : 'No registrado') ?></dd>
     </div>
     <div>
      <dt>RUC</dt>
      <dd><?= $e(($perfil['ruc'] ?? '') !== '' ? $perfil['ruc'] : 'No registrado') ?></dd>
     </div>
    </dl>
    <div class="perfil-resumen-acciones">
     <a class="btn-mini" href="?ruta=mis-pedidos">📦 Ver mis pedidos</a>
     <a class="btn-mini" href="?ruta=favoritos">❤️ Mis favoritos</a>
     <a class="btn-mini" href="?ruta=notificaciones">📨 Notificaciones</a>
    </div>
   </section>
  </aside>
 </div>
</main>
