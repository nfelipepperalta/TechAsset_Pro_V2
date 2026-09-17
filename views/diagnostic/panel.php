<?php /* views/diagnostic/panel.php */ ?>

<!-- ── Controles ─────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h2 class="card-title mb-0">
            <i class="fa-solid fa-stethoscope me-2"></i>Lector de Diagnóstico
        </h2>
        <div class="d-flex align-items-center gap-2 flex-wrap">

            <input id="diagHost" type="text" class="form-control form-control-sm" 
                   style="width:150px" value="192.168.0.143" placeholder="IP del equipo">
            <select id="diagOS" class="form-select form-select-sm" style="width:100px">
                <option value="windows">Windows</option>
                <option value="linux">Linux</option>
            </select>
            <select id="diagHours" class="form-select form-select-sm" style="width:130px">
                <option value="1">Última hora</option>
                <option value="6">Últimas 6h</option>
                <option value="24" selected>Últimas 24h</option>
                <option value="48">Últimas 48h</option>
                <option value="168">Última semana</option>
            </select>

            <select id="diagLimit" class="form-select form-select-sm" style="width:110px">
                <option value="25">25 eventos</option>
                <option value="50" selected>50 eventos</option>
                <option value="100">100 eventos</option>
            </select>

            <button id="btnDiagnostic" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-magnifying-glass-chart me-1"></i>
                Leer Diagnóstico
            </button>
        </div>
    </div>

    <!-- Estado inicial -->
    <div id="diagIdle" class="card-body text-center py-5">
        <i class="fa-solid fa-satellite-dish fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted mb-0">Haz clic en <strong>Leer Diagnóstico</strong> para consultar los eventos de Windows y Linux.</p>
    </div>

    <!-- Spinner de carga -->
    <div id="diagLoading" class="card-body text-center py-5 d-none">
        <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem"></div>
        <p class="text-muted mb-0" id="diagLoadingMsg">Conectando con los servidores…</p>
    </div>

    <!-- Resultado -->
    <div id="diagResult" class="d-none">

        <!-- Resumen de cabecera -->
        <div class="card-body border-bottom pb-3">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <div id="sumWinTotal" class="fs-3 fw-bold text-primary">—</div>
                        <div class="small text-muted"><i class="fa-brands fa-windows me-1"></i>Windows</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <div id="sumLinTotal" class="fs-3 fw-bold text-success">—</div>
                        <div class="small text-muted"><i class="fa-brands fa-linux me-1"></i>Linux</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <div id="sumErrors" class="fs-3 fw-bold text-danger">—</div>
                        <div class="small text-muted">Errores</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-center">
                        <div id="sumWarnings" class="fs-3 fw-bold text-warning">—</div>
                        <div class="small text-muted">Advertencias</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Windows / Linux -->
        <div class="card-body">

            <!-- Filtro por nivel -->
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <span class="small text-muted me-1">Filtrar:</span>
                <button class="btn btn-sm btn-outline-secondary nivel-filter active" data-nivel="all">Todos</button>
                <button class="btn btn-sm btn-outline-danger   nivel-filter" data-nivel="danger">Errores</button>
                <button class="btn btn-sm btn-outline-warning  nivel-filter" data-nivel="warning">Advertencias</button>
                <button class="btn btn-sm btn-outline-info     nivel-filter" data-nivel="info">Info</button>
            </div>

            <ul class="nav nav-tabs mb-3" id="diagTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-tab="windows">
                        <i class="fa-brands fa-windows me-1"></i>Windows
                        <span id="tabBadgeWin" class="badge bg-secondary ms-1">0</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="linux">
                        <i class="fa-brands fa-linux me-1"></i>Linux
                        <span id="tabBadgeLin" class="badge bg-secondary ms-1">0</span>
                    </button>
                </li>
            </ul>

            <!-- Alertas de error de conexión -->
            <div id="diagWinError" class="alert alert-warning d-none">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>Windows:</strong> <span id="diagWinErrorMsg"></span>
                <div class="mt-2 small">
                    Verifica que WinRM está habilitado en <code>192.168.0.79</code>:
                    <code>Enable-PSRemoting -Force</code>
                </div>
            </div>
            <div id="diagLinError" class="alert alert-warning d-none">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>Linux:</strong> <span id="diagLinErrorMsg"></span>
            </div>

            <!-- Tabla de eventos -->
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle" id="diagTable">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:145px">Fecha/Hora</th>
                            <th style="width:90px">Origen</th>
                            <th style="width:80px">Nivel</th>
                            <th style="width:80px">Event ID</th>
                            <th>Fuente</th>
                            <th>Mensaje</th>
                        </tr>
                    </thead>
                    <tbody id="diagTableBody">
                    </tbody>
                </table>
                <p id="diagEmpty" class="text-center text-muted py-3 d-none">
                    No hay eventos para mostrar con el filtro seleccionado.
                </p>
            </div>

        </div><!-- /card-body -->
    </div><!-- /diagResult -->
