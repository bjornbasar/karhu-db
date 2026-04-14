<?php

declare(strict_types=1);

namespace Karhu\Db;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper with query helpers.
 *
 * All queries use prepared statements — no string interpolation of
 * user input. Fixes the SQL-injection vulnerabilities in Peopsquik's
 * Core_DB (getBy, autoexecute, _createSql).
 */
final class Connection
{
    private PDO $pdo;

    public function __construct(
        string $dsn,
        string $username = '',
        string $password = '',
        array $options = [],
    ) {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, $username, $password, array_merge($defaults, $options));
    }

    /** Get the underlying PDO instance. */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return all rows.
     *
     * @param array<string, mixed> $params Named parameters
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return the first row, or null.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Execute a query and return a single scalar value.
     *
     * @param array<string, mixed> $params
     */
    public function fetchScalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute a non-SELECT query (INSERT, UPDATE, DELETE).
     *
     * @param array<string, mixed> $params
     * @return int Affected row count
     */
    public function run(string $sql, array $params = []): int
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Insert a row and return the last insert ID.
     *
     * @param array<string, mixed> $data Column => value pairs
     */
    public function insert(string $table, array $data): string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn(string $k): string => ':' . $k, array_keys($data)));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, $data);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching conditions.
     *
     * @param array<string, mixed> $data  Column => value pairs to set
     * @param array<string, mixed> $where Column => value conditions (AND-joined)
     * @return int Affected row count
     */
    public function update(string $table, array $data, array $where): int
    {
        $setParts = array_map(fn(string $k): string => "{$k} = :set_{$k}", array_keys($data));
        $whereParts = array_map(fn(string $k): string => "{$k} = :where_{$k}", array_keys($where));

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts)
             . " WHERE " . implode(' AND ', $whereParts);

        $params = [];
        foreach ($data as $k => $v) {
            $params["set_{$k}"] = $v;
        }
        foreach ($where as $k => $v) {
            $params["where_{$k}"] = $v;
        }

        return $this->run($sql, $params);
    }

    /**
     * Delete rows matching conditions.
     *
     * @param array<string, mixed> $where Column => value conditions (AND-joined)
     * @return int Affected row count
     */
    public function delete(string $table, array $where): int
    {
        $whereParts = array_map(fn(string $k): string => "{$k} = :{$k}", array_keys($where));
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereParts);

        return $this->run($sql, $where);
    }

    /**
     * Execute a prepared statement.
     *
     * @param array<string, mixed> $params
     */
    private function execute(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
