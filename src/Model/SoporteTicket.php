<?php
declare(strict_types=1);

namespace Model;

/**
 * Tickets de soporte postventa (Jeremy Tech).
 *
 * Usa la tabla `soporte_tickets`, que existía desde el esquema inicial pero
 * no tenía flujo de tienda ni de panel. Un ticket pertenece a un cliente,
 * puede referenciar un pedido suyo y recibe UNA respuesta del equipo; el
 * estado (`abierto`, `en_proceso`, `resuelto`, `cerrado`) se gestiona desde
 * el panel.
 *
 * Toda consulta del cliente filtra por `cliente_id`: el id del ticket nunca
 * se usa solo, para que una cuenta no pueda leer tickets ajenos.
 */
final class SoporteTicket
{
    public const ABIERTO = 'abierto';
    public const EN_PROCESO = 'en_proceso';
    public const RESUELTO = 'resuelto';
    public const CERRADO = 'cerrado';

    /** @var array<int, string> */
    public const ESTADOS = [self::ABIERTO, self::EN_PROCESO, self::RESUELTO, self::CERRADO];

    /** @var array<int, string> */
    public const CATEGORIAS = [
        'General',
        'Pedido',
        'Pago',
        'Envío',
        'Garantía',
        'Producto',
        'Cuenta',
        'Otro',
    ];

    public const ASUNTO_MIN = 5;
    public const ASUNTO_MAX = 180;
    public const DESCRIPCION_MIN = 10;
    public const DESCRIPCION_MAX = 1000;
    public const RESPUESTA_MAX = 1500;

    /**
     * Crea un ticket para el cliente autenticado.
     *
     * @return array{ok: bool, error?: string, id?: int}
     */
    public static function crear(
        int $clienteId,
        int $pedidoId,
        string $asunto,
        string $categoria,
        string $descripcion
    ): array {
        if ($clienteId <= 0) {
            return ['ok' => false, 'error' => 'Inicia sesión para abrir un ticket.'];
        }

        $asunto = trim($asunto);
        $descripcion = trim($descripcion);
        $categoria = trim($categoria);

        if (!in_array($categoria, self::CATEGORIAS, true)) {
            return ['ok' => false, 'error' => 'Selecciona una categoría válida.'];
        }
        if (mb_strlen($asunto) < self::ASUNTO_MIN) {
            return ['ok' => false, 'error' => 'El asunto debe tener al menos ' . self::ASUNTO_MIN . ' caracteres.'];
        }
        if (mb_strlen($asunto) > self::ASUNTO_MAX) {
            return ['ok' => false, 'error' => 'El asunto no puede superar los ' . self::ASUNTO_MAX . ' caracteres.'];
        }
        if (mb_strlen($descripcion) < self::DESCRIPCION_MIN) {
            return ['ok' => false, 'error' => 'Cuéntanos un poco más: mínimo ' . self::DESCRIPCION_MIN . ' caracteres.'];
        }
        if (mb_strlen($descripcion) > self::DESCRIPCION_MAX) {
            return ['ok' => false, 'error' => 'La descripción no puede superar los ' . self::DESCRIPCION_MAX . ' caracteres.'];
        }

        if ($pedidoId > 0 && self::pedidoDeCliente($pedidoId, $clienteId) === null) {
            return ['ok' => false, 'error' => 'El pedido indicado no pertenece a tu cuenta.'];
        }

        $stmt = conexion()->prepare(
            'INSERT INTO soporte_tickets
                (cliente_id, pedido_id, asunto, categoria, descripcion, estado)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $clienteId,
            $pedidoId > 0 ? $pedidoId : null,
            $asunto,
            $categoria,
            $descripcion,
            self::ABIERTO,
        ]);

