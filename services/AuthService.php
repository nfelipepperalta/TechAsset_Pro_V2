<?php
// ============================================================
//  services/AuthService.php
//  Lógica de autenticación — login, logout, registro
// ============================================================

namespace Services;

use Repositories\UserRepository;
use Core\Auth;

class AuthService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    // Intentar login — retorna array con resultado
    public function login(string $email, string $password): array
    {
        // Validaciones básicas
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email y contraseña son requeridos.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El email no tiene un formato válido.'];
        }

        // Buscar usuario activo por email
        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        // Verificar contraseña con bcrypt
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        // Actualizar último login
        $this->userRepo->updateLastLogin($user['id']);

        // Guardar en sesión (sin el hash)
        Auth::login($user);

        return ['success' => true, 'user' => $user];
    }

    // Cerrar sesión
    public function logout(): void
    {
        Auth::logout();
    }

    // Crear usuario (usado por admin)
    public function createUser(array $data): array
    {
        // Validar campos requeridos
        $required = ['nombre', 'email', 'password', 'rol'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo {$field} es requerido."];
            }
        }

        // Validar email único
        if ($this->userRepo->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'El email ya está registrado.'];
        }

        // Validar rol
        if (!in_array($data['rol'], ROLES)) {
            return ['success' => false, 'message' => 'Rol no válido.'];
        }

        // Hash de contraseña
        $id = $this->userRepo->insert([
            'nombre'        => $data['nombre'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
            'rol'           => $data['rol'],
            'department_id' => $data['department_id'] ?? null,
            'activo'        => 1,
        ]);

        return ['success' => true, 'id' => $id];
    }

    // Cambiar contraseña
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
        }

        $this->userRepo->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
        ]);

        return ['success' => true, 'message' => 'Contraseña actualizada correctamente.'];
    }
}
