<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AssessmentCategory;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAssessmentScore;
use App\Models\StudentTermSummary;
use App\Models\Subject;
use App\Models\TermResult;
use App\Models\User;
use PDO;

/**
 * Repository for Gradebook, Assessment Scores, Term Results and Summaries
 */
final class GradebookRepository
{
    private readonly PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * @return array<int, AssessmentCategory>
     */
    public function getCategoriesByContext(int $sessionId, int $termId, ?int $classSubjectId = null, ?int $academicLevelId = null): array
    {
        if ($classSubjectId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT ac.*, al.name as academic_level_name FROM `assessment_categories` ac
                 LEFT JOIN `academic_levels` al ON al.id = ac.academic_level_id
                 WHERE ac.session_id = :session_id AND ac.term_id = :term_id AND ac.class_subject_id = :class_subject_id
                 ORDER BY ac.id ASC'
            );
            $stmt->execute([
                ':session_id' => $sessionId,
                ':term_id' => $termId,
                ':class_subject_id' => $classSubjectId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return array_map(fn (array $r) => AssessmentCategory::fromArray($r), $rows);
            }

            // If no specific class-subject override, determine class's academic level if not provided
            if ($academicLevelId === null) {
                try {
                    $csStmt = $this->pdo->prepare('SELECT c.academic_level_id FROM class_subjects cs JOIN classes c ON c.id = cs.class_id WHERE cs.id = :csid LIMIT 1');
                    $csStmt->execute([':csid' => $classSubjectId]);
                    $lvl = $csStmt->fetchColumn();
                    $academicLevelId = ($lvl !== false && $lvl !== null) ? (int)$lvl : null;
                } catch (\Throwable $e) {
                    $academicLevelId = null;
                }
            }
        }

        // Try academic-level specific categories if level is known
        if ($academicLevelId !== null && $academicLevelId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT ac.*, al.name as academic_level_name FROM `assessment_categories` ac
                 LEFT JOIN `academic_levels` al ON al.id = ac.academic_level_id
                 WHERE ac.session_id = :session_id AND ac.term_id = :term_id 
                   AND ac.academic_level_id = :level_id AND ac.class_subject_id IS NULL
                 ORDER BY ac.id ASC'
            );
            $stmt->execute([
                ':session_id' => $sessionId,
                ':term_id' => $termId,
                ':level_id' => $academicLevelId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return array_map(fn (array $r) => AssessmentCategory::fromArray($r), $rows);
            }
        }

        // Fallback to global term default categories (academic_level_id IS NULL and class_subject_id IS NULL)
        $stmt = $this->pdo->prepare(
            'SELECT ac.*, al.name as academic_level_name FROM `assessment_categories` ac
             LEFT JOIN `academic_levels` al ON al.id = ac.academic_level_id
             WHERE ac.session_id = :session_id AND ac.term_id = :term_id 
               AND ac.class_subject_id IS NULL AND ac.academic_level_id IS NULL
             ORDER BY ac.id ASC'
        );
        $stmt->execute([
            ':session_id' => $sessionId,
            ':term_id' => $termId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If no global defaults, return any categories configured for this session/term
        if (empty($rows)) {
            $stmt = $this->pdo->prepare(
                'SELECT ac.*, al.name as academic_level_name FROM `assessment_categories` ac
                 LEFT JOIN `academic_levels` al ON al.id = ac.academic_level_id
                 WHERE ac.session_id = :session_id AND ac.term_id = :term_id AND ac.class_subject_id IS NULL
                 ORDER BY ac.id ASC'
            );
            $stmt->execute([
                ':session_id' => $sessionId,
                ':term_id' => $termId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return array_map(fn (array $r) => AssessmentCategory::fromArray($r), $rows);
    }

    /**
     * @return array<int, AssessmentCategory>
     */
    public function getAllCategories(int $sessionId, int $termId, ?int $academicLevelId = null): array
    {
        $sql = 'SELECT ac.*, al.name as academic_level_name FROM `assessment_categories` ac
                LEFT JOIN `academic_levels` al ON al.id = ac.academic_level_id
                WHERE ac.session_id = :session_id AND ac.term_id = :term_id AND ac.class_subject_id IS NULL';
        
        $params = [
            ':session_id' => $sessionId,
            ':term_id' => $termId,
        ];

        if ($academicLevelId !== null) {
            $sql .= ' AND ac.academic_level_id = :academic_level_id';
            $params[':academic_level_id'] = $academicLevelId;
        }

        $sql .= ' ORDER BY ac.academic_level_id ASC, ac.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $r) => AssessmentCategory::fromArray($r), $rows);
    }

    public function findCategoryById(int $id): ?AssessmentCategory
    {
        $stmt = $this->pdo->prepare(
            'SELECT ac.*, al.name as academic_level_name FROM `assessment_categories` ac
             LEFT JOIN `academic_levels` al ON al.id = ac.academic_level_id
             WHERE ac.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AssessmentCategory::fromArray($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `assessment_categories` 
             (`session_id`, `term_id`, `class_subject_id`, `academic_level_id`, `name`, `weight_percentage`, `max_points`) 
             VALUES (:session_id, :term_id, :class_subject_id, :academic_level_id, :name, :weight_percentage, :max_points)'
        );
        $stmt->execute([
            ':session_id' => $data['session_id'],
            ':term_id' => $data['term_id'],
            ':class_subject_id' => !empty($data['class_subject_id']) ? (int)$data['class_subject_id'] : null,
            ':academic_level_id' => !empty($data['academic_level_id']) ? (int)$data['academic_level_id'] : null,
            ':name' => $data['name'],
            ':weight_percentage' => (float)$data['weight_percentage'],
            ':max_points' => (float)($data['max_points'] ?? 100.0),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCategory(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `assessment_categories` 
             SET `name` = :name, `weight_percentage` = :weight_percentage, `max_points` = :max_points,
                 `academic_level_id` = :academic_level_id 
             WHERE `id` = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':weight_percentage' => (float)$data['weight_percentage'],
            ':max_points' => (float)($data['max_points'] ?? 100.0),
            ':academic_level_id' => !empty($data['academic_level_id']) ? (int)$data['academic_level_id'] : null,
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        // Safe check for foreign key integrity
        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM `student_assessment_scores` WHERE `assessment_category_id` = :id');
        $countStmt->execute([':id' => $id]);
        $scoresCount = (int)$countStmt->fetchColumn();

        if ($scoresCount > 0) {
            throw new \RuntimeException("Cannot delete this assessment category because {$scoresCount} student assessment score(s) have already been recorded against it. You can edit its details instead.");
        }

        $stmt = $this->pdo->prepare('DELETE FROM `assessment_categories` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * @return array<int, StudentAssessmentScore>
     */
    public function getScoresByClassSubject(int $classSubjectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `student_assessment_scores` 
             WHERE `class_subject_id` = :class_subject_id'
        );
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $r) => StudentAssessmentScore::fromArray($r), $rows);
    }

    public function upsertScore(
        int $categoryId,
        int $studentId,
        int $classSubjectId,
        float $rawScore,
        int $recordedBy
    ): void {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = date('Y-m-d H:i:s');

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `student_assessment_scores` 
                 (`assessment_category_id`, `student_id`, `class_subject_id`, `raw_score`, `recorded_by`, `recorded_at`)
                 VALUES (:category_id, :student_id, :class_subject_id, :raw_score, :recorded_by, :recorded_at)
                 ON CONFLICT(`assessment_category_id`, `student_id`, `class_subject_id`) DO UPDATE SET 
                    `raw_score` = excluded.`raw_score`,
                    `recorded_by` = excluded.`recorded_by`,
                    `recorded_at` = excluded.`recorded_at`'
            );
            $stmt->execute([
                ':category_id' => $categoryId,
                ':student_id' => $studentId,
                ':class_subject_id' => $classSubjectId,
                ':raw_score' => $rawScore,
                ':recorded_by' => $recordedBy,
                ':recorded_at' => $now,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `student_assessment_scores` 
                 (`assessment_category_id`, `student_id`, `class_subject_id`, `raw_score`, `recorded_by`, `recorded_at`)
                 VALUES (:category_id, :student_id, :class_subject_id, :raw_score, :recorded_by, NOW())
                 ON DUPLICATE KEY UPDATE 
                    `raw_score` = VALUES(`raw_score`),
                    `recorded_by` = VALUES(`recorded_by`),
                    `recorded_at` = NOW()'
            );
            $stmt->execute([
                ':category_id' => $categoryId,
                ':student_id' => $studentId,
                ':class_subject_id' => $classSubjectId,
                ':raw_score' => $rawScore,
                ':recorded_by' => $recordedBy,
            ]);
        }
    }

    public function upsertTermResult(
        int $studentId,
        int $classSubjectId,
        int $termId,
        float $computedScore,
        string $gradeLetter,
        ?float $gradePoint,
        ?string $remark,
        array $breakdown,
        bool $isLocked = false,
        ?int $lockedBy = null
    ): void {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = date('Y-m-d H:i:s');
        $breakdownJson = json_encode($breakdown);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `term_results` 
                 (`student_id`, `class_subject_id`, `term_id`, `computed_score`, `grade_letter`, `grade_point`, `remark`, `breakdown_json`, `is_locked`, `locked_at`, `locked_by`)
                 VALUES (:student_id, :class_subject_id, :term_id, :computed_score, :grade_letter, :grade_point, :remark, :breakdown_json, :is_locked, :locked_at, :locked_by)
                 ON CONFLICT(`student_id`, `class_subject_id`, `term_id`) DO UPDATE SET 
                    `computed_score` = excluded.`computed_score`,
                    `grade_letter` = excluded.`grade_letter`,
                    `grade_point` = excluded.`grade_point`,
                    `remark` = excluded.`remark`,
                    `breakdown_json` = excluded.`breakdown_json`,
                    `is_locked` = excluded.`is_locked`,
                    `locked_at` = excluded.`locked_at`,
                    `locked_by` = excluded.`locked_by`'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':class_subject_id' => $classSubjectId,
                ':term_id' => $termId,
                ':computed_score' => $computedScore,
                ':grade_letter' => $gradeLetter,
                ':grade_point' => $gradePoint,
                ':remark' => $remark,
                ':breakdown_json' => $breakdownJson,
                ':is_locked' => $isLocked ? 1 : 0,
                ':locked_at' => $isLocked ? $now : null,
                ':locked_by' => $lockedBy,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `term_results` 
                 (`student_id`, `class_subject_id`, `term_id`, `computed_score`, `grade_letter`, `grade_point`, `remark`, `breakdown_json`, `is_locked`, `locked_at`, `locked_by`)
                 VALUES (:student_id, :class_subject_id, :term_id, :computed_score, :grade_letter, :grade_point, :remark, :breakdown_json, :is_locked, :locked_at, :locked_by)
                 ON DUPLICATE KEY UPDATE 
                    `computed_score` = VALUES(`computed_score`),
                    `grade_letter` = VALUES(`grade_letter`),
                    `grade_point` = VALUES(`grade_point`),
                    `remark` = VALUES(`remark`),
                    `breakdown_json` = VALUES(`breakdown_json`),
                    `is_locked` = VALUES(`is_locked`),
                    `locked_at` = VALUES(`locked_at`),
                    `locked_by` = VALUES(`locked_by`)'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':class_subject_id' => $classSubjectId,
                ':term_id' => $termId,
                ':computed_score' => $computedScore,
                ':grade_letter' => $gradeLetter,
                ':grade_point' => $gradePoint,
                ':remark' => $remark,
                ':breakdown_json' => $breakdownJson,
                ':is_locked' => $isLocked ? 1 : 0,
                ':locked_at' => $isLocked ? $now : null,
                ':locked_by' => $lockedBy,
            ]);
        }
    }

    /**
     * @return array<int, TermResult>
     */
    public function getTermResultsByClassSubject(int $classSubjectId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tr.*, u.name AS student_name, u.email AS student_email, s.admission_number,
                    sub.name AS subject_name, sub.code AS subject_code
             FROM `term_results` tr
             JOIN `students` s ON tr.student_id = s.id
             JOIN `users` u ON s.user_id = u.id
             JOIN `class_subjects` cs ON tr.class_subject_id = cs.id
             JOIN `subjects` sub ON cs.subject_id = sub.id
             WHERE tr.class_subject_id = :class_subject_id AND tr.term_id = :term_id
             ORDER BY u.name ASC'
        );
        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $student = Student::fromArray([
                'id' => $row['student_id'],
                'admission_number' => $row['admission_number'] ?? '',
            ], User::fromArray([
                'name' => $row['student_name'] ?? '',
                'email' => $row['student_email'] ?? '',
            ]));

            $subject = Subject::fromArray([
                'id' => 0,
                'name' => $row['subject_name'] ?? '',
                'code' => $row['subject_code'] ?? '',
            ]);

            $classSubject = ClassSubject::fromArray([
                'id' => $row['class_subject_id'],
            ], null, null, $subject);

            $results[] = TermResult::fromArray($row, $student, $classSubject);
        }

        return $results;
    }

    /**
     * @return array<int, TermResult>
     */
    public function getTermResultsByStudent(int $studentId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tr.*, 
                    sub.name AS subject_name, sub.code AS subject_code,
                    cs.class_id, cs.teacher_id, cs.session_id, cs.subject_id
             FROM `term_results` tr
             JOIN `class_subjects` cs ON tr.class_subject_id = cs.id
             JOIN `subjects` sub ON cs.subject_id = sub.id
             WHERE tr.student_id = :student_id AND tr.term_id = :term_id
             ORDER BY sub.name ASC'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':term_id' => $termId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $subject = Subject::fromArray([
                'id' => $row['subject_id'],
                'name' => $row['subject_name'],
                'code' => $row['subject_code'],
            ]);

            $classSubject = ClassSubject::fromArray([
                'id' => $row['class_subject_id'],
                'class_id' => $row['class_id'],
                'subject_id' => $row['subject_id'],
                'teacher_id' => $row['teacher_id'],
                'session_id' => $row['session_id'],
            ], null, null, $subject);

            $results[] = TermResult::fromArray($row, null, $classSubject);
        }

