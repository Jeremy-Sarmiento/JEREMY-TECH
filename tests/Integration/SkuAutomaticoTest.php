<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SkuAutomaticoTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->assertSame('jeremy_tech_test', DB_NAME);
        $this->pdo = testPdo();
        $this->assertSame('jeremy_tech_test', $this->pdo->query('SELECT current_database()')->fetchColumn());
        resetTestDb();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testOmitirSkuGeneraDosCodigosUnicosConLaIdentidadDeCadaProducto(): void
    {
        $filas = $this->pdo->query("INSERT INTO productos (slug, categoria, nombre, especificaciones) VALUES
            ('sku-uno', 'Celulares', 'Uno', ''), ('sku-dos', 'Celulares', 'Dos', '')
            RETURNING id, codigo_sku")->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame([
            ['id' => 1, 'codigo_sku' => 'JT-00001'],
            ['id' => 2, 'codigo_sku' => 'JT-00002'],
        ], $filas);
    }

    #[DataProvider('skuAusente')]
    public function testNullVacioOEspaciosGeneranSkuAlInsertar(?string $sku): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO productos (slug, categoria, nombre, especificaciones, codigo_sku)
            VALUES ('sku-vacio', 'Prueba', 'Vacio', '', ?) RETURNING codigo_sku");
        $stmt->execute([$sku]);
        $this->assertSame('JT-00001', $stmt->fetchColumn());
    }

    #[DataProvider('skuAusente')]
    public function testNullVacioOEspaciosRegeneranSkuAlActualizar(?string $sku): void
    {
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, codigo_sku)
            VALUES (15, 'sku-update', 'Prueba', 'Original', '', 'MANUAL-15')");
        $stmt = $this->pdo->prepare('UPDATE productos SET codigo_sku = ? WHERE id = 15 RETURNING codigo_sku');
        $stmt->execute([$sku]);
        $this->assertSame('JT-00015', $stmt->fetchColumn());
    }

    public static function skuAusente(): array
    {
        return ['null' => [null], 'vacio' => [''], 'espacios' => ['   ']];
    }

    #[DataProvider('idsGrandes')]
    public function testNoTruncaNiConvierteABigintDe32BitsLosIdsGrandes(string $id): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO productos (id, slug, categoria, nombre, especificaciones)
            VALUES (?, 'sku-bigint', 'Prueba', 'Bigint', '') RETURNING id, codigo_sku");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($id, (string) $fila['id']);
        $this->assertSame('JT-' . $id, $fila['codigo_sku']);
    }

    public static function idsGrandes(): array
    {
        return [
            'seis digitos' => ['100000'],
            'supera integer' => ['4294967296'],
            'maximo bigint' => ['9223372036854775807'],
        ];
    }

    public function testConservaSkuExplicitoImportadoYManualInclusoAlEditarOtrosDatos(): void
    {
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, codigo_sku) VALUES
            (1, 'sku-manual', 'Celulares', 'Manual', '', '  MANUAL-001  '),
            (2, 'sku-importado', 'Celulares', 'Importado', '', 'JT-98765')");
        $this->pdo->exec("UPDATE productos SET nombre = 'Editado', stock = 9 WHERE id IN (1, 2)");
        $this->assertSame([
            ['id' => 1, 'codigo_sku' => '  MANUAL-001  ', 'nombre' => 'Editado', 'stock' => 9],
            ['id' => 2, 'codigo_sku' => 'JT-98765', 'nombre' => 'Editado', 'stock' => 9],
        ], $this->pdo->query('SELECT id, codigo_sku, nombre, stock FROM productos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));

        $this->pdo->exec("UPDATE productos SET codigo_sku = 'IMPORTADO-ORIGINAL' WHERE id = 2");
        $this->assertSame('IMPORTADO-ORIGINAL', $this->pdo->query('SELECT codigo_sku FROM productos WHERE id = 2')->fetchColumn());
    }

    public function testColisionConSkuManualFallaSinRenumerarNiReintentar(): void
    {
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, codigo_sku)
            VALUES (8, 'sku-reservado', 'Prueba', 'Historico', '', 'JT-00009')");
        $antes = $this->productos();
        try {
            $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones)
                VALUES (9, 'sku-colision', 'Prueba', 'Nuevo', '')");
            $this->fail('Se aceptó o renumeró silenciosamente un SKU generado que ya existía.');
        } catch (PDOException $e) {
            $this->assertSame('23505', $e->getCode());
        }
        $this->assertSame($antes, $this->productos());
    }

    public function testElEsquemaNuevoGeneraSkuSinNecesitarLaMigracion(): void
    {
        $this->enEsquema(false, function (): void {
            $sku = $this->pdo->query("INSERT INTO productos (slug, categoria, nombre, especificaciones)
                VALUES ('nuevo', 'Celulares', 'Nuevo', '') RETURNING codigo_sku")->fetchColumn();
            $this->assertSame('JT-00001', $sku);
        });
    }

    #[DataProvider('estadosTimestamp')]
    public function testBackfillSoloCompletaAusentesYReejecutarConservaDatosEIdentidades(string $modo, string $estado): void
    {
        $this->enEsquema(true, function () use ($modo, $estado): void {
            $this->sembrarLegacy();
            $this->pdo->exec("ALTER TABLE productos $modo TRIGGER trg_productos_actualizado_en");
            $antes = $this->productosSinSku();
            $identidades = $this->identidades();
            $secuencia = $this->secuencia();
            $timeout = $this->pdo->query('SHOW lock_timeout')->fetchColumn();

            $this->migrar();
            $this->assertSame([
                ['id' => 1, 'codigo_sku' => 'JT-00001'],
                ['id' => 2, 'codigo_sku' => 'JT-00002'],
                ['id' => 3, 'codigo_sku' => 'JT-00003'],
                ['id' => 4, 'codigo_sku' => '  LEGACY-MANUAL  '],
                ['id' => 5, 'codigo_sku' => 'JT-01000'],
                ['id' => 100000, 'codigo_sku' => 'JT-100000'],
            ], $this->pdo->query('SELECT id, codigo_sku FROM productos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
            // El touch_actualizado_en existente no debe convertir el backfill en una edición del producto.
            $this->assertSame($antes, $this->productosSinSku());
            $datosMigrados = $this->productos();
            $disparadores = $this->disparadores();
            $this->assertSame($estado, array_column($disparadores, 'tgenabled', 'tgname')['trg_productos_actualizado_en']);

            $this->migrar();

            $this->assertSame($datosMigrados, $this->productos());
            $this->assertSame($disparadores, $this->disparadores());
            $this->assertSame($identidades, $this->identidades());
            $this->assertSame($secuencia, $this->secuencia());
            $this->assertSame($timeout, $this->pdo->query('SHOW lock_timeout')->fetchColumn());
            $this->assertFalse($this->pdo->inTransaction());
            $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones)
                VALUES (6, 'tras-migrar', 'Celulares', 'Tras migrar', '')");
            $this->assertSame('JT-00006', $this->pdo->query('SELECT codigo_sku FROM productos WHERE id = 6')->fetchColumn());
        });
    }

    #[DataProvider('estadosTimestamp')]
    public function testBackfillConColisionLegacyAbortaTodoSinAlterarFilasNiDisparadores(string $modo, string $estado): void
    {
        $this->enEsquema(true, function () use ($modo, $estado): void {
            $this->sembrarLegacy();
            $this->pdo->exec("ALTER TABLE productos $modo TRIGGER trg_productos_actualizado_en");
            $this->pdo->exec("UPDATE productos SET codigo_sku = 'JT-00001' WHERE id = 4");
            $antes = $this->productos();
            $identidades = $this->identidades();
            $secuencia = $this->secuencia();
            $disparadores = $this->disparadores();
            $timeout = $this->pdo->query('SHOW lock_timeout')->fetchColumn();
            try {
                $this->migrar();
                $this->fail('La migración ocultó una colisión con el SKU histórico.');
            } catch (PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $this->assertSame('23505', $e->getCode());
            }
            $this->assertSame($antes, $this->productos());
            $this->assertSame($identidades, $this->identidades());
            $this->assertSame($secuencia, $this->secuencia());
            $this->assertSame($disparadores, $this->disparadores());
            $this->assertSame($estado, array_column($this->disparadores(), 'tgenabled', 'tgname')['trg_productos_actualizado_en']);
            $this->assertSame($timeout, $this->pdo->query('SHOW lock_timeout')->fetchColumn());
            $this->assertNull($this->pdo->query("SELECT to_regprocedure('asignar_codigo_sku()')")->fetchColumn());
        });
    }

    public static function estadosTimestamp(): array
    {
        return [
            'normal' => ['ENABLE', 'O'],
            'siempre' => ['ENABLE ALWAYS', 'A'],
            'replica' => ['ENABLE REPLICA', 'R'],
            'desactivado' => ['DISABLE', 'D'],
        ];
    }

    private function enEsquema(bool $legacy, callable $prueba): void
    {
        $schema = 'sku_test_' . bin2hex(random_bytes(6));
        $this->pdo->exec('CREATE SCHEMA ' . $schema);
        try {
            // Sin public en search_path, ni la estructura destructiva ni la migración alcanzan otras tablas.
            $this->pdo->exec('SET search_path TO ' . $schema);
            $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/jeremy_tech_estructura.sql'));
            if ($legacy) {
                $this->pdo->exec('DROP TRIGGER IF EXISTS trg_productos_codigo_sku ON productos');
                $this->pdo->exec('DROP FUNCTION IF EXISTS asignar_codigo_sku()');
            }
            $this->pdo->exec("INSERT INTO categorias (nombre) VALUES ('Celulares')");
            $prueba();
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->pdo->exec('SET search_path TO public');
            $this->pdo->exec('DROP SCHEMA ' . $schema . ' CASCADE');
        }
    }

    private function sembrarLegacy(): void
    {
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, codigo_sku,
            stock, precio_pen, activo, archivado_en, actualizado_en) VALUES
            (1, 'legacy-null', 'Celulares', 'Null original', 'Ficha uno', NULL, 7, 11.11, 1, NULL, '2001-01-01'),
            (2, 'legacy-vacio', 'Celulares', 'Vacio original', 'Ficha dos', '', 8, 22.22, 1, NULL, '2002-01-01'),
            (3, 'legacy-espacios', 'Celulares', 'Archivado', 'Ficha tres', '   ', 9, 33.33, 0, '2020-01-01', NULL),
            (4, 'legacy-manual', 'Celulares', 'Manual original', 'Ficha cuatro', '  LEGACY-MANUAL  ', 10, 44.44, 1, NULL, NULL),
            (5, 'legacy-jt', 'Celulares', 'JT importado', 'Ficha cinco', 'JT-01000', 11, 55.55, 1, NULL, '2005-01-01'),
            (100000, 'legacy-grande', 'Celulares', 'Id grande', 'Ficha seis', NULL, 12, 66.66, 1, NULL, NULL)");
    }

    private function migrar(): void
    {
        $archivo = __DIR__ . '/../../database/sku-automatico.sql';
        if (!is_file($archivo)) {
            $this->markTestSkipped('Falta database/sku-automatico.sql (migración no versionada).');
        }
        $this->pdo->exec(file_get_contents($archivo));
    }

    private function productos(): array
    {
        return $this->pdo->query('SELECT row_to_json(p)::text FROM productos p ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    }

    private function productosSinSku(): array
    {
        return $this->pdo->query("SELECT (to_jsonb(p) - 'codigo_sku')::text FROM productos p ORDER BY id")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    private function identidades(): array
    {
        return $this->pdo->query("SELECT oid, relname, relkind FROM pg_class
            WHERE relnamespace = current_schema()::regnamespace AND relkind IN ('r', 'S') ORDER BY relname")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function secuencia(): array
    {
        return $this->pdo->query('SELECT last_value, is_called FROM productos_id_seq')->fetch(PDO::FETCH_ASSOC);
    }

    private function disparadores(): array
    {
        return $this->pdo->query("SELECT tgname, tgenabled, pg_get_triggerdef(oid) AS definicion FROM pg_trigger
            WHERE tgrelid = 'productos'::regclass AND NOT tgisinternal ORDER BY tgname")->fetchAll(PDO::FETCH_ASSOC);
    }
}
