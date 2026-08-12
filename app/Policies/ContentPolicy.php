<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\ContentItem;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Course Content & Materials
 * Governed strictly by .ai/06-rbac-permissions.md
 */
final class ContentPolicy
{
    /**
     * Determine if a user can create content in a class-subject.
     * Allowed: super_admin, admin, or the assigned teacher for that class-subject.
     */
    public static function canCreateContent(
        UserContext $userContext,
        int $classSubjectId,
        ?int $sessionId = null,
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

        if ($sessionId !== null && $classSubject->sessionId !== $sessionId) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can edit/update a content item.
     */
    public static function canEditContent(
        UserContext $userContext,
        ContentItem $item,
        ?int $sessionId = null,
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
        if (!$teacher || $item->teacherId !== $teacher->id) {
            return false;
        }

        $academicRepo = $academicRepository ?? new AcademicRepository();
        $classSubject = $academicRepo->findClassSubjectById($item->classSubjectId);
        if (!$classSubject || !$classSubject->isActive() || $classSubject->teacherId !== $teacher->id) {
            return false;
        }

        if ($sessionId !== null && $classSubject->sessionId !== $sessionId) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can delete a content item.
     */
    public static function canDeleteContent(
        UserContext $userContext,
        ContentItem $item,
        ?int $sessionId = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ): bool {
        return self::canEditContent($userContext, $item, $sessionId, $academicRepository, $teacherRepository);
    }

    /**
     * Determine if a user can view a content item.
     * Allowed:
     * - super_admin, admin
     * - assigned teacher
     * - enrolled student (if published)
     * - parent of enrolled student (if published)
     */
    public static function canViewContent(
        UserContext $userContext,
        ContentItem $item,
        ?int $sessionId = null,
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
        $classSubject = $academicRepo->findClassSubjectById($item->classSubjectId);
        if (!$classSubject) {
            return false;
        }

        $effectiveSessionId = $sessionId ?? $classSubject->sessionId;

        // Teacher check
        if ($userContext->hasRole('teacher')) {
            $teacherRepo = $teacherRepository ?? new TeacherRepository();
            $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
            if ($teacher && $classSubject->teacherId === $teacher->id) {
                return true;
            }
        }

        // Student check: must be published AND student enrolled in subject
        if ($userContext->hasRole('student') && $item->isPublished()) {
            $studentRepo = $studentRepository ?? new StudentRepository();
            $student = $studentRepo->findByUserId($userContext->id);
            if ($student) {
                $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();
                if ($enrollmentRepo->isStudentEnrolledInSubject($student->id, $item->classSubjectId, $effectiveSessionId)) {
                    return true;
                }
            }
        }

        // Parent check: must be published AND parent linked to an enrolled student
        if ($userContext->hasRole('parent') && $item->isPublished()) {
            $parentRepo = $parentRepository ?? new ParentRepository();
            $parent = $parentRepo->findByUserId($userContext->id);
            if ($parent) {
                $linkedStudents = $parentRepo->getLinkedStudents($parent->id);
                $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();
                foreach ($linkedStudents as $student) {
                    if ($enrollmentRepo->isStudentEnrolledInSubject($student->id, $item->classSubjectId, $effectiveSessionId)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
