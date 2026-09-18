<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Unified Learning Activity Progress & Completion
 * Supports documents, CBT quizzes, and coursework activities.
 */
final class ActivityProgress
{
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_DOCUMENT_SECTION = 'document_section';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_ASSIGNMENT = 'assignment';

    public const COMPLETION_THRESHOLD_PERCENT = 90.0;

    /**
     * @param int[] $pagesRead
     */
    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly string $activityType,
        public readonly int $activityId,
        public readonly ?int $lastPage = null,
        public readonly ?int $totalPages = null,
        public readonly array $pagesRead = [],
        public readonly float $progressPercent = 0.0,
        public readonly bool $isCompleted = false,
        public readonly ?string $completedAt = null,
        public readonly ?string $lastAccessedAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $pages = [];
        if (!empty($data['pages_read_json'])) {
            if (is_array($data['pages_read_json'])) {
                $pages = $data['pages_read_json'];
            } else {
                $decoded = json_decode((string)$data['pages_read_json'], true);
                if (is_array($decoded)) {
                    $pages = $decoded;
                }
            }
        }

        // Ensure unique, numeric, positive, sorted page list
        $pages = array_values(array_unique(array_filter(
            array_map('intval', $pages),
            fn(int $p) => $p > 0
        )));
        sort($pages, SORT_NUMERIC);

        return new self(
            id: (int)($data['id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            activityType: (string)($data['activity_type'] ?? self::TYPE_DOCUMENT),
            activityId: (int)($data['activity_id'] ?? 0),
            lastPage: isset($data['last_page']) && $data['last_page'] !== null ? (int)$data['last_page'] : null,
            totalPages: isset($data['total_pages']) && $data['total_pages'] !== null ? (int)$data['total_pages'] : null,
            pagesRead: $pages,
            progressPercent: (float)($data['progress_percent'] ?? 0.0),
            isCompleted: (bool)($data['is_completed'] ?? false),
            completedAt: $data['completed_at'] ?? null,
            lastAccessedAt: $data['last_accessed_at'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function getUniquePagesCount(): int
    {
        return count($this->pagesRead);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'pagesReadJson') {
            return $this->pagesRead;
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'activity_type' => $this->activityType,
            'activity_id' => $this->activityId,
            'last_page' => $this->lastPage,
            'total_pages' => $this->totalPages,
            'pages_read' => $this->pagesRead,
            'pages_read_count' => $this->getUniquePagesCount(),
            'progress_percent' => $this->progressPercent,
            'is_completed' => $this->isCompleted,
            'completed_at' => $this->completedAt,
            'last_accessed_at' => $this->lastAccessedAt,
        ];
    }
}
