<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Logical PDF Document Section
 * Partitions a single physical PDF into structured learning segments.
 */
#[\AllowDynamicProperties]
final class DocumentSection
{
    public const COMPLETION_THRESHOLD_PERCENT = 90.0;

    public function __construct(
        public readonly int $id,
        public readonly int $contentItemId,
        public readonly string $title,
        public readonly int $startPage,
        public readonly int $endPage,
        public readonly int $sequenceOrder = 0,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public ?float $progressPercent = null;
    public ?bool $isCompleted = null;
    public ?string $completedAt = null;
    public bool $isUnlocked = true;
    public ?array $prerequisiteStatus = null;
    public array $configuredPrerequisites = [];

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            contentItemId: (int)($data['content_item_id'] ?? 0),
            title: trim((string)($data['title'] ?? '')),
            startPage: (int)($data['start_page'] ?? 1),
            endPage: (int)($data['end_page'] ?? 1),
            sequenceOrder: (int)($data['sequence_order'] ?? 0),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    /**
     * Get array of all page numbers belonging to this section.
     *
     * @return int[]
     */
    public function getPageRange(): array
    {
        if ($this->startPage > $this->endPage) {
            return [$this->startPage];
        }

        return range($this->startPage, $this->endPage);
    }

    /**
     * Total number of pages inside this section.
     */
    public function getTotalPages(): int
    {
        return max(1, $this->endPage - $this->startPage + 1);
    }

    /**
     * Check if a given page number falls within this section.
     */
    public function containsPage(int $page): bool
    {
        return $page >= $this->startPage && $page <= $this->endPage;
    }

    /**
     * Check if a given page range overlaps with this section.
     */
    public function overlapsWith(int $startPage, int $endPage): bool
    {
        return max($this->startPage, $startPage) <= min($this->endPage, $endPage);
    }

    /**
     * Authoritatively derive section reading progress and completion from document pages read.
     *
     * Formula:
     * section_pages_read = intersection(document_pages_read, section_page_range)
     * section_progress = count(section_pages_read) / total_section_pages * 100
     * completion = section_progress >= 90.00%
     *
     * @param int[] $documentPagesRead
     * @return array{
     *     pages_read: int[],
     *     unique_pages_count: int,
     *     total_pages: int,
     *     progress_percent: float,
     *     is_completed: bool
     * }
     */
    public function calculateProgress(array $documentPagesRead): array
    {
        $range = $this->getPageRange();
        $total = count($range);

        $readInSection = array_values(array_unique(array_intersect($documentPagesRead, $range)));
        sort($readInSection, SORT_NUMERIC);
        $readCount = count($readInSection);

        $progressPercent = $total > 0
            ? min(100.00, round(($readCount / $total) * 100, 2))
            : 0.00;

        $isCompleted = $progressPercent >= self::COMPLETION_THRESHOLD_PERCENT;

        return [
            'pages_read' => $readInSection,
            'unique_pages_count' => $readCount,
            'total_pages' => $total,
            'progress_percent' => $progressPercent,
            'is_completed' => $isCompleted,
        ];
    }

    public function toArray(): array
    {
        $arr = [
            'id' => $this->id,
            'content_item_id' => $this->contentItemId,
            'title' => $this->title,
            'start_page' => $this->startPage,
            'end_page' => $this->endPage,
            'sequence_order' => $this->sequenceOrder,
            'total_pages' => $this->getTotalPages(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->progressPercent !== null) {
            $arr['progress_percent'] = $this->progressPercent;
            $arr['is_completed'] = $this->isCompleted ?? false;
            $arr['completed_at'] = $this->completedAt;
        }

        if ($this->prerequisiteStatus !== null) {
            $arr['is_unlocked'] = $this->isUnlocked;
            $arr['prerequisite_status'] = $this->prerequisiteStatus;
        }

        if (!empty($this->configuredPrerequisites)) {
            $arr['configured_prerequisites'] = $this->configuredPrerequisites;
        }

        return $arr;
    }
}
