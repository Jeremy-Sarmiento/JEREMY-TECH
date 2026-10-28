<?php
declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Inventario;
use Model\ProductoVendedor;
use Model\Vendedor;
use Security\Proteccion;

/**
 * Controlador del panel "Mis productos" (Jeremy Tech) — Fase 3.
 *
 * Permite a un usuario con `users.rol = 'vendedor'` o a un cliente con
 * `users.tipo = 'C2C'` publicar, editar, controlar el stock y
 * activar/ocultar sus productos sin entrar al panel de administración.
 * Toda lectura y escritura se acota por el id de la sesión: un vendedor
 * nunca puede tocar publicaciones ajenas.
 */
class VendedorController
{
    /** Clave de sesión para el aviso de la siguiente petición. */
    private const FLASH = 'flash_vendedor';

    /** Clave de sesión para conservar el formulario cuando falla una validación. */
    private const BORRADOR = 'producto_previo';

    /**
     * Listado de publicaciones del vendedor.
     */
    public function productos(): void
    {
        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $vendedorId = (int) $vendedor['id'];
        $busqueda = isset($_GET['q']) && is_scalar($_GET['q'])
            ? mb_substr(trim((string) $_GET['q']), 0, 100)
            : '';
        $verArchivados = (($_GET['vista'] ?? '') === 'archivados');

        $productos = ProductoVendedor::listar($vendedorId, $busqueda, $verArchivados);
        $resumen = ProductoVendedor::resumen($vendedorId);

        // (Bloque 1) Economía, compradores, stock y reputación del vendedor.
        // Todo se acota por el id de la sesión; no hay tablas nuevas.
        $ganancias = Vendedor::resumenGanancias($vendedorId);
        $compradores = Vendedor::compradoresDe($vendedorId, 50);
        $movimientos = Inventario::historialDeVendedor($vendedorId, 20);
        $reputacion = Vendedor::reputacionDe($vendedorId);
        $calificaciones = Vendedor::ultimasCalificacionesDe($vendedorId, 5);

        $flash = self::tomarFlash();
        $rutaVista = 'mis-productos';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/mis_productos.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Formulario de publicación (nuevo o edición de un producto propio).
     */
    public function formulario(): void
    {
        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $vendedorId = (int) $vendedor['id'];
        $slugEditar = isset($_GET['slug']) && is_scalar($_GET['slug'])
            ? trim((string) $_GET['slug'])
            : '';
        $producto = null;

        if ($slugEditar !== '') {
            $producto = ProductoVendedor::porSlug($vendedorId, $slugEditar);
            if ($producto === null) {
                self::guardarFlash('No tienes permiso sobre ese producto.', true);
                header('Location: ?ruta=mis-productos');
                exit;
            }
            if (($producto['archivado_en'] ?? null) !== null) {
                self::guardarFlash('Esta publicación está archivada. Restáurala para editarla.', true);
                header('Location: ?ruta=mis-productos&vista=archivados');
                exit;
            }
        }

        $borrador = self::tomarBorrador();
        $formulario = $borrador ?? $this->formularioDesdeProducto($producto);
        $esEdicion = $slugEditar !== '';
        $variantes = $esEdicion
            ? ProductoVendedor::variantesDe($vendedorId, $slugEditar)
            : [];

        $flash = self::tomarFlash();
        $categorias = ProductoVendedor::categorias();
        $marcas = ProductoVendedor::marcas();
        $garantias = ProductoVendedor::garantias();
        $rutaVista = 'mis-productos';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/mis_productos_form.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Guarda una publicación nueva o editada.
     */
    public function guardar(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $vendedorId = (int) $vendedor['id'];
        $slugEditar = isset($_POST['slug_editar']) && is_scalar($_POST['slug_editar'])
            ? trim((string) $_POST['slug_editar'])
            : '';

        $resultado = ProductoVendedor::guardar($vendedorId, $_POST, $slugEditar);

        if (!$resultado['ok']) {
            self::guardarBorrador($_POST);
            self::guardarFlash($resultado['error'], true);
            $destino = $slugEditar !== ''
                ? '?ruta=mis-productos/editar&slug=' . urlencode($slugEditar)
                : '?ruta=mis-productos/nuevo';
            header('Location: ' . $destino);
            exit;
        }

        self::limpiarBorrador();
        self::guardarFlash(
            $slugEditar !== ''
                ? 'Publicación actualizada correctamente.'
                : 'Producto publicado correctamente.'
        );
        header('Location: ?ruta=mis-productos');
        exit;
    }

    /**
     * Alterna activo/oculto de una publicación propia.
     */
    public function estado(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';

        if ($slug === '' || !ProductoVendedor::cambiarEstado((int) $vendedor['id'], $slug)) {
            self::guardarFlash('No se pudo cambiar el estado del producto.', true);
        } else {
            self::guardarFlash('Estado de la publicación actualizado.');
        }

        header('Location: ?ruta=mis-productos');
        exit;
    }

    /**
     * Actualiza rápidamente el stock desde el listado.
     */
    public function stock(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';
        $stockRaw = isset($_POST['stock']) && is_scalar($_POST['stock'])
            ? trim((string) $_POST['stock'])
            : '';

        if ($slug === '' || !preg_match('/^\d+$/', $stockRaw)) {
            self::guardarFlash('Ingresa un stock válido (número entero mayor o igual a 0).', true);
        } elseif (!ProductoVendedor::actualizarStock((int) $vendedor['id'], $slug, (int) $stockRaw)) {
            self::guardarFlash('No se pudo actualizar el stock del producto.', true);
        } else {
            self::guardarFlash('Stock actualizado.');
        }

        header('Location: ?ruta=mis-productos');
        exit;
    }

    /**
     * Archiva una publicación propia (sale del panel y del catálogo, pero
     * conserva ventas, reseñas y movimientos).
     */
    public function archivar(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';

        if ($slug === '' || !ProductoVendedor::archivar((int) $vendedor['id'], $slug)) {
            self::guardarFlash('No se pudo archivar la publicación.', true);
        } else {
            self::guardarFlash('Publicación archivada. Puedes restaurarla desde la pestaña Archivados.');
        }

        header('Location: ?ruta=mis-productos');
        exit;
    }

    /**
     * Restaura una publicación archivada (vuelve como oculta).
     */
    public function restaurar(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';

        if ($slug === '' || !ProductoVendedor::restaurar((int) $vendedor['id'], $slug)) {
            self::guardarFlash('No se pudo restaurar la publicación.', true);
        } else {
            self::guardarFlash('Publicación restaurada como oculta. Actívala cuando quieras volver a publicarla.');
        }

        header('Location: ' . self::volverPanel());
        exit;
    }

    /**
     * Elimina definitivamente una publicación propia sin historial.
     */
    public function eliminar(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';

        $resultado = ProductoVendedor::eliminar((int) $vendedor['id'], $slug);
        self::guardarFlash(
            $resultado['ok'] ? 'Publicación eliminada definitivamente.' : $resultado['error'],
            !$resultado['ok']
        );

        header('Location: ' . self::volverPanel());
        exit;
    }

    /**
     * Crea o actualiza una variante de una publicación propia.
     */
    public function varianteGuardar(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';

        if ($slug === '') {
            self::guardarFlash('No se indicó la publicación.', true);
            header('Location: ?ruta=mis-productos');
            exit;
        }

        $resultado = ProductoVendedor::guardarVariante((int) $vendedor['id'], $slug, $_POST);
        self::guardarFlash(
            $resultado['ok'] ? 'Variante guardada.' : $resultado['error'],
            !$resultado['ok']
        );

        header('Location: ' . self::urlEdicion($slug));
        exit;
    }

    /**
     * Activa u oculta una variante.
     */
    public function varianteEstado(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';
        $id = isset($_POST['id']) && is_scalar($_POST['id'])
            ? (int) $_POST['id']
            : 0;

        if ($slug === '' || !ProductoVendedor::cambiarEstadoVariante((int) $vendedor['id'], $slug, $id)) {
            self::guardarFlash('No se pudo cambiar el estado de la variante.', true);
        } else {
            self::guardarFlash('Estado de la variante actualizado.');
        }

        header('Location: ' . ($slug !== '' ? self::urlEdicion($slug) : '?ruta=mis-productos'));
        exit;
    }

    /**
     * Elimina una variante de una publicación propia.
     */
    public function varianteEliminar(): void
    {
        Proteccion::exigirPost();

        $vendedor = $this->requerirVendedor();
        if ($vendedor === null) {
            return;
        }

        $slug = isset($_POST['slug']) && is_scalar($_POST['slug'])
            ? trim((string) $_POST['slug'])
            : '';
        $id = isset($_POST['id']) && is_scalar($_POST['id'])
            ? (int) $_POST['id']
            : 0;

        if ($slug === '' || !ProductoVendedor::eliminarVariante((int) $vendedor['id'], $slug, $id)) {
            self::guardarFlash('No se pudo eliminar la variante.', true);
        } else {
            self::guardarFlash('Variante eliminada.');
        }

        header('Location: ' . ($slug !== '' ? self::urlEdicion($slug) : '?ruta=mis-productos'));
        exit;
    }

    /** Destino del panel conservando la pestaña de archivados. */
    private static function volverPanel(): string
    {
        $vista = isset($_POST['vista']) && is_scalar($_POST['vista'])
            ? (string) $_POST['vista']
            : '';

        return $vista === 'archivados'
            ? '?ruta=mis-productos&vista=archivados'
            : '?ruta=mis-productos';
    }

    /** Vuelve al formulario de edición, en la sección de variantes. */
    private static function urlEdicion(string $slug): string
    {
        return '?ruta=mis-productos/editar&slug=' . urlencode($slug) . '#variantes';
    }

    /**
     * Exige sesión de tienda y rol de vendedor.
     *
     * @return array<string, mixed>|null datos del vendedor o null si ya se
     *         emitió una redirección / la vista de acceso denegado.
     */
    private function requerirVendedor(): ?array
    {
        $usuario = Cuenta::usuarioActual();
        if ($usuario === null) {
            header('Location: ?ruta=login');
            exit;
        }

        if (!Cuenta::puedePublicar($usuario)) {
            http_response_code(403);
            $rutaVista = 'mis-productos';
            $flash = ['tipo' => 'error', 'mensaje' => ''];

            require BASE_PATH . '/views/layout/header.php';
            require BASE_PATH . '/views/mis_productos_sin_acceso.php';
            require BASE_PATH . '/views/layout/footer.php';
            return null;
        }

        return $usuario;
    }

    /**
     * Normaliza un producto de la BD al shape del formulario.
     *
     * @param array<string, mixed>|null $producto
     * @return array<string, mixed>
     */
    private function formularioDesdeProducto(?array $producto): array
    {
        if ($producto === null) {
            return [
                'nombre' => '',
                'modelo' => '',
                'categoria' => '',
                'marca_id' => '',
                'garantia_id' => '',
                'codigo_sku' => '',
                'precio_pen' => '',
                'stock' => '0',
                'stock_minimo' => '5',
                'peso_kg' => '',
                'dimensiones' => '',
                'especificaciones' => '',
                'colores' => '',
                'imagen' => '',
                'activo' => 1,
            ];
        }

        $colores = (string) ($producto['colores'] ?? '');
        $listaColores = json_decode($colores, true);
        if (is_array($listaColores)) {
            $colores = implode(', ', array_map('strval', $listaColores));
        }

        return [
            'nombre' => (string) ($producto['nombre'] ?? ''),
            'modelo' => (string) ($producto['modelo'] ?? ''),
            'categoria' => (string) ($producto['categoria'] ?? ''),
            'marca_id' => $producto['marca_id'] !== null ? (string) $producto['marca_id'] : '',
            'garantia_id' => $producto['garantia_id'] !== null ? (string) $producto['garantia_id'] : '',
            'codigo_sku' => (string) ($producto['codigo_sku'] ?? ''),
            'precio_pen' => (string) ($producto['precio_pen'] ?? ''),
            'stock' => (string) ($producto['stock'] ?? '0'),
            'stock_minimo' => (string) ($producto['stock_minimo'] ?? '5'),
            'peso_kg' => $producto['peso_kg'] !== null ? (string) $producto['peso_kg'] : '',
            'dimensiones' => (string) ($producto['dimensiones'] ?? ''),
            'especificaciones' => (string) ($producto['especificaciones'] ?? ''),
            'colores' => $colores,
            'imagen' => (string) ($producto['imagen'] ?? ''),
            'activo' => (int) ($producto['activo'] ?? 1),
        ];
    }

    /**
     * Guarda el POST fallido para que el formulario no pierda lo escrito.
     *
     * @param array<string, mixed> $datos
     */
    private static function guardarBorrador(array $datos): void
    {
        Proteccion::iniciarSesion();

        $permitidos = [
            'nombre', 'modelo', 'categoria', 'marca_id', 'garantia_id', 'codigo_sku',
            'precio_pen', 'stock', 'stock_minimo', 'peso_kg', 'dimensiones',
            'especificaciones', 'colores', 'imagen', 'activo',
        ];
        $borrador = [];
        foreach ($permitidos as $clave) {
            $valor = $datos[$clave] ?? '';
            $borrador[$clave] = is_scalar($valor) ? (string) $valor : '';
        }
        $borrador['activo'] = !empty($datos['activo']) ? 1 : 0;

        $_SESSION[self::BORRADOR] = $borrador;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function tomarBorrador(): ?array
    {
        Proteccion::iniciarSesion();
        $borrador = $_SESSION[self::BORRADOR] ?? null;
        unset($_SESSION[self::BORRADOR]);

        return is_array($borrador) ? $borrador : null;
    }

    private static function limpiarBorrador(): void
    {
        Proteccion::iniciarSesion();
        unset($_SESSION[self::BORRADOR]);
    }

    private static function guardarFlash(string $mensaje, bool $esError = false): void
    {
        Proteccion::iniciarSesion();
        $_SESSION[self::FLASH] = [
            'tipo' => $esError ? 'error' : 'ok',
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @return array{tipo:string, mensaje:string}
     */
    private static function tomarFlash(): array
    {
        Proteccion::iniciarSesion();
        $flash = $_SESSION[self::FLASH] ?? null;
        unset($_SESSION[self::FLASH]);

        if (!is_array($flash)) {
            return ['tipo' => '', 'mensaje' => ''];
        }

        return [
            'tipo' => ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error',
            'mensaje' => (string) ($flash['mensaje'] ?? ''),
        ];
    }
}