</div><!-- /card -->


<script>
(function () {
    // ── Leer parámetros GET ──────────────────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('host')) {
        document.getElementById('diagHost').value = urlParams.get('host');
    }
    if (urlParams.get('os')) {
        document.getElementById('diagOS').value = urlParams.get('os');
    }
    // Auto-ejecutar si viene con parámetros
    if (urlParams.get('host')) {
        setTimeout(() => document.getElementById('btnDiagnostic').click(), 500);
    }

    // ── Estado ────────────────────────────────────────────────
    let todosEventos = [];    // todos los eventos cargados
    let tabActual    = 'windows';
    let nivelActual  = 'all';

    // ── Elementos DOM ────────────────────────────────────────
    const btn          = document.getElementById('btnDiagnostic');
    const idle         = document.getElementById('diagIdle');
    const loading      = document.getElementById('diagLoading');
    const loadingMsg   = document.getElementById('diagLoadingMsg');
    const result       = document.getElementById('diagResult');
    const tableBody    = document.getElementById('diagTableBody');
    const emptyMsg     = document.getElementById('diagEmpty');

    const sumWinTotal  = document.getElementById('sumWinTotal');
    const sumLinTotal  = document.getElementById('sumLinTotal');
    const sumErrors    = document.getElementById('sumErrors');
    const sumWarnings  = document.getElementById('sumWarnings');

    const tabBadgeWin  = document.getElementById('tabBadgeWin');
    const tabBadgeLin  = document.getElementById('tabBadgeLin');
    const winError     = document.getElementById('diagWinError');
    const linError     = document.getElementById('diagLinError');
    const winErrorMsg  = document.getElementById('diagWinErrorMsg');
    const linErrorMsg  = document.getElementById('diagLinErrorMsg');

    // ── Botón principal ──────────────────────────────────────
    btn.addEventListener('click', () => leerDiagnostico());

    // ── Tabs ─────────────────────────────────────────────────
    document.querySelectorAll('[data-tab]').forEach(t => {
        t.addEventListener('click', function () {
            document.querySelectorAll('[data-tab]').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            tabActual = this.dataset.tab;
            renderTabla();
        });
    });

    // ── Filtros de nivel ─────────────────────────────────────
    document.querySelectorAll('.nivel-filter').forEach(f => {
        f.addEventListener('click', function () {
            document.querySelectorAll('.nivel-filter').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            nivelActual = this.dataset.nivel;
            renderTabla();
        });
    });

    // ── Función principal ────────────────────────────────────
    async function leerDiagnostico() {
        const hours = document.getElementById('diagHours').value;
        const limit = document.getElementById('diagLimit').value;

        // UI → loading
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Leyendo…';
        idle.classList.add('d-none');
        result.classList.add('d-none');
        loading.classList.remove('d-none');

        // Mensajes de progreso animados
        const msgs = [
            'Conectando con Windows 192.168.0.79…',
            'Leyendo Application Event Log…',
            'Leyendo System Event Log…',
            'Analizando logs de Linux…',
            'Consolidando eventos…',
        ];
        let mi = 0;
        loadingMsg.textContent = msgs[0];
        const interval = setInterval(() => {
            mi = (mi + 1) % msgs.length;
            loadingMsg.textContent = msgs[mi];
        }, 1800);

        try {
            const fd = new FormData();
            const os    = document.getElementById('diagOS').value;
            fd.append('host',  document.getElementById('diagHost').value || '192.168.0.143');
            fd.append('os',    os);
            fd.append('hours', hours);
            fd.append('limit', limit);

            const resp = await fetch('<?= APP_URL ?>/diagnostic/read', {
                method: 'POST',
                body:   fd,
            });

            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const data = await resp.json();

            clearInterval(interval);
            procesarRespuesta(data);

        } catch (err) {
            clearInterval(interval);
            loading.classList.add('d-none');
            idle.classList.remove('d-none');
            alert('Error al consultar el diagnóstico: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-magnifying-glass-chart me-1"></i>Leer Diagnóstico';
        }
    }

    // ── Procesar JSON de respuesta ───────────────────────────
    function procesarRespuesta(data) {
        loading.classList.add('d-none');

        // Consolidar eventos
        const evWin = data.windows?.eventos ?? [];
        const evLin = data.linux?.eventos   ?? [];
        todosEventos = [...evWin, ...evLin];

        // Resumen numérico
        const errores    = todosEventos.filter(e => e.badge === 'danger').length;
        const advertencias = todosEventos.filter(e => e.badge === 'warning').length;

        sumWinTotal.textContent = data.windows?.total ?? 0;
        sumLinTotal.textContent = data.linux?.total   ?? 0;
        sumErrors.textContent   = errores;
        sumWarnings.textContent = advertencias;

        tabBadgeWin.textContent = evWin.length;
        tabBadgeLin.textContent = evLin.length;

        // Errores de conexión
        if (data.windows?.error) {
            winError.classList.remove('d-none');
            winErrorMsg.textContent = data.windows.error;
        } else {
            winError.classList.add('d-none');
        }
        if (data.linux?.error) {
            linError.classList.remove('d-none');
            linErrorMsg.textContent = data.linux.error;
        } else {
            linError.classList.add('d-none');
        }

        result.classList.remove('d-none');
        renderTabla();
    }

    // ── Renderizar tabla según tab y filtro activos ──────────
    function renderTabla() {
        let eventos = todosEventos.filter(e => e.origin === tabActual);

        if (nivelActual !== 'all') {
            eventos = eventos.filter(e => e.badge === nivelActual);
        }

        if (eventos.length === 0) {
            tableBody.innerHTML = '';
            emptyMsg.classList.remove('d-none');
            return;
        }
        emptyMsg.classList.add('d-none');

        tableBody.innerHTML = eventos.map(e => `
            <tr class="event-row-${e.badge}">
                <td class="text-nowrap small">${formatFecha(e.time)}</td>
                <td>
                    <span class="badge bg-secondary">
                        ${e.origin === 'windows'
                            ? '<i class="fa-brands fa-windows me-1"></i>Win'
                            : '<i class="fa-brands fa-linux me-1"></i>Linux'}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${e.badge}">${e.level}</span>
                </td>
                <td class="small text-muted">${e.event_id}</td>
                <td class="small">${escHtml(e.source)}</td>
                <td class="small text-truncate" style="max-width:380px" title="${escHtml(e.message)}">
                    ${escHtml(e.message)}
                </td>
            </tr>
        `).join('');
    }

    function formatFecha(raw) {
        if (!raw || raw === '-') return '-';
        // Formato WMI: 20260512025354.156875-000
        const m = String(raw).match(/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/);
        if (m) return `${m[1]}-${m[2]}-${m[3]} ${m[4]}:${m[5]}:${m[6]}`;
        return raw;
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<style>
.event-row-danger  { border-left: 3px solid #dc3545; }
.event-row-warning { border-left: 3px solid #ffc107; }
.event-row-info    { border-left: 3px solid #0dcaf0; }
#diagTable tbody tr:hover { background: rgba(var(--bs-primary-rgb), .05); }
</style>
