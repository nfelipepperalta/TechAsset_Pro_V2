<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">

    <!-- Escanear IP individual -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Analizar equipo por IP
                </h2>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    Escanea un equipo específico para obtener hostname, sistema operativo, 
                    puertos abiertos, MAC address y un análisis completo de seguridad con IA.
                </p>

                <form method="POST" action="<?= APP_URL ?>/scanner/ip" id="formIP">
                    <div class="mb-3">
                        <label class="form-label required">Dirección IP</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-network-wired"></i></span>
                            <input type="text" name="ip" class="form-control"
                                   placeholder="192.168.0.100" required
                                   pattern="^(\d{1,3}\.){3}\d{1,3}$">
                        </div>
                        <small class="text-muted">Ej: 192.168.0.1, 10.0.0.50</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Intensidad del escaneo</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="intensidad"
                                       id="rapido" value="rapido">
                                <label class="form-check-label" for="rapido">
                                    <strong>Rápido</strong>
                                    <small class="text-muted d-block">Top 100 puertos (~30s)</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="intensidad"
                                       id="normal" value="normal" checked>
                                <label class="form-check-label" for="normal">
                                    <strong>Normal</strong>
                                    <small class="text-muted d-block">Top 10,000 puertos (~2min)</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="intensidad"
                                       id="completo" value="completo">
                                <label class="form-check-label" for="completo">
                                    <strong>Completo</strong>
                                    <small class="text-muted d-block">Todos los puertos (~10min)</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="btnScanIP">
                        <i class="fa-solid fa-radar me-2"></i>Analizar equipo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Escanear rango -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-sitemap me-2"></i>Descubrir equipos en la red
                </h2>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    Escanea un rango de IPs para descubrir todos los equipos activos 
                    en la red. Útil para hacer inventario de dispositivos desconocidos.
                </p>

                <form method="POST" action="<?= APP_URL ?>/scanner/range" id="formRange">
                    <div class="mb-3">
                        <label class="form-label required">Rango CIDR</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-network-wired"></i></span>
                            <input type="text" name="range" class="form-control"
                                   placeholder="192.168.0.0/24" required>
                        </div>
                        <small class="text-muted">
                            Ej: 192.168.0.0/24 (254 hosts), 10.0.0.0/16 (65,534 hosts)
                        </small>
                    </div>

                    <div class="alert alert-info py-2 small">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        El escaneo de rango solo detecta equipos activos. Para análisis completo
                        de seguridad usa "Analizar equipo por IP".
                    </div>

                    <button type="submit" class="btn btn-outline-primary w-100 mt-2" id="btnScanRange">
                        <i class="fa-solid fa-sitemap me-2"></i>Descubrir equipos
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Indicador de carga -->
<div id="loadingOverlay" class="d-none">
    <div class="text-center py-5 mt-4">
        <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;"></div>
        <h4 id="loadingMsg">Escaneando...</h4>
        <p class="text-muted small" id="loadingDetail">Esto puede tomar varios minutos según la intensidad seleccionada.</p>
    </div>
</div>

<script>
document.getElementById('formIP').addEventListener('submit', function() {
    const intensidad = document.querySelector('input[name="intensidad"]:checked')?.value || 'normal';
    const tiempos = { rapido: '~30 segundos', normal: '~2 minutos', completo: '~10 minutos' };
    document.getElementById('loadingOverlay').classList.remove('d-none');
    document.getElementById('loadingMsg').textContent = 'Analizando equipo con IA...';
    document.getElementById('loadingDetail').textContent = `Escaneo ${intensidad}: ${tiempos[intensidad]}. Por favor espera.`;
    document.getElementById('btnScanIP').disabled = true;
});

document.getElementById('formRange').addEventListener('submit', function() {
    document.getElementById('loadingOverlay').classList.remove('d-none');
    document.getElementById('loadingMsg').textContent = 'Descubriendo equipos en la red...';
    document.getElementById('loadingDetail').textContent = 'Escaneando el rango de IPs. Esto puede tardar 1-2 minutos.';
    document.getElementById('btnScanRange').disabled = true;
});
</script>
