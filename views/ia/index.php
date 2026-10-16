<?php use Core\Auth; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
    <div class="alert alert-warning">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Modelo no disponible:</strong> <?= htmlspecialchars($data['error']) ?>
        <br><small class="text-muted mt-1 d-block">
            Verifica que Python3 y las dependencias estén instaladas:
            <code>pip3 install scikit-learn pandas numpy pyodbc --break-system-packages</code>
        </small>
    </div>
<?php else: ?>

<!-- Encabezado con botón de actualizar -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">
            Modelo: <strong><?= htmlspecialchars($data['modelo'] ?? '—') ?></strong>
            &nbsp;·&nbsp;
            Generado: <strong><?= htmlspecialchars($resumen['generado_en'] ?? '—') ?></strong>
            <?php if (!empty($resumen['precision_modelo'])): ?>
                &nbsp;·&nbsp; Precisión: <strong><?= $resumen['precision_modelo'] ?>%</strong>
            <?php endif; ?>
        </p>
    </div>
    <?php if (Auth::canEdit()): ?>
    <form method="POST" action="<?= APP_URL ?>/ia/refresh">
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-rotate me-2"></i>Actualizar modelo
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Métricas principales -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-secondary">
                <i class="fa-solid fa-server"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value"><?= number_format($resumen['total_activos'] ?? 0) ?></span>
                <span class="metric-label">Activos analizados</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card border-danger">
            <div class="metric-icon bg-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value text-danger"><?= $resumen['riesgo_alto'] ?? 0 ?></span>
                <span class="metric-label">Riesgo ALTO</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card border-warning">
            <div class="metric-icon bg-warning">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value text-warning"><?= $resumen['riesgo_medio'] ?? 0 ?></span>
                <span class="metric-label">Riesgo MEDIO</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="metric-card">
            <div class="metric-icon bg-info">
                <i class="fa-solid fa-brain"></i>
            </div>
            <div class="metric-info">
                <span class="metric-value"><?= $resumen['score_promedio'] ?? 0 ?>%</span>
                <span class="metric-label">Score promedio</span>
            </div>
        </div>
    </div>
</div>

<!-- Segunda fila: gráfica distribución + top 5 riesgo -->
<div class="row g-3 mb-4">

    <!-- Gráfica distribución de riesgo -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-chart-pie me-2"></i>Distribución de riesgo
                </h2>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="max-height:260px; width:100%;">
                    <canvas id="chartRiesgo"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 5 activos de mayor riesgo -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-ranking-star me-2"></i>Top activos de mayor riesgo
                </h2>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($top, 0, 5) as $i => $activo): ?>
                    <li class="list-group-item d-flex align-items-center gap-3 py-3">
                        <span class="badge bg-<?= $activo['color_riesgo'] ?> rounded-pill" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:14px;">
                            <?= $i + 1 ?>
                        </span>
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-semibold">
                                <a href="<?= APP_URL ?>/assets/<?= $activo['id'] ?>">
                                    <?= htmlspecialchars($activo['nombre']) ?>
                                </a>
                            </p>
                            <small class="text-muted">
                                <?= htmlspecialchars($activo['tipo']) ?>
                                &nbsp;·&nbsp; <?= $activo['edad_anos'] ?> años
                                &nbsp;·&nbsp; <?= $activo['mant_correctivos'] ?> mant. correctivos
                            </small>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bold text-<?= $activo['color_riesgo'] ?> fs-5">
                                <?= $activo['probabilidad_fallo'] ?>%
                            </div>
                            <small class="badge bg-<?= $activo['color_riesgo'] ?>">
                                <?= $activo['nivel_riesgo'] ?>
                            </small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- Tabla completa de predicciones -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title">
            <i class="fa-solid fa-table me-2"></i>Predicciones por activo
        </h2>
        <!-- Filtro rápido -->
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger  filter-btn active" data-filter="all">Todos</button>
            <button class="btn btn-sm btn-outline-danger  filter-btn" data-filter="ALTO">Alto</button>
            <button class="btn btn-sm btn-outline-warning filter-btn" data-filter="MEDIO">Medio</button>
            <button class="btn btn-sm btn-outline-success filter-btn" data-filter="BAJO">Bajo</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaPredicciones">
            <thead>
                <tr>
                    <th>Activo</th>
                    <th>Tipo</th>
                    <th>Edad</th>
                    <th>Mant. correctivos</th>
                    <th>Sin mantenimiento</th>
                    <th>Próximo mant.</th>
                    <th>Riesgo</th>
                    <th>Probabilidad</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['activos'] ?? [] as $activo): ?>
                <tr data-nivel="<?= $activo['nivel_riesgo'] ?>">
                    <td>
                        <a href="<?= APP_URL ?>/assets/<?= $activo['id'] ?>">
                            <?= htmlspecialchars($activo['nombre']) ?>
                        </a>
                        <small class="text-muted d-block"><?= htmlspecialchars($activo['departamento']) ?></small>
                    </td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($activo['tipo']) ?></span></td>
                    <td><?= $activo['edad_anos'] ?> años</td>
                    <td>
                        <?php if ($activo['mant_correctivos'] > 0): ?>
                            <span class="text-danger fw-semibold"><?= $activo['mant_correctivos'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $dias = $activo['dias_sin_mantenimiento']; ?>
                        <span class="<?= $dias > 365 ? 'text-danger fw-semibold' : ($dias > 180 ? 'text-warning fw-semibold' : 'text-muted') ?>">
                            <?= $dias > 9000 ? 'Nunca' : $dias . ' días' ?>
                        </span>
                    </td>
                    <td>
                        <small class="<?= $activo['dias_proximo_mant'] <= 7 ? 'text-danger fw-semibold' : ($activo['dias_proximo_mant'] <= 30 ? 'text-warning fw-semibold' : 'text-muted') ?>">
                            <?= $activo['fecha_proximo_mant'] ?>
                            <br>(<?= $activo['dias_proximo_mant'] ?> días)
                        </small>
                    </td>
                    <td>
                        <span class="badge bg-<?= $activo['color_riesgo'] ?>">
                            <?= $activo['nivel_riesgo'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:8px;">
                                <div class="progress-bar bg-<?= $activo['color_riesgo'] ?>"
                                     style="width:<?= $activo['probabilidad_fallo'] ?>%"></div>
                            </div>
                            <span class="fw-bold text-<?= $activo['color_riesgo'] ?>" style="min-width:42px">
                                <?= $activo['probabilidad_fallo'] ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Gráfica de distribución ───────────────────────────────────
function initChartRiesgo() {
    const resumen = <?= json_encode($resumen) ?>;
    const ctx = document.getElementById('chartRiesgo');
    if (!ctx || !resumen.total_activos) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Riesgo Alto', 'Riesgo Medio', 'Riesgo Bajo'],
            datasets: [{
                data: [resumen.riesgo_alto, resumen.riesgo_medio, resumen.riesgo_bajo],
                backgroundColor: ['#EF4444', '#F59E0B', '#10B981'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 14, font: { size: 12 } }
                }
            }
        }
    });
}

// Esperar a que Chart.js esté disponible
if (typeof Chart !== 'undefined') {
    initChartRiesgo();
} else {
    window.addEventListener('load', initChartRiesgo);
}

// ── Filtro por nivel de riesgo ────────────────────────────────
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const filter = this.dataset.filter;
        document.querySelectorAll('#tablaPredicciones tbody tr').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.nivel === filter) ? '' : 'none';
        });
    });
});
</script>
