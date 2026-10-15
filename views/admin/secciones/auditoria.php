<?php
$pdo = conexion();
$registros = $pdo->query('SELECT * FROM auditoria_admin ORDER BY id DESC LIMIT 250')->fetchAll();
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="admin-seccion">
    <div class="admin-encabezado">
        <div>
            <h2 class="admin-titulo">Auditoría</h2>
            <p class="admin-nota">Últimos 250 eventos registrados por los administradores.</p>
        </div>
    </div>

    <div class="admin-tabla-wrap">
        <table class="tabla-admin">
            <thead><tr><th>ID</th><th>Fecha</th><th>Admin</th><th>Acción</th><th>Ref.</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= (int) $r['id'] ?></td>
                    <td><?= $e($r['creado_en'] ?? '') ?></td>
                    <td><?= $e($r['admin_usuario'] ?? '') ?></td>
                    <td><code><?= $e($r['accion'] ?? '') ?></code></td>
                    <td><?= (int) ($r['referencia_id'] ?? 0) ?></td>
                    <td><?= $e($r['detalle'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$registros): ?>
                <tr><td colspan="6" class="admin-vacio">Aún no hay eventos de auditoría.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
