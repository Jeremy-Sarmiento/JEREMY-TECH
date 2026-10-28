<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Resena (Jeremy Tech MVC) — Fase 2.
 *
 * Una fila de `resenas` por cliente y producto (UNIQUE `resena_unica`).
 * Solo puede opinar quien compró el producto en un pedido ya pagado: la
 * comprobación se hace siempre en la base de datos, nunca con lo que envía
 * el navegador.
 *
 * La columna `producto_id` es BIGINT UNSIGNED desde la migración de la
 * Etapa 2 (el esquema heredado la declaraba VARCHAR).
 */
final class Resena
{
    public const CALIFICACION_MIN = 1;
    public const CALIFICACION_MAX = 5;
    public const COMENTARIO_MAX = 500;

    /**
     * Estados de pedido que habilitan la reseña. "Pendiente" (pago sin
     * confirmar) y "Cancelado" no cuentan como compra.
     *
     * @var array<int, string>
     */
    private const ESTADOS_COMPRA = ['Pagado', 'Enviado', 'Entregado'];

    /**
     * Reseñas de un producto, las más recientes primero, con el nombre del
     * autor para poder mostrarlo de forma pública.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deProducto(int $productoId, int $limite = 20): array
    {
        if ($productoId <= 0) {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $stmt = conexion()->prepare(
            'SELECT r.id, r.producto_id, r.cliente_id, r.calificacion, r.comentario,
                    r.creado_en, u.name AS cliente_nombre, u.apellidos AS cliente_apellidos
               FROM resenas r
               JOIN users u ON u.id = r.cliente_id
              WHERE r.producto_id = ? AND r.estado = \'visible\'
              ORDER BY r.creado_en DESC, r.id DESC
              LIMIT ' . $limite
        );
        $stmt->execute([$productoId]);

        return $stmt->fetchAll();
    }

    /**
     * Resumen de valoraciones de un producto.
     *
     * @return array{total: int, promedio: float, distribucion: array<int, int>}
     */
    public static function resumen(int $productoId): array
    {
        if ($productoId <= 0) {
            return ['total' => 0, 'promedio' => 0.0, 'distribucion' => self::distribucionVacia()];
        }

        $stmt = conexion()->prepare(
            'SELECT COUNT(*) AS total,
                    COALESCE(AVG(calificacion), 0) AS promedio,
                    SUM(CASE WHEN calificacion = 5 THEN 1 ELSE 0 END) AS c5,
                    SUM(CASE WHEN calificacion = 4 THEN 1 ELSE 0 END) AS c4,
                    SUM(CASE WHEN calificacion = 3 THEN 1 ELSE 0 END) AS c3,
                    SUM(CASE WHEN calificacion = 2 THEN 1 ELSE 0 END) AS c2,
                    SUM(CASE WHEN calificacion = 1 THEN 1 ELSE 0 END) AS c1
               FROM resenas
              WHERE producto_id = ? AND estado = \'visible\''
        );
        $stmt->execute([$productoId]);
        $fila = $stmt->fetch() ?: [];

        return [
            'total'        => (int) ($fila['total'] ?? 0),
            'promedio'     => round((float) ($fila['promedio'] ?? 0), 1),
            'distribucion' => [
                5 => (int) ($fila['c5'] ?? 0),
                4 => (int) ($fila['c4'] ?? 0),
                3 => (int) ($fila['c3'] ?? 0),
                2 => (int) ($fila['c2'] ?? 0),
                1 => (int) ($fila['c1'] ?? 0),
            ],
        ];
    }

    /**
     * Resúmenes de varios productos en una sola consulta (tarjetas del
     * catálogo y fichas relacionadas).
     *
     * @param array<int, int|string> $productoIds
     * @return array<int, array{total: int, promedio: float}>
     */
    public static function resumenDeProductos(array $productoIds): array
    {
        $ids = [];
        foreach ($productoIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $ids = array_values($ids);
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = conexion()->prepare(
            "SELECT producto_id, COUNT(*) AS total, AVG(calificacion) AS promedio
               FROM resenas
              WHERE producto_id IN ($marcas) AND estado = 'visible'
              GROUP BY producto_id"
        );
        $stmt->execute($ids);

        $resumenes = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resumenes[(int) $fila['producto_id']] = [
                'total'    => (int) $fila['total'],
                'promedio' => round((float) $fila['promedio'], 1),
            ];
        }

        return $resumenes;
    }

