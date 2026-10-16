<?php use Core\Auth; ?>

<!-- Mensajes flash -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= APP_URL ?>/assets" class="row g-2 align-items-end">

            <div class="col-12 col-md-4">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nombre, serial, marca..."
                           value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($estados as $e): ?>
                        <option value="<?= $e ?>" <?= $filters['estado'] === $e ? 'selected' : '' ?>>
                            <?= ucfirst($e) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Tipo</label>
                <select name="asset_type_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filters['asset_type_id'] == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Departamento</label>
                <select name="department_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($deptos as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filters['department_id'] == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>/assets" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>

        </form>
    </div>
</div>

<!-- Acciones superiores -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">
        <?= number_format($paging['total']) ?> activos encontrados
    </p>
    <?php if (Auth::canEdit()): ?>
        <a href="<?= APP_URL ?>/assets/create" class="btn btn-primary">
            <i class="fa-solid fa-plus me-2"></i>Registrar activo
        </a>
    <?php endif; ?>
</div>

<!-- Tabla de activos -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Activo</th>
                    <th>Tipo</th>
                    <th>Serial</th>
                    <th>Estado</th>
                    <th>Ubicación</th>
                    <th>Garantía</th>
                    <th>Departamento</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
                            No se encontraron activos
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assets as $a): ?>
                    <tr>
                        <td>
                            <div class="asset-name-cell">
                                <strong><?= htmlspecialchars($a['nombre']) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($a['marca']) ?> <?= htmlspecialchars($a['modelo']) ?></small>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($a['tipo_nombre'] ?? '—') ?></span></td>
                        <td><code><?= htmlspecialchars($a['serial'] ?? '—') ?></code></td>
                        <td>
                            <?php
                                $estadoBadge = match($a['estado']) {
                                    'activo'        => 'success',
                                    'mantenimiento' => 'warning',
                                    'baja'          => 'danger',
                                    'adquisicion'   => 'info',
                                    default         => 'secondary',
                                };
                            ?>
                            <span class="badge bg-<?= $estadoBadge ?>"><?= ucfirst($a['estado']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($a['ubicacion']) ?></td>
                        <td>
                            <?php if ($a['garantia_hasta']): ?>
                                <?php $dias = (int)((strtotime($a['garantia_hasta']) - time()) / 86400); ?>
                                <span class="<?= $dias < 30 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                    <?= $a['garantia_hasta'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($a['departamento_nombre'] ?? '—') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= APP_URL ?>/assets/<?= $a['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <?php if (Auth::canEdit()): ?>
                                <a href="<?= APP_URL ?>/assets/<?= $a['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginación -->
<?php if ($paging['last_page'] > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php for ($p = 1; $p <= $paging['last_page']; $p++): ?>
        <li class="page-item <?= $p === $paging['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
