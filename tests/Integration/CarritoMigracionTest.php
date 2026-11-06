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
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones) VALUES
            (1, 'carrito-uno', 'Prueba', 'Uno', ''), (2, 'carrito-dos', 'Prueba', 'Dos', '')");
        $this->pdo->exec("INSERT INTO producto_variantes (id, producto_id, nombre_variante) VALUES
            (1, 1, 'Azul'), (2, 1, 'Rojo')");
        $this->pdo->exec('INSERT INTO carritos (id, cliente_id) VALUES (1, 3), (2, 4)');
        $this->pdo->exec("SELECT setval(pg_get_serial_sequence('carritos', 'id'), 2)");
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        // Las pruebas de DOWN alteran el esquema; no dejarlo degradado para otras suites.
        $this->pdo->exec('DELETE FROM carrito_items');
        $this->migrar('carrito-persistente.sql');
    }

    public function testUpgradeIdempotenteConservaFilasLegacyYLasIdentidadesDeLasTablas(): void
    {
        $this->migrar('carrito-persistente-down.sql');
        $this->pdo->exec('INSERT INTO carrito_items (id, carrito_id, producto_id, cantidad) VALUES (41, 1, 1, 3)');
        $carritos = $this->pdo->query('SELECT id, cliente_id, actualizado_en FROM carritos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $identidades = $this->identidades();

        $this->migrar('carrito-persistente.sql');
        $estructura = $this->estructuraCarrito('public');
        $this->migrar('carrito-persistente.sql');

        $this->assertSame($estructura, $this->estructuraCarrito('public'));
        $this->assertSame($identidades, $this->identidades());
        $this->assertSame($carritos, $this->pdo->query('SELECT id, cliente_id, actualizado_en FROM carritos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
        $this->assertSame([
            ['id' => 41, 'carrito_id' => 1, 'producto_id' => 1, 'variante_id' => null, 'cantidad' => 3],
        ], $this->lineas());
    }

    public function testEsquemaNuevoYMigradoSonEquivalentes(): void
    {
        // Normalizar solo la fixture: integridad-rendimiento puede haber dejado este CHECK pendiente.
        $this->pdo->exec('ALTER TABLE carrito_items VALIDATE CONSTRAINT ck_carrito_items_cantidad');
        $schema = 'carrito_migracion_' . bin2hex(random_bytes(6));
        $this->pdo->exec("CREATE SCHEMA $schema");
        try {
            // Sin public en search_path, los DROP del esquema nuevo nunca alcanzan tablas compartidas.
            $this->pdo->exec("SET search_path TO $schema");
            $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/jeremy_tech_estructura.sql'));
            $nuevo = $this->estructuraCarrito($schema);
        } finally {
            $this->pdo->exec('SET search_path TO public');
            $this->pdo->exec("DROP SCHEMA $schema CASCADE");
        }

        $this->migrar('carrito-persistente-down.sql');
        $this->migrar('carrito-persistente.sql');
        $this->assertSame($nuevo, $this->estructuraCarrito('public'));
    }

    public function testBaseYVariantesDistintasCoexistenYElUpsertResuelveCadaLinea(): void
    {
        $insertar = $this->pdo->prepare('INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad)
            VALUES (?, 1, ?, ?) ON CONFLICT (carrito_id, producto_id, (COALESCE(variante_id, 0)))
            DO UPDATE SET cantidad = carrito_items.cantidad + EXCLUDED.cantidad');
        foreach ([[1, null, 2], [1, 1, 3], [1, 2, 4], [2, null, 5], [1, null, 1], [1, 1, 2]] as $linea) {
            $insertar->execute($linea);
        }

        $this->assertSame([
            ['carrito_id' => 1, 'variante_id' => null, 'cantidad' => 3],
            ['carrito_id' => 1, 'variante_id' => 1, 'cantidad' => 5],
            ['carrito_id' => 1, 'variante_id' => 2, 'cantidad' => 4],
            ['carrito_id' => 2, 'variante_id' => null, 'cantidad' => 5],
        ], $this->pdo->query('SELECT carrito_id, variante_id, cantidad FROM carrito_items
            ORDER BY carrito_id, variante_id NULLS FIRST')->fetchAll(PDO::FETCH_ASSOC));
        $antes = $this->lineas();
        $this->migrar('carrito-persistente.sql');
        $this->assertSame($antes, $this->lineas());
        $this->rechaza('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)', '23505');
        $this->rechaza('INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 1, 1)', '23505');
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
            $this->rechaza($sql, '23514');
        }
        foreach ([
            'INSERT INTO carritos (cliente_id) VALUES (999999)',
            'INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (999999, 1, 1)',
            'INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 999999, 1)',
            'INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 999999, 1)',
        ] as $sql) {
            $this->rechaza($sql, '23503');
        }
        $this->rechaza('INSERT INTO carritos (cliente_id) VALUES (3)', '23505');
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)');
        foreach (['DELETE FROM carritos WHERE id = 1', 'DELETE FROM productos WHERE id = 1', 'DELETE FROM users WHERE id = 3'] as $sql) {
            $this->rechaza($sql, '23503');
        }
        $indices = $this->pdo->query("SELECT indexname, indexdef FROM pg_indexes
            WHERE schemaname = 'public' AND tablename = 'carrito_items'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->assertStringContainsString('(variante_id)', $indices['idx_carrito_items_variante']);
    }

    public function testDownVacioEsIdempotenteYSePuedeVolverASubir(): void
    {
        $antes = $this->estructuraCarrito('public');
        $this->migrar('carrito-persistente-down.sql');
        $legacy = $this->estructuraCarrito('public');
        $this->migrar('carrito-persistente-down.sql');

        $this->assertSame($legacy, $this->estructuraCarrito('public'));
        $this->assertNotContains('variante_id', array_column($legacy['columnas'], 'column_name'));
        $this->assertContains('uq_carrito_items_carrito_item_unico', array_column($legacy['restricciones'], 'conname'));
        $this->assertNotContains('uq_carrito_items_linea', array_column($legacy['indices'], 'indexname'));
        $this->migrar('carrito-persistente.sql');
        $this->assertSame($antes, $this->estructuraCarrito('public'));
    }

    public function testDownConLineasBaseConservaDatosYRestituyeLaUnicidadOriginal(): void
    {
        $this->pdo->exec('INSERT INTO carrito_items (id, carrito_id, producto_id, cantidad) VALUES (41, 1, 1, 3)');
        $identidades = $this->identidades();
        $this->migrar('carrito-persistente-down.sql');
        $this->migrar('carrito-persistente-down.sql');

        $this->assertSame($identidades, $this->identidades());
        $this->assertSame([
            'id' => 41, 'carrito_id' => 1, 'producto_id' => 1, 'cantidad' => 3,
        ], $this->pdo->query('SELECT id, carrito_id, producto_id, cantidad FROM carrito_items')->fetch(PDO::FETCH_ASSOC));
        $this->rechaza('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 1)', '23505');
        $this->rechaza('UPDATE carrito_items SET cantidad = 0', '23514');
    }

    public function testDownRehusaPerderIdentidadInclusoConUnaSolaVariante(): void
    {
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, variante_id, cantidad) VALUES (1, 1, 1, 3)');
        $antes = $this->lineas();
        $estructura = $this->estructuraCarrito('public');

        $this->rechaza(file_get_contents(__DIR__ . '/../../database/carrito-persistente-down.sql'), '23514');

        $this->assertSame($antes, $this->lineas());
        $this->assertSame($estructura, $this->estructuraCarrito('public'));
    }

    public function testDownRehusaDuplicadosSinBorrarNiFusionarLasFilas(): void
    {
        // Simula una base alterada externamente: DOWN debe comprobar los datos, no confiar en el índice.
        $this->pdo->exec('DROP INDEX uq_carrito_items_linea');
        $this->pdo->exec('INSERT INTO carrito_items (carrito_id, producto_id, cantidad) VALUES (1, 1, 2), (1, 1, 3)');
        $antes = $this->lineas();
        $estructura = $this->estructuraCarrito('public');

        $this->rechaza(file_get_contents(__DIR__ . '/../../database/carrito-persistente-down.sql'), '23514');

        $this->assertSame($antes, $this->lineas());
        $this->assertSame($estructura, $this->estructuraCarrito('public'));
    }

    private function migrar(string $archivo): void
    {
        $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/' . $archivo));
    }

    private function rechaza(string $sql, string $sqlState): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->assertSame($sqlState, $e->getCode(), $e->getMessage());
            return;
        }
        $this->fail('La base aceptó una operación inválida: ' . $sql);
    }

    private function lineas(): array
    {
        return $this->pdo->query('SELECT id, carrito_id, producto_id, variante_id, cantidad FROM carrito_items ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function identidades(): array
    {
        return $this->pdo->query("SELECT oid, relname FROM pg_class
            WHERE oid IN ('public.carritos'::regclass, 'public.carrito_items'::regclass,
                pg_get_serial_sequence('public.carritos', 'id')::regclass,
                pg_get_serial_sequence('public.carrito_items', 'id')::regclass) ORDER BY relname")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function estructuraCarrito(string $schema): array
    {
        $columnas = $this->pdo->prepare("SELECT table_name, column_name, data_type, is_nullable, column_default,
            is_identity, identity_generation FROM information_schema.columns
            WHERE table_schema = ? AND table_name IN ('carritos', 'carrito_items') ORDER BY table_name, ordinal_position");
        $columnas->execute([$schema]);
        $restricciones = $this->pdo->prepare("SELECT t.relname, c.conname, c.contype, c.convalidated,
            pg_get_constraintdef(c.oid) AS definicion FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
            JOIN pg_namespace n ON n.oid = t.relnamespace WHERE n.nspname = ?
            AND t.relname IN ('carritos', 'carrito_items') ORDER BY t.relname, c.conname");
        $restricciones->execute([$schema]);
        $indices = $this->pdo->prepare("SELECT tablename, indexname, replace(indexdef, ? || '.', '') AS definicion
            FROM pg_indexes WHERE schemaname = ? AND tablename IN ('carritos', 'carrito_items') ORDER BY tablename, indexname");
        $indices->execute([$schema, $schema]);

        return [
            'columnas' => $columnas->fetchAll(PDO::FETCH_ASSOC),
            'restricciones' => $restricciones->fetchAll(PDO::FETCH_ASSOC),
            'indices' => $indices->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
