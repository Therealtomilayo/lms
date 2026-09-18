<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\ClassDiscussion;
use App\Repositories\AcademicRepository;
use App\Repositories\DiscussionRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

class DiscussionService
{
    private DiscussionRepository $discussionRepo;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;

    public function __construct(
        ?DiscussionRepository $discussionRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?StudentRepository $studentRepo = null,
        ?ParentRepository $parentRepo = null
    ) {
        $this->discussionRepo = $discussionRepo ?? new DiscussionRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
    }

    /**
     * Check if user is authorized to access a class subject's discussion feed.
     */
    public function authorizeAccess(int $classSubjectId, UserContext $actor): void
    {
        if ($actor->hasAnyRole(['super_admin', 'admin'])) {
            return;
        }

        $userId = (int)($actor->id ?? $actor->userId ?? 0);

        if ($actor->hasRole('teacher')) {
            $teacher = $this->teacherRepo->findTeacherByUserId($userId);
            if (!$teacher) {
                throw new AuthorizationException('Teacher profile not found.');
            }
            $cs = $this->academicRepo->findClassSubjectById($classSubjectId);
            if (!$cs || (int)$cs->teacherId !== (int)$teacher->id) {
                throw new AuthorizationException('You are not assigned to this class subject discussion.');
            }
            return;
        }

        if ($actor->hasRole('student')) {
            $student = $this->studentRepo->findByUserId($userId);
            if (!$student) {
                throw new AuthorizationException('Student profile not found.');
            }

            if (!$this->isStudentEnrolledInClassSubject($student->id, $classSubjectId)) {
                throw new AuthorizationException('You are not enrolled in this class subject.');
            }
            return;
        }

        if ($actor->hasRole('parent')) {
            $parent = $this->parentRepo->findByUserId($userId);
            if (!$parent) {
                throw new AuthorizationException('Parent profile not found.');
            }

            $linkedStudents = $this->parentRepo->getLinkedStudents($parent->id);
            $hasChildEnrolled = false;
            foreach ($linkedStudents as $child) {
                if ($this->isStudentEnrolledInClassSubject((int)$child->id, $classSubjectId)) {
                    $hasChildEnrolled = true;
                    break;
                }
            }

            if (!$hasChildEnrolled) {
                throw new AuthorizationException('None of your linked active children are enrolled in this class subject.');
            }
            return;
        }

        throw new AuthorizationException('Access denied to class discussion.');
    }

    /**
     * Determine if actor can moderate (pin, lock, delete topics/replies).
     */
    public function canModerate(int $classSubjectId, UserContext $actor): bool
    {
        if ($actor->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($actor->hasRole('teacher')) {
            $userId = (int)($actor->id ?? $actor->userId ?? 0);
            $teacher = $this->teacherRepo->findTeacherByUserId($userId);
            if (!$teacher) {
                return false;
            }
            $cs = $this->academicRepo->findClassSubjectById($classSubjectId);
            return $cs && (int)$cs->teacherId === (int)$teacher->id;
        }

        return false;
    }

    /**
     * Get discussion topics for a class subject.
     */
    public function getDiscussions(int $classSubjectId, UserContext $actor, int $limit = 30, int $offset = 0): array
    {
        $this->authorizeAccess($classSubjectId, $actor);

        $cs = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$cs) {
            throw new ResourceNotFoundException('Class subject not found.');
        }

        $discussions = $this->discussionRepo->getDiscussionsBySubject($classSubjectId, $limit, $offset);
        $totalCount = $this->discussionRepo->countDiscussionsBySubject($classSubjectId);

        return [
            'class_subject' => $cs,
            'discussions' => $discussions,
            'total_count' => $totalCount,
            'can_moderate' => $this->canModerate($classSubjectId, $actor),
            'can_post' => !$actor->hasRole('parent'), // Parents have read-only visibility per SRS §47
        ];
    }

    /**
     * Get single discussion topic with full reply thread.
     */
    public function getDiscussionThread(int $discussionId, UserContext $actor): array
    {
        $discussion = $this->discussionRepo->getDiscussionWithReplies($discussionId);
        if (!$discussion) {
            throw new ResourceNotFoundException('Discussion topic not found.');
        }

        $this->authorizeAccess($discussion->classSubjectId, $actor);

        $cs = $this->academicRepo->findClassSubjectById($discussion->classSubjectId);
        $canModerate = $this->canModerate($discussion->classSubjectId, $actor);
        $canReply = !$actor->hasRole('parent') && (!$discussion->isLocked || $canModerate);

        return [
            'discussion' => $discussion,
            'replies' => $discussion->replies,
            'class_subject' => $cs,
            'can_moderate' => $canModerate,
            'can_reply' => $canReply,
        ];
    }

    /**
     * Alias for getDiscussionThread.
     */
    public function getDiscussionWithReplies(int $discussionId, UserContext $actor): array
    {
        return $this->getDiscussionThread($discussionId, $actor);
    }

