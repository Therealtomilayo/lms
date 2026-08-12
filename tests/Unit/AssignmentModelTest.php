<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Assignment;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AssignmentModelTest extends TestCase
{
    public function testHydrateAssignmentFromArray(): void
    {
        $data = [
            'id' => 1,
            'class_subject_id' => 10,
            'term_id' => 2,
            'assessment_category_id' => null,
            'teacher_id' => 5,
            'topic' => 'Organic Chemistry',
            'title' => 'Hydrocarbons Nomenclature Assignment',
            'instructions' => 'Complete questions 1 to 15 from chapter 4.',
            'due_at' => '2026-09-01 23:59:59',
            'max_score' => 50.00,
            'file_id' => 18,
            'status' => Assignment::STATUS_PUBLISHED,
            'created_at' => '2026-08-12 10:00:00',
            'updated_at' => '2026-08-12 10:00:00',
        ];

        $assignment = Assignment::fromArray($data);

        $this->assertSame(1, $assignment->id);
        $this->assertSame(10, $assignment->classSubjectId);
        $this->assertSame(2, $assignment->termId);
        $this->assertNull($assignment->assessmentCategoryId);
        $this->assertSame(5, $assignment->teacherId);
        $this->assertSame('Organic Chemistry', $assignment->topic);
        $this->assertSame('Hydrocarbons Nomenclature Assignment', $assignment->title);
        $this->assertSame('Complete questions 1 to 15 from chapter 4.', $assignment->instructions);
        $this->assertSame('2026-09-01 23:59:59', $assignment->dueAt);
        $this->assertSame(50.0, $assignment->maxScore);
        $this->assertSame(18, $assignment->fileId);
        $this->assertSame(Assignment::STATUS_PUBLISHED, $assignment->status);
        $this->assertTrue($assignment->isPublished());
        $this->assertFalse($assignment->isDraft());
        $this->assertFalse($assignment->isArchived());
        $this->assertTrue($assignment->hasFile());
    }

    public function testPastDueCalculation(): void
    {
        $pastAssignment = Assignment::fromArray([
            'id' => 2,
            'class_subject_id' => 10,
            'term_id' => 2,
            'teacher_id' => 5,
            'title' => 'Past Due Task',
            'instructions' => 'Do it now.',
            'due_at' => '2026-08-01 12:00:00',
            'max_score' => 100.00,
        ]);

        $futureAssignment = Assignment::fromArray([
            'id' => 3,
            'class_subject_id' => 10,
            'term_id' => 2,
            'teacher_id' => 5,
            'title' => 'Future Task',
            'instructions' => 'Do it later.',
            'due_at' => '2026-09-30 12:00:00',
            'max_score' => 100.00,
        ]);

        $referenceTime = new DateTimeImmutable('2026-08-15 12:00:00');

        $this->assertTrue($pastAssignment->isPastDue($referenceTime));
        $this->assertFalse($futureAssignment->isPastDue($referenceTime));
    }

    public function testStatusHelpers(): void
    {
        $draft = Assignment::fromArray([
            'id' => 4,
            'class_subject_id' => 1,
            'term_id' => 1,
            'teacher_id' => 1,
            'title' => 'Draft Assignment',
            'instructions' => 'WIP',
            'due_at' => '2026-10-01 00:00:00',
            'status' => Assignment::STATUS_DRAFT,
        ]);

        $archived = Assignment::fromArray([
            'id' => 5,
            'class_subject_id' => 1,
            'term_id' => 1,
            'teacher_id' => 1,
            'title' => 'Archived Assignment',
            'instructions' => 'Done',
            'due_at' => '2026-01-01 00:00:00',
            'status' => Assignment::STATUS_ARCHIVED,
        ]);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isPublished());

        $this->assertTrue($archived->isArchived());
        $this->assertFalse($archived->isPublished());
    }
}
