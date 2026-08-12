<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseTest extends TestCase
{
    private PDO $sqlitePdo;

    protected function setUp(): void
    {
        $this->sqlitePdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->sqlitePdo->exec("CREATE TABLE test_items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)");
        Database::setConnection($this->sqlitePdo);
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    public function testExecuteAndFetch(): void
    {
        $count = Database::execute("INSERT INTO test_items (name) VALUES (:name)", [':name' => 'Item A']);
        $this->assertSame(1, $count);

        $item = Database::fetchOne("SELECT * FROM test_items WHERE name = :name", [':name' => 'Item A']);
        $this->assertNotNull($item);
        $this->assertSame('Item A', $item['name']);
    }

    public function testTransactionCommitsSuccessfully(): void
    {
        $result = Database::transaction(function (PDO $pdo) {
            Database::execute("INSERT INTO test_items (name) VALUES (:name)", [':name' => 'Tx Item 1']);
            Database::execute("INSERT INTO test_items (name) VALUES (:name)", [':name' => 'Tx Item 2']);
            return 'done';
        });

        $this->assertSame('done', $result);

        $items = Database::fetchAll("SELECT * FROM test_items");
        $this->assertCount(2, $items);
    }

    public function testTransactionRollsBackOnException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Simulated failure');

        try {
            Database::transaction(function (PDO $pdo) {
                Database::execute("INSERT INTO test_items (name) VALUES (:name)", [':name' => 'Should Rollback']);
                throw new RuntimeException('Simulated failure');
            });
        } finally {
            $items = Database::fetchAll("SELECT * FROM test_items");
            $this->assertCount(0, $items);
        }
    }
}
