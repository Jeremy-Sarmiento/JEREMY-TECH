<?php

declare(strict_types=1);

namespace Model;

final class Banner
{
    /**
     * Banners activos ordenados para la tienda pública.
     * No carga el binario `datos` para mayor ligereza.
     *
     * @return array<int, array{id: int, titulo: string, subtitulo: string, enlace: string, orden: int}>
     */
    public static function activos(): array
    {
        $stmt = conexion()->query(
            'SELECT id, titulo, subtitulo, enlace, orden
               FROM banners
              WHERE activo = 1
              ORDER BY orden ASC, id ASC'
        );
        $filas = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $res = [];
        foreach ($filas as $f) {
            $res[] = [
                'id'        => (int) $f['id'],
                'titulo'    => (string) $f['titulo'],
                'subtitulo' => (string) ($f['subtitulo'] ?? ''),
                'enlace'    => (string) ($f['enlace'] ?? ''),
                'orden'     => (int) $f['orden'],
            ];
        }
        return $res;
    }

    /**
     * Todos los banners para el panel de administración.
     *
     * @return array<int, array{id: int, titulo: string, subtitulo: string, enlace: string, orden: int, activo: int, mime: string, bytes: int, actualizado_en: string}>
     */
    public static function todos(): array
    {
        $stmt = conexion()->query(
            'SELECT id, titulo, subtitulo, enlace, orden, activo, mime,
                    octet_length(datos) AS bytes, actualizado_en
               FROM banners
              ORDER BY orden ASC, id ASC'
        );
        $filas = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $res = [];
        foreach ($filas as $f) {
            $res[] = [
                'id'             => (int) $f['id'],
                'titulo'         => (string) $f['titulo'],
                'subtitulo'      => (string) ($f['subtitulo'] ?? ''),
                'enlace'         => (string) ($f['enlace'] ?? ''),
                'orden'          => (int) $f['orden'],
                'activo'         => (int) $f['activo'],
                'mime'           => (string) $f['mime'],
                'bytes'          => (int) ($f['bytes'] ?? 0),
                'actualizado_en' => (string) ($f['actualizado_en'] ?? ''),
            ];
        }
        return $res;
    }

    /**
     * Obtiene un banner por ID incluyendo los datos binarios de la imagen.
     *
     * @return array{id: int, titulo: string, subtitulo: string, enlace: string, orden: int, activo: int, mime: string, datos: string}|null
     */
    public static function obtener(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT id, titulo, subtitulo, enlace, orden, activo, mime, datos
               FROM banners
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        /** @var array{id:int, titulo:string, subtitulo:string, enlace:string, orden:int, activo:int, mime:string, datos:string|resource}|false $fila */
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$fila) {
            return null;
        }

        $datos = $fila['datos'];
        if (is_resource($datos)) {
            $datos = stream_get_contents($datos);
        }

