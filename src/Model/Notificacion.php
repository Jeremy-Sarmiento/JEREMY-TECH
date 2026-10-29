<?php

declare(strict_types=1);

namespace Model;

/**
 * Notificaciones de pedido en modo SIMULADO.
 *
 * Eventos de correo:
 *   - pago confirmado;
 *   - pedido enviado / listo para recojo;
 *   - reembolso procesado.
 *
 * No se envía nada por SMTP. Cada notificación se escribe en
 * `MAIL_STORAGE_PATH/AAAA/MM` y se registra en `notificaciones` con
 * `enviado = 0`.
 * La deduplicación por `(pedido_id, tipo)` evita que un doble POST del panel
 * genere dos correos del mismo hito.
 */
final class Notificacion
{
    public const PAGO_CONFIRMADO = 'pago_confirmado';
    public const PEDIDO_ENVIADO = 'pedido_enviado';
    public const REEMBOLSO = 'reembolso';

    /**
     * Avisos registrados para un cliente, más recientes primero.
     *
     * La bandeja del cliente es la contraparte visible de los correos
     * simulados: no se envía nada por SMTP, pero el aviso queda aquí para
     * que el comprador pueda consultarlo.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function deCliente(int $clienteId, int $limite = 100): array
    {
        if ($clienteId <= 0) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $stmt = conexion()->prepare(
            'SELECT id, pedido_id, tipo, asunto, cuerpo, enviado, creado_en
               FROM notificaciones
              WHERE cliente_id = ?
           ORDER BY id DESC
              LIMIT ' . $limite
        );
        $stmt->execute([$clienteId]);

        return $stmt->fetchAll();
    }

    /**
     * Genera el correo de pago confirmado para un pedido ya pagado.
     * Devuelve el id de la notificación, o null si no corresponde.
     */
    public static function pagoConfirmado(int $pedidoId): ?int
    {
        $pedido = self::pedido($pedidoId);
        if ($pedido === null || (string) ($pedido['pago_estado'] ?? '') !== 'Pagado') {
            return null;
        }
        if (self::yaRegistrada($pedidoId, self::PAGO_CONFIRMADO)) {
            return null;
        }

        $codigo = self::codigo($pedidoId);
        $detalle = self::detallePedido($pedidoId);
        $asunto = 'Pago confirmado — Pedido ' . $codigo;

        return self::registrar(
            $pedido,
            self::PAGO_CONFIRMADO,
            $asunto,
            self::html($pedido, $detalle, self::PAGO_CONFIRMADO),
            self::texto($pedido, $detalle, self::PAGO_CONFIRMADO)
        );
    }

    /**
     * Genera el correo de pedido enviado o listo para recojo.
     * Devuelve el id de la notificación, o null si no corresponde.
     */
    public static function pedidoEnviado(int $pedidoId): ?int
    {
        $pedido = self::pedido($pedidoId);
        if ($pedido === null
            || !in_array((string) ($pedido['estado'] ?? ''), ['Enviado', 'Entregado'], true)
        ) {
            return null;
        }
        if (self::yaRegistrada($pedidoId, self::PEDIDO_ENVIADO)) {
            return null;
        }

        $codigo = self::codigo($pedidoId);
        $detalle = self::detallePedido($pedidoId);
        $recojo = self::esRecojo($pedido);
        $asunto = ($recojo ? 'Pedido listo para recojo' : 'Pedido enviado') . ' — ' . $codigo;

        return self::registrar(
            $pedido,
            self::PEDIDO_ENVIADO,
            $asunto,
            self::html($pedido, $detalle, self::PEDIDO_ENVIADO),
            self::texto($pedido, $detalle, self::PEDIDO_ENVIADO)
        );
    }

