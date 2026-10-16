<?php

namespace Repositories;

class TicketRepository extends BaseRepository
{
    protected string $table = 'ticket';

    // Obtener las solicitudes de un usuario de forma paginada para Azure SQL
    public function findByUserPaginated(int $userId, int $page = 1, int $perPage = DEFAULT_PAGE_SIZE): array
    {
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) AS total
                     FROM dbo.ticket
                     WHERE user_id = :user_id";

        $row = $this->queryOne($countSql, ['user_id' => $userId]);
        $total = (int)($row['total'] ?? 0);

        $sql = "SELECT *
                FROM dbo.ticket
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY";

        $rows = $this->query($sql, [
            'user_id' => $userId,
            'offset'  => (int)$offset,
            'perPage' => (int)$perPage,
        ]);

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    // Obtener todas las solicitudes de un usuario sin paginar
    public function findByUser(int $userId): array
    {
        $sql = "SELECT *
                FROM dbo.ticket
                WHERE user_id = :user_id
                ORDER BY created_at DESC";

        return $this->query($sql, ['user_id' => $userId]);
    }

    // Buscar un ticket específico que le pertenezca al usuario
    public function findByIdForUser(int $id, int $userId): ?array
    {
        $sql = "SELECT *
                FROM dbo.ticket
                WHERE id = :id
                  AND user_id = :user_id";

        return $this->queryOne($sql, [
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    // Cambiar el estado de un ticket (Abierto, Cerrado, etc.)
    public function updateStatus(int $id, string $estado): bool
    {
        $sql = "UPDATE dbo.ticket
                SET estado = :estado,
                    updated_at = SYSUTCDATETIME()
                WHERE id = :id";

        $stmt = $this->execute($sql, [
            'estado' => $estado,
            'id'     => $id,
        ]);

        return $stmt->rowCount() > 0;
    }
}
