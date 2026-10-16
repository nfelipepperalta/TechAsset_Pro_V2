<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($asset['nombre']) ?> — TechAsset Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F1F5F9; }
        .qr-header { background: #0F172A; color: white; padding: 16px 20px;
            display: flex; align-items: center; gap: 10px; }
        .qr-brand { font-size: 16px; font-weight: 700; }
        .qr-brand span { color: #3B82F6; }
        .qr-card { max-width: 520px; margin: 24px auto; padding: 0 16px; }
        .estado-badge { font-size: 14px; padding: 6px 14px; border-radius: 20px; }
        .field-label { font-size: 11px; text-transform: uppercase;
            letter-spacing: .06em; color: #94A3B8; font-weight: 600; }
        .field-value { font-size: 14px; font-weight: 500; color: #1E293B; }
        .divider { border-color: #E2E8F0; }
    </style>
</head>
<body>

<!-- Header público -->
<div class="qr-header">
    <i class="fa-solid fa-microchip"></i>
    <span class="qr-brand">TechAsset <span>Pro</span></span>
    <span class="ms-auto text-muted small">Escaneo de activo</span>
</div>

<div class="qr-card">

    <!-- Nombre y estado -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h1 class="h5 fw-bold mb-1"><?= htmlspecialchars($asset['nombre']) ?></h1>
                    <p class="text-muted small mb-0">
                        <?= htmlspecialchars($asset['marca']) ?> <?= htmlspecialchars($asset['modelo']) ?>
                    </p>
                </div>
                <?php
                    $estadoBadge = match($asset['estado']) {
                        'activo'        => 'success',
                        'mantenimiento' => 'warning',
                        'baja'          => 'danger',
                        'adquisicion'   => 'info',
                        default         => 'secondary',
                    };
                ?>
                <span class="badge bg-<?= $estadoBadge ?> estado-badge">
                    <?= ucfirst($asset['estado']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Datos técnicos -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <?php
                $fields = [
                    ['Tipo',        $asset['tipo_nombre']          ?? '—'],
                    ['Serial',      $asset['serial']               ?? '—'],
                    ['Ubicación',   $asset['ubicacion']],
                    ['Departamento',$asset['departamento_nombre']  ?? '—'],
                    ['Asignado a',  $asset['usuario_nombre']       ?? 'Sin asignar'],
                    ['Garantía',    $asset['garantia_hasta']       ?? '—'],
                ];
                foreach ($fields as [$label, $value]):
                ?>
                <div class="col-6">
                    <p class="field-label mb-0"><?= $label ?></p>
                    <p class="field-value mb-0"><?= htmlspecialchars((string)$value) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Notas -->
    <?php if (!empty($asset['notas'])): ?>
    <div class="card mb-3">
        <div class="card-body">
            <p class="field-label mb-1">Notas</p>
            <p class="small mb-0"><?= nl2br(htmlspecialchars($asset['notas'])) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pie -->
    <p class="text-center text-muted small mt-3">
        <i class="fa-solid fa-qrcode me-1"></i>
        Generado por TechAsset Pro · <?= date('d/m/Y') ?>
    </p>

</div>

</body>
</html>
