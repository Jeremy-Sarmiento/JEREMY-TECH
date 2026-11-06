<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Anula DATABASE_URL del .env: las pruebas deben usar solo la base local,
// nunca la base alojada configurada para producción.
// No usar una cadena vacía: proc_open puede omitirla y cargar otra vez el .env remoto.
putenv('DATABASE_URL=postgresql://postgres:admin@127.0.0.1:5432/jeremy_tech_test?sslmode=disable');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=5432');
putenv('DB_NAME=jeremy_tech_test');
putenv('DB_USER=postgres');
putenv('DB_PASS=admin');
putenv('APP_TIMEZONE=America/Lima');
putenv('APP_URL=http://localhost');
putenv('MAIL_STORAGE_PATH=/tmp/opencode/mail');
putenv('CHAT_MODE=rules');
putenv('PAGOS_MODO=simulado');

require_once __DIR__ . '/../config/conexion.php';

function testPdo(): \PDO
{
    return new \PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
}

function ensureTestDb(): void
{
    $server = new \PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=postgres',
        DB_USER,
        DB_PASS,
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
    );
    $existe = $server->query("SELECT 1 FROM pg_database WHERE datname = 'jeremy_tech_test'")->fetchColumn();
    if ($existe === false) {
        $server->exec('CREATE DATABASE jeremy_tech_test');
    }
    $pdo = testPdo();
    $tablas = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'"
    )->fetchColumn();
    if ($tablas < 30) {
        // Los tests truncan y siembran sus propias filas: basta la estructura.
        $pdo->exec(file_get_contents(__DIR__ . '/../database/jeremy_tech_estructura.sql'));
    }
    // Migraciones idempotentes que una base de pruebas ya creada no tiene.
    $pdo->exec(file_get_contents(__DIR__ . '/../database/imagenes-producto.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/../database/carrito-persistente.sql'));
}

function resetTestDb(): void
{
    ensureTestDb();
    $pdo = testPdo();
    $tablas = $pdo->query(
        "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
    )->fetchAll(\PDO::FETCH_COLUMN);
    $pdo->exec('TRUNCATE TABLE ' . implode(', ', $tablas) . ' RESTART IDENTITY CASCADE');
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
    // Los ids explícitos no avanzan la secuencia identity: hay que sincronizarla.
    foreach (['users', 'tarifas_envio'] as $tabla) {
        $pdo->exec(sprintf(
            "SELECT setval(pg_get_serial_sequence('%s', 'id'), (SELECT MAX(id) FROM %s))",
            $tabla,
            $tabla
        ));
    }
}

function findTestUser(string $email): ?array
{
    $pdo = testPdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}
