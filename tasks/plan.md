# Plan: Roles cliente/vendedor + solicitud y panel por tipo

## Decisiones de arquitectura
- `puedePublicar()` = solo `rol === 'vendedor'`. Se elimina la ruta `cliente + C2C`.
- La regla se centraliza en `Cuenta::puedePublicar()` y `Vendedor::CONDICION_VENDEDOR`.
- Nueva tabla `solicitudes_vendedor` (pendiente/aprobada/rechazada) + sección `admin/solicitudes`.
- Aprobar solicitud = `UPDATE users SET rol='vendedor'` + estado + auditoría.
- 3 vistas de vendedor: `views/vendedor/panel_b2c.php`, `panel_b2b.php`, `panel_c2c.php`.
  Comparten un partial de gestión (`_gestion_productos.php`) para no triplicar el CRUD.
- Sin tests automatizados en el repo: verificación con `php -l` + smoke test HTTP.

## Orden
1. BD: tabla `solicitudes_vendedor` (dump + live + ANALYZE).
2. Modelo `SolicitudVendedor`.
3. Permisos: `Cuenta`, `Vendedor`, `Producto`, contadores admin.
4. Ruta+controlador cliente para solicitar rol.
5. Sección admin `solicitudes` (listar/aprobar/rechazar) + badge sidebar.
6. Panel vendedor: 3 vistas por tipo + partial compartido.
7. Textos/UI obsoletos (login, perfil, sin_acceso, header, README).
8. Verificación: `php -l`, arranque, smoke test.

## Riesgos
- Usuarios C2C existentes pierden acceso a `mis-productos` (esperado por decisión 1).
- `AdminController::atender()` exige POST solo por regex: añadir `aprobar|rechazar`.
- `admin/panel.php` sidebar debe incluir la sección nueva solo para admin.
