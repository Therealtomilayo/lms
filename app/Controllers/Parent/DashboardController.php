<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\ParentService;

/**
 * Controller for Parent Portal Dashboard Overview
 */
class DashboardController extends Controller
{
    private ParentService $parentService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ParentService $parentService = null
    ) {
        parent::__construct($authenticator);
        $this->parentService = $parentService ?? new ParentService();
    }

    /**
     * Display the Parent Portal multi-child overview dashboard.
     * Route: GET /parent/dashboard
     */
    public function index(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $session = $request->getSession();
        $sessionSelectedId = $session ? $session->get('_selected_child_id') : null;
        $queryStudentId = $request->getQuery('student_id');

        $selectedId = $queryStudentId !== null && $queryStudentId !== ''
            ? (int)$queryStudentId
            : ($sessionSelectedId ? (int)$sessionSelectedId : null);

        $dashboardData = $this->parentService->getDashboardData($userContext, $selectedId);

        // If a child is selected, ensure session reflects it
        if ($session && isset($dashboardData['selectedChild']) && $dashboardData['selectedChild']) {
            $session->set('_selected_child_id', $dashboardData['selectedChild']->id);
        }

        return Response::html($this->render('parent/dashboard/index', array_merge($dashboardData, [
            'title' => 'Guardian Portal — Claret LMS',
            'user' => $userContext,
            'csrf_token' => $session ? $session->get('_csrf_token', '') : '',
        ]), 'layouts/parent'));
    }
}
