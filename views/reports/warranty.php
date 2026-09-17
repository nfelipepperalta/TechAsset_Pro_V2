<div class="d-flex gap-2 mb-4">
    <?php foreach ([30, 60, 90] as $d): ?>
        <a href="?days=<?= $d ?>"
           class="btn btn-sm <?= $days == $d ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= $d ?> días
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Activo</th><th>Tipo</th><th>Garantía hasta</th><th>Días restantes</th><th>Departamento</th></tr>
            </thead>
            <tbody>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-circle-check text-success fa-2x d-block mb-2"></i>
                        Sin garantías por vencer en <?= $days ?> días
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($assets as $a): ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/assets/<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></a></td>
                        <td><?= htmlspecialchars($a['tipo_nombre'] ?? '—') ?></td>
                        <td><?= $a['garantia_hasta'] ?></td>
                        <td>
                            <span class="badge <?= (int)$a['dias_restantes'] <= 7 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                <?= $a['dias_restantes'] ?> días
                            </span>
                        </td>
                        <td><?= htmlspecialchars($a['departamento_nombre'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
