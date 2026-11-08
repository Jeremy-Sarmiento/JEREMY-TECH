<?php

declare(strict_types=1);

namespace Controller;

use Model\Administrador as Admin;
use Model\ChatbotFaq;
use Model\Envio;
use Model\Notificacion;
use Model\PedidoEvento;

/**
 * Panel de administración de Jeremy Tech (vía ?ruta=admin/...).
 * Sesión independiente de la del cliente: usa la clave `admin_id` en
 * $_SESSION (ver Model\Administrador) para no interrumpir una cuenta
 * de cliente activa. Cada mutación se registra con Admin::auditar().
 */
final class AdminController
{
    private const ERROR_STOCK_CAMBIADO = 'El stock cambió desde que abriste el formulario. Recarga la página y revisa las existencias antes de guardar.';

    public function atender(string $subRuta): void
    {
        $subRuta = trim($subRuta, '/');
        if ($subRuta === '') {
            $subRuta = 'inicio';
        }

        // Las acciones que modifican datos solo se aceptan por POST. El
        // token CSRF se valida de forma central en public/index.php.
        if ($subRuta === 'cerrar'
            || preg_match('#/(guardar|eliminar|estado|liquidar|autenticar|reembolsar|ajustar|responder|ocultar|mostrar|aprobar|rechazar)$#', $subRuta) === 1
        ) {
            \Security\Proteccion::exigirPost();
        }

        if ($subRuta === 'cerrar') {
            $this->cerrar();
            return;
        }

        if (!Admin::estaAutenticado()) {
            // Un solo login: la sesión del panel se abre al entrar a la
            // tienda con una cuenta admin o vendedor.
            header('Location: ?ruta=login');
            exit;
        }

        // (Etapa 6) El panel es uno y se filtra por rol: un vendedor no
        // puede ni siquiera llegar al código de las secciones de admin.
        $permitidas = Admin::seccionesPermitidas();
        if ($permitidas === []) {
            Admin::cerrarSesion();
            header('Location: ?ruta=login');
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
            'usuarios' => 'admin/usuarios', 'solicitudes' => 'admin/solicitudes',
            'configuracion' => 'admin/configuracion',
            'auditoria' => 'admin/auditoria', 'vendedores' => 'admin/vendedores',
            'ventas' => 'admin/ventas', 'comisiones' => 'admin/comisiones',
            'inventario' => 'admin/inventario', 'notificaciones' => 'admin/notificaciones',
            'chatbot' => 'admin/chatbot', 'analitica' => 'admin/analitica',
            'soporte' => 'admin/soporte', 'resenas' => 'admin/resenas',
            'pagos' => 'admin/pagos',
        ];
        foreach ($permitidas as $seccion) {
            if (isset($mapa[$seccion])) {
                return $mapa[$seccion];
            }
        }
        return 'admin/dashboard';
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
        // El panel comparte cuenta con la tienda: se cierran las dos sesiones.
        \Model\Cuenta::cerrarSesion();
        Admin::cerrarSesion();
        header('Location: ?ruta=login');
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
        \Security\Proteccion::iniciarSesion();
        $_SESSION['admin_flash'] = ['tipo' => $esError ? 'error' : 'ok', 'mensaje' => $mensaje];
    }

