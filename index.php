<?php
declare(strict_types=1);

// El punto de entrada real es public/index.php. Este archivo evita que
// Apache muestre el listado de directorios al entrar a la raíz del proyecto.
header('Location: public/index.php', true, 302);
exit;
