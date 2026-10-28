<?php
declare(strict_types=1);

namespace Model;

/**
 * Modelo Direccion (Jeremy Tech).
 * Direcciones de envío guardadas por el cliente (tabla `direcciones`,
 * `cliente_id` -> `users.id`). Cada dirección lleva su ZONA de envío
 * (`tarifa_envio_id` -> `tarifas_envio`), que es lo único que determina el
 * costo del pedido. No hay geo-ubigeo: dirección y referencia son texto libre.
 */
class Direccion
{
    /**
     * Direcciones del cliente, la predeterminada primero.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function delCliente(int $clienteId): array
    {
        $stmt = conexion()->prepare(
            'SELECT d.*, t.zona, t.costo, t.dias_estimados
               FROM direcciones d
          LEFT JOIN tarifas_envio t ON t.id = d.tarifa_envio_id
              WHERE d.cliente_id = ?
           ORDER BY d.predeterminada DESC, d.id'
        );
        $stmt->execute([$clienteId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function porId(int $id, int $clienteId): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT d.*, t.zona, t.costo, t.dias_estimados
               FROM direcciones d
          LEFT JOIN tarifas_envio t ON t.id = d.tarifa_envio_id
              WHERE d.id = ? AND d.cliente_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $clienteId]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function predeterminadaDe(int $clienteId): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT d.*, t.zona, t.costo, t.dias_estimados
               FROM direcciones d
          LEFT JOIN tarifas_envio t ON t.id = d.tarifa_envio_id
              WHERE d.cliente_id = ? AND d.predeterminada = 1
           ORDER BY d.id LIMIT 1'
        );
        $stmt->execute([$clienteId]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    public static function total(int $clienteId): int
    {
        $stmt = conexion()->prepare('SELECT COUNT(*) FROM direcciones WHERE cliente_id = ?');
        $stmt->execute([$clienteId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crea una dirección (la primera queda como predeterminada).
     *
     * @param array<string, mixed> $datos  etiqueta, direccion, referencia, tarifa_envio_id, predeterminada
     * @return string|null null si salió bien, o un mensaje de error.
     */
    public static function crear(int $clienteId, array $datos): ?string
    {
        $error = self::validar($datos);
        if ($error !== null) {
            return $error;
        }

        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $predeterminada = (int) ($datos['predeterminada'] ?? 0) === 1 || self::total($clienteId) === 0;
            if ($predeterminada) {
                $pdo->prepare('UPDATE direcciones SET predeterminada = 0 WHERE cliente_id = ?')
                    ->execute([$clienteId]);
            }

            $pdo->prepare(
                'INSERT INTO direcciones (cliente_id, etiqueta, direccion, referencia, tarifa_envio_id, predeterminada)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $clienteId,
                self::etiqueta($datos),
                trim((string) $datos['direccion']),
                self::nulo(trim((string) ($datos['referencia'] ?? ''))),
                self::tarifaId($datos),
                $predeterminada ? 1 : 0,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return 'No se pudo guardar la dirección: ' . $e->getMessage();
        }

        return null;
    }

    /**
     * Crea la primera dirección de una cuenta recién registrada.
     *
     * A diferencia de `crear()`, la zona es opcional: el registro pide la
     * dirección antes de que el cliente haya elegido una zona de envío. Si
     * se indica una zona, se valida contra las tarifas activas.
     *
     * @param array<string, mixed> $datos direccion, tarifa_envio_id, latitud, longitud
     * @return string|null null si salió bien, o un mensaje de error.
     */
    public static function registrarInicial(int $clienteId, array $datos): ?string
    {
        if ($clienteId <= 0) {
            return 'La cuenta no es válida.';
        }

        $direccion = trim((string) ($datos['direccion'] ?? ''));
        if ($direccion === '') {
            return 'La dirección es obligatoria.';
        }
        if (mb_strlen($direccion) > 255) {
            return 'La dirección no puede superar los 255 caracteres.';
        }

        $tarifaId = (int) ($datos['tarifa_envio_id'] ?? 0);
        if ($tarifaId > 0 && Envio::tarifa($tarifaId) === null) {
            return 'La zona de envío no es válida.';
        }

        $latitud = isset($datos['latitud']) && is_numeric($datos['latitud'])
            ? (float) $datos['latitud']
            : null;
        $longitud = isset($datos['longitud']) && is_numeric($datos['longitud'])
            ? (float) $datos['longitud']
            : null;

        if (($latitud === null) !== ($longitud === null)) {
            return 'Marca la ubicación completa (latitud y longitud) o deja ambas vacías.';
        }
        if ($latitud !== null && ($latitud < -90 || $latitud > 90)) {
            return 'La latitud debe estar entre -90 y 90.';
        }
        if ($longitud !== null && ($longitud < -180 || $longitud > 180)) {
            return 'La longitud debe estar entre -180 y 180.';
        }

        conexion()->prepare(
            'INSERT INTO direcciones
                (cliente_id, etiqueta, direccion, referencia, tarifa_envio_id, latitud, longitud, predeterminada)
             VALUES (?, ?, ?, NULL, ?, ?, ?, 1)'
        )->execute([
            $clienteId,
            'Principal',
            $direccion,
            $tarifaId > 0 ? $tarifaId : null,
            $latitud,
            $longitud,
        ]);

        return null;
    }

