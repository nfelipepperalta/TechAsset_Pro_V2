<?php
// ============================================================
//  repositories/DepartmentRepository.php
//  Acceso a datos — tabla dbo.department
// ============================================================

namespace Repositories;

class DepartmentRepository extends BaseRepository
{
    protected string $table = 'department';

    // Departamentos con nombre del responsable
    public function findAllWithResponsable(): array
    {
        $sql = "SELECT d.*, u.nombre AS responsable_nombre
                FROM dbo.department d
                LEFT JOIN dbo.[user] u ON d.responsable_id = u.id
                ORDER BY d.nombre";
        return $this->query($sql);
    }

    // Lista plana para select/combo
    public function findForSelect(): array
    {
        $sql = "SELECT id, nombre, sede FROM dbo.department ORDER BY nombre";
        return $this->query($sql);
    }
}
