<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Mis Activos Asignados</h1>
        <p class="text-muted">A continuación se enlistan los dispositivos tecnológicos y activos de red bajo tu responsabilidad.</p>
    </div>

    <div class="row g-4">
        <?php if (empty($assets)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fa-solid fa-laptop-code display-4 mb-3 d-block text-secondary"></i>
                No tienes ningún activo asignado a tu nombre en el inventario actual de la empresa.
            </div>
        <?php else: ?>
            <?php foreach ($assets as $asset): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($asset['tipo_nombre'] ?? 'Activo') ?></span>
                            <span class="badge bg-success"><?= htmlspecialchars($asset['status']) ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($asset['name']) ?></h5>
                            <p class="card-text mb-1 text-muted">
                                <i class="fa-solid fa-barcode me-2"></i><strong>S/N:</strong> <?= htmlspecialchars($asset['serial_number']) ?>
                            </p>
                            <p class="card-text text-muted small">
                                <i class="fa-solid fa-calendar-day me-2"></i>Asignado el: <?= date('d/m/Y', strtotime($asset['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
