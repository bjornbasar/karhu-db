<?php

declare(strict_types=1);

namespace Karhu\Db\Tests;

use Karhu\Db\Connection;
use Karhu\Db\TableBase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemTable extends TableBase
{
    protected string $table = 'items';
    protected string $primaryKey = 'id';
}

final class TableBaseTest extends TestCase
{
    private Connection $db;
    private ItemTable $items;

    protected function setUp(): void
    {
        $this->db = new Connection('sqlite::memory:');
        $this->db->pdo()->exec('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, status TEXT)');
        $this->db->insert('items', ['name' => 'Alpha', 'status' => 'active']);
        $this->db->insert('items', ['name' => 'Beta', 'status' => 'inactive']);
        $this->db->insert('items', ['name' => 'Gamma', 'status' => 'active']);

        $this->items = new ItemTable($this->db);
    }

    #[Test]
    public function get_all(): void
    {
        $this->assertCount(3, $this->items->getAll());
    }

    #[Test]
    public function get_by_id(): void
    {
        $row = $this->items->get(1);
        $this->assertNotNull($row);
        $this->assertSame('Alpha', $row['name']);
    }

    #[Test]
    public function get_by_id_returns_null(): void
    {
        $this->assertNull($this->items->get(999));
    }

    #[Test]
    public function get_by_conditions(): void
    {
        $rows = $this->items->getBy(['status' => 'active']);
        $this->assertCount(2, $rows);
    }

    #[Test]
    public function create_returns_id(): void
    {
        $id = $this->items->create(['name' => 'Delta', 'status' => 'active']);
        $this->assertSame('4', $id);
        $this->assertCount(4, $this->items->getAll());
    }

    #[Test]
    public function update_by_id(): void
    {
        $this->items->update(1, ['name' => 'Alpha Updated']);
        $row = $this->items->get(1);
        $this->assertSame('Alpha Updated', $row['name']);
    }

    #[Test]
    public function delete_by_id(): void
    {
        $this->items->delete(2);
        $this->assertNull($this->items->get(2));
        $this->assertCount(2, $this->items->getAll());
    }

    #[Test]
    public function count_all(): void
    {
        $this->assertSame(3, $this->items->count());
    }

    #[Test]
    public function count_with_conditions(): void
    {
        $this->assertSame(2, $this->items->count(['status' => 'active']));
    }
}
