<?php
declare(strict_types=1);

namespace Model;

/**
 * Procesador de pagos simulados de Jeremy Tech.
 *
 * No se conecta a ninguna pasarela y NUNCA cobra dinero real. Su objetivo es
 * que el checkout tenga un comportamiento realista: valida los datos mínimos,
 * aprueba o rechaza tarjetas de prueba, confirma Yape y deja en estado
 * pendiente los métodos que requieren verificación humana o pago al recibir.
 *
 * Los datos sensibles de la tarjeta se usan solo en memoria para validar y
 * jamás se guardan en la base de datos ni se devuelven al navegador.
 */
final class PagoSimulado
{
    /** Tarjeta de prueba que siempre se aprueba. */
    public const TARJETA_APROBADA = '4111111111111111';

    /** Tarjeta de prueba que siempre se rechaza. */
    public const TARJETA_RECHAZADA = '4000000000000002';

    /** Código Yape que simula un rechazo. */
    public const YAPE_RECHAZADO = '000000';

    /**
     * Procesa un intento de pago simulado.
     *
     * @param string               $metodo Método elegido en el checkout.
     * @param array<string, mixed> $datos  Datos del formulario.
     * @return array{
     *     ok: bool,
     *     error?: string,
     *     metodo?: string,
     *     estado_pedido?: string,
     *     estado_pago?: string,
     *     transaccion_id?: string,
     *     mensaje?: string
     * }
     */
    public static function procesar(string $metodo, array $datos): array
    {
        return match (strtolower(trim($metodo))) {
            'tarjeta'       => self::tarjeta($datos),
            'yape'          => self::yape($datos),
            'transferencia' => self::transferencia($datos),
            'contraentrega' => self::contraentrega(),
            default         => self::error('Selecciona un método de pago válido.'),
        };
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private static function tarjeta(array $datos): array
    {
        $numero  = preg_replace('/\D+/', '', (string) ($datos['tarjeta_numero'] ?? '')) ?? '';
        $titular = trim((string) ($datos['tarjeta_titular'] ?? ''));
        $expira  = trim((string) ($datos['tarjeta_expiracion'] ?? ''));
        $cvv     = preg_replace('/\D+/', '', (string) ($datos['tarjeta_cvv'] ?? '')) ?? '';

        if (strlen($numero) < 13 || strlen($numero) > 19 || !self::luhn($numero)) {
            return self::error('El número de tarjeta no es válido. Revisa los dígitos.');
        }
        if (mb_strlen($titular) < 3 || mb_strlen($titular) > 80) {
            return self::error('Ingresa el nombre del titular tal como aparece en la tarjeta.');
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expira, $partes)) {
            return self::error('La fecha de expiración debe tener el formato MM/AA.');
        }
        $mes  = (int) $partes[1];
        $anio = 2000 + (int) $partes[2];
        $ultimoDia = (new \DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes)))
            ->modify('last day of this month')
            ->setTime(23, 59, 59);
        if ($ultimoDia < new \DateTimeImmutable('now')) {
            return self::error('La tarjeta está vencida.');
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            return self::error('El CVV debe tener 3 o 4 dígitos.');
        }

        if ($numero === self::TARJETA_RECHAZADA) {
            return self::error(
                'Pago rechazado por el banco emisor (simulación). '
                . 'Prueba con la tarjeta 4111 1111 1111 1111.'
            );
        }

        return self::aprobado(
            'tarjeta',
            self::referencia('CARD'),
            'Pago con tarjeta aprobado en modo demostración. No se realizó ningún cobro real.'
        );
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private static function yape(array $datos): array
    {
        $telefono = preg_replace('/\D+/', '', (string) ($datos['yape_telefono'] ?? '')) ?? '';
        $codigo   = preg_replace('/\D+/', '', (string) ($datos['yape_codigo'] ?? '')) ?? '';

        if (!preg_match('/^9\d{8}$/', $telefono)) {
            return self::error('Ingresa un celular Yape de 9 dígitos que empiece con 9.');
        }
        if (!preg_match('/^\d{6}$/', $codigo)) {
            return self::error('El código de operación Yape debe tener 6 dígitos.');
        }
        if ($codigo === self::YAPE_RECHAZADO) {
            return self::error(
                'Yape rechazó la operación (simulación). '
                . 'Usa un código distinto de 000000 para simular la aprobación.'
            );
        }

        return self::aprobado(
            'yape',
            self::referencia('YAPE'),
            'Yape verificado en modo demostración. No se realizó ningún cobro real.'
        );
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private static function transferencia(array $datos): array
    {
        $codigo = trim((string) ($datos['transf_codigo'] ?? ''));

        if (!preg_match('/^[A-Za-z0-9-]{4,15}$/', $codigo)) {
            return self::error('Ingresa un número de operación bancaria válido (4 a 15 caracteres).');
        }

        return [
            'ok'             => true,
            'metodo'         => 'transferencia',
            'estado_pedido'  => 'Pendiente',
            'estado_pago'    => 'En verificación',
            'transaccion_id' => self::referencia('TRANSF'),
            'mensaje'        => 'Pedido registrado. La transferencia quedó en verificación (simulación); '
                . 'un administrador confirmará el pago.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function contraentrega(): array
    {
        return [
            'ok'             => true,
            'metodo'         => 'contraentrega',
            'estado_pedido'  => 'Pendiente',
            'estado_pago'    => 'Pendiente',
            'transaccion_id' => self::referencia('CONTRA'),
            'mensaje'        => 'Pedido registrado. El pago queda pendiente hasta la entrega (simulación).',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function aprobado(string $metodo, string $referencia, string $mensaje): array
    {
        return [
            'ok'             => true,
            'metodo'         => $metodo,
            'estado_pedido'  => 'Pagado',
            'estado_pago'    => 'Pagado',
            'transaccion_id' => $referencia,
            'mensaje'        => $mensaje,
        ];
    }

    /**
     * @return array{ok: false, error: string}
     */
    private static function error(string $mensaje): array
    {
        return ['ok' => false, 'error' => $mensaje];
    }

    /** Referencia interna de una transacción simulada. */
    private static function referencia(string $tipo): string
    {
        return 'SIM-' . $tipo . '-' . date('YmdHis') . '-' . random_int(1000, 9999);
    }

    /** Algoritmo de Luhn: valida el dígito verificador sin consultar bancos. */
    private static function luhn(string $numero): bool
    {
        $suma = 0;
        $duplicar = false;
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];
            if ($duplicar) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $suma += $digito;
            $duplicar = !$duplicar;
        }

        return $suma % 10 === 0;
    }
}
