<?php

declare(strict_types=1);

namespace Karhu\Db;

use Karhu\Auth\UserRepositoryInterface;

/**
 * PDO-backed implementation of karhu's UserRepositoryInterface.
 *
 * Expects tables: `users` (username, password_hash) and `user_roles`
 * (username, role). Customise table/column names via constructor.
 */
final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly Connection $db,
        private readonly string $usersTable = 'users',
        private readonly string $rolesTable = 'user_roles',
    ) {}

    /**
     * @return array{username: string, password_hash: string, roles: list<string>}|null
     */
    public function findByUsername(string $username): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT username, password_hash FROM {$this->usersTable} WHERE username = :username",
            ['username' => $username],
        );

        if ($user === null) {
            return null;
        }

        return [
            'username' => (string) $user['username'],
            'password_hash' => (string) $user['password_hash'],
            'roles' => $this->rolesFor($username),
        ];
    }

    /**
     * @return list<string>
     */
    public function rolesFor(string $username): array
    {
        $rows = $this->db->fetchAll(
            "SELECT role FROM {$this->rolesTable} WHERE username = :username",
            ['username' => $username],
        );

        return array_map(fn(array $row): string => (string) $row['role'], $rows);
    }
}
