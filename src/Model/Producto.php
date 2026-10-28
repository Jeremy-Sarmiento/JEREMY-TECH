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
        . 'p.precio_pen, p.stock, p.stock_minimo, p.activo, p.archivado_en, p.colores, p.imagen, p.peso_kg, p.dimensiones, '
        . 'p.vendedor_id, p.marca_id';

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
     * Incluye a los revendedores C2C, que también publican en la tienda.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function vendedores(): array
    {
        return conexion()->query(
            "SELECT id, name, email FROM users
              WHERE (rol = 'vendedor' OR tipo = 'C2C')
                AND deleted_at IS NULL AND activo = 1
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
     * Búsqueda simple para el chatbot y otros consumidores internos.
     *
     * Comparte la normalización de términos con `catalogoFiltrado()`: busca
     * coincidencias parciales en nombre, modelo, categoría, especificaciones
     * y SKU de productos activos. Los términos vacíos o demasiado genéricos
     * se descartan para que frases como "busco un laptop" no exijan "un".
     *
     * @return array<int, array<string, mixed>>
     */
    public static function buscar(string $texto, int $limite = 3): array
    {
        $terminos = self::terminosBusqueda($texto);
        if ($terminos === []) {
            return [];
        }

        $limite = max(1, min(10, $limite));

        $condiciones = [];
        $parametros = [];
        foreach ($terminos as $termino) {
            $condiciones[] = "LOWER(CONCAT_WS(' ', p.nombre, p.modelo, p.categoria, p.especificaciones, p.codigo_sku)) LIKE ?";
            $parametros[] = '%' . $termino . '%';
        }

        $sql = 'SELECT ' . self::COLUMNAS . self::COLUMNAS_VENDEDOR
             . ' FROM productos p' . self::JOIN_VENDEDOR
             . ' WHERE p.activo = 1 AND p.archivado_en IS NULL AND (' . implode(' AND ', $condiciones) . ')'
             . ' ORDER BY p.nombre ASC'
             . ' LIMIT ' . $limite;

        $stmt = conexion()->prepare($sql);
        $stmt->execute($parametros);

        return array_map(self::hidratar(...), $stmt->fetchAll());
    }

    /**
     * Catálogo público con búsqueda de texto y filtros combinados.
     *
     * La búsqueda recorre nombre, modelo, SKU, categoría y especificaciones.
     * Los filtros de categoría, marca, rango de precio, vendedor y orden se
     * aplican en la misma consulta para que puedan combinarse entre sí.
     *
     * @param array{
     *     q?: string,
     *     categoria?: string,
     *     marca?: string,
     *     precio_min?: float|int|string|null,
     *     precio_max?: float|int|string|null,
     *     orden?: string,
     *     vendedor?: int|null,
     *     limite?: int|null
     * } $filtros
     * @return array<int, array<string, mixed>>
     */
    public static function catalogoFiltrado(array $filtros = []): array
    {
        [$whereSql, $parametros, $orderBy] = self::condicionesCatalogo($filtros);

        $sql = 'SELECT ' . self::COLUMNAS . self::COLUMNAS_VENDEDOR
             . ' FROM productos p' . self::JOIN_VENDEDOR
             . ' WHERE ' . $whereSql
             . ' ORDER BY ' . $orderBy;

        $limite = $filtros['limite'] ?? null;
        if (is_numeric($limite) && (int) $limite > 0) {
            $sql .= ' LIMIT ' . max(1, min(50, (int) $limite));
        }

        $stmt = conexion()->prepare($sql);
        $stmt->execute($parametros);

        return array_map(self::hidratar(...), $stmt->fetchAll());
    }

    /**
     * Catálogo paginado por TARJETA, no por fila.
     *
     * Las variantes de un mismo producto comparten una sola tarjeta. Para no
     * partir una tarjeta entre dos páginas, la consulta pagina los grupos
     * (`categoria`, `nombre`) y después trae todas las variantes de los grupos
     * elegidos que cumplen los filtros. El total de productos y el total de
     * páginas se calculan por separado porque un grupo puede tener varias
     * variantes.
     *
     * @param array<string, mixed> $filtros Mismos filtros que `catalogoFiltrado()`.
     * @return array{
     *     productos: array<int, array<string, mixed>>,
     *     gruposPorCategoria: array<string, array<int, array<int, array<string, mixed>>>>,
     *     totalProductos: int,
     *     totalGrupos: int,
     *     pagina: int,
     *     porPagina: int,
     *     totalPaginas: int,
     *     desdeGrupo: int,
     *     hastaGrupo: int
     * }
     */
    public static function catalogoPaginado(array $filtros = [], int $porPagina = 12, int $pagina = 1): array
    {
        [$whereSql, $parametros, $orderBy, $orden] = self::condicionesCatalogo($filtros);
        $porPagina = max(1, min(48, $porPagina));
        $pdo = conexion();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos p WHERE ' . $whereSql);
        $stmt->execute($parametros);
        $totalProductos = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM (SELECT 1 FROM productos p WHERE ' . $whereSql
            . ' GROUP BY p.categoria, p.nombre) grupos'
        );
        $stmt->execute($parametros);
        $totalGrupos = (int) $stmt->fetchColumn();

        $totalPaginas = max(1, (int) ceil($totalGrupos / $porPagina));
        $pagina = max(1, min($pagina, $totalPaginas));
        $offset = ($pagina - 1) * $porPagina;

        $vacio = [
            'productos' => [],
            'gruposPorCategoria' => [],
            'totalProductos' => $totalProductos,
            'totalGrupos' => $totalGrupos,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'totalPaginas' => $totalPaginas,
            'desdeGrupo' => 0,
            'hastaGrupo' => 0,
        ];

        if ($totalGrupos === 0) {
            return $vacio;
        }

        // 1) Grupos de la página actual. El orden por precio usa el precio
        //    mínimo del grupo para que la tarjeta no salte de página.
        $stmt = $pdo->prepare(
            'SELECT p.categoria, p.nombre
               FROM productos p
              WHERE ' . $whereSql . '
              GROUP BY p.categoria, p.nombre
              ORDER BY ' . self::ordenGruposCatalogo($orden) . '
              LIMIT ' . $porPagina . ' OFFSET ' . $offset
        );
        $stmt->execute($parametros);
        $gruposPagina = $stmt->fetchAll();

        if ($gruposPagina === []) {
            return $vacio;
        }

        // 2) Todas las variantes de esos grupos que cumplen los filtros.
        $condiciones = [];
        $parametrosProductos = $parametros;
        foreach ($gruposPagina as $grupo) {
            $condiciones[] = '(p.categoria = ? AND p.nombre = ?)';
            $parametrosProductos[] = (string) $grupo['categoria'];
            $parametrosProductos[] = (string) $grupo['nombre'];
        }

        $stmt = $pdo->prepare(
            'SELECT ' . self::COLUMNAS . self::COLUMNAS_VENDEDOR
            . ' FROM productos p' . self::JOIN_VENDEDOR
            . ' WHERE ' . $whereSql . ' AND (' . implode(' OR ', $condiciones) . ')'
            . ' ORDER BY ' . $orderBy
        );
        $stmt->execute($parametrosProductos);
        $productos = array_map(self::hidratar(...), $stmt->fetchAll());

        return [
            'productos' => $productos,
            'gruposPorCategoria' => self::agruparPorCategoria($productos),
            'totalProductos' => $totalProductos,
            'totalGrupos' => $totalGrupos,
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'totalPaginas' => $totalPaginas,
            'desdeGrupo' => $offset + 1,
            'hastaGrupo' => min($offset + $porPagina, $totalGrupos),
        ];
    }

    /**
     * WHERE, parámetros, ORDER BY y orden normalizado del catálogo público.
     *
     * Es la única fuente de la búsqueda y los filtros: la usan tanto el
     * listado simple (`catalogoFiltrado`) como la paginación por tarjetas.
     *
     * @param array<string, mixed> $filtros
     * @return array{0: string, 1: array<int, mixed>, 2: string, 3: string}
     */
    private static function condicionesCatalogo(array $filtros): array
    {
        $texto = $filtros['q'] ?? '';
        $texto = is_scalar($texto) ? trim((string) $texto) : '';
        $terminos = self::terminosBusqueda($texto);

        $where = ['p.activo = 1', 'p.archivado_en IS NULL'];
        $parametros = [];

        // Un texto sin términos útiles (solo conectores) no debe devolver
        // todo el catálogo como si la búsqueda hubiera funcionado.
        if ($texto !== '' && $terminos === []) {
            $where[] = '1 = 0';
        }

        foreach ($terminos as $termino) {
            $where[] = "LOWER(CONCAT_WS(' ', p.nombre, p.modelo, p.codigo_sku, p.categoria, p.especificaciones)) LIKE ?";
            $parametros[] = '%' . $termino . '%';
        }

        $categoria = $filtros['categoria'] ?? '';
        $categoria = is_scalar($categoria) ? trim((string) $categoria) : '';
        if ($categoria !== '' && isset(self::GRIDS[$categoria])) {
            $where[] = 'p.categoria = ?';
            $parametros[] = $categoria;
        }

        // La marca se filtra por nombre para que la URL sea legible
        // (`?marca=Apple`) y no dependa de un id interno.
        $marca = $filtros['marca'] ?? '';
        $marca = is_scalar($marca) ? trim((string) $marca) : '';
        if ($marca !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM marcas m WHERE m.id = p.marca_id AND m.nombre = ?)';
            $parametros[] = $marca;
        }

        $precioMin = $filtros['precio_min'] ?? null;
        if ($precioMin !== null && $precioMin !== '' && is_numeric($precioMin)) {
            $where[] = 'p.precio_pen >= ?';
            $parametros[] = (float) $precioMin;
        }

        $precioMax = $filtros['precio_max'] ?? null;
        if ($precioMax !== null && $precioMax !== '' && is_numeric($precioMax)) {
            $where[] = 'p.precio_pen <= ?';
            $parametros[] = (float) $precioMax;
        }

        $vendedorId = $filtros['vendedor'] ?? null;
        if (is_numeric($vendedorId) && (int) $vendedorId > 0) {
            $where[] = 'p.vendedor_id = ?';
            $parametros[] = (int) $vendedorId;
        }

        $orden = $filtros['orden'] ?? 'relevancia';
        $orden = is_scalar($orden) ? (string) $orden : 'relevancia';
        $orderBy = match ($orden) {
            'nombre' => 'p.nombre ASC, p.categoria ASC, p.precio_pen ASC',
            'precio_asc' => 'p.precio_pen ASC, p.nombre ASC, p.categoria ASC',
            'precio_desc' => 'p.precio_pen DESC, p.nombre ASC, p.categoria ASC',
            default => 'p.categoria ASC, p.nombre ASC, p.precio_pen ASC',
        };

        return [implode(' AND ', $where), $parametros, $orderBy, $orden];
    }

    /**
     * Orden de los grupos (tarjetas) al paginar. En los órdenes por precio se
     * usa el precio que muestra la tarjeta: el mínimo al ordenar de menor a
     * mayor y el máximo al ordenar de mayor a menor (la primera variante del
     * grupo es la que abre la tarjeta).
     */
    private static function ordenGruposCatalogo(string $orden): string
    {
        return match ($orden) {
            'nombre' => 'p.nombre ASC, p.categoria ASC',
            'precio_asc' => 'MIN(p.precio_pen) ASC, p.nombre ASC, p.categoria ASC',
            'precio_desc' => 'MAX(p.precio_pen) DESC, p.nombre ASC, p.categoria ASC',
            default => 'p.categoria ASC, p.nombre ASC',
        };
    }

    /**
     * Marcas con publicaciones activas, para el desplegable del catálogo.
     *
     * @return array<int, array{nombre: string, total: int}>
     */
    public static function marcasDisponibles(): array
    {
        $stmt = conexion()->query(
            'SELECT m.nombre, COUNT(*) AS total
               FROM productos p
               JOIN marcas m ON m.id = p.marca_id
              WHERE p.activo = 1 AND p.archivado_en IS NULL
              GROUP BY m.id, m.nombre
              ORDER BY m.nombre'
        );

        return array_map(static fn (array $fila): array => [
            'nombre' => (string) $fila['nombre'],
            'total' => (int) $fila['total'],
        ], $stmt->fetchAll());
    }

    /**
     * Agrupa un listado de productos por categoría, juntando variantes que
     * comparten el mismo nombre comercial. Respeta el orden de GRIDS.
     *
     * @param array<int, array<string, mixed>> $catalogo
     * @return array<string, array<int, array<int, array<string, mixed>>>>
     */
    public static function agruparPorCategoria(array $catalogo): array
    {
        $grupos = [];

        foreach (array_keys(self::GRIDS) as $categoria) {
            $nombresVistos = [];
            foreach ($catalogo as $p) {
                if (($p['categoria'] ?? '') !== $categoria || isset($nombresVistos[$p['nombre']])) {
                    continue;
                }
                $nombresVistos[$p['nombre']] = true;
                $grupo = array_values(array_filter(
                    $catalogo,
                    static fn (array $item): bool => ($item['categoria'] ?? '') === $categoria
                        && ($item['nombre'] ?? '') === $p['nombre']
                ));
                $grupos[$categoria][] = $grupo;
            }
        }

        return $grupos;
    }

    /**
     * Sugerencias breves para el autocompletado del buscador.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sugerencias(string $texto, int $limite = 6, ?int $vendedorId = null): array
    {
        if (self::terminosBusqueda($texto) === []) {
            return [];
        }

        return self::catalogoFiltrado([
            'q' => $texto,
            'orden' => 'nombre',
            'limite' => max(1, min(10, $limite)),
            'vendedor' => $vendedorId,
        ]);
    }

    /**
     * Recomendaciones para la ficha pública: productos de la misma categoría
     * (excluyendo la publicación actual) ordenados por afinidad de marca y
     * cercanía de precio. Devuelve grupos de una sola variante (la mejor
     * candidata del producto recomendado), listos para el partial de tarjeta.
     */
    public static function recomendados(int $productoId, int $limite = 4): array
    {
        $actual = self::porId($productoId);
        if ($actual === null) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT ' . self::COLUMNAS . self::COLUMNAS_VENDEDOR
            . ' FROM productos p' . self::JOIN_VENDEDOR
            . ' WHERE p.activo = 1 AND p.archivado_en IS NULL AND p.categoria = ?'
            . ' ORDER BY p.nombre ASC, p.id ASC'
        );
        $stmt->execute([(string) $actual['categoria']]);
        $filas = $stmt->fetchAll();

        $candidatos = [];
        foreach ($filas as $fila) {
            $candidato = self::hidratar($fila);
            if ((int) $candidato['id'] === (int) $actual['id']) {
                continue;
            }
            if ((string) $candidato['nombre'] === (string) $actual['nombre']) {
                continue;
            }
            $candidatos[] = $candidato;
        }

        usort($candidatos, static fn (array $a, array $b): int => self::puntuacionRecomendado($b, $actual) <=> self::puntuacionRecomendado($a, $actual));

        $recomendados = [];
        $vistosNombres = [];
        foreach ($candidatos as $candidato) {
            if (isset($vistosNombres[$candidato['nombre']])) {
                continue;
            }
            $vistosNombres[$candidato['nombre']] = true;
            $recomendados[] = [$candidato];
            if (count($recomendados) >= max(1, min(12, $limite))) {
                break;
            }
        }

        return $recomendados;
    }

    private static function puntuacionRecomendado(array $item, array $actual): float
    {
        $mismaMarca = ((int) ($actual['marcaId'] ?? 0)) > 0
            && (int) ($item['marcaId'] ?? 0) === (int) ($actual['marcaId'] ?? 0);
        $cercanoPrecio = 1.0 / (1.0 + abs((float) $item['precioPEN'] - (float) $actual['precioPEN']));

        return ($mismaMarca ? 10.0 : 0.0) + $cercanoPrecio;
    }

    /**
     * Agrupa las variantes bajo su nombre comercial base, por categoría,
     * respetando el orden de GRIDS (espejo de la antigua lógica JS).
     * @return array<string, array<int, array<int, array<string, mixed>>>> categoria => grupos de variantes
     */
    public static function gruposPorCategoria(?int $vendedorId = null): array
    {
        return self::agruparPorCategoria(self::catalogoFiltrado([
            'vendedor' => $vendedorId,
        ]));
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
        if ($producto === null || !empty($producto['archivado'])) {
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
     * Incluye nombre, modelo, SKU, especificaciones, categoría y vendedor.
     * @param array<int, array<string, mixed>> $grupo Variantes que comparten tarjeta.
     */
    public static function textoBusqueda(array $grupo): string
    {
        $terminos = [];
        foreach ($grupo as $p) {
            $terminos[] = strtolower(
                $p['nombre'] . ' ' . ($p['modelo'] ?? '') . ' ' . ($p['codigoSKU'] ?? '')
                . ' ' . $p['especificaciones'] . ' ' . $p['categoria']
                . ' ' . ($p['vendedorNombre'] ?? '')
            );
        }
        return htmlspecialchars(implode(' ', $terminos), ENT_QUOTES);
    }

    /**
     * Normaliza un texto libre y devuelve hasta cinco términos útiles.
     *
     * Se descartan artículos, muletillas y palabras genéricas para que
     * frases como "busco un laptop" exijan únicamente "laptop" al producto.
     *
     * @return array<int, string>
     */
    private static function terminosBusqueda(string $texto): array
    {
        $texto = trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $texto));
        if ($texto === '') {
            return [];
        }

        $terminos = preg_split('/\s+/u', mb_strtolower($texto), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terminos = array_map(
            static fn (string $termino): string => strtr($termino, [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
                'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
                'ñ' => 'n',
            ]),
            $terminos
        );
        $ignorar = [
            'a', 'al', 'algo', 'algun', 'alguna', 'alguno', 'un', 'una', 'unos', 'unas',
            'el', 'la', 'los', 'las', 'de', 'del', 'para', 'por', 'con', 'que',
            'me', 'mi', 'mis', 'tu', 'tus', 'su', 'sus', 'y', 'o',
            'busco', 'buscar', 'quiero', 'quisiera', 'necesito', 'tienes', 'tiene',
            'hay', 'ver', 'mostrar', 'muestrame', 'recomienda', 'recomiendas',
            'precio', 'precios', 'cuanto', 'cuanta', 'cuesta', 'cuestan', 'costo',
            'producto', 'productos', 'catalogo',
        ];
        $terminos = array_values(array_filter(
            $terminos,
            static fn (string $termino): bool => mb_strlen($termino) >= 2
                && !in_array($termino, $ignorar, true)
        ));

        return array_slice($terminos, 0, 5);
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
            'marcaId'         => (int) ($fila['marca_id'] ?? 0),
            'nombre'          => $fila['nombre'],
            'modelo'          => $fila['modelo'] !== null ? (string) $fila['modelo'] : null,
            'codigoSKU'       => $fila['codigo_sku'] !== null ? (string) $fila['codigo_sku'] : null,
            'especificaciones' => $fila['especificaciones'],
            'precioPEN'       => (float) $fila['precio_pen'],
            'stock'           => (int) $fila['stock'],
            'stockMinimo'     => (int) $fila['stock_minimo'],
            'activo'          => (int) $fila['activo'],
            // Bloque 2: una publicación archivada nunca se vende, aunque un
            // administrador la haya vuelto a marcar como activa.
            'archivado'       => $fila['archivado_en'] !== null,
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