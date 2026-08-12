<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ClassSubject;
use App\Models\ContentItem;
use App\Models\FileRecord;
use App\Models\Teacher;
use PDO;

/**
 * Data Access Layer for Class-Subject Content Items (Lesson notes, documents, videos, links)
 */
class ContentRepository
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
        int $teacherId,
        ?string $topic,
        string $title,
        ?string $description,
        string $type,
        ?int $fileId = null,
        ?string $externalUrl = null,
        ?string $publishedAt = null
    ): ContentItem {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `content_items` (
                `class_subject_id`, `teacher_id`, `topic`, `title`, `description`, 
                `type`, `file_id`, `external_url`, `published_at`, `created_at`, `updated_at`
            ) VALUES (
                :class_subject_id, :teacher_id, :topic, :title, :description, 
                :type, :file_id, :external_url, :published_at, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':teacher_id' => $teacherId,
            ':topic' => $topic !== null && trim($topic) !== '' ? trim($topic) : null,
            ':title' => trim($title),
            ':description' => $description,
            ':type' => $type,
            ':file_id' => $fileId,
            ':external_url' => $externalUrl !== null && trim($externalUrl) !== '' ? trim($externalUrl) : null,
            ':published_at' => $publishedAt,
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

        if (array_key_exists('description', $data)) {
            $fields[] = '`description` = :description';
            $params[':description'] = $data['description'];
        }

        if (array_key_exists('type', $data)) {
            $fields[] = '`type` = :type';
            $params[':type'] = $data['type'];
        }

        if (array_key_exists('file_id', $data)) {
            $fields[] = '`file_id` = :file_id';
            $params[':file_id'] = $data['file_id'] !== null && $data['file_id'] !== '' ? (int)$data['file_id'] : null;
        }

        if (array_key_exists('external_url', $data)) {
            $fields[] = '`external_url` = :external_url';
            $params[':external_url'] = $data['external_url'] !== null && trim((string)$data['external_url']) !== '' ? trim((string)$data['external_url']) : null;
        }

        if (array_key_exists('published_at', $data)) {
            $fields[] = '`published_at` = :published_at';
            $params[':published_at'] = $data['published_at'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = '`updated_at` = :updated_at';
        $params[':updated_at'] = date('Y-m-d H:i:s');

        $sql = 'UPDATE `content_items` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `content_items` WHERE `id` = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function findById(int $id): ?ContentItem
    {
        $sql = 'SELECT ci.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `content_items` ci
                JOIN `class_subjects` cs ON cs.id = ci.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `teachers` t ON t.id = ci.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = ci.file_id AND f.deleted_at IS NULL
                WHERE ci.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ContentItem::fromArray($row) : null;
    }

    /**
     * @return ContentItem[]
     */
    public function getByClassSubject(int $classSubjectId): array
    {
        $sql = 'SELECT ci.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `content_items` ci
                JOIN `class_subjects` cs ON cs.id = ci.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `teachers` t ON t.id = ci.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = ci.file_id AND f.deleted_at IS NULL
                WHERE ci.class_subject_id = :class_subject_id
                ORDER BY ci.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => ContentItem::fromArray($row), $rows);
    }

    /**
     * @return ContentItem[]
     */
    public function getPublishedByClassSubject(int $classSubjectId): array
    {
        $sql = 'SELECT ci.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `content_items` ci
                JOIN `class_subjects` cs ON cs.id = ci.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `teachers` t ON t.id = ci.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = ci.file_id AND f.deleted_at IS NULL
                WHERE ci.class_subject_id = :class_subject_id
                  AND ci.published_at IS NOT NULL
                ORDER BY ci.published_at DESC, ci.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => ContentItem::fromArray($row), $rows);
    }

    /**
     * @param int[] $classSubjectIds
     * @return ContentItem[]
     */
    public function getPublishedForMultipleClassSubjects(array $classSubjectIds): array
    {
        if (empty($classSubjectIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($classSubjectIds), '?'));
        $sql = "SELECT ci.*, 
                       cs.session_id, cs.class_id, cs.subject_id,
                       s.name as subject_name, s.code as subject_code,
                       c.name as class_name, c.section_arm,
                       t.user_id as teacher_user_id, t.staff_id as teacher_staff_id,
                       u.name as teacher_name, u.email as teacher_email,
                       f.uuid as file_uuid, f.storage_key as file_storage_key, f.original_name as file_original_name,
                       f.mime_type as file_mime_type, f.size_bytes as file_size_bytes, f.sha256 as file_sha256,
                       f.uploaded_by as file_uploaded_by, f.owner_type as file_owner_type, f.owner_id as file_owner_id,
                       f.created_at as file_created_at, f.deleted_at as file_deleted_at
                FROM `content_items` ci
                JOIN `class_subjects` cs ON cs.id = ci.class_subject_id
                JOIN `subjects` s ON s.id = cs.subject_id
                JOIN `classes` c ON c.id = cs.class_id
                JOIN `teachers` t ON t.id = ci.teacher_id
                JOIN `users` u ON u.id = t.user_id
                LEFT JOIN `files` f ON f.id = ci.file_id AND f.deleted_at IS NULL
                WHERE ci.class_subject_id IN ({$placeholders})
                  AND ci.published_at IS NOT NULL
                ORDER BY ci.published_at DESC, ci.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($classSubjectIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => ContentItem::fromArray($row), $rows);
    }

    /**
     * @return string[]
     */
    public function getTopicsByClassSubject(int $classSubjectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT `topic` 
             FROM `content_items` 
             WHERE `class_subject_id` = :class_subject_id AND `topic` IS NOT NULL AND `topic` != "" 
             ORDER BY `topic` ASC'
        );
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_filter($rows, fn($r) => is_string($r) && trim($r) !== '');
    }

    public function publish(int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `content_items` SET `published_at` = :published_at, `updated_at` = :updated_at WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':published_at' => $now,
            ':updated_at' => $now,
        ]);
    }

    public function unpublish(int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `content_items` SET `published_at` = NULL, `updated_at` = :updated_at WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':updated_at' => $now,
        ]);
    }
}
