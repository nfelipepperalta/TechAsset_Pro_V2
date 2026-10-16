<?php if (isset($result['error'])): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <?= htmlspecialchars($result['error']) ?>
    </div>
    <a href="<?= APP_URL ?>/scanner" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Volver
    </a>
<?php else: ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">
            Rango: <strong><?= htmlspecialchars($range) ?></strong>
            &nbsp;·&nbsp;
            <strong><?= $result['total_hosts'] ?? 0 ?></strong> equipos encontrados
            &nbsp;·&nbsp;
            <?= $result['generado_en'] ?? '' ?>
        </p>
    </div>
    <a href="<?= APP_URL ?>/scanner" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-2"></i>Nuevo escaneo
    </a>
</div>

<?php if (empty($result['hosts'])): ?>
    <div class="card">
        <div class="empty-state p-5">
            <i class="fa-solid fa-network-wired fa-3x text-muted mb-3 d-block"></i>
            <h3>No se encontraron equipos activos</h3>
            <p class="text-muted">El rango <?= htmlspecialchars($range) ?> no tiene equipos respondiendo.</p>
        </div>
    </div>
<?php else: ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>IP</th>
                    <th>Hostname</th>
                    <th>MAC Address</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['hosts'] as $i => $host): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td><code><?= htmlspecialchars($host['ip']) ?></code></td>
                    <td><?= htmlspecialchars($host['hostname'] ?: '—') ?></td>
                    <td><code class="text-muted"><?= htmlspecialchars($host['mac'] ?: '—') ?></code></td>
                    <td>
                        <span class="badge bg-<?= $host['estado'] === 'up' ? 'success' : 'secondary' ?>">
                            <?= $host['estado'] === 'up' ? 'Activo' : $host['estado'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <form method="POST" action="<?= APP_URL ?>/scanner/ip">
                                <input type="hidden" name="ip" value="<?= htmlspecialchars($host['ip']) ?>">
                                <input type="hidden" name="intensidad" value="rapido">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Analizar con IA">
                                    <i class="fa-solid fa-shield-halved me-1"></i>Analizar
                                </button>
                            </form>
                            <a href="<?= APP_URL ?>/assets/create?nombre=<?= urlencode($host['hostname'] ?: $host['ip']) ?>&notas=IP: <?= urlencode($host['ip']) ?> | MAC: <?= urlencode($host['mac'] ?? '') ?>"
                               class="btn btn-sm btn-outline-success" title="Registrar en inventario">
                                <i class="fa-solid fa-plus"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php endif; ?>
