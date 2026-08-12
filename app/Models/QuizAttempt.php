<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeZone;

/**
 * QuizAttempt Domain Model
 * Represents a student's timed assessment attempt session.
 */
final class QuizAttempt
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_GRADED = 'graded';

    /**
     * @param array<int, QuizAnswer> $answers
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $quizId,
        public readonly int $studentId,
        public readonly int $attemptNumber = 1,
        public readonly string $startedAt = '',
        public readonly ?string $submittedAt = null,
        public readonly ?float $score = null,
        public readonly float $maxScore = 0.00,
        public readonly string $status = self::STATUS_IN_PROGRESS,
        public readonly ?Quiz $quiz = null,
        public readonly ?Student $student = null,
        public readonly array $answers = [],
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * Hydrate QuizAttempt model from database associative array.
     *
     * @param array<string, mixed> $data
     * @param array<int, QuizAnswer> $answers
     */
    public static function fromArray(
        array $data,
        ?Quiz $quiz = null,
        ?Student $student = null,
        array $answers = []
    ): self {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            uuid: (string)($data['uuid'] ?? ''),
            quizId: (int)($data['quiz_id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            attemptNumber: (int)($data['attempt_number'] ?? 1),
            startedAt: (string)($data['started_at'] ?? ''),
            submittedAt: isset($data['submitted_at']) ? (string)$data['submitted_at'] : null,
            score: isset($data['score']) ? (float)$data['score'] : null,
            maxScore: isset($data['max_score']) ? (float)$data['max_score'] : 0.00,
            status: (string)($data['status'] ?? self::STATUS_IN_PROGRESS),
            quiz: $quiz,
            student: $student,
            answers: $answers,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED || $this->status === self::STATUS_GRADED;
    }

    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    /**
     * Calculate whether the server-side timer has expired for this attempt.
     * Includes an optional network latency grace buffer (default 30 seconds).
     */
    public function hasExpired(int $timeLimitMinutes, int $graceSeconds = 30, ?string $currentTime = null): bool
    {
        if ($timeLimitMinutes <= 0) {
            return false;
        }

        $startedTimestamp = strtotime($this->startedAt);
        if ($startedTimestamp === false) {
            return false;
        }

        $nowTimestamp = $currentTime ? strtotime($currentTime) : time();
        $allowedDurationSeconds = ($timeLimitMinutes * 60) + $graceSeconds;

        return ($nowTimestamp - $startedTimestamp) > $allowedDurationSeconds;
    }

    /**
     * Get remaining time in seconds (returns 0 if expired, or negative if overdue).
     */
    public function getRemainingSeconds(int $timeLimitMinutes, ?string $currentTime = null): int
    {
        if ($timeLimitMinutes <= 0) {
            return PHP_INT_MAX;
        }

        $startedTimestamp = strtotime($this->startedAt);
        if ($startedTimestamp === false) {
            return 0;
        }

        $nowTimestamp = $currentTime ? strtotime($currentTime) : time();
        $allowedDurationSeconds = ($timeLimitMinutes * 60);
        $elapsedSeconds = $nowTimestamp - $startedTimestamp;

        $remaining = $allowedDurationSeconds - $elapsedSeconds;
        return max(0, $remaining);
    }

    /**
     * Get target expiration ISO/SQL datetime.
     */
    public function getExpiresAt(int $timeLimitMinutes): ?string
    {
        if ($timeLimitMinutes <= 0) {
            return null;
        }

        $startedTimestamp = strtotime($this->startedAt);
        if ($startedTimestamp === false) {
            return null;
        }

        $expiresTimestamp = $startedTimestamp + ($timeLimitMinutes * 60);
        return date('Y-m-d H:i:s', $expiresTimestamp);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'quiz_id' => $this->quizId,
            'student_id' => $this->studentId,
            'attempt_number' => $this->attemptNumber,
            'started_at' => $this->startedAt,
            'submitted_at' => $this->submittedAt,
            'score' => $this->score,
            'max_score' => $this->maxScore,
            'status' => $this->status,
            'answers_count' => count($this->answers),
            'answers' => array_map(fn(QuizAnswer $a) => $a->toArray(), $this->answers),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
