<?php

declare(strict_types=1);

namespace App\Models;

/**
 * QuizAnswer Domain Model
 * Represents a student's answer to a specific quiz question within an attempt.
 */
final class QuizAnswer
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $attemptId,
        public readonly int $questionId,
        public readonly ?int $selectedOptionId = null,
        public readonly ?string $textAnswer = null,
        public readonly ?float $pointsAwarded = null,
        public readonly ?string $teacherComment = null,
        public readonly ?Question $question = null,
        public readonly ?QuestionOption $selectedOption = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * Hydrate QuizAnswer model from database associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data,
        ?Question $question = null,
        ?QuestionOption $selectedOption = null
    ): self {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            attemptId: (int)($data['attempt_id'] ?? 0),
            questionId: (int)($data['question_id'] ?? 0),
            selectedOptionId: isset($data['selected_option_id']) && $data['selected_option_id'] !== '' ? (int)$data['selected_option_id'] : null,
            textAnswer: isset($data['text_answer']) && $data['text_answer'] !== '' ? (string)$data['text_answer'] : null,
            pointsAwarded: isset($data['points_awarded']) ? (float)$data['points_awarded'] : null,
            teacherComment: isset($data['teacher_comment']) && $data['teacher_comment'] !== '' ? (string)$data['teacher_comment'] : null,
            question: $question,
            selectedOption: $selectedOption,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function isGraded(): bool
    {
        return $this->pointsAwarded !== null;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'attempt_id' => $this->attemptId,
            'question_id' => $this->questionId,
            'selected_option_id' => $this->selectedOptionId,
            'text_answer' => $this->textAnswer,
            'points_awarded' => $this->pointsAwarded,
            'teacher_comment' => $this->teacherComment,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
