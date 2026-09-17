<?php
// ============================================================
//  repositories/AssetTypeRepository.php
//  Acceso a datos — tabla dbo.asset_type
// ============================================================

namespace Repositories;

class AssetTypeRepository extends BaseRepository
{
    protected string $table = 'asset_type';

    // Tipos agrupados por categoría
    public function findGroupedByCategoria(): array
    {
        $sql = "SELECT * FROM dbo.asset_type ORDER BY categoria, nombre";
        $rows = $this->query($sql);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['categoria']][] = $row;
        }
        return $grouped;
    }

    // Lista plana para select/combo
    public function findForSelect(): array
    {
        $sql = "SELECT id, nombre, categoria FROM dbo.asset_type ORDER BY nombre";
        return $this->query($sql);
    }
}
