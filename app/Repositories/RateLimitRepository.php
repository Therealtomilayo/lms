<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Repository for Rate Limiting Records with Expiration
 */
class RateLimitRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Increment the counter for a given key and return total hits.
     */
    public function hit(string $key, int $decaySeconds): int
    {
        $now = time();
        $expiresAt = $now + $decaySeconds;

        $stmt = $this->db->prepare("SELECT hits, expires_at FROM rate_limits WHERE `key` = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if ((int)$row['expires_at'] < $now) {
                $update = $this->db->prepare("UPDATE rate_limits SET hits = 1, expires_at = :expires_at WHERE `key` = :key");
                $update->execute([':key' => $key, ':expires_at' => $expiresAt]);
                return 1;
            }

            $update = $this->db->prepare("UPDATE rate_limits SET hits = hits + 1 WHERE `key` = :key");
            $update->execute([':key' => $key]);
            return (int)$row['hits'] + 1;
        }

        $insert = $this->db->prepare("INSERT INTO rate_limits (`key`, `hits`, `expires_at`) VALUES (:key, 1, :expires_at)");
        $insert->execute([':key' => $key, ':expires_at' => $expiresAt]);
        return 1;
    }

    /**
     * Get current attempts for a given key if not expired.
     */
    public function attempts(string $key): int
    {
        $now = time();
        $stmt = $this->db->prepare("SELECT hits, expires_at FROM rate_limits WHERE `key` = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['expires_at'] < $now) {
            return 0;
        }

        return (int)$row['hits'];
    }

    /**
     * Get remaining seconds until the rate limit key expires.
     */
    public function availableIn(string $key): int
    {
        $now = time();
        $stmt = $this->db->prepare("SELECT expires_at FROM rate_limits WHERE `key` = :key");
        $stmt->execute([':key' => $key]);
        $expiresAt = $stmt->fetchColumn();

        if ($expiresAt === false || (int)$expiresAt <= $now) {
            return 0;
        }

        return (int)$expiresAt - $now;
    }

    /**
     * Reset/clear a specific key.
     */
    public function reset(string $key): void
    {
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE `key` = :key");
        $stmt->execute([':key' => $key]);
    }

    /**
     * Clear all rate limits (useful for testing and admin maintenance).
     */
    public function clearAll(): void
    {
        $this->db->exec("DELETE FROM rate_limits");
    }

    /**
     * Purge all expired rate limit records.
     */
    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE expires_at < :now");
        $stmt->execute([':now' => time()]);
        return $stmt->rowCount();
    }
}