    /**
     * Genera el correo de reembolso de un pedido ya reembolsado.
     *
     * Solo se registra si el último pago quedó en estado `Reembolsado`, que
     * es lo que deja el flujo de `admin/pedidos/reembolsar`. La deduplicación
     * por `(pedido_id, tipo)` impide dos avisos si se repite el POST.
     * Devuelve el id de la notificación, o null si no corresponde.
     */
    public static function reembolso(int $pedidoId): ?int
    {
        $pedido = self::pedido($pedidoId);
        if ($pedido === null || (string) ($pedido['pago_estado'] ?? '') !== 'Reembolsado') {
            return null;
        }
        if (self::yaRegistrada($pedidoId, self::REEMBOLSO)) {
            return null;
        }

        $codigo = self::codigo($pedidoId);
        $detalle = self::detallePedido($pedidoId);
        $asunto = 'Reembolso procesado — Pedido ' . $codigo;

        return self::registrar(
            $pedido,
            self::REEMBOLSO,
            $asunto,
            self::html($pedido, $detalle, self::REEMBOLSO),
            self::texto($pedido, $detalle, self::REEMBOLSO)
        );
    }

    /**
     * Cola completa de notificaciones para el visor del panel (solo lectura).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function cola(
        string $buscar = '',
        string $tipo = '',
        string $estado = '',
        int $limite = 300
    ): array {
        $sql = 'SELECT n.*, u.name AS cliente_nombre, u.apellidos AS cliente_apellidos
                  FROM notificaciones n
             LEFT JOIN users u ON u.id = n.cliente_id
                 WHERE 1 = 1';
        $params = [];

        $buscar = trim($buscar);
        if ($buscar !== '') {
            $sql .= ' AND (n.destinatario LIKE ? OR n.asunto LIKE ? OR n.cuerpo LIKE ?
                           OR CAST(n.pedido_id AS CHAR) LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($tipo !== '') {
            $sql .= ' AND n.tipo = ?';
            $params[] = $tipo;
        }
        if ($estado === 'pendiente') {
            $sql .= " AND n.enviado = 0 AND n.error = ''";
        } elseif ($estado === 'enviado') {
            $sql .= ' AND n.enviado = 1';
        } elseif ($estado === 'error') {
            $sql .= " AND n.error <> ''";
        }

        $sql .= ' ORDER BY n.id DESC LIMIT ' . max(1, min(500, $limite));
        $stmt = conexion()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Tipos presentes en la cola, para el filtro del visor.
     *
     * @return array<int, string>
     */
    public static function tiposCola(): array
    {
        return conexion()
            ->query('SELECT DISTINCT tipo FROM notificaciones ORDER BY tipo')
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Contadores globales de la cola (independientes del filtro activo).
     *
     * @return array{total:int, pendientes:int, enviadas:int, errores:int}
     */
    public static function resumenCola(): array
    {
        $fila = conexion()->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN enviado = 0 AND error = '' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN enviado = 1 THEN 1 ELSE 0 END) AS enviadas,
                    SUM(CASE WHEN error <> '' THEN 1 ELSE 0 END) AS errores
               FROM notificaciones"
        )->fetch();

