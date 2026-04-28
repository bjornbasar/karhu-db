# karhu-db — Project Documentation

**Version:** 0.1.0 | **License:** MIT | **PHP:** >=8.3

Thin PDO wrapper + active-record base for the [karhu](https://github.com/bjornbasar/karhu) PHP microframework.

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Language | PHP 8.3+ |
| DB driver | PDO (any DSN — MySQL, PostgreSQL, SQLite) |
| Autoloading | Composer PSR-4 (`Karhu\Db\`) |
| Testing | PHPUnit 11 |
| Static analysis | PHPStan |
| CI | GitHub Actions (ubuntu-latest, public repo) |

Zero runtime dependencies. All queries use prepared statements.

---

## Directory Structure

```
karhu-db/
├── src/
│   ├── Connection.php          # PDO wrapper — fetchAll/fetchOne/fetchScalar,
│   │                           #   insert/update/delete with parameter binding
│   ├── TableBase.php           # Active-record base — getAll/get/getBy/create/
│   │                           #   update/delete/count keyed on $table + $primaryKey
│   └── PdoUserRepository.php   # Implements karhu's UserRepositoryInterface
│                               #   for RBAC (users + user_roles tables)
├── tests/
│   ├── ConnectionTest.php      # Driven against in-memory SQLite
│   └── TableBaseTest.php
└── composer.json
```

---

## API Surface

### `Karhu\Db\Connection`

| Method | Returns | Description |
|---|---|---|
| `__construct($dsn, $user, $pass, $options=[])` | — | Opens a PDO connection with `ERRMODE_EXCEPTION` and `FETCH_ASSOC` defaults. |
| `pdo()` | `\PDO` | Underlying PDO handle for transactions / driver-specific work. |
| `fetchAll(string $sql, array $bindings=[])` | `array<int, array>` | Prepared SELECT, all rows. |
| `fetchOne(string $sql, array $bindings=[])` | `?array` | Prepared SELECT, first row or null. |
| `fetchScalar(string $sql, array $bindings=[])` | `mixed` | First column of first row. |
| `insert(string $table, array $data)` | `string` | Returns last insert ID. |
| `update(string $table, array $data, array $where)` | `int` | Affected row count. |
| `delete(string $table, array $where)` | `int` | Affected row count. |

### `Karhu\Db\TableBase`

Inherit and set `protected string $table` + `protected string $primaryKey = 'id'`. Inherits `getAll`, `get`, `getBy`, `create`, `update`, `delete`, `count` — all routed through the injected `Connection`.

### `Karhu\Db\PdoUserRepository`

Implements `Karhu\Auth\UserRepositoryInterface` (from karhu). Expects two tables:

```sql
CREATE TABLE users (
    username VARCHAR(64) PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE user_roles (
    username VARCHAR(64) NOT NULL,
    role VARCHAR(32) NOT NULL,
    PRIMARY KEY (username, role)
);
```

Wire it into the karhu container:

```php
$db = new Karhu\Db\Connection($dsn, $user, $pass);
$app->container()->set(
    Karhu\Auth\UserRepositoryInterface::class,
    new Karhu\Db\PdoUserRepository($db),
);
```

---

## Key Design Decisions

- **Zero runtime deps** — `require` only has PHP 8.3+. karhu is a *suggest*, not a require.
- **DSN-agnostic** — works with any PDO driver. Tests use in-memory SQLite so the test suite has no DB infra to bootstrap.
- **Always-prepared** — no string interpolation; bindings are passed through `prepare()`/`execute()`.
- **Active-record-lite** — TableBase is intentionally minimal (no relations, no migrations, no query builder). When you need more, drop down to `Connection::pdo()` and write the SQL.
- **Auth integration is opt-in** — PdoUserRepository sits in the same package because it's the canonical karhu pairing, but karhu-db is fully usable without karhu installed.

---

## Development

```bash
composer install
composer test            # PHPUnit
composer analyse         # PHPStan
composer check           # cs-check + analyse + tests
```

---

## CI/CD

GitHub Actions on `ubuntu-latest` (public repo, free minutes):
- PHP 8.3 + 8.4 matrix
- PHPUnit + PHPStan + composer audit on push/PR

---

## Related Repos

| Repo | Purpose |
|------|---------|
| [karhu](https://github.com/bjornbasar/karhu) | Parent microframework |
| [karhu-queue](https://github.com/bjornbasar/karhu-queue) | Queue/worker (ships with `DatabaseQueue` backed by karhu-db) |
| [istrbuddy](https://github.com/bjornbasar/istrbuddy) | Reference app — uses `Connection` for all queries and `PdoUserRepository` for auth |
