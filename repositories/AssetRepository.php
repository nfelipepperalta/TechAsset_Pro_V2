<?php
// ============================================================
//  repositories/AssetRepository.php
//  Acceso a datos — tabla dbo.asset
// ============================================================

namespace Repositories;

class AssetRepository extends BaseRepository
{
    protected string $table = 'asset';

    // Listar activos con joins a tipo, usuario y departamento
    public function findAllWithDetails(array $filters = [], int $page = 1, int $perPage = DEFAULT_PAGE_SIZE): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[]            = 'a.estado = :estado';
            $params['estado']   = $filters['estado'];
        }
        if (!empty($filters['asset_type_id'])) {
            $where[]                 = 'a.asset_type_id = :asset_type_id';
            $params['asset_type_id'] = $filters['asset_type_id'];
        }
        if (!empty($filters['department_id'])) {
            $where[]                = 'a.department_id = :department_id';
            $params['department_id']= $filters['department_id'];
        }
        if (!empty($filters['search'])) {
            $where[]              = '(a.nombre LIKE :search1 OR a.serial LIKE :search2 OR a.marca LIKE :search3)';
            $params['search1']  = '%' . $filters['search'] . '%';
            $params['search2']  = '%' . $filters['search'] . '%';
            $params['search3']  = '%' . $filters['search'] . '%';
        }

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        // Conteo total con filtros
        $countSql = "SELECT COUNT(*) AS total
                     FROM dbo.asset a
                     WHERE {$whereStr}";
        $total = (int)($this->queryOne($countSql, $params)['total'] ?? 0);

        // Datos paginados
        $sql = "SELECT
                    a.*,
                    at.nombre        AS tipo_nombre,
                    at.categoria     AS tipo_categoria,
                    u.nombre         AS usuario_nombre,
                    d.nombre         AS departamento_nombre
                FROM dbo.asset a
                LEFT JOIN dbo.asset_type  at ON a.asset_type_id = at.id
                LEFT JOIN dbo.[user]       u ON a.user_id        = u.id
                LEFT JOIN dbo.department   d ON a.department_id  = d.id
                WHERE {$whereStr}
                ORDER BY a.id DESC
                OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY";

        $params['offset']  = (int)$offset;
        $params['perPage'] = (int)$perPage;

        return [
            'data'         => $this->query($sql, $params),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    // Detalle completo de un activo con todos sus joins
    public function findByIdWithDetails(int $id): ?array
    {
        $sql = "SELECT
                    a.*,
                    at.nombre        AS tipo_nombre,
                    at.categoria     AS tipo_categoria,
                    at.icono         AS tipo_icono,
                    u.nombre         AS usuario_nombre,
                    u.email          AS usuario_email,
                    d.nombre         AS departamento_nombre,
                    d.sede           AS departamento_sede,
                    q.token_unico    AS qr_token,
                    q.url_publica    AS qr_url
                FROM dbo.asset a
                LEFT JOIN dbo.asset_type  at ON a.asset_type_id = at.id
                LEFT JOIN dbo.[user]       u ON a.user_id        = u.id
                LEFT JOIN dbo.department   d ON a.department_id  = d.id
                LEFT JOIN dbo.qr_code      q ON q.asset_id       = a.id
                WHERE a.id = :id";

        return $this->queryOne($sql, ['id' => $id]);
    }

    // Activos por departamento
    public function findByDepartment(int $departmentId): array
    {
        $sql = "SELECT a.*, at.nombre AS tipo_nombre
                FROM dbo.asset a
                LEFT JOIN dbo.asset_type at ON a.asset_type_id = at.id
                WHERE a.department_id = :department_id
                ORDER BY a.nombre";

        return $this->query($sql, ['department_id' => $departmentId]);
    }

    // Activos asignados a un usuario
    public function findByUser(int $userId): array
    {
        $sql = "SELECT a.*, at.nombre AS tipo_nombre
                FROM dbo.asset a
                LEFT JOIN dbo.asset_type at ON a.asset_type_id = at.id
                WHERE a.user_id = :user_id
                ORDER BY a.nombre";

        return $this->query($sql, ['user_id' => $userId]);
    }

    // Activos con garantía próxima a vencer (próximos N días)
    public function findWarrantyExpiringSoon(int $days = 30): array
    {
        $sql = "SELECT
                    a.*,
                    at.nombre  AS tipo_nombre,
                    d.nombre   AS departamento_nombre,
                    u.nombre   AS usuario_nombre,
                    DATEDIFF(DAY, CAST(GETUTCDATE() AS DATE), a.garantia_hasta) AS dias_restantes
                FROM dbo.asset a
                LEFT JOIN dbo.asset_type  at ON a.asset_type_id = at.id
                LEFT JOIN dbo.department   d ON a.department_id  = d.id
                LEFT JOIN dbo.[user]       u ON a.user_id        = u.id
                WHERE a.estado <> 'baja'
                  AND a.garantia_hasta IS NOT NULL
                  AND a.garantia_hasta <= DATEADD(DAY, CAST(:days AS INT), CAST(GETUTCDATE() AS DATE))
                  AND a.garantia_hasta >= CAST(GETUTCDATE() AS DATE)
                ORDER BY a.garantia_hasta ASC";

        return $this->query($sql, ['days' => $days]);
    }

    // Conteo de activos agrupados por estado
    public function countByEstado(): array
    {
        $sql = "SELECT estado, COUNT(*) AS total
                FROM dbo.asset
                GROUP BY estado";

        return $this->query($sql);
    }

    // Conteo de activos agrupados por tipo
    public function countByTipo(): array
    {
        $sql = "SELECT at.nombre AS tipo, at.categoria, COUNT(a.id) AS total
                FROM dbo.asset_type at
                LEFT JOIN dbo.asset a ON a.asset_type_id = at.id
                GROUP BY at.id, at.nombre, at.categoria
                ORDER BY total DESC";

        return $this->query($sql);
    }

    // Cambiar estado de un activo y registrar en historial
    public function cambiarEstado(int $id, string $nuevoEstado, int $userId): bool
    {
        $asset = $this->findById($id);
        if (!$asset) return false;

        $this->beginTransaction();
        try {
            // Actualizar estado
            $this->update($id, [
                'estado'     => $nuevoEstado,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Registrar en historial
            $historyRepo = new AssetHistoryRepository();
            $historyRepo->insert([
                'asset_id'        => $id,
                'campo'           => 'estado',
                'valor_anterior'  => $asset['estado'],
                'valor_nuevo'     => $nuevoEstado,
                'modificado_por'  => $userId,
            ]);

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            error_log('[AssetRepository] cambiarEstado error: ' . $e->getMessage());
            return false;
        }
    }

    // Valor total del inventario activo
    public function valorTotalActivos(): float
    {
        $sql = "SELECT SUM(ISNULL(valor, 0)) AS total
                FROM dbo.asset
                WHERE estado <> 'baja'";

        $row = $this->queryOne($sql);
        return (float)($row['total'] ?? 0);
    }
}
