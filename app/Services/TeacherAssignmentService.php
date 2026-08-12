<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\DTO\ServiceResult;
use App\Models\AcademicSession;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;

/**
 * Application Service for Class Subject Mappings and Teacher Assignments
 * Enforces session-scoped assignment invariants, teacher role validation, and historical constraints.
 */
class TeacherAssignmentService
{
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;
    private UserRepository $userRepository;

    public function __construct(
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?UserRepository $userRepository = null
    ) {
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    /**
     * Create/Register a Teacher profile for an existing User with a teacher role.
     */
    public function createTeacher(int $userId, string $staffId): ServiceResult
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            throw new ValidationException(['staff_id' => ['Staff ID is required.']]);
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ResourceNotFoundException("User #{$userId} not found.");
        }

        if (!$user->hasRole('teacher')) {
            throw new DomainRuleException("User '{$user->name}' does not hold the 'teacher' role.");
        }

        $existingByUser = $this->teacherRepository->findTeacherByUserId($userId);
        if ($existingByUser) {
            throw new DomainRuleException("A teacher record already exists for user '{$user->name}'.");
        }

        $existingByStaff = $this->teacherRepository->findTeacherByStaffId($staffId);
        if ($existingByStaff) {
            throw new DomainRuleException("Staff ID '{$staffId}' is already assigned to another teacher.");
        }

        $teacher = $this->teacherRepository->createTeacher($userId, $staffId);

        return ServiceResult::success($teacher);
    }

    /**
     * Assign a teacher to deliver a subject for a class in a specific academic session.
     */
    public function assignTeacherToClassSubject(array $data): ServiceResult
    {
        $sessionId = (int)($data['session_id'] ?? 0);
        $classId = (int)($data['class_id'] ?? 0);
        $subjectId = (int)($data['subject_id'] ?? 0);
        $teacherId = (int)($data['teacher_id'] ?? 0);
        $status = $data['status'] ?? ClassSubject::STATUS_ACTIVE;

        if ($sessionId <= 0 || $classId <= 0 || $subjectId <= 0 || $teacherId <= 0) {
            throw new ValidationException(['general' => ['Academic session, class, subject, and teacher are required.']]);
        }

        // Validate Session
        $session = $this->academicRepository->findSessionById($sessionId);
        if (!$session) {
            throw new ResourceNotFoundException("Academic session #{$sessionId} not found.");
        }
        if ($session->status === AcademicSession::STATUS_ARCHIVED) {
            throw new DomainRuleException("Cannot assign teaching subjects in an archived academic session.");
        }

        // Validate Class
        $class = $this->academicRepository->findClassById($classId);
        if (!$class) {
            throw new ResourceNotFoundException("Class #{$classId} not found.");
        }
        if ($class->status !== SchoolClass::STATUS_ACTIVE) {
            throw new DomainRuleException("Cannot allocate subjects to an inactive class ('{$class->name}').");
        }

        // Validate Subject
        $subject = $this->academicRepository->findSubjectById($subjectId);
        if (!$subject) {
            throw new ResourceNotFoundException("Subject #{$subjectId} not found.");
        }
        if ($subject->status !== Subject::STATUS_ACTIVE) {
            throw new DomainRuleException("Cannot allocate inactive subject ('{$subject->name}').");
        }

        // Validate Teacher & Role
        $teacher = $this->teacherRepository->findTeacherById($teacherId);
        if (!$teacher) {
            throw new ResourceNotFoundException("Teacher #{$teacherId} not found.");
        }
        $teacherUser = $this->userRepository->findById($teacher->userId);
        if (!$teacherUser || !$teacherUser->hasRole('teacher') || !$teacherUser->isActive()) {
            throw new DomainRuleException("Assigned teacher account is inactive or lacks active 'teacher' role.");
        }

        // Check for existing class subject mapping in the session
        $existing = $this->academicRepository->findClassSubject($sessionId, $classId, $subjectId);
        if ($existing !== null) {
            throw new DomainRuleException(
                "Subject '{$subject->name}' is already assigned to '{$class->name}' in session '{$session->name}'."
            );
        }

        $classSubject = $this->academicRepository->createClassSubject([
            'session_id' => $sessionId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'status' => $status,
        ]);

        return ServiceResult::success($classSubject);
    }

    /**
     * Reassign the teacher for an existing class-subject mapping.
     */
    public function reassignTeacher(int $classSubjectId, int $newTeacherId): ServiceResult
    {
        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException("Class-subject assignment #{$classSubjectId} not found.");
        }

        $session = $this->academicRepository->findSessionById($classSubject->sessionId);
        if ($session && $session->status === AcademicSession::STATUS_ARCHIVED) {
            throw new DomainRuleException("Cannot modify teaching assignments in an archived academic session.");
        }

        if ($classSubject->teacherId === $newTeacherId) {
            return ServiceResult::success($classSubject);
        }

        $teacher = $this->teacherRepository->findTeacherById($newTeacherId);
        if (!$teacher) {
            throw new ResourceNotFoundException("Teacher #{$newTeacherId} not found.");
        }

        $teacherUser = $this->userRepository->findById($teacher->userId);
        if (!$teacherUser || !$teacherUser->hasRole('teacher') || !$teacherUser->isActive()) {
            throw new DomainRuleException("Assigned teacher account is inactive or lacks active 'teacher' role.");
        }

        $this->academicRepository->updateClassSubjectTeacher($classSubjectId, $newTeacherId);

        return ServiceResult::success($this->academicRepository->findClassSubjectById($classSubjectId));
    }

    /**
     * Update active / inactive status of a class-subject mapping.
     */
    public function updateClassSubjectStatus(int $classSubjectId, string $status): ServiceResult
    {
        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException("Class-subject assignment #{$classSubjectId} not found.");
        }

        if (!in_array($status, [ClassSubject::STATUS_ACTIVE, ClassSubject::STATUS_INACTIVE], true)) {
            throw new DomainRuleException("Invalid class-subject status '{$status}'.");
        }

        $session = $this->academicRepository->findSessionById($classSubject->sessionId);
        if ($session && $session->status === AcademicSession::STATUS_ARCHIVED) {
            throw new DomainRuleException("Cannot modify teaching assignments in an archived academic session.");
        }

        $this->academicRepository->updateClassSubjectStatus($classSubjectId, $status);

        return ServiceResult::success($this->academicRepository->findClassSubjectById($classSubjectId));
    }
}
