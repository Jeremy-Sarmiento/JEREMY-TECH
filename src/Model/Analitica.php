<?php

declare(strict_types=1);

namespace Model;

/**
 * Analítica de comportamiento en tienda (Jeremy Tech MVC).
 *
 * Escribe los eventos que reciben el endpoint público y calcula los agregados
 * que consume el dashboard del panel. Los eventos se guardan en
 * `eventos_analitica`; el payload se guarda como JSON de hasta 1000 caracteres.
 *
 * Reglas:
 *  - No se aceptan eventos desconocidos desde el endpoint: el controlador valida
 *    el catálogo y este modelo normaliza lo que recibe.
 *  - El análisis nunca debe romper la operación que lo dispara: el endpoint y los
 *    llamadores deben envolver el registro en try/catch.
 *  - `cliente_id` es opcional: los visitantes sin sesión de cliente generan eventos
 *    anónimos (útil para tráfico global).
 */
final class Analitica
{
    /**
     * Registra un evento de analítica.
     *
     * @param array<string, mixed> $datos
     */
    public static function registrar(string $tipoEvento, array $datos = [], string $pagina = '', ?int $clienteId = null): bool
    {
        $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE) ?: '{}';
        // Conserva el evento sin datos ilegibles: el JSON antes truncado ya se leía como payload vacío.
        if (mb_strlen($datosJson) > 1000) {
            $datosJson = '{}';
        }

        $pdo = conexion();

        $stmt = $pdo->prepare(
            'INSERT INTO eventos_analitica (tipo_evento, datos_json, pagina, cliente_id)
             VALUES (?, ?, ?, ?)'
        );

