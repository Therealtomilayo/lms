<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Coursework Assignments and Submissions
 * Governed strictly by .ai/06-rbac-permissions.md
 */
final class AssignmentPolicy
{
    /**
     * Determine if a user can create an assignment in a class-subject and term.
     */
    public static function canCreateAssignment(
        UserContext $userContext,
        int $classSubjectId,
        ?int $termId = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (!$userContext->hasRole('teacher')) {
            return false;
        }

        $teacherRepo = $teacherRepository ?? new TeacherRepository();
        $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
        if (!$teacher) {
            return false;
        }

        $academicRepo = $academicRepository ?? new AcademicRepository();
        $classSubject = $academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject || !$classSubject->isActive() || $classSubject->teacherId !== $teacher->id) {
            return false;
        }

        if ($termId !== null) {
            $term = $academicRepo->findTermById($termId);
            if (!$term || $term->sessionId !== $classSubject->sessionId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a user can edit/update an assignment.
     */
    public static function canEditAssignment(
        UserContext $userContext,
        Assignment $assignment,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (!$userContext->hasRole('teacher')) {
            return false;
        }

        $teacherRepo = $teacherRepository ?? new TeacherRepository();
        $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
        if (!$teacher || $assignment->teacherId !== $teacher->id) {
            return false;
        }

        $academicRepo = $academicRepository ?? new AcademicRepository();
        $classSubject = $academicRepo->findClassSubjectById($assignment->classSubjectId);
        if (!$classSubject || !$classSubject->isActive() || $classSubject->teacherId !== $teacher->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can delete/deactivate an assignment.
     */
    public static function canDeleteAssignment(
        UserContext $userContext,
        Assignment $assignment,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ): bool {
        return self::canEditAssignment($userContext, $assignment, $academicRepository, $teacherRepository);
    }

    /**
     * Determine if a user can view an assignment.
     */
    public static function canViewAssignment(
        UserContext $userContext,
        Assignment $assignment,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?ParentRepository $parentRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $academicRepo = $academicRepository ?? new AcademicRepository();
        $classSubject = $academicRepo->findClassSubjectById($assignment->classSubjectId);
        if (!$classSubject) {
            return false;
        }

        // Teacher check
        if ($userContext->hasRole('teacher')) {
            $teacherRepo = $teacherRepository ?? new TeacherRepository();
            $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
            if ($teacher && $classSubject->teacherId === $teacher->id) {
                return true;
            }
        }

        // Student check: must be published AND student enrolled in subject
        if ($userContext->hasRole('student') && $assignment->isPublished()) {
            $studentRepo = $studentRepository ?? new StudentRepository();
            $student = $studentRepo->findByUserId($userContext->id);
            if ($student) {
                $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();
                if ($enrollmentRepo->isStudentEnrolledInSubject($student->id, $assignment->classSubjectId, $classSubject->sessionId)) {
                    return true;
                }
            }
        }

        // Parent check: must be published AND parent linked to an enrolled student
        if ($userContext->hasRole('parent') && $assignment->isPublished()) {
            $parentRepo = $parentRepository ?? new ParentRepository();
            $parent = $parentRepo->findByUserId($userContext->id);
            if ($parent) {
                $linkedStudents = $parentRepo->getLinkedStudents($parent->id);
                $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();
                foreach ($linkedStudents as $student) {
                    if ($enrollmentRepo->isStudentEnrolledInSubject($student->id, $assignment->classSubjectId, $classSubject->sessionId)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Determine if a user can submit work for an assignment.
     */
    public static function canSubmitAssignment(
        UserContext $userContext,
        Assignment $assignment,
        ?AcademicRepository $academicRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null
    ): bool {
        if (!$userContext->hasRole('student') || !$assignment->isPublished()) {
            return false;
        }

        $studentRepo = $studentRepository ?? new StudentRepository();
        $student = $studentRepo->findByUserId($userContext->id);
        if (!$student) {
            return false;
        }

        $academicRepo = $academicRepository ?? new AcademicRepository();
        $classSubject = $academicRepo->findClassSubjectById($assignment->classSubjectId);
        if (!$classSubject) {
            return false;
        }

        $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();

        return $enrollmentRepo->isStudentEnrolledInSubject($student->id, $assignment->classSubjectId, $classSubject->sessionId);
    }

    /**
     * Determine if a user can grade a submission.
     */
    public static function canGradeSubmission(
        UserContext $userContext,
        AssignmentSubmission $submission,
        ?AssignmentRepository $assignmentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (!$userContext->hasRole('teacher')) {
            return false;
        }

        $assignment = $submission->assignment;
        if (!$assignment) {
            $assignmentRepo = $assignmentRepository ?? new AssignmentRepository();
            $assignment = $assignmentRepo->findById($submission->assignmentId);
        }

        if (!$assignment) {
            return false;
        }

        $teacherRepo = $teacherRepository ?? new TeacherRepository();
        $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
        if (!$teacher) {
            return false;
        }

        $academicRepo = $academicRepository ?? new AcademicRepository();
        $classSubject = $academicRepo->findClassSubjectById($assignment->classSubjectId);
        if (!$classSubject || !$classSubject->isActive() || $classSubject->teacherId !== $teacher->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can view a submission.
     */
    public static function canViewSubmission(
        UserContext $userContext,
        AssignmentSubmission $submission,
        ?AssignmentRepository $assignmentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?ParentRepository $parentRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        $assignment = $submission->assignment;
        if (!$assignment) {
            $assignmentRepo = $assignmentRepository ?? new AssignmentRepository();
            $assignment = $assignmentRepo->findById($submission->assignmentId);
        }

        if (!$assignment) {
            return false;
        }

        // Teacher check (assigned teacher can view)
        if ($userContext->hasRole('teacher')) {
            $teacherRepo = $teacherRepository ?? new TeacherRepository();
            $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
            if ($teacher) {
                $academicRepo = $academicRepository ?? new AcademicRepository();
                $classSubject = $academicRepo->findClassSubjectById($assignment->classSubjectId);
                if ($classSubject && $classSubject->teacherId === $teacher->id) {
                    return true;
                }
            }
        }

        // Student check: must be the student who submitted
        if ($userContext->hasRole('student')) {
            $studentRepo = $studentRepository ?? new StudentRepository();
            $student = $studentRepo->findByUserId($userContext->id);
            if ($student && $submission->studentId === $student->id) {
                return true;
            }
        }

        // Parent check: must be linked to the submitting student
        if ($userContext->hasRole('parent')) {
            $parentRepo = $parentRepository ?? new ParentRepository();
            $parent = $parentRepo->findByUserId($userContext->id);
            if ($parent && $parentRepo->isLinkedToStudent($parent->id, $submission->studentId)) {
                return true;
            }
        }

        return false;
    }
}
