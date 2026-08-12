<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\Question;
use App\Repositories\AcademicRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Question Bank Questions and Options
 * Governed strictly by .ai/06-rbac-permissions.md
 */
final class QuestionPolicy
{
    /**
     * Determine if a user can view or manage questions for a specific subject.
     */
    public static function canManageQuestionBank(
        UserContext $userContext,
        int $subjectId,
        ?TeacherRepository $teacherRepository = null,
        ?AcademicRepository $academicRepository = null
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
        $allocations = $academicRepo->findClassSubjectsByTeacherId($teacher->id);

        foreach ($allocations as $alloc) {
            if ($alloc->subjectId === $subjectId && $alloc->isActive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a user can edit/delete a specific Question.
     */
    public static function canEditQuestion(
        UserContext $userContext,
        Question $question,
        ?TeacherRepository $teacherRepository = null,
        ?AcademicRepository $academicRepository = null
    ): bool {
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if (!$userContext->hasRole('teacher')) {
            return false;
        }

        // Must be author or assigned to the subject
        return self::canManageQuestionBank($userContext, $question->subjectId, $teacherRepository, $academicRepository);
    }
}
