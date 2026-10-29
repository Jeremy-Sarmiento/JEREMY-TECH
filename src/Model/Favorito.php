<?php

declare(strict_types=1);

namespace Model;

/**
 * Modelo Favorito (Jeremy Tech MVC).
 *
 * La tabla `favoritos` guarda una fila por cliente y producto. El esquema
 * heredado declaraba `producto_id` como VARCHAR; la migración de la Etapa 2
 * lo convirtió a BIGINT UNSIGNED con FK a `productos.id`.
 */
final class Favorito
{
    /**
     * Ids de los productos favoritos del cliente, listos para consultar
     * con `isset($favoritosIds[$productoId])` en las vistas.
     *
     * @return array<int, int>
     */
    public static function idsDeCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT producto_id FROM favoritos WHERE cliente_id = ? ORDER BY id DESC'
        );
        $stmt->execute([$clienteId]);

        $ids = [];
        foreach ($stmt->fetchAll() as $fila) {
            $id = (int) $fila['producto_id'];
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return $ids;
    }

    /**
     * ¿El cliente ya guardó este producto?
     */
    public static function esFavorito(int $clienteId, int $productoId): bool
    {
        if ($clienteId <= 0 || $productoId <= 0) {
            return false;
        }

        $stmt = conexion()->prepare(
            'SELECT COUNT(*) FROM favoritos WHERE cliente_id = ? AND producto_id = ?'
        );
        $stmt->execute([$clienteId, $productoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Agrega o quita un producto de favoritos.
     *
     * Devuelve true si quedó guardado y false si se quitó. Devuelve null
     * cuando el producto no existe o no está activo (y no había una fila
     * previa que quitar).
     */
    public static function alternar(int $clienteId, int $productoId): ?bool
    {
        if ($clienteId <= 0 || $productoId <= 0) {
            return null;
        }

        $pdo = conexion();

        // Si ya era favorito, se puede quitar aunque el producto se haya
        // desactivado después: el cliente debe poder limpiar su lista.
        $stmt = $pdo->prepare(
            'SELECT id FROM favoritos WHERE cliente_id = ? AND producto_id = ? LIMIT 1'
        );
        $stmt->execute([$clienteId, $productoId]);
        if ($stmt->fetchColumn() !== false) {
            $pdo->prepare('DELETE FROM favoritos WHERE cliente_id = ? AND producto_id = ?')
                ->execute([$clienteId, $productoId]);
            return false;
        }

        $stmt = $pdo->prepare('SELECT id FROM productos WHERE id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$productoId]);
        if ($stmt->fetchColumn() === false) {
            return null;
        }

        // INSERT IGNORE evita duplicados si dos pestañas envían el clic a la
        // vez; la clave única (cliente_id, producto_id) es la última defensa.
        $pdo->prepare(
            'INSERT IGNORE INTO favoritos (cliente_id, producto_id) VALUES (?, ?)'
        )->execute([$clienteId, $productoId]);

        return true;
    }

    /**
     * Productos favoritos con los datos necesarios para la vista.
     *
     * Se incluyen productos inactivos para que el cliente pueda ver qué
     * guardó y quitarlo; la vista muestra "No disponible" en ese caso.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function productosDeCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT p.id, p.slug, p.nombre, p.categoria, p.precio_pen, p.imagen,
                    p.stock, p.activo, p.vendedor_id,
                    v.name AS vendedor_nombre, v.usuario AS vendedor_usuario,
                    v.verificado AS vendedor_verificado,
                    f.creado_en AS guardado_en
               FROM favoritos f
               JOIN productos p ON p.id = f.producto_id
          LEFT JOIN users v ON v.id = p.vendedor_id AND v.deleted_at IS NULL
              WHERE f.cliente_id = ?
           ORDER BY f.id DESC'
        );
        $stmt->execute([$clienteId]);

        return $stmt->fetchAll();
    }
}
