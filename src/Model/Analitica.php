<?php
declare(strict_types=1);

namespace Model;

/**
 * Analítica de comportamiento en tienda (Jeremy Tech MVC).
 *
 * Escribe los eventos que reciben el endpoint público y calcula los agregados
 * que consume el dashboard del panel. Los eventos se guardan en
 * `eventos_analitica`; el payload se guarda como JSON acotado a 1000 bytes.
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
        $pdo = conexion();

        $stmt = $pdo->prepare(
            'INSERT INTO eventos_analitica (tipo_evento, datos_json, pagina, cliente_id)
             VALUES (?, ?, ?, ?)'
        );

        return (bool) $stmt->execute([
            mb_substr($tipoEvento, 0, 60),
            mb_substr(json_encode($datos, JSON_UNESCAPED_UNICODE) ?: '{}', 0, 1000),
            mb_substr(trim($pagina), 0, 80),
            $clienteId,
        ]);
    }

    /**
     * Eventos dentro de un rango (fechas `Y-m-d`) y opcionalmente acotados a un
     * vendedor. Para un vendedor se filtran los eventos que remiten a productos
     * de ese vendedor (payload con `producto_id`).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function eventosEntre(?string $desde = null, ?string $hasta = null, ?int $vendedorId = null): array
    {
        $desdeSql = ($desde !== null && trim($desde) !== '') ? date('Y-m-d 00:00:00', strtotime($desde)) : '1970-01-01 00:00:00';
        $hastaSql = ($hasta !== null && trim($hasta) !== '') ? date('Y-m-d 23:59:59', strtotime($hasta)) : date('Y-m-d 23:59:59');

        $stmt = conexion()->prepare(
            'SELECT id, tipo_evento, datos_json, pagina, cliente_id, creado_en
               FROM eventos_analitica
              WHERE creado_en BETWEEN ? AND ?'
        );
        $stmt->execute([$desdeSql, $hastaSql]);
        $filas = $stmt->fetchAll();

        if ($vendedorId === null) {
            return $filas;
        }

        $ids = self::idsDeVendedor($vendedorId);
        if ($ids === []) {
            return [];
        }

        $resultado = [];
        foreach ($filas as $fila) {
            if (!self::perteneceAVendedor((string) ($fila['tipo_evento'] ?? ''), (string) ($fila['datos_json'] ?? '{}'), $ids)) {
                continue;
            }
            $resultado[] = $fila;
        }

        return $resultado;
    }

    /**
     * Resumen general de los eventos del periodo.
     *
     * @return array<string, float|int>
     */
    public static function resumenEntre(?string $desde = null, ?string $hasta = null, ?int $vendedorId = null): array
    {
        $eventos = self::eventosEntre($desde, $hasta, $vendedorId);

        $metricas = [
            'visitas' => 0,
            'vistas_producto' => 0,
            'busquedas' => 0,
            'agregados_carrito' => 0,
            'checkouts' => 0,
            'compras' => 0,
            'ingresos_pen' => 0.0,
            'clics_whatsapp' => 0,
        ];

        foreach ($eventos as $evento) {
            $tipo = (string) ($evento['tipo_evento'] ?? '');
            $datos = json_decode((string) ($evento['datos_json'] ?? '{}'), true);
            if (!is_array($datos)) {
                $datos = [];
            }

            switch ($tipo) {
                case 'visita':
                    $metricas['visitas']++;
                    break;
                case 'producto_visto':
                    $metricas['vistas_producto']++;
                    break;
                case 'busqueda':
                    $metricas['busquedas']++;
                    break;
                case 'agregar_carrito':
                    $metricas['agregados_carrito']++;
                    break;
                case 'iniciar_checkout':
                    $metricas['checkouts']++;
                    break;
                case 'compra':
                    $metricas['compras']++;
                    $metricas['ingresos_pen'] += (float) ($datos['total_pen'] ?? 0);
                    break;
                case 'clic_whatsapp':
                    $metricas['clics_whatsapp']++;
                    break;
            }
        }

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
        $eventos = self::eventosEntre($desde, $hasta, $vendedorId);
        $agrupados = [];

        foreach ($eventos as $evento) {
            $tipo = (string) ($evento['tipo_evento'] ?? '');
            if (!in_array($tipo, ['producto_visto', 'agregar_carrito', 'compra'], true)) {
                continue;
            }
            $datos = json_decode((string) ($evento['datos_json'] ?? '{}'), true);
            if (!is_array($datos)) {
                continue;
            }
            $productoId = (int) ($datos['producto_id'] ?? 0);
            $clave = $productoId > 0 ? 'id:' . $productoId : 'slug:' . mb_strtolower(trim((string) ($datos['slug'] ?? '')));
            if ($clave === 'slug:') {
                continue;
            }
            $agrupados[$clave] = ($agrupados[$clave] ?? 0) + 1;
        }

        $ordenados = array_keys($agrupados);
        usort($ordenados, static function (string $a, string $b) use ($agrupados): int {
            return $agrupados[$b] <=> $agrupados[$a];
        });

        $resultado = [];
        foreach (array_slice($ordenados, 0, max(1, $limite)) as $clave) {
            $resultado[] = [
                'clave'  => $clave,
                'etiqueta' => self::etiquetaProducto($clave),
                'interacciones' => (int) $agrupados[$clave],
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
        $eventos = self::eventosEntre($desde, $hasta);
        $metricas = [
            'total_preguntas' => 0,
            'faq' => 0,
            'ia' => 0,
            'fallback' => 0,
        ];

        foreach ($eventos as $evento) {
            if ((string) ($evento['tipo_evento'] ?? '') !== 'pregunta_chatbot') {
                continue;
            }
            $metricas['total_preguntas']++;
            $datos = json_decode((string) ($evento['datos_json'] ?? '{}'), true);
            $origen = (string) ($datos['origen'] ?? 'fallback');
            if ($origen === 'faq') {
                $metricas['faq']++;
            } elseif ($origen === 'ia') {
                $metricas['ia']++;
            } else {
                $metricas['fallback']++;
            }
        }

        return $metricas;
    }

    /**
     * Páginas con más visitas en el periodo.
     *
     * @return array<int, array{pagina: string, visitas: int}>
     */
    public static function topPaginasEntre(?string $desde = null, ?string $hasta = null, int $limite = 10, ?int $vendedorId = null): array
    {
        $eventos = self::eventosEntre($desde, $hasta, $vendedorId);
        $agrupados = [];

        foreach ($eventos as $evento) {
            if ((string) ($evento['tipo_evento'] ?? '') !== 'visita') {
                continue;
            }
            $pagina = mb_strtolower(trim((string) ($evento['pagina'] ?? '')));
            if ($pagina === '') {
                $pagina = 'inicio';
            }
            $agrupados[$pagina] = ($agrupados[$pagina] ?? 0) + 1;
        }

        $ordenados = array_keys($agrupados);
        usort($ordenados, static function (string $a, string $b) use ($agrupados): int {
            return $agrupados[$b] <=> $agrupados[$a];
        });

        $resultado = [];
        foreach (array_slice($ordenados, 0, max(1, $limite)) as $pagina) {
            $resultado[] = ['pagina' => $pagina, 'visitas' => (int) $agrupados[$pagina]];
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
        $eventos = self::eventosEntre($desde, $hasta, $vendedorId);
        $agrupados = [];

        foreach ($eventos as $evento) {
            if ((string) ($evento['tipo_evento'] ?? '') !== 'busqueda') {
                continue;
            }
            $datos = json_decode((string) ($evento['datos_json'] ?? '{}'), true);
            if (!is_array($datos)) {
                continue;
            }
            $consulta = mb_strtolower(trim((string) ($datos['consulta'] ?? '')));
            if ($consulta === '') {
                continue;
            }
            $agrupados[$consulta] = ($agrupados[$consulta] ?? 0) + 1;
        }

        $ordenados = array_keys($agrupados);
        usort($ordenados, static function (string $a, string $b) use ($agrupados): int {
            return $agrupados[$b] <=> $agrupados[$a];
        });

        $resultado = [];
        foreach (array_slice($ordenados, 0, max(1, $limite)) as $consulta) {
            $resultado[] = ['consulta' => $consulta, 'busquedas' => (int) $agrupados[$consulta]];
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
        $eventos = self::eventosEntre($desde, $hasta, $vendedorId);
        $porTipo = [];

        foreach ($eventos as $evento) {
            $tipo = (string) ($evento['tipo_evento'] ?? '');
            $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + 1;
        }

        return $porTipo;
    }

    private static function idsDeVendedor(int $vendedorId): array
    {
        $stmt = conexion()->prepare('SELECT id FROM productos WHERE vendedor_id = ?');
        $stmt->execute([$vendedorId]);
        $ids = [];
        foreach ($stmt->fetchAll() as $fila) {
            $ids[] = (int) $fila['id'];
        }

        return $ids;
    }

    private static function perteneceAVendedor(string $tipo, string $datosJson, array $ids): bool
    {
        if ($tipo === 'visita' || $tipo === 'busqueda' || $tipo === 'clic_whatsapp') {
            // Sin producto en el payload no se puede acotar a un vendedor.
            return false;
        }

        $datos = json_decode($datosJson, true);
        if (!is_array($datos)) {
            return false;
        }

        $productoId = (int) ($datos['producto_id'] ?? 0);
        if ($productoId > 0) {
            return in_array($productoId, $ids, true);
        }

        $slug = mb_strtolower(trim((string) ($datos['slug'] ?? '')));
        if ($slug === '') {
            return false;
        }

        $stmt = conexion()->prepare('SELECT id FROM productos WHERE slug = ?');
        $stmt->execute([$slug]);
        $productoId = (int) ($stmt->fetchColumn() ?: 0);

        return in_array($productoId, $ids, true);
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
}
