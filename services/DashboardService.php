<?php
// ============================================================
//  services/DashboardService.php
//  Lógica de negocio — métricas y datos del dashboard
// ============================================================

namespace Services;

use Repositories\AssetRepository;
use Repositories\MaintenanceRepository;
use Repositories\AlertRepository;

class DashboardService
{
    private AssetRepository       $assetRepo;
    private MaintenanceRepository $mainRepo;
    private AlertRepository       $alertRepo;

    public function __construct()
    {
        $this->assetRepo = new AssetRepository();
        $this->mainRepo  = new MaintenanceRepository();
        $this->alertRepo = new AlertRepository();
    }

    // Todos los datos que necesita el dashboard en una sola llamada
    public function getData(int $userId): array
    {
        // Conteos por estado
        $porEstado   = $this->assetRepo->countByEstado();
        $estadoMap   = array_column($porEstado, 'total', 'estado');

        return [
            // Métricas principales
            'total_activos'       => array_sum($estadoMap),
            'activos_activos'     => (int)($estadoMap['activo']        ?? 0),
            'en_mantenimiento'    => (int)($estadoMap['mantenimiento']  ?? 0),
            'dados_de_baja'       => (int)($estadoMap['baja']           ?? 0),
            'valor_total'         => $this->assetRepo->valorTotalActivos(),

            // Alertas pendientes
            'alertas_pendientes'  => $this->alertRepo->countPendingByUser($userId),
            'alertas'             => $this->alertRepo->findPendingByUser($userId),

            // Garantías por vencer (30 días)
            'garantias_proximas'  => $this->assetRepo->findWarrantyExpiringSoon(30),

            // Próximos mantenimientos (7 días)
            'mantenimientos_proximos' => $this->mainRepo->findUpcoming(7),

            // Distribución por tipo (para gráfica)
            'distribucion_tipos'  => $this->assetRepo->countByTipo(),
        ];
    }
}
