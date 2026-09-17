<?php
$isEdit = !empty($maint);
$old    = $old ?? $maint ?? [];
function valM(string $k, array $d): string { return htmlspecialchars($d[$k] ?? ''); }
function errM(string $k, array $e): string {
    return !empty($e[$k]) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($e[$k]) . '</div>' : '';
}
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<form method="POST"
      action="<?= APP_URL ?>/maintenance/<?= $isEdit ? $maint['id'] . '/edit' : 'create' ?>"
      novalidate>

    <input type="hidden" name="asset_id" value="<?= $asset['id'] ?? ($old['asset_id'] ?? '') ?>">

    <!-- Activo asociado -->
    <?php if ($asset): ?>
    <div class="card mb-4">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="text-primary fs-3"><i class="fa-solid fa-server"></i></div>
            <div>
                <p class="mb-0 fw-semibold"><?= htmlspecialchars($asset['nombre']) ?></p>
                <small class="text-muted">
                    <?= htmlspecialchars($asset['marca']) ?> <?= htmlspecialchars($asset['modelo']) ?>
                    &nbsp;·&nbsp; <code><?= htmlspecialchars($asset['serial'] ?? '—') ?></code>
                </small>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!$asset && !empty($assets)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-server me-2"></i>Activo a mantener</h2>
        </div>
        <div class="card-body">
            <label class="form-label">Seleccionar activo</label>
            <select name="asset_id" class="form-select" required>
                <option value="">— Seleccione un activo —</option>
                <?php foreach ($assets as $a): ?>
                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?> — <?= htmlspecialchars($a['marca']) ?> <?= htmlspecialchars($a['modelo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Datos del mantenimiento</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label required">Tipo</label>
                    <select name="tipo" class="form-select <?= isset($errors['tipo']) ? 'is-invalid' : '' ?>" required>
                        <?php foreach (['preventivo','correctivo','actualizacion','inspeccion'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($old['tipo'] ?? '') === $t ? 'selected' : '' ?>>
                                <?= ucfirst($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= errM('tipo', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Fecha</label>
                    <input type="date" name="fecha" class="form-control <?= isset($errors['fecha']) ? 'is-invalid' : '' ?>"
                           value="<?= valM('fecha', $old) ?>" required>
                    <?= errM('fecha', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Estado</label>
                    <select name="estado" class="form-select">
                        <?php foreach (['programado','en_proceso','completado','cancelado'] as $e): ?>
                            <option value="<?= $e ?>" <?= ($old['estado'] ?? 'completado') === $e ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $e)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label required">Descripción</label>
                    <textarea name="descripcion" class="form-control <?= isset($errors['descripcion']) ? 'is-invalid' : '' ?>"
                              rows="3" required><?= valM('descripcion', $old) ?></textarea>
                    <?= errM('descripcion', $errors) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Técnico responsable</label>
                    <input type="text" name="tecnico" class="form-control <?= isset($errors['tecnico']) ? 'is-invalid' : '' ?>"
                           value="<?= valM('tecnico', $old) ?>" required>
                    <?= errM('tecnico', $errors) ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Costo</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="costo" class="form-control" step="0.01" min="0"
                               value="<?= valM('costo', $old) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Próximo mantenimiento</label>
                    <input type="date" name="proximo" class="form-control"
                           value="<?= valM('proximo_mantenimiento', $old) ?>">
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="<?= !empty($asset) ? APP_URL . '/assets/' . $asset['id'] : APP_URL . '/maintenance' ?>"
           class="btn btn-outline-secondary">
            <i class="fa-solid fa-xmark me-2"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-2"></i>
            <?= $isEdit ? 'Guardar cambios' : 'Registrar mantenimiento' ?>
        </button>
    </div>

</form>
</div>
</div>
