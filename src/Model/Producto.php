<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Producto (Jeremy Tech MVC).
 * Capa de Datos: solo consultas SQL estructuradas contra la tabla `productos`.
 */
class Producto
{
    /**
     * Orden de presentación de las categorías en la tienda.
     * @var array<string, string>
     */
    public const GRIDS = [
        'Celulares'    => 'grid-celulares',
        'Laptops'      => 'grid-laptops',
        'Audifonos'    => 'grid-audifonos',
        'Accesorios'   => 'grid-accesorios',
        'Smartwatches' => 'grid-smartwatches',
        'Tablets'      => 'grid-tablets',
        'Gaming'       => 'grid-gaming',
    ];

    /**
     * Columnas que consumen la tienda. Comparten una única fuente para que
     * `todos()`, `porId()` y `porSlug()` devuelvan siempre el mismo shape
     * y `hidratar()` no falle con claves ausentes.
     */
    private const COLUMNAS = 'p.id, p.slug, p.categoria, p.nombre, p.modelo, p.codigo_sku, p.especificaciones, '
        . 'p.precio_pen, p.stock, p.stock_minimo, p.activo, p.colores, p.imagen, p.peso_kg, p.dimensiones, '
        . 'p.vendedor_id';

    /**
     * Columnas extra que solo aporta el listado del catálogo (Etapa 6):
     * el dueño de la publicación para poder mostrar "Vendido por".
     */
    private const COLUMNAS_VENDEDOR = ', v.name AS vendedor_nombre, v.verificado AS vendedor_verificado';

    /** LEFT JOIN al dueño (usuarios dados de baja no se muestran). */
    private const JOIN_VENDEDOR = ' LEFT JOIN users v ON v.id = p.vendedor_id AND v.deleted_at IS NULL';

    /**
     * Retorna el catálogo completo, opcionalmente acotado a un vendedor
     * (`?vendedor=<id>` en la tienda).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todos(?int $vendedorId = null): array
    {
        $sql = 'SELECT ' . self::COLUMNAS . self::COLUMNAS_VENDEDOR
             . ' FROM productos p' . self::JOIN_VENDEDOR;

        if ($vendedorId !== null && $vendedorId > 0) {
            $sql .= ' WHERE p.vendedor_id = ?';
            $stmt = conexion()->prepare($sql . ' ORDER BY p.slug');
            $stmt->execute([$vendedorId]);
        } else {
            $stmt = conexion()->prepare($sql . ' ORDER BY p.slug');
            $stmt->execute();
        }

        return array_map(self::hidratar(...), $stmt->fetchAll());
    }

    /**
     * Nombre del vendedor que publica un producto (vacío = la plataforma).
     */
    public static function vendedorDe(array $producto): string
    {
        $nombre = (string) ($producto['vendedorNombre'] ?? '');
        if ($nombre !== '') {
            return $nombre;
        }
        $id = $producto['vendedorId'] ?? null;
        return $id !== null ? Vendedor::nombreDe((int) $id) : '';
    }

