<?php
// ============================================================
//  repositories/MaintenanceRepository.php
//  Acceso a datos — tabla dbo.maintenance
// ============================================================

namespace Repositories;

class MaintenanceRepository extends BaseRepository
{
    protected string $table = 'maintenance';

    // Mantenimientos de un activo ordenados por fecha desc
    public function findByAsset(int $assetId): array
    {
        $sql = "SELECT * FROM dbo.maintenance
                WHERE asset_id = :asset_id
                ORDER BY fecha DESC";
        return $this->query($sql, ['asset_id' => $assetId]);
    }

    // Próximos mantenimientos programados (próximos N días)
    public function findUpcoming(int $days = 30): array
    {
        $sql = "SELECT
                    m.*,
                    a.nombre        AS asset_nombre,
                    a.serial        AS asset_serial,
                    at.nombre       AS tipo_nombre,
                    d.nombre        AS departamento_nombre,
                    DATEDIFF(DAY, CAST(GETUTCDATE() AS DATE), m.fecha) AS dias_restantes
                FROM dbo.maintenance m
                JOIN dbo.asset       a  ON m.asset_id       = a.id
                JOIN dbo.asset_type  at ON a.asset_type_id  = at.id
                LEFT JOIN dbo.department d ON a.department_id = d.id
                WHERE a.estado <> 'baja'
                  AND m.fecha >= DATEADD(DAY, -7, CAST(GETUTCDATE() AS DATE))
                  AND m.fecha <= DATEADD(DAY, CAST(:days AS INT), CAST(GETUTCDATE() AS DATE))
                ORDER BY m.fecha ASC";

        return $this->query($sql, ['days' => $days]);
    }

    // Último mantenimiento registrado para un activo
    public function findLastByAsset(int $assetId): ?array
    {
        $sql = "SELECT TOP 1 * FROM dbo.maintenance
                WHERE asset_id = :asset_id
                ORDER BY fecha DESC";
        return $this->queryOne($sql, ['asset_id' => $assetId]);
    }

    // Costo total de mantenimientos de un activo
    public function totalCostByAsset(int $assetId): float
    {
        $sql = "SELECT SUM(ISNULL(costo, 0)) AS total
                FROM dbo.maintenance
                WHERE asset_id = :asset_id";
        $row = $this->queryOne($sql, ['asset_id' => $assetId]);
        return (float)($row['total'] ?? 0);
    }
}
