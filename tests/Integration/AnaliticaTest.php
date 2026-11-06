<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Model\Analitica;
use PHPUnit\Framework\TestCase;

final class AnaliticaTest extends TestCase
{
    private const DESDE = '2024-01-10';
    private const HASTA = '2024-01-11';

    protected function setUp(): void
    {
        resetTestDb();
        testPdo()->exec("INSERT INTO productos (id, slug, vendedor_id, categoria, nombre, especificaciones)
            VALUES (1, 'telefono', 2, 'Celulares', 'Teléfono', ''),
                   (2147483648, 'ajeno', 1, 'Celulares', 'Otro teléfono', '')");
    }

    public function testResumenConservaCamposTiposFechasYPrecisionDeIngresos(): void
    {
        $this->evento('visita', '{}', '', '2024-01-10 00:00:00');
        $this->evento('visita');
        $this->evento('producto_visto', '{"producto_id":1}');
        $this->evento('busqueda', '{"consulta":"teléfono"}');
        $this->evento('agregar_carrito', '{"producto_id":"1"}');
        $this->evento('iniciar_checkout', '{"producto_id":1}');
        $this->evento('compra', '{"total_pen":"100.125"}');
        $this->evento('compra', '{"total_pen":2.5}', '', '2024-01-11 23:59:59');
        $this->evento('clic_whatsapp');
        $this->evento('otro');
        $this->evento('compra', '{"total_pen":999}', '', '2024-01-09 23:59:59');
        $this->evento('compra', '{"total_pen":999}', '', '2024-01-12 00:00:00');

        $this->assertSame([
            'visitas' => 2,
            'vistas_producto' => 1,
            'busquedas' => 1,
            'agregados_carrito' => 1,
            'checkouts' => 1,
            'compras' => 2,
            'ingresos_pen' => 102.625,
            'clics_whatsapp' => 1,
            'tasa_conversion' => 100.0,
        ], Analitica::resumenEntre(self::DESDE, self::HASTA));
        $tipos = Analitica::eventosPorTipoEntre(self::DESDE, self::HASTA);
        $this->assertSame(10, array_sum($tipos));
        $this->assertSame(2, $tipos['compra']);
        $this->assertSame(1, $tipos['otro']);
    }

    public function testProductosAgrupanPorIdSinSepararSlugsYConservanEventosSoloSlug(): void
    {
        $this->evento('producto_visto', '{"producto_id":1,"slug":"antiguo"}');
        $this->evento('agregar_carrito', '{"producto_id":"1","slug":"telefono"}');
        $this->evento('compra', '{"producto_id":1}');
        $this->evento('producto_visto', '{"slug":" \tTELÉFONO-SIN-FICHA\n "}');
        $this->evento('producto_visto', '{"producto_id":"","slug":"teléfono-sin-ficha"}');
        $this->evento('producto_visto', '{"slug":" TELEFONO "}');
        $this->evento('producto_visto', '{"producto_id":2147483648,"slug":"ajeno"}');
        $this->evento('producto_visto', '{}');
        $this->evento('producto_visto', '{"producto_id":null,"slug":" \t "}');
        $this->evento('visita', '{"producto_id":1}');

        $this->assertSame([
            ['clave' => 'id:1', 'etiqueta' => 'Teléfono', 'interacciones' => 3],
            ['clave' => 'slug:teléfono-sin-ficha', 'etiqueta' => 'slug:teléfono-sin-ficha', 'interacciones' => 2],
            ['clave' => 'slug:telefono', 'etiqueta' => 'Teléfono', 'interacciones' => 1],
            ['clave' => 'id:2147483648', 'etiqueta' => 'Otro teléfono', 'interacciones' => 1],
        ], Analitica::topProductosEntre(self::DESDE, self::HASTA));
        $this->assertCount(1, Analitica::topProductosEntre(self::DESDE, self::HASTA, 0));
        $this->assertCount(2, Analitica::topProductosEntre(self::DESDE, self::HASTA, 2));
    }

    public function testPaginasYBusquedasNormalizanMayusculasEspaciosYCamposFaltantes(): void
    {
        foreach ([" \tCATÁLOGO\n ", 'catálogo', 'CATÁLOGO', '', " \r\n\t ", ' INICIO ', ' OFERTAS '] as $pagina) {
            $this->evento('visita', '{}', $pagina);
        }
        foreach ([
            '{"consulta":" \tTELÉFONO\n "}', '{"consulta":"teléfono"}', '{"consulta":"TELÉFONO"}',
            '{"consulta":"  auriculares  "}', '{"consulta":""}', '{"consulta":" \r\n\t "}',
            '{"consulta":null}', '{}',
        ] as $datos) {
            $this->evento('busqueda', $datos);
        }

        $this->assertSame([
            ['pagina' => 'catálogo', 'visitas' => 3],
            ['pagina' => 'inicio', 'visitas' => 3],
            ['pagina' => 'ofertas', 'visitas' => 1],
        ], Analitica::topPaginasEntre(self::DESDE, self::HASTA));
        $this->assertSame([
            ['consulta' => 'teléfono', 'busquedas' => 3],
            ['consulta' => 'auriculares', 'busquedas' => 1],
        ], Analitica::topBusquedasEntre(self::DESDE, self::HASTA));
        $this->assertCount(1, Analitica::topPaginasEntre(self::DESDE, self::HASTA, -1));
        $this->assertCount(1, Analitica::topBusquedasEntre(self::DESDE, self::HASTA, 0));
    }

    public function testChatbotCuentaComoFallbackCualquierOrigenDistintoDeFaqOIA(): void
    {
        foreach (['{"origen":"faq"}', '{"origen":"ia"}', '{"origen":"fallback"}',
            '{"origen":"nuevo"}', '{"origen":"FAQ"}', '{"origen":""}', '{"origen":null}', '{}', '{'] as $datos) {
            $this->evento('pregunta_chatbot', $datos);
        }
        $this->evento('visita', '{"origen":"faq"}');

        $this->assertSame([
            'total_preguntas' => 9, 'faq' => 1, 'ia' => 1, 'fallback' => 7,
        ], Analitica::chatbotEntre(self::DESDE, self::HASTA));
        $this->assertSame([
            'total_preguntas' => 0, 'faq' => 0, 'ia' => 0, 'fallback' => 0,
        ], Analitica::chatbotEntre('2023-01-01', '2023-01-01'));
    }

    public function testVendedorPriorizaIdSobreSlugYExcluyeEventosGlobales(): void
    {
        $this->evento('producto_visto', '{"producto_id":1,"slug":"ajeno"}');
        $this->evento('agregar_carrito', '{"slug":" \tTELEFONO\n "}');
        $this->evento('compra', '{"producto_id":"","slug":"telefono","total_pen":"12.50"}');
        $this->evento('iniciar_checkout', '{"producto_id":null,"slug":"telefono"}');
        $this->evento('producto_visto', '{"producto_id":2147483648,"slug":"telefono"}');
        $this->evento('compra', '{"producto_id":999,"slug":"telefono","total_pen":1000}');
        $this->evento('producto_visto', '{"slug":"ajeno"}');
        $this->evento('producto_visto', '{"producto_id":1');
        foreach (['visita', 'busqueda', 'clic_whatsapp'] as $tipo) {
            $this->evento($tipo, '{"producto_id":1,"slug":"telefono","consulta":"teléfono"}');
        }

        $eventos = Analitica::eventosEntre(self::DESDE, self::HASTA, 2);
        $this->assertCount(4, $eventos);
        $this->assertSame(['id', 'tipo_evento', 'datos_json', 'pagina', 'cliente_id', 'creado_en'], array_keys($eventos[0]));
        $this->assertEqualsCanonicalizing([
            'producto_visto', 'agregar_carrito', 'compra', 'iniciar_checkout',
        ], array_column($eventos, 'tipo_evento'));
        $this->assertSame([
            'visitas' => 0, 'vistas_producto' => 1, 'busquedas' => 0, 'agregados_carrito' => 1,
            'checkouts' => 1, 'compras' => 1, 'ingresos_pen' => 12.5, 'clics_whatsapp' => 0,
            'tasa_conversion' => 0.0,
        ], Analitica::resumenEntre(self::DESDE, self::HASTA, 2));
        $this->assertSame([
            ['clave' => 'slug:telefono', 'etiqueta' => 'Teléfono', 'interacciones' => 2],
            ['clave' => 'id:1', 'etiqueta' => 'Teléfono', 'interacciones' => 1],
        ], Analitica::topProductosEntre(self::DESDE, self::HASTA, 10, 2));
        $this->assertSame(4, array_sum(Analitica::eventosPorTipoEntre(self::DESDE, self::HASTA, 2)));
        $this->assertSame([], Analitica::topPaginasEntre(self::DESDE, self::HASTA, 10, 2));
        $this->assertSame([], Analitica::topBusquedasEntre(self::DESDE, self::HASTA, 10, 2));
    }

    public function testVendedoresSinProductosIncluidoCeroNoRecibenDatosGlobales(): void
    {
        $this->evento('visita');
        $this->evento('compra', '{"producto_id":1,"total_pen":50}');
        foreach ([0, 3, -1] as $vendedorId) {
            $this->assertSame([], Analitica::eventosEntre(self::DESDE, self::HASTA, $vendedorId));
            $this->assertSame([], Analitica::eventosPorTipoEntre(self::DESDE, self::HASTA, $vendedorId));
            $this->assertSame([], Analitica::topProductosEntre(self::DESDE, self::HASTA, 10, $vendedorId));
            $this->assertSame([], Analitica::topPaginasEntre(self::DESDE, self::HASTA, 10, $vendedorId));
            $this->assertSame([], Analitica::topBusquedasEntre(self::DESDE, self::HASTA, 10, $vendedorId));
            $this->assertSame([
                'visitas' => 0, 'vistas_producto' => 0, 'busquedas' => 0, 'agregados_carrito' => 0,
                'checkouts' => 0, 'compras' => 0, 'ingresos_pen' => 0.0, 'clics_whatsapp' => 0,
                'tasa_conversion' => 0.0,
            ], Analitica::resumenEntre(self::DESDE, self::HASTA, $vendedorId));
        }
    }

    public function testJsonHistoricoInvalidoYCamposNoNumericosNoRompenNiSeReescriben(): void
    {
        $datosHistoricos = [
            '', '{', '{"producto_id":1', 'null', '[]', '"texto"',
            '{"producto_id":"","total_pen":""}', '{"producto_id":"no-id","total_pen":"no-dinero"}',
            '{"producto_id":null,"total_pen":null}', '{"producto_id":{},"total_pen":{}}',
            '{"producto_id":[],"total_pen":[]}', '{"producto_id":"1e999999","total_pen":"1e999999"}',
            '{"total_pen":"NaN"}', '{"total_pen":"Infinity"}', '{"consulta":"\\u0000"}',
        ];
        foreach ($datosHistoricos as $datos) {
            $this->evento('compra', $datos);
            $this->evento('busqueda', $datos);
        }
        $this->evento('compra', '{"total_pen":"1.25e2"}');
        $this->evento('compra', '{"total_pen":" 2.5 soles"}');
        $antes = testPdo()->query('SELECT id, datos_json FROM eventos_analitica ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        $resumen = Analitica::resumenEntre(self::DESDE, self::HASTA);
        $this->assertSame(count($datosHistoricos) + 2, $resumen['compras']);
        $this->assertSame(127.5, $resumen['ingresos_pen']);
        $this->assertSame([], Analitica::topProductosEntre(self::DESDE, self::HASTA));
        $this->assertSame([], Analitica::topBusquedasEntre(self::DESDE, self::HASTA));
        $this->assertSame([], Analitica::eventosEntre(self::DESDE, self::HASTA, 2));
        $this->assertSame($antes, testPdo()->query('SELECT id, datos_json FROM eventos_analitica ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
    }

    public function testRegistrarNoTruncaJsonYRespetaCapacidadEnCaracteresNoBytes(): void
    {
        $datos = ['consulta' => str_repeat('Á', 1000 - mb_strlen((string) json_encode(['consulta' => ''])))];
        $this->assertTrue(Analitica::registrar(str_repeat('v', 61), $datos, ' inicio ', 3));
        $fila = testPdo()->query('SELECT * FROM eventos_analitica')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($datos, json_decode($fila['datos_json'], true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(1000, mb_strlen($fila['datos_json']));
        $this->assertGreaterThan(1000, strlen($fila['datos_json']));
        $this->assertSame(str_repeat('v', 60), $fila['tipo_evento']);
        $this->assertSame('inicio', $fila['pagina']);
        $this->assertSame(3, (int) $fila['cliente_id']);

        $this->assertTrue(Analitica::registrar('busqueda', ['consulta' => str_repeat('x', 1000)]));
        $this->assertSame(2, (int) testPdo()->query('SELECT COUNT(*) FROM eventos_analitica')->fetchColumn());
        $this->assertSame('{}', testPdo()->query('SELECT datos_json FROM eventos_analitica ORDER BY id DESC LIMIT 1')->fetchColumn());
        $this->assertTrue(Analitica::registrar('busqueda', ['consulta' => "\xB1"]));
        $this->assertSame('{}', testPdo()->query('SELECT datos_json FROM eventos_analitica ORDER BY id DESC LIMIT 1')->fetchColumn());
    }

    public function testConsultaRespetaTransaccionDelLlamadorYRecreaFuncionesTrasRollback(): void
    {
        $this->evento('producto_visto', '{"producto_id":1}');
        $pdo = conexion();
        $pdo->beginTransaction();
        try {
            $this->assertSame(1, Analitica::resumenEntre(self::DESDE, self::HASTA, 2)['vistas_producto']);
            $this->assertTrue($pdo->inTransaction());
        } finally {
            $pdo->rollBack();
        }
        $this->assertSame(1, Analitica::resumenEntre(self::DESDE, self::HASTA, 2)['vistas_producto']);
        $this->assertFalse($pdo->inTransaction());
    }

    private function evento(string $tipo, string $datos = '{}', string $pagina = '', string $fecha = '2024-01-10 12:00:00'): void
    {
        $stmt = testPdo()->prepare('INSERT INTO eventos_analitica (tipo_evento, datos_json, pagina, creado_en) VALUES (?, ?, ?, ?)');
        $stmt->execute([$tipo, $datos, $pagina, $fecha]);
    }
}
