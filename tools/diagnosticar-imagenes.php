<?php

declare(strict_types=1);

/**
 * Diagnóstico de rutas de imagen: compara productos (BD) con archivos (disco).
 *
 * Muestra qué rutas de productos no existen en disco y sugiere el nombre
 * correcto si existe una coincidencia ignorando mayúsculas/minúsculas.
 *
 * Uso:
 *   php tools/diagnosticar-imagenes.php
 */

require __DIR__ . '/../config/conexion.php';

$pdo = conexion();

/**
 * 1. Índice de archivos reales en disco (clave lowercase -> ruta real).
 */
$imgDir = __DIR__ . '/../public/img';
$discoPorLower = [];
$iterador = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($imgDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterador as $archivo) {
    if (!$archivo->isFile()) {
        continue;
    }
    $rutaRelativa = 'img/' . str_replace('\\', '/', substr($archivo->getPathname(), strlen($imgDir) + 1));
    $discoPorLower[strtolower($rutaRelativa)] = $rutaRelativa;
}

/**
 * 2. Productos con imagen en disco (img/...).
 */
$stmt = $pdo->prepare(
    "SELECT p.id, p.slug, p.nombre, p.imagen
       FROM productos p
      WHERE p.imagen IS NOT NULL AND p.imagen <> ''
        AND p.imagen LIKE 'img/%'
      ORDER BY p.id"
);
$stmt->execute();
$productos = $stmt->fetchAll();

echo "Productos con imagen en disco: " . count($productos) . "\n\n";

$ok = 0;
$faltan = 0;
$sugerencias = 0;

foreach ($productos as $p) {
    $id = (int) $p['id'];
    $slug = (string) ($p['slug'] ?? '');
    $nombre = (string) ($p['nombre'] ?? '');
    $rutaBd = (string) ($p['imagen'] ?? '');

    $existe = isset($discoPorLower[strtolower($rutaBd)]);

    if ($existe) {
        $ok++;
        $rutaReal = $discoPorLower[strtolower($rutaBd)];
        if ($rutaReal !== $rutaBd) {
            echo "  [CASE] id=$id $slug — BD: $rutaBd | disco: $rutaReal\n";
            $sugerencias++;
        }
    } else {
        $faltan++;
        echo "  [FALTA] id=$id $slug — BD: $rutaBd\n";

        // Sugerencia: mismo nombre de archivo, cualquier carpeta
        $nombreArchivo = basename($rutaBd);
        $candidatos = [];
        foreach ($discoPorLower as $lower => $real) {
            if (strtolower(basename($real)) === strtolower($nombreArchivo)) {
                $candidatos[] = $real;
            }
        }
        if ($candidatos) {
            echo "      ¿Quisiste decir?: " . implode(', ', $candidatos) . "\n";
            $sugerencias++;
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Rutas correctas:   $ok\n";
echo "Rutas faltantes:   $faltan\n";
echo "Con sugerencia:    $sugerencias\n";

echo "\n=== LISTO ===\n";
echo "Si hay sugerencias, actualiza las rutas en la BD y vuelve a ejecutar:\n";
echo "  php tools/migrar-imagenes-a-bd.php\n";
