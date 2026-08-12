<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * PDO Database Wrapper with Strict Error Mode and Transaction Lifecycle
 */
class Database
{
    private static ?PDO $instance = null;
    private static ?Database $customWrapper = null;

    public function __construct(private ?PDO $pdo = null)
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$customWrapper !== null && self::$customWrapper->pdo !== null) {
            return self::$customWrapper->pdo;
        }

        if (self::$instance === null) {
            $driver = Config::get('database.driver', 'mysql');
            $host = Config::get('database.host', '127.0.0.1');
            $port = Config::get('database.port', 3306);
            $database = Config::get('database.database', 'lms');
            $charset = Config::get('database.charset', 'utf8mb4');
            $username = Config::get('database.username', 'root');
            $password = Config::get('database.password', '');

            $dsn = sprintf('%s:host=%s;port=%d;dbname=%s;charset=%s', $driver, $host, $port, $database, $charset);

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    public static function getConnection(): PDO
    {
        return self::getInstance();
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function setCustomWrapper(?Database $wrapper): void
    {
        self::$customWrapper = $wrapper;
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$customWrapper = null;
    }

    public function getPdo(): PDO
    {
        return $this->pdo ?? self::getInstance();
    }

    public static function beginTransaction(): bool
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        if (!$pdo->inTransaction()) {
            return $pdo->beginTransaction();
        }

        return false;
    }

    public static function commit(): bool
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        if ($pdo->inTransaction()) {
            return $pdo->commit();
        }

        return false;
    }

    public static function rollBack(): bool
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        if ($pdo->inTransaction()) {
            return $pdo->rollBack();
        }

        return false;
    }

    public static function inTransaction(): bool
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        return $pdo->inTransaction();
    }

    /**
     * Executes callback within a transaction. Commits on success, rolls back on exception.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     * @throws Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        $alreadyInTransaction = $pdo->inTransaction();

        if (!$alreadyInTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);

            if (!$alreadyInTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if (!$alreadyInTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);

        return $stmt->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = self::query($sql, $params);

        return $stmt->fetchColumn($column);
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);

        return $stmt->rowCount();
    }

    public static function lastInsertId(?string $name = null): string|false
    {
        $pdo = (self::$customWrapper !== null && self::$customWrapper->pdo !== null)
            ? self::$customWrapper->pdo
            : self::getInstance();

        return $pdo->lastInsertId($name);
    }
}
