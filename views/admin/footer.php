<script>
// Drawer móvil del panel: abrir/cerrar sin JS de terceros (tema oscuro fijo).
(function () {
  var shell = document.querySelector('.admin-shell');
  if (!shell) { return; }

  function alternar(abrir) {
    shell.classList.toggle('menu-abierto', abrir);
    document.body.style.overflow = abrir ? 'hidden' : '';
  }

  var btnMenu = document.getElementById('adminBtnMenu');
  var btnCerrar = document.getElementById('adminCerrarDrawer');
  var backdrop = document.getElementById('adminBackdrop');

  if (btnMenu) { btnMenu.addEventListener('click', function () { alternar(!shell.classList.contains('menu-abierto')); }); }
  if (btnCerrar) { btnCerrar.addEventListener('click', function () { alternar(false); }); }
  if (backdrop) { backdrop.addEventListener('click', function () { alternar(false); }); }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { alternar(false); }
  });
})();
</script>

<script>
// Modales de creación/edición (<dialog> nativo, estilo Filament).
//  · [data-modal-abrir=ID]   abre el modal; con data-limpiar vacía el formulario.
//  · [data-modal-cerrar]      cierra; si trae data-volver navega a esa ruta.
//  · [data-modal-auto="1"]    se abre solo al cargar (caso edición ?editar=ID).
(function () {
  document.querySelectorAll('dialog.admin-modal').forEach(function (dlg) {
    dlg.querySelectorAll('[data-modal-cerrar]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var volver = btn.getAttribute('data-volver');
        if (volver) { window.location.href = volver; return; }
        dlg.close();
      });
    });

    // Clic sobre el fondo oscuro (el backdrop) cierra el modal.
    dlg.addEventListener('click', function (e) {
      if (e.target === dlg) { dlg.close(); }
    });

    // Edición (?editar=ID, modal auto): cerrar por Esc, fondo o X debe volver a
    // la ruta limpia para que reaparezca el botón "Nuevo".
    dlg.addEventListener('close', function () {
      if (dlg.getAttribute('data-modal-auto') !== '1') { return; }
      var volver = dlg.querySelector('[data-volver]');
      if (volver) { window.location.href = volver.getAttribute('data-volver'); }
    });

    if (dlg.getAttribute('data-modal-auto') === '1' && !dlg.open) {
      dlg.showModal();
    }
  });

  document.querySelectorAll('[data-modal-abrir]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var dlg = document.getElementById(btn.getAttribute('data-modal-abrir'));
      if (!dlg) { return; }
      if (btn.hasAttribute('data-limpiar')) {
        var form = dlg.querySelector('form');
        if (form) { form.reset(); }
      }
      dlg.showModal();
    });
  });
})();
</script>
</body>
</html>
