<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for an Activity assigned to a Learning Module
 * Associates a document, quiz, or assignment with sequence order and required completion flag.
 */
#[\AllowDynamicProperties]
final class ModuleItem
{
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_ASSIGNMENT = 'assignment';

    public function __construct(
        public readonly int $id,
        public readonly int $moduleId,
        public readonly string $activityType,
        public readonly int $activityId,
        public readonly int $sequenceOrder = 1,
        public readonly bool $isRequired = true,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public string $title = '';
    public ?string $description = null;
    public ?string $itemSubtype = null; // e.g. 'pdf', 'docx', 'mcq', etc.
    public ?float $progressPercent = null;
    public bool $isCompleted = false;
    public ?string $completedAt = null;
    public bool $isUnlocked = true;
    public array $unmetPrerequisites = [];
    public ?string $resumeUrl = null;
    public string $statusState = 'not_started'; // 'locked', 'not_started', 'in_progress', 'completed'
    public mixed $entity = null;

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            moduleId: (int)($data['module_id'] ?? 0),
            activityType: (string)($data['activity_type'] ?? self::TYPE_DOCUMENT),
            activityId: (int)($data['activity_id'] ?? 0),
            sequenceOrder: (int)($data['sequence_order'] ?? 1),
            isRequired: (bool)($data['is_required'] ?? true),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->moduleId,
            'activity_type' => $this->activityType,
            'activity_id' => $this->activityId,
            'sequence_order' => $this->sequenceOrder,
            'is_required' => $this->isRequired,
            'title' => $this->title,
            'description' => $this->description,
            'item_subtype' => $this->itemSubtype,
            'progress_percent' => $this->progressPercent,
            'is_completed' => $this->isCompleted,
            'completed_at' => $this->completedAt,
            'is_unlocked' => $this->isUnlocked,
            'unmet_prerequisites' => $this->unmetPrerequisites,
            'resume_url' => $this->resumeUrl,
            'status_state' => $this->statusState,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
