<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use PDO;

/**
 * Data Access Layer for Class & Subject Enrollments
 */
class EnrollmentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function findClassEnrollmentById(int $id): ?ClassEnrollment
    {
        $sql = 'SELECT ce.*, 
                       s.admission_number, s.date_of_birth, s.gender, s.current_class_id,
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status,
                       ses.name as session_name, ses.start_date as session_start_date, ses.end_date as session_end_date, ses.status as session_status
                FROM `class_enrollments` ce
                JOIN `students` s ON s.id = ce.student_id
                JOIN `users` u ON u.id = s.user_id
                JOIN `classes` c ON c.id = ce.class_id
                JOIN `sessions` ses ON ses.id = ce.session_id
                WHERE ce.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateClassEnrollment($row);
    }

    public function findClassEnrollment(int $studentId, int $sessionId): ?ClassEnrollment
    {
        $sql = 'SELECT ce.*, 
                       s.admission_number, s.date_of_birth, s.gender, s.current_class_id,
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status,
                       ses.name as session_name, ses.start_date as session_start_date, ses.end_date as session_end_date, ses.status as session_status
                FROM `class_enrollments` ce
                JOIN `students` s ON s.id = ce.student_id
                JOIN `users` u ON u.id = s.user_id
                JOIN `classes` c ON c.id = ce.class_id
                JOIN `sessions` ses ON ses.id = ce.session_id
                WHERE ce.student_id = :student_id AND ce.session_id = :session_id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':session_id' => $sessionId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateClassEnrollment($row);
    }

    public function enrollInClass(int $studentId, int $classId, int $sessionId, string $status = 'active'): ClassEnrollment
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->findClassEnrollment($studentId, $sessionId);

        if ($existing) {
            $sql = 'UPDATE `class_enrollments` SET `class_id` = :class_id, `status` = :status, `updated_at` = :updated_at WHERE `id` = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $existing->id,
                ':class_id' => $classId,
                ':status' => $status,
                ':updated_at' => $now,
            ]);
        } else {
            $sql = 'INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `enrolled_at`, `created_at`, `updated_at`)
                    VALUES (:student_id, :class_id, :session_id, :status, :enrolled_at, :created_at, :updated_at)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':student_id' => $studentId,
                ':class_id' => $classId,
                ':session_id' => $sessionId,
                ':status' => $status,
                ':enrolled_at' => $now,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        return $this->findClassEnrollment($studentId, $sessionId);
    }

    /**
     * @return ClassEnrollment[]
     */
    public function getClassRoster(int $classId, int $sessionId, ?string $status = null): array
    {
        $where = ['ce.class_id = :class_id', 'ce.session_id = :session_id'];
        $params = [
            ':class_id' => $classId,
            ':session_id' => $sessionId,
        ];

        if ($status !== null) {
            $where[] = 'ce.status = :status';
            $params[':status'] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT ce.*, 
                       s.admission_number, s.date_of_birth, s.gender, s.current_class_id,
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status,
                       ses.name as session_name, ses.start_date as session_start_date, ses.end_date as session_end_date, ses.status as session_status
                FROM `class_enrollments` ce
                JOIN `students` s ON s.id = ce.student_id
                JOIN `users` u ON u.id = s.user_id
                JOIN `classes` c ON c.id = ce.class_id
                JOIN `sessions` ses ON ses.id = ce.session_id
                {$whereClause}
                ORDER BY u.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $roster = [];
        foreach ($rows as $row) {
            $roster[] = $this->hydrateClassEnrollment($row);
        }

        return $roster;
    }

    public function updateClassEnrollmentStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `class_enrollments` SET `status` = :status, `updated_at` = :now WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':now' => $now,
        ]);
    }

    public function isStudentEnrolledInSubject(int $studentId, int $classSubjectId, ?int $sessionId = null): bool
    {
        $sql = 'SELECT 1 FROM `student_subject_enrollments` 
                WHERE `student_id` = :student_id 
                  AND `class_subject_id` = :class_subject_id';
        $params = [
            ':student_id' => $studentId,
            ':class_subject_id' => $classSubjectId,
        ];

        if ($sessionId !== null) {
            $sql .= ' AND `session_id` = :session_id';
            $params[':session_id'] = $sessionId;
        }

        $sql .= ' AND `status` = "active" LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    public function enrollInSubject(
        int $studentId,
        int $classSubjectId,
        int $sessionId,
        bool $isElective = false,
        string $status = 'active'
    ): StudentSubjectEnrollment {
        $now = date('Y-m-d H:i:s');
        $existing = $this->findSubjectEnrollment($studentId, $classSubjectId);

        if ($existing) {
            $sql = 'UPDATE `student_subject_enrollments` 
                    SET `is_elective` = :is_elective, `status` = :status, `updated_at` = :updated_at 
                    WHERE `id` = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $existing->id,
                ':is_elective' => $isElective ? 1 : 0,
                ':status' => $status,
                ':updated_at' => $now,
            ]);
        } else {
            $sql = 'INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `session_id`, `is_elective`, `status`, `created_at`, `updated_at`)
                    VALUES (:student_id, :class_subject_id, :session_id, :is_elective, :status, :created_at, :updated_at)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':student_id' => $studentId,
                ':class_subject_id' => $classSubjectId,
                ':session_id' => $sessionId,
                ':is_elective' => $isElective ? 1 : 0,
                ':status' => $status,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        return $this->findSubjectEnrollment($studentId, $classSubjectId);
    }

    public function findSubjectEnrollment(int $studentId, int $classSubjectId): ?StudentSubjectEnrollment
    {
        $sql = 'SELECT sse.*,
                       s.admission_number, u.name as user_name, u.email as user_email
                FROM `student_subject_enrollments` sse
                JOIN `students` s ON s.id = sse.student_id
                JOIN `users` u ON u.id = s.user_id
                WHERE sse.student_id = :student_id AND sse.class_subject_id = :class_subject_id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':class_subject_id' => $classSubjectId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return StudentSubjectEnrollment::fromArray($row);
    }

    /**
     * @return StudentSubjectEnrollment[]
     */
    public function getStudentSubjectEnrollments(int $studentId, int $sessionId, ?string $status = 'active'): array
    {
        $where = ['sse.student_id = :student_id', 'sse.session_id = :session_id'];
        $params = [
            ':student_id' => $studentId,
            ':session_id' => $sessionId,
        ];

        if ($status !== null) {
            $where[] = 'sse.status = :status';
            $params[':status'] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT sse.*,
                       cs.session_id, cs.class_id, cs.subject_id, cs.teacher_id, cs.status as class_subject_status,
                       sub.name as subject_name, sub.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t_u.name as teacher_name
                FROM `student_subject_enrollments` sse
                JOIN `class_subjects` cs ON cs.id = sse.class_subject_id
                JOIN `subjects` sub ON sub.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                LEFT JOIN `teachers` t ON t.id = cs.teacher_id
                LEFT JOIN `users` t_u ON t_u.id = t.user_id
                {$whereClause}
                ORDER BY sub.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $list = [];
        foreach ($rows as $row) {
            $classSubject = ClassSubject::fromArray([
                'id' => $row['class_subject_id'],
                'session_id' => $row['session_id'],
                'class_id' => $row['class_id'],
                'subject_id' => $row['subject_id'],
                'teacher_id' => $row['teacher_id'],
                'status' => $row['class_subject_status'],
                'subject_name' => $row['subject_name'],
                'subject_code' => $row['subject_code'],
                'class_name' => $row['class_name'],
                'section_arm' => $row['section_arm'],
                'teacher_name' => $row['teacher_name'],
            ]);

            $list[] = StudentSubjectEnrollment::fromArray($row, null, $classSubject);
        }

        return $list;
    }

    /**
     * @return Student[]
     */
    public function getStudentsByClassAndSession(int $classId, int $sessionId, string $status = 'active'): array
    {
        $sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
                FROM `class_enrollments` ce
                JOIN `students` s ON s.id = ce.student_id
                JOIN `users` u ON u.id = s.user_id
                WHERE ce.class_id = :class_id AND ce.session_id = :session_id AND ce.status = :status
                ORDER BY u.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':session_id' => $sessionId,
            ':status' => $status,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(function ($row) {
            $user = \App\Models\User::fromArray([
                'id' => $row['user_id'] ?? 0,
                'name' => $row['user_name'] ?? '',
                'email' => $row['user_email'] ?? '',
                'phone' => $row['user_phone'] ?? null,
                'status' => $row['user_status'] ?? 'active',
            ]);
            return Student::fromArray($row, $user);
        }, $rows);
    }

    /**
     * @return Student[]
     */
    public function getStudentsBySubjectAndSession(int $classId, int $subjectId, int $sessionId, string $status = 'active'): array
    {
        $sql = "SELECT DISTINCT s.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
                FROM `student_subject_enrollments` sse
                JOIN `class_subjects` cs ON cs.id = sse.class_subject_id
                JOIN `students` s ON s.id = sse.student_id
                JOIN `users` u ON u.id = s.user_id
                WHERE cs.class_id = :class_id 
                  AND cs.subject_id = :subject_id 
                  AND cs.session_id = :session_id
                  AND sse.status = :status
                ORDER BY u.name ASC";

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':class_id' => $classId,
                ':subject_id' => $subjectId,
                ':session_id' => $sessionId,
                ':status' => $status,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        if (empty($rows)) {
            return $this->getStudentsByClassAndSession($classId, $sessionId, $status);
        }

        return array_map(function ($row) {
            $user = \App\Models\User::fromArray([
                'id' => $row['user_id'] ?? 0,
                'name' => $row['user_name'] ?? '',
                'email' => $row['user_email'] ?? '',
                'phone' => $row['user_phone'] ?? null,
                'status' => $row['user_status'] ?? 'active',
            ]);
            return Student::fromArray($row, $user);
        }, $rows);
    }

    private function hydrateClassEnrollment(array $row): ClassEnrollment
    {
        $student = Student::fromArray([
            'id' => $row['student_id'],
            'user_id' => $row['user_id'] ?? 0,
            'admission_number' => $row['admission_number'] ?? '',
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'gender' => $row['gender'] ?? null,
            'current_class_id' => $row['current_class_id'] ?? null,
            'user_name' => $row['user_name'] ?? '',
            'user_email' => $row['user_email'] ?? '',
            'user_phone' => $row['user_phone'] ?? null,
            'user_status' => $row['user_status'] ?? 'active',
        ]);

        $class = SchoolClass::fromArray([
            'id' => $row['class_id'],
            'academic_level_id' => $row['academic_level_id'] ?? 0,
            'name' => $row['class_name'] ?? '',
            'section_arm' => $row['section_arm'] ?? null,
            'status' => $row['class_status'] ?? 'active',
        ]);

        $session = AcademicSession::fromArray([
            'id' => $row['session_id'],
            'name' => $row['session_name'] ?? '',
            'start_date' => $row['session_start_date'] ?? '',
            'end_date' => $row['session_end_date'] ?? '',
            'status' => $row['session_status'] ?? 'active',
        ]);

        return ClassEnrollment::fromArray($row, $student, $class, $session);
    }
}
