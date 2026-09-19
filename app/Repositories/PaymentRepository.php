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
                (`reference`, `user_id`, `student_id`, `session_id`, `term_id`, `invoice_id`, `purpose`, `amount`, `currency`, `channel`, `status`, `gateway_reference`, `metadata`, `paid_at`, `created_at`, `updated_at`)
                VALUES (:reference, :user_id, :student_id, :session_id, :term_id, :invoice_id, :purpose, :amount, :currency, :channel, :status, :gateway_reference, :metadata, :paid_at, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':reference' => $data['reference'],
            ':user_id' => $data['user_id'],
            ':student_id' => $data['student_id'],
            ':session_id' => $data['session_id'],
            ':term_id' => $data['term_id'],
            ':invoice_id' => !empty($data['invoice_id']) ? (int)$data['invoice_id'] : null,
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
        $baseSql = $this->getUnifiedQuery();
        $sql = "SELECT * FROM ({$baseSql}) p WHERE p.reference = :reference LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':reference' => trim($reference)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Payment::fromArray($row) : null;
    }

    /**
     * @return Payment[]
     */
    public function getPaymentsForUser(int $userId, int $limit = 50, int $offset = 0, ?int $studentId = null): array
    {
        $baseSql = $this->getUnifiedQuery();
        if ($studentId !== null && $studentId > 0) {
            $sql = "SELECT * FROM ({$baseSql}) p WHERE (p.user_id = :user_id OR p.student_id = :student_id) ORDER BY p.created_at DESC, p.id DESC LIMIT :limit OFFSET :offset";
        } else {
            $sql = "SELECT * FROM ({$baseSql}) p WHERE p.user_id = :user_id ORDER BY p.created_at DESC, p.id DESC LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($studentId !== null && $studentId > 0) {
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        }
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
        $baseSql = $this->getUnifiedQuery();
        $sql = "SELECT * FROM ({$baseSql}) p WHERE p.student_id = :student_id ORDER BY p.created_at DESC, p.id DESC LIMIT :limit OFFSET :offset";
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
    public function getAllPayments(
        int $limit = 50, 
        int $offset = 0, 
        ?string $status = null, 
        ?string $search = null,
        ?string $purpose = null
    ): array {
        $where = [];
        $params = [];

        if (!empty($status)) {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($purpose)) {
            $where[] = 'p.purpose = :purpose';
            $params[':purpose'] = $purpose;
        }

        if (!empty($search)) {
            $where[] = '(p.reference LIKE :search_ref OR p.student_name LIKE :search_name OR p.payer_email LIKE :search_email OR p.payer_name LIKE :search_pname OR p.student_admission_number LIKE :search_adm)';
            $searchVal = '%' . trim($search) . '%';
            $params[':search_ref'] = $searchVal;
            $params[':search_name'] = $searchVal;
            $params[':search_email'] = $searchVal;
            $params[':search_pname'] = $searchVal;
            $params[':search_adm'] = $searchVal;
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $baseSql = $this->getUnifiedQuery();
        $sql = "SELECT * FROM ({$baseSql}) p {$whereClause} ORDER BY p.created_at DESC, p.id DESC LIMIT :limit OFFSET :offset";

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

    public function countPayments(
        ?string $status = null, 
        ?string $search = null,
        ?string $purpose = null
    ): int {
        $where = [];
        $params = [];

        if (!empty($status)) {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($purpose)) {
            $where[] = 'p.purpose = :purpose';
            $params[':purpose'] = $purpose;
        }

        if (!empty($search)) {
            $where[] = '(p.reference LIKE :search_ref OR p.student_name LIKE :search_name OR p.payer_email LIKE :search_email OR p.payer_name LIKE :search_pname OR p.student_admission_number LIKE :search_adm)';
            $searchVal = '%' . trim($search) . '%';
            $params[':search_ref'] = $searchVal;
            $params[':search_name'] = $searchVal;
            $params[':search_email'] = $searchVal;
            $params[':search_pname'] = $searchVal;
            $params[':search_adm'] = $searchVal;
        }

        $whereClause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $baseSql = $this->getUnifiedQuery();
        $sql = "SELECT COUNT(*) FROM ({$baseSql}) p {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getSummaryStats(): array
    {
        $baseSql = $this->getUnifiedQuery();
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(CASE WHEN p.status = 'successful' THEN p.amount ELSE 0 END), 0) as total_volume,
                    COUNT(CASE WHEN p.status = 'successful' THEN 1 END) as successful_count,
                    COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN p.status = 'successful' AND p.purpose = 'result_pin' THEN 1 END) as pin_payments_count,
                    COUNT(CASE WHEN p.status = 'successful' AND p.purpose = 'admission' THEN 1 END) as admission_payments_count
                FROM ({$baseSql}) p";

        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_transactions' => (int)($row['total_transactions'] ?? 0),
            'total_volume' => (float)($row['total_volume'] ?? 0.0),
            'successful_count' => (int)($row['successful_count'] ?? 0),
            'pending_count' => (int)($row['pending_count'] ?? 0),
            'pin_payments_count' => (int)($row['pin_payments_count'] ?? 0),
            'admission_payments_count' => (int)($row['admission_payments_count'] ?? 0),
        ];
    }

    private ?bool $hasAdmissionPaymentsTable = null;

    private function hasAdmissionPaymentsTable(): bool
    {
        if ($this->hasAdmissionPaymentsTable !== null) {
            return $this->hasAdmissionPaymentsTable;
        }

        try {
            $this->pdo->query('SELECT 1 FROM `admission_payments` LIMIT 1');
            $this->hasAdmissionPaymentsTable = true;
        } catch (\Throwable $e) {
            $this->hasAdmissionPaymentsTable = false;
        }

        return $this->hasAdmissionPaymentsTable;
    }

    private function getUnifiedQuery(): string
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $isSqlite = $driver === 'sqlite';

        $studentConcat = $isSqlite
            ? "TRIM(COALESCE(w.first_name, '') || ' ' || COALESCE(w.last_name, ''))"
            : "TRIM(CONCAT(COALESCE(w.first_name, ''), ' ', COALESCE(w.last_name, '')))";

        $generalQuery = 'SELECT p.id, p.reference, p.user_id, p.student_id, p.session_id, p.term_id, p.invoice_id,
                               p.purpose, p.amount, p.currency, p.channel, p.status,
                               p.gateway_reference, p.metadata, p.paid_at, p.created_at, p.updated_at,
                               pu.name as payer_name, pu.email as payer_email,
                               su.name as student_name, s.admission_number as student_admission_number,
                               ses.name as session_name,
                               t.name as term_name
                        FROM `payments` p
                        LEFT JOIN `users` pu ON pu.id = p.user_id
                        LEFT JOIN `students` s ON s.id = p.student_id
                        LEFT JOIN `users` su ON su.id = s.user_id
                        LEFT JOIN `sessions` ses ON ses.id = p.session_id
                        LEFT JOIN `terms` t ON t.id = p.term_id';

        if (!$this->hasAdmissionPaymentsTable()) {
            return $generalQuery;
        }

        $admissionQuery = "SELECT ap.id, ap.reference, ap.user_id,
                                 w.converted_student_id as student_id,
                                 ase.academic_session_id as session_id,
                                 NULL as term_id,
                                 NULL as invoice_id,
                                 'admission' as purpose,
                                 ap.amount, ap.currency, ap.channel, ap.status,
                                 ap.gateway_reference, ap.metadata, ap.paid_at, ap.created_at, ap.updated_at,
                                 pu.name as payer_name, pu.email as payer_email,
                                 {$studentConcat} as student_name,
                                 COALESCE(st.admission_number, app.application_number) as student_admission_number,
                                 COALESCE(ses.name, ase.title) as session_name,
                                 'Admission Application' as term_name
                          FROM `admission_payments` ap
                          LEFT JOIN `admission_applications` app ON app.id = ap.application_id
                          LEFT JOIN `admission_wards` w ON w.id = ap.ward_id
                          LEFT JOIN `admission_sessions` ase ON ase.id = app.admission_session_id
                          LEFT JOIN `sessions` ses ON ses.id = ase.academic_session_id
                          LEFT JOIN `students` st ON st.id = w.converted_student_id
                          LEFT JOIN `users` pu ON pu.id = ap.user_id";

        return "{$generalQuery} UNION ALL {$admissionQuery}";
    }

    private function getBaseQuery(): string
    {
        return 'SELECT p.*,
                       pu.name as payer_name, pu.email as payer_email,
                       su.name as student_name, s.admission_number as student_admission_number,
                       ses.name as session_name,
                       t.name as term_name
                FROM `payments` p
                LEFT JOIN `users` pu ON pu.id = p.user_id
                LEFT JOIN `students` s ON s.id = p.student_id
                LEFT JOIN `users` su ON su.id = s.user_id
                LEFT JOIN `sessions` ses ON ses.id = p.session_id
                LEFT JOIN `terms` t ON t.id = p.term_id';
    }
}
