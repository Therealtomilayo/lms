<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;
use App\Services\EnrollmentService;
use PHPUnit\Framework\TestCase;

final class EnrollmentServiceTest extends TestCase
{
    public function testEnrollStudentInClassSuccessfully(): void
    {
        $mockEnrollmentRepo = $this->createMock(EnrollmentRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockAcademicRepo = $this->createMock(AcademicRepository::class);

        $student = new Student(id: 1, userId: 10, admissionNumber: 'STD-001');
        $class = new SchoolClass(id: 2, academicLevelId: 1, name: 'JSS 1 Gold', status: 'active');
        $session = new AcademicSession(id: 3, name: '2026/2027', startDate: '2026-09-01', endDate: '2027-07-31', status: 'active');
        $enrollment = new ClassEnrollment(id: 100, studentId: 1, classId: 2, sessionId: 3, status: 'active');

        $mockStudentRepo->method('findById')->with(1)->willReturn($student);
        $mockAcademicRepo->method('findClassById')->with(2)->willReturn($class);
        $mockAcademicRepo->method('findSessionById')->with(3)->willReturn($session);
        $mockAcademicRepo->method('getClassSubjectsBySession')->willReturn([]);

        $mockEnrollmentRepo->method('enrollInClass')->with(1, 2, 3, 'active')->willReturn($enrollment);
        $mockStudentRepo->expects($this->once())->method('update')->with(1, null, null, null, 2);

        $service = new EnrollmentService($mockEnrollmentRepo, $mockStudentRepo, $mockAcademicRepo);
        $result = $service->enrollStudentInClass(1, 2, 3);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(100, $result->getData()->id);
    }

    public function testCannotEnrollInArchivedSession(): void
    {
        $mockEnrollmentRepo = $this->createMock(EnrollmentRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockAcademicRepo = $this->createMock(AcademicRepository::class);

        $student = new Student(id: 1, userId: 10, admissionNumber: 'STD-001');
        $class = new SchoolClass(id: 2, academicLevelId: 1, name: 'JSS 1 Gold', status: 'active');
        $session = new AcademicSession(id: 3, name: '2025/2026', startDate: '2025-09-01', endDate: '2026-07-31', status: 'archived');

        $mockStudentRepo->method('findById')->willReturn($student);
        $mockAcademicRepo->method('findClassById')->willReturn($class);
        $mockAcademicRepo->method('findSessionById')->willReturn($session);

        $service = new EnrollmentService($mockEnrollmentRepo, $mockStudentRepo, $mockAcademicRepo);

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('Cannot modify enrollments in an archived academic session.');
        $service->enrollStudentInClass(1, 2, 3);
    }

    public function testCannotEnrollInInactiveClass(): void
    {
        $mockEnrollmentRepo = $this->createMock(EnrollmentRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockAcademicRepo = $this->createMock(AcademicRepository::class);

        $student = new Student(id: 1, userId: 10, admissionNumber: 'STD-001');
        $class = new SchoolClass(id: 2, academicLevelId: 1, name: 'JSS 1 Gold', status: 'inactive');

        $mockStudentRepo->method('findById')->willReturn($student);
        $mockAcademicRepo->method('findClassById')->willReturn($class);

        $service = new EnrollmentService($mockEnrollmentRepo, $mockStudentRepo, $mockAcademicRepo);

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage("Cannot enroll student into an inactive class ('JSS 1 Gold').");
        $service->enrollStudentInClass(1, 2, 3);
    }
}
