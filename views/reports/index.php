<div class="row g-4">

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3 fs-1 text-primary"><i class="fa-solid fa-server"></i></div>
                <h3 class="h5 fw-semibold">Inventario general</h3>
                <p class="text-muted small">Listado completo de todos los activos con filtros por tipo, estado y departamento.</p>
                <a href="<?= APP_URL ?>/reports/assets" class="btn btn-outline-primary mt-2">
                    Ver reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3 fs-1 text-warning"><i class="fa-solid fa-shield-halved"></i></div>
                <h3 class="h5 fw-semibold">Garantías por vencer</h3>
                <p class="text-muted small">Activos con garantía próxima a expirar en los próximos 30, 60 o 90 días.</p>
                <a href="<?= APP_URL ?>/reports/warranty" class="btn btn-outline-warning mt-2">
                    Ver reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3 fs-1 text-success"><i class="fa-solid fa-file-csv"></i></div>
                <h3 class="h5 fw-semibold">Exportar a CSV</h3>
                <p class="text-muted small">Descarga el inventario completo en formato CSV compatible con Excel.</p>
                <a href="<?= APP_URL ?>/reports/export" class="btn btn-outline-success mt-2">
                    <i class="fa-solid fa-download me-1"></i>Descargar
                </a>
            </div>
        </div>
    </div>

</div>
