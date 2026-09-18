<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\DocumentSection;
use PDO;

/**
 * Repository for Logical PDF Document Sections
 * Supports section querying, creation, updating, overlap detection, and deletion.
 */
class DocumentSectionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Find a single section by its primary key ID.
     */
    public function findById(int $id): ?DocumentSection
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `document_sections`
            WHERE `id` = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? DocumentSection::fromArray($row) : null;
    }

    /**
     * Retrieve all sections for a given content item, sorted by sequence_order ASC, id ASC.
     *
     * @return DocumentSection[]
     */
    public function getByContentItemId(int $contentItemId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `document_sections`
            WHERE `content_item_id` = :content_item_id
            ORDER BY `sequence_order` ASC, `id` ASC
        ');
        $stmt->execute([':content_item_id' => $contentItemId]);

        $sections = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sections[] = DocumentSection::fromArray($row);
        }

        return $sections;
    }

    /**
     * Check if a proposed [startPage, endPage] range overlaps with any existing section
     * in the same document.
     *
     * Overlap rule: startPage <= other.end_page AND endPage >= other.start_page
     *
     * @param int $contentItemId
     * @param int $startPage
     * @param int $endPage
     * @param int|null $excludeSectionId Section to exclude (when editing existing)
     * @return DocumentSection|null Overlapping section if found, null otherwise
     */
    public function findOverlappingSection(
        int $contentItemId,
        int $startPage,
        int $endPage,
        ?int $excludeSectionId = null
    ): ?DocumentSection {
        $sql = '
            SELECT * FROM `document_sections`
            WHERE `content_item_id` = :content_item_id
              AND :start_page <= `end_page`
              AND :end_page >= `start_page`
        ';
        $params = [
            ':content_item_id' => $contentItemId,
            ':start_page' => $startPage,
            ':end_page' => $endPage,
        ];

        if ($excludeSectionId !== null && $excludeSectionId > 0) {
            $sql .= ' AND `id` != :exclude_id';
            $params[':exclude_id'] = $excludeSectionId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? DocumentSection::fromArray($row) : null;
    }

    /**
     * Get the next sequence order number for a new section in a document.
     */
    public function getNextSequenceOrder(int $contentItemId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT MAX(`sequence_order`) as `max_order`
            FROM `document_sections`
            WHERE `content_item_id` = :content_item_id
        ');
        $stmt->execute([':content_item_id' => $contentItemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $max = $row && isset($row['max_order']) && $row['max_order'] !== null
            ? (int)$row['max_order']
            : -1;

        return $max + 1;
    }

    /**
     * Create a new document section.
     */
    public function create(array $data): DocumentSection
    {
        $now = date('Y-m-d H:i:s');
        $order = isset($data['sequence_order'])
            ? (int)$data['sequence_order']
            : $this->getNextSequenceOrder((int)$data['content_item_id']);

        $stmt = $this->pdo->prepare('
            INSERT INTO `document_sections` (
                `content_item_id`, `title`, `start_page`, `end_page`, `sequence_order`, `created_at`, `updated_at`
            ) VALUES (
                :content_item_id, :title, :start_page, :end_page, :sequence_order, :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':content_item_id' => (int)$data['content_item_id'],
            ':title' => trim((string)$data['title']),
            ':start_page' => (int)$data['start_page'],
            ':end_page' => (int)$data['end_page'],
            ':sequence_order' => $order,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $newId = (int)$this->pdo->lastInsertId();

        return $this->findById($newId);
    }

    /**
     * Update an existing document section.
     */
    public function update(int $id, array $data): DocumentSection
    {
        $now = date('Y-m-d H:i:s');
        $fields = [];
        $params = [':id' => $id, ':updated_at' => $now];

        if (isset($data['title'])) {
            $fields[] = '`title` = :title';
            $params[':title'] = trim((string)$data['title']);
        }
        if (isset($data['start_page'])) {
            $fields[] = '`start_page` = :start_page';
            $params[':start_page'] = (int)$data['start_page'];
        }
        if (isset($data['end_page'])) {
            $fields[] = '`end_page` = :end_page';
            $params[':end_page'] = (int)$data['end_page'];
        }
        if (isset($data['sequence_order'])) {
            $fields[] = '`sequence_order` = :sequence_order';
            $params[':sequence_order'] = (int)$data['sequence_order'];
        }

        $fields[] = '`updated_at` = :updated_at';

        $sql = 'UPDATE `document_sections` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    /**
     * Delete a document section.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `document_sections` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }
}
