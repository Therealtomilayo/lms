<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Assignment Submission Entity
 * Represents a student's submission response, score, and teacher feedback.
 */
final class AssignmentSubmission
{
    public function __construct(
        public readonly int $id,
        public readonly int $assignmentId,
        public readonly int $studentId,
        public readonly string $submittedAt,
        public readonly ?int $fileId = null,
        public readonly ?string $textResponse = null,
        public readonly ?float $score = null,
        public readonly ?string $teacherComment = null,
        public readonly ?string $gradedAt = null,
        public readonly ?int $gradedBy = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?Assignment $assignment = null,
        public readonly ?Student $student = null,
        public readonly ?FileRecord $file = null,
        public readonly ?Teacher $gradedByTeacher = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?Assignment $assignment = null,
        ?Student $student = null,
        ?FileRecord $file = null,
        ?Teacher $gradedByTeacher = null
    ): self {
        $file = $file ?? (!empty($data['file_uuid']) || !empty($data['file_storage_key']) || !empty($data['file_original_name']) ? FileRecord::fromArray([
            'id' => $data['file_id'],
            'uuid' => $data['file_uuid'] ?? '',
            'storage_key' => $data['file_storage_key'] ?? '',
            'original_name' => $data['file_original_name'] ?? 'submission',
            'mime_type' => $data['file_mime_type'] ?? 'application/octet-stream',
            'size_bytes' => $data['file_size_bytes'] ?? 0,
            'sha256' => $data['file_sha256'] ?? '',
            'uploaded_by' => $data['file_uploaded_by'] ?? 0,
            'owner_type' => $data['file_owner_type'] ?? 'assignment_submission',
            'owner_id' => $data['file_owner_id'] ?? (int)$data['id'],
            'created_at' => $data['file_created_at'] ?? null,
            'deleted_at' => $data['file_deleted_at'] ?? null,
        ]) : null);

        $student = $student ?? (!empty($data['student_admission_number']) || !empty($data['student_name']) ? Student::fromArray([
            'id' => (int)$data['student_id'],
            'user_id' => (int)($data['student_user_id'] ?? 0),
            'admission_number' => (string)($data['student_admission_number'] ?? ''),
            'date_of_birth' => (string)($data['student_dob'] ?? ''),
            'gender' => (string)($data['student_gender'] ?? ''),
            'current_class_id' => isset($data['student_current_class_id']) ? (int)$data['student_current_class_id'] : null,
            'name' => (string)($data['student_name'] ?? ''),
            'email' => (string)($data['student_email'] ?? ''),
            'class_name' => (string)($data['class_name'] ?? ''),
        ]) : null);

        $gradedByTeacher = $gradedByTeacher ?? (!empty($data['grader_name']) ? Teacher::fromArray([
            'id' => (int)$data['graded_by'],
            'user_id' => (int)($data['grader_user_id'] ?? 0),
            'staff_id' => (string)($data['grader_staff_id'] ?? ''),
            'user_name' => (string)$data['grader_name'],
            'user_email' => (string)($data['grader_email'] ?? ''),
        ]) : null);

        return new self(
            id: (int)$data['id'],
            assignmentId: (int)$data['assignment_id'],
            studentId: (int)$data['student_id'],
            submittedAt: (string)$data['submitted_at'],
            fileId: isset($data['file_id']) && $data['file_id'] !== '' && $data['file_id'] !== null ? (int)$data['file_id'] : null,
            textResponse: isset($data['text_response']) && trim((string)$data['text_response']) !== '' ? (string)$data['text_response'] : null,
            score: isset($data['score']) && $data['score'] !== null && $data['score'] !== '' ? (float)$data['score'] : null,
            teacherComment: isset($data['teacher_comment']) && trim((string)$data['teacher_comment']) !== '' ? (string)$data['teacher_comment'] : null,
            gradedAt: isset($data['graded_at']) ? (string)$data['graded_at'] : null,
            gradedBy: isset($data['graded_by']) && $data['graded_by'] !== '' && $data['graded_by'] !== null ? (int)$data['graded_by'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            assignment: $assignment,
            student: $student,
            file: $file,
            gradedByTeacher: $gradedByTeacher
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignmentId,
            'student_id' => $this->studentId,
            'submitted_at' => $this->submittedAt,
            'file_id' => $this->fileId,
            'text_response' => $this->textResponse,
            'score' => $this->score,
            'teacher_comment' => $this->teacherComment,
            'graded_at' => $this->gradedAt,
            'graded_by' => $this->gradedBy,
            'is_graded' => $this->isGraded(),
            'has_attachment' => $this->hasAttachment(),
            'has_text_response' => $this->hasTextResponse(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'file' => $this->file?->toArray(),
        ];
    }

    public function isGraded(): bool
    {
        return $this->gradedAt !== null && $this->score !== null;
    }

    public function isLate(?string $dueAt = null): bool
    {
        $targetDue = $dueAt ?? $this->assignment?->dueAt;
        if ($targetDue === null) {
            return false;
        }

        $submittedTime = strtotime($this->submittedAt);
        $dueTime = strtotime($targetDue);

        return $submittedTime !== false && $dueTime !== false && $submittedTime > $dueTime;
    }

    public function hasAttachment(): bool
    {
        return $this->fileId !== null && $this->fileId > 0;
    }

    public function hasTextResponse(): bool
    {
        return $this->textResponse !== null && trim($this->textResponse) !== '';
    }
}
