<?php
/** @var string|null $tituloAdmin */
$tituloAdmin = $tituloAdmin ?? 'Panel de administración';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="csrf-token" content="<?= htmlspecialchars(\Security\Proteccion::token(), ENT_QUOTES, 'UTF-8') ?>"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="color-scheme" content="dark"/>
  <link href="img/favicon.svg" rel="icon" type="image/svg+xml"/>
  <link href="favicon.ico" rel="icon" sizes="any"/>
  <link href="img/apple-touch-icon.png" rel="apple-touch-icon"/>
  <title><?= htmlspecialchars($tituloAdmin, ENT_QUOTES, 'UTF-8') ?> · Jeremy Tech</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
  <?php
   $gruposEstilos = ['admin', 'tema'];
   require BASE_PATH . '/views/partials/estilos.php';
  ?>
</head>
<body class="body-admin-page">
