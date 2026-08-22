<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\ClassSubject;
use App\Models\GradingScale;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use PDO;

/**
 * Data Access Layer for Academic Foundation: Sessions, Terms, Levels, Classes, Subjects
 */
class AcademicRepository
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

    // ==========================================
    // 1. SESSIONS
    // ==========================================

    public function findSessionById(int $id): ?AcademicSession
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `sessions` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AcademicSession::fromArray($row) : null;
    }

    public function findSessionByName(string $name): ?AcademicSession
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `sessions` WHERE LOWER(`name`) = LOWER(:name) LIMIT 1');
        $stmt->execute([':name' => trim($name)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AcademicSession::fromArray($row) : null;
    }

    public function findActiveSession(): ?AcademicSession
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `sessions` WHERE `status` = :status LIMIT 1');
        $stmt->execute([':status' => AcademicSession::STATUS_ACTIVE]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AcademicSession::fromArray($row) : null;
    }

    public function getCurrentSession(): ?AcademicSession
    {
        return $this->findActiveSession();
    }

    public function findCurrentSession(): ?AcademicSession
    {
        return $this->findActiveSession();
    }

    public function getActiveSession(): ?AcademicSession
    {
        return $this->findActiveSession();
    }

    /**
     * @return AcademicSession[]
     */
    public function getAllSessions(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `sessions` ORDER BY `start_date` DESC, `id` DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => AcademicSession::fromArray($row), $rows);
    }

    public function createSession(array $data): AcademicSession
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `sessions` (`name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
             VALUES (:name, :start_date, :end_date, :status, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':name' => trim($data['name']),
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':status' => $data['status'] ?? AcademicSession::STATUS_PLANNING,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findSessionById($id);
    }

    public function updateSession(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `sessions` 
             SET `name` = :name, `start_date` = :start_date, `end_date` = :end_date, `updated_at` = :updated_at
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($data['name']),
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':updated_at' => $now,
        ]);
    }

    public function updateSessionStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `sessions` SET `status` = :status, `updated_at` = :updated_at WHERE `id` = :id');

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ]);
    }

    public function deactivateOtherSessions(int $activeSessionId): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `sessions` 
             SET `status` = :archived_status, `updated_at` = :updated_at 
             WHERE `status` = :active_status AND `id` != :id'
        );
        $stmt->execute([
            ':archived_status' => AcademicSession::STATUS_ARCHIVED,
            ':active_status' => AcademicSession::STATUS_ACTIVE,
            ':id' => $activeSessionId,
            ':updated_at' => $now,
        ]);
    }

    // ==========================================
    // 2. TERMS
    // ==========================================

    public function findTermById(int $id): ?Term
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `terms` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Term::fromArray($row) : null;
    }

    public function findTermByNameInSession(int $sessionId, string $name): ?Term
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `terms` WHERE `session_id` = :session_id AND LOWER(`name`) = LOWER(:name) LIMIT 1'
        );
        $stmt->execute([
            ':session_id' => $sessionId,
            ':name' => trim($name),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Term::fromArray($row) : null;
    }

    public function findActiveTermForSession(int $sessionId): ?Term
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `terms` WHERE `session_id` = :session_id AND LOWER(`status`) = :status LIMIT 1');
        $stmt->execute([
            ':session_id' => $sessionId,
            ':status' => strtolower(Term::STATUS_ACTIVE),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return Term::fromArray($row);
        }

        // Fallback: Return first term in session if none explicitly marked active
        $stmt = $this->pdo->prepare('SELECT * FROM `terms` WHERE `session_id` = :session_id ORDER BY `id` ASC LIMIT 1');
        $stmt->execute([':session_id' => $sessionId]);
        $fallbackRow = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fallbackRow ? Term::fromArray($fallbackRow) : null;
    }

    public function findActiveTermInSession(int $sessionId): ?Term
    {
        return $this->findActiveTermForSession($sessionId);
    }

    /**
     * @return Term[]
     */
    public function getTermsBySession(int $sessionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `terms` WHERE `session_id` = :session_id ORDER BY `start_date` ASC, `id` ASC');
        $stmt->execute([':session_id' => $sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Term::fromArray($row), $rows);
    }

    /**
     * @return Term[]
     */
    public function findAllTerms(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `terms` ORDER BY `start_date` DESC, `id` DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Term::fromArray($row), $rows);
    }

    /**
     * @return Term[]
     */
    public function getAllTerms(): array
    {
        return $this->findAllTerms();
    }

    public function findCurrentTerm(): ?Term
    {
        $activeSession = $this->findActiveSession();
        if ($activeSession) {
            $term = $this->findActiveTermForSession($activeSession->id);
            if ($term) {
                return $term;
            }
        }

        $stmt = $this->pdo->prepare('SELECT * FROM `terms` WHERE LOWER(`status`) = :status LIMIT 1');
        $stmt->execute([':status' => strtolower(Term::STATUS_ACTIVE)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return Term::fromArray($row);
        }

        // Final fallback: Get the latest term in database
        $stmt = $this->pdo->query('SELECT * FROM `terms` ORDER BY `id` DESC LIMIT 1');
        $lastRow = $stmt->fetch(PDO::FETCH_ASSOC);

        return $lastRow ? Term::fromArray($lastRow) : null;
    }

    public function getCurrentTerm(): ?Term
    {
        return $this->findCurrentTerm();
    }

    public function findActiveTerm(): ?Term
    {
        return $this->findCurrentTerm();
    }

    public function getActiveTerm(): ?Term
    {
        return $this->findCurrentTerm();
    }


    public function createTerm(array $data): Term
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `terms` (`session_id`, `name`, `start_date`, `end_date`, `grading_starts_at`, `grading_ends_at`, `status`, `created_at`, `updated_at`)
             VALUES (:session_id, :name, :start_date, :end_date, :grading_starts_at, :grading_ends_at, :status, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':session_id' => (int)$data['session_id'],
            ':name' => trim($data['name']),
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':grading_starts_at' => $data['grading_starts_at'] ?? null,
            ':grading_ends_at' => $data['grading_ends_at'] ?? null,
            ':status' => $data['status'] ?? Term::STATUS_PLANNING,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findTermById($id);
    }

    public function updateTerm(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `terms` 
             SET `name` = :name, `start_date` = :start_date, `end_date` = :end_date, 
                 `grading_starts_at` = :grading_starts_at, `grading_ends_at` = :grading_ends_at, 
                 `updated_at` = :updated_at
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($data['name']),
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':grading_starts_at' => $data['grading_starts_at'] ?? null,
            ':grading_ends_at' => $data['grading_ends_at'] ?? null,
            ':updated_at' => $now,
        ]);
    }

    public function updateTermStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `terms` SET `status` = :status, `updated_at` = :updated_at WHERE `id` = :id');

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ]);
    }

    public function deactivateOtherTermsInSession(int $sessionId, int $activeTermId): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `terms` 
             SET `status` = :archived_status, `updated_at` = :updated_at 
             WHERE `session_id` = :session_id AND `status` = :active_status AND `id` != :id'
        );
        $stmt->execute([
            ':archived_status' => Term::STATUS_ARCHIVED,
            ':active_status' => Term::STATUS_ACTIVE,
            ':session_id' => $sessionId,
            ':id' => $activeTermId,
            ':updated_at' => $now,
        ]);
    }

    // ==========================================
    // 3. ACADEMIC LEVELS
    // ==========================================

    public function findLevelById(int $id): ?AcademicLevel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `academic_levels` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AcademicLevel::fromArray($row) : null;
    }

    public function findLevelByName(string $name): ?AcademicLevel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `academic_levels` WHERE LOWER(`name`) = LOWER(:name) LIMIT 1');
        $stmt->execute([':name' => trim($name)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? AcademicLevel::fromArray($row) : null;
    }

    /**
     * @return AcademicLevel[]
     */
    public function getAllLevels(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `academic_levels` ORDER BY `rank_order` ASC, `id` ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => AcademicLevel::fromArray($row), $rows);
    }

    public function createLevel(array $data): AcademicLevel
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `academic_levels` (`name`, `stage`, `rank_order`, `grading_scale_id`, `created_at`, `updated_at`)
             VALUES (:name, :stage, :rank_order, :grading_scale_id, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':name' => trim($data['name']),
            ':stage' => trim($data['stage']),
            ':rank_order' => (int)($data['rank_order'] ?? 0),
            ':grading_scale_id' => !empty($data['grading_scale_id']) ? (int)$data['grading_scale_id'] : null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findLevelById($id);
    }

    public function updateLevel(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `academic_levels`
             SET `name` = :name, `stage` = :stage, `rank_order` = :rank_order, `grading_scale_id` = :grading_scale_id, `updated_at` = :updated_at
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($data['name']),
            ':stage' => trim($data['stage']),
            ':rank_order' => (int)($data['rank_order'] ?? 0),
            ':grading_scale_id' => !empty($data['grading_scale_id']) ? (int)$data['grading_scale_id'] : null,
            ':updated_at' => $now,
        ]);
    }

    public function deleteLevel(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `academic_levels` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // 4. CLASSES
    // ==========================================

    public function findClassById(int $id): ?SchoolClass
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, al.name as level_name, al.stage as level_stage, al.rank_order as level_rank_order, al.grading_scale_id as level_grading_scale_id
             FROM `classes` c
             JOIN `academic_levels` al ON al.id = c.academic_level_id
             WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $level = new AcademicLevel(
            id: (int)$row['academic_level_id'],
            name: (string)$row['level_name'],
            stage: (string)$row['level_stage'],
            rankOrder: (int)$row['level_rank_order'],
            gradingScaleId: isset($row['level_grading_scale_id']) ? (int)$row['level_grading_scale_id'] : null
        );

        return SchoolClass::fromArray($row, $level);
    }

    public function findClassByNameAndLevel(string $name, int $levelId, ?string $sectionArm = null): ?SchoolClass
    {
        $sql = 'SELECT * FROM `classes` 
                WHERE `academic_level_id` = :level_id AND LOWER(`name`) = LOWER(:name)';
        $params = [
            ':level_id' => $levelId,
            ':name' => trim($name),
        ];

        if ($sectionArm !== null) {
            $sql .= ' AND LOWER(`section_arm`) = LOWER(:section_arm)';
            $params[':section_arm'] = trim($sectionArm);
        } else {
            $sql .= ' AND `section_arm` IS NULL';
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? SchoolClass::fromArray($row) : null;
    }

    /**
     * @return SchoolClass[]
     */
    public function getAllClasses(): array
    {
        $sql = 'SELECT c.*, al.name as level_name, al.stage as level_stage, al.rank_order as level_rank_order, al.grading_scale_id as level_grading_scale_id
                FROM `classes` c
                JOIN `academic_levels` al ON al.id = c.academic_level_id
                ORDER BY al.rank_order ASC, c.name ASC, c.id ASC';

        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row) {
            $level = new AcademicLevel(
                id: (int)$row['academic_level_id'],
                name: (string)$row['level_name'],
                stage: (string)$row['level_stage'],
                rankOrder: (int)$row['level_rank_order'],
                gradingScaleId: isset($row['level_grading_scale_id']) ? (int)$row['level_grading_scale_id'] : null
            );

            return SchoolClass::fromArray($row, $level);
        }, $rows);
    }

    public function createClass(array $data): SchoolClass
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `classes` (`academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`)
             VALUES (:academic_level_id, :name, :section_arm, :status, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':academic_level_id' => (int)$data['academic_level_id'],
            ':name' => trim($data['name']),
            ':section_arm' => !empty($data['section_arm']) ? trim($data['section_arm']) : null,
            ':status' => $data['status'] ?? SchoolClass::STATUS_ACTIVE,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findClassById($id);
    }

    public function updateClass(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `classes`
             SET `academic_level_id` = :academic_level_id, `name` = :name, `section_arm` = :section_arm, `updated_at` = :updated_at
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':academic_level_id' => (int)$data['academic_level_id'],
            ':name' => trim($data['name']),
            ':section_arm' => !empty($data['section_arm']) ? trim($data['section_arm']) : null,
            ':updated_at' => $now,
        ]);
    }

    public function updateClassStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `classes` SET `status` = :status, `updated_at` = :updated_at WHERE `id` = :id');

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ]);
    }

    // ==========================================
    // 5. SUBJECTS
    // ==========================================

    public function findSubjectById(int $id): ?Subject
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `subjects` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Subject::fromArray($row) : null;
    }

    public function findSubjectByCode(string $code): ?Subject
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `subjects` WHERE UPPER(`code`) = UPPER(:code) LIMIT 1');
        $stmt->execute([':code' => trim($code)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Subject::fromArray($row) : null;
    }

    /**
     * @return Subject[]
     */
    public function getAllSubjects(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `subjects` ORDER BY `name` ASC, `code` ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Subject::fromArray($row), $rows);
    }

    public function createSubject(array $data): Subject
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `subjects` (`name`, `code`, `status`, `created_at`, `updated_at`)
             VALUES (:name, :code, :status, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':name' => trim($data['name']),
            ':code' => strtoupper(trim($data['code'])),
            ':status' => $data['status'] ?? Subject::STATUS_ACTIVE,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findSubjectById($id);
    }

    public function updateSubject(int $id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `subjects`
             SET `name` = :name, `code` = :code, `updated_at` = :updated_at
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($data['name']),
            ':code' => strtoupper(trim($data['code'])),
            ':updated_at' => $now,
        ]);
    }

    public function updateSubjectStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `subjects` SET `status` = :status, `updated_at` = :updated_at WHERE `id` = :id');

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ]);
    }

    // ==========================================
    // 6. GRADING SCALES
    // ==========================================

    /**
     * @return GradingScale[]
     */
    public function getAllGradingScales(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `grading_scales` ORDER BY `name` ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => GradingScale::fromArray($row), $rows);
    }

    public function findGradingScaleById(int $id): ?GradingScale
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `grading_scales` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? GradingScale::fromArray($row) : null;
    }

    // ==========================================
    // 7. CLASS SUBJECTS / TEACHING ASSIGNMENTS
    // ==========================================

    public function findClassSubjectById(int $id): ?ClassSubject
    {
        $stmt = $this->pdo->prepare(
            'SELECT cs.*,
                    s.name as session_name, s.start_date as session_start, s.end_date as session_end, s.status as session_status,
                    c.name as class_name, c.section_arm as class_section_arm, c.academic_level_id as class_level_id, c.status as class_status,
                    sub.name as subject_name, sub.code as subject_code, sub.status as subject_status,
                    t.staff_id as teacher_staff_id, t.user_id as teacher_user_id,
                    u.name as teacher_name, u.email as teacher_email
             FROM `class_subjects` cs
             JOIN `sessions` s ON s.id = cs.session_id
             JOIN `classes` c ON c.id = cs.class_id
             JOIN `subjects` sub ON sub.id = cs.subject_id
             JOIN `teachers` t ON t.id = cs.teacher_id
             JOIN `users` u ON u.id = t.user_id
             WHERE cs.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateClassSubject($row) : null;
    }

    public function findClassSubject(int $sessionId, int $classId, int $subjectId): ?ClassSubject
    {
        $stmt = $this->pdo->prepare(
            'SELECT cs.*,
                    s.name as session_name, s.start_date as session_start, s.end_date as session_end, s.status as session_status,
                    c.name as class_name, c.section_arm as class_section_arm, c.academic_level_id as class_level_id, c.status as class_status,
                    sub.name as subject_name, sub.code as subject_code, sub.status as subject_status,
                    t.staff_id as teacher_staff_id, t.user_id as teacher_user_id,
                    u.name as teacher_name, u.email as teacher_email
             FROM `class_subjects` cs
             JOIN `sessions` s ON s.id = cs.session_id
             JOIN `classes` c ON c.id = cs.class_id
             JOIN `subjects` sub ON sub.id = cs.subject_id
             JOIN `teachers` t ON t.id = cs.teacher_id
             JOIN `users` u ON u.id = t.user_id
             WHERE cs.session_id = :session_id AND cs.class_id = :class_id AND cs.subject_id = :subject_id
             LIMIT 1'
        );
        $stmt->execute([
            ':session_id' => $sessionId,
            ':class_id' => $classId,
            ':subject_id' => $subjectId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateClassSubject($row) : null;
    }

    /**
     * @return ClassSubject[]
     */
    public function getClassSubjectsBySession(int $sessionId, ?int $classId = null): array
    {
        $sql = 'SELECT cs.*,
                       s.name as session_name, s.start_date as session_start, s.end_date as session_end, s.status as session_status,
                       c.name as class_name, c.section_arm as class_section_arm, c.academic_level_id as class_level_id, c.status as class_status,
                       sub.name as subject_name, sub.code as subject_code, sub.status as subject_status,
                       t.staff_id as teacher_staff_id, t.user_id as teacher_user_id,
                       u.name as teacher_name, u.email as teacher_email
                FROM `class_subjects` cs
                JOIN `sessions` s ON s.id = cs.session_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `subjects` sub ON sub.id = cs.subject_id
                JOIN `teachers` t ON t.id = cs.teacher_id
                JOIN `users` u ON u.id = t.user_id
                WHERE cs.session_id = :session_id';

        $params = [':session_id' => $sessionId];

        if ($classId !== null && $classId > 0) {
            $sql .= ' AND cs.class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        $sql .= ' ORDER BY c.name ASC, sub.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->hydrateClassSubject($row), $rows);
    }

    /**
     * @return ClassSubject[]
     */
    public function getClassSubjectsByClassAndSession(int $classId, int $sessionId): array
    {
        return $this->getClassSubjectsBySession($sessionId, $classId);
    }

    /**
     * @return ClassSubject[]
     */
    public function getClassSubjectsByTeacher(int $teacherId, int $sessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cs.*,
                    s.name as session_name, s.start_date as session_start, s.end_date as session_end, s.status as session_status,
                    c.name as class_name, c.section_arm as class_section_arm, c.academic_level_id as class_level_id, c.status as class_status,
                    sub.name as subject_name, sub.code as subject_code, sub.status as subject_status,
                    t.staff_id as teacher_staff_id, t.user_id as teacher_user_id,
                    u.name as teacher_name, u.email as teacher_email
             FROM `class_subjects` cs
             JOIN `sessions` s ON s.id = cs.session_id
             JOIN `classes` c ON c.id = cs.class_id
             JOIN `subjects` sub ON sub.id = cs.subject_id
             JOIN `teachers` t ON t.id = cs.teacher_id
             JOIN `users` u ON u.id = t.user_id
             WHERE cs.teacher_id = :teacher_id AND cs.session_id = :session_id
             ORDER BY c.name ASC, sub.name ASC'
        );
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':session_id' => $sessionId,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->hydrateClassSubject($row), $rows);
    }

    /**
     * @return ClassSubject[]
     */
    public function findClassSubjectsByTeacherId(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cs.*,
                    s.name as session_name, s.start_date as session_start, s.end_date as session_end, s.status as session_status,
                    c.name as class_name, c.section_arm as class_section_arm, c.academic_level_id as class_level_id, c.status as class_status,
                    sub.name as subject_name, sub.code as subject_code, sub.status as subject_status,
                    t.staff_id as teacher_staff_id, t.user_id as teacher_user_id,
                    u.name as teacher_name, u.email as teacher_email
             FROM `class_subjects` cs
             JOIN `sessions` s ON s.id = cs.session_id
             JOIN `classes` c ON c.id = cs.class_id
             JOIN `subjects` sub ON sub.id = cs.subject_id
             JOIN `teachers` t ON t.id = cs.teacher_id
             JOIN `users` u ON u.id = t.user_id
             WHERE cs.teacher_id = :teacher_id AND cs.status = :status
             ORDER BY s.id DESC, c.name ASC, sub.name ASC'
        );
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':status' => ClassSubject::STATUS_ACTIVE,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->hydrateClassSubject($row), $rows);
    }

    public function createClassSubject(array $data): ClassSubject
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `class_subjects` (`session_id`, `class_id`, `subject_id`, `teacher_id`, `status`, `created_at`, `updated_at`)
             VALUES (:session_id, :class_id, :subject_id, :teacher_id, :status, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':session_id' => (int)$data['session_id'],
            ':class_id' => (int)$data['class_id'],
            ':subject_id' => (int)$data['subject_id'],
            ':teacher_id' => (int)$data['teacher_id'],
            ':status' => $data['status'] ?? ClassSubject::STATUS_ACTIVE,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findClassSubjectById($id);
    }

    public function updateClassSubjectTeacher(int $id, int $teacherId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `class_subjects` SET `teacher_id` = :teacher_id, `updated_at` = :updated_at WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':teacher_id' => $teacherId,
            ':updated_at' => $now,
        ]);
    }

    public function updateClassSubjectStatus(int $id, string $status): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `class_subjects` SET `status` = :status, `updated_at` = :updated_at WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ]);
    }

    private function hydrateClassSubject(array $row): ClassSubject
    {
        $session = AcademicSession::fromArray([
            'id' => $row['session_id'],
            'name' => $row['session_name'] ?? '',
            'start_date' => $row['session_start'] ?? '',
            'end_date' => $row['session_end'] ?? '',
            'status' => $row['session_status'] ?? AcademicSession::STATUS_PLANNING,
        ]);

        $class = SchoolClass::fromArray([
            'id' => $row['class_id'],
            'academic_level_id' => $row['class_level_id'] ?? 0,
            'name' => $row['class_name'] ?? '',
            'section_arm' => $row['class_section_arm'] ?? null,
            'status' => $row['class_status'] ?? SchoolClass::STATUS_ACTIVE,
        ]);

        $subject = Subject::fromArray([
            'id' => $row['subject_id'],
            'name' => $row['subject_name'] ?? '',
            'code' => $row['subject_code'] ?? '',
            'status' => $row['subject_status'] ?? Subject::STATUS_ACTIVE,
        ]);

        $teacher = Teacher::fromArray([
            'id' => $row['teacher_id'],
            'user_id' => $row['teacher_user_id'] ?? 0,
            'staff_id' => $row['teacher_staff_id'] ?? '',
            'user_name' => $row['teacher_name'] ?? '',
            'user_email' => $row['teacher_email'] ?? '',
        ]);

        return ClassSubject::fromArray($row, $session, $class, $subject, $teacher);
    }
}
