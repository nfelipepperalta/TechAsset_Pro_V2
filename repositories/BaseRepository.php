<?php
// ============================================================
//  repositories/BaseRepository.php
//  Clase padre — métodos comunes para todos los repositorios
//  Todos los repositorios extienden esta clase
// ============================================================

namespace Repositories;

use Core\Database;
use PDO;
use PDOStatement;

abstract class BaseRepository
{
    protected PDO    $db;
    protected string $table;      // Nombre de la tabla — definido en cada hijo
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Lectura ───────────────────────────────────────────────

    // Obtener todos los registros de la tabla
    public function findAll(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM dbo.{$this->table} ORDER BY {$orderBy} {$direction}";
        return $this->query($sql);
    }

    // Obtener un registro por su PK
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM dbo.{$this->table} WHERE {$this->primaryKey} = :id";
        return $this->queryOne($sql, ['id' => $id]);
    }

    // Obtener registros por un campo específico
    public function findBy(string $field, mixed $value): array
    {
        $sql = "SELECT * FROM dbo.{$this->table} WHERE {$field} = :value";
        return $this->query($sql, ['value' => $value]);
    }

    // Obtener un único registro por un campo específico
    public function findOneBy(string $field, mixed $value): ?array
    {
        $sql = "SELECT * FROM dbo.{$this->table} WHERE {$field} = :value";
        return $this->queryOne($sql, ['value' => $value]);
    }

    // Contar registros totales
    public function count(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM dbo.{$this->table}";
        $row = $this->queryOne($sql);
        return (int)($row['total'] ?? 0);
    }

    // Verificar si existe un registro por PK
    public function exists(int $id): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM dbo.{$this->table} WHERE {$this->primaryKey} = :id";
        $row = $this->queryOne($sql, ['id' => $id]);
        return (int)($row['total'] ?? 0) > 0;
    }

    // ── Escritura ─────────────────────────────────────────────

    // Insertar un registro — retorna el ID generado
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $params  = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));

        $sql = "INSERT INTO dbo.{$this->table} ({$columns}) VALUES ({$params})";
        $this->execute($sql, $data);

        return (int)$this->db->lastInsertId();
    }

    // Actualizar un registro por PK
    public function update(int $id, array $data): bool
    {
        $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $data[$this->primaryKey] = $id;

        $sql = "UPDATE dbo.{$this->table} SET {$sets} WHERE {$this->primaryKey} = :{$this->primaryKey}";
        $stmt = $this->execute($sql, $data);

        return $stmt->rowCount() > 0;
    }

    // Eliminar un registro por PK
    public function delete(int $id): bool
    {
        $sql  = "DELETE FROM dbo.{$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->execute($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ── Paginación ────────────────────────────────────────────

    public function paginate(int $page = 1, int $perPage = DEFAULT_PAGE_SIZE, string $orderBy = 'id'): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = $this->count();

        $sql  = "SELECT * FROM dbo.{$this->table}
                 ORDER BY {$orderBy}
                 OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY";

        $rows = $this->query($sql, ['offset' => (int)$offset, 'perPage' => (int)$perPage]);

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    // ── Helpers PDO ───────────────────────────────────────────

    // Ejecutar consulta y retornar array de filas
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    // Ejecutar consulta y retornar una sola fila
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    // Preparar y ejecutar un statement PDO
    protected function execute(string $sql, array $params = []): PDOStatement
    {
    	$stmt = $this->db->prepare($sql);
    	foreach ($params as $key => $value) {
        	$type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        	$stmt->bindValue(":$key", $value, $type);
    }
    	$stmt->execute();
    	return $stmt;
    }				



    // Iniciar transacción
    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    // Confirmar transacción
    protected function commit(): void
    {
        $this->db->commit();
    }

    // Revertir transacción
    protected function rollback(): void
    {
        $this->db->rollBack();
    }
}
