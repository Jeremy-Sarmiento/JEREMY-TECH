<?php
/** @var array<int, string> $gruposEstilos Grupos que necesita este layout, en orden. */
$archivosEstilos = require BASE_PATH . '/config/estilos.php';
?>
<?php foreach ($gruposEstilos as $grupoEstilos): ?>
  <?php foreach ($archivosEstilos[$grupoEstilos] as $archivoEstilos): ?>
    <link href="css/<?= htmlspecialchars($archivoEstilos, ENT_QUOTES, 'UTF-8') ?>?v=<?= (int) filemtime(BASE_PATH . '/public/css/' . $archivoEstilos) ?>" rel="stylesheet"/>
  <?php endforeach; ?>
  <?php if ($grupoEstilos === 'auth' && ($rutaVista ?? '') === 'login'): ?>
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" rel="stylesheet"/>
  <?php endif; ?>
<?php endforeach; ?>
