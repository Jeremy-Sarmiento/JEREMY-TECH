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
    /**
     * Punto de entrada del panel: recibe la sub-ruta tras `admin/`.
     * `inicio` por defecto; el resto se despacha por secciones.
     */
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

        if ($subRuta === 'inicio' || $subRuta === 'dashboard') {
            $this->dashboard();
            return;
        }

        if (str_starts_with($subRuta, 'productos')) {
            $this->productos($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'categorias')) {
            $this->categorias($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'marcas')) {
            $this->marcas($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'cupones')) {
            $this->cupones($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'envios')) {
            $this->envios($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'configuracion')) {
            $this->configuracion();
            return;
        }
        if (str_starts_with($subRuta, 'pedidos')) {
            $this->pedidos($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'clientes')) {
            $this->clientes($subRuta);
            return;
        }
        if (str_starts_with($subRuta, 'auditoria') || $subRuta === 'auditoria') {
            $this->auditoria();
            return;
        }

        $this->dashboard();
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
        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/admin/login.php';
        require BASE_PATH . '/views/layout/footer.php';
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

    // ------------------------------------------------------------ //
    //  Helpers de vista / flash
    // ------------------------------------------------------------ //

    private function renderizar(string $seccion, array $datos): void
    {
        $paginaAdmin = true;
        $seccionAdmin = $seccion;
        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/admin/panel.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    private function vista(string $seccion, array $datos): void
    {
        $this->renderizar($seccion, $datos);
    }

    private function flash(string $mensaje, bool $esError): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_flash'] = [
            'tipo'   => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
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
        $texto = strtolower(trim($texto));
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $texto
        );
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? $texto;
        return trim($texto, '-');
    }

    /** Resumen general del negocio para la portada del panel. */
    private function dashboard(): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        $totales = [
            'productos'  => (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
            'pedidos'    => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
            'pendientes' => (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn(),
            'clientes'   => (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'cupones'    => (int) $pdo->query('SELECT COUNT(*) FROM cupones')->fetchColumn(),
            'auditoria'  => (int) $pdo->query('SELECT COUNT(*) FROM auditoria_admin')->fetchColumn(),
        ];

        $ultimos = $pdo->query(
            'SELECT p.id, p.estado, p.total_pen, p.creado_en, c.nombre, c.apellidos
             FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
             ORDER BY p.id DESC LIMIT 8'
        )->fetchAll();

        $this->renderizar('dashboard', [
            'totales' => $totales,
            'ultimos' => $ultimos,
        ]);
    }

    // ------------------------------------------------------------ //
    //  PRODUCTOS
    // ------------------------------------------------------------ //

    private function productos(string $subRuta): void
    {
        $pdo = conexion();

        // Eliminar: cambia el estado a inactivo (soft) o borra si no tiene
        // referencias. Por seguridad usamos soft-delete (estado=0) siempre.
        if ($subRuta === 'productos/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('UPDATE productos SET activo = 0 WHERE id = ?')->execute([$id]);
            Admin::auditar(
                (string) (Admin::usuarioActual()['usuario'] ?? ''),
                'productos/eliminar',
                $id,
                'Ocultó el producto #' . $id
            );
            header('Location: ?ruta=admin/productos');
            exit;
        }

        // Formulario nuevo / edición.
        if ($subRuta === 'productos/crear' || str_starts_with($subRuta, 'productos/editar')) {
            $producto = null;
            $idEditar = 0;
            if (preg_match('~^productos/editar/(\d+)$~', $subRuta, $m)) {
                $idEditar = (int) $m[1];
            }
            if ($idEditar > 0) {
                $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ? LIMIT 1');
                $stmt->execute([$idEditar]);
                $producto = $stmt->fetch() ?: null;
            }
            $this->vista('productos/form', [
                'producto'   => $producto,
                'categorias' => $pdo->query('SELECT id, nombre, slug FROM categorias ORDER BY nombre')->fetchAll(),
                'marcas'     => $pdo->query('SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre')->fetchAll(),
            ]);
            return;
        }

        // Guardar (crear o actualizar).
        if ($subRuta === 'productos/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->guardarProducto();
            return;
        }

        // Cambiar estado activo (publicar/ocultar).
        if ($subRuta === 'productos/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('UPDATE productos SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            Admin::auditar(
                (string) (Admin::usuarioActual()['usuario'] ?? ''),
                'productos/estado',
                $id,
                'Cambió el estado de visibilidad del producto #' . $id
            );
            header('Location: ?ruta=admin/productos');
            exit;
        }

        // Listado con búsqueda opcional.
        $palabra = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT p.*, c.nombre AS categoria_nombre, m.nombre AS marca_nombre
                FROM productos p
                LEFT JOIN categorias c ON c.id = p.categoria
                LEFT JOIN marcas m ON m.id = p.marca_id';
        $params = [];
        if ($palabra !== '') {
            $sql .= ' WHERE p.nombre LIKE ?';
            $params[] = '%' . $palabra . '%';
        }
        $sql .= ' ORDER BY p.id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($paramsphins);

        $this->vista('productos', [
            'productos' => $stmt->fetchAll(),
            'palabra'   => $palabra,
            'categorias' => $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll(),
            'marcas'     => $pdo->query('SELECT id, nombre FROM marcas ORDER BY nombre')->fetchAll(),
        ]);
    }

    private function guardarProducto(): void
    {
        $pdo = conexion();

        $id        = (int) ($_POST['id'] ?? 0);
        $nombre    = trim((string) ($_POST['nombre'] ?? ''));
        $categoria = (int) ($_POST['categoria'] ?? 0);
        $marcaId   = ($_POST['marca_id'] ?? '') !== '' ? (int) $_POST['marca_id'] : null;
        $precio    = (float) ($_POST['precio_pen'] ?? 0);
        $stock     = (int) ($_POST['stock'] ?? 0);
        $activo    = isset($_POST['activo']) ? 1 : 0;
        $imagen    = trim((string) ($_POST['imagen'] ?? ''));

        if ($nombre === '' || $categoria <= 0) {
            $this->flash('El nombre y la categoría son obligatorios.', true);
            header('Location: ?ruta=admin/productos/crear');
            exit;
        }

        if ($id > 0) {
            $pdo->prepare(
                'UPDATE productos SET categoria = ?, nombre = ?, marca_id = ?, precio_pen = ?, stock = ?, activo = ?, imagen = ?
                 WHERE id = ?'
            )->execute([$categoria, $nombre, $marcaId, $precio, $stock, $activo, $imagen, $id]);
            $referencia = $id;
            $accion     = 'productos/editar';
        } else {
            $pdo->prepare(
                'INSERT INTO productos (categoria, nombre, marca_id, precio_pen, stock, activo, imagen)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$categoria, $nombre, $marcaId, $precio, $stock, $activo, $imagen]);
            $referencia = (int) $pdo->lastInsertId();
            $accion     = 'productos/crear';
        }

        Admin::auditar(
            (string) (Admin::usuarioActual()['usuario'] ?? ''),
            $accion,
            $referencia,
            '"' . $nombre . '"'
        );

        header('Location: ?ruta=admin/productos');
        exit;
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
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($id > 0) {
                $pdo->prepare('UPDATE categorias SET nombre = ?, activo = ? WHERE id = ?')->execute([$nombre, $activo, $id]);
                Admin::auditar((string) $admin['usuario'], 'categorias/editar', $id, 'Editó la categoría "' . $nombre . '"');
            } else {
                $pdo->prepare('INSERT INTO categorias (nombre, slug, activo) VALUES (?, ?, ?)')
                    ->execute([$nombre, $this->slugify($nombre), $activo]);
                Admin::auditar((string) $admin['usuario'], 'categorias/crear', (int) $pdo->lastInsertId(), 'Creó la categoría "' . $nombre . '"');
            }
            header('Location: ?ruta=admin/categorias');
            exit;
        }

        if ($subRuta === 'categorias/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
                Admin::auditar((string) $admin['usuario'], 'categorias/eliminar', $id, 'Eliminó la categoría #' . $id);
            } catch (\PDOException) {
                $this->flash('No se pudo eliminar: tiene productos asociados.', true);
            }
            header('Location: ?ruta=admin/categorias');
            exit;
        }

        $this->vista('categorias', [
            'categorias' => $pdo->query('SELECT * FROM categorias ORDER BY nombre')->fetchAll(),
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
                Admin::auditar((string) $admin['usuario'], 'marcas/editar', $id, 'Editó la marca "' . $nombre . '"');
            } else {
                $pdo->prepare('INSERT INTO marcas (nombre, slug, activo) VALUES (?, ?, ?)')
                    ->execute([$nombre, $this->slugify2($nombre), $activo]);
                Admin::auditar((string) $admin['usuario'], 'marcas/crear', (int) $pdo->lastInsertId(), 'Creó la marca "' . $nombre . '"');
            }
            header('Location: ?ruta=admin/marcas');
            exit;
        }

        if ($subRuta === 'marcas/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM marcas WHERE id = ?')->execute([$id]);
                Admin::auditar((string) $admin['usuario'], 'marcas/eliminar', $id, 'Eliminó la marca #' . $id);
            } catch (\PDOException) {
                $this->flash('No se pudo eliminar: tiene productos asociados.', true);
            }
            header('Location: ?ruta=admin/marcas');
            exit;
        }

        $this->vista('marcas', [
            'marcas' => $pdo->query('SELECT * FROM marcas ORDER BY nombre')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Marcas
    // ------------------------------------------------------------ //

            header('Location: ?ruta=admin/marcas');
            exit;
        }

        if ($subRuta === 'marcas/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM marcas WHERE id = ?')->execute([$id]);
                Admin::auditar((string) $admin['usuario'], 'marcas/eliminar', $id, 'Eliminó la marca #' . $id);
            } catch (\PDOException) {
                $this->flash('No se pudo eliminar: tiene marcas asociadas.', true);
            }
            header('Location: ?ruta=admin/marcas');
            exit;
        }

        // -------------------------------------------------------- //
        //  Cupones
        // -------------------------------------------------------- //

        if ($subRuta === 'cupones/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int) ($_POST['id'] ?? 0);
            $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
            $tipo   = (string) ($_POST['tipo'] ?? 'porcentaje');
            $valor  = (float) ($_POST['valor'] ?? 0);
            $minimo = (float) ($_POST['minimo'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($codigo === '') {
                $this->flash('El código del cupón es obligatorio.', true);
                header('Location: ?ruta=admin/cupones');
                exit;
            }

            if ($id > 0) {
                $pdo->prepare('UPDATE cupones SET codigo = ?, tipo = ?, valor = ?, minimo = ?, activo = ? WHERE id = ?')
                    ->execute([$codigo, $tipo, $valor, $minimo, $activo, $id]);
                Admin::auditar((string) $admin['usuario'], 'cupones/editar', $id, 'Editó el cupón "' . $codigo . '"');
            } else {
                $pdo->prepare('INSERT INTO cupones (codigo, tipo, valor, minimo, activo) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$codigo, $tipo, $valor, $minimo, $activo]);
                Admin::auditar((string) $admin['usuario'], 'cupones/crear', (int) $pdo->lastInsertId(), 'Creó el cupón "' . $codigo . '"');
            }
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        if ($subRuta === 'cupones/estado') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('UPDATE cupones SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            Admin::auditar((string) $admin['usuario'], 'cupones/estado', $id, 'Cambió el estado del cupón #' . $id);
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        if ($subRuta === 'cupones/eliminar') {
            $id = (int) ($_GET['id'] ?? 0);
            $pdo->prepare('DELETE FROM cupones WHERE id = ?')->execute([$id]);
            Admin::auditar((string) $admin['usuario'], 'cupones/eliminar', $id, 'Eliminó el cupón #' . $id);
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        $this->vista('cupones', [
            'cupones' => $pdo->query('SELECT * FROM cupones ORDER BY id DESC')->fetchAll(),
        ]);
    }

    // @@ANADIR_METODOS_5@@
}
