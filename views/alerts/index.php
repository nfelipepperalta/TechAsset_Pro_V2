<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($alerts)): ?>
            <div class="empty-state p-5">
                <i class="fa-solid fa-bell-slash fa-3x text-success mb-3 d-block"></i>
                <h3>Sin alertas pendientes</h3>
                <p class="text-muted">El sistema no tiene alertas activas en este momento.</p>
            </div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($alerts as $alert): ?>
                <?php
                    $iconMap = [
                        'garantia_vence'        => ['fa-shield-halved', 'danger'],
                        'mantenimiento_proximo' => ['fa-screwdriver-wrench', 'warning'],
                        'sin_revision'          => ['fa-eye-slash', 'secondary'],
                        'contrato_vence'        => ['fa-file-contract', 'info'],
                        'baja_pendiente'        => ['fa-ban', 'dark'],
                        'custom'                => ['fa-bell', 'primary'],
                    ];
                    [$icon, $color] = $iconMap[$alert['tipo']] ?? ['fa-bell', 'primary'];
                ?>
                <li class="list-group-item d-flex align-items-center gap-3 py-3">
                    <div class="alert-icon text-<?= $color ?>">
                        <i class="fa-solid <?= $icon ?> fa-lg"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-0 fw-medium"><?= htmlspecialchars($alert['mensaje']) ?></p>
                        <small class="text-muted">
                            <?= htmlspecialchars($alert['activo_nombre'] ?? '') ?>
                            &nbsp;·&nbsp; <?= $alert['fecha_disparo'] ?>
                        </small>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="<?= APP_URL ?>/assets/<?= $alert['asset_id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver activo">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <form method="POST" action="<?= APP_URL ?>/alerts/<?= $alert['id'] ?>/resolve">
                            <button class="btn btn-sm btn-outline-success" title="Marcar resuelta">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
