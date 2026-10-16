<?php
// ============================================================
//  controllers/ReportController.php
//  Reportes y exportación de datos
// ============================================================

namespace Controllers;

use Services\ReportService;
use Core\Auth;
use Core\Response;

class ReportController
{
    private ReportService $reportService;

    public function __construct()
    {
        Auth::requireRole('auditor');
        $this->reportService = new ReportService();
    }

    // GET /reports
    public function index(): void
    {
        Response::layout('reports/index', [
            'title' => 'Reportes',
            'user'  => Auth::user(),
        ]);
    }

    // GET /reports/assets
    public function assets(): void
    {
        $filters = [
            'estado'        => $_GET['estado']        ?? '',
            'asset_type_id' => $_GET['asset_type_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
        ];

        $assets = $this->reportService->getInventory($filters);

        Response::layout('reports/assets', [
            'title'   => 'Reporte de Inventario',
            'assets'  => $assets,
            'filters' => $filters,
            'user'    => Auth::user(),
        ]);
    }

    // GET /reports/warranty
    public function warranty(): void
    {
        $days   = (int)($_GET['days'] ?? 90);
        $assets = $this->reportService->getWarrantyReport($days);

        Response::layout('reports/warranty', [
            'title'  => 'Garantías por Vencer',
            'assets' => $assets,
            'days'   => $days,
            'user'   => Auth::user(),
        ]);
    }

    // GET /reports/export  → descarga CSV
    public function export(): void
    {
        $filters = [
            'estado'        => $_GET['estado']        ?? '',
            'asset_type_id' => $_GET['asset_type_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
        ];

        $this->reportService->downloadCsv($filters);
    }
}
