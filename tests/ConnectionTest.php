<?php

declare(strict_types=1);

namespace Karhu\Db\Tests;

use Karhu\Db\Connection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = new Connection('sqlite::memory:');
        $this->db->pdo()->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price REAL)');
        $this->db->insert('items', ['name' => 'Widget', 'price' => 9.99]);
        $this->db->insert('items', ['name' => 'Gadget', 'price' => 19.99]);
    }

    #[Test]
    public function fetch_all(): void
    {
        $rows = $this->db->fetchAll('SELECT * FROM items');
        $this->assertCount(2, $rows);
        $this->assertSame('Widget', $rows[0]['name']);
    }

    #[Test]
    public function fetch_one(): void
    {
        $row = $this->db->fetchOne('SELECT * FROM items WHERE name = :name', ['name' => 'Gadget']);
        $this->assertNotNull($row);
        $this->assertSame('Gadget', $row['name']);
    }

    #[Test]
    public function fetch_one_returns_null(): void
    {
        $row = $this->db->fetchOne('SELECT * FROM items WHERE name = :name', ['name' => 'Missing']);
        $this->assertNull($row);
    }

    #[Test]
    public function fetch_scalar(): void
    {
        $count = $this->db->fetchScalar('SELECT COUNT(*) FROM items');
        $this->assertSame(2, (int) $count);
    }

    #[Test]
    public function insert_returns_last_id(): void
    {
        $id = $this->db->insert('items', ['name' => 'New', 'price' => 5.00]);
        $this->assertSame('3', $id);
    }

    #[Test]
    public function update_rows(): void
    {
        $affected = $this->db->update('items', ['price' => 12.99], ['name' => 'Widget']);
        $this->assertSame(1, $affected);

        $row = $this->db->fetchOne('SELECT price FROM items WHERE name = :name', ['name' => 'Widget']);
        $this->assertSame(12.99, (float) $row['price']);
    }

    #[Test]
    public function delete_rows(): void
    {
        $affected = $this->db->delete('items', ['name' => 'Widget']);
        $this->assertSame(1, $affected);
        $this->assertCount(1, $this->db->fetchAll('SELECT * FROM items'));
    }

    #[Test]
    public function run_executes_arbitrary_sql(): void
    {
        $affected = $this->db->run('DELETE FROM items WHERE price > :price', ['price' => 15.00]);
        $this->assertSame(1, $affected);
    }
}
