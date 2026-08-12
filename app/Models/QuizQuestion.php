<?php

declare(strict_types=1);

namespace App\Models;

/**
 * QuizQuestion Pivot Domain Model
 * Represents a question mapped to a specific quiz with allocated points and ordering.
 */
final class QuizQuestion
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $quizId,
        public readonly int $questionId,
        public readonly float $points = 1.00,
        public readonly int $sortOrder = 0,
        public readonly ?Question $question = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * Hydrate QuizQuestion model from database associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, ?Question $question = null): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            quizId: (int)($data['quiz_id'] ?? 0),
            questionId: (int)($data['question_id'] ?? 0),
            points: isset($data['points']) ? (float)$data['points'] : 1.00,
            sortOrder: (int)($data['sort_order'] ?? 0),
            question: $question,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    /**
     * Convert to array representation.
     */
    public function toArray(bool $includeCorrectness = true): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quizId,
            'question_id' => $this->questionId,
            'points' => $this->points,
            'sort_order' => $this->sortOrder,
            'question' => $this->question?->toArray($includeCorrectness),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