        return [
            'total'      => (int) ($fila['total'] ?? 0),
            'pendientes' => (int) ($fila['pendientes'] ?? 0),
            'enviadas'   => (int) ($fila['enviadas'] ?? 0),
            'errores'    => (int) ($fila['errores'] ?? 0),
        ];
    }

    /** @return array<string, mixed>|null */
    private static function pedido(int $pedidoId): ?array
    {
        if ($pedidoId <= 0) {
            return null;
        }

        $stmt = conexion()->prepare(
            'SELECT p.*,
                    u.name AS cliente_nombre,
                    u.apellidos AS cliente_apellidos,
                    u.email AS cliente_email,
                    u.telefono AS cliente_telefono,
                    pa.metodo AS pago_metodo,
                    pa.estado AS pago_estado,
                    pa.transaccion_id AS pago_transaccion,
                    pa.creado_en AS pago_creado_en
               FROM pedidos p
               JOIN users u ON u.id = p.cliente_id
          LEFT JOIN pagos pa ON pa.pedido_id = p.id
              WHERE p.id = ?
           ORDER BY pa.id DESC
              LIMIT 1'
        );
        $stmt->execute([$pedidoId]);
        $fila = $stmt->fetch();

        return $fila ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    private static function detallePedido(int $pedidoId): array
    {
        $stmt = conexion()->prepare(
            'SELECT producto_nombre, variante_nombre, cantidad, precio_unitario_pen
               FROM detalle_pedido
              WHERE pedido_id = ?
              ORDER BY id'
        );
        $stmt->execute([$pedidoId]);

        return $stmt->fetchAll();
    }

    private static function yaRegistrada(int $pedidoId, string $tipo): bool
    {
        $stmt = conexion()->prepare(
            'SELECT COUNT(*) FROM notificaciones WHERE pedido_id = ? AND tipo = ?'
        );
        $stmt->execute([$pedidoId, $tipo]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array<string, mixed> $pedido
     */
    private static function registrar(
        array $pedido,
        string $tipo,
        string $asunto,
        string $html,
        string $texto
    ): int {
        $archivo = '';
        $error = '';

        try {
            $archivo = CorreoSimulado::guardar(
                $tipo,
                (int) $pedido['id'],
                (string) ($pedido['cliente_email'] ?? ''),
                $asunto,
                $html,
                $texto
            );
        } catch (\Throwable $e) {
            $error = mb_substr($e->getMessage(), 0, 255);
            error_log('Correo simulado pedido #' . (int) $pedido['id'] . ': ' . $error);
        }

        $stmt = conexion()->prepare(
            'INSERT INTO notificaciones
                (cliente_id, pedido_id, destinatario, tipo, asunto, cuerpo, archivo, error, enviado, enviado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NULL)'
        );
        $stmt->execute([
            (int) $pedido['cliente_id'],
            (int) $pedido['id'],
            mb_substr((string) ($pedido['cliente_email'] ?? ''), 0, 190),
            $tipo,
            mb_substr($asunto, 0, 180),
            mb_substr($texto, 0, 500),
            $archivo,
            $error,
        ]);

        return (int) conexion()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $pedido
     * @param array<int, array<string, mixed>> $detalle
     */
    private static function html(array $pedido, array $detalle, string $tipo): string
    {
        $e = static fn ($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
        $recojo = self::esRecojo($pedido);
        $codigo = self::codigo((int) $pedido['id']);
        $nombre = trim((string) ($pedido['cliente_nombre'] ?? '') . ' ' . (string) ($pedido['cliente_apellidos'] ?? ''));
        $metodo = self::metodoEtiqueta((string) ($pedido['pago_metodo'] ?? ''));
        $total = number_format((float) ($pedido['total_pen'] ?? 0), 2);

        $items = '';
        foreach ($detalle as $item) {
            $variante = trim((string) ($item['variante_nombre'] ?? ''));
            $items .= '<tr>'
                . '<td style="padding:8px 0;border-bottom:1px solid #e5e7eb;color:#111827;">'
                . $e((string) ($item['producto_nombre'] ?? 'Producto'))
                . ($variante !== '' ? ' <span style="color:#6b7280;font-size:12px;">(' . $e($variante) . ')</span>' : '')
                . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:center;color:#4b5563;">×' . (int) ($item['cantidad'] ?? 0) . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;color:#111827;">S/ '
                . $e(number_format((float) ($item['precio_unitario_pen'] ?? 0), 2)) . '</td>'
                . '</tr>';
        }
        if ($items === '') {
            $items = '<tr><td colspan="3" style="padding:8px 0;color:#6b7280;">Detalle no disponible.</td></tr>';
        }

        if ($tipo === self::PAGO_CONFIRMADO) {
            $titulo = 'Pago confirmado';
            $intro = 'Recibimos el pago de tu pedido. Ya estamos preparándolo.';
            $bloque = '<tr><td style="padding:8px 0;color:#6b7280;">Método de pago</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                . $e($metodo !== '' ? $metodo : 'No registrado') . '</td></tr>'
                . '<tr><td style="padding:8px 0;color:#6b7280;">Estado</td><td style="padding:8px 0;text-align:right;color:#15803d;font-weight:600;">Pagado</td></tr>';
            if ((string) ($pedido['pago_transaccion'] ?? '') !== '') {
                $bloque .= '<tr><td style="padding:8px 0;color:#6b7280;">Referencia</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                    . $e((string) $pedido['pago_transaccion']) . '</td></tr>';
            }
        } elseif ($tipo === self::REEMBOLSO) {
            $titulo = 'Reembolso procesado';
            $intro = 'Procesamos el reembolso de tu pedido. El monto se verá reflejado según el medio de pago utilizado.';
            $bloque = '<tr><td style="padding:8px 0;color:#6b7280;">Método de pago</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                . $e($metodo !== '' ? $metodo : 'No registrado') . '</td></tr>'
                . '<tr><td style="padding:8px 0;color:#6b7280;">Estado del pago</td><td style="padding:8px 0;text-align:right;color:#b45309;font-weight:600;">Reembolsado</td></tr>';
            if ((string) ($pedido['pago_transaccion'] ?? '') !== '') {
                $bloque .= '<tr><td style="padding:8px 0;color:#6b7280;">Referencia</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                    . $e((string) $pedido['pago_transaccion']) . '</td></tr>';
            }
        } else {
            if ($recojo) {
                $titulo = 'Pedido listo para recojo';
                $intro = 'Tu pedido ya está listo. Puedes recogerlo en nuestra tienda.';
                $bloque = '<tr><td style="padding:8px 0;color:#6b7280;">Lugar</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                    . $e(self::direccionTienda()) . '</td></tr>'
                    . '<tr><td style="padding:8px 0;color:#6b7280;">Teléfono</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                    . $e(self::telefonoTienda()) . '</td></tr>';
            } else {
                $titulo = 'Pedido enviado';
                $intro = 'Tu pedido salió de nuestro almacén y va en camino.';
                $courier = trim((string) ($pedido['courier'] ?? ''));
                $guia = trim((string) ($pedido['guia_numero'] ?? ''));
                $bloque = '<tr><td style="padding:8px 0;color:#6b7280;">Courier</td><td style="padding:8px 0;text-align:right;color:#111827;">'
                    . $e($courier !== '' ? $courier : 'Por confirmar') . '</td></tr>'
                    . '<tr><td style="padding:8px 0;color:#6b7280;">Número de guía</td><td style="padding:8px 0;text-align:right;color:#111827;font-weight:600;">'
                    . $e($guia !== '' ? $guia : 'Por confirmar') . '</td></tr>';
            }
        }

        $entrega = (string) ($pedido['direccion_entrega'] ?? '');
        if ($entrega === '') {
            $entrega = $recojo ? Envio::ZONA_RECOJO : 'Por confirmar';
        }

        $link = APP_URL . '/index.php?ruta=carrito#historial';

        return '<!doctype html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $e($titulo) . ' — ' . $e($codigo) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">'
            . '<tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<tr><td style="background:#0a0f1a;padding:22px 26px;color:#ffffff;">'
            . '<div style="font-size:20px;font-weight:700;letter-spacing:1px;color:#00d4ff;">JEREMY-TECH</div>'
            . '<div style="font-size:13px;color:#9ca3af;margin-top:4px;">Tecnología y accesorios</div>'
            . '</td></tr>'
            . '<tr><td style="padding:26px;">'
            . '<h1 style="margin:0 0 8px;font-size:24px;color:#111827;">' . $e($titulo) . '</h1>'
            . '<p style="margin:0 0 18px;color:#4b5563;line-height:1.6;">Hola ' . $e($nombre !== '' ? $nombre : 'cliente') . '. ' . $e($intro) . '</p>'
            . '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:18px;">'
            . '<div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;">Pedido</div>'
            . '<div style="font-size:20px;font-weight:700;color:#111827;margin-top:3px;">' . $e($codigo) . '</div>'
            . '<div style="font-size:13px;color:#6b7280;margin-top:4px;">' . $e(self::fecha((string) ($pedido['creado_en'] ?? ''))) . '</div>'
            . '</div>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:18px;">'
            . $bloque
            . '<tr><td style="padding:8px 0;color:#6b7280;">Entrega</td><td style="padding:8px 0;text-align:right;color:#111827;">' . $e($entrega) . '</td></tr>'
            . '<tr><td style="padding:8px 0;color:#6b7280;">Total</td><td style="padding:8px 0;text-align:right;color:#111827;font-weight:700;">S/ ' . $e($total) . '</td></tr>'
            . '</table>'
            . '<h2 style="font-size:15px;color:#111827;margin:0 0 8px;">Productos</h2>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:22px;">' . $items . '</table>'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto;"><tr><td style="background:#00d4ff;border-radius:8px;">'
            . '<a href="' . $e($link) . '" style="display:inline-block;padding:12px 22px;color:#0a0f1a;text-decoration:none;font-weight:700;font-size:14px;">Ver seguimiento</a>'
            . '</td></tr></table>'
            . '<p style="margin:22px 0 0;color:#6b7280;font-size:12px;line-height:1.6;">'
            . 'Este mensaje fue generado en modo de demostración. No se envió por correo y no contiene datos de tarjetas.'
            . '</p>'
            . '</td></tr>'
            . '<tr><td style="background:#f9fafb;padding:16px 26px;color:#6b7280;font-size:12px;text-align:center;">'
            . 'Jeremy-Tech · ' . $e(self::direccionTienda()) . ' · ' . $e(self::telefonoTienda())
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /**
     * @param array<string, mixed> $pedido
     * @param array<int, array<string, mixed>> $detalle
     */
    private static function texto(array $pedido, array $detalle, string $tipo): string
    {
        $codigo = self::codigo((int) $pedido['id']);
        $nombre = trim((string) ($pedido['cliente_nombre'] ?? '') . ' ' . (string) ($pedido['cliente_apellidos'] ?? ''));
        $metodo = self::metodoEtiqueta((string) ($pedido['pago_metodo'] ?? ''));
        $recojo = self::esRecojo($pedido);

        $titulo = match ($tipo) {
            self::PAGO_CONFIRMADO => 'Pago confirmado',
            self::REEMBOLSO      => 'Reembolso procesado',
            default              => $recojo ? 'Pedido listo para recojo' : 'Pedido enviado',
        };

        $lineas = [
            'JEREMY-TECH',
            $titulo . ' — ' . $codigo,
            '',
            'Hola ' . ($nombre !== '' ? $nombre : 'cliente') . '.',
        ];

        if ($tipo === self::PAGO_CONFIRMADO) {
            $lineas[] = 'Recibimos el pago de tu pedido. Ya estamos preparándolo.';
            $lineas[] = 'Método de pago: ' . ($metodo !== '' ? $metodo : 'No registrado');
            $lineas[] = 'Estado: Pagado';
            if ((string) ($pedido['pago_transaccion'] ?? '') !== '') {
                $lineas[] = 'Referencia: ' . (string) $pedido['pago_transaccion'];
            }
        } elseif ($tipo === self::REEMBOLSO) {
            $lineas[] = 'Procesamos el reembolso de tu pedido. El monto se verá reflejado según el medio de pago utilizado.';
            $lineas[] = 'Método de pago: ' . ($metodo !== '' ? $metodo : 'No registrado');
            $lineas[] = 'Estado del pago: Reembolsado';
            if ((string) ($pedido['pago_transaccion'] ?? '') !== '') {
                $lineas[] = 'Referencia: ' . (string) $pedido['pago_transaccion'];
            }
        } elseif ($recojo) {
            $lineas[] = 'Tu pedido ya está listo para recoger en tienda.';
            $lineas[] = 'Lugar: ' . self::direccionTienda();
            $lineas[] = 'Teléfono: ' . self::telefonoTienda();
        } else {
            $lineas[] = 'Tu pedido salió de nuestro almacén y va en camino.';
            $lineas[] = 'Courier: ' . ((string) ($pedido['courier'] ?? '') !== '' ? (string) $pedido['courier'] : 'Por confirmar');
            $lineas[] = 'Número de guía: ' . ((string) ($pedido['guia_numero'] ?? '') !== '' ? (string) $pedido['guia_numero'] : 'Por confirmar');
        }

        $lineas[] = '';
        $lineas[] = 'Productos:';
        foreach ($detalle as $item) {
            $lineas[] = '- ' . (int) ($item['cantidad'] ?? 0) . 'x ' . (string) ($item['producto_nombre'] ?? 'Producto')
                . ((string) ($item['variante_nombre'] ?? '') !== '' ? ' (' . (string) $item['variante_nombre'] . ')' : '');
        }
        $lineas[] = '';
        $lineas[] = 'Total: S/ ' . number_format((float) ($pedido['total_pen'] ?? 0), 2);
        $lineas[] = 'Entrega: ' . ((string) ($pedido['direccion_entrega'] ?? '') !== ''
            ? (string) $pedido['direccion_entrega']
            : ($recojo ? Envio::ZONA_RECOJO : 'Por confirmar'));
        $lineas[] = '';
        $lineas[] = 'Ver seguimiento: ' . APP_URL . '/index.php?ruta=carrito#historial';
        $lineas[] = '';
        $lineas[] = 'Este mensaje fue generado en modo de demostración. No se envió por correo.';

        return implode("\n", $lineas);
    }

    /** @param array<string, mixed> $pedido */
    private static function esRecojo(array $pedido): bool
    {
        return (string) ($pedido['envio_zona'] ?? '') === Envio::ZONA_RECOJO;
    }

    private static function codigo(int $pedidoId): string
    {
        return '#JT-' . str_pad((string) $pedidoId, 5, '0', STR_PAD_LEFT);
    }

    private static function metodoEtiqueta(string $metodo): string
    {
        $metodo = trim($metodo);

        return match (strtolower($metodo)) {
            'tarjeta'       => 'Tarjeta',
            'yape'          => 'Yape',
            'transferencia' => 'Transferencia',
            'contraentrega' => 'Pago contra entrega',
            'efectivo'      => 'Efectivo',
            default         => $metodo !== '' ? ucfirst($metodo) : '',
        };
    }

    private static function fecha(string $fecha): string
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return '';
        }
        $ts = strtotime($fecha);
        return $ts === false ? $fecha : date('d/m/Y H:i', $ts);
    }

    private static function direccionTienda(): string
    {
        return self::configuracion('direccion', 'Av. Paz Soldán, Cercado, Arequipa');
    }

    private static function telefonoTienda(): string
    {
        return self::configuracion('telefono', '997833866');
    }

    private static function configuracion(string $clave, string $porDefecto = ''): string
    {
        static $cache = [];

        if (!array_key_exists($clave, $cache)) {
            $stmt = conexion()->prepare('SELECT valor FROM configuracion WHERE clave = ? LIMIT 1');
            $stmt->execute([$clave]);
            $valor = $stmt->fetchColumn();
            $cache[$clave] = $valor === false ? '' : trim((string) $valor);
        }

        return $cache[$clave] !== '' ? $cache[$clave] : $porDefecto;
    }
}
