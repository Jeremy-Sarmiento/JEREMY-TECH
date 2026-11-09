<?php

require_once __DIR__ . '/../vendor/autoload.php';

putenv('DB_HOST=127.0.0.1');
putenv('DB_NAME=jeremy_tech_test');
putenv('DB_USER=root');
putenv('DB_PASS=admin');
putenv('APP_TIMEZONE=America/Lima');
putenv('APP_URL=http://localhost');
putenv('MAIL_STORAGE_PATH=/tmp/opencode/mail');
putenv('CHAT_MODE=rules');
putenv('PAGOS_MODO=simulado');

require_once __DIR__ . '/../config/conexion.php';

function testPdo(): \PDO
{
    $pdo = new \PDO(
        'mysql:host=127.0.0.1;port=' . DB_PORT . ';dbname=jeremy_tech_test;charset=utf8mb4',
        'root',
        'admin',
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );

    // Misma zona horaria de sesión que conexion(). Sin esto, en un host en UTC
    // (como el runner de GitHub) los TIMESTAMP insertados y los rangos
    // consultados se desplazan 5 horas y las pruebas de fechas fallan.
    $offset = (new \DateTimeImmutable('now', new \DateTimeZone(APP_TIMEZONE)))->format('P');
    $pdo->exec('SET time_zone = ' . $pdo->quote($offset));

    return $pdo;
}

function ensureTestDb(): void
{
    $server = new \PDO(
        'mysql:host=127.0.0.1;port=' . DB_PORT . ';charset=utf8mb4',
        'root',
        'admin',
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
    $server->exec('CREATE DATABASE IF NOT EXISTS `jeremy_tech_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = testPdo();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
    if (count($tables) < 30 || !in_array('productos_id_seq', $tables, true)) {
        // PDO no entiende DELIMITER: se ejecuta el DDL y luego cada trigger.
        $sql = str_replace(
            '`jeremy_tech`',
            '`jeremy_tech_test`',
            file_get_contents(__DIR__ . '/../database/jeremy_tech_estructura.sql')
        );
        [$ddl, $resto] = preg_split('/^DELIMITER ;;\r?$/m', $sql, 2);
        $pdo->exec($ddl);
        [$triggers] = preg_split('/^DELIMITER ;\r?$/m', $resto, 2);
        foreach (preg_split('/;;\s*/', $triggers) as $trigger) {
            if (trim($trigger) !== '') {
                $pdo->exec($trigger);
            }
        }
        $datos = str_replace(
            '`jeremy_tech`',
            '`jeremy_tech_test`',
            file_get_contents(__DIR__ . '/../database/jeremy_tech_datos.sql')
        );
        $pdo->exec($datos);
    }
}

function resetTestDb(): void
{
    ensureTestDb();
    $pdo = testPdo();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
        $pdo->exec("TRUNCATE TABLE `$table`;");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    // El TRUNCATE vacia la secuencia del SKU; sin la fila el trigger no asigna id.
    $pdo->exec('INSERT INTO productos_id_seq (id) VALUES (0)');
    // Seed tarifas
    $pdo->exec("INSERT INTO tarifas_envio (id, zona, costo, dias_estimados, activo) VALUES
        (1, 'Lima Metropolitana', 15.00, '1-2', 1),
        (2, 'Lima Provincias', 25.00, '2-4', 1),
        (3, 'Provincia Costa', 30.00, '3-5', 1),
        (4, 'Provincia Sierra', 35.00, '4-6', 1),
        (5, 'Provincia Selva', 40.00, '5-7', 1);
    ");
    // Seed minimal users
    $pdo->exec("INSERT INTO users (id,name,apellidos,email,usuario,password,rol,tipo,activo,verificado,telefono,direccion,dni,ruc,empresa,plataforma_reventa,pregunta_secreta,respuesta_secreta_hash,created_at)
        VALUES (1,'Admin','Demo','admin@example.com','admin','" . password_hash('admin', PASSWORD_DEFAULT) . "','admin','B2C',1,1,'999000000','Av. Demo','','','','','¿Cuál es el nombre de tu primera mascota?','" . password_hash('mascota', PASSWORD_DEFAULT) . "',NOW()),
        (2,'Vendedor','Demo','vendedor@example.com','vendedor','" . password_hash('vendedor', PASSWORD_DEFAULT) . "','vendedor','B2B',1,1,'999000001','Av. Demo','','20123456789','Empresa Demo','','¿En qué ciudad naciste?','" . password_hash('ciudad', PASSWORD_DEFAULT) . "',NOW()),
        (3,'Cliente','Demo','cliente@example.com',NULL,'" . password_hash('cliente', PASSWORD_DEFAULT) . "','cliente','B2C',1,1,'999000002','Av. Demo','00000001','','','','¿Cómo se llamaba tu colegio de primaria?','" . password_hash('colegio', PASSWORD_DEFAULT) . "',NOW()),
        (4,'Cliente','B2B','cliente_b2b@example.com',NULL,'" . password_hash('cliente', PASSWORD_DEFAULT) . "','cliente','B2B',1,1,'999000003','Av. Demo','','20123456789','Empresa B2B','','¿Cuál es tu plato favorito?','" . password_hash('plato', PASSWORD_DEFAULT) . "',NOW()),
        (5,'Cliente','C2C','cliente_c2c@example.com',NULL,'" . password_hash('cliente', PASSWORD_DEFAULT) . "','cliente','C2C',1,1,'999000004','Av. Demo','','','','Facebook','¿Cuál es el apellido de soltera de tu madre?','" . password_hash('madre', PASSWORD_DEFAULT) . "',NOW());");
}

function findTestUser(string $email): ?array
{
    $pdo = testPdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}
