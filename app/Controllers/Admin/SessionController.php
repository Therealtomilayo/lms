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
 * Controller for Academic Session Administration
 */
class SessionController extends Controller
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
            return $this->forbidden('You are not authorized to manage academic sessions.');
        }

        $sessions = $this->repository->getAllSessions();

        return $this->view('admin/sessions/index', [
            'title' => 'Academic Sessions — Claret LMS',
            'headerTitle' => 'Academic Sessions',
            'sessions' => $sessions,
        ], 'layouts/admin');
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return $this->forbidden('You are not authorized to create academic sessions.');
        }

        try {
            $result = $this->sessionService->createSession([
                'name' => $request->post('name'),
                'start_date' => $request->post('start_date'),
                'end_date' => $request->post('end_date'),
                'status' => $request->post('status', 'planning'),
            ]);

            return $this->redirectWithSuccess('/admin/sessions', 'Academic session created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/sessions', $e->getMessage());
        }
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)$id;

        try {
            $this->sessionService->updateSession($id, [
                'name' => $request->post('name'),
                'start_date' => $request->post('start_date'),
                'end_date' => $request->post('end_date'),
            ]);

            return $this->redirectWithSuccess('/admin/sessions', 'Academic session updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError('/admin/sessions', $e->getMessage());
        }
    }

    public function makeCurrent(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));

        try {
            $this->sessionService->makeSessionActive($id);
            return $this->redirectWithSuccess('/admin/sessions', 'Academic session activated as current session.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/admin/sessions', $e->getMessage());
        }
    }

    public function archive(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageSessionsAndTerms($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));

        try {
            $this->sessionService->archiveSession($id);
            return $this->redirectWithSuccess('/admin/sessions', 'Academic session and its terms have been archived.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/admin/sessions', $e->getMessage());
        }
    }
}
