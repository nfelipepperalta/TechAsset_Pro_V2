<?php
// ============================================================
//  services/ReportService.php
//  Lógica de negocio — generación de reportes y exportación
// ============================================================

namespace Services;

use Repositories\AssetRepository;
use Repositories\MaintenanceRepository;

class ReportService
{
    private AssetRepository       $assetRepo;
    private MaintenanceRepository $mainRepo;

    public function __construct()
    {
        $this->assetRepo = new AssetRepository();
        $this->mainRepo  = new MaintenanceRepository();
    }

    // Reporte de inventario general con filtros
    public function getInventory(array $filters = []): array
    {
        $result = $this->assetRepo->findAllWithDetails($filters, 1, 9999);
        return $result['data'];
    }

    // Reporte de garantías por vencer
    public function getWarrantyReport(int $days = 90): array
    {
        return $this->assetRepo->findWarrantyExpiringSoon($days);
    }

    // Exportar inventario a CSV (retorna string del contenido)
    public function exportToCsv(array $filters = []): string
    {
        $assets = $this->getInventory($filters);

        $headers = [
            'ID', 'Nombre', 'Tipo', 'Marca', 'Modelo', 'Serial',
            'Estado', 'Ubicación', 'Fecha Compra', 'Garantía Hasta',
            'Valor', 'Asignado A', 'Departamento',
        ];

        $output  = implode(',', $headers) . "\n";

        foreach ($assets as $a) {
            $row = [
                $a['id'],
                '"' . str_replace('"', '""', $a['nombre'])              . '"',
                '"' . str_replace('"', '""', $a['tipo_nombre'] ?? '')   . '"',
                '"' . str_replace('"', '""', $a['marca'])               . '"',
                '"' . str_replace('"', '""', $a['modelo'])              . '"',
                $a['serial']               ?? '',
                $a['estado'],
                '"' . str_replace('"', '""', $a['ubicacion'])           . '"',
                $a['fecha_compra'],
                $a['garantia_hasta']       ?? '',
                $a['valor']                ?? '',
                '"' . str_replace('"', '""', $a['usuario_nombre'] ?? '') . '"',
                '"' . str_replace('"', '""', $a['departamento_nombre'] ?? '') . '"',
            ];
            $output .= implode(',', $row) . "\n";
        }

        return $output;
    }

    // Enviar CSV como descarga al navegador
    public function downloadCsv(array $filters = []): never
    {
        $filename = 'techasset_inventario_' . date('Ymd_His') . '.csv';
        $content  = $this->exportToCsv($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // BOM para que Excel abra correctamente con UTF-8
        echo "\xEF\xBB\xBF" . $content;
        exit;
    }
}
