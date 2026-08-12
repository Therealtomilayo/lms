<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Question Domain Model
 * Represents an independent, reusable question bank entry scoped to a subject.
 */
final class Question
{
    public const TYPE_MCQ = 'mcq';
    public const TYPE_SHORT_ANSWER = 'short_answer';

    /**
     * @param array<int, QuestionOption> $options
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $subjectId,
        public readonly ?string $topic,
        public readonly string $questionText,
        public readonly string $type = self::TYPE_MCQ,
        public readonly float $defaultPoints = 1.00,
        public readonly int $createdBy = 0,
        public readonly ?Subject $subject = null,
        public readonly ?User $author = null,
        public readonly array $options = [],
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * Hydrate Question model from database associative array.
     *
     * @param array<string, mixed> $data
     * @param array<int, QuestionOption> $options
     */
    public static function fromArray(
        array $data,
        ?Subject $subject = null,
        ?User $author = null,
        array $options = []
    ): self {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            subjectId: (int)($data['subject_id'] ?? 0),
            topic: isset($data['topic']) && $data['topic'] !== '' ? (string)$data['topic'] : null,
            questionText: (string)($data['question_text'] ?? ''),
            type: (string)($data['type'] ?? self::TYPE_MCQ),
            defaultPoints: isset($data['default_points']) ? (float)$data['default_points'] : 1.00,
            createdBy: (int)($data['created_by'] ?? 0),
            subject: $subject,
            author: $author,
            options: $options,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function isMcq(): bool
    {
        return $this->type === self::TYPE_MCQ;
    }

    public function isShortAnswer(): bool
    {
        return $this->type === self::TYPE_SHORT_ANSWER;
    }

    /**
     * Get correct option if MCQ.
     */
    public function getCorrectOption(): ?QuestionOption
    {
        foreach ($this->options as $option) {
            if ($option->isCorrect) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Convert to array representation.
     *
     * @param bool $includeCorrectness If false, hides correctness of child options
     * @return array<string, mixed>
     */
    public function toArray(bool $includeCorrectness = true): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subjectId,
            'topic' => $this->topic,
            'question_text' => $this->questionText,
            'type' => $this->type,
            'default_points' => $this->defaultPoints,
            'created_by' => $this->createdBy,
            'options' => array_map(fn(QuestionOption $opt) => $opt->toArray($includeCorrectness), $this->options),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
