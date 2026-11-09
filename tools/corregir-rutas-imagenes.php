<?php

declare(strict_types=1);

/**
 * Corrige automáticamente las rutas de imagen en la BD cuando no coinciden
 * con los archivos en disco (problema de mayúsculas/minúsculas en Linux).
 *
 * Solo corrige cuando hay UNA coincidencia clara (mismo nombre de archivo,
 * ignorando mayúsculas). Si hay varios candidatos o ninguno, lo reporta sin
 * tocar nada.
 *
 * Uso:
 *   php tools/corregir-rutas-imagenes.php      (solo muestra qué cambiaría)
 *   php tools/corregir-rutas-imagenes.php --fix (aplica los cambios en la BD)
 */

require __DIR__ . '/../config/conexion.php';

$aplicar = in_array('--fix', $argv, true);

$pdo = conexion();

/**
 * 1. Índice de archivos reales en disco: lowercase -> ruta real.
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
 * 2. Índice por nombre de archivo (basename lowercase -> lista de rutas reales).
 *     Se usa cuando la ruta completa no coincide pero el nombre del archivo sí.
 */
$basenameCandidatos = [];
foreach ($discoPorLower as $lower => $real) {
    $base = strtolower(basename($real));
    $basenameCandidatos[$base][] = $real;
}

/**
 * 3. Productos con imagen en disco.
 */
$stmt = $pdo->prepare(
    "SELECT p.id, p.slug, p.imagen
       FROM productos p
      WHERE p.imagen IS NOT NULL AND p.imagen <> ''
        AND p.imagen LIKE 'img/%'
      ORDER BY p.id"
);
$stmt->execute();
$productos = $stmt->fetchAll();

echo "Productos con imagen en disco: " . count($productos) . "\n";
echo $aplicar ? "MODO: APLICAR CAMBIOS\n" : "MODO: SOLO MOSTRAR (usa --fix para aplicar)\n\n";

$corregidos = 0;
$sinCambio = 0;
$ambiguos = 0;
$sinCandidato = 0;

foreach ($productos as $p) {
    $id = (int) $p['id'];
    $slug = (string) ($p['slug'] ?? '');
    $rutaBd = (string) ($p['imagen'] ?? '');
    $lower = strtolower($rutaBd);

    // Caso 1: la ruta exacta ya existe (quizás con diferente case)
    if (isset($discoPorLower[$lower])) {
        $rutaReal = $discoPorLower[$lower];
        if ($rutaReal !== $rutaBd) {
            echo "  [CORREGIR] id=$id $slug\n";
            echo "      BD:      $rutaBd\n";
            echo "      Disco:   $rutaReal\n";
            if ($aplicar) {
                $u = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
                $u->execute([$rutaReal, $id]);
            }
            $corregidos++;
        } else {
            $sinCambio++;
        }
        continue;
    }

    // Caso 2: ruta no existe, buscar por nombre de archivo
    $nombreArchivo = basename($rutaBd);
    $candidatos = $basenameCandidatos[strtolower($nombreArchivo)] ?? [];

    if (count($candidatos) === 1) {
        $rutaReal = $candidatos[0];
        echo "  [CORREGIR] id=$id $slug\n";
        echo "      BD:      $rutaBd\n";
        echo "      Disco:   $rutaReal\n";
        if ($aplicar) {
            $u = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
            $u->execute([$rutaReal, $id]);
        }
        $corregidos++;
    } elseif (count($candidatos) > 1) {
        echo "  [AMBIGUO] id=$id $slug — BD: $rutaBd\n";
        echo "      Candidatos: " . implode(', ', $candidatos) . "\n";
        $ambiguos++;
    } else {
        echo "  [SIN CANDIDATO] id=$id $slug — BD: $rutaBd\n";
        $sinCandidato++;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Ya correctas:      $sinCambio\n";
echo "Corregidas:        $corregidos\n";
echo "Ambiguas:          $ambiguos\n";
echo "Sin candidato:     $sinCandidato\n";

if (!$aplicar) {
    echo "\nPara aplicar los cambios ejecuta:\n";
    echo "  php tools/corregir-rutas-imagenes.php --fix\n";
}
