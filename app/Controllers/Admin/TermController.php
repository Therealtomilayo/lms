<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Services\AcademicSessionService;

/**
 * Controller for Term Administration
 */
class TermController extends Controller
{
    private AcademicSessionService $sessionService;
    private AcademicRepository $repository;

    public function __construct(
        ?AcademicSessionService $sessionService = null,
        ?AcademicRepository $repository = null
    ) {
        $this->sessionService = $sessionService ?? new AcademicSessionService();
        $this->repository = $repository ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return $this->forbidden('You are not authorized to manage terms.');
        }

        $sessions = $this->repository->getAllSessions();
        $activeSession = $this->repository->findActiveSession();

        $selectedSessionId = (int)$request->query('session_id', $activeSession?->id ?? ($sessions[0]->id ?? 0));
        $terms = $selectedSessionId > 0 ? $this->repository->getTermsBySession($selectedSessionId) : [];

        return $this->view('admin/terms/index', [
            'title' => 'Academic Terms — Claret LMS',
            'headerTitle' => 'Academic Terms',
            'sessions' => $sessions,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
        ], 'layouts/admin');
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return $this->forbidden('You are not authorized to create terms.');
        }

        $sessionId = (int)$request->post('session_id', 0);

        try {
            $this->sessionService->createTerm([
                'session_id' => $sessionId,
                'name' => $request->post('name'),
                'start_date' => $request->post('start_date'),
                'end_date' => $request->post('end_date'),
                'grading_starts_at' => $request->post('grading_starts_at') ?: null,
                'grading_ends_at' => $request->post('grading_ends_at') ?: null,
                'status' => $request->post('status', 'planning'),
            ]);

            return $this->redirectWithSuccess("/admin/terms?session_id={$sessionId}", 'Term created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError("/admin/terms?session_id={$sessionId}", $e->getMessage());
        }
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)$id;
        $sessionId = (int)$request->post('session_id', 0);

        try {
            $this->sessionService->updateTerm($id, [
                'name' => $request->post('name'),
                'start_date' => $request->post('start_date'),
                'end_date' => $request->post('end_date'),
                'grading_starts_at' => $request->post('grading_starts_at') ?: null,
                'grading_ends_at' => $request->post('grading_ends_at') ?: null,
            ]);

            return $this->redirectWithSuccess("/admin/terms?session_id={$sessionId}", 'Term updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError("/admin/terms?session_id={$sessionId}", $e->getMessage());
        }
    }

    public function makeCurrent(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));
        $term = $this->repository->findTermById($id);
        $sessionId = $term?->sessionId ?? 0;

        try {
            $this->sessionService->makeTermActive($id);
            return $this->redirectWithSuccess("/admin/terms?session_id={$sessionId}", 'Term activated as current active term.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError("/admin/terms?session_id={$sessionId}", $e->getMessage());
        }
    }

    public function status(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));
        $targetStatus = (string)$request->post('status', '');
        $term = $this->repository->findTermById($id);
        $sessionId = $term?->sessionId ?? 0;

        try {
            $this->sessionService->transitionTermStatus($id, $targetStatus);
            return $this->redirectWithSuccess("/admin/terms?session_id={$sessionId}", "Term status updated to '{$targetStatus}'.");
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError("/admin/terms?session_id={$sessionId}", $e->getMessage());
        }
    }
}
