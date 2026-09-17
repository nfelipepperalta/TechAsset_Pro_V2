<?php
use Helpers\DateHelper;
/** @var array $data */
?>

<!-- Métricas principales -->
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-primary">
                <i class="fa-solid fa-server"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value"><?= number_format($data['total_activos']) ?></span>
                <span class="metric-label">Total activos</span>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value"><?= number_format($data['activos_activos']) ?></span>
                <span class="metric-label">En operación</span>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-warning">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value"><?= number_format($data['en_mantenimiento']) ?></span>
                <span class="metric-label">En mantenimiento</span>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-info">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value">$<?= number_format($data['valor_total'], 0, ',', '.') ?></span>
                <span class="metric-label">Valor total</span>
            </div>
        </div>
    </div>

</div>

<!-- Segunda fila: gráfica + alertas -->
<div class="row g-3 mb-4">

    <!-- Gráfica distribución por tipo -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-chart-pie me-2"></i>Distribución por tipo
                </h2>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="max-height:280px; width:100%;">
                    <canvas id="chartTipos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas pendientes -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">
                    <i class="fa-solid fa-bell me-2"></i>Alertas pendientes
                </h2>
                <?php if ($data['alertas_pendientes'] > 0): ?>
                    <span class="badge bg-danger"><?= $data['alertas_pendientes'] ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($data['alertas'])): ?>
                    <div class="empty-state p-4">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        <p>Sin alertas pendientes</p>
                    </div>
                <?php else: ?>
                    <ul class="alert-list">
                        <?php foreach (array_slice($data['alertas'], 0, 5) as $alert): ?>
                        <li class="alert-item alert-<?= $alert['tipo'] ?>">
                            <div class="alert-dot"></div>
                            <div class="alert-content">
                                <p class="alert-msg"><?= htmlspecialchars($alert['mensaje']) ?></p>
                                <span class="alert-asset"><?= htmlspecialchars($alert['activo_nombre']) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($data['alertas_pendientes'] > 5): ?>
                        <div class="p-3 text-center">
                            <a href="<?= APP_URL ?>/alerts" class="btn btn-sm btn-outline-primary">
                                Ver todas (<?= $data['alertas_pendientes'] ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Tercera fila: garantías + mantenimientos -->
<div class="row g-3">

    <!-- Garantías por vencer -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-shield-halved me-2"></i>Garantías por vencer
                </h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($data['garantias_proximas'])): ?>
                    <div class="empty-state p-4">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        <p>Sin garantías próximas a vencer</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Activo</th>
                                    <th>Vence</th>
                                    <th>Días</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['garantias_proximas'] as $g): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/assets/<?= $g['id'] ?>">
                                            <?= htmlspecialchars($g['nombre']) ?>
                                        </a>
                                    </td>
                                    <td><?= $g['garantia_hasta'] ?></td>
                                    <td>
                                        <span class="badge <?= (int)$g['dias_restantes'] <= 7 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                            <?= $g['dias_restantes'] ?>d
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Próximos mantenimientos -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-calendar-check me-2"></i>Próximos mantenimientos
                </h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($data['mantenimientos_proximos'])): ?>
                    <div class="empty-state p-4">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        <p>Sin mantenimientos en los próximos 7 días</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Activo</th>
                                    <th>Fecha</th>
                                    <th>Técnico</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['mantenimientos_proximos'] as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['activo_nombre']) ?></td>
                                    <td><?= $m['proximo_mantenimiento'] ?></td>
                                    <td><?= htmlspecialchars($m['tecnico']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
<!-- Script gráfica -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.addEventListener('load', function () {
    const tipos = <?= json_encode($data['distribucion_tipos']) ?>;
    const labels = tipos.map(t => t.tipo);
    const values = tipos.map(t => parseInt(t.total));
    const colors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#F97316','#EC4899'];

    new Chart(document.getElementById('chartTipos'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: colors, borderWidth: 2 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } }
            }
        }
    });
});
</script>
