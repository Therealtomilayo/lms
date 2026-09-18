<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ClassDiscussion;
use App\Models\ClassDiscussionReply;
use App\Models\User;
use PDO;

class DiscussionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Get discussions for a class subject, ordered with pinned topics first, then newest.
     * @return ClassDiscussion[]
     */
    public function getDiscussionsBySubject(int $classSubjectId, int $limit = 30, int $offset = 0): array
    {
        $sql = "
            SELECT 
                cd.*,
                u.name AS author_name,
                u.email AS author_email,
                (SELECT COUNT(1) FROM class_discussion_replies cdr WHERE cdr.discussion_id = cd.id) AS reply_count
            FROM class_discussions cd
            JOIN users u ON u.id = cd.user_id
            WHERE cd.class_subject_id = :class_subject_id
            ORDER BY cd.is_pinned DESC, cd.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':class_subject_id', $classSubjectId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            $author = new User(
                id: (int)$row['user_id'],
                uuid: '',
                name: (string)$row['author_name'],
                email: (string)$row['author_email'],
                passwordHash: '',
                roles: []
            );
            return ClassDiscussion::fromArray($row, $author);
        }, $rows);
    }

    public function countDiscussionsBySubject(int $classSubjectId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(1) FROM class_discussions WHERE class_subject_id = :cs_id");
        $stmt->execute(['cs_id' => $classSubjectId]);

        return (int)$stmt->fetchColumn();
    }

    public function findDiscussionById(int $discussionId): ?ClassDiscussion
    {
        $sql = "
            SELECT 
                cd.*,
                u.name AS author_name,
                u.email AS author_email,
                (SELECT COUNT(1) FROM class_discussion_replies cdr WHERE cdr.discussion_id = cd.id) AS reply_count
            FROM class_discussions cd
            JOIN users u ON u.id = cd.user_id
            WHERE cd.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $discussionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $author = new User(
            id: (int)$row['user_id'],
            uuid: '',
            name: (string)$row['author_name'],
            email: (string)$row['author_email'],
            passwordHash: '',
            roles: []
        );

        return ClassDiscussion::fromArray($row, $author);
    }

    /**
     * Get discussion with all replies in chronological order.
     */
    public function getDiscussionWithReplies(int $discussionId): ?ClassDiscussion
    {
        $discussion = $this->findDiscussionById($discussionId);
        if (!$discussion) {
            return null;
        }

        $sql = "
            SELECT 
                cdr.*,
                u.name AS author_name,
                u.email AS author_email
            FROM class_discussion_replies cdr
            JOIN users u ON u.id = cdr.user_id
            WHERE cdr.discussion_id = :disc_id
            ORDER BY cdr.created_at ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['disc_id' => $discussionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $replies = array_map(function ($row) {
            $author = new User(
                id: (int)$row['user_id'],
                uuid: '',
                name: (string)$row['author_name'],
                email: (string)$row['author_email'],
                passwordHash: '',
                roles: []
            );
            return ClassDiscussionReply::fromArray($row, $author);
        }, $rows);

        $discussion->replies = $replies;
        $discussion->replyCount = count($replies);

        return $discussion;
    }

    public function createDiscussion(int $classSubjectId, int $userId, string $title, string $content): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            INSERT INTO class_discussions (class_subject_id, user_id, title, content, is_pinned, is_locked, created_at, updated_at)
            VALUES (:class_subject_id, :user_id, :title, :content, 0, 0, :created_at, :updated_at)
        ");
        $stmt->execute([
            'class_subject_id' => $classSubjectId,
            'user_id' => $userId,
            'title' => trim($title),
            'content' => trim($content),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function addReply(int $discussionId, int $userId, string $content): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare("
            INSERT INTO class_discussion_replies (discussion_id, user_id, content, created_at, updated_at)
            VALUES (:discussion_id, :user_id, :content, :created_at, :updated_at)
        ");
        $stmt->execute([
            'discussion_id' => $discussionId,
            'user_id' => $userId,
            'content' => trim($content),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Touch discussion updated_at
        $touch = $this->pdo->prepare("UPDATE class_discussions SET updated_at = :updated_at WHERE id = :id");
        $touch->execute(['updated_at' => $now, 'id' => $discussionId]);

        return (int)$this->pdo->lastInsertId();
    }

    public function togglePin(int $discussionId, bool $isPinned): void
    {
        $stmt = $this->pdo->prepare("UPDATE class_discussions SET is_pinned = :pinned WHERE id = :id");
        $stmt->execute(['pinned' => $isPinned ? 1 : 0, 'id' => $discussionId]);
    }

    public function toggleLock(int $discussionId, bool $isLocked): void
    {
        $stmt = $this->pdo->prepare("UPDATE class_discussions SET is_locked = :locked WHERE id = :id");
        $stmt->execute(['locked' => $isLocked ? 1 : 0, 'id' => $discussionId]);
    }

    public function deleteDiscussion(int $discussionId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM class_discussions WHERE id = :id");
        $stmt->execute(['id' => $discussionId]);
    }

    public function deleteReply(int $replyId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM class_discussion_replies WHERE id = :id");
        $stmt->execute(['id' => $replyId]);
    }

    public function findReplyById(int $replyId): ?ClassDiscussionReply
    {
        $stmt = $this->pdo->prepare("SELECT * FROM class_discussion_replies WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $replyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ClassDiscussionReply::fromArray($row) : null;
    }
}
