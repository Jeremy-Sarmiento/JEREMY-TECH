<?php
/**
 * VISTA: Reportes de ventas del panel de vendedor.
 *
 * Datos de entrada (VendedorController::reportes()):
 *   $vendedor, $ganancias, $mensual, $totales, $masVendidos,
 *   $mejoresCompradores, $meses, $flash.
 */
require __DIR__ . '/_comun.php';

$mesesNombre = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$etiquetaMes = static function (string $ym) use ($mesesNombre): string {
    [$anio, $mes] = explode('-', $ym);
    return ($mesesNombre[(int) $mes] ?? $mes) . ' ' . $anio;
};
$urlPeriodo = static fn (int $n): string => '?ruta=mis-productos/reportes&meses=' . $n;
?>
<main class="cart-page cuenta-page mis-productos-page">
 <div class="cart-page-header">
  <h1><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-pedidos"/></svg>Reportes de ventas</h1>
  <p>Evolución mensual, productos más vendidos y mejores compradores. Solo pedidos cobrados.</p>
 </div>

 <?php require BASE_PATH . '/views/partials/cuenta_nav.php'; ?>

 <p><a class="btn-mini" href="?ruta=mis-productos">← Volver a mis publicaciones</a></p>

 <?php if (($flash['mensaje'] ?? '') !== ''): ?>
 <div class="auth-flash auth-flash-<?= ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
  <?= $e($flash['mensaje']) ?>
 </div>
 <?php endif; ?>

 <nav class="pv-filtros" aria-label="Periodo del reporte">
  <?php foreach ([3, 6, 12] as $n): ?>
  <a class="pv-filtro<?= $meses === $n ? ' pv-filtro-activo' : '' ?>"
     href="<?= $e($urlPeriodo($n)) ?>"
     <?= $meses === $n ? 'aria-current="page"' : '' ?>><?= $n ?> meses</a>
  <?php endforeach; ?>
  <a class="pv-filtro" href="?ruta=mis-productos/reportes.csv">Descargar CSV</a>
 </nav>

 <section class="pv-panel" aria-labelledby="pv-reporte-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-reporte-titulo">Resumen del periodo</h2>
    <p class="pv-panel-nota">Últimos <?= (int) $meses ?> meses. Importes sin IGV.</p>
   </div>
  </div>
  <div class="pv-ganancias">
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= (int) $totales['pedidos'] ?></span>
    <span class="pv-ganancia-etiqueta">Pedidos</span>
   </div>
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= (int) $totales['unidades'] ?></span>
    <span class="pv-ganancia-etiqueta">Unidades vendidas</span>
   </div>
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $fmt((float) $totales['vendido']) ?></span>
    <span class="pv-ganancia-etiqueta">Vendido</span>
   </div>
   <div class="pv-ganancia pv-ganancia-neto">
    <span class="pv-ganancia-valor"><?= $fmt((float) $totales['neto']) ?></span>
    <span class="pv-ganancia-etiqueta">Neto</span>
   </div>
   <div class="pv-ganancia pv-ganancia-saldo">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['saldo'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Saldo pendiente total</span>
   </div>
  </div>
 </section>

 <section class="pv-panel" aria-labelledby="pv-mensual-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-mensual-titulo">Mes a mes</h2>
    <p class="pv-panel-nota">Los meses sin ventas aparecen en cero para comparar la evolución.</p>
   </div>
  </div>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Mes</th>
      <th>Pedidos</th>
      <th>Unidades</th>
      <th>Vendido</th>
      <th>Comisión</th>
      <th>Neto</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($mensual as $fila): ?>
     <tr>
      <td><strong><?= $e($etiquetaMes($fila['mes'])) ?></strong></td>
      <td><?= (int) $fila['pedidos'] ?></td>
      <td><?= (int) $fila['unidades'] ?></td>
      <td><?= $fmt((float) $fila['vendido']) ?></td>
      <td><?= $fmt((float) $fila['comision']) ?></td>
      <td><?= $fmt((float) $fila['neto']) ?></td>
     </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
 </section>

 <section class="pv-panel" aria-labelledby="pv-top-productos-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-top-productos-titulo">Productos más vendidos</h2>
    <p class="pv-panel-nota">Por ingresos de todo el histórico, no solo del periodo.</p>
   </div>
  </div>
  <?php if ($masVendidos === []): ?>
  <p class="pv-vacio-mini">Todavía no tienes ventas cobradas.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Producto</th>
      <th>Unidades</th>
      <th>Ingresos</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($masVendidos as $p): ?>
     <tr>
      <td><strong><?= $e($p['nombre']) ?></strong></td>
      <td><?= (int) $p['unidades'] ?></td>
      <td><?= $fmt((float) $p['ingresos']) ?></td>
     </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>
 </section>

 <section class="pv-panel" aria-labelledby="pv-top-compradores-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-top-compradores-titulo">Mejores compradores</h2>
    <p class="pv-panel-nota">Quiénes más gastaron en tus publicaciones.</p>
   </div>
  </div>
  <?php if ($mejoresCompradores === []): ?>
  <p class="pv-vacio-mini">Todavía no tienes compradores con pedidos cobrados.</p>
  <?php else: ?>
  <div class="pv-tabla-wrap">
   <table class="pv-tabla">
    <thead>
     <tr>
      <th>Comprador</th>
      <th>Pedidos</th>
      <th>Total</th>
     </tr>
    </thead>
    <tbody>
     <?php foreach ($mejoresCompradores as $c): ?>
     <tr>
      <td><strong><?= $e($c['nombre']) ?></strong></td>
      <td><?= (int) $c['pedidos'] ?></td>
      <td><?= $fmt((float) $c['total']) ?></td>
     </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
  <?php endif; ?>
 </section>

</main>
