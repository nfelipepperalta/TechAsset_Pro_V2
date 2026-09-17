<?php
// ============================================================
//  services/AlertService.php
//  Lógica de negocio — alertas automáticas del sistema
// ============================================================

namespace Services;

use Repositories\AlertRepository;
use Repositories\AssetRepository;
use Repositories\MaintenanceRepository;

class AlertService
{
    private AlertRepository       $alertRepo;
    private AssetRepository       $assetRepo;
    private MaintenanceRepository $mainRepo;

    public function __construct()
    {
        $this->alertRepo = new AlertRepository();
        $this->assetRepo = new AssetRepository();
        $this->mainRepo  = new MaintenanceRepository();
    }

    // Alertas pendientes del usuario actual
    public function getPendingByUser(int $userId): array
    {
        return $this->alertRepo->findPendingByUser($userId);
    }

    // Contador para el badge del menú
    public function countPending(int $userId): int
    {
        return $this->alertRepo->countPendingByUser($userId);
    }

    // Marcar como vista
    public function markAsRead(int $id): array
    {
        $ok = $this->alertRepo->markAsRead($id);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'message' => 'No se pudo actualizar la alerta.'];
    }

    // Marcar como resuelta
    public function resolve(int $id): array
    {
        $ok = $this->alertRepo->markAsResolved($id);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'message' => 'No se pudo resolver la alerta.'];
    }

    // ── Generación automática de alertas ─────────────────────
    // Llamar este método desde un cron job diario

    public function generarAlertas(): array
    {
        $creadas = 0;
        $creadas += $this->alertarGarantiasProximas();
        $creadas += $this->alertarMantenimientosProximos();

        return ['success' => true, 'alertas_creadas' => $creadas];
    }

    // Crear alertas para activos con garantía en los próximos 30 días
    private function alertarGarantiasProximas(): int
    {
        $activos = $this->assetRepo->findWarrantyExpiringSoon(30);
        $creadas = 0;

        foreach ($activos as $activo) {
            if ($this->alertRepo->existsActive($activo['id'], 'garantia_vence')) {
                continue; // Ya tiene alerta activa
            }

            $dias = (int)$activo['dias_restantes'];
            $this->alertRepo->insert([
                'asset_id'     => $activo['id'],
                'user_id'      => null, // notifica a todos los admins
                'tipo'         => 'garantia_vence',
                'mensaje'      => "La garantía de \"{$activo['nombre']}\" vence en {$dias} días ({$activo['garantia_hasta']}).",
                'fecha_disparo' => date('Y-m-d H:i:s'),
                'estado'       => 'pendiente',
            ]);
            $creadas++;
        }

        return $creadas;
    }

    // Crear alertas para mantenimientos programados en los próximos 7 días
    private function alertarMantenimientosProximos(): int
    {
        $proximos = $this->mainRepo->findUpcoming(7);
        $creadas  = 0;

        foreach ($proximos as $mant) {
            if ($this->alertRepo->existsActive($mant['asset_id'], 'mantenimiento_proximo')) {
                continue;
            }

            $dias = (int)$mant['dias_restantes'];
            $this->alertRepo->insert([
                'asset_id'      => $mant['asset_id'],
                'user_id'       => null,
                'tipo'          => 'mantenimiento_proximo',
                'mensaje'       => "Mantenimiento programado para \"{$mant['activo_nombre']}\" en {$dias} días.",
                'fecha_disparo' => date('Y-m-d H:i:s'),
                'estado'        => 'pendiente',
            ]);
            $creadas++;
        }

        return $creadas;
    }
}
