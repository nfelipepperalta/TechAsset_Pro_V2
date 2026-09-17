<?php
// ============================================================
//  repositories/AlertRepository.php
//  Acceso a datos — tabla dbo.alert
// ============================================================

namespace Repositories;

class AlertRepository extends BaseRepository
{
    protected string $table = 'alert';

    // Alertas pendientes para un usuario (o globales si user_id es null)
    public function findPendingByUser(int $userId): array
    {
        $sql = "SELECT
                    al.*,
                    a.nombre  AS activo_nombre,
                    a.serial  AS activo_serial
                FROM dbo.alert al
                JOIN dbo.asset a ON al.asset_id = a.id
                WHERE al.estado = 'pendiente'
                  AND (al.user_id = :user_id OR al.user_id IS NULL)
                ORDER BY al.fecha_disparo DESC";

        return $this->query($sql, ['user_id' => $userId]);
    }

    // Contar alertas pendientes (para el badge del menú)
    public function countPendingByUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM dbo.alert
                WHERE estado = 'pendiente'
                  AND (user_id = :user_id OR user_id IS NULL)";

        $row = $this->queryOne($sql, ['user_id' => $userId]);
        return (int)($row['total'] ?? 0);
    }

    // Marcar alerta como vista
    public function markAsRead(int $id): bool
    {
        $sql = "UPDATE dbo.alert
                SET estado = 'vista', vista_at = SYSUTCDATETIME()
                WHERE id = :id";
        $stmt = $this->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Marcar alerta como resuelta
    public function markAsResolved(int $id): bool
    {
        $sql = "UPDATE dbo.alert SET estado = 'resuelta' WHERE id = :id";
        $stmt = $this->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // Verificar si ya existe una alerta activa del mismo tipo para el activo
    public function existsActive(int $assetId, string $tipo): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM dbo.alert
                WHERE asset_id = :asset_id
                  AND tipo     = :tipo
                  AND estado IN ('pendiente', 'vista')";

        $row = $this->queryOne($sql, ['asset_id' => $assetId, 'tipo' => $tipo]);
        return (int)($row['total'] ?? 0) > 0;
    }
}