    /**
     * Actualiza una dirección del cliente.
     *
     * @param array<string, mixed> $datos
     * @return string|null null si salió bien, o un mensaje de error.
     */
    public static function actualizar(int $id, int $clienteId, array $datos): ?string
    {
        if (self::porId($id, $clienteId) === null) {
            return 'Esa dirección ya no existe.';
        }

        $error = self::validar($datos);
        if ($error !== null) {
            return $error;
        }

        $predeterminada = (int) ($datos['predeterminada'] ?? 0) === 1;

        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            if ($predeterminada) {
                $pdo->prepare('UPDATE direcciones SET predeterminada = 0 WHERE cliente_id = ?')
                    ->execute([$clienteId]);
            }

            $pdo->prepare(
                'UPDATE direcciones
                    SET etiqueta = ?, direccion = ?, referencia = ?, tarifa_envio_id = ?, predeterminada = ?
                  WHERE id = ? AND cliente_id = ?'
            )->execute([
                self::etiqueta($datos),
                trim((string) $datos['direccion']),
                self::nulo(trim((string) ($datos['referencia'] ?? ''))),
                self::tarifaId($datos),
                $predeterminada ? 1 : 0,
                $id,
                $clienteId,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return 'No se pudo actualizar la dirección: ' . $e->getMessage();
        }

        return null;
    }

    /**
     * Marca una dirección como predeterminada (y desmarca las demás).
     */
    public static function marcarPredeterminada(int $id, int $clienteId): void
    {
        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE direcciones SET predeterminada = 0 WHERE cliente_id = ?')
                ->execute([$clienteId]);
            $pdo->prepare('UPDATE direcciones SET predeterminada = 1 WHERE id = ? AND cliente_id = ?')
                ->execute([$id, $clienteId]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
        }
    }

    /**
     * Elimina una dirección; si era la predeterminada, hereda esa condición
     * a la más antigua que quede.
     */
    public static function eliminar(int $id, int $clienteId): bool
    {
        $direccion = self::porId($id, $clienteId);
        if ($direccion === null) {
            return false;
        }

        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM direcciones WHERE id = ? AND cliente_id = ?')
                ->execute([$id, $clienteId]);

            if ((int) $direccion['predeterminada'] === 1) {
                $stmt = $pdo->prepare(
                    'SELECT id FROM direcciones WHERE cliente_id = ? ORDER BY id LIMIT 1'
                );
                $stmt->execute([$clienteId]);
                $siguiente = $stmt->fetchColumn();
                if ($siguiente !== false) {
                    $pdo->prepare('UPDATE direcciones SET predeterminada = 1 WHERE id = ?')
                        ->execute([(int) $siguiente]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }

        return true;
    }

    /**
     * Texto inmutable que se guarda en el pedido (snapshot del destino).
     *
     * @param array<string, mixed> $direccion
     */
    public static function texto(array $direccion): string
    {
        $partes = [
            trim((string) ($direccion['etiqueta'] ?? '')),
            trim((string) ($direccion['direccion'] ?? '')),
            trim((string) ($direccion['referencia'] ?? '')),
        ];
        $partes = array_values(array_filter($partes, static fn (string $p): bool => $p !== ''));

        return implode(' — ', $partes);
    }

    /**
     * @param array<string, mixed> $datos
     * @return string|null null si los datos son válidos, o el mensaje.
     */
    private static function validar(array $datos): ?string
    {
        $direccion = trim((string) ($datos['direccion'] ?? ''));
        if ($direccion === '') {
            return 'La dirección es obligatoria.';
        }
        if (mb_strlen($direccion) > 255) {
            return 'La dirección no puede superar los 255 caracteres.';
        }
        if (mb_strlen(trim((string) ($datos['etiqueta'] ?? ''))) > 60) {
            return 'La etiqueta no puede superar los 60 caracteres.';
        }
        if (mb_strlen(trim((string) ($datos['referencia'] ?? ''))) > 180) {
            return 'La referencia no puede superar los 180 caracteres.';
        }
        if (self::tarifaId($datos) === null) {
            // Sin zona no hay costo: el pedido no podría confirmarse.
            return 'Elige una zona de envío.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private static function tarifaId(array $datos): ?int
    {
        $id = (int) ($datos['tarifa_envio_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        // Solo se admite una tarifa vigente: una zona desactivada no cotiza.
        return Envio::tarifa($id) === null ? null : $id;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private static function etiqueta(array $datos): string
    {
        $etiqueta = trim((string) ($datos['etiqueta'] ?? ''));
        return $etiqueta === '' ? 'Principal' : mb_substr($etiqueta, 0, 60);
    }

    private static function nulo(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }
}
