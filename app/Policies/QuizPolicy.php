<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Quizzes, Quiz Attempts, and CBT Assessment Engine
 * Governed strictly by .ai/06-rbac-permissions.md
 */
final class QuizPolicy
{
    /**
     * Determine if a user can create a quiz in a class-subject and term.
     */
    public static function canCreateQuiz(
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

        // Cross-Session Invariant Check: term must match class_subject session
        if ($termId !== null) {
            $term = $academicRepo->findTermById($termId);
            if (!$term || $term->sessionId !== $classSubject->sessionId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a user can edit/publish/delete a quiz.
     */
    public static function canEditQuiz(
        UserContext $userContext,
        Quiz $quiz,
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
        $classSubject = $academicRepo->findClassSubjectById($quiz->classSubjectId);
        if (!$classSubject || $classSubject->teacherId !== $teacher->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can view a quiz definition / overview.
     */
    public static function canViewQuiz(
        UserContext $userContext,
        Quiz $quiz,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?ParentRepository $parentRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Teacher check
        if ($userContext->hasRole('teacher')) {
            $teacherRepo = $teacherRepository ?? new TeacherRepository();
            $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
            if ($teacher) {
                $academicRepo = $academicRepository ?? new AcademicRepository();
                $classSubject = $academicRepo->findClassSubjectById($quiz->classSubjectId);
                if ($classSubject && $classSubject->teacherId === $teacher->id) {
                    return true;
                }
            }
        }

        // Student check
        if ($userContext->hasRole('student')) {
            if (!$quiz->isPublished()) {
                return false;
            }

            $studentRepo = $studentRepository ?? new StudentRepository();
            $student = $studentRepo->findByUserId($userContext->id);
            if (!$student) {
                return false;
            }

            $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();
            return $enrollmentRepo->isStudentEnrolledInSubject($student->id, $quiz->classSubjectId);
        }

        // Parent check
        if ($userContext->hasRole('parent')) {
            if (!$quiz->isPublished()) {
                return false;
            }

            $parentRepo = $parentRepository ?? new ParentRepository();
            $parent = $parentRepo->findByUserId($userContext->id);
            if (!$parent) {
                return false;
            }

            $linkedStudents = $parentRepo->getLinkedStudents($parent->id);
            $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();

            foreach ($linkedStudents as $student) {
                if ($enrollmentRepo->isStudentEnrolledInSubject($student->id, $quiz->classSubjectId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if a student can start a new quiz attempt.
     */
    public static function canStartAttempt(
        UserContext $userContext,
        Quiz $quiz,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?QuizRepository $quizRepository = null
    ): bool {
        if (!$userContext->hasRole('student')) {
            return false;
        }

        if (!$quiz->isPublished()) {
            return false;
        }

        $studentRepo = $studentRepository ?? new StudentRepository();
        $student = $studentRepo->findByUserId($userContext->id);
        if (!$student) {
            return false;
        }

        $enrollmentRepo = $enrollmentRepository ?? new EnrollmentRepository();
        if (!$enrollmentRepo->isStudentEnrolledInSubject($student->id, $quiz->classSubjectId)) {
            return false;
        }

        $quizRepo = $quizRepository ?? new QuizRepository();

        // Check if there is an active in-progress attempt already
        $activeAttempt = $quizRepo->getActiveAttempt((int)$quiz->id, $student->id);
        if ($activeAttempt !== null) {
            return false;
        }

        // Check attempt limit
        $attemptCount = $quizRepo->getAttemptCount((int)$quiz->id, $student->id);
        if ($attemptCount >= $quiz->maxAttempts) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can take, view questions, or autosave answers in an active attempt.
     */
    public static function canTakeAttempt(
        UserContext $userContext,
        QuizAttempt $attempt,
        Quiz $quiz,
        ?StudentRepository $studentRepository = null
    ): bool {
        if (!$userContext->hasRole('student')) {
            return false;
        }

        $studentRepo = $studentRepository ?? new StudentRepository();
        $student = $studentRepo->findByUserId($userContext->id);
        if (!$student || $attempt->studentId !== $student->id) {
            return false;
        }

        if (!$attempt->isInProgress()) {
            return false;
        }

        // Server-authoritative timer check
        if ($attempt->hasExpired($quiz->timeLimitMinutes)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if a user can view the result / answers of an attempt.
     */
    public static function canViewAttemptResult(
        UserContext $userContext,
        QuizAttempt $attempt,
        Quiz $quiz,
        ?StudentRepository $studentRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?ParentRepository $parentRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Teacher check
        if ($userContext->hasRole('teacher')) {
            $teacherRepo = $teacherRepository ?? new TeacherRepository();
            $teacher = $teacherRepo->findTeacherByUserId($userContext->id);
            if ($teacher && $quiz->teacherId === $teacher->id) {
                return true;
            }
        }

        // Student check
        if ($userContext->hasRole('student')) {
            $studentRepo = $studentRepository ?? new StudentRepository();
            $student = $studentRepo->findByUserId($userContext->id);
            if ($student && $attempt->studentId === $student->id && $attempt->isSubmitted()) {
                return true;
            }
        }

        // Parent check
        if ($userContext->hasRole('parent')) {
            $parentRepo = $parentRepository ?? new ParentRepository();
            $parent = $parentRepo->findByUserId($userContext->id);
            if ($parent && $parentRepo->isLinkedToStudent($parent->id, $attempt->studentId) && $attempt->isSubmitted()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a user can grade short answers or reset an attempt.
     */
    public static function canGradeOrResetAttempt(
        UserContext $userContext,
        Quiz $quiz,
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
        if (!$teacher || $quiz->teacherId !== $teacher->id) {
            return false;
        }

        return true;
    }
}