        return $results;
    }

    public function getTermResult(int $studentId, int $classSubjectId, int $termId): ?TermResult
    {
        $stmt = $this->pdo->prepare(
            'SELECT tr.*, 
                    sub.name AS subject_name, sub.code AS subject_code,
                    cs.class_id, cs.teacher_id, cs.session_id, cs.subject_id
             FROM `term_results` tr
             JOIN `class_subjects` cs ON tr.class_subject_id = cs.id
             JOIN `subjects` sub ON cs.subject_id = sub.id
             WHERE tr.student_id = :student_id 
               AND tr.class_subject_id = :class_subject_id 
               AND tr.term_id = :term_id
             LIMIT 1'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $subject = Subject::fromArray([
            'id' => $row['subject_id'],
            'name' => $row['subject_name'],
            'code' => $row['subject_code'],
        ]);

        $classSubject = ClassSubject::fromArray([
            'id' => $row['class_subject_id'],
            'class_id' => $row['class_id'],
            'subject_id' => $row['subject_id'],
            'teacher_id' => $row['teacher_id'],
            'session_id' => $row['session_id'],
        ], null, null, $subject);

        return TermResult::fromArray($row, null, $classSubject);
    }

    public function upsertStudentTermSummary(
        int $studentId,
        int $termId,
        int $classId,
        ?float $totalScore,
        ?float $averageScore,
        ?float $gpa,
        ?int $rankInClass,
        int $attendancePresent = 0,
        int $attendanceTotal = 0,
        ?string $teacherRemark = null,
        ?string $principalRemark = null,
        string $promotionStatus = 'pending',
        bool $isLocked = false,
        ?int $lockedBy = null
    ): void {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = date('Y-m-d H:i:s');

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `student_term_summaries` 
                 (`student_id`, `term_id`, `class_id`, `total_score`, `average_score`, `gpa`, `rank_in_class`, `attendance_present_count`, `attendance_total_count`, `class_teacher_remark`, `principal_remark`, `promotion_status`, `is_locked`, `locked_at`, `locked_by`)
                 VALUES (:student_id, :term_id, :class_id, :total_score, :average_score, :gpa, :rank_in_class, :attendance_present, :attendance_total, :teacher_remark, :principal_remark, :promotion_status, :is_locked, :locked_at, :locked_by)
                 ON CONFLICT(`student_id`, `term_id`, `class_id`) DO UPDATE SET 
                    `total_score` = excluded.`total_score`,
                    `average_score` = excluded.`average_score`,
                    `gpa` = excluded.`gpa`,
                    `rank_in_class` = excluded.`rank_in_class`,
                    `attendance_present_count` = excluded.`attendance_present_count`,
                    `attendance_total_count` = excluded.`attendance_total_count`,
                    `class_teacher_remark` = excluded.`class_teacher_remark`,
                    `principal_remark` = excluded.`principal_remark`,
                    `promotion_status` = excluded.`promotion_status`,
                    `is_locked` = excluded.`is_locked`,
                    `locked_at` = excluded.`locked_at`,
                    `locked_by` = excluded.`locked_by`'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':term_id' => $termId,
                ':class_id' => $classId,
                ':total_score' => $totalScore,
                ':average_score' => $averageScore,
                ':gpa' => $gpa,
                ':rank_in_class' => $rankInClass,
                ':attendance_present' => $attendancePresent,
                ':attendance_total' => $attendanceTotal,
                ':teacher_remark' => $teacherRemark,
                ':principal_remark' => $principalRemark,
                ':promotion_status' => $promotionStatus,
                ':is_locked' => $isLocked ? 1 : 0,
                ':locked_at' => $isLocked ? $now : null,
                ':locked_by' => $lockedBy,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `student_term_summaries` 
                 (`student_id`, `term_id`, `class_id`, `total_score`, `average_score`, `gpa`, `rank_in_class`, `attendance_present_count`, `attendance_total_count`, `class_teacher_remark`, `principal_remark`, `promotion_status`, `is_locked`, `locked_at`, `locked_by`)
                 VALUES (:student_id, :term_id, :class_id, :total_score, :average_score, :gpa, :rank_in_class, :attendance_present, :attendance_total, :teacher_remark, :principal_remark, :promotion_status, :is_locked, :locked_at, :locked_by)
                 ON DUPLICATE KEY UPDATE 
                    `total_score` = VALUES(`total_score`),
                    `average_score` = VALUES(`average_score`),
                    `gpa` = VALUES(`gpa`),
                    `rank_in_class` = VALUES(`rank_in_class`),
                    `attendance_present_count` = VALUES(`attendance_present_count`),
                    `attendance_total_count` = VALUES(`attendance_total_count`),
                    `class_teacher_remark` = VALUES(`class_teacher_remark`),
                    `principal_remark` = VALUES(`principal_remark`),
                    `promotion_status` = VALUES(`promotion_status`),
                    `is_locked` = VALUES(`is_locked`),
                    `locked_at` = VALUES(`locked_at`),
                    `locked_by` = VALUES(`locked_by`)'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':term_id' => $termId,
                ':class_id' => $classId,
                ':total_score' => $totalScore,
                ':average_score' => $averageScore,
                ':gpa' => $gpa,
                ':rank_in_class' => $rankInClass,
                ':attendance_present' => $attendancePresent,
                ':attendance_total' => $attendanceTotal,
                ':teacher_remark' => $teacherRemark,
                ':principal_remark' => $principalRemark,
                ':promotion_status' => $promotionStatus,
                ':is_locked' => $isLocked ? 1 : 0,
                ':locked_at' => $isLocked ? $now : null,
                ':locked_by' => $lockedBy,
            ]);
        }
    }

    public function findStudentTermSummary(int $studentId, int $termId): ?StudentTermSummary
    {
        $stmt = $this->pdo->prepare(
            'SELECT sts.*, c.name AS class_name, c.section_arm, c.academic_level_id, c.status AS class_status,
                    u.name AS student_name, u.email AS student_email, s.admission_number
             FROM `student_term_summaries` sts
             JOIN `classes` c ON sts.class_id = c.id
             JOIN `students` s ON sts.student_id = s.id
             JOIN `users` u ON s.user_id = u.id
             WHERE sts.student_id = :student_id AND sts.term_id = :term_id'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':term_id' => $termId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $student = Student::fromArray([
            'id' => $row['student_id'],
            'admission_number' => $row['admission_number'] ?? '',
        ], User::fromArray([
            'name' => $row['student_name'] ?? '',
            'email' => $row['student_email'] ?? '',
        ]));

        $class = SchoolClass::fromArray([
            'id' => $row['class_id'],
            'academic_level_id' => $row['academic_level_id'] ?? 0,
            'name' => $row['class_name'] ?? '',
            'section_arm' => $row['section_arm'] ?? null,
            'status' => $row['class_status'] ?? 'active',
        ]);

        $subjectResults = $this->getTermResultsByStudent($studentId, $termId);

        return StudentTermSummary::fromArray($row, $student, $class, $subjectResults);
    }

    /**
     * @return array<int, StudentTermSummary>
     */
    public function getSummariesByClassAndTerm(int $classId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT sts.*, c.name AS class_name, c.section_arm, c.academic_level_id, c.status AS class_status,
                    u.name AS student_name, u.email AS student_email, s.admission_number
             FROM `student_term_summaries` sts
             JOIN `classes` c ON sts.class_id = c.id
             JOIN `students` s ON sts.student_id = s.id
             JOIN `users` u ON s.user_id = u.id
             WHERE sts.class_id = :class_id AND sts.term_id = :term_id
             ORDER BY sts.rank_in_class ASC, sts.average_score DESC'
        );
        $stmt->execute([
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summaries = [];
        foreach ($rows as $row) {
            $student = Student::fromArray([
                'id' => $row['student_id'],
                'admission_number' => $row['admission_number'] ?? '',
            ], User::fromArray([
                'name' => $row['student_name'] ?? '',
                'email' => $row['student_email'] ?? '',
            ]));

            $class = SchoolClass::fromArray([
                'id' => $row['class_id'],
                'academic_level_id' => $row['academic_level_id'] ?? 0,
                'name' => $row['class_name'] ?? '',
                'section_arm' => $row['section_arm'] ?? null,
                'status' => $row['class_status'] ?? 'active',
            ]);

            $summaries[] = StudentTermSummary::fromArray($row, $student, $class, []);
        }

        return $summaries;
    }

    public function isClassSubjectLocked(int $classSubjectId, int $termId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `term_results` 
             WHERE `class_subject_id` = :class_subject_id AND `term_id` = :term_id AND `is_locked` = 1'
        );
        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function lockClassSubjectResults(int $classSubjectId, int $termId, int $lockedBy): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $now = date('Y-m-d H:i:s');

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'UPDATE `term_results` 
                 SET `is_locked` = 1, `locked_at` = :locked_at, `locked_by` = :locked_by 
                 WHERE `class_subject_id` = :class_subject_id AND `term_id` = :term_id'
            );
            $stmt->execute([
                ':class_subject_id' => $classSubjectId,
                ':term_id' => $termId,
                ':locked_at' => $now,
                ':locked_by' => $lockedBy,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE `term_results` 
                 SET `is_locked` = 1, `locked_at` = NOW(), `locked_by` = :locked_by 
                 WHERE `class_subject_id` = :class_subject_id AND `term_id` = :term_id'
            );
            $stmt->execute([
                ':class_subject_id' => $classSubjectId,
                ':term_id' => $termId,
                ':locked_by' => $lockedBy,
            ]);
        }
    }

    public function unlockClassSubjectResults(int $classSubjectId, int $termId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `term_results` 
             SET `is_locked` = 0, `locked_at` = NULL, `locked_by` = NULL 
             WHERE `class_subject_id` = :class_subject_id AND `term_id` = :term_id'
        );
        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
        ]);
    }

    public function getScoresCountByClassSubject(int $classSubjectId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT student_id) FROM `student_assessment_scores` WHERE `class_subject_id` = :class_subject_id'
        );
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * @return array{total_students: int, avg_score: float|null, min_score: float|null, max_score: float|null, locked: bool}
     */
    public function getTermResultsStatsByClassSubject(int $classSubjectId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as total_students, 
                    AVG(computed_score) as avg_score, 
                    MIN(computed_score) as min_score, 
                    MAX(computed_score) as max_score,
                    SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as locked_count
             FROM `term_results`
             WHERE `class_subject_id` = :class_subject_id AND `term_id` = :term_id'
        );
        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_students' => (int)($row['total_students'] ?? 0),
            'avg_score' => $row['avg_score'] !== null ? round((float)$row['avg_score'], 1) : null,
            'min_score' => $row['min_score'] !== null ? round((float)$row['min_score'], 1) : null,
            'max_score' => $row['max_score'] !== null ? round((float)$row['max_score'], 1) : null,
            'locked' => ((int)($row['locked_count'] ?? 0)) > 0,
        ];
    }

    /**
     * @return array<int, bool> [class_subject_id => is_locked]
     */
    public function getLockedClassSubjectIds(int $termId, ?array $classSubjectIds = null): array
    {
        if ($classSubjectIds !== null && empty($classSubjectIds)) {
            return [];
        }

        $sql = 'SELECT class_subject_id, COUNT(*) as locked_count 
                FROM `term_results` 
                WHERE `term_id` = :term_id AND `is_locked` = 1';
        $params = [':term_id' => $termId];

        if ($classSubjectIds !== null) {
            $inPlaceholders = implode(',', array_map('intval', $classSubjectIds));
            $sql .= " AND `class_subject_id` IN ({$inPlaceholders})";
        }

        $sql .= ' GROUP BY class_subject_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lockedMap = [];
        foreach ($rows as $row) {
            $lockedMap[(int)$row['class_subject_id']] = ((int)$row['locked_count']) > 0;
        }

        return $lockedMap;
    }

    /**
     * @return array<int, array<int, array<string, mixed>>> [student_id => [subject_id => row]]
     */
    public function getTermResultsMatrixByClass(int $classId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tr.*, u.name AS student_name, u.email AS student_email, s.admission_number,
                    sub.id AS subject_id, sub.name AS subject_name, sub.code AS subject_code
             FROM `term_results` tr
             JOIN `students` s ON tr.student_id = s.id
             JOIN `users` u ON s.user_id = u.id
             JOIN `class_subjects` cs ON tr.class_subject_id = cs.id
             JOIN `subjects` sub ON cs.subject_id = sub.id
             WHERE cs.class_id = :class_id AND tr.term_id = :term_id
             ORDER BY u.name ASC'
        );
        $stmt->execute([
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[(int)$row['student_id']][(int)$row['subject_id']] = $row;
        }

        return $matrix;
    }

    /**
     * Batch update remarks for students in a class & term
     *
     * @param int $termId
     * @param int $classId
     * @param array<int, array{teacher_remark?: string|null, principal_remark?: string|null}> $remarks
     */
    public function batchUpdateRemarks(int $termId, int $classId, array $remarks): void
    {
        if (empty($remarks)) {
            return;
        }

        foreach ($remarks as $studentId => $data) {
            $studentId = (int)$studentId;
            if ($studentId <= 0) {
                continue;
            }

            // Check if record exists
            $checkStmt = $this->pdo->prepare(
                'SELECT id, class_teacher_remark, principal_remark FROM `student_term_summaries` 
                 WHERE `student_id` = :student_id AND `term_id` = :term_id'
            );
            $checkStmt->execute([
                ':student_id' => $studentId,
                ':term_id' => $termId,
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $hasTeacher = array_key_exists('teacher_remark', $data);
            $hasPrincipal = array_key_exists('principal_remark', $data);

            $teacherRemark = $hasTeacher
                ? ($data['teacher_remark'] !== null ? trim((string)$data['teacher_remark']) : null)
                : ($existing['class_teacher_remark'] ?? null);

            $principalRemark = $hasPrincipal
                ? ($data['principal_remark'] !== null ? trim((string)$data['principal_remark']) : null)
                : ($existing['principal_remark'] ?? null);

            if ($existing) {
                $updateStmt = $this->pdo->prepare(
                    'UPDATE `student_term_summaries` 
                     SET `class_teacher_remark` = :teacher_remark,
                         `principal_remark` = :principal_remark
                     WHERE `id` = :id'
                );
                $updateStmt->execute([
                    ':id' => $existing['id'],
                    ':teacher_remark' => $teacherRemark,
                    ':principal_remark' => $principalRemark,
                ]);
            } else {
                $insertStmt = $this->pdo->prepare(
                    'INSERT INTO `student_term_summaries` 
                     (`student_id`, `term_id`, `class_id`, `class_teacher_remark`, `principal_remark`, `promotion_status`, `is_locked`)
                     VALUES (:student_id, :term_id, :class_id, :teacher_remark, :principal_remark, \'pending\', 0)'
                );
                $insertStmt->execute([
                    ':student_id' => $studentId,
                    ':term_id' => $termId,
                    ':class_id' => $classId,
                    ':teacher_remark' => $teacherRemark,
                    ':principal_remark' => $principalRemark,
                ]);
            }
        }
    }
}

