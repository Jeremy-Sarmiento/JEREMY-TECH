<?php
declare(strict_types=1);

namespace Controller;

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
     * Página principal: catálogo por categoría.
     */
    public function catalogo(): void
    {
        // (Etapa 6) `?vendedor=<id>` acota el catálogo a un vendedor concreto.
        // Un id inexistente o inexistente cae al catálogo completo.
        $vendedorFiltro = null;
        $idVendedor = (int) ($_GET['vendedor'] ?? 0);
        if ($idVendedor > 0) {
            $vendedorFiltro = \Model\Vendedor::porId($idVendedor);
        }

        $gruposPorCategoria = Producto::gruposPorCategoria($vendedorFiltro !== null ? $idVendedor : null);
        $catalogoJson = json_encode(
            Producto::todos($vendedorFiltro !== null ? $idVendedor : null),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $grids = Producto::GRIDS;

        // (Etapa 7) "desde S/ x" en la tarjeta: un único mapa para todo el
        // catálogo, en lugar de una consulta por producto.
        $precioDesde = Producto::preciosDesde();

        $rutaVista = 'catalogo';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/catalogo.php';
        require BASE_PATH . '/views/layout/footer.php';
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
        if ($producto === null || (int) $producto['activo'] !== 1) {
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

        $rutaVista = 'producto';

        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/detalle.php';
        require BASE_PATH . '/views/layout/footer.php';
    }
}