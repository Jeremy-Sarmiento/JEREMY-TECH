<?php

declare(strict_types=1);

/**
 * Diagnóstico y listado ordenado de imágenes de producto en disco.
 *
 * Escanea public/img/ recursivamente, agrupa por carpeta y consulta la BD
 * para mostrar qué productos usan cada imagen.
 *
 * Uso:
 *   php tools/listar-imagenes.php
 */

require __DIR__ . '/../config/conexion.php';

$pdo = conexion();

/**
 * 1. Leer de la BD qué productos tienen imagen en disco y a qué archivo apuntan.
 */
$stmt = $pdo->prepare(
    "SELECT p.id, p.slug, p.nombre, p.imagen
       FROM productos p
      WHERE p.imagen IS NOT NULL AND p.imagen <> ''
        AND p.imagen LIKE 'img/%'
      ORDER BY p.imagen, p.id"
);
$stmt->execute();
$productosPorImagen = [];
foreach ($stmt->fetchAll() as $fila) {
    $ruta = (string) $fila['imagen'];
    $productosPorImagen[$ruta][] = [
        'id' => (int) $fila['id'],
        'slug' => (string) $fila['slug'],
        'nombre' => (string) $fila['nombre'],
    ];
}

/**
 * 2. Escanear public/img/ recursivamente y listar archivos reales.
 */
$imgDir = __DIR__ . '/../public/img';
$archivosDisco = [];
$iterador = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($imgDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterador as $archivo) {
    if (!$archivo->isFile()) {
        continue;
    }
    $rutaRelativa = 'img/' . str_replace('\\', '/', substr($archivo->getPathname(), strlen($imgDir) + 1));
    $archivosDisco[$rutaRelativa] = [
        'ruta' => $rutaRelativa,
        'tamano' => (int) $archivo->getSize(),
        'mime' => mime_content_type($archivo->getPathname()),
    ];
}

/**
 * 3. Imprimir reporte ordenado por carpeta.
 */
echo "=== IMÁGENES EN DISCO (public/img/) ===\n\n";

$carpetas = [];
foreach ($archivosDisco as $ruta => $info) {
    $partes = explode('/', $ruta);
    $categoria = $partes[0] ?? 'raiz';
    $carpetas[$categoria][] = $ruta;
}

ksort($carpetas);

$totalEnDisco = count($archivosDisco);
$totalReferenciadas = 0;
$totalHuérfanas = 0;

foreach ($carpetas as $categoria => $rutas) {
    sort($rutas);
    echo "## $categoria (" . count($rutas) . " archivos)\n";
    foreach ($rutas as $ruta) {
        $info = $archivosDisco[$ruta];
        $productos = $productosPorImagen[$ruta] ?? [];
        $totalReferenciadas += count($productos);

        $estado = count($productos) > 0 ? 'usada' : 'HUÉRFANA (sin producto)';
        $color = count($productos) > 0 ? '' : '  <-- ';
        echo $color . sprintf(
            "  %-40s %10s bytes %-10s %s",
            $ruta,
            $info['tamano'],
            $estado,
            count($productos) > 0 ? '(' . count($productos) . ' producto' . (count($productos) > 1 ? 's' : '') . ')' : ''
        ) . "\n";

        foreach ($productos as $prod) {
            echo "            -> id=" . $prod['id'] . " slug=" . $prod['slug'] . " nombre=" . $prod['nombre'] . "\n";
        }
    }
    echo "\n";
}

$totalHuérfanas = $totalEnDisco - count(array_keys($productosPorImagen));
$productosSinArchivo = 0;

echo "=== RESUMEN ===\n";
echo "Archivos en disco:        $totalEnDisco\n";
echo "Imágenes referenciadas:   " . count(array_keys($productosPorImagen)) . "\n";
echo "Imágenes huérfanas:       $totalHuérfanas\n";

// Productos que apuntan a archivo que no existe en disco
foreach ($productosPorImagen as $ruta => $productos) {
    if (!isset($archivosDisco[$ruta])) {
        $productosSinArchivo += count($productos);
        foreach ($productos as $prod) {
            echo "  FALTA EN DISCO: id=" . $prod['id'] . " slug=" . $prod['slug'] . " apunta a $ruta\n";
        }
    }
}
echo "Productos sin archivo:    $productosSinArchivo\n";

echo "\n=== LISTO ===\n";
echo "Puedes revisar la lista arriba y luego ejecutar:\n";
echo "  php tools/migrar-imagenes-a-bd.php\n";
