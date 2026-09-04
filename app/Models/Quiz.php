<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Quiz Domain Model
 * Represents a timed or untimed assessment scoped to a class_subject and academic term.
 */
final class Quiz
{
    /**
     * @param array<int, QuizQuestion> $quizQuestions
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $classSubjectId,
        public readonly int $termId,
        public readonly ?int $assessmentCategoryId,
        public readonly int $teacherId,
        public readonly string $title,
        public readonly ?string $instructions = null,
        public readonly int $timeLimitMinutes = 0,
        public readonly int $maxAttempts = 1,
        public readonly bool $isPublished = false,
        public readonly ?string $publishedAt = null,
        public readonly ?ClassSubject $classSubject = null,
        public readonly ?Term $term = null,
        public readonly ?Teacher $teacher = null,
        public readonly array $quizQuestions = [],
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?float $precomputedMaxScore = null
    ) {
    }

    /**
     * Hydrate Quiz model from database associative array.
     *
     * @param array<string, mixed> $data
     * @param array<int, QuizQuestion> $quizQuestions
     */
    public static function fromArray(
        array $data,
        ?ClassSubject $classSubject = null,
        ?Term $term = null,
        ?Teacher $teacher = null,
        array $quizQuestions = []
    ): self {
        $precomputed = isset($data['total_max_score'])
            ? (float)$data['total_max_score']
            : (isset($data['calculated_max_score']) ? (float)$data['calculated_max_score'] : (isset($data['max_score']) ? (float)$data['max_score'] : null));

        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            assessmentCategoryId: isset($data['assessment_category_id']) && $data['assessment_category_id'] !== '' ? (int)$data['assessment_category_id'] : null,
            teacherId: (int)($data['teacher_id'] ?? 0),
            title: (string)($data['title'] ?? ''),
            instructions: isset($data['instructions']) && $data['instructions'] !== '' ? (string)$data['instructions'] : null,
            timeLimitMinutes: (int)($data['time_limit_minutes'] ?? 0),
            maxAttempts: max(1, (int)($data['max_attempts'] ?? 1)),
            isPublished: !empty($data['is_published']),
            publishedAt: isset($data['published_at']) ? (string)$data['published_at'] : null,
            classSubject: $classSubject,
            term: $term,
            teacher: $teacher,
            quizQuestions: $quizQuestions,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            precomputedMaxScore: $precomputed
        );
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function hasTimeLimit(): bool
    {
        return $this->timeLimitMinutes > 0;
    }

    /**
     * Calculate total maximum score from all attached quiz questions.
     */
    public function getTotalMaxScore(): float
    {
        if (!empty($this->quizQuestions)) {
            $total = 0.0;
            foreach ($this->quizQuestions as $qq) {
                $total += (float)$qq->points;
            }
            return $total;
        }

        if ($this->precomputedMaxScore !== null) {
            return (float)$this->precomputedMaxScore;
        }

        return 0.0;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'totalMarks' || $name === 'total_marks' || $name === 'total_max_score') {
            return $this->getTotalMaxScore();
        }
        if ($name === 'teacherName') {
            return $this->teacher?->user?->name ?? ($this->teacher?->name ?? ($this->classSubject?->teacherName ?? 'Subject Teacher'));
        }
        if ($name === 'subjectName') {
            return $this->classSubject?->subjectName ?? ($this->classSubject?->subject?->name ?? '');
        }
        return null;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(bool $includeCorrectness = true): array
    {
        return [
            'id' => $this->id,
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'assessment_category_id' => $this->assessmentCategoryId,
            'teacher_id' => $this->teacherId,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'time_limit_minutes' => $this->timeLimitMinutes,
            'max_attempts' => $this->maxAttempts,
            'is_published' => $this->isPublished,
            'published_at' => $this->publishedAt,
            'questions_count' => count($this->quizQuestions),
            'total_max_score' => $this->getTotalMaxScore(),
            'quiz_questions' => array_map(fn(QuizQuestion $qq) => $qq->toArray($includeCorrectness), $this->quizQuestions),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
