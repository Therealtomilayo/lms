<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\UserContext;
use App\Models\Announcement;
use PDO;

/**
 * Announcement Repository
 */
class AnnouncementRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO announcements (author_id, scope, scope_id, title, body, published_at, expires_at, created_at, updated_at)
            VALUES (:author_id, :scope, :scope_id, :title, :body, :published_at, :expires_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':author_id', (int)($data['author_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':scope', (string)($data['scope'] ?? 'school'));
        $stmt->bindValue(':scope_id', isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? (int)$data['scope_id'] : null, isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':title', (string)($data['title'] ?? ''));
        $stmt->bindValue(':body', (string)($data['body'] ?? ''));
        $stmt->bindValue(':published_at', isset($data['published_at']) && $data['published_at'] !== '' ? $data['published_at'] : null);
        $stmt->bindValue(':expires_at', isset($data['expires_at']) && $data['expires_at'] !== '' ? $data['expires_at'] : null);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE announcements
            SET scope = :scope,
                scope_id = :scope_id,
                title = :title,
                body = :body,
                published_at = :published_at,
                expires_at = :expires_at,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':scope', (string)($data['scope'] ?? 'school'));
        $stmt->bindValue(':scope_id', isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? (int)$data['scope_id'] : null, isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':title', (string)($data['title'] ?? ''));
        $stmt->bindValue(':body', (string)($data['body'] ?? ''));
        $stmt->bindValue(':published_at', isset($data['published_at']) && $data['published_at'] !== '' ? $data['published_at'] : null);
        $stmt->bindValue(':expires_at', isset($data['expires_at']) && $data['expires_at'] !== '' ? $data['expires_at'] : null);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM announcements WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function findById(int $id): ?Announcement
    {
        $sql = "
            SELECT a.*, u.name AS author_name,
                c.name AS class_name,
                c2.name AS cs_class_name,
                sub.name AS cs_subject_name
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            LEFT JOIN classes c ON c.id = a.scope_id AND a.scope = 'class'
            LEFT JOIN class_subjects cs ON cs.id = a.scope_id AND a.scope = 'class_subject'
            LEFT JOIN classes c2 ON c2.id = cs.class_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            WHERE a.id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Announcement::fromArray($row) : null;
    }

    /**
     * Get targeted announcement feed for a user (and optionally evaluated student).
     *
     * @return array<int, Announcement>
     */
    public function getFeedForUser(
        UserContext $user,
        ?int $studentId = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $userId = $user->getUserId();

        // 1. Admin gets all active announcements
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            $sql = "
                SELECT a.*, u.name AS author_name,
                    c.name AS class_name,
                    c2.name AS cs_class_name,
                    sub.name AS cs_subject_name,
                    CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END AS is_read,
                    ar.read_at
                FROM announcements a
                JOIN users u ON u.id = a.author_id
                LEFT JOIN classes c ON c.id = a.scope_id AND a.scope = 'class'
                LEFT JOIN class_subjects cs ON cs.id = a.scope_id AND a.scope = 'class_subject'
                LEFT JOIN classes c2 ON c2.id = cs.class_id
                LEFT JOIN subjects sub ON sub.id = cs.subject_id
                LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id AND ar.user_id = :user_id
                WHERE a.published_at IS NOT NULL 
                  AND a.published_at <= CURRENT_TIMESTAMP
                  AND (a.expires_at IS NULL OR a.expires_at >= CURRENT_TIMESTAMP)
                ORDER BY a.published_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = Announcement::fromArray($row);
            }
            return $results;
        }

        // 2. Teacher: school-wide OR class-subjects they teach
        if ($user->isTeacher()) {
            $sql = "
                SELECT a.*, u.name AS author_name,
                    c.name AS class_name,
                    c2.name AS cs_class_name,
                    sub.name AS cs_subject_name,
                    CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END AS is_read,
                    ar.read_at
                FROM announcements a
                JOIN users u ON u.id = a.author_id
                LEFT JOIN classes c ON c.id = a.scope_id AND a.scope = 'class'
                LEFT JOIN class_subjects cs ON cs.id = a.scope_id AND a.scope = 'class_subject'
                LEFT JOIN classes c2 ON c2.id = cs.class_id
                LEFT JOIN subjects sub ON sub.id = cs.subject_id
                LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id AND ar.user_id = :user_id
                WHERE a.published_at IS NOT NULL 
                  AND a.published_at <= CURRENT_TIMESTAMP
                  AND (a.expires_at IS NULL OR a.expires_at >= CURRENT_TIMESTAMP)
                  AND (
                      a.scope = 'school' 
                      OR a.author_id = :user_id3
                      OR (a.scope = 'class_subject' AND a.scope_id IN (
                          SELECT teacher_cs.id FROM class_subjects teacher_cs
                          JOIN teachers t ON t.id = teacher_cs.teacher_id
                          WHERE t.user_id = :user_id2
                      ))
                      OR (a.scope = 'class' AND a.scope_id IN (
                          SELECT teacher_cs2.class_id FROM class_subjects teacher_cs2
                          JOIN teachers t2 ON t2.id = teacher_cs2.teacher_id
                          WHERE t2.user_id = :user_id4
                      ))
                  )
                ORDER BY a.published_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id3', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id4', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = Announcement::fromArray($row);
            }
            return $results;
        }

        // 3. Student or Parent (resolving by evaluated studentId or user student)
        $targetStudentId = $studentId;
        if ($targetStudentId === null && $user->isStudent()) {
            // Find student id by user_id
            $studentStmt = $this->db->prepare("SELECT id FROM students WHERE user_id = :uid LIMIT 1");
            $studentStmt->execute([':uid' => $userId]);
            $targetStudentId = (int)$studentStmt->fetchColumn();
        }

        $sql = "
            SELECT a.*, u.name AS author_name,
                c.name AS class_name,
                c2.name AS cs_class_name,
                sub.name AS cs_subject_name,
                CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END AS is_read,
                ar.read_at
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            LEFT JOIN classes c ON c.id = a.scope_id AND a.scope = 'class'
            LEFT JOIN class_subjects cs ON cs.id = a.scope_id AND a.scope = 'class_subject'
            LEFT JOIN classes c2 ON c2.id = cs.class_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            LEFT JOIN announcement_reads ar ON ar.announcement_id = a.id AND ar.user_id = :user_id
            WHERE a.published_at IS NOT NULL 
              AND a.published_at <= CURRENT_TIMESTAMP
              AND (a.expires_at IS NULL OR a.expires_at >= CURRENT_TIMESTAMP)
              AND (
                  a.scope = 'school'
                  OR (a.scope = 'class' AND a.scope_id IN (
                      SELECT ce.class_id FROM class_enrollments ce
                      WHERE ce.student_id = :student_id AND ce.status = 'active'
                  ))
                  OR (a.scope = 'class_subject' AND a.scope_id IN (
                      SELECT sse.class_subject_id FROM student_subject_enrollments sse
                      WHERE sse.student_id = :student_id2 AND sse.status = 'active'
                  ))
              )
            ORDER BY a.published_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id', $targetStudentId ?: 0, PDO::PARAM_INT);
        $stmt->bindValue(':student_id2', $targetStudentId ?: 0, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Announcement::fromArray($row);
        }
        return $results;
    }

    public function getUnreadCount(UserContext $user, ?int $studentId = null): int
    {
        $feed = $this->getFeedForUser($user, $studentId, 100, 0);
        $unread = 0;
        foreach ($feed as $item) {
            if (!$item->isRead) {
                $unread++;
            }
        }
        return $unread;
    }

    public function markAsRead(int $announcementId, int $userId): bool
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "INSERT OR IGNORE INTO announcement_reads (announcement_id, user_id, read_at, created_at) VALUES (:aid, :uid, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        } else {
            $sql = "INSERT INTO announcement_reads (announcement_id, user_id, read_at, created_at) VALUES (:aid, :uid, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':aid' => $announcementId,
            ':uid' => $userId,
        ]);
    }

    public function listAllForAdmin(int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT a.*, u.name AS author_name,
                c.name AS class_name,
                c2.name AS cs_class_name,
                sub.name AS cs_subject_name,
                0 AS is_read,
                NULL AS read_at
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            LEFT JOIN classes c ON c.id = a.scope_id AND a.scope = 'class'
            LEFT JOIN class_subjects cs ON cs.id = a.scope_id AND a.scope = 'class_subject'
            LEFT JOIN classes c2 ON c2.id = cs.class_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Announcement::fromArray($row);
        }
        return $results;
    }

    public function listByAuthor(int $authorId, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT a.*, u.name AS author_name,
                c.name AS class_name,
                c2.name AS cs_class_name,
                sub.name AS cs_subject_name,
                0 AS is_read,
                NULL AS read_at
            FROM announcements a
            JOIN users u ON u.id = a.author_id
            LEFT JOIN classes c ON c.id = a.scope_id AND a.scope = 'class'
            LEFT JOIN class_subjects cs ON cs.id = a.scope_id AND a.scope = 'class_subject'
            LEFT JOIN classes c2 ON c2.id = cs.class_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            WHERE a.author_id = :author_id
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':author_id', $authorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Announcement::fromArray($row);
        }
        return $results;
    }
}
