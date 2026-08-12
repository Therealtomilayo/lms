<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\Announcement;
use App\Policies\AnnouncementPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use PDO;

/**
 * Announcement Application Service
 */
class AnnouncementService
{
    private AnnouncementRepository $announcementRepo;
    private AnnouncementPolicy $policy;
    private AuditService $auditService;
    private AcademicRepository $academicRepo;
    private ?PDO $db;

    public function __construct(
        ?AnnouncementRepository $announcementRepo = null,
        ?AnnouncementPolicy $policy = null,
        ?AuditService $auditService = null,
        ?AcademicRepository $academicRepo = null,
        ?PDO $db = null
    ) {
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository($db);
        $this->policy = $policy ?? new AnnouncementPolicy($db);
        $this->auditService = $auditService ?? new AuditService($db);
        $this->academicRepo = $academicRepo ?? new AcademicRepository($db);
        $this->db = $db;
    }

    public function createAnnouncement(array $data, UserContext $user): Announcement
    {
        $title = trim((string)($data['title'] ?? ''));
        $body = trim((string)($data['body'] ?? ''));
        $scope = (string)($data['scope'] ?? 'school');
        $scopeId = isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? (int)$data['scope_id'] : null;

        $errors = [];
        if (empty($title)) {
            $errors['title'] = 'Announcement title is required.';
        }
        if (empty($body)) {
            $errors['body'] = 'Announcement body content is required.';
        }
        if (!in_array($scope, ['school', 'class', 'class_subject'], true)) {
            $errors['scope'] = 'Invalid announcement scope.';
        }
        if ($scope !== 'school' && ($scopeId === null || $scopeId <= 0)) {
            $errors['scope_id'] = 'Scope target selection is required for class or subject announcements.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        if (!$this->policy->canCreate($user, $scope, $scopeId)) {
            throw new AuthorizationException('You are not authorized to create an announcement for this target scope.');
        }

        $publishedAt = isset($data['published_at']) && $data['published_at'] !== '' ? (string)$data['published_at'] : date('Y-m-d H:i:s');
        $expiresAt = isset($data['expires_at']) && $data['expires_at'] !== '' ? (string)$data['expires_at'] : null;

        $id = $this->announcementRepo->create([
            'author_id' => $user->getUserId(),
            'scope' => $scope,
            'scope_id' => $scopeId,
            'title' => $title,
            'body' => $body,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
        ]);

        $this->auditService->log(
            action: 'announcement.created',
            entityType: 'announcement',
            entityId: $id,
            actorUserId: $user->getUserId(),
            before: null,
            after: ['title' => $title, 'scope' => $scope, 'scope_id' => $scopeId],
            metadata: null
        );

        $created = $this->announcementRepo->findById($id);
        if (!$created) {
            throw new ResourceNotFoundException('Failed to retrieve newly created announcement.');
        }

        return $created;
    }

    public function updateAnnouncement(int $id, array $data, UserContext $user): Announcement
    {
        $announcement = $this->announcementRepo->findById($id);
        if (!$announcement) {
            throw new ResourceNotFoundException("Announcement #{$id} not found.");
        }

        if (!$this->policy->canManage($user, $announcement)) {
            throw new AuthorizationException('You are not authorized to modify this announcement.');
        }

        $title = trim((string)($data['title'] ?? $announcement->title));
        $body = trim((string)($data['body'] ?? $announcement->body));
        $scope = (string)($data['scope'] ?? $announcement->scope);
        $scopeId = isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? (int)$data['scope_id'] : $announcement->scopeId;

        $errors = [];
        if (empty($title)) {
            $errors['title'] = 'Announcement title is required.';
        }
        if (empty($body)) {
            $errors['body'] = 'Announcement body content is required.';
        }
        if (!in_array($scope, ['school', 'class', 'class_subject'], true)) {
            $errors['scope'] = 'Invalid announcement scope.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $publishedAt = isset($data['published_at']) && $data['published_at'] !== '' ? (string)$data['published_at'] : $announcement->publishedAt;
        $expiresAt = isset($data['expires_at']) && $data['expires_at'] !== '' ? (string)$data['expires_at'] : $announcement->expiresAt;

        $this->announcementRepo->update($id, [
            'scope' => $scope,
            'scope_id' => $scopeId,
            'title' => $title,
            'body' => $body,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
        ]);

        $this->auditService->log(
            action: 'announcement.updated',
            entityType: 'announcement',
            entityId: $id,
            actorUserId: $user->getUserId(),
            before: ['title' => $announcement->title, 'body' => $announcement->body],
            after: ['title' => $title, 'body' => $body],
            metadata: null
        );

        return $this->announcementRepo->findById($id);
    }

    public function deleteAnnouncement(int $id, UserContext $user): bool
    {
        $announcement = $this->announcementRepo->findById($id);
        if (!$announcement) {
            throw new ResourceNotFoundException("Announcement #{$id} not found.");
        }

        if (!$this->policy->canManage($user, $announcement)) {
            throw new AuthorizationException('You are not authorized to delete this announcement.');
        }

        $this->announcementRepo->delete($id);

        $this->auditService->log(
            action: 'announcement.deleted',
            entityType: 'announcement',
            entityId: $id,
            actorUserId: $user->getUserId(),
            before: ['title' => $announcement->title, 'scope' => $announcement->scope],
            after: null,
            metadata: null
        );

        return true;
    }

    /**
     * Get targeted active announcements feed for user.
     *
     * @return array<int, Announcement>
     */
    public function getUserFeed(UserContext $user, ?int $studentId = null, int $limit = 50, int $offset = 0): array
    {
        return $this->announcementRepo->getFeedForUser($user, $studentId, $limit, $offset);
    }

    public function getUnreadCount(UserContext $user, ?int $studentId = null): int
    {
        return $this->announcementRepo->getUnreadCount($user, $studentId);
    }

    public function markAsRead(int $announcementId, UserContext $user): bool
    {
        $announcement = $this->announcementRepo->findById($announcementId);
        if (!$announcement) {
            throw new ResourceNotFoundException("Announcement #{$announcementId} not found.");
        }

        if (!$this->policy->canView($user, $announcement)) {
            throw new AuthorizationException('You do not have access to this announcement.');
        }

        return $this->announcementRepo->markAsRead($announcementId, $user->getUserId());
    }
}
