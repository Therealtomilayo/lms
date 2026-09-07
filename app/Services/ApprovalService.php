<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ApprovalRequest;
use App\Repositories\ApprovalRepository;
use PDO;

/**
 * Service orchestrating Super Admin Two-Tier Governance & Approvals (§6, §58.1-§58.3)
 */
class ApprovalService
{
    private ApprovalRepository $approvalRepo;
    private PDO $pdo;

    public function __construct(
        ?ApprovalRepository $approvalRepo = null,
        ?PDO $pdo = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->approvalRepo = $approvalRepo ?? new ApprovalRepository($this->pdo);
    }

    public function stageUserRegistration(
        int $userId,
        string $primaryRole,
        int $requesterId,
        array $userData
    ): ApprovalRequest {
        $requestType = match ($primaryRole) {
            'teacher' => ApprovalRequest::TYPE_TEACHER_REGISTRATION,
            'student' => ApprovalRequest::TYPE_STUDENT_REGISTRATION,
            default   => ApprovalRequest::TYPE_STUDENT_REGISTRATION,
        };

        // Ensure user status is set to pending
        $stmt = $this->pdo->prepare("UPDATE `users` SET `status` = 'pending' WHERE `id` = :id");
        $stmt->execute([':id' => $userId]);

        // If faculty, also ensure teacher record status is inactive/pending until approved
        if ($primaryRole === 'teacher') {
            try {
                $tStmt = $this->pdo->prepare("UPDATE `teachers` SET `status` = 'inactive' WHERE `user_id` = :user_id");
                $tStmt->execute([':user_id' => $userId]);
            } catch (\Throwable) {
                // Ignore if teachers table does not have status column in current environment
            }
        }

        $payload = [
            'name'             => $userData['name'] ?? '',
            'email'            => $userData['email'] ?? '',
            'phone'            => $userData['phone'] ?? null,
            'role'             => $primaryRole,
            'admission_number' => $userData['admission_number'] ?? null,
            'staff_id'         => $userData['staff_id'] ?? null,
            'class_id'         => $userData['class_id'] ?? null,
            'gender'           => $userData['gender'] ?? null,
            'date_of_birth'    => $userData['date_of_birth'] ?? null,
        ];

        return $this->approvalRepo->createRequest(
            $requestType,
            'user',
            $userId,
            $requesterId,
            $payload
        );
    }

    public function stageUserDeletion(
        int $userId,
        int $requesterId,
        string $reason,
        array $userSnapshot = []
    ): ApprovalRequest {
        $payload = array_merge($userSnapshot, [
            'user_id' => $userId,
            'reason'  => trim($reason),
        ]);

        return $this->approvalRepo->createRequest(
            ApprovalRequest::TYPE_USER_DELETION,
            'user',
            $userId,
            $requesterId,
            $payload
        );
    }

    public function stageAdminRoleAssignment(
        int $targetUserId,
        int $requesterId,
        string $reason,
        array $snapshot = []
    ): ApprovalRequest {
        $payload = array_merge($snapshot, [
            'target_user_id' => $targetUserId,
            'role'           => 'admin',
            'reason'         => trim($reason),
        ]);

        return $this->approvalRepo->createRequest(
            ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT,
            'user',
            $targetUserId,
            $requesterId,
            $payload
        );
    }

    public function stageStudentRepetition(
        int $studentId,
        int $classId,
        int $termId,
        int $requesterId,
        string $reason,
        array $metadata = []
    ): ApprovalRequest {
        $payload = array_merge($metadata, [
            'student_id' => $studentId,
            'class_id'   => $classId,
            'term_id'    => $termId,
            'reason'     => $reason,
        ]);

        return $this->approvalRepo->createRequest(
            ApprovalRequest::TYPE_STUDENT_REPETITION,
            'student',
            $studentId,
            $requesterId,
            $payload
        );
    }

