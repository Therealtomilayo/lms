<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AdmissionApplication;
use App\Models\AdmissionPayment;
use App\Models\AdmissionSession;
use App\Models\AdmissionWard;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Admissions, Prospective Student Applications, Wards & Payments
 */
class AdmissionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /* -------------------------------------------------------------------------
     * ADMISSION SESSIONS CONFIGURATION
     * ------------------------------------------------------------------------- */

    public function getActiveAdmissionSession(): ?AdmissionSession
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'SELECT a.*, s.name as academic_session_name
                FROM `admission_sessions` a
                JOIN `sessions` s ON s.id = a.academic_session_id
                WHERE a.is_active = 1
                ORDER BY 
                    CASE WHEN :now BETWEEN a.opens_at AND a.closes_at THEN 0 ELSE 1 END ASC,
                    a.opens_at DESC
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':now' => $now]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return AdmissionSession::fromArray($row);
    }

    public function getAllAdmissionSessions(): array
    {
        $sql = 'SELECT a.*, s.name as academic_session_name
                FROM `admission_sessions` a
                JOIN `sessions` s ON s.id = a.academic_session_id
                ORDER BY a.created_at DESC';

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sessions = [];
        foreach ($rows as $row) {
            $sessions[] = AdmissionSession::fromArray($row);
        }

        return $sessions;
    }

    public function findSessionById(int $id): ?AdmissionSession
    {
        $sql = 'SELECT a.*, s.name as academic_session_name
                FROM `admission_sessions` a
                JOIN `sessions` s ON s.id = a.academic_session_id
                WHERE a.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return AdmissionSession::fromArray($row);
    }

    public function createSession(array $data): ?AdmissionSession
    {
        $now = date('Y-m-d H:i:s');
        $isActive = !empty($data['is_active']) ? 1 : 0;
        if ($isActive === 1) {
            $this->pdo->exec('UPDATE `admission_sessions` SET `is_active` = 0');
        }

        $sql = 'INSERT INTO `admission_sessions` (
                    `academic_session_id`, `title`, `application_fee`, `currency`, 
                    `opens_at`, `closes_at`, `is_active`, `required_documents_json`, 
                    `instructions`, `created_by`, `created_at`, `updated_at`
                ) VALUES (
                    :academic_session_id, :title, :application_fee, :currency,
                    :opens_at, :closes_at, :is_active, :required_documents_json,
                    :instructions, :created_by, :created_at, :updated_at
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':academic_session_id' => (int)$data['academic_session_id'],
            ':title' => trim((string)$data['title']),
            ':application_fee' => (float)($data['application_fee'] ?? 0.00),
            ':currency' => (string)($data['currency'] ?? 'NGN'),
            ':opens_at' => (string)$data['opens_at'],
            ':closes_at' => (string)$data['closes_at'],
            ':is_active' => $isActive,
            ':required_documents_json' => !empty($data['required_documents_json'])
                ? (is_string($data['required_documents_json']) ? $data['required_documents_json'] : json_encode($data['required_documents_json']))
                : null,
            ':instructions' => $data['instructions'] ?? null,
            ':created_by' => (int)($data['created_by'] ?? 0),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findSessionById($id);
    }

    public function updateSession(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $allowed = ['title', 'application_fee', 'currency', 'opens_at', 'closes_at', 'is_active', 'instructions'];
        $fields = ['`updated_at` = :updated_at'];
        $params = [':id' => $id, ':updated_at' => $now];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (isset($data['is_active']) && (int)$data['is_active'] === 1) {
            $this->pdo->prepare('UPDATE `admission_sessions` SET `is_active` = 0 WHERE `id` != ?')->execute([$id]);
        }

        $sql = 'UPDATE `admission_sessions` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * @return AdmissionSession[]
     */
    public function getAllSessions(): array
    {
        $sql = 'SELECT a.*, s.name as academic_session_name
                FROM `admission_sessions` a
                JOIN `sessions` s ON s.id = a.academic_session_id
                ORDER BY a.opens_at DESC';

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sessions = [];
        foreach ($rows as $row) {
            $sessions[] = AdmissionSession::fromArray($row);
        }

        return $sessions;
    }

    /**
     * @return AdmissionApplication[]
     */
    public function getFilteredApplications(array $filters = []): array
    {
        $sql = 'SELECT DISTINCT a.id
                FROM `admission_applications` a
                JOIN `users` u ON u.id = a.applicant_user_id
                LEFT JOIN `admission_wards` w ON w.application_id = a.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['session_id'])) {
            $sql .= ' AND a.admission_session_id = :session_id';
            $params[':session_id'] = (int)$filters['session_id'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= ' AND a.status = :status';
            $params[':status'] = (string)$filters['status'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim((string)$filters['search']) . '%';
            $sql .= ' AND (a.application_number LIKE :t1 OR u.name LIKE :t2 OR u.email LIKE :t3 OR w.first_name LIKE :t4 OR w.last_name LIKE :t5)';
            $params[':t1'] = $term;
            $params[':t2'] = $term;
            $params[':t3'] = $term;
            $params[':t4'] = $term;
            $params[':t5'] = $term;
        }

        $sql .= ' ORDER BY a.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $applications = [];
        foreach ($ids as $id) {
            $app = $this->findApplicationById((int)$id);
            if ($app) {
                $applications[] = $app;
            }
        }

        return $applications;
    }

    /* -------------------------------------------------------------------------
     * ADMISSION APPLICATIONS
     * ------------------------------------------------------------------------- */

    public function findApplicationById(int $id): ?AdmissionApplication
    {
        $sql = 'SELECT a.*, 
                       u.name as applicant_name, u.email as applicant_email, u.phone as applicant_phone,
                       s.academic_session_id, s.title as session_title, s.application_fee, s.currency, s.opens_at, s.closes_at, s.is_active as session_is_active
                FROM `admission_applications` a
                JOIN `users` u ON u.id = a.applicant_user_id
                JOIN `admission_sessions` s ON s.id = a.admission_session_id
                WHERE a.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $applicant = User::fromArray([
            'id' => $row['applicant_user_id'],
            'name' => $row['applicant_name'],
            'email' => $row['applicant_email'],
            'phone' => $row['applicant_phone'],
        ]);

        $session = AdmissionSession::fromArray([
            'id' => $row['admission_session_id'],
            'academic_session_id' => (int)($row['academic_session_id'] ?? 0),
            'title' => $row['session_title'],
            'application_fee' => $row['application_fee'],
            'currency' => $row['currency'],
            'opens_at' => $row['opens_at'],
            'closes_at' => $row['closes_at'],
            'is_active' => $row['session_is_active'],
        ]);

        $wards = $this->getWardsForApplication($id);

        return AdmissionApplication::fromArray($row, $applicant, $session, $wards);
    }

    public function findApplicationByNumber(string $appNumber): ?AdmissionApplication
    {
        $sql = 'SELECT id FROM `admission_applications` WHERE UPPER(application_number) = UPPER(:num) LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':num' => trim($appNumber)]);
        $id = $stmt->fetchColumn();

        return $id ? $this->findApplicationById((int)$id) : null;
    }

    public function findApplicationByApplicantAndSession(int $applicantUserId, int $sessionId): ?AdmissionApplication
    {
        $sql = 'SELECT id FROM `admission_applications` 
                WHERE applicant_user_id = :user_id AND admission_session_id = :session_id 
                ORDER BY id DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $applicantUserId,
            ':session_id' => $sessionId,
        ]);
        $id = $stmt->fetchColumn();

        return $id ? $this->findApplicationById((int)$id) : null;
    }

    /**
     * @return AdmissionApplication[]
     */
    public function getApplicationsByApplicant(int $applicantUserId): array
    {
        $sql = 'SELECT id FROM `admission_applications` WHERE applicant_user_id = :user_id ORDER BY id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $applicantUserId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $apps = [];
        foreach ($ids as $id) {
            $app = $this->findApplicationById((int)$id);
            if ($app) {
                $apps[] = $app;
            }
        }

        return $apps;
    }

    public function createApplication(int $sessionId, int $applicantUserId, string $appNumber): AdmissionApplication
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `admission_applications` (
                    `application_number`, `admission_session_id`, `applicant_user_id`, 
                    `status`, `created_at`, `updated_at`
                ) VALUES (
                    :app_num, :session_id, :user_id, :status, :created_at, :updated_at
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':app_num' => trim($appNumber),
            ':session_id' => $sessionId,
            ':user_id' => $applicantUserId,
            ':status' => AdmissionApplication::STATUS_DRAFT,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findApplicationById($id);
    }

    public function updateApplicationStatus(
        int $id,
        string $status,
        ?string $rejectionReason = null,
        ?int $reviewedBy = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $fields = ['`status` = :status', '`updated_at` = :updated_at'];
        $params = [
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ];

        if ($status === AdmissionApplication::STATUS_SUBMITTED) {
            $fields[] = '`submitted_at` = :submitted_at';
            $params[':submitted_at'] = $now;
        }

        if (in_array($status, [AdmissionApplication::STATUS_APPROVED, AdmissionApplication::STATUS_REJECTED], true)) {
            $fields[] = '`reviewed_at` = :reviewed_at';
            $fields[] = '`reviewed_by` = :reviewed_by';
            $params[':reviewed_at'] = $now;
            $params[':reviewed_by'] = $reviewedBy;
        }

        if ($rejectionReason !== null) {
            $fields[] = '`rejection_reason` = :rejection_reason';
            $params[':rejection_reason'] = $rejectionReason;
        }

        $sql = 'UPDATE `admission_applications` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /* -------------------------------------------------------------------------
     * ADMISSION WARDS
     * ------------------------------------------------------------------------- */

    public function findWardById(int $wardId): ?AdmissionWard
    {
        $sql = 'SELECT w.*, l.name as academic_level_name, s.admission_number as student_admission_number
                FROM `admission_wards` w
                JOIN `academic_levels` l ON l.id = w.applying_for_level_id
                LEFT JOIN `students` s ON s.id = w.converted_student_id
                WHERE w.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $wardId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return AdmissionWard::fromArray($row);
    }

    /**
     * @return AdmissionWard[]
     */
    public function getWardsForApplication(int $applicationId): array
    {
        $sql = 'SELECT w.*, l.name as academic_level_name, s.admission_number as student_admission_number
                FROM `admission_wards` w
                JOIN `academic_levels` l ON l.id = w.applying_for_level_id
                LEFT JOIN `students` s ON s.id = w.converted_student_id
                WHERE w.application_id = :app_id
                ORDER BY w.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':app_id' => $applicationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $wards = [];
        foreach ($rows as $row) {
            $wards[] = AdmissionWard::fromArray($row);
        }

        return $wards;
    }

    private ?array $wardColumns = null;

    private function getWardColumns(): array
    {
        if ($this->wardColumns !== null) {
            return $this->wardColumns;
        }

        $cols = [];
        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(`admission_wards`)");
                if ($stmt) {
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $cols[] = $row['name'];
                    }
                }
            } else {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `admission_wards`");
                if ($stmt) {
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $cols[] = $row['Field'];
                    }
                }
            }
        } catch (\Throwable) {
        }

        return $this->wardColumns = $cols;
    }

    public function addWard(int $applicationId, array $data): AdmissionWard
    {
        $now = date('Y-m-d H:i:s');
        $available = $this->getWardColumns();

        $allData = [
            'application_id' => $applicationId,
            'first_name' => trim((string)$data['first_name']),
            'middle_name' => isset($data['middle_name']) && $data['middle_name'] !== '' ? trim((string)$data['middle_name']) : null,
            'last_name' => trim((string)$data['last_name']),
            'date_of_birth' => (string)$data['date_of_birth'],
            'gender' => strtolower((string)$data['gender']),
            'state_of_origin' => !empty($data['state_of_origin']) ? trim((string)$data['state_of_origin']) : null,
            'lga' => !empty($data['lga']) ? trim((string)$data['lga']) : null,
            'nationality' => !empty($data['nationality']) ? trim((string)$data['nationality']) : 'Nigerian',
            'religion' => !empty($data['religion']) ? trim((string)$data['religion']) : null,
            'applying_for_level_id' => (int)$data['applying_for_level_id'],
            'class_grade' => trim((string)$data['class_grade']),
            'curriculum_choice' => !empty($data['curriculum_choice']) ? trim((string)$data['curriculum_choice']) : null,
            'use_school_bus' => !empty($data['use_school_bus']) ? 1 : 0,
            'previous_school' => !empty($data['previous_school']) ? trim((string)$data['previous_school']) : null,
            'last_grade_passed' => !empty($data['last_grade_passed']) ? trim((string)$data['last_grade_passed']) : null,
            'medical_notes' => !empty($data['medical_notes']) ? trim((string)$data['medical_notes']) : null,
            'passport_photo_file_id' => !empty($data['passport_photo_file_id']) ? (int)$data['passport_photo_file_id'] : null,
            'birth_certificate_file_id' => !empty($data['birth_certificate_file_id']) ? (int)$data['birth_certificate_file_id'] : null,
            'previous_report_file_id' => !empty($data['previous_report_file_id']) ? (int)$data['previous_report_file_id'] : null,
            'payment_status' => AdmissionWard::PAYMENT_UNPAID,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $cols = [];
        $placeholders = [];
        $params = [];

        foreach ($allData as $col => $val) {
            if (empty($available) || in_array($col, $available, true)) {
                $cols[] = "`{$col}`";
                $placeholders[] = ":{$col}";
                $params[":{$col}"] = $val;
            }
        }

        $sql = 'INSERT INTO `admission_wards` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findWardById($id);
    }

    public function updateWardPaymentStatus(int $wardId, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `admission_wards` SET `payment_status` = :status, `updated_at` = :updated_at WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $wardId,
            ':status' => $status,
            ':updated_at' => $now,
        ]);
    }

    public function updateWard(int $wardId, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $available = $this->getWardColumns();
        $allowed = [
            'first_name', 'middle_name', 'last_name', 'date_of_birth',
            'gender', 'state_of_origin', 'lga', 'nationality', 'religion',
            'applying_for_level_id', 'class_grade', 'curriculum_choice',
            'use_school_bus', 'previous_school', 'last_grade_passed', 'medical_notes',
            'passport_photo_file_id', 'birth_certificate_file_id', 'previous_report_file_id',
            'payment_status', 'converted_student_id'
        ];

        $fields = ['`updated_at` = :updated_at'];
        $params = [
            ':id' => $wardId,
            ':updated_at' => $now,
        ];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data) && (empty($available) || in_array($col, $available, true))) {
                $fields[] = "`{$col}` = :{$col}";
                $params[":{$col}"] = $data[$col];
            }
        }

        $sql = 'UPDATE `admission_wards` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteWard(int $wardId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `admission_wards` WHERE `id` = :id');
        return $stmt->execute([':id' => $wardId]);
    }

    public function getSuccessfulPaymentForWard(int $wardId): ?AdmissionPayment
    {
        $sql = 'SELECT * FROM `admission_payments` 
                WHERE `ward_id` = :ward_id AND `status` = :status 
                ORDER BY `id` DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ward_id' => $wardId,
            ':status' => AdmissionPayment::STATUS_SUCCESSFUL,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AdmissionPayment::fromArray($row) : null;
    }

    public function linkWardConvertedStudent(int $wardId, int $studentId): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `admission_wards` SET `converted_student_id` = :student_id, `updated_at` = :updated_at WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $wardId,
            ':student_id' => $studentId,
            ':updated_at' => $now,
        ]);
    }

    /* -------------------------------------------------------------------------
     * ADMISSION PAYMENTS
     * ------------------------------------------------------------------------- */

    public function createPayment(array $data): AdmissionPayment
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `admission_payments` (
                    `application_id`, `ward_id`, `reference`, `user_id`, 
                    `amount`, `currency`, `channel`, `status`, 
                    `gateway_reference`, `metadata`, `paid_at`, `created_at`, `updated_at`
                ) VALUES (
                    :application_id, :ward_id, :reference, :user_id,
                    :amount, :currency, :channel, :status,
                    :gateway_reference, :metadata, :paid_at, :created_at, :updated_at
                )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':application_id' => (int)$data['application_id'],
            ':ward_id' => (int)$data['ward_id'],
            ':reference' => trim((string)$data['reference']),
            ':user_id' => (int)$data['user_id'],
            ':amount' => (float)$data['amount'],
            ':currency' => (string)($data['currency'] ?? 'NGN'),
            ':channel' => (string)($data['channel'] ?? 'paystack'),
            ':status' => (string)($data['status'] ?? AdmissionPayment::STATUS_PENDING),
            ':gateway_reference' => $data['gateway_reference'] ?? null,
            ':metadata' => !empty($data['metadata']) ? json_encode($data['metadata']) : null,
            ':paid_at' => $data['paid_at'] ?? null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findPaymentById($id);
    }

    public function findPaymentById(int $id): ?AdmissionPayment
    {
        $sql = 'SELECT * FROM `admission_payments` WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AdmissionPayment::fromArray($row) : null;
    }

    public function findPaymentByReference(string $reference): ?AdmissionPayment
    {
        $sql = 'SELECT * FROM `admission_payments` WHERE reference = :ref LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ref' => trim($reference)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AdmissionPayment::fromArray($row) : null;
    }

    public function updatePaymentStatus(
        int $paymentId,
        string $status,
        ?string $gatewayReference = null,
        ?string $paidAt = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `admission_payments` 
                SET `status` = :status, `gateway_reference` = :gateway_ref, `paid_at` = :paid_at, `updated_at` = :updated_at 
                WHERE `id` = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $paymentId,
            ':status' => $status,
            ':gateway_ref' => $gatewayReference,
            ':paid_at' => $paidAt ?? ($status === AdmissionPayment::STATUS_SUCCESSFUL ? $now : null),
            ':updated_at' => $now,
        ]);
    }

    public function logStatusHistory(
        int $applicationId,
        string $fromStatus,
        string $toStatus,
        int $changedBy,
        ?string $comment = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `admission_status_history` (`application_id`, `from_status`, `to_status`, `comment`, `changed_by`, `created_at`)
                VALUES (:app_id, :from_status, :to_status, :comment, :changed_by, :created_at)';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':app_id' => $applicationId,
            ':from_status' => $fromStatus,
            ':to_status' => $toStatus,
            ':comment' => $comment,
            ':changed_by' => $changedBy,
            ':created_at' => $now,
        ]);
    }

    public function getStatusHistory(int $applicationId): array
    {
        $sql = 'SELECT h.*, u.name as changed_by_name, u.email as changed_by_email
                FROM `admission_status_history` h
                LEFT JOIN `users` u ON u.id = h.changed_by
                WHERE h.application_id = :app_id
                ORDER BY h.created_at ASC, h.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':app_id' => $applicationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function generateStudentAdmissionNumber(): string
    {
        $stmt = $this->pdo->query("SELECT admission_number FROM students WHERE admission_number LIKE 'STD-%' ORDER BY id DESC");
        $maxNum = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (preg_match('/^STD-(\d+)$/', (string)$row['admission_number'], $matches)) {
                $val = (int)$matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }
        return sprintf('STD-%05d', $maxNum + 1);
    }

    public function getNextSequentialNumber(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM `admission_applications`');
        return ((int)$stmt->fetchColumn()) + 1;
    }
}
