<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Student Profiles and Lookups
 */
class StudentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function findById(int $id): ?Student
    {
        $sql = 'SELECT s.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status
                FROM `students` s
                JOIN `users` u ON u.id = s.user_id
                LEFT JOIN `classes` c ON c.id = s.current_class_id
                WHERE s.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Student::fromArray($row);
    }

    public function findByUserId(int $userId): ?Student
    {
        $sql = 'SELECT s.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status
                FROM `students` s
                JOIN `users` u ON u.id = s.user_id
                LEFT JOIN `classes` c ON c.id = s.current_class_id
                WHERE s.user_id = :user_id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Student::fromArray($row);
    }

    public function findByAdmissionNumber(string $admissionNumber): ?Student
    {
        $sql = 'SELECT s.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status
                FROM `students` s
                JOIN `users` u ON u.id = s.user_id
                LEFT JOIN `classes` c ON c.id = s.current_class_id
                WHERE LOWER(s.admission_number) = LOWER(:adm_no) LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':adm_no' => trim($admissionNumber)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return Student::fromArray($row);
    }

    public function create(
        int $userId,
        string $admissionNumber,
        ?string $dateOfBirth = null,
        ?string $gender = null,
        ?int $currentClassId = null
    ): Student {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `students` (`user_id`, `admission_number`, `date_of_birth`, `gender`, `current_class_id`, `created_at`, `updated_at`)
                VALUES (:user_id, :admission_number, :date_of_birth, :gender, :current_class_id, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':admission_number' => trim($admissionNumber),
            ':date_of_birth' => $dateOfBirth ?: null,
            ':gender' => $gender ?: null,
            ':current_class_id' => $currentClassId ?: null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $studentId = (int)$this->pdo->lastInsertId();

        return $this->findById($studentId);
    }

    public function update(
        int $studentId,
        ?string $admissionNumber = null,
        ?string $dateOfBirth = null,
        ?string $gender = null,
        ?int $currentClassId = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $fields = ['`updated_at` = :updated_at'];
        $params = [
            ':id' => $studentId,
            ':updated_at' => $now,
        ];

        if ($admissionNumber !== null) {
            $fields[] = '`admission_number` = :admission_number';
            $params[':admission_number'] = trim($admissionNumber);
        }
        if ($dateOfBirth !== null) {
            $fields[] = '`date_of_birth` = :date_of_birth';
            $params[':date_of_birth'] = $dateOfBirth ?: null;
        }
        if ($gender !== null) {
            $fields[] = '`gender` = :gender';
            $params[':gender'] = $gender ?: null;
        }
        if ($currentClassId !== null) {
            $fields[] = '`current_class_id` = :current_class_id';
            $params[':current_class_id'] = $currentClassId > 0 ? $currentClassId : null;
        }

        $sql = 'UPDATE `students` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * @return Student[]
     */
    public function getAll(int $limit = 50, int $offset = 0, ?int $classId = null, ?string $search = null): array
    {
        $where = [];
        $params = [];

        if ($classId !== null && $classId > 0) {
            $where[] = 's.current_class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR s.admission_number LIKE :search_adm)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_adm'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT s.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status
                FROM `students` s
                JOIN `users` u ON u.id = s.user_id
                LEFT JOIN `classes` c ON c.id = s.current_class_id
                {$whereClause}
                ORDER BY u.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $students = [];

        foreach ($rows as $row) {
            $students[] = Student::fromArray($row);
        }

        return $students;
    }

    public function countAll(?int $classId = null, ?string $search = null): int
    {
        $where = [];
        $params = [];

        if ($classId !== null && $classId > 0) {
            $where[] = 's.current_class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR s.admission_number LIKE :search_adm)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_adm'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM `students` s JOIN `users` u ON u.id = s.user_id {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get enrolled students for one or more class subject IDs.
     * Supports both explicit student_subject_enrollments and class_enrollments fallback.
     *
     * @param int[] $classSubjectIds
     * @return Student[]
     */
    public function getStudentsByClassSubjectIds(array $classSubjectIds): array
    {
        $csIds = array_values(array_filter(array_map('intval', $classSubjectIds), fn($id) => $id > 0));
        if (empty($csIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($csIds), '?'));

        // Query students enrolled via explicit student_subject_enrollments OR class_enrollments of these subjects
        $sql = "SELECT DISTINCT s.*,
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status
                FROM `students` s
                JOIN `users` u ON u.id = s.user_id
                LEFT JOIN `classes` c ON c.id = s.current_class_id
                WHERE s.id IN (
                    SELECT sse.student_id 
                    FROM `student_subject_enrollments` sse 
                    WHERE sse.class_subject_id IN ({$placeholders}) AND sse.status = 'active'
                    UNION
                    SELECT ce.student_id
                    FROM `class_enrollments` ce
                    JOIN `class_subjects` cs ON cs.class_id = ce.class_id AND cs.session_id = ce.session_id
                    WHERE cs.id IN ({$placeholders}) AND ce.status = 'active'
                )
                ORDER BY u.name ASC, s.admission_number ASC";

        $params = array_merge($csIds, $csIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $students = [];
        foreach ($rows as $row) {
            $students[] = Student::fromArray($row);
        }

        return $students;
    }

    /**
     * Generate a unique student admission number that does not clash with existing records.
     */
    public function generateAdmissionNumber(): string
    {
        $stmt = $this->pdo->query("SELECT admission_number FROM `students` WHERE `admission_number` LIKE 'STD-%' ORDER BY `id` DESC");
        $maxNum = 0;
        $existing = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $adm = trim((string)$row['admission_number']);
            $existing[strtolower($adm)] = true;
            if (preg_match('/^STD-(\d+)$/i', $adm, $matches)) {
                $val = (int)$matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        $candidateNum = $maxNum + 1;
        do {
            $candidate = sprintf('STD-%05d', $candidateNum);
            $candidateNum++;
        } while (isset($existing[strtolower($candidate)]));

        return $candidate;
    }
}
