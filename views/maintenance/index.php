<?php
$upcoming = $upcoming ?? [];
?>

<!-- Cabecera -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Mantenimientos</h1>
    <?php if (\Core\Auth::canEdit()): ?>
    <a href="<?= APP_URL ?>/maintenance/create" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i>Registrar mantenimiento
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Próximos mantenimientos -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">
            <i class="fa-solid fa-calendar-check me-2"></i>Próximos 30 días
        </h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($upcoming)): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-calendar-xmark fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted">No hay mantenimientos programados para los próximos 30 días.</p>
            <?php if (\Core\Auth::canEdit()): ?>
            <a href="<?= APP_URL ?>/maintenance/create" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Programar mantenimiento
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Activo</th>
                        <th>Tipo</th>
                        <th>Fecha programada</th>
                        <th>Técnico</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming as $m): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/assets/<?= $m['asset_id'] ?>">
                                <?= htmlspecialchars($m['asset_nombre'] ?? '—') ?>
                            </a>
                            <div class="small text-muted"><?= htmlspecialchars($m['asset_serial'] ?? '') ?></div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $m['tipo'] === 'preventivo' ? 'info' : 'warning' ?>">
                                <?= htmlspecialchars($m['tipo'] ?? '—') ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <?php
                            $fecha = $m['fecha_programada'] ?? '';
                            $dias  = $fecha ? (int)((strtotime($fecha) - time()) / 86400) : null;
                            ?>
                            <?= htmlspecialchars($fecha) ?>
                            <?php if ($dias !== null): ?>
                            <div class="small <?= $dias <= 7 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= $dias === 0 ? 'Hoy' : ($dias < 0 ? abs($dias) . ' días vencido' : 'En ' . $dias . ' días') ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['tecnico'] ?? '—') ?></td>
                        <td>
                            <?php
                            $estados = [
                                'pendiente'  => 'secondary',
                                'en_proceso' => 'warning',
                                'completado' => 'success',
                                'cancelado'  => 'danger',
                            ];
                            $badge = $estados[$m['estado'] ?? ''] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badge ?>">
                                <?= htmlspecialchars($m['estado'] ?? '—') ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/maintenance/<?= $m['id'] ?>/edit"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" action="<?= APP_URL ?>/maintenance/<?= $m['id'] ?>/delete"
                                  class="d-inline" onsubmit="return confirm('¿Eliminar este mantenimiento?')">
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
