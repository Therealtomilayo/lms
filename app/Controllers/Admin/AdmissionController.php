<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\AdmissionApplication;
use App\Repositories\AcademicRepository;
use App\Repositories\AdmissionRepository;
use App\Services\AdmissionService;

/**
 * Controller for Administrative Admissions Management & Conversion Engine
 */
class AdmissionController extends Controller
{
    private AdmissionService $admissionService;
    private AdmissionRepository $admissionRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AdmissionService $admissionService = null,
        ?AdmissionRepository $admissionRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->admissionService = $admissionService ?? new AdmissionService();
        $this->admissionRepo = $admissionRepo ?? new AdmissionRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Applications listing with filtering and search
     */
    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('You do not have administrative permissions to view admissions.');
        }

        $sessionId = $request->query('session_id') ? (int)$request->query('session_id') : null;
        $status = $request->query('status') ?: 'all';
        $search = $request->query('q') ? trim((string)$request->query('q')) : null;

        $filters = [
            'session_id' => $sessionId,
            'status' => $status,
            'search' => $search,
        ];

        $applications = $this->admissionService->getAllApplications($filters);
        $sessions = $this->admissionService->getAllSessions();

        // Calculate counts for badges
        $counts = [
            'all' => count($this->admissionService->getAllApplications(['session_id' => $sessionId])),
            'submitted' => count($this->admissionService->getAllApplications(['session_id' => $sessionId, 'status' => AdmissionApplication::STATUS_SUBMITTED])),
            'under_review' => count($this->admissionService->getAllApplications(['session_id' => $sessionId, 'status' => AdmissionApplication::STATUS_UNDER_REVIEW])),
            'approved' => count($this->admissionService->getAllApplications(['session_id' => $sessionId, 'status' => AdmissionApplication::STATUS_APPROVED])),
            'rejected' => count($this->admissionService->getAllApplications(['session_id' => $sessionId, 'status' => AdmissionApplication::STATUS_REJECTED])),
            'draft' => count($this->admissionService->getAllApplications(['session_id' => $sessionId, 'status' => AdmissionApplication::STATUS_DRAFT])),
        ];

        return $this->view('admin/admissions/index', [
            'applications' => $applications,
            'sessions' => $sessions,
            'selectedSessionId' => $sessionId,
            'selectedStatus' => $status,
            'searchQuery' => $search,
            'counts' => $counts,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    /**
     * Detailed applicant docket dossier review
     */
    public function show(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('You do not have administrative permissions to view admissions.');
        }

        $appId = (int)$id;
        $dossier = $this->admissionService->getApplicationDossier($appId);
        if (!$dossier) {
            Session::setFlash('error', 'Admission application docket not found.');
            return $this->redirect('/admin/admissions/applications');
        }

        return $this->view('admin/admissions/show', [
            'dossier' => $dossier,
            'application' => $dossier['application'],
            'applicant' => $dossier['applicant'],
            'session' => $dossier['session'],
            'wards' => $dossier['wards'],
            'history' => $dossier['history'],
            'classes' => $dossier['classes'],
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    /**
     * Transition application to Under Review
     */
    public function review(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $appId = (int)$id;
        $comment = $request->input('comment') ? trim((string)$request->input('comment')) : null;

        $result = $this->admissionService->updateApplicationStatus(
            $appId,
            AdmissionApplication::STATUS_UNDER_REVIEW,
            $userContext->id,
            $comment
        );

        if ($result->isSuccess()) {
            Session::setFlash('success', 'Application marked as Under Review. Dossier is locked for committee assessment.');
        } else {
            Session::setFlash('error', $result->getMessage());
        }

        return $this->redirect("/admin/admissions/applications/{$appId}");
    }

    /**
     * Approve application docket & execute student matriculation conversion
     */
    public function approve(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $appId = (int)$id;
        $comment = $request->input('comment') ? trim((string)$request->input('comment')) : null;
        $wardClasses = $request->input('ward_classes') ?: [];

        $result = $this->admissionService->approveApplication(
            $appId,
            $userContext->id,
            $comment,
            is_array($wardClasses) ? $wardClasses : []
        );

        if ($result->isSuccess()) {
            $data = $result->getData();
            $count = count($data['enrolled_students'] ?? []);
            Session::setFlash('success', "Application successfully approved! {$count} prospective student(s) matriculated with STD registration numbers, and guardian parent portal account configured.");
        } else {
            Session::setFlash('error', $result->getMessage());
        }

        return $this->redirect("/admin/admissions/applications/{$appId}");
    }

    /**
     * Reject application docket
     */
    public function reject(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $appId = (int)$id;
        $rejectionReason = trim((string)$request->input('rejection_reason', ''));

        if (empty($rejectionReason)) {
            Session::setFlash('error', 'A rejection reason is mandatory to reject an application.');
            return $this->redirect("/admin/admissions/applications/{$appId}");
        }

        $result = $this->admissionService->updateApplicationStatus(
            $appId,
            AdmissionApplication::STATUS_REJECTED,
            $userContext->id,
            $rejectionReason
        );

        if ($result->isSuccess()) {
            Session::setFlash('success', 'Application marked as Rejected. Rejection reason has been logged in docket history.');
        } else {
            Session::setFlash('error', $result->getMessage());
        }

        return $this->redirect("/admin/admissions/applications/{$appId}");
    }

    /**
     * Admission sessions management
     */
    public function sessions(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $sessions = $this->admissionService->getAllSessions();
        $academicSessions = $this->academicRepo->getAllSessions();

        return $this->view('admin/admissions/sessions', [
            'sessions' => $sessions,
            'academicSessions' => $academicSessions,
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    /**
     * Create new admission session
     */
    public function storeSession(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $data = [
            'academic_session_id' => (int)$request->input('academic_session_id'),
            'title' => trim((string)$request->input('title')),
            'application_fee' => (float)$request->input('application_fee', 10000.00),
            'currency' => trim((string)$request->input('currency', 'NGN')),
            'opens_at' => trim((string)$request->input('opens_at')),
            'closes_at' => trim((string)$request->input('closes_at')),
            'is_active' => $request->input('is_active') ? 1 : 0,
            'instructions' => trim((string)$request->input('instructions', '')),
        ];

        $result = $this->admissionService->createSession($data, $userContext->id);

        if ($result->isSuccess()) {
            Session::setFlash('success', 'Admission session configured successfully.');
        } else {
            Session::setFlash('error', $result->getMessage());
        }

        return $this->redirect('/admin/admissions/sessions');
    }

    /**
     * Update existing admission session
     */
    public function updateSession(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $sessionId = (int)$id;
        $data = [];

        if ($request->has('title')) {
            $data['title'] = trim((string)$request->input('title'));
        }
        if ($request->has('application_fee')) {
            $data['application_fee'] = (float)$request->input('application_fee');
        }
        if ($request->has('currency')) {
            $data['currency'] = trim((string)$request->input('currency'));
        }
        if ($request->has('opens_at')) {
            $data['opens_at'] = trim((string)$request->input('opens_at'));
        }
        if ($request->has('closes_at')) {
            $data['closes_at'] = trim((string)$request->input('closes_at'));
        }
        if ($request->has('instructions')) {
            $data['instructions'] = trim((string)$request->input('instructions'));
        }
        if ($request->has('is_active')) {
            $data['is_active'] = $request->input('is_active') ? 1 : 0;
        }

        $result = $this->admissionService->updateSession($sessionId, $data);

        if ($result->isSuccess()) {
            Session::setFlash('success', 'Admission session updated successfully.');
        } else {
            Session::setFlash('error', $result->getMessage());
        }

        return $this->redirect('/admin/admissions/sessions');
    }
}
