<?php
/** @var string|null $tituloAdmin */
$tituloAdmin = $tituloAdmin ?? 'Panel de administración';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="color-scheme" content="dark"/>
  <title><?= htmlspecialchars($tituloAdmin, ENT_QUOTES, 'UTF-8') ?> · Jeremy Tech</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="css/admin.css" rel="stylesheet"/>
</head>
<body class="body-admin-page">
