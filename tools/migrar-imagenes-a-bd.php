<?php

declare(strict_types=1);

/**
 * Migra las imágenes de producto que viven en disco (`public/img/...`)
 * a la tabla `producto_imagenes` (BYTEA).
 *
 * Uso (desde la raíz del proyecto):
 *   php tools/migrar-imagenes-a-bd.php
 *
 * Solo migra productos cuya columna `imagen` sea una ruta interna
 * (`img/...`) y que NO tengan ya una imagen en `producto_imagenes`.
 * Las imágenes SVG se saltan (por seguridad, igual que en el upload).
 */

require __DIR__ . '/../config/conexion.php';

use Model\ImagenProducto;

$pdo = conexion();

/**
 * Productos con imagen en disco y sin imagen en BD.
 */
$stmt = $pdo->prepare(
    "SELECT p.id, p.slug, p.imagen
       FROM productos p
      LEFT JOIN producto_imagenes i ON i.producto_id = p.id
      WHERE i.producto_id IS NULL
        AND p.imagen IS NOT NULL
        AND p.imagen <> ''
        AND p.imagen LIKE 'img/%'
      ORDER BY p.id"
);
$stmt->execute();
$productos = $stmt->fetchAll();

if (!$productos) {
    echo "No hay productos con imagen en disco para migrar.\n";
    exit;
}

echo "Productos con imagen en disco sin migrar: " . count($productos) . "\n\n";

$ok = 0;
$error = 0;
$skip = 0;

foreach ($productos as $p) {
    $productoId = (int) $p['id'];
    $slug = (string) ($p['slug'] ?? '');
    $rutaDisco = (string) ($p['imagen'] ?? '');

    if ($rutaDisco === '') {
        continue;
    }

    // Resolver ruta en disco: la ruta original o su versión .webp
    $candidatos = [
        preg_replace('/\.(png|jpe?g)$/i', '.webp', $rutaDisco),
        $rutaDisco,
    ];
    $archivo = null;
    foreach ($candidatos as $c) {
        $rutaCompleta = __DIR__ . '/../public/' . ltrim((string) $c, '/');
        if (is_file($rutaCompleta)) {
            $archivo = $rutaCompleta;
            break;
        }
    }

    if ($archivo === null) {
        echo "  [SKIP] $slug — archivo no existe: $rutaDisco\n";
        $skip++;
        continue;
    }

    // Detectar MIME real (no confiar en la extensión)
    $info = @getimagesize($archivo);
    $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
    if ($mime === '' && str_ends_with($archivo, '.webp')) {
        $mime = 'image/webp';
    }

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
        echo "  [SKIP] $slug — no es imagen válida o es SVG: $rutaDisco ($mime)\n";
        $skip++;
        continue;
    }

    $datos = @file_get_contents($archivo);
    if ($datos === false || $datos === '') {
        echo "  [ERROR] $slug — no se pudo leer el archivo: $rutaDisco\n";
        $error++;
        continue;
    }

    // Convertir a WebP si aún no lo está
    if ($mime !== 'image/webp') {
        $webp = ImagenProducto::aWebp($datos, $mime);
        if ($webp !== null) {
            $datos = $webp;
            $mime = 'image/webp';
        }
    }

    $guardada = ImagenProducto::guardar($productoId, $datos, $mime);
    if ($guardada) {
        $tamano = strlen($datos);
        echo "  [OK]   $slug — guardado en BD ($mime, $tamano bytes)\n";
        $ok++;
    } else {
        echo "  [ERROR] $slug — no se pudo guardar en BD\n";
        $error++;
    }
}

echo "\n--- Resumen ---\n";
echo "OK:   $ok\n";
echo "Error: $error\n";
echo "Skip:  $skip\n";
