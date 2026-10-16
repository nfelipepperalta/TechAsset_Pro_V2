<?php use Core\Auth; ?>

<div class="row g-4">

    <!-- Columna izquierda: datos del activo -->
    <div class="col-lg-8">

        <!-- Encabezado activo -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="mb-1"><?= htmlspecialchars($asset['nombre']) ?></h2>
                        <p class="text-muted mb-2">
                            <?= htmlspecialchars($asset['marca']) ?> <?= htmlspecialchars($asset['modelo']) ?>
                            <?php if ($asset['serial']): ?>
                                &nbsp;·&nbsp; <code><?= htmlspecialchars($asset['serial']) ?></code>
                            <?php endif; ?>
                            <?php if (!empty($asset['ip_address'])): ?>
                                &nbsp;·&nbsp; <span class="badge bg-light text-dark"><i class="fa-solid fa-network-wired me-1"></i><?= htmlspecialchars($asset['ip_address']) ?></span>
                            <?php endif; ?>
                        </p>
                        <?php
                            $estadoBadge = match($asset['estado']) {
                                'activo'        => 'success',
                                'mantenimiento' => 'warning',
                                'baja'          => 'danger',
                                'adquisicion'   => 'info',
                                default         => 'secondary',
                            };
                        ?>
                        <span class="badge bg-<?= $estadoBadge ?> fs-6">
                            <?= ucfirst($asset['estado']) ?>
                        </span>
                    </div>
                    <?php if (Auth::canEdit()): ?>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <a href="<?= APP_URL ?>/assets/<?= $asset['id'] ?>/edit"
                           class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-pen me-1"></i>Editar
                        </a>
                        <?php if (Auth::isAdmin()): ?>
                        <form method="POST" action="<?= APP_URL ?>/assets/<?= $asset['id'] ?>/delete"
                              onsubmit="return confirm('¿Eliminar este activo permanentemente?')">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fa-solid fa-trash me-1"></i>Eliminar
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ficha técnica -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-clipboard-list me-2"></i>Ficha técnica</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $fields = [
                        ['Tipo',         $asset['tipo_nombre'] ?? '—'],
                        ['Categoría',    ucfirst($asset['tipo_categoria'] ?? '—')],
                        ['Ubicación',    $asset['ubicacion']],
                        ['Fecha compra', $asset['fecha_compra']],
                        ['Garantía',     $asset['garantia_hasta'] ?? '—'],
                        ['Valor',        $asset['valor'] ? '$' . number_format($asset['valor'], 0, ',', '.') : '—'],
                        ['Departamento', $asset['departamento_nombre'] ?? '—'],
                        ['Sede',         $asset['departamento_sede'] ?? '—'],
                        ['Asignado a',   $asset['usuario_nombre'] ?? '—'],
                        ['IP Address',   $asset['ip_address'] ?? '—'],
                        ['Registrado',   $asset['created_at'] ?? '—'],
                    ];
                    foreach ($fields as [$label, $value]):
                    ?>
                    <div class="col-6 col-md-4">
                        <p class="text-muted small mb-0"><?= $label ?></p>
                        <p class="fw-medium mb-0"><?= htmlspecialchars((string)$value) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($asset['notas'])): ?>
                <hr>
                <p class="text-muted small mb-1">Notas</p>
                <p class="mb-0"><?= nl2br(htmlspecialchars($asset['notas'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel IA Predictiva -->
        <?php if (!empty($ia)): ?>
        <div class="card mb-4 border-<?= $ia['color_riesgo'] ?>">
            <div class="card-header bg-<?= $ia['color_riesgo'] ?> bg-opacity-10">
                <h2 class="card-title text-<?= $ia['color_riesgo'] ?>">
                    <i class="fa-solid fa-brain me-2"></i>Análisis IA Predictiva
                </h2>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-4 text-center">
                        <div class="display-6 fw-bold text-<?= $ia['color_riesgo'] ?>"><?= $ia['probabilidad_fallo'] ?>%</div>
                        <small class="text-muted">Prob. de fallo</small>
                    </div>
                    <div class="col-4 text-center">
                        <div class="display-6 fw-bold"><?= $ia['edad_anos'] ?></div>
                        <small class="text-muted">Años de uso</small>
                    </div>
                    <div class="col-4 text-center">
                        <div class="display-6 fw-bold"><?= $ia['mant_correctivos'] ?></div>
                        <small class="text-muted">Mant. correctivos</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="progress flex-grow-1" style="height:10px;">
                        <div class="progress-bar bg-<?= $ia['color_riesgo'] ?>"
                             style="width:<?= $ia['probabilidad_fallo'] ?>%"></div>
                    </div>
                    <span class="badge bg-<?= $ia['color_riesgo'] ?>"><?= $ia['nivel_riesgo'] ?></span>
                </div>
                <small class="text-muted">
                    <i class="fa-solid fa-calendar me-1"></i>
                    Próximo mantenimiento estimado: <strong><?= $ia['fecha_proximo_mant'] ?></strong>
                    (<?= $ia['dias_proximo_mant'] ?> días)
                </small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lector de Eventos -->
        <?php if (Auth::canAudit() && !empty($asset['ip_address'])): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title">
                    <i class="fa-solid fa-terminal me-2"></i>Lector de Eventos — Diagnóstico IA
                </h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('formEventos').classList.toggle('d-none')">
                        <i class="fa-solid fa-plug me-1"></i>Conectar aquí
                    </button>
                    <a href="<?= APP_URL ?>/diagnostic?host=<?= urlencode($asset['ip_address']) ?>&os=<?= !empty($asset['os_type']) ? urlencode($asset['os_type']) : 'windows' ?>" 
                       class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-stethoscope me-1"></i>Abrir Diagnóstico Completo
                    </a>
                </div>
            </div>
            <div class="card-body">

                <!-- Formulario de conexión -->
                <div id="formEventos" class="<?= !empty($eventos) ? 'd-none' : '' ?>">
                    <p class="text-muted small mb-3">
                        Conéctate al equipo para leer eventos del sistema y obtener diagnóstico con IA.
                        IP detectada: <strong><?= htmlspecialchars($asset['ip_address']) ?></strong>
                    </p>
                    <form method="POST" action="<?= APP_URL ?>/assets/<?= $asset['id'] ?>/events" id="formEventosForm">
                        <input type="hidden" name="ip" value="<?= htmlspecialchars($asset['ip_address']) ?>">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Sistema operativo</label>
                                <select name="os_type" class="form-select form-select-sm">
                                    <option value="windows">Windows</option>
                                    <option value="linux">Linux</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Usuario</label>
                                <input type="text" name="username" class="form-control form-control-sm"
                                       placeholder="Administrador" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control form-control-sm"
                                       placeholder="••••••••" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" id="btnConectar">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Leer eventos y analizar
                        </button>
                    </form>
                </div>

                <!-- Resultados del análisis -->
                <?php if (!empty($eventos)): ?>
                <div id="resultadosEventos">
                    <!-- Resumen -->
                    <div class="alert alert-<?= $eventos['analisis']['criticos'] > 0 ? 'danger' : ($eventos['analisis']['medios'] > 0 ? 'warning' : 'success') ?> mb-3">
                        <i class="fa-solid fa-brain me-2"></i>
                        <?= htmlspecialchars($eventos['analisis']['resumen_ia']) ?>
                    </div>

                    <!-- Métricas -->
                    <div class="row g-2 mb-3">
                        <div class="col-4 text-center">
                            <div class="fw-bold text-danger fs-4"><?= $eventos['analisis']['criticos'] ?></div>
                            <small class="text-muted">Críticos</small>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fw-bold text-warning fs-4"><?= $eventos['analisis']['medios'] ?></div>
                            <small class="text-muted">Medios</small>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fw-bold text-muted fs-4"><?= $eventos['analisis']['total'] ?></div>
                            <small class="text-muted">Total eventos</small>
                        </div>
                    </div>

                    <!-- Diagnóstico detallado -->
                    <?php if (!empty($eventos['analisis']['diagnostico'])): ?>
                    <div class="accordion accordion-flush" id="accordionEventos">
                        <?php foreach ($eventos['analisis']['diagnostico'] as $i => $diag): ?>
                        <?php
                            $iconos = ['critico'=>'fa-triangle-exclamation','medio'=>'fa-circle-exclamation','bajo'=>'fa-circle-check','info'=>'fa-circle-info'];
                            $icono  = $iconos[$diag['nivel']] ?? 'fa-circle-info';
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#ev<?= $i ?>">
                                    <span class="me-2 text-<?= $diag['color'] ?>">
                                        <i class="fa-solid <?= $icono ?>"></i>
                                    </span>
                                    <span class="badge bg-<?= $diag['color'] ?> me-2 text-uppercase" style="font-size:10px">
                                        <?= $diag['nivel'] ?>
                                    </span>
                                    <?= htmlspecialchars($diag['titulo']) ?>
                                </button>
                            </h2>
                            <div id="ev<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>">
                                <div class="accordion-body py-2">
                                    <p class="mb-1 small"><strong>Causa:</strong> <?= htmlspecialchars($diag['causa']) ?></p>
                                    <div class="alert alert-<?= $diag['color'] ?> py-2 mb-1 small">
                                        <i class="fa-solid fa-lightbulb me-1"></i>
                                        <strong>Solución IA:</strong> <?= htmlspecialchars($diag['solucion']) ?>
                                    </div>
                                    <?php if (!empty($diag['evento_raw'])): ?>
                                    <details class="mt-1">
                                        <summary class="text-muted small" style="cursor:pointer">Ver evento original</summary>
                                        <code class="small d-block mt-1 p-2 bg-light rounded"><?= htmlspecialchars($diag['evento_raw']) ?></code>
                                    </details>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state p-3">
                        <i class="fa-solid fa-circle-check text-success fa-2x d-block mb-2"></i>
                        <p>No se detectaron eventos críticos o medios. El sistema parece operar con normalidad.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php elseif (Auth::canAudit() && empty($asset['ip_address'])): ?>
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <i class="fa-solid fa-network-wired fa-2x text-muted mb-2 d-block"></i>
                <p class="text-muted mb-2">Para leer eventos remotos agrega la IP del equipo.</p>
                <?php if (Auth::canEdit()): ?>
                <a href="<?= APP_URL ?>/assets/<?= $asset['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-pen me-1"></i>Editar y agregar IP
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historial de cambios -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de cambios</h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($history)): ?>
                    <div class="empty-state p-4">
                        <i class="fa-solid fa-inbox"></i>
                        <p>Sin cambios registrados</p>
                    </div>
                <?php else: ?>
                    <ul class="timeline-list">
                        <?php foreach ($history as $h): ?>
                        <li class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <strong><?= htmlspecialchars($h['campo']) ?></strong>
                                <?php if ($h['valor_anterior']): ?>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($h['valor_anterior']) ?>
                                        → <?= htmlspecialchars($h['valor_nuevo'] ?? '') ?>
                                    </span>
                                <?php endif; ?>
                                <small class="text-muted d-block">
                                    <?= htmlspecialchars($h['usuario_nombre'] ?? '—') ?>
                                    &nbsp;·&nbsp; <?= $h['fecha'] ?>
                                </small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Columna derecha -->
    <div class="col-lg-4">

        <!-- QR Code -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-qrcode me-2"></i>Código QR</h2>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($asset['qr_url'])): ?>
                    <div class="qr-container mb-3" id="qrContainer"></div>
                    <p class="small text-muted mb-2">
                        <code><?= htmlspecialchars($asset['qr_token']) ?></code>
                    </p>
                <?php else: ?>
                    <p class="text-muted mb-3">Sin código QR generado</p>
                <?php endif; ?>
                <?php if (Auth::canEdit()): ?>
                <form method="POST" action="<?= APP_URL ?>/qr/generate/<?= $asset['id'] ?>">
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fa-solid fa-qrcode me-2"></i>
                        <?= !empty($asset['qr_url']) ? 'Regenerar QR' : 'Generar QR' ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones de estado -->
        <?php if (Auth::canEdit() && $asset['estado'] !== 'baja'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title"><i class="fa-solid fa-bolt me-2"></i>Acciones rápidas</h2>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="<?= APP_URL ?>/maintenance/create?asset_id=<?= $asset['id'] ?>"
                   class="btn btn-outline-warning">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i>Registrar mantenimiento
                </a>
                <?php if (!empty($asset['ip_address'])): ?>
                <a href="<?= APP_URL ?>/assets/<?= $asset['id'] ?>/rdp" class="btn btn-outline-info w-100">
                    <i class="fa-brands fa-windows me-2"></i>Descargar conexión RDP
                </a>
                <?php endif; ?>
<a href="<?= APP_URL ?>/scanner" onclick="document.querySelector('[name=ip]').value='<?= htmlspecialchars($asset['ip_address']) ?>'"
                   class="btn btn-outline-info">
                    <i class="fa-solid fa-radar me-2"></i>Analizar con escáner
                </a>
                <?php if (Auth::isAdmin()): ?>
                <form method="POST" action="<?= APP_URL ?>/assets/<?= $asset['id'] ?>/edit"
                      onsubmit="return confirm('¿Dar de baja este activo?')">
                    <input type="hidden" name="estado" value="baja">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="fa-solid fa-ban me-2"></i>Dar de baja
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<script>
// Indicador de carga al conectar
document.getElementById('formEventosForm')?.addEventListener('submit', function() {
    document.getElementById('btnConectar').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Conectando...';
    document.getElementById('btnConectar').disabled = true;
});
</script>
