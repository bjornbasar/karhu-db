# karhu-db

Thin PDO wrapper + active-record base for the [karhu](https://github.com/bjornbasar/karhu) PHP microframework.

Zero runtime dependencies. All queries use prepared statements.

## Install

```bash
composer require bjornbasar/karhu-db
```

## Usage

```php
use Karhu\Db\Connection;

$db = new Connection('mysql:host=localhost;dbname=myapp', 'user', 'pass');

// Query helpers
$rows = $db->fetchAll('SELECT * FROM users WHERE active = :active', ['active' => 1]);
$user = $db->fetchOne('SELECT * FROM users WHERE id = :id', ['id' => 42]);
$count = $db->fetchScalar('SELECT COUNT(*) FROM users');

// Insert / update / delete
$id = $db->insert('users', ['name' => 'Bjorn', 'email' => 'bjorn@example.com']);
$db->update('users', ['name' => 'Updated'], ['id' => $id]);
$db->delete('users', ['id' => $id]);
```

## Active Record

```php
use Karhu\Db\TableBase;

final class UserTable extends TableBase {
    protected string $table = 'users';
    protected string $primaryKey = 'id';
}

$users = new UserTable($db);
$users->getAll();
$users->get(42);
$users->getBy(['role' => 'admin']);
$users->create(['name' => 'New User']);
$users->update(42, ['name' => 'Updated']);
$users->delete(42);
$users->count(['role' => 'admin']);
```

## UserRepositoryInterface

Implements karhu's `UserRepositoryInterface` for RBAC:

```php
use Karhu\Db\PdoUserRepository;

$userRepo = new PdoUserRepository($db);
$app->container()->set(UserRepositoryInterface::class, $userRepo);
```

Expects tables: `users` (username, password_hash) and `user_roles` (username, role).
