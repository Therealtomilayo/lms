<?php

declare(strict_types=1);

namespace App\Models;

/**
 * QuestionOption Domain Model
 * Represents a choice option for a multiple-choice question (MCQ).
 */
final class QuestionOption
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $questionId,
        public readonly string $optionText,
        public readonly bool $isCorrect = false,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * Hydrate QuestionOption model from database associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            questionId: (int)($data['question_id'] ?? 0),
            optionText: (string)($data['option_text'] ?? ''),
            isCorrect: !empty($data['is_correct']),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    /**
     * Convert to array representation.
     *
     * @param bool $includeCorrectness If false, hides whether this option is correct (used when sending to student during taking)
     * @return array<string, mixed>
     */
    public function toArray(bool $includeCorrectness = true): array
    {
        $arr = [
            'id' => $this->id,
            'question_id' => $this->questionId,
            'option_text' => $this->optionText,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($includeCorrectness) {
            $arr['is_correct'] = $this->isCorrect;
        }

        return $arr;
    }
}
