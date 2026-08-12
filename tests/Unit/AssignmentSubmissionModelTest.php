<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use PHPUnit\Framework\TestCase;

final class AssignmentSubmissionModelTest extends TestCase
{
    public function testHydrateSubmissionFromArray(): void
    {
        $data = [
            'id' => 1,
            'assignment_id' => 5,
            'student_id' => 20,
            'submitted_at' => '2026-08-12 14:30:00',
            'file_id' => 45,
            'text_response' => 'Here is my essay on the topic.',
            'score' => 88.5,
            'teacher_comment' => 'Well structured arguments.',
            'graded_at' => '2026-08-12 16:00:00',
            'graded_by' => 3,
            'created_at' => '2026-08-12 14:30:00',
            'updated_at' => '2026-08-12 16:00:00',
        ];

        $sub = AssignmentSubmission::fromArray($data);

        $this->assertSame(1, $sub->id);
        $this->assertSame(5, $sub->assignmentId);
        $this->assertSame(20, $sub->studentId);
        $this->assertSame('2026-08-12 14:30:00', $sub->submittedAt);
        $this->assertSame(45, $sub->fileId);
        $this->assertSame('Here is my essay on the topic.', $sub->textResponse);
        $this->assertSame(88.5, $sub->score);
        $this->assertSame('Well structured arguments.', $sub->teacherComment);
        $this->assertSame('2026-08-12 16:00:00', $sub->gradedAt);
        $this->assertSame(3, $sub->gradedBy);
        $this->assertTrue($sub->isGraded());
        $this->assertTrue($sub->hasAttachment());
        $this->assertTrue($sub->hasTextResponse());
    }

    public function testUngradedSubmission(): void
    {
        $sub = AssignmentSubmission::fromArray([
            'id' => 2,
            'assignment_id' => 5,
            'student_id' => 21,
            'submitted_at' => '2026-08-12 15:00:00',
            'file_id' => null,
            'text_response' => 'Draft submission',
            'score' => null,
            'teacher_comment' => null,
            'graded_at' => null,
            'graded_by' => null,
        ]);

        $this->assertFalse($sub->isGraded());
        $this->assertFalse($sub->hasAttachment());
        $this->assertTrue($sub->hasTextResponse());
    }

    public function testLatenessDetection(): void
    {
        $onTime = AssignmentSubmission::fromArray([
            'id' => 3,
            'assignment_id' => 1,
            'student_id' => 10,
            'submitted_at' => '2026-08-10 12:00:00',
        ]);

        $late = AssignmentSubmission::fromArray([
            'id' => 4,
            'assignment_id' => 1,
            'student_id' => 11,
            'submitted_at' => '2026-08-12 12:00:00',
        ]);

        $dueAt = '2026-08-11 23:59:59';

        $this->assertFalse($onTime->isLate($dueAt));
        $this->assertTrue($late->isLate($dueAt));

        // When assignment relation is attached
        $assignment = Assignment::fromArray([
            'id' => 1,
            'class_subject_id' => 1,
            'term_id' => 1,
            'teacher_id' => 1,
            'title' => 'Sample',
            'instructions' => 'Sample',
            'due_at' => '2026-08-11 23:59:59',
        ]);

        $lateWithRelation = AssignmentSubmission::fromArray([
            'id' => 5,
            'assignment_id' => 1,
            'student_id' => 12,
            'submitted_at' => '2026-08-12 12:00:00',
        ], assignment: $assignment);

        $this->assertTrue($lateWithRelation->isLate());
    }
}