    public function approveRequest(int $requestId, int $reviewerId): bool
    {
        $request = $this->approvalRepo->findRequestById($requestId);
        if (!$request || !$request->isPending()) {
            return false;
        }

        if ($request->requestType === ApprovalRequest::TYPE_USER_DELETION) {
            // Execute deletion/deactivation and revoke all sessions
            $stmt = $this->pdo->prepare("UPDATE `users` SET `status` = 'inactive' WHERE `id` = :id");
            $stmt->execute([':id' => $request->entityId]);

            try {
                $rStmt = $this->pdo->prepare("UPDATE `user_roles` SET `is_active` = 0 WHERE `user_id` = :user_id");
                $rStmt->execute([':user_id' => $request->entityId]);
            } catch (\Throwable) {}

            try {
                $sStmt = $this->pdo->prepare("DELETE FROM `user_sessions` WHERE `user_id` = :user_id");
                $sStmt->execute([':user_id' => $request->entityId]);
            } catch (\Throwable) {}

        } elseif ($request->requestType === ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT) {
            // Grant admin role
            $now = date('Y-m-d H:i:s');
            $checkStmt = $this->pdo->prepare("SELECT `id` FROM `user_roles` WHERE `user_id` = :user_id AND `role` = 'admin' LIMIT 1");
            $checkStmt->execute([':user_id' => $request->entityId]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $upStmt = $this->pdo->prepare("UPDATE `user_roles` SET `is_active` = 1 WHERE `id` = :id");
                $upStmt->execute([':id' => $existing['id']]);
            } else {
                $insStmt = $this->pdo->prepare("INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`) VALUES (:user_id, 'admin', 1, :created_at)");
                $insStmt->execute([':user_id' => $request->entityId, ':created_at' => $now]);
            }
        } elseif ($request->entityType === 'user') {
            // Activate the user account (for registration requests)
            $stmt = $this->pdo->prepare("UPDATE `users` SET `status` = 'active' WHERE `id` = :id");
            $stmt->execute([':id' => $request->entityId]);

            if ($request->requestType === ApprovalRequest::TYPE_TEACHER_REGISTRATION) {
                try {
                    $tStmt = $this->pdo->prepare("UPDATE `teachers` SET `status` = 'active' WHERE `user_id` = :user_id");
                    $tStmt->execute([':user_id' => $request->entityId]);
                } catch (\Throwable) {
                    // Ignore column differences
                }
            }
        } elseif ($request->requestType === ApprovalRequest::TYPE_STUDENT_REPETITION) {
            // Mark promotion_status as repeating in student_term_summaries if applicable
            $termId = $request->payload['term_id'] ?? null;
            if ($termId) {
                try {
                    $sStmt = $this->pdo->prepare(
                        "UPDATE `student_term_summaries` SET `promotion_status` = 'repeating' WHERE `student_id` = :student_id AND `term_id` = :term_id"
                    );
                    $sStmt->execute([
                        ':student_id' => $request->entityId,
                        ':term_id'    => $termId,
                    ]);
                } catch (\Throwable) {
                    // Ignore if summary does not exist
                }
            }
        }

        return $this->approvalRepo->approve($requestId, $reviewerId);
    }

    public function rejectRequest(int $requestId, int $reviewerId, string $rejectionReason): bool
    {
        $request = $this->approvalRepo->findRequestById($requestId);
        if (!$request || !$request->isPending()) {
            return false;
        }

        if ($request->requestType === ApprovalRequest::TYPE_USER_DELETION) {
            // Rejection aborts deletion: user remains active in current status
        } elseif ($request->requestType === ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT) {
            // Rejection denies admin role: ensure admin role is not active
            try {
                $rStmt = $this->pdo->prepare("UPDATE `user_roles` SET `is_active` = 0 WHERE `user_id` = :user_id AND `role` = 'admin'");
                $rStmt->execute([':user_id' => $request->entityId]);
            } catch (\Throwable) {}
        } elseif ($request->entityType === 'user') {
            // Leave user as inactive/suspended
            $stmt = $this->pdo->prepare("UPDATE `users` SET `status` = 'inactive' WHERE `id` = :id");
            $stmt->execute([':id' => $request->entityId]);
        }

        return $this->approvalRepo->reject($requestId, $reviewerId, trim($rejectionReason));
    }
}
