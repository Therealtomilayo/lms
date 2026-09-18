<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Badge;
use App\Models\StudentBadge;
use App\Models\User;
use PDO;

class BadgeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Get all active badge definitions.
     * @return Badge[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM badges ORDER BY name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Badge::fromArray($row), $rows);
    }

    public function findById(int $id): ?Badge
    {
        $stmt = $this->pdo->prepare("SELECT * FROM badges WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Badge::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Badge
    {
        $stmt = $this->pdo->prepare("SELECT * FROM badges WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Badge::fromArray($row) : null;
    }

    /**
     * Ensure standard default system badges exist in the database.
     */
    public function ensureDefaultBadgesExist(): void
    {
        $existing = $this->findAll();
        if (!empty($existing)) {
            return;
        }

        $defaults = [
            [
                'name' => 'Perfect Score',
                'slug' => 'perfect-score',
                'description' => 'Awarded for scoring 100% on any major CBT assessment or quiz.',
                'category' => 'academic',
                'icon_name' => 'award',
                'color_scheme' => 'brand',
                'is_system' => 1,
            ],
            [
                'name' => 'Course Mastery',
                'slug' => 'course-mastery',
                'description' => 'Attained upon completing 100% of all required module items and learning activities.',
                'category' => 'progression',
                'icon_name' => 'check-circle',
                'color_scheme' => 'emerald',
                'is_system' => 1,
            ],
            [
                'name' => 'Discussion Pioneer',
                'slug' => 'discussion-pioneer',
                'description' => 'Recognizes active, thoughtful contributions and peer help in class group discussions.',
                'category' => 'engagement',
                'icon_name' => 'users',
                'color_scheme' => 'sky',
                'is_system' => 1,
            ],
            [
                'name' => 'Top Scholar',
                'slug' => 'top-scholar',
                'description' => 'Exemplary academic excellence and consistent faculty commendations across coursework.',
                'category' => 'honor',
                'icon_name' => 'academic',
                'color_scheme' => 'amber',
                'is_system' => 1,
            ],
            [
                'name' => 'Punctuality Star',
                'slug' => 'punctuality-star',
                'description' => 'Unblemished attendance record and timely coursework submissions.',
                'category' => 'attendance',
                'icon_name' => 'clock',
                'color_scheme' => 'indigo',
                'is_system' => 1,
            ],
            [
                'name' => 'Diligent Learner',
                'slug' => 'diligent-learner',
                'description' => 'Outstanding perseverance, study dedication, and consistent learning progression.',
                'category' => 'effort',
                'icon_name' => 'book',
                'color_scheme' => 'rose',
                'is_system' => 1,
            ],
            [
                'name' => 'Academic Excellence',
                'slug' => 'academic-excellence',
                'description' => 'Demonstrated exceptional performance with top marks (>=90%) on coursework or CBT quizzes.',
                'category' => 'academic',
                'icon_name' => 'award',
                'color_scheme' => 'brand',
                'is_system' => 1,
            ],
            [
                'name' => 'Course Completer',
                'slug' => 'course-completer',
                'description' => 'Successfully completed 100% of all required learning activities in a course module curriculum.',
                'category' => 'progression',
                'icon_name' => 'check-circle',
                'color_scheme' => 'emerald',
                'is_system' => 1,
            ],
            [
                'name' => 'Star of Punctuality',
                'slug' => 'star-of-punctuality',
                'description' => 'Achieved exemplary, unblemished attendance and punctuality across the academic term.',
                'category' => 'attendance',
                'icon_name' => 'clock',
                'color_scheme' => 'sky',
                'is_system' => 1,
            ],
            [
                'name' => 'Most Improved Scholar',
                'slug' => 'most-improved',
                'description' => 'Commended for outstanding academic growth, dedication, and consistent improvement.',
                'category' => 'academic',
                'icon_name' => 'trending-up',
                'color_scheme' => 'purple',
                'is_system' => 1,
            ],
            [
                'name' => 'Master Artisan',
                'slug' => 'master-artisan',
                'description' => 'Awarded for exceptional creativity, precision, and diligence on coursework assignments.',
                'category' => 'academic',
                'icon_name' => 'clipboard',
                'color_scheme' => 'amber',
                'is_system' => 1,
            ],
            [
                'name' => 'Exemplary Citizen',
                'slug' => 'claret-virtue',
                'description' => 'Demonstrated moral leadership, kindness, teamwork, and adherence to Claret core values.',
                'category' => 'citizenship',
                'icon_name' => 'heart',
                'color_scheme' => 'rose',
                'is_system' => 1,
            ]
        ];

        foreach ($defaults as $badge) {
            $this->createBadge($badge);
        }
    }

    /**
     * Create a new badge definition.
     */
    public function createBadge(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            INSERT INTO badges (name, slug, description, category, icon_name, color_scheme, is_system, created_at)
            VALUES (:name, :slug, :description, :category, :icon_name, :color_scheme, :is_system, :created_at)
        ");
        $stmt->execute([
            'name' => trim((string)$data['name']),
            'slug' => trim((string)$data['slug']),
            'description' => trim((string)$data['description']),
            'category' => (string)($data['category'] ?? 'academic'),
            'icon_name' => (string)($data['icon_name'] ?? 'award'),
            'color_scheme' => (string)($data['color_scheme'] ?? 'brand'),
            'is_system' => (int)($data['is_system'] ?? 0),
            'created_at' => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Award a badge to a student.
     */
    public function awardBadge(
        int $studentId,
        int $badgeId,
        int $awardedBy,
        string $reason,
        ?int $classSubjectId = null,
        ?int $sessionId = null,
        ?int $termId = null
    ): StudentBadge {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            INSERT INTO student_badges (student_id, badge_id, awarded_by, class_subject_id, session_id, term_id, reason, awarded_at)
            VALUES (:student_id, :badge_id, :awarded_by, :class_subject_id, :session_id, :term_id, :reason, :awarded_at)
        ");
        $stmt->execute([
            'student_id' => $studentId,
            'badge_id' => $badgeId,
            'awarded_by' => $awardedBy,
            'class_subject_id' => $classSubjectId,
            'session_id' => $sessionId,
            'term_id' => $termId,
            'reason' => trim($reason),
            'awarded_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        $badge = $this->findById($badgeId);

        return new StudentBadge(
            id: $id,
            studentId: $studentId,
            badgeId: $badgeId,
            awardedBy: $awardedBy,
            reason: trim($reason),
            classSubjectId: $classSubjectId,
            sessionId: $sessionId,
            termId: $termId,
            awardedAt: date('Y-m-d H:i:s'),
            badge: $badge
        );
    }

    /**
     * Check if student has already earned a specific badge (optionally within a subject or session).
     */
    public function hasBadge(int $studentId, int $badgeId, ?int $classSubjectId = null, ?int $sessionId = null): bool
    {
        $sql = "SELECT COUNT(1) FROM student_badges WHERE student_id = :student_id AND badge_id = :badge_id";
        $params = [
            'student_id' => $studentId,
            'badge_id' => $badgeId,
        ];

        if ($classSubjectId !== null) {
            $sql .= " AND class_subject_id = :class_subject_id";
            $params['class_subject_id'] = $classSubjectId;
        }

        if ($sessionId !== null) {
            $sql .= " AND session_id = :session_id";
            $params['session_id'] = $sessionId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Get all badges earned by a student with badge metadata and awarder details.
     * @return StudentBadge[]
     */
    public function getStudentBadges(int $studentId): array
    {
        $sql = "
            SELECT 
                sb.*,
                b.name AS badge_name,
                b.slug AS badge_slug,
                b.description AS badge_description,
                b.category AS badge_category,
                b.icon_name AS badge_icon_name,
                b.color_scheme AS badge_color_scheme,
                b.is_system AS badge_is_system,
                u.name AS awarder_name,
                u.email AS awarder_email,
                s.name AS subject_name,
                c.name AS class_name,
                c.section_arm
            FROM student_badges sb
            JOIN badges b ON b.id = sb.badge_id
            JOIN users u ON u.id = sb.awarded_by
            LEFT JOIN class_subjects cs ON cs.id = sb.class_subject_id
            LEFT JOIN subjects s ON s.id = cs.subject_id
            LEFT JOIN classes c ON c.id = cs.class_id
            WHERE sb.student_id = :student_id
            ORDER BY sb.awarded_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $badge = new Badge(
                id: (int)$row['badge_id'],
                name: (string)$row['badge_name'],
                slug: (string)$row['badge_slug'],
                description: (string)$row['badge_description'],
                category: (string)$row['badge_category'],
                iconName: (string)$row['badge_icon_name'],
                colorScheme: (string)$row['badge_color_scheme'],
                isSystem: (bool)$row['badge_is_system']
            );

            $studentBadge = StudentBadge::fromArray($row, $badge);
            $studentBadge->awarderName = (string)$row['awarder_name'];
            $studentBadge->subjectName = isset($row['subject_name']) ? (string)$row['subject_name'] : null;
            $studentBadge->className = isset($row['class_name']) ? (string)$row['class_name'] : null;
            $studentBadge->sectionArm = isset($row['section_arm']) ? (string)$row['section_arm'] : null;

            $results[] = $studentBadge;
        }

        return $results;
    }

    /**
     * Count badges earned by a student.
     */
    public function countStudentBadges(int $studentId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(1) FROM student_badges WHERE student_id = :student_id");
        $stmt->execute(['student_id' => $studentId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get school-wide list of awarded badges with filters for admin oversight (ADMIN-34).
     * @return StudentBadge[]
     */
    public function getAllAwardedBadges(?int $badgeId = null, ?int $classId = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT 
                sb.*,
                b.name AS badge_name,
                b.slug AS badge_slug,
                b.description AS badge_description,
                b.category AS badge_category,
                b.icon_name AS badge_icon_name,
                b.color_scheme AS badge_color_scheme,
                b.is_system AS badge_is_system,
                u.name AS awarder_name,
                u.email AS awarder_email,
                stu_u.name AS student_name,
                s.admission_number,
                subj.name AS subject_name,
                c.name AS class_name,
                c.section_arm
            FROM student_badges sb
            JOIN badges b ON b.id = sb.badge_id
            JOIN users u ON u.id = sb.awarded_by
            JOIN students s ON s.id = sb.student_id
            JOIN users stu_u ON stu_u.id = s.user_id
            LEFT JOIN class_subjects cs ON cs.id = sb.class_subject_id
            LEFT JOIN subjects subj ON subj.id = cs.subject_id
            LEFT JOIN classes c ON c.id = COALESCE(cs.class_id, s.current_class_id)
            WHERE 1=1
        ";

        $params = [];
        if ($badgeId !== null && $badgeId > 0) {
            $sql .= " AND sb.badge_id = :badge_id";
            $params['badge_id'] = $badgeId;
        }

        if ($classId !== null && $classId > 0) {
            $sql .= " AND (c.id = :class_id OR s.current_class_id = :class_id_match)";
            $params['class_id'] = $classId;
            $params['class_id_match'] = $classId;
        }

        $sql .= " ORDER BY sb.awarded_at DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $badge = new Badge(
                id: (int)$row['badge_id'],
                name: (string)$row['badge_name'],
                slug: (string)$row['badge_slug'],
                description: (string)$row['badge_description'],
                category: (string)$row['badge_category'],
                iconName: (string)$row['badge_icon_name'],
                colorScheme: (string)$row['badge_color_scheme'],
                isSystem: (bool)$row['badge_is_system']
            );

            $studentBadge = StudentBadge::fromArray($row, $badge);
            $studentBadge->awarderName = (string)$row['awarder_name'];
            $studentBadge->studentName = (string)$row['student_name'];
            $studentBadge->admissionNumber = (string)($row['admission_number'] ?? '');
            $studentBadge->subjectName = isset($row['subject_name']) ? (string)$row['subject_name'] : null;
            $studentBadge->className = isset($row['class_name']) ? (string)$row['class_name'] : null;
            $studentBadge->sectionArm = isset($row['section_arm']) ? (string)$row['section_arm'] : null;

            $results[] = $studentBadge;
        }

        return $results;
    }

    /**
     * Find a single student badge award record by ID.
     */
    public function findStudentBadgeById(int $id): ?StudentBadge
    {
        $stmt = $this->pdo->prepare("
            SELECT sb.*, b.name AS badge_name, b.slug AS badge_slug, b.description AS badge_description,
                   b.category AS badge_category, b.icon_name AS badge_icon_name, b.color_scheme AS badge_color_scheme,
                   b.is_system AS badge_is_system
            FROM student_badges sb
            JOIN badges b ON b.id = sb.badge_id
            WHERE sb.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $badge = new Badge(
            id: (int)$row['badge_id'],
            name: (string)$row['badge_name'],
            slug: (string)$row['badge_slug'],
            description: (string)$row['badge_description'],
            category: (string)$row['badge_category'],
            iconName: (string)$row['badge_icon_name'],
            colorScheme: (string)$row['badge_color_scheme'],
            isSystem: (bool)$row['badge_is_system']
        );

        return StudentBadge::fromArray($row, $badge);
    }

    /**
     * Delete an awarded student badge (Revocation).
     */
    public function deleteStudentBadge(int $studentBadgeId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM student_badges WHERE id = :id");
        return $stmt->execute(['id' => $studentBadgeId]);
    }
}
