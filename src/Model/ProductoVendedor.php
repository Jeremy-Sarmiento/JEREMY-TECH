<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo ProductoVendedor (Jeremy Tech) — Fase 3.
 *
 * Capa de datos del panel "Mis productos" de la tienda. Un vendedor C2C
 * publica sobre la misma tabla `productos` del catálogo, pero TODAS las
 * consultas de este modelo están acotadas por `vendedor_id`. La autorización
 * de rol la hace el controlador; aquí la propiedad es una segunda barrera.
 *
 * No hay tablas nuevas: se reutilizan `productos`, `categorias`, `marcas` y
 * `garantias`.
 */
final class ProductoVendedor
{
    /** Imagen usada cuando la publicación no tiene una propia. */
    public const IMAGEN_PREDETERMINADA = 'img/producto-sin-imagen.svg';

    /** Límite de colores distintos por publicación. */
    private const MAX_COLORES = 10;

    /**
     * Productos del vendedor, con sus métricas de venta.
     *
     * Por defecto excluye las publicaciones archivadas; con `$archivados`
     * en true devuelve solo las archivadas (para poder restaurarlas).
     * `eliminable` es false cuando el producto tiene ventas, movimientos de
     * inventario o reseñas: borrarlo dejaría huérfano ese historial.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listar(int $vendedorId, string $busqueda = '', bool $archivados = false): array
    {
        if ($vendedorId <= 0) {
            return [];
        }

        $sql = 'SELECT p.id, p.slug, p.nombre, p.modelo, p.categoria, p.codigo_sku,
                       p.precio_pen, p.stock, p.stock_minimo, p.activo, p.imagen,
                       p.archivado_en, p.created_at, p.updated_at,
                       m.nombre AS marca_nombre, g.nombre AS garantia_nombre,
                       COALESCE(v.unidades, 0) AS unidades_vendidas,
                       (SELECT COUNT(*) FROM detalle_pedido dp2
                         WHERE dp2.producto_id = p.id) AS lineas_vendidas,
                       (SELECT COUNT(*) FROM movimientos_inventario mi
                         WHERE mi.producto_id = p.id) AS movimientos_total,
                       (SELECT COUNT(*) FROM resenas r
                         WHERE r.producto_id = p.id) AS resenas_total
                  FROM productos p
                  LEFT JOIN marcas m ON m.id = p.marca_id
                  LEFT JOIN garantias g ON g.id = p.garantia_id
                  LEFT JOIN (
                        SELECT dp.producto_id, SUM(dp.cantidad) AS unidades
                          FROM detalle_pedido dp
                          JOIN pedidos pe ON pe.id = dp.pedido_id
                         WHERE dp.vendedor_id = ?
                           AND pe.estado <> \'Cancelado\'
                         GROUP BY dp.producto_id
                  ) v ON v.producto_id = p.id
                 WHERE p.vendedor_id = ?
                   AND p.archivado_en IS ' . ($archivados ? 'NOT NULL' : 'NULL');
        $parametros = [$vendedorId, $vendedorId];

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $sql .= " AND (p.nombre LIKE ? OR p.modelo LIKE ? OR p.codigo_sku LIKE ?)";
            $comodin = '%' . $busqueda . '%';
            array_push($parametros, $comodin, $comodin, $comodin);
        }

        $sql .= $archivados
            ? ' ORDER BY p.archivado_en DESC, p.nombre ASC'
            : ' ORDER BY p.activo DESC, p.nombre ASC';

        $stmt = conexion()->prepare($sql);
        $stmt->execute($parametros);

        return array_map(static function (array $fila): array {
            $fila['id'] = (int) $fila['id'];
            $fila['precio_pen'] = (float) $fila['precio_pen'];
            $fila['stock'] = (int) $fila['stock'];
            $fila['stock_minimo'] = (int) $fila['stock_minimo'];
            $fila['activo'] = (int) $fila['activo'];
            $fila['unidades_vendidas'] = (int) $fila['unidades_vendidas'];
            $fila['archivado_en'] = $fila['archivado_en'] !== null ? (string) $fila['archivado_en'] : null;
            $fila['eliminable'] = (
                (int) $fila['lineas_vendidas']
                + (int) $fila['movimientos_total']
                + (int) $fila['resenas_total']
            ) === 0;
            unset($fila['lineas_vendidas'], $fila['movimientos_total'], $fila['resenas_total']);
            return $fila;
        }, $stmt->fetchAll());
    }

    /**
     * Totales del panel (independientes del filtro de búsqueda).
     *
     * Los indicadores comerciales excluyen las publicaciones archivadas;
     * `archivados` se informa aparte para la pestaña de archivo.
     *
     * @return array{total:int, activos:int, ocultos:int, stock_bajo:int, valor:float, archivados:int}
     */
    public static function resumen(int $vendedorId): array
    {
        if ($vendedorId <= 0) {
            return [
                'total' => 0, 'activos' => 0, 'ocultos' => 0,
                'stock_bajo' => 0, 'valor' => 0.0, 'archivados' => 0,
            ];
        }

        $stmt = conexion()->prepare(
            'SELECT COALESCE(SUM(archivado_en IS NULL), 0) AS total,
                    COALESCE(SUM(archivado_en IS NULL AND activo = 1), 0) AS activos,
                    COALESCE(SUM(archivado_en IS NULL AND activo = 0), 0) AS ocultos,
                    COALESCE(SUM(archivado_en IS NULL AND stock <= stock_minimo), 0) AS stock_bajo,
                    COALESCE(SUM(CASE WHEN archivado_en IS NULL THEN precio_pen * stock ELSE 0 END), 0) AS valor,
                    COALESCE(SUM(archivado_en IS NOT NULL), 0) AS archivados
               FROM productos
              WHERE vendedor_id = ?'
        );
        $stmt->execute([$vendedorId]);
        $fila = $stmt->fetch() ?: [];

        return [
            'total'      => (int) ($fila['total'] ?? 0),
            'activos'    => (int) ($fila['activos'] ?? 0),
            'ocultos'    => (int) ($fila['ocultos'] ?? 0),
            'stock_bajo' => (int) ($fila['stock_bajo'] ?? 0),
            'valor'      => (float) ($fila['valor'] ?? 0),
            'archivados' => (int) ($fila['archivados'] ?? 0),
        ];
    }

