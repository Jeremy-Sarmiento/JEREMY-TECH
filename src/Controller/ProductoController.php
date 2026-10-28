<?php
declare(strict_types=1);

namespace Controller;

use Model\Cuenta;
use Model\Favorito;
use Model\PrecioVolumen;
use Model\Producto;
use Model\ProductoVariante;

/**
 * Controlador de Productos (Jeremy Tech MVC).
 * Capa de Negocio: prepara los datos del catálogo y delega la renderización.
 */
class ProductoController
{
    /**
     * Tarjetas (grupos de variantes) por página del catálogo.
     */
    private const POR_PAGINA = 12;

    /**
     * Órdenes disponibles, en el mismo orden del desplegable.
     *
     * @var array<string, string>
     */
    private const ORDENES = [
        'relevancia'  => 'Relevancia',
        'nombre'      => 'Nombre (A-Z)',
        'precio_asc'  => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
    ];

    /**
     * Página principal: catálogo por categoría.
     */
    public function catalogo(): void
    {
        // (Etapa 6) `?vendedor=<id>` acota el catálogo a un vendedor concreto.
        // Un id inexistente cae al catálogo completo.
        $idVendedor = isset($_GET['vendedor']) && is_numeric($_GET['vendedor'])
            ? (int) $_GET['vendedor'] : 0;
        $vendedorFiltro = $idVendedor > 0 ? \Model\Vendedor::porId($idVendedor) : null;

        // Búsqueda por nombre/modelo/SKU/categoría, acotada opcionalmente
        // al vendedor indicado en la URL.
        $q = isset($_GET['q']) && is_scalar($_GET['q']) ? trim((string) $_GET['q']) : '';
        $q = mb_substr($q, 0, 100);

        // Filtros del catálogo (Bloque 4). Se validan aquí para que una URL
        // manipulada no llegue al modelo con valores fuera de rango.
        $categoria = isset($_GET['categoria']) && is_scalar($_GET['categoria'])
            ? trim((string) $_GET['categoria']) : '';
        if ($categoria !== '') {
            $categoriaCanonica = null;
            foreach (array_keys(Producto::GRIDS) as $categoriaValida) {
                if (mb_strtolower($categoriaValida) === mb_strtolower($categoria)) {
                    $categoriaCanonica = $categoriaValida;
                    break;
                }
            }
            $categoria = $categoriaCanonica ?? '';
        }

        $marcasDisponibles = Producto::marcasDisponibles();
        $marca = isset($_GET['marca']) && is_scalar($_GET['marca'])
            ? mb_substr(trim((string) $_GET['marca']), 0, 80) : '';
        if ($marca !== '') {
            $marcaCanonica = null;
            foreach ($marcasDisponibles as $marcaValida) {
                if (mb_strtolower($marcaValida['nombre']) === mb_strtolower($marca)) {
                    $marcaCanonica = $marcaValida['nombre'];
                    break;
                }
            }
            $marca = $marcaCanonica ?? '';
        }

        $precioMin = self::precioFiltro($_GET['precio_min'] ?? null);
        $precioMax = self::precioFiltro($_GET['precio_max'] ?? null);
        if ($precioMin !== null && $precioMax !== null && $precioMin > $precioMax) {
            [$precioMin, $precioMax] = [$precioMax, $precioMin];
        }

        $orden = isset($_GET['orden']) && is_scalar($_GET['orden'])
            ? trim((string) $_GET['orden']) : 'relevancia';
        if (!isset(self::ORDENES[$orden])) {
            $orden = 'relevancia';
        }

        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina'])
            ? max(1, (int) $_GET['pagina']) : 1;

        $filtros = [
            'q' => $q,
            'vendedor' => $vendedorFiltro !== null ? $idVendedor : null,
            'categoria' => $categoria,
            'marca' => $marca,
            'precio_min' => $precioMin,
            'precio_max' => $precioMax,
            'orden' => $orden,
        ];

        $resultado = Producto::catalogoPaginado($filtros, self::POR_PAGINA, $pagina);
        $productos = $resultado['productos'];
        $gruposPorCategoria = $resultado['gruposPorCategoria'];
        $totalProductos = $resultado['totalProductos'];
        $totalGrupos = $resultado['totalGrupos'];
        $pagina = $resultado['pagina'];
        $totalPaginas = $resultado['totalPaginas'];
        $porPagina = $resultado['porPagina'];
        $desdeGrupo = $resultado['desdeGrupo'];
        $hastaGrupo = $resultado['hastaGrupo'];

        $hayFiltrosSinBusqueda = $vendedorFiltro !== null
            || $categoria !== ''
            || $marca !== ''
            || $precioMin !== null
            || $precioMax !== null
            || $orden !== 'relevancia';
        $hayFiltros = $q !== '' || $hayFiltrosSinBusqueda;

        $catalogoJson = json_encode(
            $productos,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?: '[]';
        $grids = Producto::GRIDS;
        $categoriasDisponibles = array_keys(Producto::GRIDS);
        $ordenesDisponibles = self::ORDENES;

        // (Etapa 7) "desde S/ x" en la tarjeta: un único mapa para todo el
        // catálogo, en lugar de una consulta por producto.
        $precioDesde = Producto::preciosDesde();

        // (Fase 2) Valoración media por producto para la insignia de la tarjeta.
        $resenasResumen = \Model\Resena::resumenDeProductos(array_column($productos, 'id'));

        // Favoritos del cliente: un solo mapa para pintar el corazón de cada
        // tarjeta sin consultar por producto.
        $favoritosIds = [];
        $clienteId = Cuenta::idSesion();
        if ($clienteId !== null) {
            $favoritosIds = Favorito::idsDeCliente($clienteId);
        }

        $rutaVista = 'catalogo';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/catalogo.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    /**
     * Convierte un valor de la URL en un precio válido o null.
     */
    private static function precioFiltro(mixed $valor): ?float
    {
        if ($valor === null || !is_scalar($valor)) {
            return null;
        }

        $valor = trim((string) $valor);
        if ($valor === '' || !is_numeric($valor)) {
            return null;
        }

        $numero = (float) $valor;
        if (!is_finite($numero) || $numero < 0) {
            return null;
        }

        return round($numero, 2);
    }

    /**
     * Sugerencias JSON para el autocompletado del buscador.
     *
     * Ruta: ?ruta=catalogo/sugerencias&q=<texto>
     */
    public function sugerencias(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $q = isset($_GET['q']) && is_scalar($_GET['q']) ? trim((string) $_GET['q']) : '';
        $q = mb_substr($q, 0, 100);

        if (mb_strlen($q) < 2) {
            echo '[]';
            return;
        }

        $vendedorId = isset($_GET['vendedor']) && is_numeric($_GET['vendedor'])
            ? (int) $_GET['vendedor'] : 0;
        $vendedorId = $vendedorId > 0 && \Model\Vendedor::porId($vendedorId) !== null
            ? $vendedorId : null;

        $items = Producto::sugerencias($q, 6, $vendedorId);
        $salida = array_map(static fn (array $p): array => [
            'id' => (int) $p['id'],
            'nombre' => (string) $p['nombre'],
            'modelo' => $p['modelo'],
            'codigoSKU' => $p['codigoSKU'],
            'categoria' => (string) $p['categoria'],
            'precioPEN' => (float) $p['precioPEN'],
            'slug' => (string) $p['slug'],
            'imagen' => (string) $p['imagen'],
        ], $items);

        echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    /**
     * Ficha pública de un producto (Etapa 7): imagen, precio efectivo,
     * variantes, tramos de volumen y ficha técnica.
     *
     * Ruta: ?ruta=producto&slug=<slug>
     */
    public function detalle(): void
    {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $producto = $slug !== '' ? Producto::porSlug($slug) : null;

        // Un slug inexistente o un producto dado de baja no tiene ficha:
        // se vuelve al catálogo en lugar de servir una página rota.
        if ($producto === null || (int) $producto['activo'] !== 1 || !empty($producto['archivado'])) {
            header('Location: ?ruta=catalogo');
            exit;
        }

        $pdo = conexion();

        // Marca y garantía NO viajan en Producto::COLUMNAS (son joins
        // puntuales del catálogo del panel), así que se piden aquí.
        $stmt = $pdo->prepare(
            'SELECT m.nombre AS marca, g.nombre AS garantia
               FROM productos p
          LEFT JOIN marcas m ON m.id = p.marca_id
          LEFT JOIN garantias g ON g.id = p.garantia_id
              WHERE p.slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        $extras = $stmt->fetch() ?: [];

        $variantes = ProductoVariante::deProducto((int) $producto['id']);
        $tramos    = PrecioVolumen::deProducto((int) $producto['id']);

        // Precio mostrado al abrir la ficha: primera variante (si la hay)
        // y cantidad 1. El JS lo recalcula al cambiar de variante o cantidad.
        $varianteInicial = $variantes[0] ?? null;
        $precioInicial = PrecioVolumen::precioUnitario(
            (float) $producto['precioPEN'],
            $varianteInicial !== null ? (float) $varianteInicial['precioExtra'] : 0.0,
            $tramos,
            1
        );

        $favoritosIds = [];
        $clienteId = Cuenta::idSesion();
        if ($clienteId !== null) {
            $favoritosIds = Favorito::idsDeCliente($clienteId);
        }

        // (Fase 2) Opiniones del producto y si este cliente puede escribir
        // la suya (solo tras una compra pagada).
        $resenasProducto = \Model\Resena::deProducto((int) $producto['id']);
        $resumenResenas = \Model\Resena::resumen((int) $producto['id']);
        $puedeOpinar = $clienteId !== null
            && \Model\Resena::puedeOpinar($clienteId, (int) $producto['id']);
        $miResena = null;
        if ($clienteId !== null) {
            $miResena = \Model\Resena::deCliente($clienteId)[(int) $producto['id']] ?? null;
        }
        $avisoResena = \Controller\ResenaController::tomarAviso();

        // (Bloque 3) Reputación del vendedor C2C, visible para el comprador
        // antes de decidir. Los productos de la plataforma (sin dueño) no
        // tienen calificaciones.
        $reputacionVendedor = \Model\CalificacionVendedor::resumen(
            $producto['vendedorId'] !== null ? (int) $producto['vendedorId'] : 0
        );

        // (Bloque 11) Recomendaciones de la ficha: mismas categorías, afinidad
        // de marca y cercanía de precio. Grupos de una sola variante para el
        // partial de tarjeta.
        $recomendados = Producto::recomendados((int) $producto['id'], 4);

        // (Fase 2) Mapa de valoraciones por producto para las tarjetas
        // recomendadas (mismo lookup que el catálogo).
        $resenesResumen = \Model\Resena::resumenDeProductos([(int) $producto['id']]);
        $precioDesde = Producto::preciosDesde();

        $rutaVista = 'producto';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/detalle.php';
        require BASE_PATH . '/views/layout/footer.php';
    }
}