<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ImportBatch;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ImportRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\ImportService;
use PHPUnit\Framework\TestCase;

final class ImportServiceTest extends TestCase
{
    public function testValidateCsvCategorizesValidAndInvalidRows(): void
    {
        $mockImportRepo = $this->createMock(ImportRepository::class);
        $mockUserRepo = $this->createMock(UserRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockTeacherRepo = $this->createMock(TeacherRepository::class);
        $mockParentRepo = $this->createMock(ParentRepository::class);
        $mockAcademicRepo = $this->createMock(AcademicRepository::class);
        $mockEnrollmentRepo = $this->createMock(EnrollmentRepository::class);

        $csv = "name,email,admission_number\n" .
               "Alice Smith,alice@claret.edu,STD-001\n" .
               "Bad Email,invalid-email,STD-002\n" .
               "Missing Adm,valid@claret.edu,\n";

        $batch = new ImportBatch(
            id: 1,
            uploadedBy: 1,
            type: 'students',
            originalName: 'test.csv',
            sha256: 'hash',
            status: 'validated',
            totalRows: 3,
            validRows: 1,
            invalidRows: 2
        );

        $mockImportRepo->method('create')->willReturn($batch);
        $mockImportRepo->method('findById')->with(1)->willReturn($batch);

        $service = new ImportService(
            $mockImportRepo,
            $mockUserRepo,
            $mockStudentRepo,
            $mockTeacherRepo,
            $mockParentRepo,
            $mockAcademicRepo,
            $mockEnrollmentRepo
        );

        $result = $service->validateCsv($csv, 'students', 'test.csv', 1);

        $this->assertTrue($result->isSuccess());
        $data = $result->getData();

        $this->assertCount(1, $data['valid_rows']);
        $this->assertCount(2, $data['errors']);
        $this->assertSame('alice@claret.edu', $data['valid_rows'][0]['data']['email']);
    }
}