    /**
     * Un producto del vendedor por slug (para editar). Devuelve null si no
     * existe o pertenece a otra persona.
     *
     * @return array<string, mixed>|null
     */
    public static function porSlug(int $vendedorId, string $slug): ?array
    {
        if ($vendedorId <= 0 || $slug === '') {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT p.*, m.nombre AS marca_nombre, g.nombre AS garantia_nombre
               FROM productos p
               LEFT JOIN marcas m ON m.id = p.marca_id
               LEFT JOIN garantias g ON g.id = p.garantia_id
              WHERE p.vendedor_id = ? AND p.slug = ?
              LIMIT 1'
        );
        $stmt->execute([$vendedorId, $slug]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Crea o actualiza una publicación del vendedor.
     *
     * @param array<string, mixed> $datos Datos del formulario (POST).
     * @param string $slugEditar Slug del producto a editar; vacío = nuevo.
     * @return array{ok:bool, error:string, slug:string}
     */
    public static function guardar(int $vendedorId, array $datos, string $slugEditar = ''): array
    {
        if ($vendedorId <= 0) {
            return ['ok' => false, 'error' => 'La sesión no es válida. Vuelve a iniciar sesión.', 'slug' => ''];
        }

        // Un POST manipulado puede enviar arrays donde se espera texto.
        // Se normaliza a string una sola vez para no provocar avisos de PHP.
        $entrada = [];
        foreach ($datos as $clave => $valor) {
            if (is_string($clave)) {
                $entrada[$clave] = is_scalar($valor) ? (string) $valor : '';
            }
        }
        $datos = $entrada;

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $modelo = trim((string) ($datos['modelo'] ?? ''));
        $categoriaEntrada = trim((string) ($datos['categoria'] ?? ''));
        $sku = trim((string) ($datos['codigo_sku'] ?? ''));
        $especificaciones = trim((string) ($datos['especificaciones'] ?? ''));
        $dimensiones = trim((string) ($datos['dimensiones'] ?? ''));
        $imagen = trim((string) ($datos['imagen'] ?? ''));
        $activo = !empty($datos['activo']) ? 1 : 0;

        if ($nombre === '') {
            return ['ok' => false, 'error' => 'El nombre del producto es obligatorio.', 'slug' => $slugEditar];
        }
        if (mb_strlen($nombre) > 190) {
            return ['ok' => false, 'error' => 'El nombre no puede superar los 190 caracteres.', 'slug' => $slugEditar];
        }
        if (mb_strlen($modelo) > 120) {
            return ['ok' => false, 'error' => 'El modelo no puede superar los 120 caracteres.', 'slug' => $slugEditar];
        }
        if (mb_strlen($dimensiones) > 80) {
            return ['ok' => false, 'error' => 'Las dimensiones no pueden superar los 80 caracteres.', 'slug' => $slugEditar];
        }
        if (mb_strlen($especificaciones) > 5000) {
            return ['ok' => false, 'error' => 'Las especificaciones son demasiado largas.', 'slug' => $slugEditar];
        }

        $categoria = self::categoriaCanonica($categoriaEntrada);
        if ($categoria === null || mb_strlen($categoria) > 60) {
            return ['ok' => false, 'error' => 'Elige una categoría válida.', 'slug' => $slugEditar];
        }

        $marcaId = self::idOpcional($datos['marca_id'] ?? '');
        if ($marcaId === false || ($marcaId !== null && !self::existeMarca($marcaId))) {
            return ['ok' => false, 'error' => 'La marca elegida no es válida.', 'slug' => $slugEditar];
        }

        $garantiaId = self::idOpcional($datos['garantia_id'] ?? '');
        if ($garantiaId === false || ($garantiaId !== null && !self::existeGarantia($garantiaId))) {
            return ['ok' => false, 'error' => 'La garantía elegida no es válida.', 'slug' => $slugEditar];
        }

        $precio = self::decimalNoNegativo($datos['precio_pen'] ?? null, 2);
        if ($precio === null) {
            return ['ok' => false, 'error' => 'Ingresa un precio válido (mayor o igual a 0).', 'slug' => $slugEditar];
        }
        if ($precio > 99999999.99) {
            return ['ok' => false, 'error' => 'El precio es demasiado alto.', 'slug' => $slugEditar];
        }

        $stock = self::enteroNoNegativo($datos['stock'] ?? null);
        if ($stock === null || $stock > 9999999) {
            return ['ok' => false, 'error' => 'El stock debe ser un número entero entre 0 y 9 999 999.', 'slug' => $slugEditar];
        }

        $stockMinimo = self::enteroNoNegativo($datos['stock_minimo'] ?? null);
        if ($stockMinimo === null || $stockMinimo > 9999999) {
            return ['ok' => false, 'error' => 'El stock mínimo debe ser un número entero entre 0 y 9 999 999.', 'slug' => $slugEditar];
        }

        $pesoKg = null;
        $pesoRaw = trim((string) ($datos['peso_kg'] ?? ''));
        if ($pesoRaw !== '') {
            if (!is_numeric($pesoRaw) || (float) $pesoRaw < 0 || (float) $pesoRaw > 99999.999) {
                return ['ok' => false, 'error' => 'El peso debe ser un número mayor o igual a 0.', 'slug' => $slugEditar];
            }
            $pesoKg = round((float) $pesoRaw, 3);
        }

        $colores = self::normalizarColores($datos['colores'] ?? '');
        if ($colores === null) {
            return ['ok' => false, 'error' => 'Los colores deben ser códigos hex (por ejemplo #1A1A2E), separados por coma.', 'slug' => $slugEditar];
        }
        if (strlen($colores) > 255) {
            return ['ok' => false, 'error' => 'Hay demasiados colores para una sola publicación.', 'slug' => $slugEditar];
        }

        $imagen = self::normalizarImagen($imagen);
        if ($imagen === null) {
            return ['ok' => false, 'error' => 'La imagen debe ser una ruta interna (img/...) o una URL http(s) válida.', 'slug' => $slugEditar];
        }

        if ($sku !== '' && mb_strlen($sku) > 60) {
            return ['ok' => false, 'error' => 'El SKU no puede superar los 60 caracteres.', 'slug' => $slugEditar];
        }

        $pdo = conexion();
        $slug = '';
        $esEdicion = $slugEditar !== '';

        if ($esEdicion) {
            $actual = self::porSlug($vendedorId, $slugEditar);
            if ($actual === null) {
                return ['ok' => false, 'error' => 'No tienes permiso sobre ese producto.', 'slug' => ''];
            }
            if (($actual['archivado_en'] ?? null) !== null) {
                return [
                    'ok'    => false,
                    'error' => 'Esta publicación está archivada. Restáurala antes de editarla.',
                    'slug'  => $slugEditar,
                ];
            }
            $slug = (string) $actual['slug'];
        } else {
            $slug = self::slugUnico($nombre);
        }

        // El SKU es UNIQUE global. Se comprueba antes de escribir para dar un
        // mensaje claro en lugar de un error 500 de MySQL.
        if ($sku !== '') {
            $q = $pdo->prepare('SELECT slug FROM productos WHERE codigo_sku = ? AND slug <> ? LIMIT 1');
            $q->execute([$sku, $slug]);
            if ($q->fetch()) {
                return [
                    'ok'    => false,
                    'error' => 'El SKU "' . $sku . '" ya está en uso por otro producto.',
                    'slug'  => $slugEditar,
                ];
            }
        }

        $valores = [
            $categoria, $nombre, $modelo !== '' ? $modelo : null,
            $sku !== '' ? $sku : null, $marcaId, $garantiaId,
            $precio, $stock, $stockMinimo, $activo, $imagen, $especificaciones,
            $pesoKg, $dimensiones !== '' ? $dimensiones : null, $colores,
        ];

        try {
            if ($esEdicion) {
                $stmt = $pdo->prepare(
                    'UPDATE productos
                        SET categoria = ?, nombre = ?, modelo = ?, codigo_sku = ?,
                            marca_id = ?, garantia_id = ?, precio_pen = ?, stock = ?,
                            stock_minimo = ?, activo = ?, imagen = ?, especificaciones = ?,
                            peso_kg = ?, dimensiones = ?, colores = ?
                      WHERE slug = ? AND vendedor_id = ?'
                );
                $stmt->execute(array_merge($valores, [$slug, $vendedorId]));
                if ($stmt->rowCount() === 0 && !self::porSlug($vendedorId, $slug)) {
                    return ['ok' => false, 'error' => 'No se pudo actualizar el producto.', 'slug' => $slugEditar];
                }
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO productos
                        (categoria, nombre, modelo, codigo_sku, marca_id, garantia_id,
                         precio_pen, stock, stock_minimo, activo, imagen, especificaciones,
                         peso_kg, dimensiones, colores, vendedor_id, slug)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute(array_merge($valores, [$vendedorId, $slug]));
            }
        } catch (\PDOException $e) {
            error_log('ProductoVendedor::guardar — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo guardar la publicación. Revisa los datos e inténtalo de nuevo.', 'slug' => $slugEditar];
        }

        return ['ok' => true, 'error' => '', 'slug' => $slug];
    }

    /**
     * Activa u oculta una publicación propia.
     */
    public static function cambiarEstado(int $vendedorId, string $slug): bool
    {
        if ($vendedorId <= 0 || $slug === '') {
            return false;
        }

        $stmt = conexion()->prepare(
            'UPDATE productos SET activo = 1 - activo
              WHERE vendedor_id = ? AND slug = ? AND archivado_en IS NULL'
        );
        $stmt->execute([$vendedorId, $slug]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Actualiza el stock disponible de una publicación propia.
     */
    public static function actualizarStock(int $vendedorId, string $slug, int $stock): bool
    {
        if ($vendedorId <= 0 || $slug === '' || $stock < 0) {
            return false;
        }

        $stmt = conexion()->prepare(
            'UPDATE productos SET stock = ?
              WHERE vendedor_id = ? AND slug = ? AND archivado_en IS NULL'
        );
        $stmt->execute([$stock, $vendedorId, $slug]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        // rowCount() es 0 cuando el stock enviado es el mismo que ya tenía.
        // Se confirma que el producto existe, es propio y no está archivado.
        $producto = self::porSlug($vendedorId, $slug);
        return $producto !== null && ($producto['archivado_en'] ?? null) === null;
    }

    /**
     * Archiva una publicación propia.
     *
     * Archivar la saca del panel y del catálogo, pero conserva sus ventas,
     * reseñas y movimientos de inventario. Es la alternativa segura al
     * borrado cuando el producto ya tiene historial.
     */
    public static function archivar(int $vendedorId, string $slug): bool
    {
        if ($vendedorId <= 0 || $slug === '') {
            return false;
        }

        $stmt = conexion()->prepare(
            'UPDATE productos SET activo = 0, archivado_en = NOW()
              WHERE vendedor_id = ? AND slug = ? AND archivado_en IS NULL'
        );
        $stmt->execute([$vendedorId, $slug]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Restaura una publicación archivada.
     *
     * Vuelve como OCULTA (`activo = 0`) para que el vendedor revise el stock
     * y decida cuándo publicarla otra vez. No reaparece sola en la tienda.
     */
    public static function restaurar(int $vendedorId, string $slug): bool
    {
        if ($vendedorId <= 0 || $slug === '') {
            return false;
        }

        $stmt = conexion()->prepare(
            'UPDATE productos SET activo = 0, archivado_en = NULL
              WHERE vendedor_id = ? AND slug = ? AND archivado_en IS NOT NULL'
        );
        $stmt->execute([$vendedorId, $slug]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina definitivamente una publicación propia SIN historial.
     *
     * Se rechaza si el producto tiene líneas de pedido, movimientos de
     * inventario o reseñas: borrarlo dejaría referencias huérfanas y
     * perdería auditoría. En ese caso el vendedor debe archivarlo.
     *
     * @return array{ok:bool, error:string}
     */
    public static function eliminar(int $vendedorId, string $slug): array
    {
        if ($vendedorId <= 0 || $slug === '') {
            return ['ok' => false, 'error' => 'La sesión no es válida. Vuelve a iniciar sesión.'];
        }

        $producto = self::porSlug($vendedorId, $slug);
        if ($producto === null) {
            return ['ok' => false, 'error' => 'No tienes permiso sobre ese producto.'];
        }

        $productoId = (int) $producto['id'];
        $stmt = conexion()->prepare(
            'SELECT (SELECT COUNT(*) FROM detalle_pedido WHERE producto_id = ?) AS ventas,
                    (SELECT COUNT(*) FROM movimientos_inventario WHERE producto_id = ?) AS movimientos,
                    (SELECT COUNT(*) FROM resenas WHERE producto_id = ?) AS resenas'
        );
        $stmt->execute([$productoId, $productoId, $productoId]);
        $referencias = $stmt->fetch() ?: [];

        if ((int) ($referencias['ventas'] ?? 0) > 0) {
            return [
                'ok'    => false,
                'error' => 'Este producto tiene ventas registradas. Archívalo para conservar el historial.',
            ];
        }
        if ((int) ($referencias['movimientos'] ?? 0) > 0) {
            return [
                'ok'    => false,
                'error' => 'Este producto tiene movimientos de inventario. Archívalo para conservar el historial.',
            ];
        }
        if ((int) ($referencias['resenas'] ?? 0) > 0) {
            return [
                'ok'    => false,
                'error' => 'Este producto tiene reseñas. Archívalo para conservar el historial.',
            ];
        }

        $pdo = conexion();
        try {
            $pdo->beginTransaction();
            // Filas hijas sin valor histórico: carrito, favoritos, variantes
            // y tramos de precio. Se borran antes que el producto por las FK.
            $pdo->prepare('DELETE FROM carrito_items WHERE producto_id = ?')->execute([$productoId]);
            $pdo->prepare('DELETE FROM favoritos WHERE producto_id = ?')->execute([$productoId]);
            $pdo->prepare('DELETE FROM producto_variantes WHERE producto_id = ?')->execute([$productoId]);
            $pdo->prepare('DELETE FROM precios_volumen WHERE producto_id = ?')->execute([$productoId]);
            $pdo->prepare('DELETE FROM productos WHERE id = ? AND vendedor_id = ?')
                ->execute([$productoId, $vendedorId]);
            $pdo->commit();
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ProductoVendedor::eliminar — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo eliminar la publicación. Inténtalo de nuevo.'];
        }

        return ['ok' => true, 'error' => ''];
    }

    /**
     * Variantes de una publicación propia (incluye las inactivas).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function variantesDe(int $vendedorId, string $slug): array
    {
        if ($vendedorId <= 0 || $slug === '') {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT pv.id, pv.nombre_variante, pv.precio_extra, pv.stock, pv.color, pv.activo
               FROM producto_variantes pv
               JOIN productos p ON p.id = pv.producto_id
              WHERE p.vendedor_id = ? AND p.slug = ?
              ORDER BY pv.activo DESC, pv.nombre_variante'
        );
        $stmt->execute([$vendedorId, $slug]);

        return array_map(static function (array $fila): array {
            $fila['id'] = (int) $fila['id'];
            $fila['precio_extra'] = (float) $fila['precio_extra'];
            $fila['stock'] = (int) $fila['stock'];
            $fila['activo'] = (int) $fila['activo'];
            return $fila;
        }, $stmt->fetchAll());
    }

    /**
     * Crea o actualiza una variante de una publicación propia.
     *
     * @param array<string, mixed> $datos Campos del formulario (POST).
     * @return array{ok:bool, error:string}
     */
    public static function guardarVariante(int $vendedorId, string $slug, array $datos): array
    {
        if ($vendedorId <= 0 || $slug === '') {
            return ['ok' => false, 'error' => 'La sesión no es válida. Vuelve a iniciar sesión.'];
        }

        $producto = self::porSlug($vendedorId, $slug);
        if ($producto === null) {
            return ['ok' => false, 'error' => 'No tienes permiso sobre ese producto.'];
        }
        if (($producto['archivado_en'] ?? null) !== null) {
            return ['ok' => false, 'error' => 'Esta publicación está archivada. Restáurala antes de editarla.'];
        }

        // Un POST manipulado puede enviar arrays donde se espera texto.
        $entrada = [];
        foreach ($datos as $clave => $valor) {
            if (is_string($clave)) {
                $entrada[$clave] = is_scalar($valor) ? (string) $valor : '';
            }
        }
        $datos = $entrada;

        $id = (int) ($datos['id'] ?? 0);
        $nombre = trim((string) ($datos['nombre_variante'] ?? ''));
        $extraRaw = trim((string) ($datos['precio_extra'] ?? '0'));
        $stockRaw = trim((string) ($datos['stock'] ?? '0'));
        $color = trim((string) ($datos['color'] ?? ''));
        $activo = !empty($datos['activo']) ? 1 : 0;

        if ($nombre === '') {
            return ['ok' => false, 'error' => 'El nombre de la variante es obligatorio.'];
        }
        if (mb_strlen($nombre) > 120) {
            return ['ok' => false, 'error' => 'El nombre de la variante no puede superar los 120 caracteres.'];
        }
        if ($extraRaw === '' || !is_numeric($extraRaw) || (float) $extraRaw < 0 || (float) $extraRaw > 99999999.99) {
            return ['ok' => false, 'error' => 'El precio extra debe ser un número mayor o igual a 0.'];
        }
        if ($stockRaw === '' || !preg_match('/^\d+$/', $stockRaw) || (int) $stockRaw > 9999999) {
            return ['ok' => false, 'error' => 'El stock de la variante debe ser un entero entre 0 y 9 999 999.'];
        }
        if ($color !== '' && !preg_match('/^#[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?$/', $color)) {
            return ['ok' => false, 'error' => 'El color debe ser hexadecimal, por ejemplo #00D4FF.'];
        }

        $productoId = (int) $producto['id'];
        $pdo = conexion();

        $duplicada = $pdo->prepare(
            'SELECT id FROM producto_variantes
              WHERE producto_id = ? AND nombre_variante = ? AND id <> ? LIMIT 1'
        );
        $duplicada->execute([$productoId, $nombre, $id]);
        if ($duplicada->fetchColumn()) {
            return ['ok' => false, 'error' => 'Ya existe una variante con ese nombre en este producto.'];
        }

        $extra = round((float) $extraRaw, 2);
        $stock = (int) $stockRaw;

        try {
            if ($id > 0) {
                $propia = $pdo->prepare(
                    'SELECT pv.id
                       FROM producto_variantes pv
                       JOIN productos p ON p.id = pv.producto_id
                      WHERE pv.id = ? AND p.vendedor_id = ? AND p.slug = ? LIMIT 1'
                );
                $propia->execute([$id, $vendedorId, $slug]);
                if (!$propia->fetchColumn()) {
                    return ['ok' => false, 'error' => 'Esa variante no pertenece a este producto.'];
                }

                $pdo->prepare(
                    'UPDATE producto_variantes
                        SET nombre_variante = ?, precio_extra = ?, stock = ?, color = ?, activo = ?
                      WHERE id = ?'
                )->execute([$nombre, $extra, $stock, $color, $activo, $id]);
            } else {
                $pdo->prepare(
                    'INSERT INTO producto_variantes
                        (producto_id, nombre_variante, precio_extra, stock, color, activo)
                     VALUES (?, ?, ?, ?, ?, ?)'
                )->execute([$productoId, $nombre, $extra, $stock, $color, $activo]);
            }
        } catch (\PDOException $e) {
            error_log('ProductoVendedor::guardarVariante — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo guardar la variante. Inténtalo de nuevo.'];
        }

        return ['ok' => true, 'error' => ''];
    }

    /**
     * Activa u oculta una variante de una publicación propia.
     */
    public static function cambiarEstadoVariante(int $vendedorId, string $slug, int $id): bool
    {
        if ($vendedorId <= 0 || $slug === '' || $id <= 0) {
            return false;
        }

        $stmt = conexion()->prepare(
            'UPDATE producto_variantes pv
               JOIN productos p ON p.id = pv.producto_id
                SET pv.activo = 1 - pv.activo
              WHERE pv.id = ? AND p.vendedor_id = ? AND p.slug = ?
                AND p.archivado_en IS NULL'
        );
        $stmt->execute([$id, $vendedorId, $slug]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina una variante de una publicación propia.
     *
     * Es seguro aunque la variante se haya vendido: `detalle_pedido` guarda
     * una copia del nombre y del precio, no una FK a esta fila.
     */
    public static function eliminarVariante(int $vendedorId, string $slug, int $id): bool
    {
        if ($vendedorId <= 0 || $slug === '' || $id <= 0) {
            return false;
        }

        $stmt = conexion()->prepare(
            'DELETE pv FROM producto_variantes pv
               JOIN productos p ON p.id = pv.producto_id
              WHERE pv.id = ? AND p.vendedor_id = ? AND p.slug = ?
                AND p.archivado_en IS NULL'
        );
        $stmt->execute([$id, $vendedorId, $slug]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Categorías disponibles para el selector.
     *
     * @return array<int, array<string, string>>
     */
    public static function categorias(): array
    {
        return conexion()->query('SELECT slug, nombre FROM categorias ORDER BY nombre')->fetchAll();
    }

    /**
     * Marcas activas.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function marcas(): array
    {
        return conexion()->query(
            'SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre'
        )->fetchAll();
    }

    /**
     * Garantías activas.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function garantias(): array
    {
        return conexion()->query(
            'SELECT id, nombre FROM garantias WHERE activo = 1 ORDER BY nombre'
        )->fetchAll();
    }

    /**
     * Nombre canónico de una categoría (acepta slug o nombre visible).
     */
    private static function categoriaCanonica(string $valor): ?string
    {
        if ($valor === '') {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT nombre FROM categorias WHERE slug = ? OR nombre = ? LIMIT 1'
        );
        $stmt->execute([$valor, $valor]);
        $nombre = $stmt->fetchColumn();

        return $nombre !== false ? (string) $nombre : null;
    }

    /**
     * @return int|null|false null = sin selección, false = valor inválido
     */
    private static function idOpcional(mixed $valor): int|null|false
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }
        if (!preg_match('/^\d+$/', $texto) || (int) $texto <= 0) {
            return false;
        }
        return (int) $texto;
    }

    private static function existeMarca(int $id): bool
    {
        $stmt = conexion()->prepare('SELECT 1 FROM marcas WHERE id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    private static function existeGarantia(int $id): bool
    {
        $stmt = conexion()->prepare('SELECT 1 FROM garantias WHERE id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    private static function decimalNoNegativo(mixed $valor, int $decimales): ?float
    {
        $texto = trim((string) $valor);
        if ($texto === '' || !is_numeric($texto)) {
            return null;
        }

        $numero = round((float) $texto, $decimales);
        return $numero >= 0 ? $numero : null;
    }

    private static function enteroNoNegativo(mixed $valor): ?int
    {
        $texto = trim((string) $valor);
        if ($texto === '' || !preg_match('/^\d+$/', $texto)) {
            return null;
        }

        $numero = (int) $texto;
        return $numero >= 0 ? $numero : null;
    }

    /**
     * Convierte la entrada de colores a JSON válido. Acepta JSON, una lista
     * separada por comas o puntos y coma, y valida cada código hex.
     */
    private static function normalizarColores(mixed $valor): ?string
    {
        if (is_array($valor)) {
            $lista = $valor;
        } else {
            $texto = trim((string) $valor);
            if ($texto === '') {
                return '[]';
            }

            $decodificado = json_decode($texto, true);
            $lista = is_array($decodificado)
                ? $decodificado
                : (preg_split('/[,;]+/', $texto) ?: []);
        }

        $limpios = [];
        foreach ($lista as $color) {
            if (!is_scalar($color)) {
                return null;
            }
            $color = strtoupper(trim((string) $color));
            if ($color === '') {
                continue;
            }
            if (!preg_match('/^#(?:[0-9A-F]{3}|[0-9A-F]{6})$/', $color)) {
                return null;
            }
            $limpios[$color] = true;
        }

        $limpios = array_keys($limpios);
        if (count($limpios) > self::MAX_COLORES) {
            return null;
        }

        $json = json_encode($limpios, JSON_UNESCAPED_UNICODE);
        return $json !== false ? $json : null;
    }

    /**
     * Valida y normaliza la imagen: ruta interna bajo `img/` o URL http(s).
     */
    private static function normalizarImagen(string $imagen): ?string
    {
        if ($imagen === '') {
            return self::IMAGEN_PREDETERMINADA;
        }
        if (strlen($imagen) > 255 || str_contains($imagen, '..')) {
            return null;
        }
        if (preg_match('#^img/[A-Za-z0-9._/-]+$#', $imagen)) {
            return $imagen;
        }
        if (preg_match('#^https?://[^\s<>"\']+$#i', $imagen)) {
            return $imagen;
        }

        return null;
    }

    /**
     * Slug único a partir del nombre, con sufijo incremental si ya existe.
     */
    private static function slugUnico(string $nombre): string
    {
        $base = self::slugify($nombre);
        if ($base === '') {
            $base = 'producto';
        }

        $slug = $base;
        $pdo = conexion();
        $stmt = $pdo->prepare('SELECT 1 FROM productos WHERE slug = ? LIMIT 1');
        $sufijo = 2;

        while (true) {
            $stmt->execute([$slug]);
            if (!$stmt->fetchColumn()) {
                return $slug;
            }

            $candidato = $base . '-' . $sufijo;
            if (mb_strlen($candidato) > 190) {
                $candidato = mb_substr($base, 0, 180) . '-' . $sufijo;
            }
            $slug = $candidato;
            $sufijo++;
        }
    }

    private static function slugify(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü', 'à', 'è', 'ì', 'ò', 'ù'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto) ?? '';
        return trim($texto, '-');
    }
}
