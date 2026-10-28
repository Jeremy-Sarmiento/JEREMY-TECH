<?php
$pdo = conexion();

$claves = ['nombre_tienda', 'telefono', 'email', 'direccion', 'igv_porcentaje',
           'comision_marketplace_porcentaje'];
$config = [];
foreach ($pdo->query('SELECT clave, valor FROM configuracion')->fetchAll() as $f) {
    $config[$f['clave']] = $f['valor'];
}
$v = static fn($k) => htmlspecialchars((string) ($config[$k] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Configuración de la tienda</h2>
            <p class="admin-nota">Datos generales, contacto, tasa de IGV y comisión del marketplace.</p>
        </div>
    </div>

    <form method="post" action="?ruta=admin/configuracion" class="admin-formulario admin-formulario-ancho">
        <?= \Security\Proteccion::campo() ?>
        <label>Nombre de la tienda
            <input type="text" name="nombre_tienda" value="<?= $v('nombre_tienda') ?>">
        </label>
        <label>Teléfono
            <input type="text" name="telefono" value="<?= $v('telefono') ?>">
        </label>
        <label>Email de contacto
            <input type="email" name="email" value="<?= $v('email') ?>">
        </label>
        <label>Dirección
            <input type="text" name="direccion" value="<?= $v('direccion') ?>">
        </label>
        <label>IGV (%)
            <input type="number" step="0.01" min="0" name="igv_porcentaje" value="<?= $v('igv_porcentaje') ?>">
        </label>
        <label>Comisión del marketplace (%)
            <input type="number" step="0.01" min="0" max="100" name="comision_marketplace_porcentaje"
                   value="<?= $v('comision_marketplace_porcentaje') ?>">
            <small class="admin-nota">
                Respaldo: se usa solo cuando un vendedor no tiene porcentaje propio.
                Se calcula sobre la base sin IGV y se congela al vender.
            </small>
        </label>
        <button type="submit" class="btn btn-primario">Guardar configuración</button>
    </form>
</section>
