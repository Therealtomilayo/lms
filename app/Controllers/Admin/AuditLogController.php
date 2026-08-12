<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\SystemPolicy;
use App\Repositories\AuditLogRepository;

/**
 * Controller for Immutable Audit Log Explorer
 */
class AuditLogController extends Controller
{
    private AuditLogRepository $repository;
    private SystemPolicy $policy;

    public function __construct(
        ?AuditLogRepository $repository = null,
        ?SystemPolicy $policy = null
    ) {
        $this->repository = $repository ?? new AuditLogRepository();
        $this->policy = $policy ?? new SystemPolicy();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$this->policy->viewAuditLogs($userContext)) {
            return $this->forbidden('You are not authorized to view system audit logs.');
        }

        $page = (int)$request->query('page', 1);
        $action = (string)$request->query('action', '');
        $entityType = (string)$request->query('entity_type', '');

        $filters = array_filter([
            'action' => $action,
            'entity_type' => $entityType,
        ]);

        $paginated = $this->repository->paginate($page, 25, $filters);
        $actions = $this->repository->getDistinctActions();
        $entityTypes = $this->repository->getDistinctEntityTypes();

        if ($request->isJson() || $request->isAjax()) {
            return Response::json($paginated);
        }

        return $this->view('admin/audit_logs/index', [
            'title' => 'Audit Trail — Claret LMS',
            'headerTitle' => 'System Audit Logs',
            'logs' => $paginated['data'],
            'pagination' => $paginated,
            'actions' => $actions,
            'entityTypes' => $entityTypes,
            'filters' => $filters,
        ], 'layouts/admin');
    }
}
