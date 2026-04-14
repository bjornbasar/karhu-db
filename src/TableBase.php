<?php

declare(strict_types=1);

namespace Karhu\Db;

/**
 * Active-record base class — rewritten from Peopsquik's TableBase.
 *
 * Original had SQL-injection bugs in getBy() and autoexecute() via
 * string interpolation. This rewrite uses prepared statements exclusively.
 *
 * Subclass and set $table + $primaryKey:
 *
 *     final class UserTable extends TableBase {
 *         protected string $table = 'users';
 *         protected string $primaryKey = 'id';
 *     }
 */
abstract class TableBase
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct(
        protected readonly Connection $db,
    ) {}

    /**
     * Get all rows.
     *
     * @return list<array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table}");
    }

    /**
     * Get a single row by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function get(string|int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id",
            ['id' => $id],
        );
    }

    /**
     * Get rows matching conditions.
     *
     * @param array<string, mixed> $conditions Column => value pairs (AND-joined)
     * @return list<array<string, mixed>>
     */
    public function getBy(array $conditions): array
    {
        $parts = array_map(fn(string $k): string => "{$k} = :{$k}", array_keys($conditions));
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $parts);

        return $this->db->fetchAll($sql, $conditions);
    }

    /**
     * Insert a row.
     *
     * @param array<string, mixed> $data
     * @return string Last insert ID
     */
    public function create(array $data): string
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update a row by primary key.
     *
     * @param array<string, mixed> $data
     * @return int Affected rows
     */
    public function update(string|int $id, array $data): int
    {
        return $this->db->update($this->table, $data, [$this->primaryKey => $id]);
    }

    /**
     * Delete a row by primary key.
     *
     * @return int Affected rows
     */
    public function delete(string|int $id): int
    {
        return $this->db->delete($this->table, [$this->primaryKey => $id]);
    }

    /**
     * Count rows, optionally filtered.
     *
     * @param array<string, mixed> $conditions
     */
    public function count(array $conditions = []): int
    {
        if ($conditions === []) {
            return (int) $this->db->fetchScalar("SELECT COUNT(*) FROM {$this->table}");
        }

        $parts = array_map(fn(string $k): string => "{$k} = :{$k}", array_keys($conditions));
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $parts);

        return (int) $this->db->fetchScalar($sql, $conditions);
    }
}