        return ['ok' => true, 'id' => (int) conexion()->lastInsertId()];
    }

    /**
     * Tickets de un cliente, los más recientes primero.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deCliente(int $clienteId, int $limite = 50): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $stmt = conexion()->prepare(
            'SELECT t.*
               FROM soporte_tickets t
              WHERE t.cliente_id = ?
           ORDER BY t.id DESC
              LIMIT ' . $limite
        );
        $stmt->execute([$clienteId]);

        return $stmt->fetchAll();
    }

    /**
     * Resumen de estados para la cabecera del cliente.
     *
     * @return array{total: int, abiertos: int, en_proceso: int, resueltos: int, cerrados: int}
     */
    public static function resumenDeCliente(int $clienteId): array
    {
        $resumen = [
            'total' => 0, 'abiertos' => 0, 'en_proceso' => 0,
            'resueltos' => 0, 'cerrados' => 0,
        ];
        if ($clienteId <= 0) {
            return $resumen;
        }

        $stmt = conexion()->prepare(
            'SELECT estado, COUNT(*) AS total
               FROM soporte_tickets
              WHERE cliente_id = ?
           GROUP BY estado'
        );
        $stmt->execute([$clienteId]);

        foreach ($stmt->fetchAll() as $fila) {
            $estado = (string) $fila['estado'];
            $cantidad = (int) $fila['total'];
            $resumen['total'] += $cantidad;
            if ($estado === self::ABIERTO) {
                $resumen['abiertos'] += $cantidad;
            } elseif ($estado === self::EN_PROCESO) {
                $resumen['en_proceso'] += $cantidad;
            } elseif ($estado === self::RESUELTO) {
                $resumen['resueltos'] += $cantidad;
            } elseif ($estado === self::CERRADO) {
                $resumen['cerrados'] += $cantidad;
            }
        }

        return $resumen;
    }

    /**
     * Pedidos del cliente para el selector "¿Sobre qué pedido?".
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pedidosDeCliente(int $clienteId, int $limite = 30): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $stmt = conexion()->prepare(
            'SELECT id, estado, total_pen, creado_en
               FROM pedidos
              WHERE cliente_id = ?
           ORDER BY id DESC
              LIMIT ' . $limite
        );
        $stmt->execute([$clienteId]);

        return $stmt->fetchAll();
    }

    /**
     * Pedido del cliente (validación de propiedad y precarga del formulario).
     *
     * @return array<string, mixed>|null
     */
    public static function pedidoDeCliente(int $pedidoId, int $clienteId): ?array
    {
        if ($pedidoId <= 0 || $clienteId <= 0) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT id, estado, total_pen, creado_en
               FROM pedidos
              WHERE id = ? AND cliente_id = ?
              LIMIT 1'
        );
        $stmt->execute([$pedidoId, $clienteId]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Cola del panel con nombre y correo del cliente.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todos(string $estado = '', string $buscar = '', int $limite = 250): array
    {
        $sql = 'SELECT t.*, u.name AS cliente_nombre, u.apellidos AS cliente_apellidos,
                       u.email AS cliente_email
                  FROM soporte_tickets t
             LEFT JOIN users u ON u.id = t.cliente_id
                 WHERE 1 = 1';
        $params = [];

        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $sql .= ' AND t.estado = ?';
            $params[] = $estado;
        }

        $buscar = trim($buscar);
        if ($buscar !== '') {
            $sql .= ' AND (t.asunto LIKE ? OR t.descripcion LIKE ? OR t.respuesta LIKE ?
                           OR u.name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $buscar . '%';
            for ($i = 0; $i < 5; $i++) {
                $params[] = $like;
            }
        }

        $limite = max(1, min(500, $limite));
        $sql .= " ORDER BY FIELD(t.estado, 'abierto', 'en_proceso', 'resuelto', 'cerrado'), t.id DESC
                  LIMIT " . $limite;

        $stmt = conexion()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function porId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT t.*, u.name AS cliente_nombre, u.apellidos AS cliente_apellidos,
                    u.email AS cliente_email
               FROM soporte_tickets t
          LEFT JOIN users u ON u.id = t.cliente_id
              WHERE t.id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /**
     * Guarda la respuesta del equipo y avisa al cliente.
     *
     * @return string|null null si se guardó, o el mensaje de error.
     */
    public static function responder(int $id, string $respuesta, string $adminUsuario): ?string
    {
        $respuesta = trim($respuesta);
        if ($id <= 0) {
            return 'Ticket no válido.';
        }
        if (mb_strlen($respuesta) < 2) {
            return 'Escribe una respuesta para el cliente.';
        }
        if (mb_strlen($respuesta) > self::RESPUESTA_MAX) {
            return 'La respuesta no puede superar los ' . self::RESPUESTA_MAX . ' caracteres.';
        }

        $ticket = self::porId($id);
        if ($ticket === null) {
            return 'El ticket ya no existe.';
        }

        $estado = (string) $ticket['estado'];
        if ($estado === self::CERRADO) {
            return 'El ticket está cerrado; reábrelo antes de responder.';
        }

        $nuevoEstado = $estado === self::ABIERTO ? self::EN_PROCESO : $estado;

        conexion()->prepare(
            'UPDATE soporte_tickets
                SET respuesta = ?, respondido_por = ?, respondido_en = NOW(), estado = ?
              WHERE id = ?'
        )->execute([
            $respuesta,
            mb_substr($adminUsuario, 0, 60),
            $nuevoEstado,
            $id,
        ]);

        self::notificarRespuesta($ticket, $respuesta);

        return null;
    }

    /**
     * Cambia el estado de un ticket desde el panel.
     */
    public static function cambiarEstado(int $id, string $estado): bool
    {
        if ($id <= 0 || !in_array($estado, self::ESTADOS, true)) {
            return false;
        }

        $stmt = conexion()->prepare('UPDATE soporte_tickets SET estado = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);

        // rowCount() es 0 cuando el ticket ya tenía ese estado: la operación
        // es idempotente, así que se comprueba que la fila exista.
        if ($stmt->rowCount() === 1) {
            return true;
        }

        $existe = conexion()->prepare('SELECT 1 FROM soporte_tickets WHERE id = ? LIMIT 1');
        $existe->execute([$id]);

        return $existe->fetchColumn() !== false;
    }

    /**
     * Contadores globales de la cola del panel.
     *
     * @return array{total: int, abiertos: int, en_proceso: int, resueltos: int, cerrados: int}
     */
    public static function resumen(): array
    {
        $resumen = [
            'total' => 0, 'abiertos' => 0, 'en_proceso' => 0,
            'resueltos' => 0, 'cerrados' => 0,
        ];

        foreach (conexion()->query(
            'SELECT estado, COUNT(*) AS total FROM soporte_tickets GROUP BY estado'
        )->fetchAll() as $fila) {
            $estado = (string) $fila['estado'];
            $cantidad = (int) $fila['total'];
            $resumen['total'] += $cantidad;
            if ($estado === self::ABIERTO) {
                $resumen['abiertos'] += $cantidad;
            } elseif ($estado === self::EN_PROCESO) {
                $resumen['en_proceso'] += $cantidad;
            } elseif ($estado === self::RESUELTO) {
                $resumen['resueltos'] += $cantidad;
            } elseif ($estado === self::CERRADO) {
                $resumen['cerrados'] += $cantidad;
            }
        }

        return $resumen;
    }

    /** Código visible del ticket (`#ST-00001`). */
    public static function codigo(int $id): string
    {
        return '#ST-' . str_pad((string) max(0, $id), 5, '0', STR_PAD_LEFT);
    }

    /** Etiqueta legible del estado. */
    public static function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            self::ABIERTO => 'Abierto',
            self::EN_PROCESO => 'En proceso',
            self::RESUELTO => 'Resuelto',
            self::CERRADO => 'Cerrado',
            default => $estado,
        };
    }

    /**
     * Registra el aviso interno y el correo simulado de la respuesta.
     *
     * @param array<string, mixed> $ticket
     */
    private static function notificarRespuesta(array $ticket, string $respuesta): void
    {
        $clienteId = (int) ($ticket['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            return;
        }

        $email = trim((string) ($ticket['cliente_email'] ?? ''));
        $pedidoId = (int) ($ticket['pedido_id'] ?? 0);
        $codigo = self::codigo((int) ($ticket['id'] ?? 0));
        $asunto = 'Respuesta a tu ticket ' . $codigo . ' — Jeremy Tech';
        $resumen = 'Nuestro equipo respondió tu ticket "' . (string) ($ticket['asunto'] ?? '')
            . '". Ingresa a Mis tickets para leer la respuesta completa.';

        $archivo = '';
        $error = '';

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            try {
                $h = static fn ($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
                $html = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
                    . '<title>' . $h($asunto) . '</title></head>'
                    . '<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">'
                    . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">'
                    . '<tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden;">'
                    . '<tr><td style="background:#0a0f1a;padding:22px 26px;color:#fff;">'
                    . '<div style="font-size:20px;font-weight:700;letter-spacing:1px;color:#00d4ff;">JEREMY-TECH</div>'
                    . '<div style="font-size:13px;color:#9ca3af;margin-top:4px;">Soporte postventa</div></td></tr>'
                    . '<tr><td style="padding:26px;">'
                    . '<h1 style="margin:0 0 8px;font-size:22px;color:#111827;">Tu ticket fue respondido</h1>'
                    . '<p style="margin:0 0 12px;color:#4b5563;line-height:1.6;">Hola. Respondimos el ticket <strong>'
                    . $h($codigo) . '</strong>: ' . $h((string) ($ticket['asunto'] ?? '')) . '.</p>'
                    . '<p style="margin:0 0 18px;color:#4b5563;line-height:1.6;white-space:pre-wrap;">'
                    . $h($respuesta) . '</p>'
                    . '<p style="margin:0;color:#6b7280;font-size:12px;">Este mensaje fue generado en modo de demostración.</p>'
                    . '</td></tr></table></td></tr></table></body></html>';

                $texto = "JEREMY-TECH\nSoporte postventa\n\n"
                    . "Tu ticket {$codigo} fue respondido.\n\n"
                    . $respuesta . "\n\n"
                    . "Ingresa a Mis tickets para ver el detalle.\n\n"
                    . "Este mensaje fue generado en modo de demostración. No se envió por correo.\n";

                $archivo = CorreoSimulado::guardar(
                    'soporte_respuesta',
                    $pedidoId,
                    $email,
                    $asunto,
                    $html,
                    $texto
                );
            } catch (\Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 255);
                error_log('Correo de soporte del ticket ' . $codigo . ': ' . $error);
            }
        }

        try {
            conexion()->prepare(
                'INSERT INTO notificaciones
                    (cliente_id, pedido_id, destinatario, tipo, asunto, cuerpo, archivo, error, enviado, enviado_en)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)'
            )->execute([
                $clienteId,
                $pedidoId > 0 ? $pedidoId : null,
                mb_substr($email, 0, 190),
                'soporte_respuesta',
                mb_substr($asunto, 0, 180),
                mb_substr($resumen, 0, 500),
                $archivo,
                $error,
            ]);
        } catch (\Throwable $e) {
            error_log('No se pudo registrar la notificación del ticket ' . $codigo . ': ' . $e->getMessage());
        }
    }
}
