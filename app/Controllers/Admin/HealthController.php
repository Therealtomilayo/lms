<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\SystemPolicy;
use App\Services\HealthService;

/**
 * Controller for Deep System Health & Diagnostics Administration
 */
class HealthController extends Controller
{
    private HealthService $healthService;
    private SystemPolicy $policy;

    public function __construct(
        ?HealthService $healthService = null,
        ?SystemPolicy $policy = null
    ) {
        $this->healthService = $healthService ?? new HealthService();
        $this->policy = $policy ?? new SystemPolicy();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$this->policy->viewHealth($userContext)) {
            return $this->forbidden('You are not authorized to view system health diagnostics.');
        }

        $result = $this->healthService->checkDeepHealth();

        if ($request->isJson() || $request->isAjax()) {
            return Response::json($result->toArray(), $result->isHealthy() ? 200 : 503);
        }

        return Response::html($this->render('admin/health/index', [
            'title' => 'System Health & Diagnostics — Claret Portal',
            'headerTitle' => 'System Health & Diagnostics',
            'health' => $result,
        ], 'layouts/admin'));
    }
}
