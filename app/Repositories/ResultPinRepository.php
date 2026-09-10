<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ResultAccessPin;
use PDO;

/**
 * Data Access Layer for Result Access Scratch-Card PINs (SRS §38, §39, §40, §58.5)
 */
class ResultPinRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function createSinglePin(array $data): ResultAccessPin
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `result_access_pins`
                (`serial_number`, `pin_code`, `pin_hash`, `payment_id`, `student_id`, `session_id`, `term_id`, `created_by`, `max_uses`, `times_used`, `status`, `created_at`, `updated_at`)
                VALUES (:serial, :code, :hash, :payment_id, :student_id, :session_id, :term_id, :created_by, :max_uses, 0, :status, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':serial' => $data['serial_number'],
            ':code' => $data['pin_code'],
            ':hash' => $data['pin_hash'],
            ':payment_id' => $data['payment_id'] ?? null,
            ':student_id' => $data['student_id'] ?? null,
            ':session_id' => $data['session_id'] ?? null,
            ':term_id' => $data['term_id'] ?? null,
            ':created_by' => $data['created_by'],
            ':max_uses' => $data['max_uses'] ?? 5,
            ':status' => ResultAccessPin::STATUS_ACTIVE,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Bulk insert generated PINs.
     * @param array<array{serial_number: string, pin_code: string, pin_hash: string, payment_id: ?int, student_id: ?int, session_id: ?int, term_id: ?int, created_by: int, max_uses: int}> $pins
     */
    public function createBatch(array $pins): int
    {
        if (empty($pins)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $placeholders = [];
        $params = [];
        $idx = 0;

        foreach ($pins as $p) {
            $placeholders[] = "(:s{$idx}, :c{$idx}, :h{$idx}, :p{$idx}, :st{$idx}, :ses{$idx}, :t{$idx}, :cb{$idx}, :mu{$idx}, 0, 'active', :ca{$idx}, :ua{$idx})";
            $params[":s{$idx}"] = $p['serial_number'];
            $params[":c{$idx}"] = $p['pin_code'];
            $params[":h{$idx}"] = $p['pin_hash'];
            $params[":p{$idx}"] = $p['payment_id'] ?? null;
            $params[":st{$idx}"] = $p['student_id'] ?? null;
            $params[":ses{$idx}"] = $p['session_id'] ?? null;
            $params[":t{$idx}"] = $p['term_id'] ?? null;
            $params[":cb{$idx}"] = $p['created_by'];
            $params[":mu{$idx}"] = $p['max_uses'] ?? 5;
            $params[":ca{$idx}"] = $now;
            $params[":ua{$idx}"] = $now;
            $idx++;
        }

        $sql = 'INSERT INTO `result_access_pins`
                (`serial_number`, `pin_code`, `pin_hash`, `payment_id`, `student_id`, `session_id`, `term_id`, `created_by`, `max_uses`, `times_used`, `status`, `created_at`, `updated_at`)
                VALUES ' . implode(', ', $placeholders);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return count($pins);
    }

    public function findById(int $id): ?ResultAccessPin
    {
        $sql = $this->getBaseQuery() . ' WHERE rap.id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ResultAccessPin::fromArray($row) : null;
    }

    public function findByPinCode(string $pinCode): ?ResultAccessPin
    {
        $normalized = strtoupper(str_replace(['-', ' '], '', trim($pinCode)));
        $hash = hash('sha256', $normalized);

        $sql = $this->getBaseQuery() . ' WHERE rap.pin_hash = :hash LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ResultAccessPin::fromArray($row) : null;
    }

    public function findBySerial(string $serial): ?ResultAccessPin
    {
        $sql = $this->getBaseQuery() . ' WHERE UPPER(rap.serial_number) = UPPER(:serial) LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':serial' => trim($serial)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ResultAccessPin::fromArray($row) : null;
    }

    public function findByPaymentId(int $paymentId): ?ResultAccessPin
    {
        $sql = $this->getBaseQuery() . ' WHERE rap.payment_id = :payment_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ResultAccessPin::fromArray($row) : null;
    }

    public function findActivePinForStudentAndTerm(int $studentId, int $sessionId, int $termId): ?ResultAccessPin
    {
        $sql = $this->getBaseQuery() . ' 
                WHERE rap.student_id = :student_id 
                  AND (rap.session_id = :session_id OR rap.session_id IS NULL)
                  AND (rap.term_id = :term_id OR rap.term_id IS NULL)
                  AND rap.status = "active"
                  AND rap.times_used < rap.max_uses
                ORDER BY rap.id DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':session_id' => $sessionId,
            ':term_id' => $termId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ResultAccessPin::fromArray($row) : null;
    }

    public function incrementUsage(int $pinId, int $studentId, ?int $sessionId = null, ?int $termId = null): void
    {
        $now = date('Y-m-d H:i:s');
        $pin = $this->findById($pinId);
        if (!$pin) {
            return;
        }

        $newTimesUsed = $pin->timesUsed + 1;
        $newStatus = $newTimesUsed >= $pin->maxUses ? ResultAccessPin::STATUS_DEPLETED : ResultAccessPin::STATUS_ACTIVE;
        $firstUsed = $pin->firstUsedAt ?? $now;

        $sql = 'UPDATE `result_access_pins` 
                SET `times_used` = :times_used,
                    `status` = :status,
                    `student_id` = COALESCE(`student_id`, :student_id),
                    `session_id` = COALESCE(`session_id`, :session_id),
                    `term_id` = COALESCE(`term_id`, :term_id),
                    `first_used_at` = :first_used_at,
                    `last_used_at` = :last_used_at,
                    `updated_at` = :updated_at
                WHERE `id` = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':times_used' => $newTimesUsed,
            ':status' => $newStatus,
            ':student_id' => $studentId,
            ':session_id' => $sessionId,
            ':term_id' => $termId,
            ':first_used_at' => $firstUsed,
            ':last_used_at' => $now,
            ':updated_at' => $now,
            ':id' => $pinId,
        ]);
    }

    public function revoke(int $pinId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `result_access_pins` SET `status` = "revoked", `updated_at` = :now WHERE `id` = :id');
        return $stmt->execute([':now' => $now, ':id' => $pinId]);
    }

    /**
     * @return ResultAccessPin[]
     */
    public function getPagedPins(
        int $limit = 50,
        int $offset = 0,
        ?string $status = null,
        ?int $termId = null,
        ?string $search = null
    ): array {
        $where = [];
        $params = [];

        if (!empty($status)) {
            $where[] = 'rap.status = :status';
            $params[':status'] = $status;
        }

        if ($termId !== null && $termId > 0) {
            $where[] = 'rap.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        if (!empty($search)) {
            $where[] = '(rap.serial_number LIKE :search OR su.name LIKE :search OR s.admission_number LIKE :search OR rap.pin_code LIKE :search)';
            $params[':search'] = '%' . trim($search) . '%';
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = $this->getBaseQuery() . " {$whereClause} ORDER BY rap.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $r) => ResultAccessPin::fromArray($r), $rows);
    }

    public function countPins(?string $status = null, ?int $termId = null, ?string $search = null): int
    {
        $where = [];
        $params = [];

        if (!empty($status)) {
            $where[] = 'rap.status = :status';
            $params[':status'] = $status;
        }

        if ($termId !== null && $termId > 0) {
            $where[] = 'rap.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        if (!empty($search)) {
            $where[] = '(rap.serial_number LIKE :search OR su.name LIKE :search OR s.admission_number LIKE :search OR rap.pin_code LIKE :search)';
            $params[':search'] = '%' . trim($search) . '%';
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM `result_access_pins` rap
                LEFT JOIN `students` s ON s.id = rap.student_id
                LEFT JOIN `users` su ON su.id = s.user_id
                {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getSummaryStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_pins,
                    COUNT(CASE WHEN status = 'active' AND times_used = 0 THEN 1 END) as unassigned_unused_count,
                    COUNT(CASE WHEN times_used > 0 THEN 1 END) as redeemed_count,
                    COUNT(CASE WHEN status = 'depleted' THEN 1 END) as depleted_count,
                    COUNT(CASE WHEN status = 'revoked' THEN 1 END) as revoked_count,
                    COUNT(CASE WHEN payment_id IS NOT NULL THEN 1 END) as online_purchased_count
                FROM `result_access_pins`";

        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_pins' => (int)($row['total_pins'] ?? 0),
            'unused_count' => (int)($row['unassigned_unused_count'] ?? 0),
            'redeemed_count' => (int)($row['redeemed_count'] ?? 0),
            'depleted_count' => (int)($row['depleted_count'] ?? 0),
            'revoked_count' => (int)($row['revoked_count'] ?? 0),
            'online_purchased_count' => (int)($row['online_purchased_count'] ?? 0),
        ];
    }

    private function getBaseQuery(): string
    {
        return 'SELECT rap.*,
                       su.name as student_name, s.admission_number as student_admission_number,
                       ses.name as session_name,
                       t.name as term_name,
                       p.reference as payment_reference,
                       cu.name as creator_name
                FROM `result_access_pins` rap
                LEFT JOIN `students` s ON s.id = rap.student_id
                LEFT JOIN `users` su ON su.id = s.user_id
                LEFT JOIN `sessions` ses ON ses.id = rap.session_id
                LEFT JOIN `terms` t ON t.id = rap.term_id
                LEFT JOIN `payments` p ON p.id = rap.payment_id
                LEFT JOIN `users` cu ON cu.id = rap.created_by';
    }
}
