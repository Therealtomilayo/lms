<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\FileRecord;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\ContentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Protected Files
 * Governed strictly by .ai/06-rbac-permissions.md
 */
final class FilePolicy
{
    /**
     * Determine if a user can access and download a protected file.
     * Evaluates ownership/scope predicate for the parent owning domain entity.
     */
    public static function userCanAccessFile(
        UserContext $userContext,
        FileRecord $file,
        ?ContentRepository $contentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?ParentRepository $parentRepository = null,
        ?AssignmentRepository $assignmentRepository = null
    ): bool {
        if ($file->isDeleted()) {
            return false;
        }

        // Super Admin & Admin have full access
        if ($userContext->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Direct uploader can access
        if ($file->uploadedBy === $userContext->id) {
            return true;
        }

        // Evaluate based on owning resource type
        if ($file->ownerType === 'content_item') {
            $contentRepo = $contentRepository ?? new ContentRepository();
            $contentItem = $contentRepo->findById($file->ownerId);

            if (!$contentItem) {
                return false;
            }

            return ContentPolicy::canViewContent(
                $userContext,
                $contentItem,
                null,
                $academicRepository,
                $teacherRepository,
                $studentRepository,
                $enrollmentRepository,
                $parentRepository
            );
        }

        if ($file->ownerType === 'assignment') {
            $assignmentRepo = $assignmentRepository ?? new AssignmentRepository();
            $assignment = $assignmentRepo->findById($file->ownerId);

            if (!$assignment) {
                return false;
            }

            return AssignmentPolicy::canViewAssignment(
                $userContext,
                $assignment,
                $academicRepository,
                $teacherRepository,
                $studentRepository,
                $enrollmentRepository,
                $parentRepository
            );
        }

        if ($file->ownerType === 'assignment_submission') {
            $assignmentRepo = $assignmentRepository ?? new AssignmentRepository();
            $submission = $assignmentRepo->findSubmissionById($file->ownerId);

            if (!$submission) {
                return false;
            }

            return AssignmentPolicy::canViewSubmission(
                $userContext,
                $submission,
                $assignmentRepo,
                $academicRepository,
                $teacherRepository,
                $studentRepository,
                $parentRepository
            );
        }

        return false;
    }
}
