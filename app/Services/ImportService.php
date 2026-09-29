<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\AcademicSession;
use App\Models\ImportBatch;
use App\Models\SchoolClass;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ImportRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Utils\ClassResolver;
use PDO;

/**
 * Application Service for Bulk Onboarding of Students, Teachers, and Parents
 * Implements chunked, idempotent processing with conservative class resolution,
 * decoupled parent contact deduplication, and temporary credential tracking.
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
    private EnrollmentService $enrollmentService;
    private ClassResolver $classResolver;
    private PDO $pdo;

    public function __construct(
        ?ImportRepository $importRepository = null,
        ?UserRepository $userRepository = null,
        ?StudentRepository $studentRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?ParentRepository $parentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?PDO $pdo = null,
        ?EnrollmentService $enrollmentService = null,
        ?ClassResolver $classResolver = null
    ) {
        $this->importRepository = $importRepository ?? new ImportRepository();
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->enrollmentService = $enrollmentService ?? new EnrollmentService(
            $this->enrollmentRepository,
            $this->studentRepository,
            $this->academicRepository
        );
        $this->classResolver = $classResolver ?? new ClassResolver(
            $this->academicRepository,
            $this->importRepository
        );
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Initialize a new Import Batch session prior to chunk processing.
     */
    public function initBatch(
        int $uploadedBy,
        string $type,
        string $fileName,
        string $sha256,
        ?int $sessionId,
        int $totalRows
    ): ServiceResult {
        if (!in_array($type, ['students', 'teachers', 'parents'], true)) {
            throw new ValidationException(['type' => ['Invalid import type specified.']]);
        }

        if ($type === 'students') {
            if (!$sessionId || $sessionId <= 0) {
                throw new ValidationException(['session_id' => ['An academic session is required for student onboarding.']]);
            }
            $session = $this->academicRepository->findSessionById($sessionId);
            if (!$session) {
                throw new ResourceNotFoundException("Academic session #{$sessionId} does not exist.");
            }
            if ($session->status === AcademicSession::STATUS_ARCHIVED) {
                throw new DomainRuleException("Cannot onboard students into an archived academic session.");
            }
        }

        $batch = $this->importRepository->create(
            uploadedBy: $uploadedBy,
            type: $type,
            originalName: $fileName,
            sha256: $sha256,
            totalRows: $totalRows,
            validRows: 0,
            invalidRows: 0,
            status: ImportBatch::STATUS_UPLOADED,
            sessionId: $sessionId
        );

        return ServiceResult::success([
            'batch' => $batch,
            'import_id' => $batch->id,
        ]);
    }

    /**
     * Resolve a list of raw class strings extracted by the client SheetJS parser.
     */
    public function resolveClasses(array $classStrings, ?int $sessionId = null): ServiceResult
    {
        $uniqueRaw = array_values(array_unique(array_filter(array_map('trim', $classStrings))));
        $resolution = $this->classResolver->resolveMultiple($uniqueRaw);

        $availableClasses = array_map(function (SchoolClass $c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'section_arm' => $c->sectionArm,
                'display' => $c->name . ($c->sectionArm ? " ({$c->sectionArm})" : ''),
                'status' => $c->status,
            ];
        }, $this->academicRepository->getAllClasses());

        return ServiceResult::success([
            'matched' => $resolution['matched'],
            'unmatched' => $resolution['unmatched'],
            'ambiguous' => $resolution['ambiguous'],
            'available_classes' => $availableClasses,
        ]);
    }

    /**
     * Save human-confirmed class mappings.
     *
     * @param array<string, int> $mappings Key: raw class string, Value: canonical class ID
     */
    public function saveClassMappings(array $mappings, int $userId): ServiceResult
    {
        $savedCount = 0;
        foreach ($mappings as $raw => $canonicalClassId) {
            $raw = trim((string)$raw);
            $canonicalClassId = (int)$canonicalClassId;

            if ($raw === '' || $canonicalClassId <= 0) {
                continue;
            }

            $normalized = $this->classResolver->normalizeClassString($raw);
            $this->importRepository->saveClassMapping($raw, $normalized, $canonicalClassId, $userId);
            $savedCount++;
        }

        return ServiceResult::success([
            'saved_count' => $savedCount,
        ]);
    }

    /**
     * Process an isolated chunk of import rows with idempotency and authoritative server validation.
     *
     * @param array<int, array{row_number: int, data: array<string, mixed>}> $rows
     */
    public function processChunk(
        int $importId,
        int $chunkNumber,
        int $totalChunks,
        array $rows,
        UserContext $actor
    ): ServiceResult {
        $batch = $this->importRepository->findById($importId);
        if (!$batch) {
            throw new ResourceNotFoundException("Import batch #{$importId} not found.");
        }

        if ($batch->isCommitted()) {
            throw new DomainRuleException("This import batch has already been finalized/committed.");
        }

        // Chunk Idempotency Check: if already recorded as processed, return cached success
        if ($this->importRepository->isChunkProcessed($importId, $chunkNumber)) {
            return ServiceResult::success([
                'already_processed' => true,
                'chunk_number' => $chunkNumber,
                'total_chunks' => $totalChunks,
                'valid_count' => 0,
                'invalid_count' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'errors' => [],
                'credentials' => [],
            ]);
        }

        $this->pdo->beginTransaction();

        try {
            $validCount = 0;
            $invalidCount = 0;
            $createdCount = 0;
            $updatedCount = 0;
            $chunkErrors = [];
            $credentials = [];

            // Preload classes for matching
            $allClasses = $this->academicRepository->getAllClasses();
            $classesById = [];
            foreach ($allClasses as $c) {
                $classesById[$c->id] = $c;
            }

            foreach ($rows as $item) {
                $rowNumber = (int)($item['row_number'] ?? 0);
                $row = (array)($item['data'] ?? $item);

                $rowErrors = [];

                if ($batch->type === 'students') {
                    $this->processStudentRow(
                        $batch,
                        $rowNumber,
                        $row,
                        $classesById,
                        $rowErrors,
                        $createdCount,
                        $updatedCount,
                        $validCount,
                        $invalidCount,
                        $chunkErrors,
                        $credentials
                    );
                } elseif ($batch->type === 'teachers') {
                    $this->processTeacherRow(
                        $batch,
                        $rowNumber,
                        $row,
                        $rowErrors,
                        $createdCount,
                        $updatedCount,
                        $validCount,
                        $invalidCount,
                        $chunkErrors,
                        $credentials
                    );
                } elseif ($batch->type === 'parents') {
                    $this->processParentRow(
                        $batch,
                        $rowNumber,
                        $row,
                        $rowErrors,
                        $createdCount,
                        $updatedCount,
                        $validCount,
                        $invalidCount,
                        $chunkErrors
                    );
                }
            }

            // Record chunk completion into imports table
            $this->importRepository->recordChunkProcessed($importId, $chunkNumber, $validCount, $invalidCount);

            $this->pdo->commit();

            return ServiceResult::success([
                'already_processed' => false,
                'chunk_number' => $chunkNumber,
                'total_chunks' => $totalChunks,
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'errors' => $chunkErrors,
                'credentials' => $credentials,
            ]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new DomainRuleException("Failed processing chunk #{$chunkNumber}: " . $e->getMessage());
        }
    }

    /**
     * Authoritatively validate and process a single student row.
     */
    private function processStudentRow(
        ImportBatch $batch,
        int $rowNumber,
        array $row,
        array $classesById,
        array &$rowErrors,
        int &$createdCount,
        int &$updatedCount,
        int &$validCount,
        int &$invalidCount,
        array &$chunkErrors,
        array &$credentials
    ): void {
        // 1. Authoritative Student Identity Validation
        $firstName = trim((string)($row['first_name'] ?? ''));
        $lastName = trim((string)($row['last_name'] ?? ''));
        $otherNames = trim((string)($row['other_names'] ?? $row['middle_name'] ?? ''));
        $rawName = trim((string)($row['name'] ?? ''));

        if ($firstName === '' && $lastName === '' && $rawName === '') {
            $rowErrors[] = 'Student name is required.';
        }

        $fullName = $rawName !== '' ? $rawName : trim("{$firstName} {$otherNames} {$lastName}");
        $fullName = (string)preg_replace('/\s+/', ' ', $fullName);

        $admNo = trim((string)($row['admission_number'] ?? ''));
        if ($admNo === '') {
            $rowErrors[] = 'Admission number is required.';
        }

        $rawClass = trim((string)($row['class_name'] ?? $row['class'] ?? ''));
        if ($rawClass === '' && $batch->sessionId !== null) {
            $rowErrors[] = 'Class name is required for academic session enrollment.';
        }

        $gender = null;
        if (!empty($row['gender'])) {
            $g = strtolower(trim((string)$row['gender']));
            if (in_array($g, ['m', 'male'], true)) {
                $gender = 'Male';
            } elseif (in_array($g, ['f', 'female'], true)) {
                $gender = 'Female';
            } else {
                $gender = ucfirst($g);
            }
        }

        $dob = null;
        if (!empty($row['date_of_birth'])) {
            $dob = $this->normalizeDate((string)$row['date_of_birth']);
            if (!$dob) {
                $rowErrors[] = "Invalid date of birth format '{$row['date_of_birth']}' (expected YYYY-MM-DD or DD/MM/YYYY).";
            }
        }

        $studentEmail = !empty($row['email']) ? strtolower(trim((string)$row['email'])) : null;
        if ($studentEmail !== null && !filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
            $rowErrors[] = "Invalid student email format '{$studentEmail}'.";
        }

        // Parent fields
        $parentName = trim((string)($row['parent_name'] ?? ''));
        if ($parentName === '' && (!empty($row['parent_first_name']) || !empty($row['parent_last_name']))) {
            $parentName = trim((string)($row['parent_first_name'] ?? '') . ' ' . (string)($row['parent_last_name'] ?? ''));
        }
        $parentPhone = !empty($row['parent_phone']) ? trim((string)$row['parent_phone']) : (!empty($row['parent_contact']) ? trim((string)$row['parent_contact']) : null);
        $parentEmail = !empty($row['parent_email']) ? strtolower(trim((string)$row['parent_email'])) : null;
        $parentRelationship = !empty($row['parent_relationship']) ? trim((string)$row['parent_relationship']) : (!empty($row['relationship']) ? trim((string)$row['relationship']) : 'Guardian');

        if ($parentEmail !== null && !filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            $rowErrors[] = "Invalid parent email format '{$parentEmail}'.";
        }

        // Resolve Class
        $canonicalClassId = null;
        if ($rawClass !== '') {
            $resolved = $this->classResolver->resolve($rawClass);
            if ($resolved['class_id'] !== null && in_array($resolved['status'], [ClassResolver::STATUS_AUTO_RESOLVED, ClassResolver::STATUS_PREVIOUSLY_MAPPED], true)) {
                $canonicalClassId = (int)$resolved['class_id'];
            } elseif ($resolved['status'] === ClassResolver::STATUS_AMBIGUOUS) {
                $rowErrors[] = "Class '{$rawClass}' is ambiguous ({$resolved['message']}). Please map it before importing.";
            } else {
                $rowErrors[] = "Class '{$rawClass}' could not be matched to an active class.";
            }
        }

        // If validation errors exist, record and abort row
        if (!empty($rowErrors)) {
            $invalidCount++;
            $chunkErrors[] = [
                'row_number' => $rowNumber,
                'raw_data' => $row,
                'errors' => $rowErrors,
            ];
            $this->importRepository->addError($batch->id, $rowNumber, $row, $rowErrors);
            return;
        }

        // Demographic fields
        $stateOfOrigin = !empty($row['state_of_origin']) ? trim((string)$row['state_of_origin']) : null;
        $lga = !empty($row['lga']) ? trim((string)$row['lga']) : null;
        $religion = !empty($row['religion']) ? trim((string)$row['religion']) : null;
        $nationality = !empty($row['nationality']) ? trim((string)$row['nationality']) : 'Nigerian';
        $admissionDate = !empty($row['admission_date']) ? $this->normalizeDate((string)$row['admission_date']) : null;

        // 2. Reconcile Student Profile
        $student = $this->studentRepository->findByAdmissionNumber($admNo);
        $sessionId = $batch->sessionId;

        if ($student) {
            // Existing student: reconcile demographics and check historical session enrollments
            if ($sessionId) {
                $existingEnrollment = $this->enrollmentRepository->findClassEnrollment($student->id, $sessionId);
                if ($existingEnrollment) {
                    if ($existingEnrollment->classId !== $canonicalClassId) {
                        $existingClassName = $classesById[$existingEnrollment->classId]->getFullName() ?? ($existingEnrollment->class?->getFullName() ?? "ID #{$existingEnrollment->classId}");
                        $err = ["Student is already enrolled in class '{$existingClassName}' for this session. Automatic re-enrollment prohibited."];
                        $invalidCount++;
                        $chunkErrors[] = ['row_number' => $rowNumber, 'raw_data' => $row, 'errors' => $err];
                        $this->importRepository->addError($batch->id, $rowNumber, $row, $err);
                        return;
                    }
                    // Same class: idempotent
                } else {
                    // Enroll into class and auto-enroll subjects
                    $this->enrollmentService->enrollStudentInClass($student->id, $canonicalClassId, $sessionId, 'active', true);
                }
            }

            // Update student profile
            $this->studentRepository->update(
                studentId: $student->id,
                dateOfBirth: $dob,
                gender: $gender,
                currentClassId: $canonicalClassId,
                stateOfOrigin: $stateOfOrigin,
                lga: $lga,
                nationality: $nationality,
                religion: $religion,
                admissionDate: $admissionDate
            );

            $updatedCount++;
            $validCount++;
        } else {
            // New Student: create user, student, and enrollments
            if (!$studentEmail) {
                $cleanAdm = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $admNo));
                $studentEmail = "{$cleanAdm}.student@claret.edu";
            }

            // Check if generated email clashes with existing user
            $existingUser = $this->userRepository->findByEmail($studentEmail);
            if ($existingUser) {
                $studentEmail = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $admNo)) . '.' . substr(bin2hex(random_bytes(2)), 0, 3) . '.student@claret.edu';
            }

            $tempPassword = $this->generateTempPassword();
            $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            $user = $this->userRepository->create([
                'uuid' => $this->generateUuid(),
                'name' => $fullName,
                'email' => $studentEmail,
                'phone' => !empty($row['phone']) ? trim((string)$row['phone']) : null,
                'password_hash' => $passwordHash,
                'status' => 'active',
                'must_change_password' => 1,
            ], ['student']);

            $student = $this->studentRepository->create(
                userId: $user->id,
                admissionNumber: $admNo,
                dateOfBirth: $dob,
                gender: $gender,
                currentClassId: $canonicalClassId,
                stateOfOrigin: $stateOfOrigin,
                lga: $lga,
                nationality: $nationality,
                religion: $religion,
                admissionDate: $admissionDate
            );

            if ($sessionId && $canonicalClassId) {
                $this->enrollmentService->enrollStudentInClass($student->id, $canonicalClassId, $sessionId, 'active', true);
            }

            $credentials[] = [
                'identifier' => $admNo,
                'name' => $fullName,
                'email' => $studentEmail,
                'temp_password' => $tempPassword,
                'role' => 'student',
                'class' => $classesById[$canonicalClassId]->name ?? $rawClass,
            ];

            $createdCount++;
            $validCount++;
        }

        // 3. Reconcile Parent / Guardian Contact
        if ($parentName !== '' || $parentPhone !== null || $parentEmail !== null) {
            $this->reconcileParentForStudent($student->id, $fullName, $parentName, $parentPhone, $parentEmail, $parentRelationship);
        }
    }

    /**
     * Authoritatively validate and process a teacher row.
     */
    private function processTeacherRow(
        ImportBatch $batch,
        int $rowNumber,
        array $row,
        array &$rowErrors,
        int &$createdCount,
        int &$updatedCount,
        int &$validCount,
        int &$invalidCount,
        array &$chunkErrors,
        array &$credentials
    ): void {
        $staffId = trim((string)($row['staff_id'] ?? ''));
        $name = trim((string)($row['name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))));
        $email = strtolower(trim((string)($row['email'] ?? '')));
        $phone = !empty($row['phone']) ? trim((string)$row['phone']) : null;

        if ($staffId === '') {
            $rowErrors[] = 'Staff ID is required.';
        }
        if ($name === '') {
            $rowErrors[] = 'Teacher name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $rowErrors[] = 'A valid email address is required.';
        }

        if (!empty($rowErrors)) {
            $invalidCount++;
            $chunkErrors[] = [
                'row_number' => $rowNumber,
                'raw_data' => $row,
                'errors' => $rowErrors,
            ];
            $this->importRepository->addError($batch->id, $rowNumber, $row, $rowErrors);
            return;
        }

        $existingTeacher = $this->teacherRepository->findTeacherByStaffId($staffId);
        if ($existingTeacher) {
            // Already exists: update user details if needed
            $updatedCount++;
            $validCount++;
            return;
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            // Link existing user to teacher role
            $this->teacherRepository->createTeacher($existingUser->id, $staffId);
            $updatedCount++;
            $validCount++;
            return;
        }

        $tempPassword = $this->generateTempPassword();
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $user = $this->userRepository->create([
            'uuid' => $this->generateUuid(),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => $passwordHash,
            'status' => 'active',
            'must_change_password' => 1,
        ], ['teacher']);

        $this->teacherRepository->createTeacher($user->id, $staffId);

        $credentials[] = [
            'identifier' => $staffId,
            'name' => $name,
            'email' => $email,
            'temp_password' => $tempPassword,
            'role' => 'teacher',
            'class' => 'Staff',
        ];

        $createdCount++;
        $validCount++;
    }

    /**
     * Authoritatively validate and process a standalone parent row.
     */
    private function processParentRow(
        ImportBatch $batch,
        int $rowNumber,
        array $row,
        array &$rowErrors,
        int &$createdCount,
        int &$updatedCount,
        int &$validCount,
        int &$invalidCount,
        array &$chunkErrors
    ): void {
        $name = trim((string)($row['name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))));
        $phone = !empty($row['phone']) ? trim((string)$row['phone']) : null;
        $email = !empty($row['email']) ? strtolower(trim((string)$row['email'])) : null;
        $studentAdm = !empty($row['student_admission_number']) ? trim((string)$row['student_admission_number']) : null;
        $relationship = !empty($row['relationship']) ? trim((string)$row['relationship']) : 'Guardian';

        if ($name === '') {
            $rowErrors[] = 'Parent name is required.';
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $rowErrors[] = 'Parent email format is invalid.';
        }

        if (!empty($rowErrors)) {
            $invalidCount++;
            $chunkErrors[] = [
                'row_number' => $rowNumber,
                'raw_data' => $row,
                'errors' => $rowErrors,
            ];
            $this->importRepository->addError($batch->id, $rowNumber, $row, $rowErrors);
            return;
        }

        // Deduplication & Parent resolution
        $parent = $this->resolveOrCreateParent($name, $phone, $email);

        if ($studentAdm !== null && $studentAdm !== '') {
            $rawAdmissions = preg_split('/[,;\n\r]+/', $studentAdm);
            foreach ($rawAdmissions as $adm) {
                $trimmedAdm = trim($adm);
                if ($trimmedAdm === '') {
                    continue;
                }
                $student = $this->studentRepository->findByAdmissionNumber($trimmedAdm);
                if ($student) {
                    $this->parentRepository->linkStudent($parent->id, $student->id, $relationship);
                }
            }
        }

        $createdCount++;
        $validCount++;
    }

    /**
     * Reconcile parent contact and link to student.
     */
    private function reconcileParentForStudent(
        int $studentId,
        string $studentName,
        string $parentName,
        ?string $parentPhone,
        ?string $parentEmail,
        string $relationship
    ): void {
        $parent = $this->resolveOrCreateParent(
            $parentName !== '' ? $parentName : "Guardian of {$studentName}",
            $parentPhone,
            $parentEmail
        );

        $this->parentRepository->linkStudent($parent->id, $studentId, $relationship);
    }

    /**
     * Resolve candidate parent or create a contact-only parent.
     */
    private function resolveOrCreateParent(string $name, ?string $phone, ?string $email): object
    {
        if ($phone || $email) {
            $candidates = $this->parentRepository->findCandidates($phone, $email);
            foreach ($candidates as $cand) {
                $candName = method_exists($cand, 'getName') ? $cand->getName() : ($cand->name ?? $cand->displayName ?? '');
                // If candidate name matches incoming name, reuse parent profile
                if ($this->isCandidateNameMatch((string)$candName, $name)) {
                    return $cand;
                }
            }
        }

        // No candidate found or material name conflict: create new contact-only parent
        return $this->parentRepository->createContactOnly($name, $phone, $email);
    }

    /**
     * Compare candidate parent name with incoming parent name.
     */
    public function isCandidateNameMatch(?string $candidateName, ?string $incomingName): bool
    {
        $cleanCandidate = $this->cleanPersonName((string)$candidateName);
        $cleanIncoming = $this->cleanPersonName((string)$incomingName);

        if ($cleanCandidate === '' || $cleanIncoming === '') {
            return false;
        }

        if ($cleanCandidate === $cleanIncoming) {
            return true;
        }

        $candTokens = array_values(array_filter(explode(' ', $cleanCandidate)));
        $incTokens = array_values(array_filter(explode(' ', $cleanIncoming)));

        $candSorted = $candTokens;
        $incSorted = $incTokens;
        sort($candSorted);
        sort($incSorted);
        if ($candSorted === $incSorted) {
            return true;
        }

        $intersection = array_intersect($candTokens, $incTokens);
        if (count($intersection) >= 2) {
            return true;
        }

        if (count($candTokens) === 1 || count($incTokens) === 1) {
            if (!empty($intersection)) {
                return true;
            }
        }

        $lev = levenshtein($cleanCandidate, $cleanIncoming);
        $maxLen = max(strlen($cleanCandidate), strlen($cleanIncoming));
        if ($maxLen > 0 && ($lev / $maxLen) <= 0.20) {
            return true;
        }

        return false;
    }

    /**
     * Strip titles and punctuation from personal names for comparison.
     */
    private function cleanPersonName(string $name): string
    {
        $cleaned = preg_replace('/\b(mr|mrs|ms|miss|dr|prof|chief|pastor|rev|engr|alhaji|alhaja)\.?\b/i', '', $name);
        $cleaned = preg_replace('/[^a-zA-Z\s]/', '', (string)$cleaned);
        return strtolower(trim((string)preg_replace('/\s+/', ' ', (string)$cleaned)));
    }

    /**
     * Normalize date string or Excel serial number to Y-m-d.
     * Accurately expands 2-digit years (e.g. 12/5/00 -> 2000-05-12).
     */
    private function normalizeDate(?string $dateStr): ?string
    {
        if ($dateStr === null) {
            return null;
        }
        $trimmed = trim($dateStr);
        if ($trimmed === '') {
            return null;
        }

        // 1. Excel numeric serial date (e.g. 36658 = 2000-05-12)
        if (is_numeric($trimmed) && (int)$trimmed > 10000 && (int)$trimmed < 65000) {
            $unixTimestamp = ((int)$trimmed - 25569) * 86400;
            return gmdate('Y-m-d', $unixTimestamp);
        }

        // 2. ISO format YYYY-MM-DD or YYYY/MM/DD
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $trimmed, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            $day = (int)$m[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // 3. DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY (4-digit year at end)
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $trimmed, $m)) {
            $day = (int)$m[1];
            $month = (int)$m[2];
            $year = (int)$m[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
            // Fallback for MM/DD/YYYY if day > 12 in second position
            if (checkdate($day, $month, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $day, $month);
            }
        }

        // 4. 2-DIGIT YEAR: DD/MM/YY or DD-MM-YY (e.g. 12/5/00 or 12/05/00)
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2})$/', $trimmed, $m)) {
            $day = (int)$m[1];
            $month = (int)$m[2];
            $rawYear = (int)$m[3];

            // Expand 2-digit year: 00-35 -> 2000-2035, 36-99 -> 1936-1999
            $year = ($rawYear <= 35) ? (2000 + $rawYear) : (1900 + $rawYear);

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
            if (checkdate($day, $month, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $day, $month);
            }
        }

        // 5. Standard DateTime parsing fallback
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat('!' . $fmt, $trimmed);
            if ($dt && $dt->format($fmt) === $trimmed) {
                return $dt->format('Y-m-d');
            }
        }

        $timestamp = strtotime($trimmed);
        if ($timestamp !== false && $timestamp > 0) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }


    /**
     * Finalize an import batch upon all chunks completion.
     */
    public function finalizeBatch(int $importId, UserContext $actor): ServiceResult
    {
        $batch = $this->importRepository->findById($importId);
        if (!$batch) {
            throw new ResourceNotFoundException("Import batch #{$importId} not found.");
        }

        if ($batch->isCommitted()) {
            return ServiceResult::success(['batch' => $batch, 'already_finalized' => true]);
        }

        $this->importRepository->markCommitted($importId);

        return ServiceResult::success([
            'batch' => $this->importRepository->findById($importId),
            'already_finalized' => false,
        ]);
    }

    /**
     * RFC 4122 version 4 UUID generator.
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate secure temporary alphanumeric password.
     */
    private function generateTempPassword(): string
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        $random = '';
        for ($i = 0; $i < 6; $i++) {
            $random .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return 'Claret!' . $random;
    }

    /**
     * Backwards-compatible CSV validation method for legacy forms.
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
        $rowNumber = 1;

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
            'batch' => $this->importRepository->findById($batch->id) ?? $batch,
            'valid_rows' => $validRows,
            'errors' => $errors,
        ]);
    }

    private function validateRow(array $row, string $type, array &$seenEmails, array &$seenIdentifiers): array
    {
        $errors = [];

        $email = strtolower(trim($row['email'] ?? ''));
        $name = trim($row['name'] ?? '');

        if ($name === '') {
            $errors[] = 'Name is required.';
        }

        if ($type === 'parents') {
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address format is required.';
            }
        } else {
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            } elseif (isset($seenEmails[$email])) {
                $errors[] = "Duplicate email '{$email}' in import file.";
            } elseif ($this->userRepository->findByEmail($email) !== null) {
                $errors[] = "User with email '{$email}' already exists.";
            } else {
                $seenEmails[$email] = true;
            }
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

    /**
     * Backwards-compatible commit method for legacy forms.
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

        $res = $this->processChunk($importId, 1, 1, $validRows, $actor);
        $this->importRepository->markCommitted($importId);
        return $res;
    }
}
