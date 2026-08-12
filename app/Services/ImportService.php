<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\ImportBatch;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ImportRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use PDO;

/**
 * Application Service for CSV Bulk Onboarding of Students, Teachers, and Parents
 * Implements two-phase validate-then-commit architecture with row-level error reporting.
 */
class ImportService
{
    private ImportRepository $importRepository;
    private UserRepository $userRepository;
    private StudentRepository $studentRepository;
    private TeacherRepository $teacherRepository;
    private ParentRepository $parentRepository;
    private AcademicRepository $academicRepository;
    private EnrollmentRepository $enrollmentRepository;
    private PDO $pdo;

    public function __construct(
        ?ImportRepository $importRepository = null,
        ?UserRepository $userRepository = null,
        ?StudentRepository $studentRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?ParentRepository $parentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?PDO $pdo = null
    ) {
        $this->importRepository = $importRepository ?? new ImportRepository();
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Validate CSV content and create an ImportBatch preview.
     */
    public function validateCsv(string $csvContent, string $type, string $originalName, int $uploadedBy): ServiceResult
    {
        if (!in_array($type, ['students', 'teachers', 'parents'], true)) {
            throw new ValidationException(['type' => ['Invalid import type.']]);
        }

        $lines = array_filter(array_map('trim', explode("\n", $csvContent)));
        if (count($lines) < 2) {
            throw new ValidationException(['file' => ['The CSV file is empty or missing data rows.']]);
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $sha256 = hash('sha256', $csvContent);

        $validRows = [];
        $errors = [];
        $seenEmails = [];
        $seenIdentifiers = [];

        $rowNumber = 1; // Header was row 1, data starts at row 2
        foreach ($lines as $line) {
            $rowNumber++;
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line);
            if (count($cols) < count($header)) {
                $cols = array_pad($cols, count($header), '');
            }

            $row = array_combine($header, array_slice($cols, 0, count($header)));
            $rowErrors = $this->validateRow($row, $type, $seenEmails, $seenIdentifiers);

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'raw_data' => $row,
                    'errors' => $rowErrors,
                ];
            } else {
                $validRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $row,
                ];
            }
        }

        $totalRows = count($validRows) + count($errors);
        $status = count($errors) > 0 && empty($validRows) ? ImportBatch::STATUS_FAILED : ImportBatch::STATUS_VALIDATED;

        $batch = $this->importRepository->create(
            uploadedBy: $uploadedBy,
            type: $type,
            originalName: $originalName,
            sha256: $sha256,
            totalRows: $totalRows,
            validRows: count($validRows),
            invalidRows: count($errors),
            status: $status
        );

        foreach ($errors as $err) {
            $this->importRepository->addError($batch->id, $err['row_number'], $err['raw_data'], $err['errors']);
        }

        return ServiceResult::success([
            'batch' => $this->importRepository->findById($batch->id),
            'valid_rows' => $validRows,
            'errors' => $errors,
        ]);
    }

    /**
     * Transactionally commit an import batch.
     */
    public function commitImport(int $importId, array $validRows, UserContext $actor): ServiceResult
    {
        $batch = $this->importRepository->findById($importId);
        if (!$batch) {
            throw new ResourceNotFoundException("Import batch #{$importId} not found.");
        }

        if ($batch->isCommitted()) {
            throw new DomainRuleException("This import batch has already been committed.");
        }

        if (empty($validRows)) {
            throw new DomainRuleException("No valid rows to commit.");
        }

        $this->pdo->beginTransaction();

        try {
            $createdCount = 0;
            $defaultPassword = 'Password123!';
            $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            foreach ($validRows as $item) {
                $row = $item['data'] ?? $item;
                $email = strtolower(trim($row['email']));
                $name = trim($row['name']);
                $phone = !empty($row['phone']) ? trim($row['phone']) : null;

                // Check if user already exists
                $user = $this->userRepository->findByEmail($email);
                if (!$user) {
                    $uuid = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff)
                    );

                    $user = $this->userRepository->create([
                        'uuid' => $uuid,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => $passwordHash,
                        'status' => 'active',
                        'must_change_password' => 1,
                    ], [$batch->type === 'students' ? 'student' : ($batch->type === 'teachers' ? 'teacher' : 'parent')]);
                }

                if ($batch->type === 'students') {
                    $admNo = trim($row['admission_number']);
                    $dob = !empty($row['date_of_birth']) ? trim($row['date_of_birth']) : null;
                    $gender = !empty($row['gender']) ? strtolower(trim($row['gender'])) : null;
                    $classId = null;

                    if (!empty($row['class_name'])) {
                        $classes = $this->academicRepository->getAllClasses();
                        foreach ($classes as $c) {
                            if (strcasecmp($c->name, trim($row['class_name'])) === 0) {
                                $classId = $c->id;
                                break;
                            }
                        }
                    }

                    $this->studentRepository->create(
                        userId: $user->id,
                        admissionNumber: $admNo,
                        dateOfBirth: $dob,
                        gender: $gender,
                        currentClassId: $classId
                    );
                } elseif ($batch->type === 'teachers') {
                    $staffId = trim($row['staff_id']);
                    $this->teacherRepository->createTeacher($user->id, $staffId);
                } elseif ($batch->type === 'parents') {
                    $parent = $this->parentRepository->create($user->id);
                    if (!empty($row['student_admission_number'])) {
                        $student = $this->studentRepository->findByAdmissionNumber(trim($row['student_admission_number']));
                        if ($student) {
                            $this->parentRepository->linkStudent($parent->id, $student->id, $row['relationship'] ?? 'Guardian');
                        }
                    }
                }

                $createdCount++;
            }

            $this->importRepository->markCommitted($importId);
            $this->pdo->commit();

            return ServiceResult::success([
                'committed_count' => $createdCount,
                'batch_id' => $importId,
            ]);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            $this->importRepository->markFailed($importId);
            throw new DomainRuleException("Import commit failed: " . $e->getMessage());
        }
    }

    private function validateRow(array $row, string $type, array &$seenEmails, array &$seenIdentifiers): array
    {
        $errors = [];

        $email = strtolower(trim($row['email'] ?? ''));
        $name = trim($row['name'] ?? '');

        if ($name === '') {
            $errors[] = 'Name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif (isset($seenEmails[$email])) {
            $errors[] = "Duplicate email '{$email}' in import file.";
        } elseif ($this->userRepository->findByEmail($email) !== null) {
            $errors[] = "User with email '{$email}' already exists.";
        } else {
            $seenEmails[$email] = true;
        }

        if ($type === 'students') {
            $admNo = trim($row['admission_number'] ?? '');
            if ($admNo === '') {
                $errors[] = 'Admission number is required for students.';
            } elseif (isset($seenIdentifiers[$admNo])) {
                $errors[] = "Duplicate admission number '{$admNo}' in import file.";
            } elseif ($this->studentRepository->findByAdmissionNumber($admNo) !== null) {
                $errors[] = "Student with admission number '{$admNo}' already exists.";
            } else {
                $seenIdentifiers[$admNo] = true;
            }
        } elseif ($type === 'teachers') {
            $staffId = trim($row['staff_id'] ?? '');
            if ($staffId === '') {
                $errors[] = 'Staff ID is required for teachers.';
            } elseif (isset($seenIdentifiers[$staffId])) {
                $errors[] = "Duplicate staff ID '{$staffId}' in import file.";
            } elseif ($this->teacherRepository->findTeacherByStaffId($staffId) !== null) {
                $errors[] = "Teacher with staff ID '{$staffId}' already exists.";
            } else {
                $seenIdentifiers[$staffId] = true;
            }
        }

        return $errors;
    }
}
