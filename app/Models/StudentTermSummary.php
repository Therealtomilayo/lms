<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain StudentTermSummary Entity (Overall class-level term performance summary)
 */
final class StudentTermSummary
{
    /**
     * @param array<int, TermResult> $subjectResults
     */
    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $termId,
        public readonly int $classId,
        public readonly ?float $totalScore = null,
        public readonly ?float $averageScore = null,
        public readonly ?float $gpa = null,
        public readonly ?int $rankInClass = null,
        public readonly int $attendancePresentCount = 0,
        public readonly int $attendanceTotalCount = 0,
        public readonly ?string $classTeacherRemark = null,
        public readonly ?string $principalRemark = null,
        public readonly string $promotionStatus = 'pending',
        public readonly bool $isLocked = false,
        public readonly ?string $lockedAt = null,
        public readonly ?int $lockedBy = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?Student $student = null,
        public readonly ?SchoolClass $class = null,
        public readonly array $subjectResults = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, TermResult> $subjectResults
     */
    public static function fromArray(
        array $data,
        ?Student $student = null,
        ?SchoolClass $class = null,
        array $subjectResults = []
    ): self {
        return new self(
            id: (int)($data['id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            classId: (int)($data['class_id'] ?? 0),
            totalScore: isset($data['total_score']) && $data['total_score'] !== '' && $data['total_score'] !== null
                ? (float)$data['total_score']
                : null,
            averageScore: isset($data['average_score']) && $data['average_score'] !== '' && $data['average_score'] !== null
                ? (float)$data['average_score']
                : null,
            gpa: isset($data['gpa']) && $data['gpa'] !== '' && $data['gpa'] !== null
                ? (float)$data['gpa']
                : null,
            rankInClass: isset($data['rank_in_class']) && $data['rank_in_class'] !== '' && $data['rank_in_class'] !== null
                ? (int)$data['rank_in_class']
                : null,
            attendancePresentCount: (int)($data['attendance_present_count'] ?? 0),
            attendanceTotalCount: (int)($data['attendance_total_count'] ?? 0),
            classTeacherRemark: isset($data['class_teacher_remark']) && $data['class_teacher_remark'] !== ''
                ? (string)$data['class_teacher_remark']
                : null,
            principalRemark: isset($data['principal_remark']) && $data['principal_remark'] !== ''
                ? (string)$data['principal_remark']
                : null,
            promotionStatus: (string)($data['promotion_status'] ?? 'pending'),
            isLocked: (bool)($data['is_locked'] ?? false),
            lockedAt: isset($data['locked_at']) ? (string)$data['locked_at'] : null,
            lockedBy: isset($data['locked_by']) && $data['locked_by'] !== '' && $data['locked_by'] !== null
                ? (int)$data['locked_by']
                : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            student: $student,
            class: $class,
            subjectResults: $subjectResults
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'term_id' => $this->termId,
            'class_id' => $this->classId,
            'total_score' => $this->totalScore,
            'average_score' => $this->averageScore,
            'gpa' => $this->gpa,
            'rank_in_class' => $this->rankInClass,
            'attendance_present_count' => $this->attendancePresentCount,
            'attendance_total_count' => $this->attendanceTotalCount,
            'class_teacher_remark' => $this->classTeacherRemark,
            'principal_remark' => $this->principalRemark,
            'promotion_status' => $this->promotionStatus,
            'is_locked' => $this->isLocked,
            'locked_at' => $this->lockedAt,
            'locked_by' => $this->lockedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
