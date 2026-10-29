<?php
/** @var string $seccionAdmin */

// Sidebar estilo Filament: secciones agrupadas + icono por enlace.
$secciones = [
    'dashboard'     => ['Resumen',      'admin/dashboard'],
    'productos'     => ['Productos',    'admin/productos'],
    'variantes'     => ['Variantes',    'admin/variantes'],
    'categorias'    => ['Categorías',   'admin/categorias'],
    'marcas'        => ['Marcas',       'admin/marcas'],
    'garantias'     => ['Garantías',    'admin/garantias'],
    'cupones'       => ['Cupones',      'admin/cupones'],
    'envios'        => ['Envíos',       'admin/envios'],
    'pedidos'       => ['Pedidos',      'admin/pedidos'],
    'vendedores'    => ['Vendedores',   'admin/vendedores'],
    'comisiones'    => ['Comisiones',   'admin/comisiones'],
    'ventas'        => ['Mis ventas',   'admin/ventas'],
    'inventario'    => ['Inventario',   'admin/inventario'],
    'notificaciones' => ['Notificaciones', 'admin/notificaciones'],
    'soporte'       => ['Soporte',      'admin/soporte'],
    'resenas'       => ['Reseñas',      'admin/resenas'],
    'pagos'         => ['Pagos',        'admin/pagos'],
    'analitica'    => ['Analítica',   'admin/analitica'],
    'chatbot'      => ['Chatbot FAQ',  'admin/chatbot'],
    'usuarios'      => ['Usuarios',     'admin/usuarios'],
    'solicitudes'   => ['Solicitudes',  'admin/solicitudes'],
    'configuracion' => ['Configuración','admin/configuracion'],
    'auditoria'     => ['Auditoría',    'admin/auditoria'],
];

$grupos = [
    ['General', ['dashboard']],
    ['Catálogo', ['productos', 'variantes', 'categorias', 'marcas', 'garantias']],
    ['Ventas', ['pedidos', 'ventas', 'cupones', 'envios', 'vendedores', 'comisiones', 'pagos', 'analitica']],
    ['Operaciones', ['inventario', 'notificaciones', 'soporte', 'resenas']],
    ['Sistema', ['usuarios', 'solicitudes', 'configuracion', 'auditoria', 'chatbot']],
];

// (Etapa 6) El panel es único y se filtra por rol. La fuente de verdad del
// alcance es Administrador::seccionesPermitidas(), la misma que usa el
// router de AdminController: sidebar y dispatch no pueden divergir.
$permitidas = \Model\Administrador::seccionesPermitidas();
$solicitudesPendientes = in_array('solicitudes', $permitidas, true)
    ? \Model\SolicitudVendedor::pendientesCount()
    : 0;
$secciones  = array_intersect_key($secciones, array_flip($permitidas));
$grupos = array_values(array_filter(
    $grupos,
    static function (array $grupo) use ($permitidas): bool {
        return array_intersect($grupo[1], $permitidas) !== [];
    }
));
foreach ($grupos as $i => $grupo) {
    $grupos[$i][1] = array_values(array_intersect($grupo[1], $permitidas));
}

// Iconos SVG (trazo estilo Lucide, se pintan con currentColor).
$iconos = [
    'dashboard'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
    'productos'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/><path d="M3 7l9 5 9-5"/><path d="M12 12v10"/></svg>',
    'variantes'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
    'categorias'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>',
    'marcas'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12.52.5A2 2 0 0 0 11.17 2H4a2 2 0 0 0-2 2v7.17a2 2 0 0 0 .59 1.41l8.7 8.7a2.43 2.43 0 0 0 3.42 0l6.58-6.58a2.43 2.43 0 0 0 0-3.42Z"/><circle cx="7.5" cy="7.5" r="1.3"/></svg>',
    'garantias'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5 4.5 5.5v6c0 4.6 3.2 8.7 7.5 9.9 4.3-1.2 7.5-5.3 7.5-9.9v-6L12 2.5Z"/><path d="m9 12 2 2 4-4"/></svg>',
    'cupones'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v3a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-3a3 3 0 0 1 0-6V6a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v3Z"/><path d="M13 6v2M13 11v2M13 16v2"/></svg>',
    'envios'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17V5H2v12h2"/><path d="M14 9h4l4 4v4h-6"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>',
    'pedidos'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M2 3h3l2.6 11.4a1.5 1.5 0 0 0 1.5 1.2h7.6a1.5 1.5 0 0 0 1.5-1.2L21 7H6"/></svg>',
    'usuarios'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5.5c0 4.4-3 8-7 9.5-4-1.5-7-5.1-7-9.5V6l7-3Z"/><path d="M9.4 12l1.9 1.9 3.4-3.7"/></svg>',
    'configuracion' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h10M18 18h2"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="16" cy="18" r="2"/></svg>',
    'auditoria'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3 8.5"/><path d="M3 3.5v5h5"/><path d="M12 7.5V12l3.2 1.9"/></svg>',
    'solicitudes'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>',
    'vendedores'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'ventas'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-4 4"/></svg>',
    'comisiones'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>',
    'inventario'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M5 7V5a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v2"/><rect x="4" y="7" width="16" height="13" rx="1.5"/><path d="M9 12h6"/></svg>',
    'notificaciones' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M13.7 20a2 2 0 0 1-3.4 0"/></svg>',
    'soporte'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="13" width="4" height="7" rx="1.5"/><rect x="18" y="13" width="4" height="7" rx="1.5"/><path d="M20 20a3 3 0 0 1-3 3h-3"/></svg>',
    'resenas'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/></svg>',
    'pagos'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>',
    'analitica'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-4 4"/></svg>',
    'chatbot'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
    'tienda'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    'salir'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>',
];

