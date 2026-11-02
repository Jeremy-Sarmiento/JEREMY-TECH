<?php

declare(strict_types=1);

/**
 * Configuración y conexión PDO segura — Jeremy Tech (Jeremy Tech MVC).
 *
 * Contiene:
 *  1. Constantes de acceso a la base de datos.
 *  2. Autocarga PSR-4 mínima (Model\ -> src/Model, Controller\ -> src/Controller).
 *  3. Conexión PDO única con atributos seguros.
 *
 * NOTA: el esquema y las semillas ya NO se crean desde PHP. Ahora viven en el
 * volcado `database/jeremy_tech_full.sql`. Antes de servir la app por primera
 * vez (o tras clonar), ejecuta:
 *
 *   createdb jeremy_tech
 *   psql -d jeremy_tech -f database/jeremy_tech_full.sql
 *
 * El volcado recrea las tablas y carga los datos completos, así que debe
 * importarse sobre una base vacía (no es idempotente).
 *
 * Las credenciales ya NO se escriben aquí: se leen desde el archivo `.env`
 * (no versionado), que es la fuente principal. Copia `.env.example` a `.env`
 * y rellena los valores.
 *
 * `.ht.env` se admite como respaldo opcional: si existe, solo completa las
 * claves que no estén definidas en `.env`. Cualquier valor escrito en `.env`
 * tiene prioridad.
 *
 * En este servidor Apache el archivo `.env` tiene una ACL de solo lectura para
 * el usuario `http` (PHP lo necesita) y una regla en
 * `/etc/httpd/conf/conf.d/jeremy-tech-env.conf` que impide servirlo por HTTP.
 */

// Raíz absoluta del proyecto (un nivel arriba de config/)
define('BASE_PATH', dirname(__DIR__));

// ---------------------------------------------------------------------------
// 1. Credenciales (desde el archivo .env, fuera del control de versiones)
// ---------------------------------------------------------------------------
/**
 * Carga las variables definidas en BASE_PATH/.env hacia $_ENV/getenv().
 * Formato admitido: líneas CLAVE=VALOR, comentarios con # y valores
 * opcionalmente entre comillas simples o dobles.
 */
function cargar_env(string $ruta): void
{
    if (!is_file($ruta) || !is_readable($ruta)) {
        return;
    }

    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        return;
    }

    // Un .env guardado desde Windows puede llevar BOM. Sin quitarlo, la
    // PRIMERA clave queda "\xEF\xBB\xBFDB_HOST" y se ignora en silencio,
    // con lo que la app cae al fallback sin ningún aviso.
    if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
        $contenido = substr($contenido, 3);
    }

    foreach (preg_split('/\r\n|\r|\n/', $contenido) as $linea) {
        $linea = trim($linea);

        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }

        [$clave, $valor] = array_pad(explode('=', $linea, 2), 2, '');
        $clave = trim($clave);
        $valor = trim($valor);

        if ($clave === '') {
            continue;
        }

        // Comentario en línea (`CLAVE=valor # nota`): solo si el valor NO va
        // entre comillas y hay un espacio delante, para no romper una
        // contraseña que contenga '#'. Si la lleva, se cita y se respeta.
        if ($valor !== '' && $valor[0] !== '"' && $valor[0] !== "'") {
            $corte = strpos($valor, ' #');
            if ($corte !== false) {
                $valor = rtrim(substr($valor, 0, $corte));
            }
        }

        if (strlen($valor) >= 2
            && (($valor[0] === '"' && str_ends_with($valor, '"'))
                || ($valor[0] === "'" && str_ends_with($valor, "'")))
        ) {
            $valor = substr($valor, 1, -1);
        }

        // No sobrescribir variables ya definidas en el entorno real.
        if (getenv($clave) !== false) {
            continue;
        }

        putenv("$clave=$valor");
        $_ENV[$clave] = $valor;
    }
}

// `.env` manda. Después se intenta `.ht.env` únicamente para rellenar claves
// ausentes: `cargar_env()` no sobrescribe variables ya definidas.
cargar_env(BASE_PATH . '/.env');
cargar_env(BASE_PATH . '/.ht.env');

/**
 * Valor de una variable de entorno, o $porDefecto si no está definida o
 * viene vacía.
 *
 * OJO: NO se usa el atajo `?:`. `getenv()` devuelve el string '0', que PHP
 * considera falso, así que `getenv('DB_PASS') ?: ''` convertiría una
 * contraseña legítima "0" en contraseña vacía.
 */
