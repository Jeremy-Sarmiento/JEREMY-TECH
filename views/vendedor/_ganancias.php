<?php
/**
 * Parcial del panel de vendedor: economía del vendedor (comisión, neto, saldo).
 * Requiere `views/vendedor/_comun.php` y los datos del controlador.
 */
?>
 <section class="pv-panel" id="mis-ganancias" aria-labelledby="pv-ganancias-titulo">
  <div class="pv-panel-cabecera">
   <div>
    <h2 class="pv-panel-titulo" id="pv-ganancias-titulo"><svg class="inline-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><use href="img/icons.svg#icon-cash"/></svg>Mis ganancias</h2>
    <p class="pv-panel-nota">
     Solo cuenta pedidos cobrados: un pedido pendiente o cancelado todavía no genera
     ganancia. Los importes son la base sin IGV congelada al momento de la compra.
    </p>
   </div>
   <a class="btn-mini" href="?ruta=mis-productos/reportes">Ver reportes</a>
  </div>
  <div class="pv-ganancias">
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['vendido'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Vendido (sin IGV)</span>
   </div>
   <div class="pv-ganancia pv-ganancia-comision">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['comision'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Comisión de la plataforma</span>
   </div>
   <div class="pv-ganancia pv-ganancia-neto">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['neto'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Neto a recibir</span>
   </div>
   <div class="pv-ganancia">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['liquidado'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Ya liquidado</span>
   </div>
   <div class="pv-ganancia pv-ganancia-saldo">
    <span class="pv-ganancia-valor"><?= $fmt((float) ($ganancias['saldo'] ?? 0)) ?></span>
    <span class="pv-ganancia-etiqueta">Saldo pendiente</span>
   </div>
  </div>
 </section>
