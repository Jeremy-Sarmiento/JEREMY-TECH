<?php

declare(strict_types=1);

namespace Model;

/**
 * Imagen de portada de un producto, guardada en la base de datos.
 *
 * El archivo vive en `producto_imagenes` (BYTEA). No se escribe en disco: en
 * Render el disco es efímero y las imágenes se perderían al reiniciar.
 *
 * Solo se aceptan JPEG, PNG, WebP y GIF reales (se comprueba el contenido,
 * no la extensión) y un máximo de 3 MB. SVG queda fuera a propósito porque
 * puede ejecutar JavaScript servido desde el mismo origen.
 */
final class ImagenProducto
{
    /** Tamaño máximo aceptado del archivo subido. */
    public const MAX_BYTES = 3 * 1024 * 1024;

    /** Tipos MIME admitidos y su extensión. */
    private const MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * Lee `$_FILES['imagen_archivo']`.
     *
     * @param array<string, mixed>|null $archivo
     * @return array{estado: 'vacio'|'error'|'ok', error?: string, datos?: string, mime?: string}
     */
    public static function leer(?array $archivo): array
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['estado' => 'vacio'];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return [
                'estado' => 'error',
                'error'  => $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE
                    ? 'La imagen supera el límite permitido por el servidor.'
                    : 'No se pudo subir la imagen. Inténtalo de nuevo.',
            ];
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['estado' => 'error', 'error' => 'No se pudo leer el archivo subido.'];
        }

        // El tamaño real del archivo manda: el que informa el navegador se
        // puede falsificar.
        $tamano = (int) @filesize($tmp);
        if ($tamano <= 0) {
            $tamano = (int) ($archivo['size'] ?? 0);
        }

        return self::validarArchivo($tmp, $tamano);
    }

    /**
     * Valida un archivo local. Separado de `leer()` para poder probarlo.
     *
     * @return array{estado: 'error'|'ok', error?: string, datos?: string, mime?: string}
     */
    public static function validarArchivo(string $ruta, int $tamano): array
    {
        if ($tamano <= 0 || $tamano > self::MAX_BYTES) {
            return ['estado' => 'error', 'error' => 'La imagen debe pesar como máximo 3 MB.'];
        }

        $info = @getimagesize($ruta);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        if (!isset(self::MIMES[$mime])) {
            return ['estado' => 'error', 'error' => 'La imagen debe ser JPEG, PNG, WebP o GIF.'];
        }

        $datos = @file_get_contents($ruta);
        if ($datos === false || $datos === '') {
            return ['estado' => 'error', 'error' => 'No se pudo leer el archivo subido.'];
        }

        return ['estado' => 'ok', 'datos' => $datos, 'mime' => $mime];
    }

    /**
     * Inserta o reemplaza la imagen de un producto.
     */
    public static function guardar(int $productoId, string $datos, string $mime): bool
    {
        if ($productoId <= 0 || !isset(self::MIMES[$mime]) || $datos === '') {
            return false;
        }

        $stmt = conexion()->prepare(
            'INSERT INTO producto_imagenes (producto_id, mime, datos)
             VALUES (?, ?, ?)
             ON CONFLICT (producto_id) DO UPDATE
                SET mime = EXCLUDED.mime,
                    datos = EXCLUDED.datos,
                    actualizado_en = CURRENT_TIMESTAMP'
        );
        $stmt->bindValue(1, $productoId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $mime, \PDO::PARAM_STR);
        $stmt->bindValue(3, $datos, \PDO::PARAM_LOB);

        return $stmt->execute();
    }

    /**
     * Metadatos de la imagen para servirla; null si el producto no tiene una.
     *
     * @return array{mime: string, datos: string}|null
     */
    public static function deSlug(string $slug): ?array
    {
        $stmt = conexion()->prepare(
            'SELECT i.mime, i.datos
               FROM producto_imagenes i
               JOIN productos p ON p.id = i.producto_id
              WHERE p.slug = ?
              LIMIT 1'
        );
        $stmt->execute([$slug]);
        $fila = $stmt->fetch();

        if (!$fila || !isset(self::MIMES[(string) $fila['mime']])) {
            return null;
        }

        // pdo_pgsql entrega BYTEA como flujo; hay que leerlo antes de servirlo.
        $datos = $fila['datos'];
        if (is_resource($datos)) {
            $datos = stream_get_contents($datos);
        }

        return ['mime' => (string) $fila['mime'], 'datos' => (string) $datos];
    }
}