    private function tomarFlash(): array
    {
        \Security\Proteccion::iniciarSesion();
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

    private function stockFormulario(mixed $valor): ?int
    {
        if (!is_scalar($valor)) {
            return null;
        }
        $texto = trim((string) $valor);
        return preg_match('/^\d+$/', $texto) === 1 && (int) $texto <= 9999999 ? (int) $texto : null;
    }

    private function idOpcional(mixed $valor): int|null|false
    {
        if (!is_scalar($valor)) {
            return false;
        }
        $texto = trim((string) $valor);
        return $texto === '' ? null : filter_var($texto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    }

    /** Bloquea el producto dentro de la transacción y vuelve a comprobar su dueño. */
    private function bloquearProducto(\PDO $pdo, string $slug): array
    {
        [$alcance, $params] = \Model\Producto::alcancePanel();
        $stmt = $pdo->prepare('SELECT p.id, p.stock FROM productos p WHERE p.slug = ?' . $alcance . ' FOR UPDATE');
        $stmt->execute(array_merge([$slug], $params));
        $producto = $stmt->fetch();
        if ($producto === false) {
            throw new \RuntimeException('El producto ya no existe o no tienes permiso para editarlo.');
        }
        return $producto;
    }

    private function ajustarStockProducto(\PDO $pdo, string $slug, int $stock, int $stockOriginal): int
    {
        $producto = $this->bloquearProducto($pdo, $slug);
        if ((int) $producto['stock'] !== $stockOriginal) {
            throw new \RuntimeException(self::ERROR_STOCK_CAMBIADO);
        }
        $productoId = (int) $producto['id'];
        $delta = $stock - $stockOriginal;
        if ($delta !== 0) {
            $error = \Model\Inventario::ajustar(
                $pdo,
                $productoId,
                null,
                $delta > 0 ? 'entrada' : 'salida',
                abs($delta),
                'Stock desde el panel'
            );
            if ($error !== null) {
                throw new \RuntimeException($error);
            }
        }
        return $productoId;
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
                "SELECT COALESCE(SUM(c.monto_comision + c.monto_vendedor), 0) FROM comisiones c
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
                  GROUP BY p.id, c.name, c.apellidos
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
            'comisiones' => (float) $pdo->query('SELECT COALESCE(SUM(monto_comision), 0) FROM comisiones')->fetchColumn(),
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
            $slug = (string) ($_POST['id'] ?? '');
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
            $id = is_scalar($_POST['id'] ?? '') ? trim((string) ($_POST['id'] ?? '')) : '';
            $stockOriginal = $this->stockFormulario($_POST['stock_original'] ?? null);
            if ($id !== '' && $stockOriginal === null) {
                $this->flash('Falta un stock original válido. Recarga la página antes de guardar.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }
            if (array_filter($_POST, static fn ($valor) => !is_scalar($valor)) !== []) {
                $this->flash('Los datos del producto no son válidos.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }
            $nombre  = trim((string) ($_POST['nombre'] ?? ''));
            $categoria = trim((string) ($_POST['categoria'] ?? ''));
            $marcaId = $this->idOpcional($_POST['marca_id'] ?? '');
            $garantiaId = $this->idOpcional($_POST['garantia_id'] ?? '');
            $precio  = is_numeric($_POST['precio_pen'] ?? null) ? (float) $_POST['precio_pen'] : -1;
            $stock = $this->stockFormulario($_POST['stock'] ?? 0);
            $stockMinimo = $this->stockFormulario($_POST['stock_minimo'] ?? 0);
            $activo  = isset($_POST['activo']) ? 1 : 0;
            $imagen  = trim((string) ($_POST['imagen'] ?? ''));
            $especificaciones = trim((string) ($_POST['especificaciones'] ?? ''));
            $imagen = $imagen !== '' ? $imagen : 'img/producto-default.png';

            // Ficha técnica (Etapa 3). Cadena vacía -> NULL.
            $modelo      = trim((string) ($_POST['modelo'] ?? '')) ?: null;
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
                    $lista = array_values(array_filter(array_map('trim', explode(',', $coloresRaw)), static fn ($x) => $x !== ''));
                    $colores = json_encode($lista, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($nombre === '' || $categoria === '') {
                $this->flash('El nombre y la categoría son obligatorios.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            if (!is_finite($precio) || $precio < 0 || $precio > 99999999.99) {
                $this->flash('El precio debe ser un número entre 0 y 99 999 999.99.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            if ($stock === null || $stockMinimo === null) {
                $this->flash('El stock y el stock mínimo deben ser enteros entre 0 y 9 999 999.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            if ($marcaId === false || $garantiaId === false) {
                $this->flash('La marca o la garantía elegida no es válida.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            // La imagen subida se valida antes de tocar la base: si el archivo
            // no es una imagen real, no se guarda nada del formulario.
            $imagenSubida = \Model\ImagenProducto::leer($_FILES['imagen_archivo'] ?? null);
            if ($imagenSubida['estado'] === 'error') {
                $this->flash((string) ($imagenSubida['error'] ?? 'La imagen no es válida.'), true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

            // (Etapa 6) Quién es dueño de la publicación. Un vendedor del
            // panel solo puede publicar a su propio nombre; un admin elige.
            $vendedorId = Admin::esVendedor()
                ? Admin::idSesion()
                : $this->idOpcional($_POST['vendedor_id'] ?? '');
            if ($vendedorId === false) {
                $this->flash('Elige un vendedor activo o la plataforma.', true);
                header('Location: ?ruta=admin/productos');
                exit;
            }

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

            try {
                $pdo->beginTransaction();

                // La ficha guarda el nombre canónico; nunca un slug o texto libre sin referencia.
                $stmtCat = $pdo->prepare('SELECT nombre FROM categorias WHERE slug = ? OR nombre = ? LIMIT 1');
                $stmtCat->execute([$categoria, $categoria]);
                $categoria = $stmtCat->fetchColumn();
                if ($categoria === false || trim((string) $categoria) === '' || mb_strlen((string) $categoria) > 80) {
                    throw new \RuntimeException('Elige una categoría válida de hasta 80 caracteres.');
                }

                foreach (['marcas' => $marcaId, 'garantias' => $garantiaId] as $tabla => $referenciaId) {
                    if ($referenciaId !== null) {
                        $qReferencia = $pdo->prepare('SELECT id FROM ' . $tabla . ' WHERE id = ? AND activo = 1');
                        $qReferencia->execute([$referenciaId]);
                        if (!$qReferencia->fetchColumn()) {
                            throw new \RuntimeException('La marca o la garantía elegida no está disponible.');
                        }
                    }
                }
                if ($id !== '') {
                    $productoId = $this->ajustarStockProducto($pdo, $id, $stock, $stockOriginal);
                }
                if ($vendedorId !== null) {
                    $qVendedor = $pdo->prepare(
                        "SELECT id FROM users WHERE id = ? AND rol = 'vendedor' AND activo = 1 AND deleted_at IS NULL FOR SHARE"
                    );
                    $qVendedor->execute([$vendedorId]);
                    if (!$qVendedor->fetchColumn()) {
                        throw new \RuntimeException('Elige un vendedor activo o la plataforma.');
                    }
                }

                if ($id !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE productos SET categoria = ?, nombre = ?, modelo = ?, marca_id = ?, garantia_id = ?, '
                        . 'precio_pen = ?, stock_minimo = ?, activo = ?, '
                        // Al reactivar una publicación se conserva la limpieza del archivo.
                        . 'archivado_en = CASE WHEN ? = 1 THEN NULL ELSE archivado_en END, '
                        . 'imagen = ?, especificaciones = ?, '
                        . 'peso_kg = ?, dimensiones = ?, colores = ?, vendedor_id = ? WHERE id = ?'
                    );
                    $stmt->execute([
                        $categoria, $nombre, $modelo, $marcaId, $garantiaId,
                        $precio, $stockMinimo, $activo, $activo, $imagen, $especificaciones,
                        $pesoKg, $dimensiones, $colores, $vendedorId, $productoId,
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO productos (slug, categoria, nombre, modelo, marca_id, garantia_id, '
                        . 'precio_pen, stock, stock_minimo, activo, imagen, especificaciones, peso_kg, dimensiones, colores, vendedor_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id'
                    );
                    $stmt->execute([
                        $slugId, $categoria, $nombre, $modelo, $marcaId, $garantiaId,
                        $precio, $stockMinimo, $activo, $imagen, $especificaciones,
                        $pesoKg, $dimensiones, $colores, $vendedorId,
                    ]);
                    $productoId = (int) $stmt->fetchColumn();
                    if ($stock > 0) {
                        $errorStock = \Model\Inventario::ajustar($pdo, $productoId, null, 'entrada', $stock, 'Stock inicial desde el panel');
                        if ($errorStock !== null) {
                            throw new \RuntimeException($errorStock);
                        }
                    }
                }

                if ($productoId <= 0) {
                    throw new \RuntimeException('No se pudo guardar el producto.');
                }

                if ($imagenSubida['estado'] === 'ok') {
                    $guardada = \Model\ImagenProducto::guardar(
                        $productoId,
                        (string) ($imagenSubida['datos'] ?? ''),
                        (string) ($imagenSubida['mime'] ?? '')
                    );
                    if (!$guardada) {
                        throw new \RuntimeException('No se pudo guardar la imagen del producto.');
                    }
                }

                $this->auditar(
                    $admin,
                    $id !== '' ? 'productos/editar' : 'productos/crear',
                    $productoId,
                    ($id !== '' ? 'Editó' : 'Creó') . ' el producto "' . $nombre . '"'
                );
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensaje = 'No se pudo guardar el producto. Revisa los datos e inténtalo de nuevo.';
                if (get_class($e) === \RuntimeException::class) {
                    $mensaje = $e->getMessage();
                } elseif ($e instanceof \PDOException && $e->getCode() === '23505') {
                    $mensaje = 'Ya existe un producto con ese SKU o identificador.';
                } elseif ($e instanceof \PDOException && $e->getCode() === '23503') {
                    $mensaje = 'La categoría, marca, garantía o vendedor ya no está disponible. Recarga la página.';
                } else {
                    error_log('AdminController::productos/guardar — ' . $e->getMessage());
                }
                $this->flash($mensaje, true);
            }

            header('Location: ?ruta=admin/productos');
            exit;
        }

        if ($subRuta === 'productos/estado') {
            $slug = (string) ($_POST['id'] ?? '');
            if ($slug !== '') {
                if (!\Model\Producto::puedeGestionar($slug)) {
                    $this->flash('No tienes permiso sobre ese producto.', true);
                    header('Location: ?ruta=admin/productos');
                    exit;
                }
                // Al reactivar, se limpia `archivado_en`: una publicación
                // archivada no puede quedar visible en la tienda.
                $pdo->prepare(
                    'UPDATE productos
                        SET archivado_en = CASE WHEN activo = 1 THEN archivado_en ELSE NULL END,
                            activo = 1 - activo
                      WHERE slug = ?'
                )->execute([$slug]);
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
            $sql .= ' AND p.nombre ILIKE ?';
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
            if (array_filter($_POST, static fn ($valor) => !is_scalar($valor)) !== []) {
                $this->flash('Los datos de la categoría no son válidos.', true);
                header('Location: ?ruta=admin/categorias');
                exit;
            }
            $id     = (int) ($_POST['id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $desc   = trim((string) ($_POST['descripcion'] ?? ''));

            if ($nombre === '' || mb_strlen($nombre) > 80 || mb_strlen($desc) > 255) {
                $this->flash('Indica un nombre de 1 a 80 caracteres y una descripción de hasta 255.', true);
                header('Location: ?ruta=admin/categorias');
                exit;
            }

            try {
                $pdo->beginTransaction();
                if ($id > 0) {
                    $pdo->prepare('UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?')->execute([$nombre, $desc, $id]);
                    $this->auditar($admin, 'categorias/editar', $id, 'Editó la categoría "' . $nombre . '"');
                } else {
                    $nueva = $pdo->prepare('INSERT INTO categorias (nombre, slug, descripcion) VALUES (?, ?, ?) RETURNING id');
                    $nueva->execute([$nombre, $this->slugify($nombre), $desc]);
                    $this->auditar($admin, 'categorias/crear', (int) $nueva->fetchColumn(), 'Creó la categoría "' . $nombre . '"');
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($e instanceof \PDOException && $e->getCode() === '23505') {
                    $this->flash('Ya existe una categoría con ese nombre o identificador.', true);
                } elseif ($e instanceof \PDOException && $e->getCode() === '23503') {
                    $this->flash('No se pudo guardar la categoría por sus productos asociados.', true);
                } else {
                    error_log('AdminController::categorias/guardar — ' . $e->getMessage());
                    $this->flash('No se pudo guardar la categoría. Revisa los datos e inténtalo de nuevo.', true);
                }
            }
            header('Location: ?ruta=admin/categorias');
            exit;
        }

        if ($subRuta === 'categorias/eliminar') {
            $id = $this->idOpcional($_POST['id'] ?? '');
            if ($id === null || $id === false) {
                $this->flash('La categoría no es válida.', true);
                header('Location: ?ruta=admin/categorias');
                exit;
            }
            try {
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'categorias/eliminar', $id, 'Eliminó la categoría #' . $id);
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($e instanceof \PDOException && $e->getCode() === '23503') {
                    $this->flash('No se pudo eliminar: tiene productos asociados.', true);
                } else {
                    error_log('AdminController::categorias/eliminar — ' . $e->getMessage());
                    $this->flash('No se pudo eliminar la categoría. Inténtalo de nuevo.', true);
                }
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
            $id = (int) ($_POST['id'] ?? 0);
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
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE garantias SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'garantias/estado', $id, 'Cambió el estado de la garantía #' . $id);
            header('Location: ?ruta=admin/garantias');
            exit;
        }

        if ($subRuta === 'garantias/eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
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

            if (!in_array($tipo, ['porcentaje', 'fijo'], true) || $valor <= 0
                || ($tipo === 'porcentaje' && $valor > 100)) {
                $this->flash('El valor del cupón debe ser mayor que 0 y, si es porcentaje, como máximo 100.', true);
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
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE cupones SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'cupones/estado', $id, 'Cambió el estado del cupón #' . $id);
            header('Location: ?ruta=admin/cupones');
            exit;
        }

        if ($subRuta === 'cupones/eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
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
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE tarifas_envio SET activo = 1 - activo WHERE id = ?')->execute([$id]);
            $this->auditar($admin, 'envios/estado', $id, 'Cambió el estado de la tarifa #' . $id);
            header('Location: ?ruta=admin/envios');
            exit;
        }

        if ($subRuta === 'envios/eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                $pdo->prepare('DELETE FROM tarifas_envio WHERE id = ?')->execute([$id]);
                $this->auditar($admin, 'envios/eliminar', $id, 'Eliminó la tarifa #' . $id);
                $this->flash('Tarifa eliminada.');
            } catch (\PDOException $e) {
                if (($e->errorInfo[0] ?? '') === '23503') {
                    $this->flash('No se puede eliminar: la tarifa tiene direcciones asociadas.', true);
                } else {
                    error_log('AdminController::envios/eliminar — ' . $e->getMessage());
                    $this->flash('No se pudo eliminar la tarifa. Inténtalo de nuevo.', true);
                }
            }
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
                     ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor'
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

        // El reembolso es una operación financiera exclusiva del administrador.
        // Un vendedor comparte la sección `pedidos`, pero no puede reembolsar
        // ni los pedidos donde participa.
        if ($subRuta === 'pedidos/reembolsar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Admin::esAdmin()) {
                $this->flash('Solo un administrador puede reembolsar pedidos.', true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $id     = (int) ($_POST['id'] ?? 0);
            $motivo = trim((string) ($_POST['motivo'] ?? ''));
            if (mb_strlen($motivo) > 160) {
                $motivo = mb_substr($motivo, 0, 160);
            }

            if ($id <= 0) {
                $this->flash('Pedido no válido.', true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $monto = 0.0;
            $devuelto = false;

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                $pedido = $stmt->fetch();
                if (!$pedido) {
                    throw new \RuntimeException('El pedido ya no existe.');
                }

                // La elegibilidad la decide el PAGO, no el estado del pedido:
                // un pedido enviado o entregado ya no se puede cancelar, pero
                // sí reembolsar; y un pedido cancelado cuyo pago quedó
                // confirmado todavía puede necesitar el reembolso.
                $stmt = $pdo->prepare(
                    'SELECT * FROM pagos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE'
                );
                $stmt->execute([$id]);
                $pago = $stmt->fetch();
                if (!$pago) {
                    throw new \RuntimeException('El pedido no tiene un pago registrado.');
                }
                if ((string) $pago['estado'] === 'Reembolsado') {
                    throw new \RuntimeException('El pago de este pedido ya fue reembolsado.');
                }
                if (!\Model\Pedido::pagoReembolsable((string) $pago['estado'])) {
                    throw new \RuntimeException(
                        'El pago no está confirmado (estado actual: "' . (string) $pago['estado'] . '").'
                    );
                }

                $monto = (float) $pago['monto_pen'];
                if ($monto <= 0) {
                    throw new \RuntimeException('El pago no tiene un monto válido para reembolsar.');
                }

                // El pedido pasa a Cancelado ANTES de tocar cualquier saldo:
                // las consultas de comisiones excluyen Cancelado, así que el
                // pedido deja de generar ganancias para el vendedor y no
                // puede entrar en una liquidación posterior.
                $pdo->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = ?")->execute([$id]);
                $pdo->prepare("UPDATE pagos SET estado = 'Reembolsado' WHERE id = ?")
                    ->execute([(int) $pago['id']]);

                // Devuelve las unidades al stock y registra los movimientos
                // de entrada. Es idempotente por pedido.
                $devuelto = \Model\Inventario::devolver($pdo, $id);

                \Model\PedidoEvento::registrar(
                    $pdo,
                    $id,
                    \Model\PedidoEvento::REEMBOLSADO,
                    \Model\Pedido::CANCELADO,
                    'Reembolso de S/ ' . number_format($monto, 2)
                        . ($motivo !== '' ? ' · ' . $motivo : '')
                );

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('AdminController::pedidos/reembolsar — ' . $e->getMessage());
                $this->flash(get_class($e) === \RuntimeException::class
                    ? $e->getMessage() : 'No se pudo reembolsar el pedido. Inténtalo de nuevo.', true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $this->auditar(
                $admin,
                'pedidos/reembolsar',
                $id,
                mb_substr(
                    'Reembolsó S/ ' . number_format($monto, 2) . ' del pedido #' . $id
                    . ($devuelto ? ' y devolvió stock' : ' (stock ya devuelto)')
                    . ($motivo !== '' ? ' · ' . $motivo : ''),
                    0,
                    255
                )
            );

            // El aviso se registra DESPUÉS del commit: si el reembolso falla,
            // no se genera un correo de un pago que no se revirtió.
            $avisoCorreo = '';
            try {
                Notificacion::reembolso($id);
            } catch (\Throwable $e) {
                $avisoCorreo = ' No se pudo registrar el correo simulado.';
                error_log('Correo simulado de reembolso del pedido #' . $id . ': ' . $e->getMessage());
            }

            $this->flash(
                'Pedido #' . $id . ' reembolsado por S/ ' . number_format($monto, 2)
                . ($devuelto ? '. Stock devuelto al inventario.' : '.') . $avisoCorreo,
                $avisoCorreo !== ''
            );
            header('Location: ?ruta=admin/pedidos');
            exit;
        }

        if ($subRuta === 'pedidos/estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id      = (int) ($_POST['id'] ?? 0);
            $estado  = (string) ($_POST['estado'] ?? '');
            $courier = trim((string) ($_POST['courier'] ?? ''));
            $guia    = trim((string) ($_POST['guia_numero'] ?? ''));

            // Un vendedor solo puede mover pedidos que llevan sus líneas: el
            // listado ya viene acotado, pero el POST no.
            if ($id > 0 && Admin::esVendedor()) {
                $qPermiso = $pdo->prepare(
                    'SELECT 1 FROM detalle_pedido WHERE pedido_id = ? AND vendedor_id = ? LIMIT 1'
                );
                $qPermiso->execute([$id, (int) Admin::idSesion()]);
                if (!$qPermiso->fetchColumn()) {
                    $this->flash('No tienes permiso sobre ese pedido.', true);
                    header('Location: ?ruta=admin/pedidos');
                    exit;
                }
            }

            $qPedido = $pdo->prepare('SELECT estado, envio_zona FROM pedidos WHERE id = ?');
            $qPedido->execute([$id]);
            $filaPedido = $qPedido->fetch();
            $actual = $filaPedido ? (string) $filaPedido['estado'] : '';
            $zona   = $filaPedido ? (string) ($filaPedido['envio_zona'] ?? '') : '';

            if ($actual === '' || !\Model\Pedido::transicionPermitida($actual, $estado)) {
                $this->flash('Transición de estado no permitida: "' . $actual . '" → "' . $estado . '".', true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            // Evita eventos/correos duplicados por un doble POST o por volver
            // a enviar el mismo estado.
            if ($actual === $estado) {
                $this->flash('El pedido #' . $id . ' ya está en estado "' . $estado . '".');
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $esRecojo = $zona === Envio::ZONA_RECOJO;
            if ($estado === 'Enviado') {
                if ($esRecojo) {
                    // El recojo no tiene courier ni guía: el hito se muestra
                    // como "Listo para recojo" en el historial del cliente.
                    $courier = 'Recojo en tienda';
                    $guia = '';
                } elseif ($courier === '' || $guia === '') {
                    $this->flash('Para marcar el pedido como Enviado debes indicar courier y número de guía.', true);
                    header('Location: ?ruta=admin/pedidos');
                    exit;
                }
                $courier = mb_substr($courier, 0, 80);
                $guia = mb_substr($guia, 0, 80);
            } else {
                $courier = '';
                $guia = '';
            }

            $evento = match ($estado) {
                'Pagado'    => PedidoEvento::PAGO_CONFIRMADO,
                'Enviado'   => PedidoEvento::ENVIADO,
                'Entregado' => PedidoEvento::ENTREGADO,
                'Cancelado' => PedidoEvento::CANCELADO,
                default     => null,
            };
            $detalleEvento = match ($estado) {
                'Pagado'    => 'Pago confirmado desde el panel.',
                'Enviado'   => $esRecojo
                    ? 'Pedido listo para recojo en tienda.'
                    : 'Courier: ' . $courier . ' · Guía: ' . $guia,
                'Entregado' => 'Pedido entregado al cliente.',
                'Cancelado' => 'Pedido cancelado desde el panel.',
                default     => '',
            };

            $pdo->beginTransaction();
            try {
                // La condición sobre `estado` repite el estado leído: dos
                // peticiones simultáneas no pueden aplicar la misma transición
                // dos veces (doble devolución de stock, doble evento).
                if ($estado === 'Enviado') {
                    $cambio = $pdo->prepare(
                        'UPDATE pedidos
                            SET estado = ?, courier = ?, guia_numero = ?,
                                fecha_envio = COALESCE(fecha_envio, NOW())
                          WHERE id = ? AND estado = ?'
                    );
                    $cambio->execute([$estado, $courier, $guia, $id, $actual]);
                } else {
                    $cambio = $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ? AND estado = ?');
                    $cambio->execute([$estado, $id, $actual]);
                }
                if ($cambio->rowCount() === 0) {
                    throw new \RuntimeException('El pedido cambió de estado mientras se procesaba la solicitud.');
                }

                // Si el administrador confirma un pedido pendiente (por ejemplo
                // una transferencia), el pago simulado también queda confirmado.
                if ($estado === 'Pagado') {
                    $pdo->prepare(
                        "UPDATE pagos SET estado = 'Pagado'
                          WHERE pedido_id = ? AND estado IN ('Pendiente', 'En verificación')"
                    )->execute([$id]);
                }

                // (Etapa 8) Cancelar devuelve las unidades al inventario y lo
                // registra como movimiento de entrada. `devolver()` es
                // idempotente: un doble clic no duplica stock.
                if ($estado === 'Cancelado') {
                    // Cancelar no devuelve dinero: si el pago está confirmado,
                    // el camino correcto es Reembolsar (deja el pago en
                    // "Reembolsado" y devuelve el stock en la misma transacción).
                    $qPago = $pdo->prepare(
                        'SELECT estado FROM pagos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1'
                    );
                    $qPago->execute([$id]);
                    if ((string) $qPago->fetchColumn() === 'Pagado') {
                        throw new \RuntimeException(
                            'El pedido tiene un pago confirmado: usa Reembolsar para devolver el dinero.'
                        );
                    }
                    \Model\Inventario::devolver($pdo, $id);
                }

                if ($evento !== null) {
                    PedidoEvento::registrar($pdo, $id, $evento, $estado, $detalleEvento);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('AdminController::pedidos/estado — ' . $e->getMessage());
                $this->flash(get_class($e) === \RuntimeException::class
                    ? $e->getMessage() : 'No se pudo actualizar el pedido. Inténtalo de nuevo.', true);
                header('Location: ?ruta=admin/pedidos');
                exit;
            }

            $this->auditar(
                $admin,
                'pedidos/estado',
                $id,
                mb_substr('Cambió el estado del pedido #' . $id . ' a "' . $estado . '"' . ($detalleEvento !== '' ? ' · ' . $detalleEvento : ''), 0, 255)
            );

            // El correo se registra DESPUÉS del commit: si el cambio de estado
            // falla, no se genera un aviso de un pedido que no se actualizó.
            $avisoCorreo = '';
            try {
                if ($estado === 'Pagado') {
                    Notificacion::pagoConfirmado($id);
                } elseif ($estado === 'Enviado') {
                    Notificacion::pedidoEnviado($id);
                }
            } catch (\Throwable $e) {
                $avisoCorreo = ' No se pudo registrar el correo simulado.';
                error_log('Correo simulado del pedido #' . $id . ': ' . $e->getMessage());
            }

            $this->flash(
                'Pedido #' . $id . ' actualizado a "' . $estado . '".' . $avisoCorreo,
                $avisoCorreo !== ''
            );
            header('Location: ?ruta=admin/pedidos');
            exit;
        }

        // La vista ya consulta y pagina los pedidos; no duplicar todo el historial.
        $this->vista('pedidos', []);
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
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0 && $id !== $yoId) {
                $fila = $this->usuarioPorId($pdo, $id);
                if ($fila === null) {
                    $this->flash('El usuario ya no existe.', true);
                } elseif ((int) $fila['activo'] === 1
                    && ($fila['rol'] ?? '') === 'admin'
                    && $this->esUltimoAdminActivo($pdo, $id)) {
                    $this->flash('No puedes desactivar al único administrador activo.', true);
                } else {
                    $pdo->prepare('UPDATE users SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END WHERE id = ?')
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
            $id = (int) ($_POST['id'] ?? 0);
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
            $tipo     = strtoupper(trim((string) ($_POST['tipo'] ?? 'B2C')));
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
            } elseif (!in_array($tipo, ['B2C', 'B2B', 'C2C'], true)) {
                $error = 'Tipo de cliente no válido.';
            } elseif ($nombre === '') {
                $error = 'El nombre es obligatorio.';
            } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ingresa un correo electrónico válido.';
            } elseif ($rol !== 'cliente' && $usuario === '') {
                $error = 'El nombre de usuario es obligatorio para administradores y vendedores.';
            } elseif ((!$esEdicion || $clave !== '') && ($errorClave = \Security\Contrasena::error($clave)) !== null) {
                $error = $errorClave;
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
                        $pdo->prepare('UPDATE users SET name = ?, apellidos = ?, email = ?, usuario = ?, password = ?, rol = ?, tipo = ?, activo = ? WHERE id = ?')
                            ->execute([$nombre, $apellidos, $email, $usuario !== '' ? $usuario : null, password_hash($clave, PASSWORD_DEFAULT), $rol, $tipo, $activo, $id]);

                        // Una contraseña nueva invalida los enlaces de
                        // recuperación que estuvieran pendientes.
                        $pdo->prepare('UPDATE tokens_recuperacion SET usado = 1 WHERE usuario_id = ? AND usado = 0')
                            ->execute([$id]);
                    } else {
                        $pdo->prepare('UPDATE users SET name = ?, apellidos = ?, email = ?, usuario = ?, rol = ?, tipo = ?, activo = ? WHERE id = ?')
                            ->execute([$nombre, $apellidos, $email, $usuario !== '' ? $usuario : null, $rol, $tipo, $activo, $id]);
                    }
                    $this->auditar($admin, 'usuarios/editar', $id, 'Editó al usuario "' . $nombre . '"');
                    $this->flash('Usuario actualizado.');
                } else {
                    $pdo->prepare('INSERT INTO users (name, apellidos, email, usuario, password, rol, tipo, activo)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute([
                            $nombre,
                            $apellidos,
                            $email,
                            $usuario !== '' ? $usuario : null,
                            password_hash($clave, PASSWORD_DEFAULT),
                            $rol,
                            $tipo,
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
                "SELECT id, name, apellidos, email, usuario, rol, tipo, activo, ultimo_ingreso, created_at
                 FROM users WHERE deleted_at IS NULL
                 ORDER BY CASE rol WHEN 'admin' THEN 0 WHEN 'vendedor' THEN 1 ELSE 2 END, id"
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

    // ------------------------------------------------------------ //
    //  Inventario (historial y ajustes manuales)
    // ------------------------------------------------------------ //

    private function inventario(string $subRuta): void
    {
        $pdo = conexion();
        $admin = Admin::usuarioActual();

        if ($subRuta === 'inventario/ajustar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoId = (int) ($_POST['producto_id'] ?? 0);
            $varianteId = (int) ($_POST['variante_id'] ?? 0);
            $tipo       = (string) ($_POST['tipo'] ?? '');
            $cantidad   = (int) ($_POST['cantidad'] ?? 0);
            $motivo     = trim((string) ($_POST['motivo'] ?? ''));

            $volver = '?ruta=admin/inventario' . ($productoId > 0 ? '&producto=' . $productoId : '');

            $pdo->beginTransaction();
            try {
                // El stock y el movimiento se escriben en la MISMA transacción:
                // si una parte falla, no queda un ajuste sin rastro ni un
                // movimiento sin stock actualizado.
                $error = \Model\Inventario::ajustar(
                    $pdo,
                    $productoId,
                    $varianteId > 0 ? $varianteId : null,
                    $tipo,
                    $cantidad,
                    $motivo
                );
                if ($error !== null) {
                    $pdo->rollBack();
                    $this->flash($error, true);
                    header('Location: ' . $volver);
                    exit;
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('AdminController::inventario/ajustar — ' . $e->getMessage());
                $this->flash('No se pudo registrar el ajuste. Inténtalo de nuevo.', true);
                header('Location: ' . $volver);
                exit;
            }

            $this->auditar(
                $admin,
                'inventario/ajustar',
                $productoId,
                mb_substr(
                    ($tipo === 'entrada' ? 'Entrada' : 'Salida') . ' de ' . $cantidad
                    . ' unidad(es) · ' . $motivo,
                    0,
                    255
                )
            );
            $this->flash(
                'Ajuste registrado: ' . ($tipo === 'entrada' ? '+' : '−') . $cantidad
                . ' unidad(es) de stock.'
            );
            header('Location: ' . $volver);
            exit;
        }

        // La vista calcula sus propios filtros y datos (mismo patrón que el
        // resto de secciones del panel).
        $this->vista('inventario', []);
    }

    // ------------------------------------------------------------ //
    //  Notificaciones (visor de la cola simulada)
    // ------------------------------------------------------------ //

    private function notificaciones(string $subRuta): void
    {
        // Solo lectura: la cola la generan los flujos de pedidos y el
        // reembolso. No se permite marcar como enviada ni editar el cuerpo.
        $this->vista('notificaciones', []);
    }

    // ------------------------------------------------------------ //
    //  Soporte postventa
    // ------------------------------------------------------------ //

    // ------------------------------------------------------------ //
    //  Solicitudes de rol de vendedor
    // ------------------------------------------------------------ //

    private function solicitudes(string $subRuta): void
    {
        $admin   = Admin::usuarioActual();
        $usuario = (string) ($admin['usuario'] ?? 'admin');

        if ($subRuta === 'solicitudes/aprobar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id    = (int) ($_POST['id'] ?? 0);
            $error = \Model\SolicitudVendedor::aprobar($id, $usuario);

            if ($error !== null) {
                $this->flash($error, true);
            } else {
                $this->auditar($admin, 'solicitudes/aprobar', $id, 'Aprobó la solicitud de vendedor #' . $id);
                $this->flash('Solicitud aprobada: la cuenta ya puede publicar y conserva todos sus datos.');
            }

            header('Location: ?ruta=admin/solicitudes');
            exit;
        }

        if ($subRuta === 'solicitudes/rechazar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id     = (int) ($_POST['id'] ?? 0);
            $motivo = (string) ($_POST['motivo'] ?? '');
            $error  = \Model\SolicitudVendedor::rechazar($id, $usuario, $motivo);

            if ($error !== null) {
                $this->flash($error, true);
            } else {
                $this->auditar($admin, 'solicitudes/rechazar', $id, 'Rechazó la solicitud de vendedor #' . $id);
                $this->flash('Solicitud rechazada. El usuario verá el motivo en su perfil.');
            }

            header('Location: ?ruta=admin/solicitudes');
            exit;
        }

        $this->vista('solicitudes', []);
    }

    private function soporte(string $subRuta): void
    {
        $admin = Admin::usuarioActual();

        if ($subRuta === 'soporte/responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $respuesta = (string) ($_POST['respuesta'] ?? '');
            $error = \Model\SoporteTicket::responder(
                $id,
                $respuesta,
                (string) ($admin['usuario'] ?? 'admin')
            );

            if ($error !== null) {
                $this->flash($error, true);
            } else {
                $this->auditar($admin, 'soporte/responder', $id, 'Respondió el ticket ' . \Model\SoporteTicket::codigo($id));
                $this->flash('Respuesta guardada. El cliente recibió el aviso en su bandeja.');
            }

            header('Location: ?ruta=admin/soporte#ticket-' . $id);
            exit;
        }

        if ($subRuta === 'soporte/estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $estado = (string) ($_POST['estado'] ?? '');

            if (\Model\SoporteTicket::cambiarEstado($id, $estado)) {
                $this->auditar(
                    $admin,
                    'soporte/estado',
                    $id,
                    'Cambió el ticket ' . \Model\SoporteTicket::codigo($id)
                        . ' a ' . \Model\SoporteTicket::etiquetaEstado($estado)
                );
                $this->flash('Estado del ticket actualizado.');
            } else {
                $this->flash('No se pudo cambiar el estado del ticket.', true);
            }

            header('Location: ?ruta=admin/soporte#ticket-' . $id);
            exit;
        }

        $this->vista('soporte', []);
    }

    // ------------------------------------------------------------ //
    //  Moderación de reseñas
    // ------------------------------------------------------------ //

    private function resenas(string $subRuta): void
    {
        $admin = Admin::usuarioActual();

        if ($subRuta === 'resenas/ocultar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $motivo = (string) ($_POST['motivo'] ?? '');
            $error = \Model\Resena::ocultar($id, $motivo, (string) ($admin['usuario'] ?? 'admin'));

            if ($error !== null) {
                $this->flash($error, true);
            } else {
                $this->auditar($admin, 'resenas/ocultar', $id, 'Ocultó la reseña #' . $id . ($motivo !== '' ? ': ' . $motivo : ''));
                $this->flash('Reseña oculta: ya no se muestra en el catálogo.');
            }

            header('Location: ?ruta=admin/resenas#resena-' . $id);
            exit;
        }

        if ($subRuta === 'resenas/mostrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $error = \Model\Resena::mostrar($id, (string) ($admin['usuario'] ?? 'admin'));

            if ($error !== null) {
                $this->flash($error, true);
            } else {
                $this->auditar($admin, 'resenas/mostrar', $id, 'Volvió a publicar la reseña #' . $id);
                $this->flash('Reseña publicada de nuevo.');
            }

            header('Location: ?ruta=admin/resenas#resena-' . $id);
            exit;
        }

        $this->vista('resenas', []);
    }

    // ------------------------------------------------------------ //
    //  Pagos y webhooks
    // ------------------------------------------------------------ //

    private function pagos(string $subRuta): void
    {
        // Solo lectura: los estados los mueve el checkout o el webhook.
        $this->vista('pagos', []);
    }

    private function auditoria(): void
    {
        $pdo = conexion();
        $this->vista('auditoria', [
            'registros' => $pdo->query('SELECT * FROM auditoria_admin ORDER BY id DESC LIMIT 250')->fetchAll(),
        ]);
    }

    // ------------------------------------------------------------ //
    //  Analítica (dashboard de comportamiento en tienda)
    // ------------------------------------------------------------ //

    private function analitica(string $subRuta): void
    {
        // La vista calcula los agregados con el modelo: admin global o vendedor acotado.
        $this->vista('analitica', []);
    }

    // ------------------------------------------------------------ //
    //  Chatbot FAQ (CRUD)
    // ------------------------------------------------------------ //

    private function chatbot(string $subRuta): void
    {
        $admin = Admin::usuarioActual();

        if ($subRuta === 'chatbot/guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $pregunta = trim((string) ($_POST['pregunta'] ?? ''));
            $palabrasClave = trim((string) ($_POST['palabras_clave'] ?? ''));
            $respuesta = trim((string) ($_POST['respuesta'] ?? ''));
            $orden = (int) ($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 0;

            if ($pregunta === '' || $respuesta === '') {
                $this->flash('La pregunta y la respuesta son obligatorias.', true);
                header('Location: ?ruta=admin/chatbot');
                exit;
            }

            if ($id > 0) {
                ChatbotFaq::actualizar($id, $pregunta, $palabrasClave, $respuesta, $orden, $activo === 1);
                $this->auditar($admin, 'chatbot/faq/editar', $id, 'Actualizó la FAQ #' . $id);
            } else {
                $faqId = ChatbotFaq::crear($pregunta, $palabrasClave, $respuesta, $orden, $activo === 1);
                $this->auditar($admin, 'chatbot/faq/crear', $faqId, 'Creó la FAQ #' . $faqId);
            }

            $this->flash('FAQ guardada correctamente.');
            header('Location: ?ruta=admin/chatbot');
            exit;
        }

        if ($subRuta === 'chatbot/estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $faq = ChatbotFaq::porId($id);
            if ($faq === null) {
                $this->flash('La FAQ no existe.', true);
                header('Location: ?ruta=admin/chatbot');
                exit;
            }

            $activoActual = (int) ($faq['activo'] ?? 0);
            ChatbotFaq::setActivo($id, $activoActual !== 1);
            $this->auditar($admin, 'chatbot/faq/estado', $id, $activoActual !== 1 ? 'Activó la FAQ #' . $id : 'Desactivó la FAQ #' . $id);

            $this->flash($activoActual !== 1 ? 'FAQ activada.' : 'FAQ desactivada.');
            header('Location: ?ruta=admin/chatbot');
            exit;
        }

        if ($subRuta === 'chatbot/eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            ChatbotFaq::eliminar($id);
            $this->auditar($admin, 'chatbot/faq/eliminar', $id, 'Eliminó la FAQ #' . $id);

            $this->flash('FAQ eliminada.');
            header('Location: ?ruta=admin/chatbot');
            exit;
        }

        $this->vista('chatbot', []);
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
        $slugRaw = $_GET['p'] ?? $_POST['p'] ?? '';
        $slug = is_scalar($slugRaw) ? trim((string) $slugRaw) : '';
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

            $id = is_scalar($_POST['id'] ?? 0) ? (int) ($_POST['id'] ?? 0) : 0;
            $stockOriginal = $this->stockFormulario($_POST['stock_original'] ?? null);
            if ($id > 0 && $stockOriginal === null) {
                $this->flash('Falta un stock original válido. Recarga la página antes de guardar.', true);
                header('Location: ' . $volver);
                exit;
            }
            if (array_filter($_POST, static fn ($valor) => !is_scalar($valor)) !== []) {
                $this->flash('Los datos de la variante no son válidos.', true);
                header('Location: ' . $volver);
                exit;
            }
            $nombre = trim((string) ($_POST['nombre_variante'] ?? ''));
            $extra = is_numeric($_POST['precio_extra'] ?? 0) ? (float) ($_POST['precio_extra'] ?? 0) : -1;
            $stock = $this->stockFormulario($_POST['stock'] ?? 0);
            $color  = trim((string) ($_POST['color'] ?? ''));
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '' || mb_strlen($nombre) > 120) {
                $this->flash('El nombre de la variante es obligatorio y no puede superar los 120 caracteres.', true);
                header('Location: ' . $volver);
                exit;
            }
            if ($stock === null || !is_finite($extra) || $extra < 0 || $extra > 99999999.99) {
                $this->flash('Revisa el precio extra y el stock entero entre 0 y 9 999 999.', true);
                header('Location: ' . $volver);
                exit;
            }
            if ($color !== '' && !preg_match('/^#[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?$/', $color)) {
                $this->flash('El color debe ser hexadecimal, por ejemplo #00D4FF.', true);
                header('Location: ' . $volver);
                exit;
            }

            try {
                $pdo->beginTransaction();
                // Mismo orden que las ventas y los ajustes: producto, luego variante.
                $producto = $this->bloquearProducto($pdo, $slug);
                $productoId = (int) $producto['id'];
                $esEdicion = $id > 0;
                if ($esEdicion) {
                    $qActual = $pdo->prepare('SELECT stock FROM producto_variantes WHERE id = ? AND producto_id = ? FOR UPDATE');
                    $qActual->execute([$id, $productoId]);
                    $stockActual = $qActual->fetchColumn();
                    if ($stockActual === false) {
                        throw new \RuntimeException('Esa variante no pertenece a este producto.');
                    }
                    if ((int) $stockActual !== $stockOriginal) {
                        throw new \RuntimeException(self::ERROR_STOCK_CAMBIADO);
                    }
                }
                if (\Model\ProductoVariante::existe($productoId, $nombre, $id)) {
                    throw new \RuntimeException('Ya existe una variante con ese nombre en este producto.');
                }
                if (!$esEdicion) {
                    $stockOriginal = 0;
                    $nueva = $pdo->prepare(
                        'INSERT INTO producto_variantes
                            (producto_id, nombre_variante, precio_extra, stock, color, activo)
                         VALUES (?, ?, ?, 0, ?, ?) RETURNING id'
                    );
                    $nueva->execute([$productoId, $nombre, $extra, $color, $activo]);
                    $id = (int) $nueva->fetchColumn();
                }

                $delta = $stock - $stockOriginal;
                if ($delta !== 0) {
                    $errorStock = \Model\Inventario::ajustar(
                        $pdo,
                        $productoId,
                        $id,
                        $delta > 0 ? 'entrada' : 'salida',
                        abs($delta),
                        'Stock de variante desde el panel'
                    );
                    if ($errorStock !== null) {
                        throw new \RuntimeException($errorStock);
                    }
                }
                $pdo->prepare(
                    'UPDATE producto_variantes SET nombre_variante = ?, precio_extra = ?, color = ?, activo = ?
                      WHERE id = ? AND producto_id = ?'
                )->execute([$nombre, $extra, $color, $activo, $id, $productoId]);
                $this->auditar(
                    $admin,
                    $esEdicion ? 'variantes/editar' : 'variantes/crear',
                    $id,
                    ($esEdicion ? 'Editó' : 'Creó') . ' la variante "' . $nombre . '"'
                );
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if (get_class($e) !== \RuntimeException::class) {
                    error_log('AdminController::variantes/guardar — ' . $e->getMessage());
                }
                $this->flash(get_class($e) === \RuntimeException::class
                    ? $e->getMessage() : 'No se pudo guardar la variante. Revisa los datos e inténtalo de nuevo.', true);
            }
            header('Location: ' . $volver);
            exit;
        }

        if ($subRuta === 'variantes/variantes/estado') {
            $id = (int) ($_POST['id'] ?? 0);
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
            $id = (int) ($_POST['id'] ?? 0);
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
                    $this->auditar(
                        $admin,
                        'volumen/editar',
                        $id,
                        'Editó el tramo de volumen #' . $id . ' (' . $cmin . '..' . ($cmax ?? '∞') . ')'
                    );
                } else {
                    $pdo->prepare(
                        'INSERT INTO precios_volumen (producto_id, cantidad_minima, cantidad_maxima, precio_unitario)
                         VALUES (?, ?, ?, ?)'
                    )->execute([(int) $producto['id'], $cmin, $cmax, $precio]);
                    $this->auditar(
                        $admin,
                        'volumen/crear',
                        (int) $pdo->lastInsertId(),
                        'Creó un tramo de volumen (' . $cmin . '..' . ($cmax ?? '∞') . ')'
                    );
                }
            } catch (\PDOException) {
                $this->flash('No se pudo guardar el tramo de volumen.', true);
            }
            header('Location: ' . $volver);
            exit;
        }

        if ($subRuta === 'variantes/volumen/eliminar') {
            $id = (int) ($_POST['id'] ?? 0);
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
