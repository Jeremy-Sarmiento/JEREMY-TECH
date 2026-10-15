<?php
declare(strict_types=1);

namespace Controller;

use Model\Administrador as Admin;

/**
 * Panel de administración de Jeremy Tech (vía ?ruta=admin/...).
 * Sesión independiente de la del cliente: usa la clave `admin_id` en
 * $_SESSION (ver Model\Administrador) para no interrumpir una cuenta
 * de cliente activa. Cada mutación se registra con Admin::auditar().
 */
final class AdminController
{
    public function atender(string $subRuta): void
    {
        $subRuta = trim($subRuta, '/');
        if ($subRuta === '') {
            $subRuta = 'inicio';
        }
        if ($subRuta === 'login' || $subRuta === 'login/autenticar') {
            $this->login($subRuta);
            return;
        }
        if ($subRuta === 'cerrar') {
            $this->cerrar();
            return;
        }

        if (!Admin::estaAutenticado()) {
            header('Location: ?ruta=admin/login');
            exit;
        }

        // (Etapa 6) El panel es uno y se filtra por rol: un vendedor no
        // puede ni siquiera llegar al código de las secciones de admin.
        $permitidas = Admin::seccionesPermitidas();
        if ($permitidas === []) {
            Admin::cerrarSesion();
            header('Location: ?ruta=admin/login');
            exit;
        }

        if ($subRuta === 'inicio' || $subRuta === 'dashboard') {
            $this->dashboard();
            return;
        }

        // (Etapa 4) La sección "Clientes" ya no existe: todo vive en Usuarios.
        if ($subRuta === 'clientes' || str_starts_with($subRuta, 'clientes/')) {
            header('Location: ?ruta=' . $this->rutaPorDefecto($permitidas));
            exit;
        }

        foreach ($permitidas as $prefijo) {
            if ($subRuta === $prefijo || str_starts_with($subRuta, $prefijo . '/')) {
                $this->$prefijo($subRuta);
                return;
            }
        }

        // Sección fuera del alcance del rol (o desconocida): volver a la suya.
        header('Location: ?ruta=' . $this->rutaPorDefecto($permitidas));
        exit;
    }

    /**
     * Primera ruta navegable para un rol, usada para los redirigidos.
     *
     * @param array<int, string> $permitidas
     */
    private function rutaPorDefecto(array $permitidas): string
    {
        $mapa = [
            'dashboard' => 'admin/dashboard', 'productos' => 'admin/productos',
            'variantes' => 'admin/variantes',
            'categorias' => 'admin/categorias', 'marcas' => 'admin/marcas',
            'garantias' => 'admin/garantias', 'cupones' => 'admin/cupones',
            'envios' => 'admin/envios', 'pedidos' => 'admin/pedidos',
            'usuarios' => 'admin/usuarios', 'configuracion' => 'admin/configuracion',
            'auditoria' => 'admin/auditoria', 'vendedores' => 'admin/vendedores',
            'ventas' => 'admin/ventas', 'comisiones' => 'admin/comisiones',
        ];
        foreach ($permitidas as $seccion) {
            if (isset($mapa[$seccion])) {
                return $mapa[$seccion];
            }
        }
        return 'admin/dashboard';
    }

    // ------------------------------------------------------------ //
    //  Autenticación
    // ------------------------------------------------------------ //

    private function login(string $subRuta): void
    {
        if (Admin::estaAutenticado()) {
            header('Location: ?ruta=admin');
            exit;
        }

        if ($subRuta === 'login/autenticar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $clave   = (string) ($_POST['clave'] ?? '');

            if (Admin::iniciarSesion($usuario, $clave)) {
                $admin = Admin::usuarioActual();
                Admin::auditar(
                    (string) ($admin['usuario'] ?? $usuario),
                    'admin/login',
                    (int) ($admin['id'] ?? 0),
                    'Ingreso al panel de administración'
                );
                header('Location: ?ruta=admin');
                exit;
            }

            $this->flash('Usuario o contraseña incorrectos (o cuenta inactiva).', true);
            header('Location: ?ruta=admin/login');
            exit;
        }

        $flash = $this->tomarFlash();
        $tituloAdmin = 'Acceso';
        require BASE_PATH . '/views/admin/header.php';
        require BASE_PATH . '/views/admin/login.php';
        require BASE_PATH . '/views/admin/footer.php';
    }

    private function cerrar(): void
    {
        $admin = Admin::usuarioActual();
        if ($admin !== null) {
            Admin::auditar(
                (string) ($admin['usuario'] ?? ''),
                'admin/cerrar',
                (int) ($admin['id'] ?? 0),
                'Cierre de sesión del panel'
            );
        }
        Admin::cerrarSesion();
        header('Location: ?ruta=admin/login');
        exit;
    }

    private function renderizar(string $seccion, array $datos): void
    {
        $paginaAdmin  = true;
        $seccionAdmin = $seccion;
        $tituloAdmin  = 'Panel';
        $flashAdmin   = $this->tomarFlash();
        require BASE_PATH . '/views/admin/header.php';
        require BASE_PATH . '/views/admin/panel.php';
        require BASE_PATH . '/views/admin/footer.php';
    }

    private function vista(string $seccion, array $datos): void
    {
        $this->renderizar($seccion, $datos);
    }

    private function flash(string $mensaje, bool $esError = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_flash'] = ['tipo' => $esError ? 'error' : 'ok', 'mensaje' => $mensaje];
    }

    private function tomarFlash(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $flash = $_SESSION['admin_flash'] ?? ['tipo' => '', 'mensaje' => ''];
        unset($_SESSION['admin_flash']);
        return $flash;
    }

    private function slugify(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'], ['a', 'e', 'i', 'o', 'u', 'n', 'u'], $texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? $texto;
        return trim($texto, '-');
    }

    private function auditar(array $admin, string $accion, int $referencia, string $detalle): void
    {
        Admin::auditar((string) ($admin['usuario'] ?? ''), $accion, $referencia, $detalle);
    }

    // ------------------------------------------------------------ //
    //  Dashboard
    // ------------------------------------------------------------ //

