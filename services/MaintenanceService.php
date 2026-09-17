<?php
// ============================================================
//  services/MaintenanceService.php
//  Lógica de negocio — mantenimientos de activos
// ============================================================

namespace Services;

use Repositories\MaintenanceRepository;
use Repositories\AssetHistoryRepository;
use Repositories\AssetRepository;

class MaintenanceService
{
    private MaintenanceRepository $mainRepo;
    private AssetHistoryRepository $historyRepo;
    private AssetRepository       $assetRepo;

    public function __construct()
    {
        $this->mainRepo  = new MaintenanceRepository();
        $this->assetRepo = new AssetRepository();
        $this->historyRepo = new AssetHistoryRepository();
    }

    // Mantenimientos de un activo
    public function getByAsset(int $assetId): array
    {
        return $this->mainRepo->findByAsset($assetId);
    }

    // Próximos mantenimientos (para dashboard y alertas)
    public function getUpcoming(int $days = 30): array
    {
        return $this->mainRepo->findUpcoming($days);
    }

    // Registrar un mantenimiento
    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Verificar que el activo existe
        if (!$this->assetRepo->exists((int)$data['asset_id'])) {
            return ['success' => false, 'message' => 'Activo no encontrado.'];
        }

        $id = $this->mainRepo->insert([
            'asset_id'              => (int)$data['asset_id'],
            'tipo'                  => $data['tipo'],
            'descripcion'           => trim($data['descripcion']),
            'fecha'                 => $data['fecha'],
            'tecnico'               => trim($data['tecnico']),
            'costo'                 => !empty($data['costo'])    ? (float)$data['costo'] : null,
            'proximo_mantenimiento' => !empty($data['proximo'])  ? $data['proximo']      : null,
            'estado'                => $data['estado']           ?? 'completado',
        ]);

        // Registrar en historial del activo
        $userId = \Core\Auth::user()['id'];
        $detalle = ucfirst($data['tipo'] ?? 'mantenimiento') . ' — ' . trim($data['descripcion'] ?? '');
        $this->historyRepo->registrar(
            (int)$data['asset_id'], 'mantenimiento', null, substr($detalle, 0, 200), $userId
        );

        // Si el activo estaba en mantenimiento y se completó, volver a activo
        if (($data['estado'] ?? '') === 'completado') {
            $asset = $this->assetRepo->findById((int)$data['asset_id']);
            if ($asset && $asset['estado'] === 'mantenimiento') {
                $this->assetRepo->update((int)$data['asset_id'], ['estado' => 'activo']);
            }
        }

        // Si el mantenimiento comienza, poner activo en estado mantenimiento
        if (($data['estado'] ?? '') === 'en_proceso') {
            $this->assetRepo->update((int)$data['asset_id'], ['estado' => 'mantenimiento']);
        }

        return ['success' => true, 'id' => $id];
    }

    // Actualizar mantenimiento
    public function update(int $id, array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->mainRepo->update($id, [
            'tipo'                  => $data['tipo'],
            'descripcion'           => trim($data['descripcion']),
            'fecha'                 => $data['fecha'],
            'tecnico'               => trim($data['tecnico']),
            'costo'                 => !empty($data['costo'])   ? (float)$data['costo'] : null,
            'proximo_mantenimiento' => !empty($data['proximo']) ? $data['proximo']      : null,
            'estado'                => $data['estado']          ?? 'completado',
        ]);

        return ['success' => true];
    }

    // Eliminar mantenimiento
    public function delete(int $id): array
    {
        $ok = $this->mainRepo->delete($id);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'message' => 'No se pudo eliminar el mantenimiento.'];
    }

    // Costo total de mantenimientos de un activo
    public function totalCostByAsset(int $assetId): float
    {
        return $this->mainRepo->totalCostByAsset($assetId);
    }

    // Validación
    private function validate(array $data): array
    {
        $errors = [];
        $tipos  = ['preventivo', 'correctivo', 'actualizacion', 'inspeccion'];
        $estados = ['programado', 'en_proceso', 'completado', 'cancelado'];

        if (empty($data['asset_id']))    $errors['asset_id']    = 'El activo es requerido.';
        if (empty($data['tipo']) || !in_array($data['tipo'], $tipos)) {
            $errors['tipo'] = 'El tipo de mantenimiento no es válido.';
        }
        if (empty($data['descripcion'])) $errors['descripcion'] = 'La descripción es requerida.';
        if (empty($data['fecha']))       $errors['fecha']        = 'La fecha es requerida.';
        if (empty($data['tecnico']))     $errors['tecnico']      = 'El técnico es requerido.';
        if (!empty($data['estado']) && !in_array($data['estado'], $estados)) {
            $errors['estado'] = 'El estado no es válido.';
        }

        return $errors;
    }
}
