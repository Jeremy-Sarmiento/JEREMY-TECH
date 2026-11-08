<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;

class SolicitudConcurrenteTest extends TestCase
{
    public function testCrearTraduceLaColisionDelIndiceEnSolicitudPendiente(): void
    {
        $this->assertSame('jeremy_tech_test', DB_NAME);
        $pdo = testPdo();
        $this->assertSame('jeremy_tech_test', $pdo->query('SELECT current_database()')->fetchColumn());
        resetTestDb();
        $nombre = 'jt_solicitud_' . bin2hex(random_bytes(6));
        $bloqueo = random_int(1, 2147483647);
        $proceso = null;
        $pipes = [];

        $pdo->exec("CREATE FUNCTION $nombre() RETURNS trigger LANGUAGE plpgsql AS \$\$
            BEGIN
                IF current_setting('application_name') = '$nombre' THEN
                    PERFORM pg_advisory_xact_lock($bloqueo);
                END IF;
                RETURN NEW;
            END; \$\$");
        try {
            $pdo->exec("CREATE TRIGGER $nombre BEFORE INSERT ON solicitudes_vendedor
                FOR EACH ROW EXECUTE FUNCTION $nombre()");
            $pdo->exec("SELECT pg_advisory_lock($bloqueo)");
            $codigo = 'require ' . var_export(__DIR__ . '/../bootstrap.php', true) . ';'
                . 'conexion()->exec(' . var_export("SET application_name = '$nombre'; SET statement_timeout = '10s'", true) . ');'
                . 'try { echo json_encode(\Model\SolicitudVendedor::crear(3, "Solicitud concurrente")); }'
                . 'catch (\Throwable $e) { echo json_encode(["exception" => get_class($e), "code" => $e->getCode()]); }';
            $proceso = proc_open([PHP_BINARY, '-r', $codigo], [
                0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
            ], $pipes);
            $this->assertIsResource($proceso);

            // El INSERT espera después de pendienteDe(): la otra conexión puede ganar sin carreras de temporización.
            $esperando = $pdo->prepare("SELECT COUNT(*) FROM pg_stat_activity
                WHERE datname = current_database() AND application_name = ?
                AND wait_event_type = 'Lock' AND wait_event = 'advisory'");
            $limite = microtime(true) + 5;
            do {
                $esperando->execute([$nombre]);
                $bloqueado = (int) $esperando->fetchColumn() === 1;
                if (!$bloqueado) {
                    usleep(10000);
                }
            } while (!$bloqueado && microtime(true) < $limite);
            $this->assertTrue($bloqueado, 'El trabajador no alcanzó la barrera tras consultar las pendientes.');

            $pdo->exec("INSERT INTO solicitudes_vendedor (usuario_id, mensaje) VALUES (3, 'Solicitud ganadora')");
            $pdo->exec("SELECT pg_advisory_unlock($bloqueo)");
            $salida = stream_get_contents($pipes[1]);
            $errores = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $estado = proc_close($proceso);
            $proceso = null;
            $pipes = [];

            $this->assertSame(0, $estado, $errores);
            $resultado = json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
            $this->assertArrayNotHasKey('exception', $resultado, $salida);
            $this->assertFalse($resultado['ok']);
            $this->assertStringContainsString('pendiente', $resultado['error']);
            $this->assertSame([
                ['usuario_id' => 3, 'estado' => 'pendiente', 'mensaje' => 'Solicitud ganadora'],
            ], $pdo->query('SELECT usuario_id, estado, mensaje FROM solicitudes_vendedor ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
        } finally {
            $pdo->exec("SELECT pg_advisory_unlock($bloqueo)");
            if (is_resource($proceso)) {
                foreach ($pipes as $pipe) {
                    stream_get_contents($pipe);
                    fclose($pipe);
                }
                proc_close($proceso);
            }
            $pdo->exec("DROP TRIGGER IF EXISTS $nombre ON solicitudes_vendedor");
            $pdo->exec("DROP FUNCTION $nombre()");
        }
    }
}
