<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Term Results, Publications, and Report Cards
 */
final class ResultPolicy
{
    public static function canReview(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canPublish(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canUnpublish(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canViewReportCard(
        UserContext $user,
        int $studentId,
        int $termId,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?GradebookRepository $gradebookRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null
    ): bool {
        // Super Admin & Admin can always view any report card
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        // Check if user is an authorized Class / Form Teacher
        if ($user->isTeacher()) {
            $teacherRepo = $teacherRepo ?? new TeacherRepository();
            $teacher = $teacherRepo->findTeacherByUserId($user->id);
            if (!$teacher) {
                return false;
            }

            $academicRepo = $academicRepo ?? new AcademicRepository();
            $gradebookRepo = $gradebookRepo ?? new GradebookRepository();
            $studentRepo = $studentRepo ?? new StudentRepository();

            // 1. Check summary record for this student and term
            $summary = $gradebookRepo->findStudentTermSummary($studentId, $termId);
            if ($summary && $summary->classId) {
                $class = $academicRepo->findClassById((int)$summary->classId);
                if ($class && (int)$class->formTeacherId === (int)$teacher->id) {
                    return true;
                }
            }

            // 2. Check student's current assigned class
            $student = $studentRepo->findById($studentId);
            if ($student && $student->currentClassId) {
                $class = $academicRepo->findClassById((int)$student->currentClassId);
                if ($class && (int)$class->formTeacherId === (int)$teacher->id) {
                    return true;
                }
            }

            // 3. Check session enrollment for the term
            $term = $academicRepo->findTermById($termId);
            if ($term) {
                $enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
                $enrollment = $enrollmentRepo->findClassEnrollment($studentId, (int)$term->sessionId);
                if ($enrollment && $enrollment->classId) {
                    $class = $academicRepo->findClassById((int)$enrollment->classId);
                    if ($class && (int)$class->formTeacherId === (int)$teacher->id) {
                        return true;
                    }
                }
            }

            return false;
        }

        return false;
    }

    public static function canViewStudentResults(
        UserContext $user,
        int $studentId,
        bool $isPublished,
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null
    ): bool {
        // Super Admin & Admin can always view
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        // Students & Parents can strictly NEVER view unpublished results
        if (!$isPublished) {
            return false;
        }

        // Student can only view their own results
        if ($user->isStudent()) {
            return $user->getStudentId($studentRepo) === $studentId;
        }

        // Parent can only view results of their linked children
        if ($user->isParent()) {
            $parentId = $user->getParentId($parentRepo);
            if ($parentId === null || $parentRepo === null) {
                return false;
            }
            return $parentRepo->isLinkedToStudent($parentId, $studentId);
        }

        return false;
    }
}
