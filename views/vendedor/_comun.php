<?php
/**
 * Helpers compartidos por los tres paneles de vendedor (B2C/B2B/C2C).
 *
 * Datos de entrada (VendedorController::productos()):
 *   $productos, $resumen, $busqueda, $flash, $vendedor, $verArchivados,
 *   $ganancias, $compradores, $movimientos, $reputacion, $calificaciones.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $monto): string => 'S/ ' . number_format($monto, 2);
$imagenPredeterminada = 'img/producto-sin-imagen.svg';
$vendedorId = (int) ($vendedor['id'] ?? 0);
$fecha = static function (?string $valor): string {
    return $valor !== null && $valor !== '' ? date('d/m/Y', strtotime($valor)) : '—';
};
$fechaHora = static function (?string $valor): string {
    return $valor !== null && $valor !== '' ? date('d/m/Y H:i', strtotime($valor)) : '—';
};
$claseEstado = static function (string $estado): string {
    return match ($estado) {
        'Pagado', 'Entregado' => 'pv-estado-ok',
        'Enviado' => 'pv-estado-info',
        'Pendiente', 'En verificación' => 'pv-estado-pendiente',
        'Cancelado', 'Reembolsado' => 'pv-estado-off',
        default => '',
    };
};
$tipoPanel = strtoupper((string) ($vendedor['tipo'] ?? 'B2C'));
if (!in_array($tipoPanel, ['B2C', 'B2B', 'C2C'], true)) {
    $tipoPanel = 'B2C';
}