    /**
     * Reseñas ya escritas por el cliente, indexadas por producto.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deCliente(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $stmt = conexion()->prepare(
            'SELECT producto_id, calificacion, comentario, estado, motivo_oculto,
                    moderado_en, actualizado_en, creado_en
               FROM resenas
              WHERE cliente_id = ?'
        );
        $stmt->execute([$clienteId]);

        $resenas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resenas[(int) $fila['producto_id']] = $fila;
        }

        return $resenas;
    }

    /**
     * ¿El cliente compró ese producto en un pedido pagado o posterior?
     */
    public static function puedeOpinar(int $clienteId, int $productoId): bool
    {
        if ($clienteId <= 0 || $productoId <= 0) {
            return false;
        }

        $marcas = implode(',', array_fill(0, count(self::ESTADOS_COMPRA), '?'));
        $stmt = conexion()->prepare(
            "SELECT 1
               FROM detalle_pedido dp
               JOIN pedidos p ON p.id = dp.pedido_id
              WHERE dp.producto_id = ? AND p.cliente_id = ?
                AND p.estado IN ($marcas)
              LIMIT 1"
        );
        $stmt->execute(array_merge([$productoId, $clienteId], self::ESTADOS_COMPRA));

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Ids de los productos que el cliente ya compró (pedidos pagados o
     * posteriores). Se usa como mapa para pintar los widgets de reseña de
     * "Mis pedidos" sin una consulta por línea.
     *
     * @return array<int, true>
     */
    public static function productosComprados(int $clienteId): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count(self::ESTADOS_COMPRA), '?'));
        $stmt = conexion()->prepare(
            "SELECT DISTINCT dp.producto_id
               FROM detalle_pedido dp
               JOIN pedidos p ON p.id = dp.pedido_id
              WHERE p.cliente_id = ?
                AND p.estado IN ($marcas)"
        );
        $stmt->execute(array_merge([$clienteId], self::ESTADOS_COMPRA));

        $comprados = [];
        foreach ($stmt->fetchAll() as $fila) {
            $id = (int) $fila['producto_id'];
            if ($id > 0) {
                $comprados[$id] = true;
            }
        }

        return $comprados;
    }

    /**
     * Crea o actualiza la reseña del cliente para un producto.
     *
     * @return string|null null si se guardó, o el mensaje de error.
     */
    public static function guardar(int $clienteId, int $productoId, int $calificacion, string $comentario): ?string
    {
        if ($clienteId <= 0) {
            return 'Inicia sesión para dejar tu opinión.';
        }
        if ($productoId <= 0) {
            return 'Producto no válido.';
        }
        if ($calificacion < self::CALIFICACION_MIN || $calificacion > self::CALIFICACION_MAX) {
            return 'Elige una calificación de 1 a 5 estrellas.';
        }

        $comentario = trim($comentario);
        if (mb_strlen($comentario) > self::COMENTARIO_MAX) {
            return 'El comentario no puede superar los ' . self::COMENTARIO_MAX . ' caracteres.';
        }

        if (!self::puedeOpinar($clienteId, $productoId)) {
            return 'Solo puedes opinar sobre productos que ya compraste.';
        }

        // ON DUPLICATE KEY UPDATE usa la UNIQUE (producto_id, cliente_id):
        // volver a opinar actualiza la reseña en vez de duplicarla. La fecha
        // original se conserva para que la edición no la haga parecer nueva.
        $stmt = conexion()->prepare(
            'INSERT INTO resenas (producto_id, cliente_id, calificacion, comentario)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                calificacion = VALUES(calificacion),
                comentario = VALUES(comentario)'
        );
        $stmt->execute([$productoId, $clienteId, $calificacion, $comentario]);

        return null;
    }

    /**
     * Cola de moderación del panel. Incluye reseñas visibles y ocultas.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todas(string $estado = '', string $buscar = '', int $limite = 300): array
    {
        $sql = 'SELECT r.*, p.nombre AS producto_nombre, p.slug AS producto_slug,
                       u.name AS cliente_nombre, u.apellidos AS cliente_apellidos,
                       u.email AS cliente_email
                  FROM resenas r
             LEFT JOIN productos p ON p.id = r.producto_id
             LEFT JOIN users u ON u.id = r.cliente_id
                 WHERE 1 = 1';
        $params = [];

        if ($estado === 'visible' || $estado === 'oculta') {
            $sql .= ' AND r.estado = ?';
            $params[] = $estado;
        }

        $buscar = trim($buscar);
        if ($buscar !== '') {
            $sql .= ' AND (r.comentario LIKE ? OR p.nombre LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $buscar . '%';
            for ($i = 0; $i < 4; $i++) {
                $params[] = $like;
            }
        }

        $limite = max(1, min(500, $limite));
        $sql .= ' ORDER BY r.id DESC LIMIT ' . $limite;

        $stmt = conexion()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Contadores de moderación.
     *
     * @return array{total: int, visibles: int, ocultas: int}
     */
    public static function resumenModeracion(): array
    {
        $fila = conexion()->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'visible' THEN 1 ELSE 0 END) AS visibles,
                    SUM(CASE WHEN estado = 'oculta' THEN 1 ELSE 0 END) AS ocultas
               FROM resenas"
        )->fetch() ?: [];

        return [
            'total'    => (int) ($fila['total'] ?? 0),
            'visibles' => (int) ($fila['visibles'] ?? 0),
            'ocultas'  => (int) ($fila['ocultas'] ?? 0),
        ];
    }

    /**
     * Oculta una reseña del catálogo público.
     *
     * @return string|null null si se ocultó, o el mensaje de error.
     */
    public static function ocultar(int $id, string $motivo, string $adminUsuario): ?string
    {
        if ($id <= 0) {
            return 'Reseña no válida.';
        }

        $motivo = mb_substr(trim($motivo), 0, 255);
        $stmt = conexion()->prepare(
            "UPDATE resenas
                SET estado = 'oculta', motivo_oculto = ?, moderado_por = ?, moderado_en = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$motivo, mb_substr($adminUsuario, 0, 60), $id]);

        // rowCount() es 0 si la fila ya estaba oculta y nada cambió; eso no
        // es un error. Solo se falla cuando la reseña no existe.
        return $stmt->rowCount() === 1 || self::existe($id) ? null : 'La reseña ya no existe.';
    }

    /**
     * Vuelve a publicar una reseña oculta.
     *
     * @return string|null null si se publicó, o el mensaje de error.
     */
    public static function mostrar(int $id, string $adminUsuario): ?string
    {
        if ($id <= 0) {
            return 'Reseña no válida.';
        }

        $stmt = conexion()->prepare(
            "UPDATE resenas
                SET estado = 'visible', motivo_oculto = '', moderado_por = ?, moderado_en = NOW()
              WHERE id = ?"
        );
        $stmt->execute([mb_substr($adminUsuario, 0, 60), $id]);

        return $stmt->rowCount() === 1 || self::existe($id) ? null : 'La reseña ya no existe.';
    }

    /** ¿Sigue existiendo la reseña? */
    private static function existe(int $id): bool
    {
        $stmt = conexion()->prepare('SELECT 1 FROM resenas WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetchColumn() !== false;
    }

    /** Etiqueta legible del estado de moderación. */
    public static function etiquetaEstado(string $estado): string
    {
        return $estado === 'oculta' ? 'Oculta' : 'Visible';
    }

    /**
     * Texto de estrellas para un promedio (por ejemplo "★★★★☆").
     */
    public static function estrellas(float $promedio): string
    {
        $llenas = (int) round(max(0.0, min(5.0, $promedio)));
        return str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
    }

    /**
     * @return array<int, int>
     */
    private static function distribucionVacia(): array
    {
        return [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    }
}