function env(string $clave, string $porDefecto = ''): string
{
    $valor = getenv($clave);
    return ($valor === false || $valor === '') ? $porDefecto : $valor;
}

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '5432'));
define('DB_NAME', env('DB_NAME', 'jeremy_tech'));
define('DB_USER', env('DB_USER', 'postgres'));
define('DB_PASS', env('DB_PASS', ''));

// Directorio de los correos simulados. Debe quedar FUERA del DocumentRoot:
// los `.htaccess` no protegen nada si Apache tiene AllowOverride None.
// Se configura en `.env` (o en `.ht.env`, si existe, como respaldo); el
// fallback queda dentro del proyecto para despliegues con DocumentRoot en
// `public/`.
$mailStorage = env('MAIL_STORAGE_PATH', BASE_PATH . '/storage/mail');
if (!str_starts_with($mailStorage, '/')) {
    $mailStorage = BASE_PATH . '/' . $mailStorage;
}
define('MAIL_STORAGE_PATH', rtrim($mailStorage, '/'));

// URL pública absoluta. Se usa en los enlaces de los correos simulados:
// sin ella, un enlace relativo no funciona al abrir el archivo `.eml`.
// En producción conviene fijarla en `.env` (p. ej. APP_URL=https://...).
// Si no está definida, se deduce del host actual y de la ruta del script.
if (!defined('APP_URL')) {
    $appUrl = env('APP_URL');
    if ($appUrl === '') {
        if (PHP_SAPI === 'cli') {
            // En CLI no hay host ni ruta pública; se evita construir una URL
            // con la ruta del script de turno (p. ej. /tmp/...).
            $appUrl = 'http://localhost';
        } else {
            $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            if ($host === '') {
                $host = 'localhost';
            }
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
            $base = rtrim(dirname($script), '/');
            if ($base === '/' || $base === '.') {
                $base = '';
            }
            $appUrl = $esquema . '://' . $host . $base;
        }
    }
    define('APP_URL', rtrim($appUrl, '/'));
}

// Zona horaria de la app. El sistema y PostgreSQL corren en America/Lima; sin
// fijarla, PHP queda en UTC y la lógica que compara fechas con `date()`
// (p.ej. la vigencia de los cupones en Carrito::aplicarCupon) se evalúa
// 5 horas desplazada: a las 20:00 en Lima un cupón que vence hoy ya parece
// vencido porque para UTC es mañana.
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Lima'));
date_default_timezone_set(APP_TIMEZONE);

// ---------------------------------------------------------------------------
// 2. Autocarga PSR-4 mínima
// ---------------------------------------------------------------------------
spl_autoload_register(function (string $clase): void {
    $prefijo = 'Controller\\';
    if (str_starts_with($clase, $prefijo)) {
        $ruta = BASE_PATH . '/src/Controller/' . substr($clase, strlen($prefijo)) . '.php';
        if (is_file($ruta)) {
            require $ruta;
        }
        return;
    }

    $prefijo = 'Model\\';
    if (str_starts_with($clase, $prefijo)) {
        $ruta = BASE_PATH . '/src/Model/' . substr($clase, strlen($prefijo)) . '.php';
        if (is_file($ruta)) {
            require $ruta;
        }
        return;
    }

    $prefijo = 'Security\\';
    if (str_starts_with($clase, $prefijo)) {
        $ruta = BASE_PATH . '/src/Security/' . substr($clase, strlen($prefijo)) . '.php';
        if (is_file($ruta)) {
            require $ruta;
        }
    }
});

// ---------------------------------------------------------------------------
// 3. Conexión PDO segura (patrón singleton)
// ---------------------------------------------------------------------------
/**
 * Devuelve la instancia única de PDO hacia PostgreSQL.
 */
function conexion(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $error) {
        http_response_code(500);
        die(
            "No se pudo conectar a la base de datos '" . DB_NAME . "'. Verifica que " .
            'PostgreSQL esté encendido y que la base exista (createdb jeremy_tech).<br>' .
            'Detalle técnico: ' . htmlspecialchars($error->getMessage())
        );
    }

    // Alinea la zona horaria de la SESIÓN con la de PHP. Las columnas con
    // CURRENT_TIMESTAMP escriben con la zona de la sesión: si una de las dos
    // está en UTC y la otra en Lima, los `creado_en` salen 5 horas corridos
    // respecto a cualquier `date()` de PHP.
    // Best-effort: si el rol no puede cambiarla, la app sigue viva.
    try {
        $pdo->exec('SET TIME ZONE ' . $pdo->quote(APP_TIMEZONE));
        $pdo->exec("SET client_encoding = 'UTF8'");
    } catch (Throwable) {
        // El servidor no permite cambiar la zona: se queda con la suya.
    }

    return $pdo;
}
