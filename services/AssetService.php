<?php
// ============================================================
//  services/AssetService.php
//  Lógica de negocio — gestión de activos tecnológicos
// ============================================================

namespace Services;

use Repositories\AssetRepository;
use Repositories\AssetHistoryRepository;
use Repositories\AssetTypeRepository;
use Repositories\UserRepository;
use Repositories\DepartmentRepository;
use Core\Auth;

class AssetService
{
    private AssetRepository        $assetRepo;
    private AssetHistoryRepository $historyRepo;
    private AssetTypeRepository    $typeRepo;
    private UserRepository         $userRepo;
    private DepartmentRepository   $deptRepo;

    public function __construct()
    {
        $this->assetRepo   = new AssetRepository();
        $this->historyRepo = new AssetHistoryRepository();
        $this->typeRepo    = new AssetTypeRepository();
        $this->userRepo    = new UserRepository();
        $this->deptRepo    = new DepartmentRepository();
    }

    // Listar activos con filtros y paginación
    public function getAll(array $filters = [], int $page = 1): array
    {
        return $this->assetRepo->findAllWithDetails($filters, $page);
    }

    // Detalle completo de un activo
    public function getById(int $id): ?array
    {
        return $this->assetRepo->findByIdWithDetails($id);
    }

    // Crear activo
    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        try {
            $id = $this->assetRepo->insert($this->preparePayload($data));
        } catch (\PDOException $e) {
            $msg = str_contains($e->getMessage(), 'uq_asset_serial')
                ? 'El número de serie ya existe en otro activo.'
                : 'Error al guardar: ' . $e->getMessage();
            return ['success' => false, 'errors' => ['serial' => $msg]];
        }
        $this->historyRepo->registrar($id, 'creacion', null, 'activo', Auth::user()['id']);

        return ['success' => true, 'id' => $id];
    }

    // Actualizar activo y registrar cambios en historial
    public function update(int $id, array $data): array
    {
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $original = $this->assetRepo->findById($id);
        if (!$original) {
            return ['success' => false, 'message' => 'Activo no encontrado.'];
        }

        $payload = $this->preparePayload($data);
        $this->assetRepo->update($id, $payload);

        // Registrar en historial solo los campos que cambiaron
        $userId          = Auth::user()['id'];
        $camposAuditados = ['estado', 'user_id', 'department_id', 'ubicacion', 'garantia_hasta', 'ip_address'];

        foreach ($camposAuditados as $campo) {
            if (isset($payload[$campo]) && (string)$payload[$campo] !== (string)($original[$campo] ?? '')) {
                $this->historyRepo->registrar($id, $campo, $original[$campo], $payload[$campo], $userId);
            }
        }

        return ['success' => true];
    }

    // Cambiar estado del activo
    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        if (!in_array($nuevoEstado, ASSET_STATES)) {
            return ['success' => false, 'message' => 'Estado no válido.'];
        }

        $ok = $this->assetRepo->cambiarEstado($id, $nuevoEstado, Auth::user()['id']);
        if (!$ok) {
            return ['success' => false, 'message' => 'No se pudo actualizar el estado.'];
        }

        return ['success' => true];
    }

    // Dar de baja un activo
    public function darDeBaja(int $id): array
    {
        return $this->cambiarEstado($id, 'baja');
    }

    // Eliminar activo (solo admin)
    public function delete(int $id): array
    {
        if (!Auth::isAdmin()) {
            return ['success' => false, 'message' => 'No tienes permisos para eliminar activos.'];
        }

        $ok = $this->assetRepo->delete($id);
        return $ok
            ? ['success' => true]
            : ['success' => false, 'message' => 'No se pudo eliminar el activo.'];
    }

    // Historial de cambios de un activo
    public function getHistory(int $id): array
    {
        return $this->historyRepo->findByAsset($id);
    }

    // Datos para formularios (combos de tipo, usuario, departamento)
    public function getFormData(): array
    {
        return [
            'tipos'         => $this->typeRepo->findForSelect(),
            'usuarios'      => $this->userRepo->findForSelect(),
            'departamentos' => $this->deptRepo->findForSelect(),
            'estados'       => ASSET_STATES,
        ];
    }

    // Preparar payload limpio para insert/update
    private function preparePayload(array $data): array
    {
        return [
            'nombre'        => trim($data['nombre']),
            'asset_type_id' => (int)$data['asset_type_id'],
            'marca'         => trim($data['marca']),
            'modelo'        => trim($data['modelo']),
            'serial'        => !empty($data['serial'])        ? trim($data['serial'])   : null,
            'estado'        => $data['estado']                ?? 'activo',
            'ubicacion'     => trim($data['ubicacion']),
            'fecha_compra'  => $data['fecha_compra'],
            'garantia_hasta'=> !empty($data['garantia_hasta']) ? $data['garantia_hasta'] : null,
            'valor'         => !empty($data['valor'])          ? (float)$data['valor']  : null,
            'user_id'       => !empty($data['user_id'])        ? (int)$data['user_id']  : null,
            'department_id' => !empty($data['department_id'])  ? (int)$data['department_id'] : null,
            'notas'         => !empty($data['notas'])          ? trim($data['notas'])   : null,
            'ip_address'    => !empty($data['ip_address'])    ? trim($data['ip_address']) : null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
    }

    // Validación de campos requeridos
    private function validate(array $data, ?int $id = null): array
    {
        $errors = [];

        if (empty($data['nombre']))        $errors['nombre']        = 'El nombre es requerido.';
        if (empty($data['asset_type_id'])) $errors['asset_type_id'] = 'El tipo de activo es requerido.';
        if (empty($data['marca']))         $errors['marca']         = 'La marca es requerida.';
        if (empty($data['modelo']))        $errors['modelo']        = 'El modelo es requerido.';
        if (empty($data['ubicacion']))     $errors['ubicacion']     = 'La ubicación es requerida.';
        if (empty($data['fecha_compra']))  $errors['fecha_compra']  = 'La fecha de compra es requerida.';

        if (!empty($data['estado']) && !in_array($data['estado'], ASSET_STATES)) {
            $errors['estado'] = 'El estado no es válido.';
        }

        if (!empty($data['garantia_hasta']) && $data['garantia_hasta'] < $data['fecha_compra']) {
            $errors['garantia_hasta'] = 'La garantía no puede ser anterior a la fecha de compra.';
        }

        return $errors;
    }
}
