<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicSession;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use PHPUnit\Framework\TestCase;

final class ClassSubjectTest extends TestCase
{
    public function testClassSubjectInstantiationAndStatus(): void
    {
        $cs = new ClassSubject(
            id: 1,
            sessionId: 2,
            classId: 3,
            subjectId: 4,
            teacherId: 5,
            status: ClassSubject::STATUS_ACTIVE
        );

        $this->assertSame(1, $cs->id);
        $this->assertSame(2, $cs->sessionId);
        $this->assertSame(3, $cs->classId);
        $this->assertSame(4, $cs->subjectId);
        $this->assertSame(5, $cs->teacherId);
        $this->assertTrue($cs->isActive());

        $array = $cs->toArray();
        $this->assertSame(2, $array['session_id']);
        $this->assertSame(3, $array['class_id']);
        $this->assertSame(4, $array['subject_id']);
        $this->assertSame(5, $array['teacher_id']);
        $this->assertSame('active', $array['status']);
    }

    public function testClassSubjectFromArrayWithHydratedRelations(): void
    {
        $session = new AcademicSession(id: 2, name: '2026/2027', startDate: '2026-09-01', endDate: '2027-07-20');
        $class = new SchoolClass(id: 3, academicLevelId: 1, name: 'Grade 7A', sectionArm: 'A');
        $subject = new Subject(id: 4, name: 'Mathematics', code: 'MTH101');
        $teacher = new Teacher(id: 5, userId: 10, staffId: 'STF-10');

        $cs = ClassSubject::fromArray([
            'id' => 1,
            'session_id' => 2,
            'class_id' => 3,
            'subject_id' => 4,
            'teacher_id' => 5,
            'status' => 'active',
        ], $session, $class, $subject, $teacher);

        $this->assertSame(1, $cs->id);
        $this->assertSame('2026/2027', $cs->academicSession?->name);
        $this->assertSame('Grade 7A', $cs->schoolClass?->name);
        $this->assertSame('Mathematics', $cs->subject?->name);
        $this->assertSame('STF-10', $cs->teacher?->staffId);
    }
}
