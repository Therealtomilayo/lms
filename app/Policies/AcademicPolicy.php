<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;

/**
 * Authorization Policy for Academic Sessions, Terms, Levels, Classes, and Subjects
 * Governed strictly by .ai/06-rbac-permissions.md
 */
final class AcademicPolicy
{
    /**
     * Determine if the user can manage (create, edit, transition) academic sessions and terms.
     * Allowed: super_admin, admin.
     */
    public static function canManageSessionsAndTerms(UserContext $userContext): bool
    {
        return $userContext->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine if the user can define/edit academic levels, classes, and subjects.
     * Allowed: super_admin, admin.
     */
    public static function canManageAcademicStructure(UserContext $userContext): bool
    {
        return $userContext->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine if the user can view academic sessions and terms.
     * Allowed: all authenticated users.
     */
    public static function canViewSessionsAndTerms(UserContext $userContext): bool
    {
        return $userContext->isAuthenticated();
    }

    /**
     * Determine if the user can view classes and subjects.
     * Allowed: all authenticated users.
     */
    public static function canViewAcademicStructure(UserContext $userContext): bool
    {
        return $userContext->isAuthenticated();
    }

    /**
     * Determine if the user can manage (create, reassign, activate/deactivate) class subjects.
     * Allowed: super_admin, admin.
     */
    public static function canManageClassSubjects(UserContext $userContext): bool
    {
        return $userContext->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine if the user can view class subjects.
     * Allowed: all authenticated users.
     */
    public static function canViewClassSubjects(UserContext $userContext): bool
    {
        return $userContext->isAuthenticated();
    }

    /**
     * Determine if the user can manage class and subject enrollments.
     * Allowed: super_admin, admin.
     */
    public static function canManageEnrollments(UserContext $userContext): bool
    {
        return $userContext->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine if the user can view enrollment rosters.
     * Allowed: super_admin, admin, teacher.
     */
    public static function canViewEnrollments(UserContext $userContext): bool
    {
        return $userContext->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    /**
     * Determine if the user can manage guardian (parent-student) links.
     * Allowed: super_admin, admin.
     */
    public static function canManageGuardians(UserContext $userContext): bool
    {
        return $userContext->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Teacher -> Class-Subject Scope predicate
     * True only if an active class_subjects record links $teacherId to $classSubjectId for the given $sessionId.
     */
    public static function teacherCanManageClassSubject(
        int $teacherId,
        int $classSubjectId,
        int $sessionId,
        ?\App\Repositories\AcademicRepository $academicRepository = null
    ): bool {
        $repo = $academicRepository ?? new \App\Repositories\AcademicRepository();
        $classSubject = $repo->findClassSubjectById($classSubjectId);

        if (!$classSubject) {
            return false;
        }

        return $classSubject->isActive()
            && $classSubject->teacherId === $teacherId
            && $classSubject->sessionId === $sessionId;
    }

    /**
     * Student -> Subject Enrollment Scope predicate
     * True only if the student has an active student_subject_enrollments row for $classSubjectId in $sessionId.
     */
    public static function studentCanAccessClassSubject(
        int $studentId,
        int $classSubjectId,
        int $sessionId,
        ?\App\Repositories\EnrollmentRepository $enrollmentRepository = null
    ): bool {
        $repo = $enrollmentRepository ?? new \App\Repositories\EnrollmentRepository();
        return $repo->isStudentEnrolledInSubject($studentId, $classSubjectId, $sessionId);
    }

    /**
     * Parent -> Linked Child Scope predicate
     * True only if an active parent_student record exists linking $parentId to $studentId.
     */
    public static function parentCanViewStudent(
        int $parentId,
        int $studentId,
        ?\App\Repositories\ParentRepository $parentRepository = null
    ): bool {
        $repo = $parentRepository ?? new \App\Repositories\ParentRepository();
        return $repo->isLinked($parentId, $studentId);
    }
}