        return (bool) $stmt->execute([
            mb_substr($tipoEvento, 0, 60),
            $datosJson,
            mb_substr(trim($pagina), 0, 80),
            $clienteId,
        ]);
    }

    /**
     * Eventos dentro de un rango (fechas `Y-m-d`) y opcionalmente acotados a un
     * vendedor. Para un vendedor se filtran los eventos que remiten a productos
     * de ese vendedor (payload con `producto_id` o `slug`).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function eventosEntre(?string $desde = null, ?string $hasta = null, ?int $vendedorId = null): array
    {
        $stmt = self::consultarEntre(
            'SELECT id, tipo_evento, datos_json, pagina, cliente_id, creado_en FROM eventos',
            $desde,
            $hasta,
            $vendedorId
        );

        return $stmt->fetchAll();
    }

    /**
     * Resumen general de los eventos del periodo.
     *
     * @return array<string, float|int>
     */
    public static function resumenEntre(?string $desde = null, ?string $hasta = null, ?int $vendedorId = null): array
    {
        $stmt = self::consultarEntre(
            "SELECT tipo_evento, COUNT(*) AS c,
                   SUM(CASE WHEN tipo_evento = 'compra'
                       THEN pg_temp.analitica_numero(datos ->> 'total_pen') ELSE 0 END) AS ingresos
               FROM eventos
               GROUP BY tipo_evento",
            $desde,
            $hasta,
            $vendedorId
        );
        $porTipo = [];
        $ingresos = 0.0;
        foreach ($stmt->fetchAll() as $fila) {
            $porTipo[(string) $fila['tipo_evento']] = (int) $fila['c'];
            if ($fila['tipo_evento'] === 'compra') {
                $ingresos = (float) $fila['ingresos'];
            }
        }

        $metricas = [
            'visitas' => $porTipo['visita'] ?? 0,
            'vistas_producto' => $porTipo['producto_visto'] ?? 0,
            'busquedas' => $porTipo['busqueda'] ?? 0,
            'agregados_carrito' => $porTipo['agregar_carrito'] ?? 0,
            'checkouts' => $porTipo['iniciar_checkout'] ?? 0,
            'compras' => $porTipo['compra'] ?? 0,
            'ingresos_pen' => $ingresos,
            'clics_whatsapp' => $porTipo['clic_whatsapp'] ?? 0,
        ];

        $metricas['tasa_conversion'] = $metricas['visitas'] > 0
            ? round(($metricas['compras'] / $metricas['visitas']) * 100, 2)
            : 0.0;

        return $metricas;
    }

    /**
     * Productos con más interacción en el periodo (vista, agregado o compra).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function topProductosEntre(?string $desde = null, ?string $hasta = null, int $limite = 10, ?int $vendedorId = null): array
    {
        $stmt = self::consultarEntre(
            "SELECT
                CASE WHEN producto_id > 0 THEN 'id:' || producto_id::text ELSE 'slug:' || slug END AS clave,
                COUNT(*) AS c
             FROM eventos
             WHERE tipo_evento IN ('producto_visto','agregar_carrito','compra')
               AND (producto_id > 0 OR slug <> '')
             GROUP BY 1
             ORDER BY c DESC, MIN(id)
             LIMIT " . max(1, $limite),
            $desde,
            $hasta,
            $vendedorId
        );
        $filas = $stmt->fetchAll();

        $resultado = [];
        foreach ($filas as $fila) {
            $clave = (string) $fila['clave'];
            $resultado[] = [
                'clave'  => $clave,
                'etiqueta' => self::etiquetaProducto($clave),
                'interacciones' => (int) $fila['c'],
            ];
        }

        return $resultado;
    }

    /**
     * Estadísticas del chatbot dentro del periodo.
     *
     * @return array<string, int>
     */
    public static function chatbotEntre(?string $desde = null, ?string $hasta = null): array
    {
        $stmt = self::consultarEntre(
            "SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE datos ->> 'origen' = 'faq') AS faq,
                COUNT(*) FILTER (WHERE datos ->> 'origen' = 'ia') AS ia,
                COUNT(*) FILTER (WHERE COALESCE(datos ->> 'origen', '') NOT IN ('faq', 'ia')) AS fallback
             FROM eventos
             WHERE tipo_evento = 'pregunta_chatbot'",
            $desde,
            $hasta,
            null
        );
        $fila = $stmt->fetch() ?: [];

        return [
            'total_preguntas' => (int) ($fila['total'] ?? 0),
            'faq' => (int) ($fila['faq'] ?? 0),
            'ia' => (int) ($fila['ia'] ?? 0),
            'fallback' => (int) ($fila['fallback'] ?? 0),
        ];
    }

    /**
     * Páginas con más visitas en el periodo.
     *
     * @return array<int, array{pagina: string, visitas: int}>
     */
    public static function topPaginasEntre(?string $desde = null, ?string $hasta = null, int $limite = 10, ?int $vendedorId = null): array
    {
        $stmt = self::consultarEntre(
            "SELECT COALESCE(NULLIF(lower(btrim(pagina, E' \\t\\n\\r\\013')), ''), 'inicio') AS pagina, COUNT(*) AS c
               FROM eventos
              WHERE tipo_evento = 'visita'
              GROUP BY 1
              ORDER BY c DESC, MIN(id)
              LIMIT " . max(1, $limite),
            $desde,
            $hasta,
            $vendedorId
        );
        $filas = $stmt->fetchAll();

        $resultado = [];
        foreach ($filas as $fila) {
            $resultado[] = ['pagina' => (string) $fila['pagina'], 'visitas' => (int) $fila['c']];
        }
        return $resultado;
    }

    /**
     * Términos de búsqueda más usados en el periodo.
     *
     * @return array<int, array{consulta: string, busquedas: int}>
     */
    public static function topBusquedasEntre(?string $desde = null, ?string $hasta = null, int $limite = 10, ?int $vendedorId = null): array
    {
        $stmt = self::consultarEntre(
            "SELECT lower(btrim(datos ->> 'consulta', E' \\t\\n\\r\\013')) AS consulta, COUNT(*) AS c
               FROM eventos
              WHERE tipo_evento = 'busqueda'
                AND btrim(datos ->> 'consulta', E' \\t\\n\\r\\013') <> ''
              GROUP BY 1
              ORDER BY c DESC, MIN(id)
              LIMIT " . max(1, $limite),
            $desde,
            $hasta,
            $vendedorId
        );
        $filas = $stmt->fetchAll();

        $resultado = [];
        foreach ($filas as $fila) {
            $resultado[] = ['consulta' => (string) $fila['consulta'], 'busquedas' => (int) $fila['c']];
        }
        return $resultado;
    }

    /**
     * Conteo de eventos por tipo en el periodo (dashboard de comportamiento).
     *
     * @return array<string, int>
     */
    public static function eventosPorTipoEntre(?string $desde = null, ?string $hasta = null, ?int $vendedorId = null): array
    {
        $stmt = self::consultarEntre(
            'SELECT tipo_evento, COUNT(*) AS c
               FROM eventos
               GROUP BY tipo_evento',
            $desde,
            $hasta,
            $vendedorId
        );
        $porTipo = [];
        foreach ($stmt->fetchAll() as $fila) {
            $porTipo[(string) $fila['tipo_evento']] = (int) $fila['c'];
        }
        return $porTipo;
    }

    private static function etiquetaProducto(string $clave): string
    {
        if (str_starts_with($clave, 'id:')) {
            $id = (int) substr($clave, 3);
            $stmt = conexion()->prepare('SELECT nombre, slug FROM productos WHERE id = ?');
            $stmt->execute([$id]);
            $fila = $stmt->fetch();
            if ($fila !== false) {
                return (string) $fila['nombre'];
            }
        }

        if (str_starts_with($clave, 'slug:')) {
            $slug = substr($clave, 5);
            $stmt = conexion()->prepare('SELECT nombre, slug FROM productos WHERE slug = ?');
            $stmt->execute([$slug]);
            $fila = $stmt->fetch();
            if ($fila !== false) {
                return (string) $fila['nombre'];
            }
        }

        return $clave;
    }

    private static function timestamp(string $fecha): int
    {
        $fecha = trim($fecha);
        if ($fecha === '' || $fecha === '1970-01-01') {
            return 0;
        }

        $fechaHora = \DateTime::createFromFormat('Y-m-d', $fecha);
        if ($fechaHora === false) {
            return (int) (\DateTime::createFromFormat('Y-m-d H:i:s', $fecha) ?: new \DateTime($fecha))->getTimestamp();
        }

        return (int) $fechaHora->getTimestamp();
    }

    private static function consultarEntre(string $consulta, ?string $desde, ?string $hasta, ?int $vendedorId): \PDOStatement
    {
        $pdo = conexion();
        $transaccionPropia = !$pdo->inTransaction();
        // Mantiene las funciones temporales y la consulta en la misma sesión incluso con pooler transaccional.
        if ($transaccionPropia) {
            $pdo->beginTransaction();
        }

        try {
            [$eventosSql, $params] = self::eventosSql($desde, $hasta, $vendedorId);
            $stmt = $pdo->prepare($eventosSql . ' ' . $consulta);
            $stmt->execute($params);
            if ($transaccionPropia) {
                $pdo->commit();
            }
            return $stmt;
        } catch (\Throwable $error) {
            if ($transaccionPropia && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Fuente SQL compartida para agregar sin traer todos los eventos a PHP.
     *
     * @return array{string, array}
     */
    private static function eventosSql(?string $desde, ?string $hasta, ?int $vendedorId): array
    {
        // PG14 no tiene pg_input_is_valid; estas funciones son solo de sesión y no reescriben históricos.
        conexion()->exec(<<<'SQL'
            CREATE OR REPLACE FUNCTION pg_temp.analitica_jsonb(valor text) RETURNS jsonb
            LANGUAGE plpgsql IMMUTABLE AS $$
            BEGIN
                RETURN COALESCE(valor::jsonb, '{}'::jsonb);
            EXCEPTION WHEN data_exception THEN
                RETURN '{}'::jsonb;
            END;
            $$;
            CREATE OR REPLACE FUNCTION pg_temp.analitica_numero(valor text) RETURNS numeric
            LANGUAGE plpgsql IMMUTABLE AS $$
            BEGIN
                RETURN COALESCE(substring(valor FROM '^[[:space:]]*[+-]?(?:[0-9]+(?:[.][0-9]*)?|[.][0-9]+)(?:[eE][+-]?[0-9]+)?')::numeric, 0);
            EXCEPTION WHEN data_exception THEN
                RETURN 0;
            END;
            $$;
            SQL);

        $desdeSql = ($desde !== null && trim($desde) !== '')
            ? date('Y-m-d 00:00:00', strtotime($desde))
            : '1970-01-01 00:00:00';
        $hastaSql = ($hasta !== null && trim($hasta) !== '')
            ? date('Y-m-d 23:59:59', strtotime($hasta))
            : date('Y-m-d 23:59:59');

        $params = [$desdeSql, $hastaSql];
        $filtroVendedor = '';

        if ($vendedorId !== null) {
            $filtroVendedor = "WHERE tipo_evento NOT IN ('visita','busqueda','clic_whatsapp')
                AND EXISTS (
                    SELECT 1 FROM productos p WHERE p.vendedor_id = ?
                    AND CASE WHEN e.producto_id > 0 THEN p.id = e.producto_id
                             ELSE p.slug = NULLIF(e.slug, '') END
                )";
            $params[] = $vendedorId;
        }

        $sql = "WITH datos_eventos AS (
                    SELECT ea.*, pg_temp.analitica_jsonb(datos_json) AS datos
                    FROM eventos_analitica ea WHERE creado_en BETWEEN ? AND ?
                ), eventos_normalizados AS (
                    SELECT *,
                        LEAST(GREATEST(trunc(pg_temp.analitica_numero(datos ->> 'producto_id')),
                            -9223372036854775808), 9223372036854775807)::bigint AS producto_id,
                        lower(btrim(COALESCE(datos ->> 'slug', ''), E' \\t\\n\\r\\013')) AS slug
                    FROM datos_eventos
                ), eventos AS (
                    SELECT * FROM eventos_normalizados e $filtroVendedor
                )";

        return [$sql, $params];
    }
}
