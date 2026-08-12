<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\ImportBatch;
use App\Models\ParentProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\User;
use PHPUnit\Framework\TestCase;

final class StudentModelTest extends TestCase
{
    public function testStudentModelInstantiationAndMethods(): void
    {
        $user = new User(id: 10, uuid: 'uuid-1', name: 'John Doe', email: 'john@claret.edu', passwordHash: 'hash', roles: ['student']);
        $student = new Student(
            id: 5,
            userId: 10,
            admissionNumber: 'STD-2026-001',
            dateOfBirth: '2010-05-15',
            gender: 'male',
            currentClassId: 2,
            user: $user
        );

        $this->assertSame(5, $student->id);
        $this->assertSame('STD-2026-001', $student->admissionNumber);
        $this->assertSame('male', $student->gender);
        $this->assertSame(2, $student->currentClassId);
        $this->assertSame($user, $student->user);

        $array = $student->toArray();
        $this->assertSame('STD-2026-001', $array['admission_number']);
        $this->assertSame('John Doe', $array['user_name']);

        $fromArray = Student::fromArray([
            'id' => 6,
            'user_id' => 11,
            'admission_number' => 'STD-2026-002',
            'gender' => 'female',
            'user_name' => 'Jane Doe',
            'user_email' => 'jane@claret.edu',
        ]);
        $this->assertSame(6, $fromArray->id);
        $this->assertSame('Jane Doe', $fromArray->user?->name);
    }

    public function testParentProfileInstantiation(): void
    {
        $user = new User(id: 20, uuid: 'uuid-2', name: 'Parent One', email: 'parent@claret.edu', passwordHash: 'hash', roles: ['parent']);
        $parent = new ParentProfile(id: 3, userId: 20, user: $user);

        $this->assertSame(3, $parent->id);
        $this->assertSame(20, $parent->userId);
        $this->assertSame($user, $parent->user);

        $array = $parent->toArray();
        $this->assertSame(3, $array['id']);
        $this->assertSame('Parent One', $array['user_name']);
    }

    public function testClassEnrollmentInstantiation(): void
    {
        $student = new Student(id: 1, userId: 10, admissionNumber: 'STD-1');
        $class = new SchoolClass(id: 2, academicLevelId: 1, name: 'JSS 1');
        $session = new AcademicSession(id: 3, name: '2026/2027', startDate: '2026-09-01', endDate: '2027-07-31');

        $enrollment = new ClassEnrollment(
            id: 100,
            studentId: 1,
            classId: 2,
            sessionId: 3,
            status: 'active',
            student: $student,
            class: $class,
            session: $session
        );

        $this->assertTrue($enrollment->isActive());
        $this->assertSame('active', $enrollment->status);
        $this->assertSame('JSS 1', $enrollment->class?->name);

        $inactiveEnr = new ClassEnrollment(id: 101, studentId: 1, classId: 2, sessionId: 3, status: 'withdrawn');
        $this->assertFalse($inactiveEnr->isActive());
    }

    public function testStudentSubjectEnrollmentInstantiation(): void
    {
        $classSubject = new ClassSubject(id: 50, sessionId: 1, classId: 2, subjectId: 3, teacherId: 4);
        $sse = new StudentSubjectEnrollment(
            id: 200,
            studentId: 1,
            classSubjectId: 50,
            sessionId: 1,
            isElective: false,
            status: 'active',
            classSubject: $classSubject
        );

        $this->assertTrue($sse->isActive());
        $this->assertFalse($sse->isElective);
        $this->assertSame(50, $sse->classSubjectId);
    }

    public function testImportBatchInstantiation(): void
    {
        $batch = new ImportBatch(
            id: 1,
            uploadedBy: 2,
            type: 'students',
            originalName: 'students.csv',
            sha256: 'abc123hash',
            status: 'validated',
            totalRows: 10,
            validRows: 8,
            invalidRows: 2
        );

        $this->assertFalse($batch->isCommitted());
        $this->assertFalse($batch->isFailed());

        $committedBatch = new ImportBatch(
            id: 2,
            uploadedBy: 2,
            type: 'teachers',
            originalName: 'teachers.csv',
            sha256: 'xyz',
            status: 'committed'
        );
        $this->assertTrue($committedBatch->isCommitted());
    }
}
