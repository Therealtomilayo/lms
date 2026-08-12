<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Timetable Management and Viewing
 */
final class TimetablePolicy
{
    /**
     * Determine if the user can manage (create, update, delete) timetable slots.
     */
    public static function canManage(?UserContext $user): bool
    {
        return $user !== null && $user->isAdmin();
    }

    /**
     * Determine if the user can view any timetable at all.
     */
    public static function canViewAny(?UserContext $user): bool
    {
        return $user !== null && ($user->isAdmin() || $user->isTeacher() || $user->isStudent() || $user->isParent());
    }

    /**
     * Determine if the user can view a specific class timetable.
     */
    public static function canViewClassTimetable(
        UserContext $user,
        int $classId,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?ParentRepository $parentRepo = null
    ): bool {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            $teacherId = $user->getTeacherId();
            if ($teacherId !== null) {
                $acRepo = $academicRepo ?? new AcademicRepository();
                $teacherClassSubjects = $acRepo->findClassSubjectsByTeacherId($teacherId);
                foreach ($teacherClassSubjects as $cs) {
                    if ((int)$cs->classId === $classId) {
                        return true;
                    }
                }
            }
        }

        if ($user->isStudent()) {
            $studentId = $user->getStudentId();
            if ($studentId !== null) {
                $enrRepo = $enrollmentRepo ?? new EnrollmentRepository();
                $enrollment = $enrRepo->getCurrentClassEnrollment($studentId);
                if ($enrollment && (int)$enrollment->classId === $classId) {
                    return true;
                }
            }
        }

        if ($user->isParent()) {
            $pRepo = $parentRepo ?? new ParentRepository();
            $parentId = $user->getParentId($pRepo);
            if ($parentId !== null) {
                $enrRepo = $enrollmentRepo ?? new EnrollmentRepository();
                $linkedStudents = $pRepo->getLinkedStudents($parentId);
                foreach ($linkedStudents as $student) {
                    $enrollment = $enrRepo->getCurrentClassEnrollment((int)$student->id);
                    if ($enrollment && (int)$enrollment->classId === $classId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Determine if the user can view a specific teacher's timetable.
     */
    public static function canViewTeacherTimetable(
        UserContext $user,
        int $teacherId,
        ?TeacherRepository $teacherRepo = null
    ): bool {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->isTeacher()) {
            return false;
        }

        $authenticatedTeacherId = $user->getTeacherId($teacherRepo);
        return $authenticatedTeacherId !== null && $authenticatedTeacherId === $teacherId;
    }

    /**
     * Determine if the user can view a specific student's timetable.
     */
    public static function canViewStudentTimetable(
        UserContext $user,
        int $studentId,
        ?StudentRepository $studentRepo = null,
        ?ParentRepository $parentRepo = null
    ): bool {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            $authenticatedStudentId = $user->getStudentId($studentRepo);
            return $authenticatedStudentId !== null && $authenticatedStudentId === $studentId;
        }

        if ($user->isParent()) {
            $pRepo = $parentRepo ?? new ParentRepository();
            $parentId = $user->getParentId($pRepo);
            if ($parentId === null) {
                return false;
            }

            return $pRepo->isLinkedToStudent($parentId, $studentId);
        }

        return false;
    }
}
