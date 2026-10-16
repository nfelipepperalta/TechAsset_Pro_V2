<?php
// ============================================================
//  repositories/UserRepository.php
//  Acceso a datos — tabla dbo.[user]
// ============================================================

namespace Repositories;

class UserRepository extends BaseRepository
{
    protected string $table = '[user]';

    // Buscar usuario por email (para login)
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM dbo.[user] WHERE email = :email AND activo = 1";
        return $this->queryOne($sql, ['email' => $email]);
    }

    // Listar usuarios con nombre de departamento
    public function findAllWithDepartment(): array
    {
        $sql = "SELECT u.*, d.nombre AS departamento_nombre
                FROM dbo.[user] u
                LEFT JOIN dbo.department d ON u.department_id = d.id
                ORDER BY u.nombre";
        return $this->query($sql);
    }

    // Usuarios activos para asignar a activos (select/combo)
    public function findForSelect(): array
    {
        $sql = "SELECT id, nombre, email, rol
                FROM dbo.[user]
                WHERE activo = 1
                ORDER BY nombre";
        return $this->query($sql);
    }

    // Actualizar fecha de último login
    public function updateLastLogin(int $id): void
    {
        $sql = "UPDATE dbo.[user] SET last_login = SYSUTCDATETIME() WHERE id = :id";
        $this->execute($sql, ['id' => $id]);
    }

    // Verificar si el email ya existe (para validación en registro)
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) AS total FROM dbo.[user] WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId) {
            $sql           .= " AND id <> :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $row = $this->queryOne($sql, $params);
        return (int)($row['total'] ?? 0) > 0;
    }
}
