<?php

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Página no encontrada | Jeremy Tech</title>
</head>
<body>
  <main>
    <h1>Página no encontrada</h1>
    <p>La página que buscas no existe.</p>
    <a href="<?= htmlspecialchars($basePublica . '/index.php?ruta=catalogo', ENT_QUOTES, 'UTF-8') ?>">Volver al catálogo de Jeremy Tech</a>
  </main>
</body>
</html>
