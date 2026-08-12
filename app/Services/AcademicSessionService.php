<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\DTO\ServiceResult;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Repositories\AcademicRepository;

/**
 * Business Service for Academic Sessions, Terms, Lifecycles, and Cross-Session Integrity
 */
class AcademicSessionService
{
    private AcademicRepository $repository;

    public function __construct(?AcademicRepository $repository = null)
    {
        $this->repository = $repository ?? new AcademicRepository();
    }

    private function runInTransaction(callable $callback): mixed
    {
        $pdo = $this->repository->getPdo();
        $isNested = $pdo->inTransaction();

        if (!$isNested) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback();
            if (!$isNested) {
                $pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if (!$isNested && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // ==========================================
    // SESSIONS
    // ==========================================

    public function createSession(array $data): ServiceResult
    {
        $name = trim((string)($data['name'] ?? ''));
        $startDate = trim((string)($data['start_date'] ?? ''));
        $endDate = trim((string)($data['end_date'] ?? ''));

        if ($name === '' || $startDate === '' || $endDate === '') {
            throw new ValidationException(['general' => ['Name, start date, and end date are required.']]);
        }

        if (strtotime($startDate) >= strtotime($endDate)) {
            throw new DomainRuleException('Session start date must be before the end date.');
        }

        if ($this->repository->findSessionByName($name) !== null) {
            throw new DomainRuleException("An academic session with name '{$name}' already exists.");
        }

        $session = $this->repository->createSession([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $data['status'] ?? AcademicSession::STATUS_PLANNING,
        ]);

        return ServiceResult::success($session);
    }

    public function updateSession(int $id, array $data): ServiceResult
    {
        $session = $this->repository->findSessionById($id);
        if (!$session) {
            throw new ResourceNotFoundException("Academic session #{$id} not found.");
        }

        if ($session->isArchived()) {
            throw new DomainRuleException('Archived academic sessions cannot be modified.');
        }

        $name = trim((string)($data['name'] ?? $session->name));
        $startDate = trim((string)($data['start_date'] ?? $session->startDate));
        $endDate = trim((string)($data['end_date'] ?? $session->endDate));

        if (strtotime($startDate) >= strtotime($endDate)) {
            throw new DomainRuleException('Session start date must be before the end date.');
        }

        $existing = $this->repository->findSessionByName($name);
        if ($existing !== null && $existing->id !== $id) {
            throw new DomainRuleException("An academic session with name '{$name}' already exists.");
        }

        $this->repository->updateSession($id, [
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return ServiceResult::success($this->repository->findSessionById($id));
    }

    public function makeSessionActive(int $sessionId): ServiceResult
    {
        $session = $this->repository->findSessionById($sessionId);
        if (!$session) {
            throw new ResourceNotFoundException("Academic session #{$sessionId} not found.");
        }

        if ($session->isActive()) {
            return ServiceResult::success($session);
        }

        if (!$session->canTransitionTo(AcademicSession::STATUS_ACTIVE)) {
            throw new DomainRuleException("Cannot transition session from '{$session->status}' to 'active'.");
        }

        $this->runInTransaction(function () use ($sessionId) {
            $this->repository->deactivateOtherSessions($sessionId);
            $this->repository->updateSessionStatus($sessionId, AcademicSession::STATUS_ACTIVE);
        });

        return ServiceResult::success($this->repository->findSessionById($sessionId));
    }

    public function archiveSession(int $sessionId): ServiceResult
    {
        $session = $this->repository->findSessionById($sessionId);
        if (!$session) {
            throw new ResourceNotFoundException("Academic session #{$sessionId} not found.");
        }

        if ($session->isArchived()) {
            return ServiceResult::success($session);
        }

        if (!$session->canTransitionTo(AcademicSession::STATUS_ARCHIVED)) {
            throw new DomainRuleException("Cannot transition session from '{$session->status}' to 'archived'.");
        }

        $this->runInTransaction(function () use ($sessionId) {
            $terms = $this->repository->getTermsBySession($sessionId);
            foreach ($terms as $term) {
                if (!$term->isArchived()) {
                    $this->repository->updateTermStatus($term->id, Term::STATUS_ARCHIVED);
                }
            }
            $this->repository->updateSessionStatus($sessionId, AcademicSession::STATUS_ARCHIVED);
        });

        return ServiceResult::success($this->repository->findSessionById($sessionId));
    }

    // ==========================================
    // TERMS
    // ==========================================

    public function createTerm(array $data): ServiceResult
    {
        $sessionId = (int)($data['session_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $startDate = trim((string)($data['start_date'] ?? ''));
        $endDate = trim((string)($data['end_date'] ?? ''));

        if ($sessionId <= 0 || $name === '' || $startDate === '' || $endDate === '') {
            throw new ValidationException(['general' => ['Session, term name, start date, and end date are required.']]);
        }

        $session = $this->repository->findSessionById($sessionId);
        if (!$session) {
            throw new ResourceNotFoundException("Academic session #{$sessionId} not found.");
        }

        if ($session->isArchived()) {
            throw new DomainRuleException('Cannot add terms to an archived academic session.');
        }

        if (strtotime($startDate) >= strtotime($endDate)) {
            throw new DomainRuleException('Term start date must be before the end date.');
        }

        // Validate term dates fall within or closely align with session dates
        if (strtotime($startDate) < strtotime($session->startDate) || strtotime($endDate) > strtotime($session->endDate)) {
            throw new DomainRuleException("Term dates ({$startDate} - {$endDate}) must fall within the session dates ({$session->startDate} - {$session->endDate}).");
        }

        if ($this->repository->findTermByNameInSession($sessionId, $name) !== null) {
            throw new DomainRuleException("A term with name '{$name}' already exists in this academic session.");
        }

        $term = $this->repository->createTerm([
            'session_id' => $sessionId,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'grading_starts_at' => $data['grading_starts_at'] ?? null,
            'grading_ends_at' => $data['grading_ends_at'] ?? null,
            'status' => $data['status'] ?? Term::STATUS_PLANNING,
        ]);

        return ServiceResult::success($term);
    }

    public function updateTerm(int $id, array $data): ServiceResult
    {
        $term = $this->repository->findTermById($id);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$id} not found.");
        }

        if ($term->isArchived()) {
            throw new DomainRuleException('Archived terms cannot be modified.');
        }

        $session = $this->repository->findSessionById($term->sessionId);
        $name = trim((string)($data['name'] ?? $term->name));
        $startDate = trim((string)($data['start_date'] ?? $term->startDate));
        $endDate = trim((string)($data['end_date'] ?? $term->endDate));

        if (strtotime($startDate) >= strtotime($endDate)) {
            throw new DomainRuleException('Term start date must be before the end date.');
        }

        if ($session && (strtotime($startDate) < strtotime($session->startDate) || strtotime($endDate) > strtotime($session->endDate))) {
            throw new DomainRuleException("Term dates must fall within the session dates ({$session->startDate} - {$session->endDate}).");
        }

        $existing = $this->repository->findTermByNameInSession($term->sessionId, $name);
        if ($existing !== null && $existing->id !== $id) {
            throw new DomainRuleException("A term with name '{$name}' already exists in this session.");
        }

        $this->repository->updateTerm($id, [
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'grading_starts_at' => $data['grading_starts_at'] ?? $term->gradingStartsAt,
            'grading_ends_at' => $data['grading_ends_at'] ?? $term->gradingEndsAt,
        ]);

        return ServiceResult::success($this->repository->findTermById($id));
    }

    public function makeTermActive(int $termId): ServiceResult
    {
        $term = $this->repository->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$termId} not found.");
        }

        $session = $this->repository->findSessionById($term->sessionId);
        if (!$session || !$session->isActive()) {
            throw new DomainRuleException('Cannot activate a term belonging to a non-active academic session.');
        }

        if ($term->isActive()) {
            return ServiceResult::success($term);
        }

        if (!$term->canTransitionTo(Term::STATUS_ACTIVE)) {
            throw new DomainRuleException("Cannot transition term from '{$term->status}' to 'active'.");
        }

        $this->runInTransaction(function () use ($term) {
            $this->repository->deactivateOtherTermsInSession($term->sessionId, $term->id);
            $this->repository->updateTermStatus($term->id, Term::STATUS_ACTIVE);
        });

        return ServiceResult::success($this->repository->findTermById($termId));
    }

    public function transitionTermStatus(int $termId, string $targetStatus): ServiceResult
    {
        $term = $this->repository->findTermById($termId);
        if (!$term) {
            throw new ResourceNotFoundException("Term #{$termId} not found.");
        }

        if ($term->status === $targetStatus) {
            return ServiceResult::success($term);
        }

        if ($targetStatus === Term::STATUS_ACTIVE) {
            return $this->makeTermActive($termId);
        }

        if (!$term->canTransitionTo($targetStatus)) {
            throw new DomainRuleException("Cannot transition term from '{$term->status}' to '{$targetStatus}'.");
        }

        $this->repository->updateTermStatus($termId, $targetStatus);

        return ServiceResult::success($this->repository->findTermById($termId));
    }

    // ==========================================
    // CROSS-SESSION INTEGRITY
    // ==========================================

    public function validateTermBelongsToSession(int $termId, int $sessionId): bool
    {
        $term = $this->repository->findTermById($termId);
        if (!$term) {
            return false;
        }

        return $term->sessionId === $sessionId;
    }
}
