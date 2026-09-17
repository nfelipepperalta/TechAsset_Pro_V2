<?php
// ============================================================
//  services/UserService.php
//  Lógica de negocio — gestión de usuarios
// ============================================================

namespace Services;

use Repositories\UserRepository;
use Repositories\DepartmentRepository;

class UserService
{
    private UserRepository       $userRepo;
    private DepartmentRepository $deptRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->deptRepo = new DepartmentRepository();
    }

    // Listar todos los usuarios con su departamento
    public function getAll(): array
    {
        return $this->userRepo->findAllWithDepartment();
    }

    // Obtener usuario por ID
    public function getById(int $id): ?array
    {
        return $this->userRepo->findById($id);
    }

    // Crear usuario
    public function create(array $data): array
    {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($this->userRepo->emailExists($data['email'])) {
            return ['success' => false, 'errors' => ['email' => 'El email ya está en uso.']];
        }

        $id = $this->userRepo->insert([
            'nombre'        => trim($data['nombre']),
            'email'         => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
            'rol'           => $data['rol'],
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
            'activo'        => 1,
        ]);

        return ['success' => true, 'id' => $id];
    }

    // Actualizar usuario
    public function update(int $id, array $data): array
    {
        $errors = $this->validate($data, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($this->userRepo->emailExists($data['email'], $id)) {
            return ['success' => false, 'errors' => ['email' => 'El email ya está en uso.']];
        }

        $payload = [
            'nombre'        => trim($data['nombre']),
            'email'         => strtolower(trim($data['email'])),
            'rol'           => $data['rol'],
            'department_id' => !empty($data['department_id']) ? (int)$data['department_id'] : null,
        ];

        // Solo actualizar contraseña si se envió una nueva
        if (!empty($data['password'])) {
            $payload['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        }

        $this->userRepo->update($id, $payload);
        return ['success' => true];
    }

    // Desactivar usuario (soft delete)
    public function deactivate(int $id): array
    {
        $user = $this->userRepo->findById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }

        $this->userRepo->update($id, ['activo' => 0]);
        return ['success' => true];
    }

    // Datos para formularios (combos)
    public function getFormData(): array
    {
        return [
            'departamentos' => $this->deptRepo->findForSelect(),
            'roles'         => ROLES,
        ];
    }

    // Validación interna
    private function validate(array $data, ?int $id = null): array
    {
        $errors = [];

        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es requerido.';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'El email es requerido.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido.';
        }

        if (!in_array($data['rol'] ?? '', ROLES)) {
            $errors['rol'] = 'El rol no es válido.';
        }

        // Contraseña requerida solo en creación
        if (!$id && empty($data['password'])) {
            $errors['password'] = 'La contraseña es requerida.';
        }

        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        return $errors;
    }
}
