<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Payment;
use PDO;

/**
 * Data Access Layer for Financial Transactions & Paystack-Ready Commerce
 */
class PaymentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function create(array $data): Payment
    {
        $now = date('Y-m-d H:i:s');
        $metadataJson = isset($data['metadata']) ? (is_string($data['metadata']) ? $data['metadata'] : json_encode($data['metadata'], JSON_UNESCAPED_UNICODE)) : null;

        $sql = 'INSERT INTO `payments` 
                (`reference`, `user_id`, `student_id`, `session_id`, `term_id`, `purpose`, `amount`, `currency`, `channel`, `status`, `gateway_reference`, `metadata`, `paid_at`, `created_at`, `updated_at`)
                VALUES (:reference, :user_id, :student_id, :session_id, :term_id, :purpose, :amount, :currency, :channel, :status, :gateway_reference, :metadata, :paid_at, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':reference' => $data['reference'],
            ':user_id' => $data['user_id'],
            ':student_id' => $data['student_id'],
            ':session_id' => $data['session_id'],
            ':term_id' => $data['term_id'],
            ':purpose' => $data['purpose'] ?? Payment::PURPOSE_RESULT_PIN,
            ':amount' => $data['amount'],
            ':currency' => $data['currency'] ?? 'NGN',
            ':channel' => $data['channel'] ?? 'simulated',
            ':status' => $data['status'] ?? Payment::STATUS_PENDING,
            ':gateway_reference' => $data['gateway_reference'] ?? null,
            ':metadata' => $metadataJson,
            ':paid_at' => $data['paid_at'] ?? null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }

    public function updateStatus(int $paymentId, string $status, ?string $gatewayRef = null, ?string $paidAt = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $paidAtTime = $paidAt ?? ($status === Payment::STATUS_SUCCESSFUL ? $now : null);

        $sql = 'UPDATE `payments` 
                SET `status` = :status, 
                    `gateway_reference` = COALESCE(:gateway_ref, `gateway_reference`),
                    `paid_at` = COALESCE(:paid_at, `paid_at`),
                    `updated_at` = :now
                WHERE `id` = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':gateway_ref' => $gatewayRef,
            ':paid_at' => $paidAtTime,
            ':now' => $now,
            ':id' => $paymentId,
        ]);
    }

    public function findById(int $id): ?Payment
    {
        $sql = $this->getBaseQuery() . ' WHERE p.id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Payment::fromArray($row) : null;
    }

    public function findByReference(string $reference): ?Payment
    {
        $sql = $this->getBaseQuery() . ' WHERE p.reference = :reference LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':reference' => trim($reference)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Payment::fromArray($row) : null;
    }

    /**
     * @return Payment[]
     */
    public function getPaymentsForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $sql = $this->getBaseQuery() . ' WHERE p.user_id = :user_id ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $r) => Payment::fromArray($r), $rows);
    }

    /**
     * @return Payment[]
     */
    public function getPaymentsForStudent(int $studentId, int $limit = 50, int $offset = 0): array
    {
        $sql = $this->getBaseQuery() . ' WHERE p.student_id = :student_id ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $r) => Payment::fromArray($r), $rows);
    }

    /**
     * @return Payment[]
     */
    public function getAllPayments(int $limit = 50, int $offset = 0, ?string $status = null, ?string $search = null): array
    {
        $where = [];
        $params = [];

        if (!empty($status)) {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $where[] = '(p.reference LIKE :search_ref OR su.name LIKE :search_name OR su.email LIKE :search_email OR pu.name LIKE :search_pname)';
            $searchVal = '%' . trim($search) . '%';
            $params[':search_ref'] = $searchVal;
            $params[':search_name'] = $searchVal;
            $params[':search_email'] = $searchVal;
            $params[':search_pname'] = $searchVal;
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = $this->getBaseQuery() . " {$whereClause} ORDER BY p.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $r) => Payment::fromArray($r), $rows);
    }

    public function countPayments(?string $status = null, ?string $search = null): int
    {
        $where = [];
        $params = [];

        if (!empty($status)) {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $where[] = '(p.reference LIKE :search_ref OR su.name LIKE :search_name OR su.email LIKE :search_email OR pu.name LIKE :search_pname)';
            $searchVal = '%' . trim($search) . '%';
            $params[':search_ref'] = $searchVal;
            $params[':search_name'] = $searchVal;
            $params[':search_email'] = $searchVal;
            $params[':search_pname'] = $searchVal;
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM `payments` p 
                JOIN `users` pu ON pu.id = p.user_id
                JOIN `students` s ON s.id = p.student_id
                JOIN `users` su ON su.id = s.user_id
                {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getSummaryStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(CASE WHEN status = 'successful' THEN amount ELSE 0 END), 0) as total_volume,
                    COUNT(CASE WHEN status = 'successful' THEN 1 END) as successful_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'successful' AND purpose = 'result_pin' THEN 1 END) as pin_payments_count
                FROM `payments`";

        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_transactions' => (int)($row['total_transactions'] ?? 0),
            'total_volume' => (float)($row['total_volume'] ?? 0.0),
            'successful_count' => (int)($row['successful_count'] ?? 0),
            'pending_count' => (int)($row['pending_count'] ?? 0),
            'pin_payments_count' => (int)($row['pin_payments_count'] ?? 0),
        ];
    }

    private function getBaseQuery(): string
    {
        return 'SELECT p.*,
                       pu.name as payer_name, pu.email as payer_email,
                       su.name as student_name, s.admission_number as student_admission_number,
                       ses.name as session_name,
                       t.name as term_name
                FROM `payments` p
                JOIN `users` pu ON pu.id = p.user_id
                JOIN `students` s ON s.id = p.student_id
                JOIN `users` su ON su.id = s.user_id
                JOIN `sessions` ses ON ses.id = p.session_id
                JOIN `terms` t ON t.id = p.term_id';
    }
}