    /**
     * Cláusula WHERE (+ parámetros) que acota el panel al vendedor actual.
     * Un admin ve todo; un vendedor solo lo suyo. Se concatena tras un
     * `WHERE 1=1` para que el caller no tenga que distinguir AND/WHERE.
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    public static function alcancePanel(): array
    {
        if (!Administrador::esVendedor()) {
            return ['', []];
        }
        $vendedorId = Administrador::idSesion();
        if ($vendedorId === null) {
            return ['', []];
        }
        return [' AND p.vendedor_id = ? ', [$vendedorId]];
    }

    /**
     * ¿Puede el usuario del panel gestionar este producto?
     * Un admin puede con cualquiera; un vendedor solo con los suyos.
     */
    public static function puedeGestionar(string $slug): bool
    {
        if (!Administrador::esVendedor()) {
            return true;
        }
        $vendedorId = Administrador::idSesion();
        if ($vendedorId === null) {
            return false;
        }

        $stmt = conexion()->prepare('SELECT vendedor_id FROM productos WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $dueno = $stmt->fetchColumn();

        return $dueno !== false && $dueno !== null && (int) $dueno === $vendedorId;
    }

    /**
     * Lista de vendedores para el selector del formulario de producto.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function vendedores(): array
    {
        return conexion()->query(
            "SELECT id, name, email FROM users
              WHERE rol = 'vendedor' AND deleted_at IS NULL AND activo = 1
              ORDER BY name"
        )->fetchAll();
    }

    /**
     * Busca un producto por su clave primaria numérica.
     * @return array<string, mixed>|null
     */
    public static function porId(int $id): ?array
    {
        $stmt = conexion()->prepare('SELECT ' . self::COLUMNAS . ' FROM productos p WHERE p.id = ?');
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ? self::hidratar($fila) : null;
    }

    /**
     * Busca un producto por su slug (identificador único legible).
     * @return array<string, mixed>|null
     */
    public static function porSlug(string $slug): ?array
    {
        $stmt = conexion()->prepare('SELECT ' . self::COLUMNAS . ' FROM productos p WHERE p.slug = ?');
        $stmt->execute([$slug]);
        $fila = $stmt->fetch();

        return $fila ? self::hidratar($fila) : null;
    }

    /**
     * Agrupa las variantes bajo su nombre comercial base, por categoría,
     * respetando el orden de GRIDS (espejo de la antigua lógica JS).
     * @return array<string, array<int, array<int, array<string, mixed>>>> categoria => grupos de variantes
     */
    public static function gruposPorCategoria(?int $vendedorId = null): array
    {
        $catalogo = self::todos($vendedorId);
        $grupos = [];

        foreach (array_keys(self::GRIDS) as $categoria) {
            $nombresVistos = [];
            foreach ($catalogo as $p) {
                if ($p['categoria'] !== $categoria || isset($nombresVistos[$p['nombre']])) {
                    continue;
                }
                $nombresVistos[$p['nombre']] = true;
                $grupo = array_values(array_filter(
                    $catalogo,
                    fn ($item) => $item['categoria'] === $categoria && $item['nombre'] === $p['nombre']
                ));
                $grupos[$categoria][] = $grupo;
            }
        }
        return $grupos;
    }

    /**
     * Mapa productoId => precio más barato entre sus tramos de volumen.
     * Una sola consulta para todo el catálogo: la tarjeta muestra "desde S/ x"
     * sin disparar N queries.
     *
     * @return array<int, float>
     */
    public static function preciosDesde(): array
    {
        $stmt = conexion()->query(
            'SELECT producto_id, MIN(precio_unitario) AS desde
               FROM precios_volumen
              GROUP BY producto_id'
        );

        $mapa = [];
        foreach ($stmt->fetchAll() as $fila) {
            $mapa[(int) $fila['producto_id']] = (float) $fila['desde'];
        }
        return $mapa;
    }

    /**
     * Precio unitario efectivo de una línea del carrito (Etapa 7).
     *
     * Es la ÚNICA implementación de la regla de precio de la tienda: la
     * ficha, el carrito y el pedido deben pasar por aquí para no divergir.
     *
     *   unitario = precio_pen + variante.precio_extra
     *   si la cantidad cae en un tramo de volumen, el tramo sustituye el
     *   precio_pen base y el extra de la variante se suma encima.
     *
     * @return array{
     *     producto: array<string, mixed>,
     *     variante: array<string, mixed>|null,
     *     tramos: array<int, array<string, mixed>>,
     *     tramo: array<string, mixed>|null,
     *     precio: float
     * }|null null si el producto no existe
     */
    public static function precioDe(int $productoId, ?int $varianteId, int $cantidad): ?array
    {
        $producto = self::porId($productoId);
        if ($producto === null) {
            return null;
        }

        $variante = null;
        if ($varianteId !== null && $varianteId > 0) {
            $variante = ProductoVariante::porId($varianteId);
            // Una variante de otro producto no debe poder colarse en la línea.
            if ($variante === null || (int) $variante['productoId'] !== $productoId) {
                $variante = null;
            }
        }

        $tramos = PrecioVolumen::deProducto($productoId);
        $extra = $variante !== null ? (float) $variante['precioExtra'] : 0.0;
        $calculado = PrecioVolumen::precioUnitario(
            (float) $producto['precioPEN'],
            $extra,
            $tramos,
            max(1, $cantidad)
        );

        return [
            'producto' => $producto,
            'variante' => $variante,
            'tramos'   => $tramos,
            'tramo'    => $calculado['tramoId'] !== null
                ? PrecioVolumen::porId($calculado['tramoId'])
                : null,
            'precio'   => $calculado['precio'],
        ];
    }

    /**
     * Estilo inline para un indicador de color, aplicando borde sutil a tonos claros.
     */
    public static function colorStyle(string $color): string
    {
        $claros = ['#FFFFFF', '#F5F5DC', '#C0C0C0', '#E8D5C4', '#E8D5B7', '#FFB6C1'];
        $border = in_array(strtoupper($color), array_map('strtoupper', $claros), true)
            ? ' border: 2px solid #ddd'
            : '';
        return ' style="background:' . $color . ';' . $border . '"';
    }

    /**
     * Texto de índice para el filtro de búsqueda del catálogo (atributo data-search).
     * @param array<int, array<string, mixed>> $grupo Variantes que comparten tarjeta.
     */
    public static function textoBusqueda(array $grupo): string
    {
        $terminos = [];
        foreach ($grupo as $p) {
            $terminos[] = strtolower(
                $p['nombre'] . ' ' . $p['especificaciones'] . ' ' . $p['categoria']
                . ' ' . ($p['vendedorNombre'] ?? '')
            );
        }
        return htmlspecialchars(implode(' ', $terminos), ENT_QUOTES);
    }

    /**
     * Convierte una fila SQL al mismo shape que usaba el array estático histórico.
     * `precio_pen` es DECIMAL(10,2) desde la Etapa 3: se expone como float.
     */
    private static function hidratar(array $fila): array
    {
        return [
            'id'              => (int) $fila['id'],
            'slug'            => (string) $fila['slug'],
            'categoria'       => $fila['categoria'],
            'nombre'          => $fila['nombre'],
            'modelo'          => $fila['modelo'] !== null ? (string) $fila['modelo'] : null,
            'codigoSKU'       => $fila['codigo_sku'] !== null ? (string) $fila['codigo_sku'] : null,
            'especificaciones' => $fila['especificaciones'],
            'precioPEN'       => (float) $fila['precio_pen'],
            'stock'           => (int) $fila['stock'],
            'stockMinimo'     => (int) $fila['stock_minimo'],
            'activo'          => (int) $fila['activo'],
            'colores'         => json_decode($fila['colores'] ?: '[]', true) ?: [],
            'imagen'          => $fila['imagen'],
            'pesoKg'          => $fila['peso_kg'] !== null ? (float) $fila['peso_kg'] : null,
            'dimensiones'     => $fila['dimensiones'] !== null ? (string) $fila['dimensiones'] : null,
            // Etapa 6: dueño de la publicación (solo lo aporta el listado).
            'vendedorId'         => isset($fila['vendedor_id']) && $fila['vendedor_id'] !== null
                ? (int) $fila['vendedor_id'] : null,
            'vendedorNombre'     => (string) ($fila['vendedor_nombre'] ?? ''),
            'vendedorVerificado' => (int) ($fila['vendedor_verificado'] ?? 0),
        ];
    }
}