        return [
            'id'        => (int) $fila['id'],
            'titulo'    => (string) $fila['titulo'],
            'subtitulo' => (string) ($fila['subtitulo'] ?? ''),
            'enlace'    => (string) ($fila['enlace'] ?? ''),
            'orden'     => (int) $fila['orden'],
            'activo'    => (int) $fila['activo'],
            'mime'      => (string) $fila['mime'],
            'datos'     => (string) $datos,
        ];
    }

    /**
     * Guarda (crea o edita) un banner.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed>|null $archivo
     * @return array{ok: bool, id?: int, error?: string}
     */
    public static function guardar(array $post, ?array $archivo): array
    {
        $entrada = [];
        foreach (['id', 'titulo', 'subtitulo', 'enlace', 'orden', 'activo'] as $campo) {
            $valor = $post[$campo] ?? (in_array($campo, ['id', 'orden', 'activo'], true) ? '0' : '');
            if (!is_scalar($valor)) {
                return ['ok' => false, 'error' => 'Los datos del banner no son válidos.'];
            }
            $entrada[$campo] = (string) $valor;
        }
        $id = filter_var($entrada['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        $titulo = trim($entrada['titulo']);
        $subtitulo = trim($entrada['subtitulo']);
        $enlace = trim($entrada['enlace']);
        $orden = filter_var($entrada['orden'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        $activo = $entrada['activo'];

        if ($id === false || $orden === false || !in_array($activo, ['0', '1'], true)) {
            return ['ok' => false, 'error' => 'El identificador, el orden o el estado no son válidos.'];
        }

        if ($titulo === '' || mb_strlen($titulo) > 120) {
            return ['ok' => false, 'error' => 'El título debe tener entre 1 y 120 caracteres.'];
        }
        if (mb_strlen($subtitulo) > 255) {
            return ['ok' => false, 'error' => 'El subtítulo no debe superar 255 caracteres.'];
        }
        if (mb_strlen($enlace) > 255) {
            return ['ok' => false, 'error' => 'El enlace no debe superar 255 caracteres.'];
        }
        if (!self::enlaceValido($enlace)) {
            return ['ok' => false, 'error' => 'Usa un enlace interno (?ruta=...) o una URL http(s) válida.'];
        }

        $subida = ImagenProducto::leer($archivo);
        if ($subida['estado'] === 'error') {
            return ['ok' => false, 'error' => $subida['error'] ?? 'La imagen no es válida.'];
        }
        if ($subida['estado'] === 'vacio' && $id === 0) {
            return ['ok' => false, 'error' => 'Debes subir una imagen para el nuevo banner.'];
        }

        $datosImagen = $subida['datos'] ?? null;
        $mimeImagen = $subida['mime'] ?? null;

        $pdo = conexion();
        if ($id > 0) {
            if ($datosImagen !== null && $mimeImagen !== null) {
                $stmt = $pdo->prepare(
                    'UPDATE banners
                        SET titulo = ?, subtitulo = ?, enlace = ?, orden = ?, activo = ?,
                            mime = ?, datos = ?, actualizado_en = CURRENT_TIMESTAMP
                      WHERE id = ?'
                );
                $stmt->bindValue(1, $titulo, \PDO::PARAM_STR);
                $stmt->bindValue(2, $subtitulo, \PDO::PARAM_STR);
                $stmt->bindValue(3, $enlace, \PDO::PARAM_STR);
                $stmt->bindValue(4, $orden, \PDO::PARAM_INT);
                $stmt->bindValue(5, $activo, \PDO::PARAM_INT);
                $stmt->bindValue(6, $mimeImagen, \PDO::PARAM_STR);
                $stmt->bindValue(7, $datosImagen, \PDO::PARAM_LOB);
                $stmt->bindValue(8, $id, \PDO::PARAM_INT);
                $ok = $stmt->execute();
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE banners
                        SET titulo = ?, subtitulo = ?, enlace = ?, orden = ?, activo = ?,
                            actualizado_en = CURRENT_TIMESTAMP
                      WHERE id = ?'
                );
                $ok = $stmt->execute([$titulo, $subtitulo, $enlace, $orden, $activo, $id]);
            }
            return $ok && $stmt->rowCount() === 1
                ? ['ok' => true, 'id' => $id]
                : ['ok' => false, 'error' => 'El banner ya no existe o no se pudo actualizar.'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO banners (titulo, subtitulo, enlace, orden, activo, mime, datos)
             VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id'
        );
        $stmt->bindValue(1, $titulo, \PDO::PARAM_STR);
        $stmt->bindValue(2, $subtitulo, \PDO::PARAM_STR);
        $stmt->bindValue(3, $enlace, \PDO::PARAM_STR);
        $stmt->bindValue(4, $orden, \PDO::PARAM_INT);
        $stmt->bindValue(5, $activo, \PDO::PARAM_INT);
        $stmt->bindValue(6, (string) $mimeImagen, \PDO::PARAM_STR);
        $stmt->bindValue(7, (string) $datosImagen, \PDO::PARAM_LOB);

        return $stmt->execute()
            ? ['ok' => true, 'id' => (int) $stmt->fetchColumn()]
            : ['ok' => false, 'error' => 'No se pudo crear el banner.'];
    }

    public static function enlaceValido(string $enlace): bool
    {
        if ($enlace === '') {
            return true;
        }
        if (preg_match('/[\s<>"\'\\\\]/u', $enlace) !== 0) {
            return false;
        }
        if (str_starts_with($enlace, '?') || (str_starts_with($enlace, '/') && !str_starts_with($enlace, '//'))) {
            return true;
        }
        return filter_var($enlace, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($enlace, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /**
     * Elimina un banner por ID.
     */
    public static function eliminar(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = conexion()->prepare('DELETE FROM banners WHERE id = ?');
        return $stmt->execute([$id]) && $stmt->rowCount() === 1;
    }

    /**
     * Alterna o asigna el estado activo de un banner.
     */
    public static function cambiarEstado(int $id, ?int $activo = null): bool
    {
        if ($id <= 0) {
            return false;
        }
        if ($activo !== null) {
            $stmt = conexion()->prepare('UPDATE banners SET activo = ? WHERE id = ?');
            return $stmt->execute([$activo === 1 ? 1 : 0, $id]) && $stmt->rowCount() === 1;
        }
        $stmt = conexion()->prepare('UPDATE banners SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END WHERE id = ?');
        return $stmt->execute([$id]) && $stmt->rowCount() === 1;
    }
}
