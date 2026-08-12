<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Migrator;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private PDO $sqlitePdo;
    private string $tempMigrationsDir;

    protected function setUp(): void
    {
        $this->sqlitePdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->tempMigrationsDir = sys_get_temp_dir() . '/lms_test_migrations_' . bin2hex(random_bytes(4));
        mkdir($this->tempMigrationsDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempMigrationsDir)) {
            $files = glob($this->tempMigrationsDir . '/*');
            foreach ($files as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
            rmdir($this->tempMigrationsDir);
        }
    }

    public function testRunsMigrationsInOrder(): void
    {
        file_put_contents(
            $this->tempMigrationsDir . '/0001_create_table_a.sql',
            "CREATE TABLE table_a (id INTEGER PRIMARY KEY, name TEXT);"
        );
        file_put_contents(
            $this->tempMigrationsDir . '/0002_create_table_b.sql',
            "CREATE TABLE table_b (id INTEGER PRIMARY KEY, a_id INTEGER);"
        );

        $migrator = new Migrator($this->sqlitePdo, $this->tempMigrationsDir);
        $executed = $migrator->run();

        $this->assertCount(2, $executed);
        $this->assertSame(['0001_create_table_a.sql', '0002_create_table_b.sql'], $executed);

        // Second run should execute 0 migrations
        $secondRun = $migrator->run();
        $this->assertEmpty($secondRun);
    }
}
