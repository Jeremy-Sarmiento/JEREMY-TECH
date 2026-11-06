<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class CarritoMigracionTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        resetTestDb();
        $this->pdo = testPdo();
        $this->migrar('carrito-persistente.sql');
        // Restos de una prueba interrumpida que alteró el índice único.
        $this->pdo->exec('DROP INDEX IF EXISTS jt_tmp_carrito ON carrito_items');
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones) VALUES
            (1, 'carrito-uno', 'Prueba', 'Uno', ''), (2, 'carrito-dos', 'Prueba', 'Dos', '')");
        $this->pdo->exec("INSERT INTO producto_variantes (id, producto_id, nombre_variante) VALUES
            (1, 1, 'Azul'), (2, 1, 'Rojo')");
        $this->pdo->exec('INSERT INTO carritos (id, cliente_id) VALUES (1, 3), (2, 4)');
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        // Las pruebas de DOWN alteran el esquema; no dejarlo degradado para otras suites.
        $this->pdo->exec('DELETE FROM carrito_items');
        $this->migrar('carrito-persistente.sql');
        $this->pdo->exec('DROP INDEX IF EXISTS jt_tmp_carrito ON carrito_items');
    }

    public function testUpgradeIdempotenteConservaFilasLegacy(): void
    {
        $this->migrar('carrito-persistente-down.sql');
        $this->pdo->exec('INSERT INTO carrito_items (id, carrito_id, producto_id, cantidad) VALUES (41, 1, 1, 3)');
        $carritos = $this->pdo->query('SELECT id, cliente_id, actualizado_en FROM carritos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        $this->migrar('carrito-persistente.sql');
        $estructura = $this->estructuraCarrito($this->pdo, 'jeremy_tech_test');
        $this->migrar('carrito-persistente.sql');

        $this->assertSame($estructura, $this->estructuraCarrito($this->pdo, 'jeremy_tech_test'));
        $this->assertSame($carritos, $this->pdo->query('SELECT id, cliente_id, actualizado_en FROM carritos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
        $this->assertSame([
            ['id' => 41, 'carrito_id' => 1, 'producto_id' => 1, 'variante_id' => null, 'cantidad' => 3],
        ], $this->lineas());
    }

    public function testEsquemaNuevoYMigradoSonEquivalentes(): void
    {
        $dump = (string) file_get_contents(__DIR__ . '/../../database/jeremy_tech_full.sql');
        preg_match_all('/CREATE TABLE `(?:carritos|carrito_items)` \(.*?\n\) ENGINE=[^;]+;/s', $dump, $coincidencias);
        $this->assertCount(2, $coincidencias[0]);

        $schema = 'carrito_migracion_' . bin2hex(random_bytes(6));
        $servidor = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $servidor->exec("CREATE DATABASE `$schema` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        try {
            $servidor->exec("USE `$schema`");
            $servidor->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($coincidencias[0] as $create) {
                $servidor->exec($create);
            }
            $servidor->exec('SET FOREIGN_KEY_CHECKS=1');
            $nuevo = $this->estructuraCarrito($servidor, $schema);
        } finally {
            $servidor->exec("DROP DATABASE `$schema`");
        }

        $this->migrar('carrito-persistente-down.sql');
        $this->migrar('carrito-persistente.sql');
        $this->assertSame($nuevo, $this->estructuraCarrito($this->pdo, 'jeremy_tech_test'));
    }

    public function testBaseYVariantesDistintasCoexistenYElUpsertResuelveCadaLinea(): void
    {
        $insertar = $this->pdo->prepare('INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad)
            VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)');
        foreach ([[1, null, 2], [1, 1, 3], [1, 2, 4], [2, null, 5], [1, null, 1], [1, 1, 2]] as $linea) {
            $insertar->execute($linea);
        }

        $this->assertSame([
            ['carrito_id' => 1, 'variante_id' => null, 'cantidad' => 3],
            ['carrito_id' => 1, 'variante_id' => 1, 'cantidad' => 5],
            ['carrito_id' => 1, 'variante_id' => 2, 'cantidad' => 4],
            ['carrito_id' => 2, 'variante_id' => null, 'cantidad' => 5],
        ], $this->pdo->query('SELECT carrito_id, variante_id, cantidad FROM carrito_items
            ORDER BY carrito_id, variante_id')->fetchAll(PDO::FETCH_ASSOC));
        $antes = $this->lineas();
        $this->migrar('carrito-persistente.sql');
        $this->assertSame($antes, $this->lineas());
        $this->rechaza('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)', 1062);
        $this->rechaza('INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 1, 1)', 1062);
    }

    public function testBorrarUnaVarianteEliminaSoloSuLineaSinConvertirlaEnBase(): void
    {
        $this->pdo->exec('INSERT INTO carrito_items (id, carrito_id, producto_id, variante_id, cantidad) VALUES
            (1, 1, 1, NULL, 2), (2, 1, 1, 1, 3), (3, 1, 1, 2, 4), (4, 1, 2, NULL, 5)');
        $antes = $this->lineas();

        $this->pdo->exec('DELETE FROM producto_variantes WHERE id = 1');

        $this->assertSame([$antes[0], $antes[2], $antes[3]], $this->lineas());
        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn());
        $this->assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM carritos')->fetchColumn());
    }

    public function testConservaReferenciasYChecksYRechazaElSentinelComoVariante(): void
    {
        foreach ([
            'INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 0)',
            'INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 0, 1)',
            'INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, -1, 1)',
        ] as $sql) {
            $this->rechaza($sql, 4025);
        }
        foreach ([
            'INSERT INTO carritos (cliente_id) VALUES (999999)',
            'INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (999999, 1, 1)',
            'INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 999999, 1)',
            'INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 999999, 1)',
        ] as $sql) {
            $this->rechaza($sql, 1452);
        }
        $this->rechaza('INSERT INTO carritos (cliente_id) VALUES (3)', 1062);
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)');
        foreach (['DELETE FROM carritos WHERE id = 1', 'DELETE FROM productos WHERE id = 1', 'DELETE FROM users WHERE id = 3'] as $sql) {
            $this->rechaza($sql, 1451);
        }
        $indices = $this->pdo->query("SELECT index_name, column_name FROM information_schema.statistics
            WHERE table_schema = DATABASE() AND table_name = 'carrito_items'
            AND index_name = 'idx_carrito_items_variante'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->assertSame('variante_id', $indices['idx_carrito_items_variante']);
    }

    public function testDownVacioEsIdempotenteYSePuedeVolverASubir(): void
    {
        $antes = $this->estructuraCarrito($this->pdo, 'jeremy_tech_test');
        $this->migrar('carrito-persistente-down.sql');
        $legacy = $this->estructuraCarrito($this->pdo, 'jeremy_tech_test');
        $this->migrar('carrito-persistente-down.sql');

        $this->assertSame($legacy, $this->estructuraCarrito($this->pdo, 'jeremy_tech_test'));
        $this->assertNotContains('variante_id', array_column($legacy['columnas'], 'column_name'));
        $this->assertContains('carrito_item_unico', array_column($legacy['restricciones'], 'constraint_name'));
        $this->assertNotContains('uq_carrito_items_linea', array_column($legacy['indices'], 'index_name'));
        $this->migrar('carrito-persistente.sql');
        $this->assertSame($antes, $this->estructuraCarrito($this->pdo, 'jeremy_tech_test'));
    }

    public function testDownConservaDatosYRestituyeLaUnicidadOriginal(): void
    {
        $this->pdo->exec('INSERT INTO carrito_items (id, carrito_id, producto_id, cantidad) VALUES (41, 1, 1, 3)');
        $this->migrar('carrito-persistente-down.sql');
        $this->migrar('carrito-persistente-down.sql');

        $this->assertSame([
            'id' => 41, 'carrito_id' => 1, 'producto_id' => 1, 'cantidad' => 3,
        ], $this->pdo->query('SELECT id, carrito_id, producto_id, cantidad FROM carrito_items')->fetch(PDO::FETCH_ASSOC));
        $this->rechaza('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)', 1062);
        $this->rechaza('UPDATE carrito_items SET cantidad = 0', 4025);
    }

    public function testDownRehusaPerderIdentidadInclusoConUnaSolaVariante(): void
    {
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 1, 3)');
        $antes = $this->lineas();
        $estructura = $this->estructuraCarrito($this->pdo, 'jeremy_tech_test');

        $this->rechaza(file_get_contents(__DIR__ . '/../../database/carrito-persistente-down.sql'), 4025);

        $this->assertSame($antes, $this->lineas());
        $this->assertSame($estructura, $this->estructuraCarrito($this->pdo, 'jeremy_tech_test'));
    }

    public function testDownRehusaDuplicadosSinBorrarNiFusionarLasFilas(): void
    {
        // Simula una base alterada externamente: DOWN debe comprobar los datos, no confiar en el índice.
        $this->pdo->exec('DROP INDEX IF EXISTS jt_tmp_carrito ON carrito_items');
        $this->pdo->exec('CREATE INDEX jt_tmp_carrito ON carrito_items (carrito_id)');
        $this->pdo->exec('DROP INDEX uq_carrito_items_linea ON carrito_items');
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 2), (1, 1, 3)');
        $antes = $this->lineas();
        $estructura = $this->estructuraCarrito($this->pdo, 'jeremy_tech_test');

        $this->rechaza(file_get_contents(__DIR__ . '/../../database/carrito-persistente-down.sql'), 1062);

        $this->assertSame($antes, $this->lineas());
        $this->assertSame($estructura, $this->estructuraCarrito($this->pdo, 'jeremy_tech_test'));
    }

    private function migrar(string $archivo): void
    {
        $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/' . $archivo));
    }

    private function rechaza(string $sql, int $codigo): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->assertSame($codigo, (int) ($e->errorInfo[1] ?? 0), $e->getMessage());
            return;
        }
        $this->fail('La base aceptó una operación inválida: ' . $sql);
    }

    private function lineas(): array
    {
        return $this->pdo->query('SELECT id, carrito_id, producto_id, variante_id, cantidad FROM carrito_items ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function estructuraCarrito(PDO $pdo, string $schema): array
    {
        $columnas = $pdo->prepare("SELECT table_name, column_name, column_type, is_nullable, column_default,
            extra, generation_expression FROM information_schema.columns
            WHERE table_schema = ? AND table_name IN ('carritos', 'carrito_items') ORDER BY table_name, ordinal_position");
        $columnas->execute([$schema]);
        $restricciones = $pdo->prepare("SELECT table_name, constraint_name, constraint_type
            FROM information_schema.table_constraints
            WHERE constraint_schema = ? AND table_name IN ('carritos', 'carrito_items')
            ORDER BY table_name, constraint_name");
        $restricciones->execute([$schema]);
        $indices = $pdo->prepare("SELECT table_name, index_name, non_unique, seq_in_index, column_name
            FROM information_schema.statistics
            WHERE table_schema = ? AND table_name IN ('carritos', 'carrito_items')
            ORDER BY table_name, index_name, seq_in_index");
        $indices->execute([$schema]);

        return [
            'columnas' => $columnas->fetchAll(PDO::FETCH_ASSOC),
            'restricciones' => $restricciones->fetchAll(PDO::FETCH_ASSOC),
            'indices' => $indices->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
