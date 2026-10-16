<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0"><?= count($assets) ?> activos encontrados</p>
    <a href="<?= APP_URL ?>/reports/export?<?= http_build_query($filters) ?>"
       class="btn btn-success btn-sm">
        <i class="fa-solid fa-download me-2"></i>Exportar CSV
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th><th>Tipo</th><th>Serial</th>
                    <th>Estado</th><th>Departamento</th>
                    <th>Garantía</th><th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assets as $a): ?>
                <tr>
                    <td><a href="<?= APP_URL ?>/assets/<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></a></td>
                    <td><?= htmlspecialchars($a['tipo_nombre'] ?? '—') ?></td>
                    <td><code><?= htmlspecialchars($a['serial'] ?? '—') ?></code></td>
                    <td><span class="badge bg-<?= match($a['estado']){'activo'=>'success','mantenimiento'=>'warning','baja'=>'danger',default=>'secondary'} ?>"><?= ucfirst($a['estado']) ?></span></td>
                    <td><?= htmlspecialchars($a['departamento_nombre'] ?? '—') ?></td>
                    <td><?= $a['garantia_hasta'] ?? '—' ?></td>
                    <td><?= $a['valor'] ? '$'.number_format($a['valor'],0,',','.') : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