$actual = $seccionAdmin ?? 'dashboard';
// Si la sección pedida no está en el alcance del rol, caer a la primera.
if (!isset($secciones[$actual])) {
    $actual = array_key_first($secciones) ?? 'dashboard';
}
$etiquetaActual = $secciones[$actual][0] ?? $actual;
$admin = \Model\Administrador::usuarioActual();
$letraAdmin = mb_strtoupper(mb_substr((string) ($admin['usuario'] ?? 'A'), 0, 1));
$rolPanel = \Model\Administrador::rol() ?? 'cliente';
$etiquetaRol = $rolPanel === 'vendedor' ? 'Vendedor' : 'Administrador';
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="admin-shell">
    <div class="admin-backdrop" id="adminBackdrop"></div>

    <aside class="admin-sidebar">
        <h2 class="admin-logo">
            <span class="admin-logo-caja">J</span>
            <span>Jeremy Tech<small>Panel</small></span>
            <button type="button" class="admin-cerrar-drawer" id="adminCerrarDrawer" aria-label="Cerrar menú">&times;</button>
        </h2>

        <nav class="admin-nav">
            <?php foreach ($grupos as [$tituloGrupo, $clavesGrupo]): ?>
                <p class="admin-nav-grupo"><?= $e($tituloGrupo) ?></p>
                <?php foreach ($clavesGrupo as $claveSeccion): ?>
                    <a class="admin-enlace<?= $actual === $claveSeccion ? ' admin-enlace-activo' : '' ?>"
                       href="?ruta=<?= $secciones[$claveSeccion][1] ?>">
                        <?= $iconos[$claveSeccion] ?>
                        <span><?= $e($secciones[$claveSeccion][0]) ?></span>
                        <?php if ($claveSeccion === 'solicitudes' && $solicitudesPendientes > 0): ?>
                        <span class="etiqueta etiqueta-aviso"><?= $solicitudesPendientes ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-pie">
            <a class="admin-enlace" href="index.php"><?= $iconos['tienda'] ?><span>Ver la tienda</span></a>
            <form method="post" action="?ruta=admin/cerrar" class="admin-logout-form">
                <?= \Security\Proteccion::campo() ?>
                <button type="submit" class="admin-enlace admin-enlace-salir">
                    <?= $iconos['salir'] ?><span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="admin-cuerpo">
        <header class="admin-topbar">
            <button type="button" class="admin-topbar-btn" id="adminBtnMenu" aria-label="Abrir menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <div class="admin-migas">
                <span class="admin-migas-ruta">Jeremy Tech / Panel (<?= $e($etiquetaRol) ?>) /</span>
                <strong><?= $e($etiquetaActual) ?></strong>
            </div>

            <div class="admin-topbar-derecha">
                <a class="btn btn-claro" href="index.php" style="padding:7px 12px;font-size:13px;">Ver tienda</a>
                <span class="admin-avatar" title="<?= $e(($admin['usuario'] ?? 'Administrador') . ' — ' . $etiquetaRol) ?>"><?= $e($letraAdmin) ?></span>
            </div>
        </header>

        <main class="admin-contenido">
            <?php
            $flashAdmin = $flashAdmin ?? ['tipo' => '', 'mensaje' => ''];
            if ($flashAdmin['mensaje'] !== '' && ($flashAdmin['tipo'] === 'ok' || $flashAdmin['tipo'] === 'error')) {
                echo '<p class="flash flash-' . ($flashAdmin['tipo'] === 'ok' ? 'ok' : 'error') . '">'
                    . $e($flashAdmin['mensaje']) . '</p>';
            }

            $archivoSeccion = BASE_PATH . '/views/admin/secciones/' . basename($actual) . '.php';
            if (is_file($archivoSeccion)) {
                require $archivoSeccion;
            } else {
                echo '<section class="admin-seccion"><p>Sección no encontrada: <code>' . $e($actual) . '</code></p></section>';
            }
            ?>
        </main>
    </div>
</div>
