<?php if (isset($result['error'])): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <strong>Error al escanear <?= htmlspecialchars($ip) ?>:</strong>
        <?= htmlspecialchars($result['error']) ?>
    </div>
    <a href="<?= APP_URL ?>/scanner" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Volver
    </a>
<?php else:
    $scan    = $result['scan']    ?? [];
    $analisis= $result['analisis']?? [];
    $sugerido= $result['sugerencia_activo'] ?? [];
?>

<!-- Encabezado del equipo -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2 class="mb-1">
                    <i class="fa-solid fa-server me-2 text-primary"></i>
                    <?= htmlspecialchars($scan['hostname'] ?: $ip) ?>
                </h2>
                <p class="text-muted mb-2">
                    IP: <strong><?= htmlspecialchars($ip) ?></strong>
                    <?php if ($scan['mac']): ?>
                        &nbsp;·&nbsp; MAC: <code><?= htmlspecialchars($scan['mac']) ?></code>
                        <?php if ($scan['fabricante']): ?>
                            &nbsp;·&nbsp; <span class="badge bg-light text-dark"><?= htmlspecialchars($scan['fabricante']) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
                <?php if ($scan['os_mejor']): ?>
                    <p class="mb-0">
                        <i class="fa-solid fa-display me-1 text-muted"></i>
                        <?= htmlspecialchars($scan['os_mejor']) ?>
                        <?php if (!empty($scan['os'][0]['precision'])): ?>
                            <small class="text-muted">(<?= $scan['os'][0]['precision'] ?>% precisión)</small>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <div class="display-6 fw-bold text-<?= $analisis['color_riesgo'] ?>">
                    <?= $analisis['score_riesgo'] ?>/100
                </div>
                <span class="badge bg-<?= $analisis['color_riesgo'] ?> fs-6">
                    Riesgo <?= $analisis['nivel_riesgo'] ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Resumen IA -->
<?php if (!empty($analisis['resumen_ia'])): ?>
<div class="card mb-4 border-<?= $analisis['color_riesgo'] ?>">
    <div class="card-header bg-<?= $analisis['color_riesgo'] ?> bg-opacity-10">
        <h2 class="card-title text-<?= $analisis['color_riesgo'] ?>">
            <i class="fa-solid fa-brain me-2"></i>Análisis de Seguridad — IA TechAsset Pro
        </h2>
    </div>
    <div class="card-body">
        <p class="mb-0"><?= htmlspecialchars($analisis['resumen_ia']) ?></p>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">

    <!-- Hallazgos de seguridad -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">
                    <i class="fa-solid fa-shield-halved me-2"></i>Hallazgos de seguridad
                </h2>
                <span class="badge bg-secondary"><?= $analisis['total_hallazgos'] ?> hallazgos</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($analisis['hallazgos'])): ?>
                    <div class="empty-state p-4">
                        <i class="fa-solid fa-circle-check text-success fa-2x mb-2 d-block"></i>
                        <p>Sin hallazgos de seguridad. El equipo parece estar bien configurado.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $iconos = [
                        'critico' => ['fa-triangle-exclamation', 'danger'],
                        'alto'    => ['fa-circle-exclamation',   'danger'],
                        'medio'   => ['fa-circle-minus',         'warning'],
                        'bajo'    => ['fa-circle-check',         'success'],
                        'info'    => ['fa-circle-info',          'info'],
                    ];
                    ?>
                    <div class="accordion accordion-flush" id="accordionHallazgos">
                        <?php foreach ($analisis['hallazgos'] as $i => $hallazgo): ?>
                        <?php [$icono, $color] = $iconos[$hallazgo['nivel']] ?? ['fa-circle-info','info']; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#h<?= $i ?>">
                                    <span class="me-2 text-<?= $color ?>">
                                        <i class="fa-solid <?= $icono ?>"></i>
                                    </span>
                                    <span class="badge bg-<?= $color ?> me-2 text-uppercase" style="font-size:10px">
                                        <?= $hallazgo['nivel'] ?>
                                    </span>
                                    <?= htmlspecialchars($hallazgo['titulo']) ?>
                                </button>
                            </h2>
                            <div id="h<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
                                <div class="accordion-body">
                                    <p class="mb-2"><strong>Descripción:</strong> <?= htmlspecialchars($hallazgo['descripcion']) ?></p>
                                    <div class="alert alert-<?= $color ?> py-2 mb-0">
                                        <i class="fa-solid fa-lightbulb me-2"></i>
                                        <strong>Recomendación:</strong> <?= htmlspecialchars($hallazgo['recomendacion']) ?>
                                    </div>
                                    <?php if (!empty($hallazgo['version'])): ?>
                                        <small class="text-muted mt-2 d-block">
                                            Versión detectada: <?= htmlspecialchars($hallazgo['version']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Panel derecho: puertos + registrar activo -->
    <div class="col-lg-4">

        <!-- Puertos abiertos -->
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-plug me-2"></i>Puertos abiertos
                    <span class="badge bg-secondary ms-1"><?= count($scan['puertos']) ?></span>
                </h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($scan['puertos'])): ?>
                    <p class="text-muted p-3 mb-0">No se detectaron puertos abiertos.</p>
                <?php else: ?>
                    <div style="max-height:300px;overflow-y:auto;">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Puerto</th><th>Servicio</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scan['puertos'] as $p): ?>
                                <?php
                                    $pRiesgo = $GLOBALS['_puertos_riesgo'][$p['puerto']] ?? null;
                                    $badgeColor = 'secondary';
                                    if ($pRiesgo) {
                                        $badgeColor = match($pRiesgo['riesgo'] ?? '') {
                                            'critico' => 'danger',
                                            'alto'    => 'danger',
                                            'medio'   => 'warning',
                                            'bajo'    => 'success',
                                            default   => 'secondary'
                                        };
                                    }
                                ?>
                                <tr>
                                    <td><code><?= $p['puerto'] ?>/<?= $p['protocolo'] ?></code></td>
                                    <td class="small"><?= htmlspecialchars($p['servicio']) ?></td>
                                    <td>
                                        <?php if ($pRiesgo): ?>
                                            <span class="badge bg-<?= $badgeColor ?>" style="font-size:9px">
                                                <?= strtoupper($pRiesgo['riesgo']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Registrar como activo -->
        <div class="card border-success">
            <div class="card-header bg-success bg-opacity-10">
                <h2 class="card-title text-success">
                    <i class="fa-solid fa-plus-circle me-2"></i>Registrar en inventario
                </h2>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Agrega este equipo al inventario de TechAsset Pro con los datos detectados.
                </p>
                <a href="<?= APP_URL ?>/assets/create?nombre=<?= urlencode($sugerido['nombre'] ?? '') ?>&marca=<?= urlencode($sugerido['fabricante'] ?? '') ?>&modelo=<?= urlencode($sugerido['os_mejor'] ?? '') ?>&notas=IP: <?= urlencode($ip) ?> | MAC: <?= urlencode($sugerido['mac'] ?? '') ?>"
                   class="btn btn-success w-100">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Registrar como activo
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Botones de acción -->
<div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/scanner" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Nuevo escaneo
    </a>
    <button onclick="window.print()" class="btn btn-outline-primary">
        <i class="fa-solid fa-print me-2"></i>Imprimir reporte
    </button>
</div>

<?php endif; ?>
