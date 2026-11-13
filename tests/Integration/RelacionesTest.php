<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\SolicitudVendedor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RelacionesTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->assertSame('jeremy_tech_test', DB_NAME);
        $this->pdo = testPdo();
        $this->assertSame('jeremy_tech_test', $this->pdo->query('SELECT current_database()')->fetchColumn());
        resetTestDb();
        $this->pdo->exec("INSERT INTO marcas (id, nombre) VALUES (10, 'Marca de prueba')");
        $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, marca_id, precio_pen, stock)
            VALUES (1, 'relaciones', 'Celulares', 'Producto conservado', 'Ficha original', 10, 123.45, 7)");
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testRechazaReferenciasHuerfanasInclusoEnTokensAdmin(): void
    {
        foreach ([
            'UPDATE productos SET marca_id = 999999 WHERE id = 1',
            "UPDATE productos SET categoria = 'Categoria inexistente' WHERE id = 1",
            'INSERT INTO solicitudes_vendedor (usuario_id) VALUES (999999)',
            "INSERT INTO tokens_recuperacion (tipo, usuario_id, token, expira_en)
                VALUES ('cliente', 999999, 'huerfano-cliente', NOW() + INTERVAL '1 hour')",
            "INSERT INTO tokens_recuperacion (tipo, usuario_id, token, expira_en)
                VALUES ('admin', 999999, 'huerfano-admin', NOW() + INTERVAL '1 hour')",
        ] as $sql) {
            $this->rechaza($sql, '23503');
        }
    }

    public function testNoBorraMarcaNiCategoriaUsadasNiReasignaElProducto(): void
    {
        $antes = $this->datos();
        $this->rechaza('DELETE FROM marcas WHERE id = 10', '23503');
        $this->rechaza("DELETE FROM categorias WHERE nombre = 'Celulares'", '23503');
        $this->rechaza('UPDATE marcas SET id = 11 WHERE id = 10', '23503');
        $this->assertSame($antes, $this->datos());
    }

    public function testMarcaEsOpcionalYSoloSePuedeBorrarAlDesvincularla(): void
    {
        $this->pdo->exec('UPDATE productos SET marca_id = NULL WHERE id = 1');
        $this->pdo->exec('DELETE FROM marcas WHERE id = 10');
        $this->assertSame(
            ['marca_id' => null, 'precio_pen' => '123.45', 'stock' => 7],
            $this->pdo->query('SELECT marca_id, precio_pen, stock FROM productos WHERE id = 1')->fetch(PDO::FETCH_ASSOC)
        );
    }

    public function testRenombrarCategoriaPropagaElNombreSinPerderElProducto(): void
    {
        $this->pdo->exec("UPDATE categorias SET nombre = 'Telefonos' WHERE nombre = 'Celulares'");
        $this->assertSame([
            'id' => 1, 'categoria' => 'Telefonos', 'marca_id' => 10, 'nombre' => 'Producto conservado',
            'especificaciones' => 'Ficha original', 'precio_pen' => '123.45', 'stock' => 7,
        ], $this->pdo->query('SELECT id, categoria, marca_id, nombre, especificaciones, precio_pen, stock
            FROM productos WHERE id = 1')->fetch(PDO::FETCH_ASSOC));
        $this->rechaza("DELETE FROM categorias WHERE nombre = 'Telefonos'", '23503');
    }

    public function testCategoriaAdmite80CaracteresYRechaza81SinTruncar(): void
    {
        $nombre = str_repeat('ñ', 80);
        $stmt = $this->pdo->prepare("UPDATE categorias SET nombre = ? WHERE nombre = 'Celulares'");
        $stmt->execute([$nombre]);
        $this->assertSame($nombre, $this->pdo->query('SELECT categoria FROM productos WHERE id = 1')->fetchColumn());
        $this->rechaza('UPDATE productos SET categoria = ' . $this->pdo->quote($nombre . 'x') . ' WHERE id = 1', '22001');
        $this->rechaza('UPDATE categorias SET nombre = ' . $this->pdo->quote($nombre . 'x')
            . ' WHERE nombre = ' . $this->pdo->quote($nombre), '22001');
        $this->assertSame($nombre, $this->pdo->query('SELECT categoria FROM productos WHERE id = 1')->fetchColumn());
    }

    public function testCategoriaEsInmediataPeroPermiteImportarHijoAntesDelPadreAlDiferir(): void
    {
        $this->rechaza("UPDATE productos SET categoria = 'Importada' WHERE id = 1", '23503');
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('SET CONSTRAINTS fk_productos_categoria DEFERRED');
            $this->pdo->exec("UPDATE productos SET categoria = 'Importada' WHERE id = 1");
            $this->pdo->exec("INSERT INTO categorias (nombre) VALUES ('Importada')");
            $this->pdo->exec('SET CONSTRAINTS fk_productos_categoria IMMEDIATE');
            $this->pdo->commit();
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
        $this->assertSame('Importada', $this->pdo->query('SELECT categoria FROM productos WHERE id = 1')->fetchColumn());
    }

    public function testSolicitudYTokensProtegenUsuariosSinImpedirSuBajaLogica(): void
    {
        $this->pdo->exec("INSERT INTO solicitudes_vendedor (usuario_id, mensaje) VALUES (3, 'Conservar solicitud')");
        $this->pdo->exec("INSERT INTO tokens_recuperacion (tipo, usuario_id, token, expira_en) VALUES
            ('cliente', 4, 'conservar-cliente', NOW() + INTERVAL '1 hour'),
            ('admin', 4, 'conservar-admin', NOW() + INTERVAL '1 hour')");
        foreach ([3, 4] as $id) {
            $this->rechaza('DELETE FROM usuarios WHERE id = ' . $id, '23503');
            $this->rechaza('UPDATE usuarios SET id = 999999 WHERE id = ' . $id, '23503');
        }
        $antes = $this->datos();
        $this->pdo->exec('UPDATE usuarios SET eliminado_en = NOW(), activo = 0 WHERE id IN (3, 4)');
        $despues = $this->datos();
        $this->assertSame($antes['solicitudes_vendedor'], $despues['solicitudes_vendedor']);
        $this->assertSame($antes['tokens_recuperacion'], $despues['tokens_recuperacion']);
    }

    public function testUnaPendientePorUsuarioNoImpideConservarHistorialNiVolverASolicitar(): void
    {
        $this->pdo->exec("INSERT INTO solicitudes_vendedor (usuario_id, estado, mensaje) VALUES
            (3, 'aprobada', 'Aprobada original'), (3, 'aprobada', 'Otra aprobada'),
            (3, 'rechazada', 'Rechazada original'), (3, 'rechazada', 'Otra rechazada'),
            (3, 'pendiente', 'Pendiente original'), (4, 'pendiente', 'Otro usuario')");
        $this->rechaza('INSERT INTO solicitudes_vendedor (usuario_id) VALUES (3)', '23505');
        $this->rechaza("UPDATE solicitudes_vendedor SET estado = 'pendiente' WHERE mensaje = 'Aprobada original'", '23505');
        $this->pdo->exec("UPDATE solicitudes_vendedor SET estado = 'rechazada'
            WHERE usuario_id = 3 AND estado = 'pendiente'");

        $this->assertTrue(SolicitudVendedor::crear(3, 'Nueva solicitud')['ok']);
        $this->assertSame(7, (int) $this->pdo->query('SELECT COUNT(*) FROM solicitudes_vendedor')->fetchColumn());
        $this->assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM solicitudes_vendedor WHERE estado = 'pendiente'")->fetchColumn());
        $this->assertSame('Aprobada original', $this->pdo->query("SELECT mensaje FROM solicitudes_vendedor
            WHERE usuario_id = 3 AND estado = 'aprobada' ORDER BY id LIMIT 1")->fetchColumn());
    }

    public function testCrearNoOcultaUn23505AjenoAlIndiceDePendientes(): void
    {
        $this->pdo->exec("INSERT INTO solicitudes_vendedor (id, usuario_id, estado) VALUES (1, 3, 'aprobada')");
        $this->pdo->exec("SELECT setval(pg_get_serial_sequence('solicitudes_vendedor', 'id'), 1, false)");
        $this->expectException(PDOException::class);
        $this->expectExceptionCode('23505');
        SolicitudVendedor::crear(3, 'No ocultar una secuencia desincronizada');
    }

    public function testLasNuevasFkEstanValidadasConNoActionYCategoriaConUpdateCascade(): void
    {
        $filas = $this->pdo->query("SELECT conname, confdeltype, confupdtype, convalidated, condeferrable, condeferred
            FROM pg_constraint WHERE conrelid IN ('productos'::regclass, 'solicitudes_vendedor'::regclass,
                'tokens_recuperacion'::regclass)
            AND conname IN ('fk_productos_marca', 'fk_productos_categoria', 'fk_solicitudes_usuario', 'fk_tokens_usuario')")
            ->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        $this->assertCount(4, $filas);
        foreach ($filas as $nombre => $fk) {
            $this->assertSame('a', $fk['confdeltype'], $nombre);
            $this->assertSame($nombre === 'fk_productos_categoria' ? 'c' : 'a', $fk['confupdtype'], $nombre);
            $this->assertTrue($fk['convalidated'], $nombre);
            $this->assertFalse($fk['condeferred'], $nombre);
        }
        $this->assertTrue($filas['fk_productos_categoria']['condeferrable']);
        $indices = $this->pdo->query("SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'public'")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ([
            'idx_productos_marca' => '(marca_id)',
            'idx_productos_categoria' => '(categoria)',
            'idx_tokens_recuperacion_usuario' => '(usuario_id)',
            'uq_solicitudes_vendedor_pendiente' => '(usuario_id)',
        ] as $nombre => $columnas) {
            $this->assertArrayHasKey($nombre, $indices);
            $this->assertStringContainsString($columnas, $indices[$nombre]);
        }
        $this->assertStringContainsString('CREATE UNIQUE INDEX', $indices['uq_solicitudes_vendedor_pendiente']);
        $this->assertMatchesRegularExpression("/WHERE .*estado.*'pendiente'/", $indices['uq_solicitudes_vendedor_pendiente']);
    }

    public function testMigracionEsIdempotenteConservaDatosEIdentidadesYEquivaleAlEsquemaNuevo(): void
    {
        $this->enEsquemaLegacy(function (array $estructuraNueva): void {
            $antes = $this->datos();
            $identidades = $this->identidades();
            $timeout = $this->pdo->query('SHOW lock_timeout')->fetchColumn();
            $this->migrar();
            $estructura = $this->estructura();
            $this->migrar();

            $this->assertSame($estructuraNueva, $estructura);
            $this->assertSame($estructura, $this->estructura());
            $this->assertSame($antes, $this->datos());
            $this->assertSame($identidades, $this->identidades());
            $this->assertSame($timeout, $this->pdo->query('SHOW lock_timeout')->fetchColumn());
            $this->assertFalse($this->pdo->inTransaction());
        });
    }

    #[DataProvider('datosLegacyInvalidos')]
    public function testMigracionFallaAtomicamenteSinRepararDatosPrevios(string $preparar, string $sqlState): void
    {
        $this->enEsquemaLegacy(function () use ($preparar, $sqlState): void {
            $this->pdo->exec($preparar);
            $antes = $this->datos();
            $estructura = $this->estructura();
            $timeout = $this->pdo->query('SHOW lock_timeout')->fetchColumn();

            $this->rechaza($this->sqlMigracion(), $sqlState);

            $this->assertSame($antes, $this->datos());
            $this->assertSame($estructura, $this->estructura());
            $this->assertSame($timeout, $this->pdo->query('SHOW lock_timeout')->fetchColumn());
        });
    }

    public static function datosLegacyInvalidos(): array
    {
        return [
            'marca inexistente' => ['UPDATE productos SET marca_id = 999999 WHERE id = 1', '23503'],
            'categoria inexistente' => ["UPDATE productos SET categoria = 'Sin categoria' WHERE id = 1", '23503'],
            'solicitud huerfana' => ['UPDATE solicitudes_vendedor SET usuario_id = 999999 WHERE id = 1', '23503'],
            'token huerfano' => ['UPDATE tokens_recuperacion SET usuario_id = 999999 WHERE id = 1', '23503'],
            'dos pendientes del mismo usuario' => ["INSERT INTO solicitudes_vendedor (usuario_id, mensaje) VALUES
                (3, 'Primera pendiente'), (3, 'Segunda pendiente')", '23505'],
        ];
    }

    private function enEsquemaLegacy(callable $prueba): void
    {
        $schema = 'relaciones_test_' . bin2hex(random_bytes(6));
        $this->pdo->exec('CREATE SCHEMA ' . $schema);
        try {
            // Sin public en search_path: ni el DROP del esquema base ni la migración alcanzan tablas compartidas.
            $this->pdo->exec('SET search_path TO ' . $schema);
            $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/jeremy_tech_estructura.sql'));
            $estructuraNueva = $this->estructura();
            foreach ([
                'productos' => ['fk_productos_marca', 'fk_productos_categoria'],
                'solicitudes_vendedor' => ['fk_solicitudes_usuario'],
                'tokens_recuperacion' => ['fk_tokens_usuario'],
            ] as $tabla => $restricciones) {
                foreach ($restricciones as $nombre) {
                    $this->pdo->exec("ALTER TABLE $tabla DROP CONSTRAINT IF EXISTS $nombre");
                }
            }
            foreach (['idx_productos_marca', 'idx_productos_categoria', 'idx_tokens_recuperacion_usuario',
                'uq_solicitudes_vendedor_pendiente'] as $indice) {
                $this->pdo->exec('DROP INDEX IF EXISTS ' . $indice);
            }
            $this->pdo->exec('ALTER TABLE productos ALTER COLUMN categoria TYPE varchar(60)');
            $this->pdo->exec("INSERT INTO usuarios (id, nombre, email, password, empresa, telefono)
                VALUES (3, 'Cuenta conservada', 'legacy@example.com', '!', 'Empresa original', '999000000')");
            $this->pdo->exec("INSERT INTO marcas (id, nombre) VALUES (10, 'Marca original')");
            $this->pdo->exec("INSERT INTO categorias (id, nombre) VALUES (10, 'Celulares')");
            $this->pdo->exec("INSERT INTO productos (id, slug, categoria, nombre, especificaciones, marca_id, precio_pen, stock)
                VALUES (1, 'legacy', 'Celulares', 'Producto original', 'Ficha intacta', 10, 123.45, 7)");
            $this->pdo->exec("INSERT INTO solicitudes_vendedor (usuario_id, estado, mensaje) VALUES
                (3, 'aprobada', 'Historial aprobado'), (3, 'rechazada', 'Historial rechazado')");
            $this->pdo->exec("INSERT INTO tokens_recuperacion (tipo, usuario_id, token, expira_en)
                VALUES ('cliente', 3, 'token-original', '2030-01-01 00:00:00')");
            $prueba($estructuraNueva);
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->pdo->exec('SET search_path TO public');
            $this->pdo->exec('DROP SCHEMA ' . $schema . ' CASCADE');
        }
    }

    private function migrar(): void
    {
        $this->pdo->exec($this->sqlMigracion());
    }

    private function sqlMigracion(): string
    {
        $archivo = __DIR__ . '/../../database/relaciones-integridad.sql';
        $this->assertFileExists($archivo);
        return file_get_contents($archivo);
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

    private function datos(): array
    {
        $datos = [];
        foreach (['usuarios', 'marcas', 'categorias', 'productos', 'solicitudes_vendedor', 'tokens_recuperacion'] as $tabla) {
            $datos[$tabla] = $this->pdo->query("SELECT row_to_json(t)::text FROM $tabla t ORDER BY id")
                ->fetchAll(PDO::FETCH_COLUMN);
        }
        return $datos;
    }

    private function identidades(): array
    {
        return $this->pdo->query("SELECT oid, relname FROM pg_class
            WHERE relnamespace = current_schema()::regnamespace AND relkind IN ('r', 'S') ORDER BY relname")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function estructura(): array
    {
        return [
            'columnas' => $this->pdo->query("SELECT table_name, column_name, data_type, character_maximum_length,
                is_nullable, column_default, is_identity, identity_generation FROM information_schema.columns
                WHERE table_schema = current_schema() AND table_name IN ('productos', 'solicitudes_vendedor', 'tokens_recuperacion')
                ORDER BY table_name, ordinal_position")->fetchAll(PDO::FETCH_ASSOC),
            'restricciones' => $this->pdo->query("SELECT t.relname, c.conname, c.convalidated,
                pg_get_constraintdef(c.oid) AS definicion FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid
                WHERE t.relnamespace = current_schema()::regnamespace
                AND t.relname IN ('productos', 'solicitudes_vendedor', 'tokens_recuperacion') ORDER BY t.relname, c.conname")
                ->fetchAll(PDO::FETCH_ASSOC),
            'indices' => $this->pdo->query("SELECT tablename, indexname, indexdef FROM pg_indexes
                WHERE schemaname = current_schema() AND tablename IN ('productos', 'solicitudes_vendedor', 'tokens_recuperacion')
                ORDER BY tablename, indexname")->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
