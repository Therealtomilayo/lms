<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\FileRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use PDO;

/**
 * Data Access Layer for Assignments and Assignment Submissions
 */
class AssignmentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function create(
        int $classSubjectId,
        int $termId,
        int $teacherId,
        string $title,
        string $instructions,
        string $dueAt,
        float $maxScore = 100.00,
        ?string $topic = null,
        ?int $fileId = null,
        string $status = Assignment::STATUS_PUBLISHED,
        ?int $assessmentCategoryId = null
    ): Assignment {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `assignments` (
                `class_subject_id`, `term_id`, `teacher_id`, `topic`, `title`, 
                `instructions`, `due_at`, `max_score`, `file_id`, `status`, 
                `assessment_category_id`, `created_at`, `updated_at`
            ) VALUES (
                :class_subject_id, :term_id, :teacher_id, :topic, :title, 
                :instructions, :due_at, :max_score, :file_id, :status, 
                :assessment_category_id, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
            ':teacher_id' => $teacherId,
            ':topic' => $topic !== null && trim($topic) !== '' ? trim($topic) : null,
            ':title' => trim($title),
            ':instructions' => $instructions,
            ':due_at' => $dueAt,
            ':max_score' => $maxScore,
            ':file_id' => $fileId,
            ':status' => $status,
            ':assessment_category_id' => $assessmentCategoryId,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('topic', $data)) {
            $fields[] = '`topic` = :topic';
            $params[':topic'] = $data['topic'] !== null && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null;
        }

        if (array_key_exists('title', $data)) {
            $fields[] = '`title` = :title';
            $params[':title'] = trim((string)$data['title']);
        }

        if (array_key_exists('instructions', $data)) {
            $fields[] = '`instructions` = :instructions';
            $params[':instructions'] = $data['instructions'];
        }

        if (array_key_exists('due_at', $data)) {
            $fields[] = '`due_at` = :due_at';
            $params[':due_at'] = $data['due_at'];
        }

        if (array_key_exists('max_score', $data)) {
            $fields[] = '`max_score` = :max_score';
            $params[':max_score'] = (float)$data['max_score'];
        }

        if (array_key_exists('file_id', $data)) {
            $fields[] = '`file_id` = :file_id';
            $params[':file_id'] = $data['file_id'] !== null && $data['file_id'] !== '' ? (int)$data['file_id'] : null;
        }

        if (array_key_exists('status', $data)) {
            $fields[] = '`status` = :status';
            $params[':status'] = $data['status'];
        }

        if (array_key_exists('term_id', $data)) {
            $fields[] = '`term_id` = :term_id';
            $params[':term_id'] = (int)$data['term_id'];
        }

        if (array_key_exists('class_subject_id', $data)) {
            $fields[] = '`class_subject_id` = :class_subject_id';
            $params[':class_subject_id'] = (int)$data['class_subject_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = '`updated_at` = :updated_at';
        $params[':updated_at'] = date('Y-m-d H:i:s');

        $sql = 'UPDATE `assignments` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `assignments` WHERE `id` = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function findById(int $id): ?Assignment
    {
        $sql = 'SELECT a.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       tm.name as term_name, tm.start_date as term_start_date, tm.end_date as term_end_date,
                       tm.status as term_status, tm.session_id as term_session_id,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignments` a
                JOIN `class_subjects` cs ON cs.id = a.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `terms` tm ON tm.id = a.term_id
                JOIN `teachers` t ON t.id = a.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = a.file_id AND f.deleted_at IS NULL
                WHERE a.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Assignment::fromArray($row) : null;
    }

    /**
     * @return Assignment[]
     */
    public function findByClassSubjectAndTerm(int $classSubjectId, int $termId): array
    {
        $sql = 'SELECT a.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       tm.name as term_name, tm.start_date as term_start_date, tm.end_date as term_end_date,
                       tm.status as term_status, tm.session_id as term_session_id,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignments` a
                JOIN `class_subjects` cs ON cs.id = a.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `terms` tm ON tm.id = a.term_id
                JOIN `teachers` t ON t.id = a.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = a.file_id AND f.deleted_at IS NULL
                WHERE a.class_subject_id = :class_subject_id AND a.term_id = :term_id
                ORDER BY a.due_at DESC, a.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_subject_id' => $classSubjectId, ':term_id' => $termId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Assignment::fromArray($row), $rows);
    }

    /**
     * @return Assignment[]
     */
    public function findForTeacher(int $teacherId, ?int $sessionId = null): array
    {
        $params = [':teacher_id' => $teacherId];
        $whereSession = '';
        if ($sessionId !== null) {
            $whereSession = ' AND cs.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }

        $sql = "SELECT a.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       tm.name as term_name, tm.start_date as term_start_date, tm.end_date as term_end_date,
                       tm.status as term_status, tm.session_id as term_session_id,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignments` a
                JOIN `class_subjects` cs ON cs.id = a.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `terms` tm ON tm.id = a.term_id
                JOIN `teachers` t ON t.id = a.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = a.file_id AND f.deleted_at IS NULL
                WHERE a.teacher_id = :teacher_id {$whereSession}
                ORDER BY a.due_at DESC, a.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Assignment::fromArray($row), $rows);
    }

    /**
     * @param int[] $classSubjectIds
     * @return Assignment[]
     */
    public function findPublishedForMultipleClassSubjects(array $classSubjectIds, ?int $termId = null): array
    {
        if (empty($classSubjectIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($classSubjectIds), '?'));
        $params = array_values($classSubjectIds);

        $termClause = '';
        if ($termId !== null) {
            $termClause = ' AND a.term_id = ?';
            $params[] = $termId;
        }

        $sql = "SELECT a.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       tm.name as term_name, tm.start_date as term_start_date, tm.end_date as term_end_date,
                       tm.status as term_status, tm.session_id as term_session_id,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignments` a
                JOIN `class_subjects` cs ON cs.id = a.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `terms` tm ON tm.id = a.term_id
                JOIN `teachers` t ON t.id = a.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = a.file_id AND f.deleted_at IS NULL
                WHERE a.class_subject_id IN ({$placeholders})
                  AND a.status = 'published'
                  {$termClause}
                ORDER BY a.due_at ASC, a.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Assignment::fromArray($row), $rows);
    }

    /**
     * @return string[]
     */
    public function getTopicsByClassSubject(int $classSubjectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT `topic` 
             FROM `assignments` 
             WHERE `class_subject_id` = :class_subject_id AND `topic` IS NOT NULL AND `topic` != "" 
             ORDER BY `topic` ASC'
        );
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_filter($rows, fn($r) => is_string($r) && trim($r) !== '');
    }

    public function createSubmission(
        int $assignmentId,
        int $studentId,
        string $submittedAt,
        ?int $fileId = null,
        ?string $textResponse = null
    ): AssignmentSubmission {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `assignment_submissions` (
                `assignment_id`, `student_id`, `submitted_at`, `file_id`, `text_response`, `created_at`, `updated_at`
            ) VALUES (
                :assignment_id, :student_id, :submitted_at, :file_id, :text_response, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            ':assignment_id' => $assignmentId,
            ':student_id' => $studentId,
            ':submitted_at' => $submittedAt,
            ':file_id' => $fileId,
            ':text_response' => $textResponse !== null && trim($textResponse) !== '' ? trim($textResponse) : null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findSubmissionById($id);
    }

    public function updateSubmission(int $submissionId, array $data): bool
    {
        $fields = [];
        $params = [':id' => $submissionId];

        if (array_key_exists('file_id', $data)) {
            $fields[] = '`file_id` = :file_id';
            $params[':file_id'] = $data['file_id'] !== null && $data['file_id'] !== '' ? (int)$data['file_id'] : null;
        }

        if (array_key_exists('text_response', $data)) {
            $fields[] = '`text_response` = :text_response';
            $params[':text_response'] = $data['text_response'] !== null && trim((string)$data['text_response']) !== '' ? trim((string)$data['text_response']) : null;
        }

        if (array_key_exists('submitted_at', $data)) {
            $fields[] = '`submitted_at` = :submitted_at';
            $params[':submitted_at'] = $data['submitted_at'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = '`updated_at` = :updated_at';
        $params[':updated_at'] = date('Y-m-d H:i:s');

        $sql = 'UPDATE `assignment_submissions` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function findSubmissionById(int $submissionId): ?AssignmentSubmission
    {
        $sql = 'SELECT sub.*, 
                       st.admission_number as student_admission_number, st.user_id as student_user_id,
                       st.date_of_birth as student_dob, st.gender as student_gender,
                       st.current_class_id as student_current_class_id,
                       su.name as student_name, su.email as student_email,
                       c.name as class_name,
                       gt.user_id as grader_user_id, gt.staff_id as grader_staff_id,
                       gu.name as grader_name, gu.email as grader_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignment_submissions` sub
                JOIN `students` st ON st.id = sub.student_id
                JOIN `users` su ON su.id = st.user_id
                LEFT JOIN `classes` c ON c.id = st.current_class_id
                LEFT JOIN `teachers` gt ON gt.id = sub.graded_by
                LEFT JOIN `users` gu ON gu.id = gt.user_id
                LEFT JOIN `files` f ON f.id = sub.file_id AND f.deleted_at IS NULL
                WHERE sub.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $submissionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $assignment = $this->findById((int)$row['assignment_id']);

        return AssignmentSubmission::fromArray($row, assignment: $assignment);
    }

    public function findSubmissionByAssignmentAndStudent(int $assignmentId, int $studentId): ?AssignmentSubmission
    {
        $sql = 'SELECT sub.*, 
                       st.admission_number as student_admission_number, st.user_id as student_user_id,
                       st.date_of_birth as student_dob, st.gender as student_gender,
                       st.current_class_id as student_current_class_id,
                       su.name as student_name, su.email as student_email,
                       c.name as class_name,
                       gt.user_id as grader_user_id, gt.staff_id as grader_staff_id,
                       gu.name as grader_name, gu.email as grader_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignment_submissions` sub
                JOIN `students` st ON st.id = sub.student_id
                JOIN `users` su ON su.id = st.user_id
                LEFT JOIN `classes` c ON c.id = st.current_class_id
                LEFT JOIN `teachers` gt ON gt.id = sub.graded_by
                LEFT JOIN `users` gu ON gu.id = gt.user_id
                LEFT JOIN `files` f ON f.id = sub.file_id AND f.deleted_at IS NULL
                WHERE sub.assignment_id = :assignment_id AND sub.student_id = :student_id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':assignment_id' => $assignmentId, ':student_id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $assignment = $this->findById((int)$row['assignment_id']);

        return AssignmentSubmission::fromArray($row, assignment: $assignment);
    }

    /**
     * @return AssignmentSubmission[]
     */
    public function getSubmissionsForAssignment(int $assignmentId): array
    {
        $sql = 'SELECT sub.*, 
                       st.admission_number as student_admission_number, st.user_id as student_user_id,
                       st.date_of_birth as student_dob, st.gender as student_gender,
                       st.current_class_id as student_current_class_id,
                       su.name as student_name, su.email as student_email,
                       c.name as class_name,
                       gt.user_id as grader_user_id, gt.staff_id as grader_staff_id,
                       gu.name as grader_name, gu.email as grader_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `assignment_submissions` sub
                JOIN `students` st ON st.id = sub.student_id
                JOIN `users` su ON su.id = st.user_id
                LEFT JOIN `classes` c ON c.id = st.current_class_id
                LEFT JOIN `teachers` gt ON gt.id = sub.graded_by
                LEFT JOIN `users` gu ON gu.id = gt.user_id
                LEFT JOIN `files` f ON f.id = sub.file_id AND f.deleted_at IS NULL
                WHERE sub.assignment_id = :assignment_id
                ORDER BY sub.submitted_at ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':assignment_id' => $assignmentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignment = $this->findById($assignmentId);

        return array_map(fn(array $row) => AssignmentSubmission::fromArray($row, assignment: $assignment), $rows);
    }

    public function gradeSubmission(int $submissionId, float $score, ?string $teacherComment, int $gradedByTeacherId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `assignment_submissions` 
             SET `score` = :score, 
                 `teacher_comment` = :teacher_comment, 
                 `graded_at` = :graded_at, 
                 `graded_by` = :graded_by, 
                 `updated_at` = :updated_at 
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $submissionId,
            ':score' => $score,
            ':teacher_comment' => $teacherComment !== null && trim($teacherComment) !== '' ? trim($teacherComment) : null,
            ':graded_at' => $now,
            ':graded_by' => $gradedByTeacherId,
            ':updated_at' => $now,
        ]);
    }

    public function countSubmissions(int $assignmentId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `assignment_submissions` WHERE `assignment_id` = :assignment_id');
        $stmt->execute([':assignment_id' => $assignmentId]);

        return (int)$stmt->fetchColumn();
    }

    public function countGradedSubmissions(int $assignmentId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `assignment_submissions` WHERE `assignment_id` = :assignment_id AND `graded_at` IS NOT NULL');
        $stmt->execute([':assignment_id' => $assignmentId]);

        return (int)$stmt->fetchColumn();
    }
}
