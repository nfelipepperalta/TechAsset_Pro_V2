<?php
$isEdit = !empty($asset);
$old    = $old ?? $asset ?? [];

function val(string $key, array $data): string {
    return htmlspecialchars($data[$key] ?? '');
}
function err(string $key, array $errors): string {
    return !empty($errors[$key])
        ? '<div class="invalid-feedback d-block">' . htmlspecialchars($errors[$key]) . '</div>'
        : '';
}
?>

<div class="row justify-content-center">
<div class="col-lg-9">

<form method="POST"
      action="<?= APP_URL ?>/assets/<?= $isEdit ? $asset['id'] . '/edit' : 'create' ?>"
      novalidate>

    <!-- Información básica -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-info-circle me-2"></i>Información básica</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label required">Nombre del activo</label>
                    <input type="text" name="nombre" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                           value="<?= val('nombre', $old) ?>" placeholder="Ej: Servidor Principal BD" required>
                    <?= err('nombre', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Tipo de activo</label>
                    <select name="asset_type_id" class="form-select <?= isset($errors['asset_type_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">Seleccionar tipo...</option>
                        <?php foreach ($formData['tipos'] as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                <?= ($old['asset_type_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err('asset_type_id', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Marca</label>
                    <input type="text" name="marca" class="form-control <?= isset($errors['marca']) ? 'is-invalid' : '' ?>"
                           value="<?= val('marca', $old) ?>" placeholder="Dell, HP, Cisco..." required>
                    <?= err('marca', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Modelo</label>
                    <input type="text" name="modelo" class="form-control <?= isset($errors['modelo']) ? 'is-invalid' : '' ?>"
                           value="<?= val('modelo', $old) ?>" placeholder="PowerEdge R740" required>
                    <?= err('modelo', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Serial / Número de serie</label>
                    <input type="text" name="serial" class="form-control <?= isset($errors['serial']) ? 'is-invalid' : '' ?>"
                           value="<?= val('serial', $old) ?>" placeholder="SRV-DELL-001">
                    <?= err('serial', $errors) ?>
                </div>

                <div class="col-md-8">
                    <label class="form-label required">Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control <?= isset($errors['ubicacion']) ? 'is-invalid' : '' ?>"
                           value="<?= val('ubicacion', $old) ?>" placeholder="Rack A - Data Center" required>
                    <?= err('ubicacion', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Estado</label>
                    <select name="estado" class="form-select <?= isset($errors['estado']) ? 'is-invalid' : '' ?>" required>
                        <?php foreach ($formData['estados'] as $e): ?>
                            <option value="<?= $e ?>"
                                <?= ($old['estado'] ?? 'activo') === $e ? 'selected' : '' ?>>
                                <?= ucfirst($e) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err('estado', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fa-solid fa-network-wired me-1 text-muted"></i>
                        Dirección IP
                    </label>
                    <input type="text" name="ip_address" class="form-control"
                           value="<?= val('ip_address', $old) ?>"
                           placeholder="192.168.0.100"
                           pattern="^(\d{1,3}\.){3}\d{1,3}$">
                    <small class="text-muted">Para lector de eventos y escáner IA</small>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2"
                              placeholder="Observaciones adicionales..."><?= val('notas', $old) ?></textarea>
                </div>

            </div>
        </div>
    </div>

    <!-- Datos de adquisición -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-receipt me-2"></i>Adquisición y garantía</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label required">Fecha de compra</label>
                    <input type="date" name="fecha_compra" class="form-control <?= isset($errors['fecha_compra']) ? 'is-invalid' : '' ?>"
                           value="<?= val('fecha_compra', $old) ?>" required>
                    <?= err('fecha_compra', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Garantía hasta</label>
                    <input type="date" name="garantia_hasta" class="form-control <?= isset($errors['garantia_hasta']) ? 'is-invalid' : '' ?>"
                           value="<?= val('garantia_hasta', $old) ?>">
                    <?= err('garantia_hasta', $errors) ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Valor de adquisición</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="valor" class="form-control" step="0.01" min="0"
                               value="<?= val('valor', $old) ?>" placeholder="0.00">
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Asignación -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-user-check me-2"></i>Asignación</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Departamento</label>
                    <select name="department_id" class="form-select">
                        <option value="">Sin departamento</option>
                        <?php foreach ($formData['departamentos'] as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                <?= ($old['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre']) ?> — <?= htmlspecialchars($d['sede']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Asignado a</label>
                    <select name="user_id" class="form-select">
                        <option value="">Sin asignar</option>
                        <?php foreach ($formData['usuarios'] as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= ($old['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- Botones -->
    <div class="d-flex gap-2 justify-content-end">
        <a href="<?= APP_URL ?>/assets<?= $isEdit ? '/' . $asset['id'] : '' ?>"
           class="btn btn-outline-secondary">
            <i class="fa-solid fa-xmark me-2"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-2"></i>
            <?= $isEdit ? 'Guardar cambios' : 'Registrar activo' ?>
        </button>
    </div>

</form>
</div>
</div>
