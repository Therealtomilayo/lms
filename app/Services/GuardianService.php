<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\DTO\ServiceResult;
use App\Models\ParentProfile;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;

/**
 * Application Service for Parent Profiles and Guardian-Student Linking
 */
class GuardianService
{
    private ParentRepository $parentRepository;
    private StudentRepository $studentRepository;
    private UserRepository $userRepository;

    public function __construct(
        ?ParentRepository $parentRepository = null,
        ?StudentRepository $studentRepository = null,
        ?UserRepository $userRepository = null
    ) {
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    /**
     * Create a parent profile for a user account.
     */
    public function createParentProfile(int $userId): ServiceResult
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new ResourceNotFoundException("User #{$userId} not found.");
        }

        if (!$user->hasRole('parent')) {
            throw new DomainRuleException("User '{$user->name}' does not have the 'parent' role.");
        }

        $existing = $this->parentRepository->findByUserId($userId);
        if ($existing) {
            return ServiceResult::success($existing);
        }

        $profile = $this->parentRepository->create($userId);

        return ServiceResult::success($profile);
    }

    /**
     * Link a guardian/parent to a student.
     */
    public function linkGuardian(int $parentId, int $studentId, ?string $relationshipType = null): ServiceResult
    {
        if ($parentId <= 0 || $studentId <= 0) {
            throw new ValidationException(['general' => ['Both Parent and Student must be selected.']]);
        }

        $parent = $this->parentRepository->findById($parentId);
        if (!$parent) {
            throw new ResourceNotFoundException("Parent profile #{$parentId} not found.");
        }

        $student = $this->studentRepository->findById($studentId);
        if (!$student) {
            throw new ResourceNotFoundException("Student profile #{$studentId} not found.");
        }

        $this->parentRepository->linkStudent($parentId, $studentId, $relationshipType);

        return ServiceResult::success([
            'parent' => $this->parentRepository->findById($parentId),
            'student' => $student,
            'relationship_type' => $relationshipType,
        ]);
    }

    /**
     * Unlink a guardian/parent from a student.
     */
    public function unlinkGuardian(int $parentId, int $studentId): ServiceResult
    {
        if ($parentId <= 0 || $studentId <= 0) {
            throw new ValidationException(['general' => ['Both Parent and Student must be selected.']]);
        }

        $this->parentRepository->unlinkStudent($parentId, $studentId);

        return ServiceResult::success(['unlinked' => true]);
    }

    /**
     * Get all students linked to a parent.
     */
    public function getGuardianChildren(int $parentId): array
    {
        return $this->parentRepository->getLinkedStudents($parentId);
    }
}
