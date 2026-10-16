<?php
$isEdit = !empty($record);
$old    = $old ?? $record ?? [];
function valU(string $k, array $d): string { return htmlspecialchars($d[$k] ?? ''); }
function errU(string $k, array $e): string {
    return !empty($e[$k]) ? '<div class="invalid-feedback d-block">' . htmlspecialchars($e[$k]) . '</div>' : '';
}
?>

<div class="row justify-content-center">
<div class="col-lg-7">

<form method="POST"
      action="<?= APP_URL ?>/users/<?= $isEdit ? $record['id'] . '/edit' : 'create' ?>"
      novalidate>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fa-solid fa-user-<?= $isEdit ? 'pen' : 'plus' ?> me-2"></i>
                <?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?>
            </h2>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-12">
                    <label class="form-label required">Nombre completo</label>
                    <input type="text" name="nombre"
                           class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
                           value="<?= valU('nombre', $old) ?>" required>
                    <?= errU('nombre', $errors) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Correo electrónico</label>
                    <input type="email" name="email"
                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= valU('email', $old) ?>" required>
                    <?= errU('email', $errors) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Rol</label>
                    <select name="rol" class="form-select <?= isset($errors['rol']) ? 'is-invalid' : '' ?>" required>
                        <?php foreach ($formData['roles'] as $r): ?>
                            <option value="<?= $r ?>" <?= ($old['rol'] ?? '') === $r ? 'selected' : '' ?>>
                                <?= ucfirst($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= errU('rol', $errors) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label <?= $isEdit ? '' : 'required' ?>">
                        Contraseña <?= $isEdit ? '(dejar vacío para no cambiar)' : '' ?>
                    </label>
                    <input type="password" name="password"
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           <?= $isEdit ? '' : 'required' ?> minlength="8"
                           placeholder="Mínimo 8 caracteres">
                    <?= errU('password', $errors) ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Departamento</label>
                    <select name="department_id" class="form-select">
                        <option value="">Sin departamento</option>
                        <?php foreach ($formData['departamentos'] as $d): ?>
                            <option value="<?= $d['id'] ?>"
                                <?= ($old['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
        <div class="card-footer d-flex gap-2 justify-content-end">
            <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary">
                <i class="fa-solid fa-xmark me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-2"></i>
                <?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?>
            </button>
        </div>
    </div>

</form>
</div>
</div>
