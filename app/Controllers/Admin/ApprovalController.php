<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ApprovalRepository;
use App\Services\ApprovalService;

/**
 * Controller for Super Admin Two-Tier Governance & Approvals Queue (ADMIN-30, §6, §58.1-§58.3)
 */
class ApprovalController extends Controller
{
    private ApprovalRepository $approvalRepo;
    private ApprovalService $approvalService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ApprovalRepository $approvalRepo = null,
        ?ApprovalService $approvalService = null
    ) {
        parent::__construct($authenticator);
        $this->approvalRepo = $approvalRepo ?? new ApprovalRepository();
        $this->approvalService = $approvalService ?? new ApprovalService($this->approvalRepo);
    }

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !$userContext->hasRole('super_admin')) {
            throw new AuthorizationException('Super Administrator access required to review approval queue.');
        }

        $statusFilter = $request->get('status', 'pending');
        if ($statusFilter === 'all') {
            $statusFilter = null;
        }

        $typeFilter = $request->get('type');
        if ($typeFilter === 'all') {
            $typeFilter = null;
        }

        $requests = $this->approvalRepo->getRequests(
            status: $statusFilter,
            requestType: $typeFilter,
            limit: 100
        );

        $counts = $this->approvalRepo->getSummaryCounts();

        return $this->view('admin/approvals/index', [
            'requests' => $requests,
            'counts' => $counts,
            'selectedStatus' => $statusFilter ?? 'all',
            'selectedType' => $typeFilter ?? 'all',
        ]);
    }

    public function approve(Request $request, int|string $id): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !$userContext->hasRole('super_admin')) {
            throw new AuthorizationException('Super Administrator access required.');
        }

        $requestId = (int)$id;
        $success = $this->approvalService->approveRequest($requestId, $userContext->getUserId());

        if (!$success) {
            return $this->redirectWithError(
                '/admin/approvals',
                'Failed to approve request. The item may have already been resolved.'
            );
        }

        return $this->redirectWithSuccess(
            '/admin/approvals',
            'Request approved successfully. The account or promotion record has been activated.'
        );
    }

    public function reject(Request $request, int|string $id): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !$userContext->hasRole('super_admin')) {
            throw new AuthorizationException('Super Administrator access required.');
        }

        $requestId = (int)$id;
        $reason = trim((string)$request->post('rejection_reason', ''));

        if ($reason === '') {
            return $this->redirectWithError(
                '/admin/approvals',
                'A specific rejection reason is mandatory so the originating admin can address the deficiency.'
            );
        }

        $success = $this->approvalService->rejectRequest($requestId, $userContext->getUserId(), $reason);

        if (!$success) {
            return $this->redirectWithError(
                '/admin/approvals',
                'Failed to reject request. The item may have already been resolved.'
            );
        }

        return $this->redirectWithSuccess(
            '/admin/approvals',
            'Request rejected. Reason recorded for administrative notification.'
        );
    }
}