    /**
     * Create a new discussion topic in a class subject.
     */
    public function createDiscussion(int $classSubjectId, string $title, string $content, UserContext $actor): int
    {
        $this->authorizeAccess($classSubjectId, $actor);

        if ($actor->hasRole('parent')) {
            throw new AuthorizationException('Parents have observation-only access to class discussion feeds.');
        }

        $trimmedTitle = trim($title);
        $trimmedContent = trim($content);

        $errors = [];
        if (empty($trimmedTitle) || mb_strlen($trimmedTitle) < 3) {
            $errors['title'] = 'Topic title must be at least 3 characters.';
        } elseif (mb_strlen($trimmedTitle) > 255) {
            $errors['title'] = 'Topic title cannot exceed 255 characters.';
        }

        if (empty($trimmedContent) || mb_strlen($trimmedContent) < 5) {
            $errors['content'] = 'Topic content must be at least 5 characters.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $userId = (int)($actor->id ?? $actor->userId ?? 0);

        return $this->discussionRepo->createDiscussion($classSubjectId, $userId, $trimmedTitle, $trimmedContent);
    }

    /**
     * Post a reply to a discussion topic.
     */
    public function addReply(int $discussionId, string $content, UserContext $actor): int
    {
        $discussion = $this->discussionRepo->findDiscussionById($discussionId);
        if (!$discussion) {
            throw new ResourceNotFoundException('Discussion topic not found.');
        }

        $this->authorizeAccess($discussion->classSubjectId, $actor);

        if ($actor->hasRole('parent')) {
            throw new AuthorizationException('Parents have observation-only access and cannot post replies.');
        }

        $canModerate = $this->canModerate($discussion->classSubjectId, $actor);
        if ($discussion->isLocked && !$canModerate) {
            throw new DomainRuleException('This discussion topic has been locked by the teacher and is no longer accepting replies.');
        }

        $trimmedContent = trim($content);
        if (empty($trimmedContent)) {
            throw new ValidationException(['content' => 'Reply content cannot be empty.']);
        }

        $userId = (int)($actor->id ?? $actor->userId ?? 0);

        return $this->discussionRepo->addReply($discussionId, $userId, $trimmedContent);
    }

    /**
     * Alias for addReply.
     */
    public function createReply(int $discussionId, string $content, UserContext $actor): int
    {
        return $this->addReply($discussionId, $content, $actor);
    }

    public function togglePin(int $discussionId, bool $pinned, UserContext $actor): void
    {
        $discussion = $this->discussionRepo->findDiscussionById($discussionId);
        if (!$discussion) {
            throw new ResourceNotFoundException('Discussion topic not found.');
        }

        if (!$this->canModerate($discussion->classSubjectId, $actor)) {
            throw new AuthorizationException('You are not authorized to pin/unpin this discussion.');
        }

        $this->discussionRepo->togglePin($discussionId, $pinned);
    }

    public function toggleLock(int $discussionId, bool $locked, UserContext $actor): void
    {
        $discussion = $this->discussionRepo->findDiscussionById($discussionId);
        if (!$discussion) {
            throw new ResourceNotFoundException('Discussion topic not found.');
        }

        if (!$this->canModerate($discussion->classSubjectId, $actor)) {
            throw new AuthorizationException('You are not authorized to lock/unlock this discussion.');
        }

        $this->discussionRepo->toggleLock($discussionId, $locked);
    }

    public function deleteDiscussion(int $discussionId, UserContext $actor): bool
    {
        $discussion = $this->discussionRepo->findDiscussionById($discussionId);
        if (!$discussion) {
            throw new ResourceNotFoundException('Discussion topic not found.');
        }

        $userId = (int)($actor->id ?? $actor->userId ?? 0);
        $isAuthor = $discussion->userId === $userId;
        $canModerate = $this->canModerate($discussion->classSubjectId, $actor);

        if (!$canModerate && !$isAuthor) {
            throw new AuthorizationException('You are not authorized to delete this discussion.');
        }

        $this->discussionRepo->deleteDiscussion($discussionId);

        return true;
    }

    public function deleteReply(int $replyId, UserContext $actor): bool
    {
        $reply = $this->discussionRepo->findReplyById($replyId);
        if (!$reply) {
            throw new ResourceNotFoundException('Reply not found.');
        }

        $discussion = $this->discussionRepo->findDiscussionById($reply->discussionId);
        if (!$discussion) {
            throw new ResourceNotFoundException('Discussion topic not found.');
        }

        $userId = (int)($actor->id ?? $actor->userId ?? 0);
        $isAuthor = $reply->userId === $userId;
        $canModerate = $this->canModerate($discussion->classSubjectId, $actor);

        if (!$canModerate && !$isAuthor) {
            throw new AuthorizationException('You are not authorized to delete this reply.');
        }

        $this->discussionRepo->deleteReply($replyId);

        return true;
    }

    private function isStudentEnrolledInClassSubject(int $studentId, int $classSubjectId): bool
    {
        $cs = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$cs) {
            return false;
        }

        try {
            $stmt = $this->academicRepo->getPdo()->prepare('
                SELECT 1 FROM `student_subject_enrollments`
                WHERE `student_id` = :student_id AND `class_subject_id` = :class_subject_id AND `status` = "active"
                LIMIT 1
            ');
            $stmt->execute([':student_id' => $studentId, ':class_subject_id' => $classSubjectId]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            $student = $this->studentRepo->findById($studentId);
            $classId = (int)($cs->classId ?? ($cs->schoolClass?->id ?? 0));
            return $student && (int)$student->currentClassId === $classId;
        }
    }
}
