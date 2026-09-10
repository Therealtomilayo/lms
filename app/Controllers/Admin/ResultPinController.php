<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\ResultPinRepository;
use App\Repositories\StudentRepository;
use App\Services\ResultPinService;

/**
 * Controller for Admin Result Access Scratch-Card PINs, Generation & Printing
 */
class ResultPinController extends Controller
{
    private ResultPinRepository $pinRepository;
    private ResultPinService $pinService;
    private AcademicRepository $academicRepository;
    private StudentRepository $studentRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ResultPinRepository $pinRepository = null,
        ?ResultPinService $pinService = null,
        ?AcademicRepository $academicRepository = null,
        ?StudentRepository $studentRepository = null
    ) {
        parent::__construct($authenticator);
        $this->pinRepository = $pinRepository ?? new ResultPinRepository();
        $this->pinService = $pinService ?? new ResultPinService($this->pinRepository);
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->forbidden('Access Denied: Administrator role required.');
        }

        $status = $request->query('status');
        $termId = $request->query('term_id') ? (int)$request->query('term_id') : null;
        $search = $request->query('q');
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $pins = $this->pinRepository->getPagedPins($limit, $offset, $status, $termId, $search);
        $total = $this->pinRepository->countPins($status, $termId, $search);
        $stats = $this->pinRepository->getSummaryStats();

        $classes = $this->academicRepository->getAllClasses();
        $sessions = $this->academicRepository->getAllSessions();
        $currentSession = $this->academicRepository->getCurrentSession();
        $terms = $currentSession ? $this->academicRepository->getTermsBySession($currentSession->id) : [];

        return $this->view('admin/results/pins/index', [
            'pins' => $pins,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'stats' => $stats,
            'classes' => $classes,
            'sessions' => $sessions,
            'currentSession' => $currentSession,
            'terms' => $terms,
            'status' => $status,
            'termId' => $termId,
            'search' => $search,
            'role' => 'admin',
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'headerTitle' => 'Result Scratch-Card PINs',
            'headerSubtitle' => 'Generate, inspect, print, and track security PINs for terminal report card access.',
        ]);
    }

    public function generate(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->forbidden('Access Denied');
        }

        $mode = $request->post('mode', 'bulk');
        $maxUses = max(1, min(50, (int)$request->post('max_uses', 5)));
        $sessionId = $request->post('session_id') ? (int)$request->post('session_id') : null;
        $termId = $request->post('term_id') ? (int)$request->post('term_id') : null;

        if ($mode === 'class') {
            $classId = (int)$request->post('class_id', 0);
            if ($classId <= 0) {
                return $this->redirectWithError('/admin/results/pins', 'Please select a valid class cohort.');
            }
            $count = $this->pinService->generateForClassCohort(
                $classId,
                $maxUses,
                $sessionId,
                $termId,
                $userContext->getUserId()
            );
            return $this->redirectWithSuccess(
                '/admin/results/pins',
                "Successfully generated {$count} Scratch-Card PINs for enrolled students in the selected class cohort."
            );
        }

        // Bulk unassigned mode
        $qty = max(1, min(500, (int)$request->post('qty', 20)));
        $this->pinService->generateBulkPins(
            $qty,
            $maxUses,
            $sessionId,
            $termId,
            $userContext->getUserId()
        );

        return $this->redirectWithSuccess(
            '/admin/results/pins',
            "Successfully generated {$qty} bulk unassigned Scratch-Card PINs."
        );
    }

    public function print(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->forbidden('Access Denied');
        }

        $limit = max(9, min(90, (int)$request->query('limit', 27)));
        $termId = $request->query('term_id') ? (int)$request->query('term_id') : null;

        $pins = $this->pinRepository->getPagedPins(limit: $limit, offset: 0, status: 'active', termId: $termId);
        $currentSession = $this->academicRepository->getCurrentSession();
        $currentTerm = $this->academicRepository->getCurrentTerm();

        return $this->view('admin/results/pins/print', [
            'pins' => $pins,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'headerTitle' => 'Print Result Scratch Cards',
        ]);
    }

    public function export(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->forbidden('Access Denied');
        }

        $pins = $this->pinRepository->getPagedPins(limit: 5000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Claret_Result_PINs_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['#', 'Serial Number', 'Scratch PIN Code', 'Assigned Student', 'Admission Number', 'Term', 'Uses Allowed', 'Times Used', 'Status', 'Created At']);

        foreach ($pins as $p) {
            fputcsv($output, [
                $p->id,
                $p->serialNumber,
                $p->getFormattedPin(),
                $p->studentName ?? 'Unassigned (Binds on use)',
                $p->studentAdmissionNumber ?? 'N/A',
                $p->termName ?? 'Any Active Term',
                $p->maxUses,
                $p->timesUsed,
                $p->status,
                $p->createdAt,
            ]);
        }

        fclose($output);
        exit;
    }

    public function revoke(Request $request, string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->forbidden('Access Denied');
        }

        $pinId = (int)$id;
        $this->pinRepository->revoke($pinId);

        return $this->redirectWithSuccess('/admin/results/pins', "Scratch-Card PIN #{$pinId} has been revoked.");
    }
}
