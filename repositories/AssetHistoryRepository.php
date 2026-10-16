<?php
// ============================================================
//  repositories/AssetHistoryRepository.php
//  Acceso a datos — tabla dbo.asset_history
// ============================================================

namespace Repositories;

class AssetHistoryRepository extends BaseRepository
{
    protected string $table = 'asset_history';

    // Historial completo de un activo con nombre del usuario
    public function findByAsset(int $assetId): array
    {
        $sql = "SELECT h.*, u.nombre AS usuario_nombre
                FROM dbo.asset_history h
                LEFT JOIN dbo.[user] u ON h.modificado_por = u.id
                WHERE h.asset_id = :asset_id
                ORDER BY h.fecha DESC";

        return $this->query($sql, ['asset_id' => $assetId]);
    }

    // Registrar un cambio en el historial
    public function registrar(int $assetId, string $campo, mixed $anterior, mixed $nuevo, int $userId): int
    {
        return $this->insert([
            'asset_id'       => $assetId,
            'campo'          => $campo,
            'valor_anterior' => $anterior !== null ? (string)$anterior : null,
            'valor_nuevo'    => $nuevo    !== null ? (string)$nuevo    : null,
            'modificado_por' => $userId,
        ]);
    }
}
