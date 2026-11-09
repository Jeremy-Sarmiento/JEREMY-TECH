<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\ImagenProducto;
use Model\Producto;
use Model\ProductoVendedor;
use PHPUnit\Framework\TestCase;

final class DatosImagenesTest extends TestCase
{
    public function testDatosUsanLasImagenesDeLaBdYPermitenEditarElProducto(): void
    {
        ensureTestDb();
        $pdo = conexion();
        $this->assertSame('jeremy_tech_test', $pdo->query('SELECT current_database()')->fetchColumn());
        $esquema = 'jt_imagenes_' . bin2hex(random_bytes(6));
        $pdo->exec('CREATE SCHEMA ' . $esquema);
        try {
            $pdo->exec('SET search_path TO ' . $esquema);
            $pdo->exec(file_get_contents(BASE_PATH . '/database/jeremy_tech_estructura.sql'));
            $pdo->exec(file_get_contents(BASE_PATH . '/database/jeremy_tech_datos.sql'));
            $pdo->exec(file_get_contents(BASE_PATH . '/database/seed-producto-imagenes.sql'));
            $productos = $pdo->query('SELECT p.slug, p.imagen FROM productos p
                JOIN producto_imagenes i ON i.producto_id = p.id ORDER BY p.id')->fetchAll(PDO::FETCH_ASSOC);
            $this->assertCount(30, $productos);
            foreach ($productos as $producto) {
                $url = '?ruta=producto/imagen&slug=' . rawurlencode($producto['slug']);
                $this->assertSame($url, $producto['imagen']);
                $this->assertSame($url, Producto::urlImagen($producto));
                $imagen = ImagenProducto::deSlug($producto['slug']);
                $this->assertNotNull($imagen);
                $this->assertSame('image/webp', $imagen['mime']);
                $this->assertSame('image/webp', getimagesizefromstring($imagen['datos'])['mime']);
            }
            $this->assertSame(2, (int) $pdo->query("SELECT COUNT(*) FROM productos WHERE imagen = 'img/producto-sin-imagen.svg'")->fetchColumn());

            $slug = 'sony-wh-1000xm5';
            $datos = [
                'nombre' => 'Sony WH-1000XM5', 'categoria' => 'Audifonos', 'precio_pen' => 2499,
                'stock' => 13, 'stock_original' => 13, 'stock_minimo' => 5,
                'imagen' => '?ruta=producto/imagen&slug=' . $slug,
            ];
            $resultado = ProductoVendedor::guardar(2, $datos, $slug);
            $this->assertTrue($resultado['ok'], $resultado['error']);
            foreach (['?ruta=admin/productos/eliminar', '?ruta=producto/imagen&slug=sony&extra=1', '?ruta=producto/imagen&slug=<script>'] as $invalida) {
                $resultado = ProductoVendedor::guardar(2, array_replace($datos, ['imagen' => $invalida]), $slug);
                $this->assertFalse($resultado['ok'], $invalida);
            }
            $this->assertSame($datos['imagen'], ProductoVendedor::porSlug(2, $slug)['imagen']);
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pdo->exec('SET search_path TO public');
            $pdo->exec('DROP SCHEMA ' . $esquema . ' CASCADE');
        }
    }
}
