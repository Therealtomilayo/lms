<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\DTO\ServiceResult;
use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\SchoolClass;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;

/**
 * Application Service for Student Class & Subject Enrollments
 * Enforces session-scoped enrollment invariants and automatic core subject allocations.
 */
class EnrollmentService
{
    private EnrollmentRepository $enrollmentRepository;
    private StudentRepository $studentRepository;
    private AcademicRepository $academicRepository;

    public function __construct(
        ?EnrollmentRepository $enrollmentRepository = null,
        ?StudentRepository $studentRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
    }

    /**
     * Enroll a student into a class for an academic session.
     */
    public function enrollStudentInClass(
        int $studentId,
        int $classId,
        int $sessionId,
        string $status = ClassEnrollment::STATUS_ACTIVE,
        bool $autoEnrollSubjects = true
    ): ServiceResult {
        if ($studentId <= 0 || $classId <= 0 || $sessionId <= 0) {
            throw new ValidationException(['general' => ['Student, Class, and Academic Session are required.']]);
        }

        // Validate Student
        $student = $this->studentRepository->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException("Student #{$studentId} not found.");
        }

        // Validate Class
        $class = $this->academicRepository->findClassById($classId);
        if (!$class) {
            throw new ResourceNotFoundException("Class #{$classId} not found.");
        }
        if ($class->status !== SchoolClass::STATUS_ACTIVE) {
            throw new DomainRuleException("Cannot enroll student into an inactive class ('{$class->name}').");
        }

        // Validate Session
        $session = $this->academicRepository->findSessionById($sessionId);
        if (!$session) {
            throw new ResourceNotFoundException("Academic session #{$sessionId} not found.");
        }
        if ($session->status === AcademicSession::STATUS_ARCHIVED) {
            throw new DomainRuleException("Cannot modify enrollments in an archived academic session.");
        }

        // Enroll in class
        $enrollment = $this->enrollmentRepository->enrollInClass($studentId, $classId, $sessionId, $status);

        // Update student's cached current_class_id
        $this->studentRepository->update($studentId, currentClassId: $classId);

        // Auto-enroll in active subjects for that class in this session
        if ($autoEnrollSubjects) {
            $this->autoEnrollClassSubjects($studentId, $classId, $sessionId);
        }

        return ServiceResult::success($enrollment);
    }

    /**
     * Bulk enroll multiple students into a class for a session.
     */
    public function bulkEnrollClass(array $studentIds, int $classId, int $sessionId): ServiceResult
    {
        if (empty($studentIds)) {
            throw new ValidationException(['students' => ['At least one student must be selected for enrollment.']]);
        }

        $enrolled = [];
        $errors = [];

        foreach ($studentIds as $sId) {
            $studentId = (int)$sId;
            try {
                $res = $this->enrollStudentInClass($studentId, $classId, $sessionId);
                $enrolled[] = $res->getData();
            } catch (\Throwable $e) {
                $errors[] = "Student #{$studentId}: " . $e->getMessage();
            }
        }

        return ServiceResult::success([
            'enrolled_count' => count($enrolled),
            'errors' => $errors,
            'enrollments' => $enrolled,
        ]);
    }

    /**
     * Automatically enroll a student into all active class-subjects of a class in a session.
     */
    public function autoEnrollClassSubjects(int $studentId, int $classId, int $sessionId): int
    {
        $class = $this->academicRepository->findClassById($classId);
        $level = $class ? $this->academicRepository->findLevelById($class->academicLevelId) : null;
        $isSeniorSecondary = $level && $level->stage === 'senior_secondary';

        // For non-senior secondary (Junior Secondary, Primary, Pre-School), all arms of that level take the same subjects.
        // If other arms in the same level have active subjects for this session that this arm does not yet have,
        // propagate them to this arm so offerings exist.
        if (!$isSeniorSecondary && $class) {
            $siblingClasses = $this->academicRepository->getClassesByLevel($class->academicLevelId);
            $existingSubjects = $this->academicRepository->getClassSubjectsBySession($sessionId, $classId);
            $existingSubjectIds = array_map(fn($cs) => $cs->subjectId, $existingSubjects);

            foreach ($siblingClasses as $sibling) {
                if ($sibling->id === $classId) {
                    continue;
                }
                $siblingSubjects = $this->academicRepository->getClassSubjectsBySession($sessionId, $sibling->id);
                foreach ($siblingSubjects as $sibCs) {
                    if (!in_array($sibCs->subjectId, $existingSubjectIds, true) && $sibCs->isActive()) {
                        $this->academicRepository->createClassSubject([
                            'session_id' => $sessionId,
                            'class_id' => $classId,
                            'subject_id' => $sibCs->subjectId,
                            'teacher_id' => $sibCs->teacherId,
                            'status' => 'active',
                        ]);
                        $existingSubjectIds[] = $sibCs->subjectId;
                    }
                }
            }
        }

        // Enroll student in all active class-subjects of this class arm
        $classSubjects = $this->academicRepository->getClassSubjectsBySession($sessionId, $classId);
        $count = 0;

        foreach ($classSubjects as $cs) {
            if ($cs->isActive()) {
                $this->enrollmentRepository->enrollInSubject(
                    studentId: $studentId,
                    classSubjectId: $cs->id,
                    sessionId: $sessionId,
                    isElective: false,
                    status: 'active'
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Transfer a student from one class to another within the same session.
     */
    public function transferStudentClass(int $studentId, int $newClassId, int $sessionId): ServiceResult
    {
        $existing = $this->enrollmentRepository->findClassEnrollment($studentId, $sessionId);
        if (!$existing) {
            throw new ResourceNotFoundException("No existing enrollment found for Student #{$studentId} in this session.");
        }

        return $this->enrollStudentInClass($studentId, $newClassId, $sessionId, ClassEnrollment::STATUS_ACTIVE, true);
    }

    /**
     * Update enrollment status (e.g. active, promoted, repeating, transferred, withdrawn).
     */
    public function updateEnrollmentStatus(int $enrollmentId, string $status): ServiceResult
    {
        $validStatuses = [
            ClassEnrollment::STATUS_ACTIVE,
            ClassEnrollment::STATUS_PROMOTED,
            ClassEnrollment::STATUS_REPEATING,
            ClassEnrollment::STATUS_TRANSFERRED,
            ClassEnrollment::STATUS_WITHDRAWN,
        ];

        if (!in_array($status, $validStatuses, true)) {
            throw new DomainRuleException("Invalid enrollment status '{$status}'.");
        }

        $enrollment = $this->enrollmentRepository->findClassEnrollmentById($enrollmentId);
        if (!$enrollment) {
            throw new ResourceNotFoundException("Enrollment #{$enrollmentId} not found.");
        }

        $session = $this->academicRepository->findSessionById($enrollment->sessionId);
        if ($session && $session->status === AcademicSession::STATUS_ARCHIVED) {
            throw new DomainRuleException("Cannot modify enrollments in an archived academic session.");
        }

        $this->enrollmentRepository->updateClassEnrollmentStatus($enrollmentId, $status);

        return ServiceResult::success($this->enrollmentRepository->findClassEnrollmentById($enrollmentId));
    }
}
