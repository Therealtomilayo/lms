<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Student Skill Rating Entity
 */
final class StudentSkillRating
{
    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $termId,
        public readonly int $skillId,
        public readonly int $rating, // 1 to 5 scale
        public readonly ?int $recordedBy = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?Skill $skill = null
    ) {
    }

    public static function fromArray(array $data, ?Skill $skill = null): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            skillId: (int)($data['skill_id'] ?? 0),
            rating: (int)($data['rating'] ?? 3),
            recordedBy: isset($data['recorded_by']) && $data['recorded_by'] !== null ? (int)$data['recorded_by'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            skill: $skill ?? (!empty($data['skill_name']) ? Skill::fromArray([
                'id' => $data['skill_id'],
                'name' => $data['skill_name'],
                'category' => $data['skill_category'] ?? 'psychomotor',
                'display_order' => $data['display_order'] ?? 1,
                'status' => $data['skill_status'] ?? 'active',
            ]) : null)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'term_id' => $this->termId,
            'skill_id' => $this->skillId,
            'rating' => $this->rating,
            'recorded_by' => $this->recordedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'skill' => $this->skill?->toArray(),
        ];
    }
}