    private function dashboard(): void
    {
        $pdo   = conexion();
        $admin = Admin::usuarioActual();

        if (Admin::esVendedor()) {
            // (Etapa 6) El vendedor solo ve lo suyo: nada de usuarios ni
            // cupones de la plataforma.
            $vendedorId = (int) Admin::idSesion();
            $totales = [
                'productos'  => 0,
                'pedidos'    => 0,
                'pendientes' => 0,
                'usuarios'   => 0,
                'cupones'    => 0,
                'auditoria'  => 0,
            ];

            $q = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE vendedor_id = ?');
            $q->execute([$vendedorId]);
            $totales['productos'] = (int) $q->fetchColumn();

            $q = $pdo->prepare(
                "SELECT COUNT(DISTINCT p.id), SUM(CASE WHEN p.estado = 'Pendiente' THEN 1 ELSE 0 END)
                   FROM pedidos p
                   JOIN detalle_pedido dp ON dp.pedido_id = p.id
                  WHERE dp.vendedor_id = ?"
            );
            $q->execute([$vendedorId]);
            [$pedidos, $pendientes] = $q->fetch(\PDO::FETCH_NUM) ?: [0, 0];
            $totales['pedidos'] = (int) $pedidos;
            $totales['pendientes'] = (int) $pendientes;

            $totales['ganancias']  = \Model\Vendedor::gananciasDe($vendedorId);
            $totales['saldo']      = \Model\Vendedor::saldoPendiente($vendedorId);
            $totales['liquidado']  = \Model\Vendedor::liquidadoDe($vendedorId);

            $q = $pdo->prepare(
                "SELECT IFNULL(SUM(c.monto_venta), 0) FROM comisiones c
                   JOIN pedidos p ON p.id = c.pedido_id
                  WHERE c.vendedor_id = ? AND p.estado NOT IN ('Cancelado', 'Pendiente')"
            );
            $q->execute([$vendedorId]);
            $totales['ventas'] = (float) $q->fetchColumn();

            $q = $pdo->prepare(
                'SELECT p.id, p.estado, p.total_pen, p.creado_en, c.name AS nombre, c.apellidos
                   FROM pedidos p
                   JOIN users c ON c.id = p.cliente_id
                   JOIN detalle_pedido dp ON dp.pedido_id = p.id AND dp.vendedor_id = ?
                  GROUP BY p.id
                  ORDER BY p.id DESC LIMIT 8'
            );
            $q->execute([$vendedorId]);
            $ultimos = $q->fetchAll();

            $this->vista('dashboard', [
                'totales' => $totales,
                'ultimos' => $ultimos,
                'vendedor' => true,
            ]);
            return;
        }

        $totales = [
            'productos'  => (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
            'pedidos'    => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
            'pendientes' => (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn(),
            'usuarios'   => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn(),
            'cupones'    => (int) $pdo->query('SELECT COUNT(*) FROM cupones')->fetchColumn(),
            'auditoria'  => (int) $pdo->query('SELECT COUNT(*) FROM auditoria_admin')->fetchColumn(),
            'vendedores' => (int) $pdo->query(
                "SELECT COUNT(*) FROM users WHERE rol = 'vendedor' AND deleted_at IS NULL"
            )->fetchColumn(),
            'comisiones' => (float) $pdo->query('SELECT IFNULL(SUM(monto_comision), 0) FROM comisiones')->fetchColumn(),
        ];

        $ultimos = $pdo->query(
            'SELECT p.id, p.estado, p.total_pen, p.creado_en, c.name AS nombre, c.apellidos
             FROM pedidos p JOIN users c ON c.id = p.cliente_id
             ORDER BY p.id DESC LIMIT 8'
        )->fetchAll();

        $this->vista('dashboard', ['totales' => $totales, 'ultimos' => $ultimos, 'vendedor' => false]);
    }

    // ------------------------------------------------------------ //
    //  Productos
    // ------------------------------------------------------------ //

    private function productos(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'productos/eliminar') {
            $slug = (string) ($_GET['id'] ?? '');
            if ($slug !== '') {
                if (!\Model\Producto::puedeGestionar($slug)) {
                    $this->flash('No tienes permiso sobre ese producto.', true);
                    header('Location: ?ruta=admin/productos');
                    exit;
                }
                $pdo->prepare('UPDATE productos SET activo = 0 WHERE slug = ?')->execute([$slug]);
                $this->auditar($admin, 'productos/eliminar', 0, 'Ocultó el producto "' . $slug . '"');
            }
            header('Location: ?ruta=admin/productos');
            exit;
        }

        if ($subRuta === 'productos/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id      = trim((string) ($_POST['id'] ?? ''));
            $nombre  = trim((string) ($_POST['nombre'] ?? ''));
            $categoria = trim((string) ($_POST['categoria'] ?? ''));
            $marcaId = ($_POST['marca_id'] ?? '') !== '' ? (int) $_POST['marca_id'] : null;
            $garantiaId = ($_POST['garantia_id'] ?? '') !== '' ? (int) $_POST['garantia_id'] : null;
            $precio  = is_numeric($_POST['precio_pen'] ?? null) ? (float) $_POST['precio_pen'] : -1;
            $stock   = (int) ($_POST['stock'] ?? 0);
            $stockMinimo = (int) ($_POST['stock_minimo'] ?? 0);
            $activo  = isset($_POST['activo']) ? 1 : 0;
            $imagen  = trim((string) ($_POST['imagen'] ?? ''));
            $especificaciones = trim((string) ($_POST['especificaciones'] ?? ''));
            $imagen = $imagen !== '' ? $imagen : 'img/producto-default.png';

            // Ficha técnica (Etapa 3). Cadena vacía -> NULL.
            $modelo      = trim((string) ($_POST['modelo'] ?? '')) ?: null;
            $sku         = trim((string) ($_POST['codigo_sku'] ?? '')) ?: null;
            $pesoRaw     = trim((string) ($_POST['peso_kg'] ?? ''));
            $pesoKg      = $pesoRaw !== '' ? (float) $pesoRaw : null;
            $dimensiones = trim((string) ($_POST['dimensiones'] ?? '')) ?: null;

            // `colores` exige JSON válido según el CHECK de la tabla.
            $coloresRaw = trim((string) ($_POST['colores'] ?? ''));
            if ($coloresRaw === '') {
                $colores = '[]';
            } else {
                $decodificado = json_decode($coloresRaw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $colores = $coloresRaw;
                } else {
                    // Lista separada por comas -> convertir a JSON.
                    $lista = array_values(array_filter(array_map('trim', explode(',', $coloresRaw)), static fn($x) => $x !== ''));
                    $colores = json_encode($lista, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($nombre === '' || $categoria === '') {
                $this->flash('El nombre y la categoría son obligatorios.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            if ($precio < 0) {
                $this->flash('El precio debe ser un número mayor o igual a 0.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            if ($stock < 0 || $stockMinimo < 0) {
                $this->flash('El stock y el stock mínimo no pueden ser negativos.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            // El formulario envía el slug de la categoría; la tabla `productos`
            // guarda el nombre visible (como las semillas y la tienda).
            $stmtCat = $pdo->prepare('SELECT nombre FROM categorias WHERE slug = ?');
            $stmtCat->execute([$categoria]);
            $categoria = (string) ($stmtCat->fetchColumn() ?: $categoria);

            // (Etapa 6) Quién es dueño de la publicación. Un vendedor del
            // panel solo puede publicar a su propio nombre; un admin elige.
            $vendedorId = Admin::esVendedor()
                ? Admin::idSesion()
                : (($_POST['vendedor_id'] ?? '') !== '' ? (int) $_POST['vendedor_id'] : null);

            $slugId = $id !== '' ? $id : $this->slugify($nombre);
            if ($id !== '' && !\Model\Producto::puedeGestionar($slugId)) {
                $this->flash('No tienes permiso sobre ese producto.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }
            if ($id === '') {
                $qSlug = $pdo->prepare('SELECT id FROM productos WHERE slug = ? LIMIT 1');
                $qSlug->execute([$slugId]);
                if ($qSlug->fetch()) {
                    $slugId = $slugId . '-' . substr(md5((string) microtime(true)), 0, 6);
                }
            }

            // El SKU es único (uq_productos_sku): avisar en el panel en lugar
            // de dejar que MySQL devuelva un error 500.
            if ($sku !== null) {
                $qSku = $pdo->prepare('SELECT slug FROM productos WHERE codigo_sku = ? AND slug <> ? LIMIT 1');
                $qSku->execute([$sku, $slugId]);
                if ($qSku->fetch()) {
                    $this->flash('El SKU "' . $sku . '" ya está en uso por otro producto.', true);
                    header('Location: ?ruta=admin/productos');
                    exit;
                }
            }

            if ($id !== '') {
                $pdo->prepare(
                    'UPDATE productos SET categoria = ?, nombre = ?, modelo = ?, codigo_sku = ?, marca_id = ?, garantia_id = ?, '
                    . 'precio_pen = ?, stock = ?, stock_minimo = ?, activo = ?, imagen = ?, especificaciones = ?, '
                    . 'peso_kg = ?, dimensiones = ?, colores = ?, vendedor_id = ? WHERE slug = ?'
                )->execute([
                    $categoria, $nombre, $modelo, $sku, $marcaId, $garantiaId,
                    $precio, $stock, $stockMinimo, $activo, $imagen, $especificaciones,
                    $pesoKg, $dimensiones, $colores, $vendedorId, $id,
                ]);
                $this->auditar($admin, 'productos/editar', 0, 'Editó el producto "' . $nombre . '"');
            } else {
                $pdo->prepare(
                    'INSERT INTO productos (slug, categoria, nombre, modelo, codigo_sku, marca_id, garantia_id, '
                    . 'precio_pen, stock, stock_minimo, activo, imagen, especificaciones, peso_kg, dimensiones, colores, vendedor_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $slugId, $categoria, $nombre, $modelo, $sku, $marcaId, $garantiaId,
                    $precio, $stock, $stockMinimo, $activo, $imagen, $especificaciones,
                    $pesoKg, $dimensiones, $colores, $vendedorId,
                ]);
                $this->auditar($admin, 'productos/crear', 0, 'Creó el producto "' . $nombre . '"');
            }
            header('Location: ?ruta=admin/productos');
            exit;
        }

        if ($subRuta === 'productos/estado') {
            $slug = (string) ($_GET['id'] ?? '');
            if ($slug !== '') {
                if (!\Model\Producto::puedeGestionar($slug)) {
                    $this->flash('No tienes permiso sobre ese producto.', true);
                    header('Location: ?ruta=admin/productos');
                    exit;
                }
                $pdo->prepare('UPDATE productos SET activo = 1 - activo WHERE slug = ?')->execute([$slug]);
                $this->auditar($admin, 'productos/estado', 0, 'Cambió el estado del producto "' . $slug . '"');
            }
            header('Location: ?ruta=admin/productos');
            exit;
        }

        // El formulario de edición (?editar=SLUG) no debe abrirse sobre un
        // producto ajeno: además del guard de escritura, se corta la lectura.
        $editar = (string) ($_GET['editar'] ?? '');
        if ($editar !== '' && !\Model\Producto::puedeGestionar($editar)) {
            $this->flash('No tienes permiso sobre ese producto.', true);
            header('Location: ?ruta=admin/productos');
            exit;
        }

        [$alcance, $params] = \Model\Producto::alcancePanel();
        $palabra = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT p.*, m.nombre AS marca_nombre, g.nombre AS garantia_nombre,
                       v.name AS vendedor_nombre, v.verificado AS vendedor_verificado
                FROM productos p
                LEFT JOIN marcas m ON m.id = p.marca_id
                LEFT JOIN garantias g ON g.id = p.garantia_id
                LEFT JOIN users v ON v.id = p.vendedor_id AND v.deleted_at IS NULL
               WHERE 1=1 ' . $alcance;
        if ($palabra !== '') {
            $sql .= ' AND p.nombre LIKE ?';
            $params[] = '%' . $palabra . '%';
        }
        $sql .= ' ORDER BY p.nombre ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $this->vista('productos', [
            'productos'  => $stmt->fetchAll(),
            'palabra'    => $palabra,
            'categorias' => $pdo->query('SELECT slug, nombre FROM categorias ORDER BY nombre')->fetchAll(),
            'marcas'     => $pdo->query('SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            'garantias'  => $pdo->query('SELECT id, nombre FROM garantias WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            'vendedores' => \Model\Producto::vendedores(),
        ]);
    }

    private function itemsFormProducto(array $admin): void
    {
        $pdo = conexion();
        $this->vista('productos/form', [
            'producto'   => null,
            'categorias' => $pdo->query('SELECT slug, nombre FROM categorias ORDER BY nombre')->fetchAll(),
            'marcas'     => $pdo->query('SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Categorías
    // ------------------------------------------------------------ //

    private function categorias(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'categorias/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int) ($_POST['id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $desc   = trim((string) ($_POST['descripcion'] ?? ''));

            if ($id > 0) {
                $pdo->prepare('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?')->execute([$nombre, $desc, $id]);
                $this->auditar($admin, 'categorias/editar', $id, 'Editó la categoría "' . $nombre . '"');
            } else {
                $pdo->prepare('INSERT INTO categorias (nombre, slug, descripcion) VALUES (?, ?, ?)')
                    ->execute([$nombre, $this->slugify($nombre), $desc]);
                $this->auditar($admin, 'categorias/crear', (int) $pdo->lastInsertId(), 'Creó la categoría "' . $nombre . '"');
            }
            header('Location: ?ruta=admin/categorias');
            exit;
        }

        if ($subRuta === 'categorias/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'categorias/eliminar', $id, 'Eliminó la categoría #' . $id);
            } catch (\PDOException) {
                $this->flash('No se pudo eliminar: tiene productos asociados.', true);
            }
            header('Location: ?ruta=admin/categorias');
            exit;
        }

        $this->vista('categorias', [
            'categorias' => $pdo->query('SELECT id, nombre, slug, descripcion FROM categorias ORDER BY nombre')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Marcas
    // ------------------------------------------------------------ //

    private function marcas(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'marcas/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int) ($_POST['id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($id > 0) {
                $pdo->prepare('UPDATE marcas SET nombre = ?, activo = ? WHERE id = ?')->execute([$nombre, $activo, $id]);
                $this->auditar($admin, 'marcas/editar', $id, 'Editó la marca "' . $nombre . '"');
            } else {
                $pdo->prepare('INSERT INTO marcas (nombre, slug, activo) VALUES (?, ?, ?)')
                    ->execute([$nombre, $this->slugify($nombre), $activo]);
                $this->auditar($admin, 'marcas/crear', (int) $pdo->lastInsertId(), 'Creó la marca "' . $nombre . '"');
            }
            header('Location: ?ruta=admin/marcas');
            exit;
        }

        if ($subRuta === 'marcas/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM marcas WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'marcas/eliminar', $id, 'Eliminó la marca #' . $id);
            } catch (\PDOException) {
                $this->flash('No se pudo eliminar: tiene productos asociados.', true);
            }
            header('Location: ?ruta=admin/marcas');
            exit;
        }

        $this->vista('marcas', [
            'marcas' => $pdo->query('SELECT id, nombre, slug, activo FROM marcas ORDER BY nombre')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Garantías (catálogo)
    // ------------------------------------------------------------ //

    private function garantias(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'garantias/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id        = (int) ($_POST['id'] ?? 0);
            $nombre    = trim((string) ($_POST['nombre'] ?? ''));
            $duracion  = (int) ($_POST['duracion_meses'] ?? 6);
            $cobertura = trim((string) ($_POST['cobertura'] ?? ''));
            $precio    = (float) ($_POST['precio_pen'] ?? 0);
            $activo    = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '') {
                $this->flash('El nombre de la garantía es obligatorio.', true);
                header('Location: ?ruta=admin/garantias');
                exit;
            }

            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE garantias SET nombre = ?, duracion_meses = ?, cobertura = ?, precio_pen = ?, activo = ? WHERE id = ?')
                        ->execute([$nombre, $duracion, $cobertura, $precio, $activo, $id]);
                    $this->auditar($admin, 'garantias/editar', $id, 'Editó la garantía "' . $nombre . '"');
                } else {
                    $pdo->prepare('INSERT INTO garantias (nombre, duracion_meses, cobertura, precio_pen, activo) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$nombre, $duracion, $cobertura, $precio, $activo]);
                    $this->auditar($admin, 'garantias/crear', (int) $pdo->lastInsertId(), 'Creó la garantía "' . $nombre . '"');
                }
            } catch (\PDOException) {
                $this->flash('Ya existe una garantía con ese nombre.', true);
            }
            header('Location: ?ruta=admin/garantias');
            exit;
        }

        if ($subRuta === 'garantias/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('UPDATE garantias SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'garantias/estado', $id, 'Cambió el estado de la garantía #' . $id);
            header('Location: ?ruta=admin/garantias');
            exit;
        }

        if ($subRuta === 'garantias/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM garantias WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'garantias/eliminar', $id, 'Eliminó la garantía #' . $id);
            } catch (\PDOException) {
                $this->flash('No se puede eliminar: hay productos que la usan.', true);
            }
            header('Location: ?ruta=admin/garantias');
            exit;
        }

        $this->vista('garantias', [
            'garantias' => $pdo->query(
                'SELECT g.*, (SELECT COUNT(*) FROM productos p WHERE p.garantia_id = g.id) AS usos
                 FROM garantias g ORDER BY g.nombre'
            )->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Cupones
    // ------------------------------------------------------------ //

    private function cupones(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'cupones/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id       = (int) ($_POST['id'] ?? 0);
            $codigo   = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
            $tipo     = (string) ($_POST['tipo'] ?? 'porcentaje');
            $valor    = (float) ($_POST['valor'] ?? 0);
            $fInicio  = trim((string) ($_POST['fecha_inicio'] ?? '')) ?: null;
            $fFin     = trim((string) ($_POST['fecha_fin'] ?? '')) ?: null;
            $usosMax  = ($_POST['usos_maximos'] ?? '') !== '' ? (int) $_POST['usos_maximos'] : null;
            $activo   = isset($_POST['activo']) ? 1 : 0;

            if ($codigo === '') {
                $this->flash('El código del cupón es obligatorio.', true);
                header('Location: ?ruta=admin/cupones');
                exit;
            }

            if ($id > 0) {
                $pdo->prepare(
                    'UPDATE cupones SET codigo = ?, tipo = ?, valor = ?, fecha_inicio = ?, fecha_fin = ?, usos_maximos = ?, activo = ? WHERE id = ?'
                )->execute([$codigo, $tipo, $valor, $fInicio, $fFin, $usosMax, $activo, $id]);
                $this->auditar($admin, 'cupones/editar', $id, 'Editó el cupón "' . $codigo . '"');
            } else {
                $pdo->prepare(
                    'INSERT INTO cupones (codigo, tipo, valor, fecha_inicio, fecha_fin, usos_maximos, activo) VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([$codigo, $tipo, $valor, $fInicio, $fFin, $usosMax, $activo]);
                $this->auditar($admin, 'cupones/crear', (int) $pdo->lastInsertId(), 'Creó el cupón "' . $codigo . '"');
            }
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        if ($subRuta === 'cupones/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('UPDATE cupones SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'cupones/estado', $id, 'Cambió el estado del cupón #' . $id);
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        if ($subRuta === 'cupones/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('DELETE FROM cupones WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'cupones/eliminar', $id, 'Eliminó el cupón #' . $id);
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        $this->vista('cupones', [
            'cupones' => $pdo->query('SELECT id, codigo, tipo, valor, fecha_inicio, fecha_fin, usos_maximos, usos_actuales, activo FROM cupones ORDER BY id DESC')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Envíos (tarifas)
    // ------------------------------------------------------------ //

    private function envios(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'envios/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id        = (int) ($_POST['id'] ?? 0);
            $zona      = trim((string) ($_POST['zona'] ?? ''));
            $costo     = (float) ($_POST['costo'] ?? 0);
            $dias      = (int) ($_POST['dias_estimados'] ?? 0);
            $activo    = isset($_POST['activo']) ? 1 : 0;

            if ($zona === '') {
                $this->flash('La zona es obligatoria.', true);
                header('Location: ?ruta=admin/envios');
                exit;
            }

            if ($id > 0) {
                $pdo->prepare('UPDATE tarifas_envio SET zona = ?, costo = ?, dias_estimados = ?, activo = ? WHERE id = ?')
                    ->execute([$zona, $costo, $dias, $activo, $id]);
                $this->auditar($admin, 'envios/editar', $id, 'Editó la tarifa de envío "' . $zona . '"');
            } else {
                $pdo->prepare('INSERT INTO tarifas_envio (zona, costo, dias_estimados, activo) VALUES (?, ?, ?, ?)')
                    ->execute([$zona, $costo, $dias, $activo]);
                $this->auditar($admin, 'envios/crear', (int) $pdo->lastInsertId(), 'Creó la tarifa de envío "' . $zona . '"');
            }
            header('Location: ?ruta=admin/envios');
            exit;
        }

        if ($subRuta === 'envios/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('UPDATE tarifas_envio SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'envios/estado', $id, 'Cambió el estado de la tarifa #' . $id);
            header('Location: ?ruta=admin/envios');
            exit;
        }

        if ($subRuta === 'envios/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('DELETE FROM tarifas_envio WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'envios/eliminar', $id, 'Eliminó la tarifa #' . $id);
            header('Location: ?ruta=admin/envios');
            exit;
        }

        $this->vista('envios', [
            'envios' => $pdo->query('SELECT * FROM tarifas_envio ORDER BY zona')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Configuración
    // ------------------------------------------------------------ //

    private function configuracion(): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['nombre_tienda', 'telefono', 'email', 'direccion', 'igv_porcentaje',
                      'comision_marketplace_porcentaje'] as $clave) {
                if (!array_key_exists($clave, $_POST)) {
                    continue;
                }
                $valor = (string) ($_POST[$clave] ?? '');
                $pdo->prepare(
                    'INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
                )->execute([$clave, $valor]);
            }
            $this->auditar($admin, 'configuracion/guardar', 0, 'Actualizó la configuración de la tienda');
            header('Location: ?ruta=admin/configuracion');
            exit;
        }

        $config = [];
        foreach ($pdo->query('SELECT clave, valor FROM configuracion')->fetchAll() as $fila) {
            $config[$fila['clave']] = $fila['valor'];
        }
        $this->vista('configuracion', ['config' => $config]);
    }

    // ------------------------------------------------------------ //
    //  Pedidos
    // ------------------------------------------------------------ //

    private function pedidos(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'pedidos/estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int) ($_POST['id'] ?? 0);
            $estado = (string) ($_POST['estado'] ?? '');

            $qPedido = $pdo->prepare('SELECT estado FROM pedidos WHERE id = ?');
            $qPedido->execute([$id]);
            $actual = (string) $qPedido->fetchColumn();

            if ($actual === '' || !\Model\Pedido::transicionPermitida($actual, $estado)) {
                $this->flash('Transición de estado no permitida: "' . $actual . '" → "' . $estado . '".', true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')->execute([$estado, $id]);

                // (Etapa 8) Cancelar devuelve las unidades al inventario y lo
                // registra como movimiento de entrada. `devolver()` es
                // idempotente: un doble clic no duplica stock.
                if ($estado === 'Cancelado') {
                    \Model\Inventario::devolver($pdo, $id);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                $this->flash('No se pudo actualizar el pedido: ' . $e->getMessage(), true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $this->auditar($admin, 'pedidos/estado', $id, 'Cambió el estado del pedido #' . $id . ' a "' . $estado . '"');
            header('Location: ?ruta=admin/pedidos');
            exit;
        }

        // (Etapa 6) Un vendedor solo ve los pedidos que llevan sus líneas y,
        // dentro de ellos, solo sus propias líneas.
        $alcance = \Model\Vendedor::alcancePanelPedidos();

        $adminFilter = (string) ($_GET['estado'] ?? '');
        $sql = 'SELECT ' . ($alcance[0] !== '' ? 'DISTINCT ' : '')
             . 'p.*, c.name AS nombre, c.apellidos
                FROM pedidos p
                JOIN users c ON c.id = p.cliente_id'
             . $alcance[0]
             . ' WHERE ' . $alcance[1];
        $params = $alcance[3];
        if ($adminFilter !== '') {
            $sql .= ' AND p.estado = ?';
            $params[] = $adminFilter;
        }
        $sql .= ' ORDER BY p.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll();

        $sqlDet = 'SELECT dp.*, pd.nombre AS producto_nombre
                   FROM detalle_pedido dp
                   JOIN productos pd ON pd.id = dp.producto_id
                 WHERE ' . $alcance[2];
        $stmtDet = $pdo->prepare($sqlDet);
        $stmtDet->execute($alcance[3]);
        $detalles = $stmtDet->fetchAll();

        $mapa = [];
        foreach ($detalles as $d) {
            $mapa[(int) $d['pedido_id']][] = $d;
        }

        $this->vista('pedidos', ['pedidos' => $pedidos, 'detalles' => $mapa]);
    }

    // ------------------------------------------------------------ //
    //  Usuarios (identidad unificada en `users` — Etapa 4)
    // ------------------------------------------------------------ //

    /** Roles que puede asignar el panel. */
    private const ROLES = ['admin', 'vendedor', 'cliente'];

    /** true si $id es el último administrador activo que queda. */
    private function esUltimoAdminActivo(\PDO $pdo, int $id): bool
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM users
              WHERE id <> ? AND rol = 'admin' AND activo = 1 AND deleted_at IS NULL"
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() === 0;
    }

    /** @return array<string, mixed>|null */
    private function usuarioPorId(\PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    private function usuarios(string $subRuta): void
    {
        $pdo   = conexion();
        $admin = Admin::usuarioActual();
        $yoId  = (int) ($admin['id'] ?? 0);

        if ($subRuta === 'usuarios/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            if ($id > 0 && $id !== $yoId) {
                $fila = $this->usuarioPorId($pdo, $id);
                if ($fila === null) {
                    $this->flash('El usuario ya no existe.', true);
                } elseif ((int) $fila['activo'] === 1
                    && ($fila['rol'] ?? '') === 'admin'
                    && $this->esUltimoAdminActivo($pdo, $id)) {
                    $this->flash('No puedes desactivar al único administrador activo.', true);
                } else {
                    $pdo->prepare('UPDATE users SET activo = IF(activo = 1, 0, 1) WHERE id = ?')
                        ->execute([$id]);
                    $this->auditar($admin, 'usuarios/estado', $id, 'Cambió el estado del usuario #' . $id);
                    $this->flash('Estado actualizado.');
                }
            } elseif ($id === $yoId && $id > 0) {
                $this->flash('No puedes desactivar tu propia cuenta.', true);
            }
            header('Location: ?ruta=admin/usuarios');
            exit;
        }

        if ($subRuta === 'usuarios/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            if ($id > 0 && $id !== $yoId) {
                $fila = $this->usuarioPorId($pdo, $id);
                if ($fila === null) {
                    $this->flash('El usuario ya no existe.', true);
                } elseif (($fila['rol'] ?? '') === 'admin' && $this->esUltimoAdminActivo($pdo, $id)) {
                    $this->flash('No puedes eliminar al único administrador activo.', true);
                } else {
                    $pdo->prepare('UPDATE users SET deleted_at = NOW(), activo = 0 WHERE id = ?')
                        ->execute([$id]);
                    $this->auditar($admin, 'usuarios/eliminar', $id, 'Dio de baja al usuario #' . $id);
                    $this->flash('Usuario dado de baja.');
                }
            } elseif ($id === $yoId && $id > 0) {
                $this->flash('No puedes dar de baja tu propia cuenta.', true);
            }
            header('Location: ?ruta=admin/usuarios');
            exit;
        }

        if ($subRuta === 'usuarios/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id       = (int) ($_POST['id'] ?? 0);
            $nombre   = trim((string) ($_POST['nombre'] ?? ''));
            $apellidos = trim((string) ($_POST['apellidos'] ?? ''));
            $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
            $usuario  = trim((string) ($_POST['usuario'] ?? ''));
            $clave    = (string) ($_POST['clave'] ?? '');
            $rol      = (string) ($_POST['rol'] ?? 'cliente');
            $activo   = isset($_POST['activo']) ? 1 : 0;
            $esEdicion = $id > 0;

            // La propia sesión no se puede degradar ni auto-desactivar.
            if ($esEdicion && $id === $yoId) {
                $rol    = 'admin';
                $activo = 1;
            }

            $error = null;
            if (!in_array($rol, self::ROLES, true)) {
                $error = 'Rol no válido.';
            } elseif ($nombre === '') {
                $error = 'El nombre es obligatorio.';
            } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ingresa un correo electrónico válido.';
            } elseif ($rol !== 'cliente' && $usuario === '') {
                $error = 'El nombre de usuario es obligatorio para administradores y vendedores.';
            } elseif (!$esEdicion && strlen($clave) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif ($esEdicion && $clave !== '' && strlen($clave) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } else {
                $error = $this->ultimoErrorFlash($pdo, $id, $email, $usuario);
            }

            if ($error === null) {
                if ($esEdicion) {
                    $fila = $this->usuarioPorId($pdo, $id);
                    if ($fila === null) {
                        $this->flash('El usuario ya no existe.', true);
                        header('Location: ?ruta=admin/usuarios');
                        exit;
                    }
                    if (($fila['rol'] ?? '') === 'admin' && $rol !== 'admin' && $this->esUltimoAdminActivo($pdo, $id)) {
                        $this->flash('No puedes degradar al único administrador activo.', true);
                        header('Location: ?ruta=admin/usuarios');
                        exit;
                    }

                    if ($clave !== '') {
                        $pdo->prepare('UPDATE users SET name = ?, apellidos = ?, email = ?, usuario = ?, password = ?, rol = ?, activo = ? WHERE id = ?')
                            ->execute([$nombre, $apellidos, $email, $usuario !== '' ? $usuario : null, password_hash($clave, PASSWORD_DEFAULT), $rol, $activo, $id]);
                    } else {
                        $pdo->prepare('UPDATE users SET name = ?, apellidos = ?, email = ?, usuario = ?, rol = ?, activo = ? WHERE id = ?')
                            ->execute([$nombre, $apellidos, $email, $usuario !== '' ? $usuario : null, $rol, $activo, $id]);
                    }
                    $this->auditar($admin, 'usuarios/editar', $id, 'Editó al usuario "' . $nombre . '"');
                    $this->flash('Usuario actualizado.');
                } else {
                    $pdo->prepare('INSERT INTO users (name, apellidos, email, usuario, password, rol, activo)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)')
                        ->execute([
                            $nombre,
                            $apellidos,
                            $email,
                            $usuario !== '' ? $usuario : null,
                            password_hash($clave, PASSWORD_DEFAULT),
                            $rol,
                            $activo,
                        ]);
                    $this->auditar($admin, 'usuarios/crear', (int) $pdo->lastInsertId(), 'Creó al usuario "' . $nombre . '"');
                    $this->flash('Usuario creado.');
                }
            } else {
                $this->flash($error, true);
            }

            header('Location: ?ruta=admin/usuarios');
            exit;
        }

        $this->vista('usuarios', [
            'usuarios' => $pdo->query(
                'SELECT id, name, apellidos, email, usuario, rol, activo, ultimo_ingreso, created_at
                 FROM users WHERE deleted_at IS NULL
                 ORDER BY FIELD(rol, "admin", "vendedor", "cliente"), id'
            )->fetchAll(),
        ]);
    }

    /**
     * Devuelve el mensaje de error de unicidad (email / usuario) o null.
     */
    private function ultimoErrorFlash(\PDO $pdo, int $id, string $email, string $usuario): ?string
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            return 'Ya existe una cuenta con ese correo.';
        }

        if ($usuario !== '') {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE usuario = ? AND id <> ? AND deleted_at IS NULL LIMIT 1');
            $stmt->execute([$usuario, $id]);
            if ($stmt->fetch()) {
                return 'Ese nombre de usuario ya está en uso.';
            }
        }

        return null;
    }

    // ------------------------------------------------------------ //
    //  Auditoría
    // ------------------------------------------------------------ //

    // ------------------------------------------------------------ //
    //  Vendedores (marketplace — Etapa 6)
    // ------------------------------------------------------------ //

    /**
     * Gestión de las fichas comerciales de los usuarios con rol
     * `vendedor`: porcentaje de comisión, verificación, actividad y
     * liquidaciones al saldo pendiente. El alta/rol sigue en Usuarios.
     */
    private function vendedores(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'vendedores/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id        = (int) ($_POST['id'] ?? 0);
            $porcentaje = trim((string) ($_POST['comision_porcentaje'] ?? ''));
            $verificado = isset($_POST['verificado']) ? 1 : 0;
            $activo     = isset($_POST['activo']) ? 1 : 0;

            if ($id <= 0 || \Model\Vendedor::porId($id) === null) {
                $this->flash('Ese vendedor ya no existe.', true);
                header('Location: ?ruta=admin/vendedores');
                exit;
            }

            if ($porcentaje === '' || !is_numeric($porcentaje) || (float) $porcentaje < 0 || (float) $porcentaje > 100) {
                $this->flash('La comisión debe ser un porcentaje entre 0 y 100.', true);
                header('Location: ?ruta=admin/vendedores');
                exit;
            }

            $pdo->prepare(
                'UPDATE users SET comision_porcentaje = ?, verificado = ?, activo = ?,
                        updated_at = NOW() WHERE id = ?'
            )->execute([round((float) $porcentaje, 2), $verificado, $activo, $id]);

            $this->auditar(
                $admin,
                'vendedores/editar',
                $id,
                'Actualizó la ficha del vendedor #' . $id . ' (comisión ' . round((float) $porcentaje, 2) . '%)'
            );
            $this->flash('Ficha del vendedor actualizada.');
            header('Location: ?ruta=admin/vendedores');
            exit;
        }

        if ($subRuta === 'vendedores/liquidar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id       = (int) ($_POST['id'] ?? 0);
            $monto    = is_numeric($_POST['monto'] ?? null) ? (float) $_POST['monto'] : 0.0;
            $metodo   = trim((string) ($_POST['metodo'] ?? ''));
            $referen  = trim((string) ($_POST['referencia'] ?? ''));

            $error = \Model\Vendedor::liquidar($id, $monto, $metodo, $referen);
            if ($error !== null) {
                $this->flash($error, true);
            } else {
                $this->auditar(
                    $admin,
                    'vendedores/liquidar',
                    $id,
                    'Liquidó S/ ' . number_format($monto, 2) . ' al vendedor #' . $id
                );
                $this->flash('Liquidación registrada.');
            }
            header('Location: ?ruta=admin/vendedores');
            exit;
        }

        $this->vista('vendedores', [
            'vendedores' => \Model\Vendedor::todos(),
        ]);
    }

    /**
     * Listado global de comisiones: una fila por pedido y vendedor con el
     * reparto congelado al momento de la compra (solo administrador).
     */
    private function comisiones(string $subRuta): void
    {
        $pdo = conexion();

        $filtroVendedor = (int) ($_GET['vendedor'] ?? 0);
        $sql = 'SELECT c.*, p.estado, p.creado_en AS pedido_fecha,
                       u.name AS vendedor_nombre, u.usuario AS vendedor_usuario
                  FROM comisiones c
                  JOIN pedidos p ON p.id = c.pedido_id
                  JOIN users u ON u.id = c.vendedor_id
                 WHERE 1=1';
        $params = [];
        if ($filtroVendedor > 0) {
            $sql .= ' AND c.vendedor_id = ?';
            $params[] = $filtroVendedor;
        }
        $sql .= ' ORDER BY c.id DESC LIMIT 300';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $this->vista('comisiones', [
            'comisiones' => $stmt->fetchAll(),
            'vendedores' => \Model\Vendedor::todos(),
            'filtroVendedor' => $filtroVendedor,
        ]);
    }

    /**
     * Ventas propias del vendedor que tiene la sesión abierta: una fila por
     * pedido y vendedor, con el reparto congelado al momento de la compra.
     */
    private function ventas(string $subRuta): void
    {
        $vendedorId = Admin::esVendedor() ? Admin::idSesion() : null;
        if ($vendedorId === null) {
            header('Location: ?ruta=admin/dashboard');
            exit;
        }

        $this->vista('ventas', [
            'ventas'       => \Model\Vendedor::ventasDe($vendedorId),
            'liquidaciones' => \Model\Vendedor::liquidacionesDe($vendedorId),
            'ganancias'    => \Model\Vendedor::gananciasDe($vendedorId),
            'liquidado'    => \Model\Vendedor::liquidadoDe($vendedorId),
            'saldo'        => \Model\Vendedor::saldoPendiente($vendedorId),
        ]);
    }

    private function auditoria(): void
    {
        $pdo = conexion();
        $this->vista('auditoria', [
            'registros' => $pdo->query('SELECT * FROM auditoria_admin ORDER BY id DESC LIMIT 250')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Variantes y precios por volumen (Etapa 7)
    // ------------------------------------------------------------ //

    /**
     * Gestiona `producto_variantes` y `precios_volumen` de un producto.
     *
     * Todo pasa por el guard del rol (`Producto::puedeGestionar`): un
     * vendedor solo puede tocar las variantes de sus propios productos.
     */
    private function variantes(string $subRuta): void
    {
        $pdo   = conexion();
        $admin = Admin::usuarioActual();

        // El producto activo de la sección viene por URL (?p=SLUG) y se
        // reutiliza para volver al listado tras cada mutación.
        $slug = trim((string) ($_GET['p'] ?? $_POST['p'] ?? ''));
        $volver = '?ruta=admin/variantes' . ($slug !== '' ? '&p=' . urlencode($slug) : '');

        /** @return array<string, mixed>|null producto si el rol puede gestionarlo */
        $productoPorSlug = static function (string $slug) use ($pdo): ?array {
            if ($slug === '' || !\Model\Producto::puedeGestionar($slug)) {
                return null;
            }
            $q = $pdo->prepare('SELECT * FROM productos WHERE slug = ? LIMIT 1');
            $q->execute([$slug]);
            return $q->fetch() ?: null;
        };

        if ($subRuta === 'variantes/variantes/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto = $productoPorSlug($slug);
            if ($producto === null) {
                $this->flash('No tienes permiso sobre ese producto.', true);
                header('Location: ' . $volver);
                exit;
            }

            $id     = (int) ($_POST['id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre_variante'] ?? ''));
            $extra  = (float) ($_POST['precio_extra'] ?? 0);
            $stock  = (int) ($_POST['stock'] ?? 0);
            $color  = trim((string) ($_POST['color'] ?? ''));
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '') {
                $this->flash('El nombre de la variante es obligatorio.', true);
                header('Location: ' . $volver);
                exit;
            }
            // Una variante que no se puede comprar no debe aparecer en la ficha.
            $stock = max(0, $stock);

            try {
                if ($id > 0) {
                    $actual = \Model\ProductoVariante::porId($id);
                    if ($actual === null || (int) $actual['productoId'] !== (int) $producto['id']) {
                        $this->flash('Esa variante no pertenece a este producto.', true);
                        header('Location: ' . $volver);
                        exit;
                    }
                    $pdo->prepare(
                        'UPDATE producto_variantes
                            SET nombre_variante = ?, precio_extra = ?, stock = ?, color = ?, activo = ?
                          WHERE id = ?'
                    )->execute([$nombre, $extra, $stock, $color, $activo, $id]);
                    $this->auditar($admin, 'variantes/editar', $id, 'Editó la variante "' . $nombre . '"');
                } else {
                    if (\Model\ProductoVariante::existe((int) $producto['id'], $nombre)) {
                        $this->flash('Ya existe una variante con ese nombre en este producto.', true);
                        header('Location: ' . $volver);
                        exit;
                    }
                    $pdo->prepare(
                        'INSERT INTO producto_variantes
                            (producto_id, nombre_variante, precio_extra, stock, color, activo)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    )->execute([(int) $producto['id'], $nombre, $extra, $stock, $color, $activo]);
                    $this->auditar($admin, 'variantes/crear', (int) $pdo->lastInsertId(),
                        'Creó la variante "' . $nombre . '"');
                }
            } catch (\PDOException) {
                $this->flash('No se pudo guardar la variante (¿nombre duplicado?).', true);
            }
            header('Location: ' . $volver);
            exit;
        }

        if ($subRuta === 'variantes/variantes/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            $q = $pdo->prepare(
                'SELECT pv.id FROM producto_variantes pv
                   JOIN productos p ON p.id = pv.producto_id
                  WHERE pv.id = ? AND p.slug = ? LIMIT 1'
            );
            $q->execute([$id, $slug]);
            if ($q->fetch() && \Model\Producto::puedeGestionar($slug)) {
                $pdo->prepare('UPDATE producto_variantes SET activo = 1 - activo WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'variantes/estado', $id, 'Cambió el estado de la variante #' . $id);
            }
            header('Location: ' . $volver);
            exit;
        }

        if ($subRuta === 'variantes/variantes/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            $q = $pdo->prepare(
                'SELECT pv.id FROM producto_variantes pv
                   JOIN productos p ON p.id = pv.producto_id
                  WHERE pv.id = ? AND p.slug = ? LIMIT 1'
            );
            $q->execute([$id, $slug]);
            if ($q->fetch() && \Model\Producto::puedeGestionar($slug)) {
                try {
                    $pdo->prepare('DELETE FROM producto_variantes WHERE id = ?')->execute([$id]);
                    $this->auditar($admin, 'variantes/eliminar', $id, 'Eliminó la variante #' . $id);
                } catch (\PDOException) {
                    $this->flash('No se pudo eliminar la variante.', true);
                }
            }
            header('Location: ' . $volver);
            exit;
        }

        if ($subRuta === 'variantes/volumen/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $producto = $productoPorSlug($slug);
            if ($producto === null) {
                $this->flash('No tienes permiso sobre ese producto.', true);
                header('Location: ' . $volver);
                exit;
            }

            $id      = (int) ($_POST['id'] ?? 0);
            $cmin    = (int) ($_POST['cantidad_minima'] ?? 0);
            $cmaxRaw = trim((string) ($_POST['cantidad_maxima'] ?? ''));
            $cmax    = $cmaxRaw !== '' ? (int) $cmaxRaw : null;
            $precio  = (float) ($_POST['precio_unitario'] ?? -1);

            if ($cmin < 1 || $precio < 0) {
                $this->flash('Revisa la cantidad mínima y el precio del tramo.', true);
                header('Location: ' . $volver);
                exit;
            }
            // Tramo invertido (ej. 5..2): no describe ningún rango.
            if ($cmax !== null && $cmax < $cmin) {
                $this->flash('La cantidad máxima no puede ser menor que la mínima.', true);
                header('Location: ' . $volver);
                exit;
            }

            // Solo un tramo abierto por producto: si no, el precio dependería
            // del orden en que se consulte. Es el mismo guard que hace la BD.
            if ($cmax === null) {
                $qAbierto = $pdo->prepare(
                    'SELECT id FROM precios_volumen WHERE producto_id = ? AND cantidad_maxima IS NULL AND id <> ? LIMIT 1'
                );
                $qAbierto->execute([(int) $producto['id'], $id]);
                if ($qAbierto->fetch()) {
                    $this->flash('Este producto ya tiene un tramo abierto ("desde N en adelante").', true);
                    header('Location: ' . $volver);
                    exit;
                }
            }

            try {
                if ($id > 0) {
                    $actual = \Model\PrecioVolumen::porId($id);
                    if ($actual === null || (int) $actual['productoId'] !== (int) $producto['id']) {
                        $this->flash('Ese tramo no pertenece a este producto.', true);
                        header('Location: ' . $volver);
                        exit;
                    }
                    $pdo->prepare(
                        'UPDATE precios_volumen
                            SET cantidad_minima = ?, cantidad_maxima = ?, precio_unitario = ?
                          WHERE id = ?'
                    )->execute([$cmin, $cmax, $precio, $id]);
                    $this->auditar($admin, 'volumen/editar', $id,
                        'Editó el tramo de volumen #' . $id . ' (' . $cmin . '..' . ($cmax ?? '∞') . ')');
                } else {
                    $pdo->prepare(
                        'INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
                         VALUES (?, ?, ?, ?)'
                    )->execute([(int) $producto['id'], $cmin, $cmax, $precio]);
                    $this->auditar($admin, 'volumen/crear', (int) $pdo->lastInsertId(),
                        'Creó un tramo de volumen (' . $cmin . '..' . ($cmax ?? '∞') . ')');
                }
            } catch (\PDOException) {
                $this->flash('No se pudo guardar el tramo de volumen.', true);
            }
            header('Location: ' . $volver);
            exit;
        }

        if ($subRuta === 'variantes/volumen/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            $q = $pdo->prepare(
                'SELECT pv.id FROM precios_volumen pv
                   JOIN productos p ON p.id = pv.producto_id
                  WHERE pv.id = ? AND p.slug = ? LIMIT 1'
            );
            $q->execute([$id, $slug]);
            if ($q->fetch() && \Model\Producto::puedeGestionar($slug)) {
                $pdo->prepare('DELETE FROM precios_volumen WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'volumen/eliminar', $id, 'Eliminó el tramo de volumen #' . $id);
            }
            header('Location: ' . $volver);
            exit;
        }

        $this->vista('variantes', []);
    }
}
