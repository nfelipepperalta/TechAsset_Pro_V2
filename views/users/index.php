<?php use Core\Auth; ?>

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

<div class="d-flex justify-content-end mb-3">
    <a href="<?= APP_URL ?>/users/create" class="btn btn-primary">
        <i class="fa-solid fa-user-plus me-2"></i>Nuevo usuario
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th>Último acceso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="<?= !$u['activo'] ? 'opacity-50' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar" style="width:32px;height:32px;font-size:12px">
                                <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($u['nombre']) ?></strong>
                                <small class="text-muted d-block"><?= htmlspecialchars($u['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                            $rolBadge = match($u['rol']) {
                                'admin'   => 'danger',
                                'ti'      => 'primary',
                                'auditor' => 'info',
                                default   => 'secondary',
                            };
                        ?>
                        <span class="badge bg-<?= $rolBadge ?>"><?= ucfirst($u['rol']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($u['departamento_nombre'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= $u['last_login'] ?? 'Nunca' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= APP_URL ?>/users/<?= $u['id'] ?>/edit"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <?php if ($u['id'] !== Auth::user()['id']): ?>
                            <form method="POST" action="<?= APP_URL ?>/users/<?= $u['id'] ?>/delete"
                                  data-confirm="¿Desactivar al usuario <?= htmlspecialchars($u['nombre']) ?>?">
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
