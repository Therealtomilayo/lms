<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Services\PromotionService;

/**
 * Controller for Student Promotions, Cohort Advancement & Terminal Graduation (SRS §17, §18, §58.3)
 */
class PromotionController extends Controller
{
    private PromotionService $promotionService;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?PromotionService $promotionService = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->promotionService = $promotionService ?? new PromotionService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Overview dashboard listing all class cohorts and 3rd-term readiness.
     */
    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || (!$userContext->isSuperAdmin() && !$userContext->isAdmin())) {
            throw new AuthorizationException('Administrator access required.');
        }

        $sessions = $this->academicRepo->getAllSessions();
        $currentSession = $this->academicRepo->getCurrentSession() ?? ($sessions[0] ?? null);

        $selectedSessionId = (int)($request->get('session_id') ?? ($currentSession?->id ?? 0));
        if ($selectedSessionId <= 0 && $currentSession) {
            $selectedSessionId = $currentSession->id;
        }

        $overview = $this->promotionService->getSessionPromotionOverview($selectedSessionId);

        return $this->view('admin/promotions/index', [
            'sessions'          => $sessions,
            'currentSession'    => $currentSession,
            'selectedSessionId' => $selectedSessionId,
            'overview'          => $overview,
        ]);
    }

    /**
     * Class cohort evaluation matrix & advancement staged controls.
     */
    public function classCohort(Request $request, int|string $classId): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || (!$userContext->isSuperAdmin() && !$userContext->isAdmin())) {
            throw new AuthorizationException('Administrator access required.');
        }

        $cId = (int)$classId;
        $sessions = $this->academicRepo->getAllSessions();
        $currentSession = $this->academicRepo->getCurrentSession() ?? ($sessions[0] ?? null);

        $selectedSessionId = (int)($request->get('session_id') ?? ($currentSession?->id ?? 0));
        if ($selectedSessionId <= 0 && $currentSession) {
            $selectedSessionId = $currentSession->id;
        }

        $cohortEvaluation = $this->promotionService->evaluateClassCohort($cId, $selectedSessionId);
        $allClasses = $this->academicRepo->getAllClasses();

        // Target sessions (typically current or upcoming session)
        return $this->view('admin/promotions/class', [
            'classId'           => $cId,
            'sessions'          => $sessions,
            'selectedSessionId' => $selectedSessionId,
            'cohort'            => $cohortEvaluation,
            'allClasses'        => $allClasses,
            'isSuperAdmin'      => $userContext->isSuperAdmin(),
        ]);
    }

    /**
     * Submit a borderline student repetition request to Super Admin approval queue (SRS §58.3).
     */
    public function stageRepetition(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || (!$userContext->isSuperAdmin() && !$userContext->isAdmin())) {
            throw new AuthorizationException('Administrator access required.');
        }

        $studentId = (int)$request->post('student_id', 0);
        $classId = (int)$request->post('class_id', 0);
        $sessionId = (int)$request->post('session_id', 0);
        $reason = trim((string)$request->post('reason', ''));

        if ($studentId <= 0 || $classId <= 0 || $sessionId <= 0) {
            return $this->redirectWithError(
                "/admin/promotions/class/{$classId}?session_id={$sessionId}",
                'Missing required student or cohort identifiers.'
            );
        }

        if ($reason === '') {
            return $this->redirectWithError(
                "/admin/promotions/class/{$classId}?session_id={$sessionId}",
                'A detailed academic reason is required when staging a borderline student for repetition review.'
            );
        }

        $result = $this->promotionService->stageBorderlineRepetition(
            $studentId,
            $classId,
            $sessionId,
            $userContext->getUserId(),
            $reason
        );

        if (!$result->isSuccess()) {
            return $this->redirectWithError(
                "/admin/promotions/class/{$classId}?session_id={$sessionId}",
                $result->getMessage()
            );
        }

        return $this->redirectWithSuccess(
            "/admin/promotions/class/{$classId}?session_id={$sessionId}",
            $result->getMessage()
        );
    }

    /**
     * Execute and commit cohort advancement batch (atomic transaction).
     */
    public function executeBatch(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || (!$userContext->isSuperAdmin() && !$userContext->isAdmin())) {
            throw new AuthorizationException('Administrator access required.');
        }

        $classId = (int)$request->post('class_id', 0);
        $fromSessionId = (int)$request->post('from_session_id', 0);
        $toSessionId = (int)$request->post('to_session_id', 0);
        $rawToClassId = $request->post('to_class_id');
        $toClassId = ($rawToClassId !== null && $rawToClassId !== '' && $rawToClassId !== '0') ? (int)$rawToClassId : null;

        if ($classId <= 0 || $fromSessionId <= 0) {
            return $this->redirectWithError(
                "/admin/promotions?session_id={$fromSessionId}",
                'Invalid cohort or session parameters for promotion execution.'
            );
        }

        // Student overrides array: [student_id => ['decision' => '...', 'to_class_id' => '...', 'reason' => '...']]
        $overrides = $request->post('overrides', []);
        if (!is_array($overrides)) {
            $overrides = [];
        }

        $result = $this->promotionService->commitBatchPromotion(
            classId: $classId,
            fromSessionId: $fromSessionId,
            toSessionId: $toSessionId,
            actorId: $userContext->getUserId(),
            decisions: $overrides
        );

        if (!$result->isSuccess()) {
            return $this->redirectWithError(
                "/admin/promotions/class/{$classId}?session_id={$fromSessionId}",
                $result->getMessage()
            );
        }

        return $this->redirectWithSuccess(
            "/admin/promotions/class/{$classId}?session_id={$fromSessionId}",
            $result->getMessage()
        );
    }

    /**
     * Export promotion and cumulative standings broadsheet as CSV.
     */
    public function export(Request $request, int|string $classId): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || (!$userContext->isSuperAdmin() && !$userContext->isAdmin())) {
            throw new AuthorizationException('Administrator access required.');
        }

        $cId = (int)$classId;
        $sessionId = (int)($request->get('session_id') ?? 0);
        if ($sessionId <= 0) {
            $currentSession = $this->academicRepo->getCurrentSession();
            $sessionId = $currentSession?->id ?? 0;
        }

        $cohort = $this->promotionService->evaluateClassCohort($cId, $sessionId);
        $students = $cohort['students'] ?? [];
        $className = $cohort['class']?->name ?? "Class_{$cId}";
        $sessionName = $cohort['session']?->name ?? "Session_{$sessionId}";

        // Prepare CSV stream
        $filename = "Promotion_Broadsheet_{$className}_{$sessionName}.csv";
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'S/N',
            'Admission No',
            'Student Name',
            '1st Term Avg',
            '2nd Term Avg',
            '3rd Term Avg',
            'Annual Cumulative Average',
            'Class Rank',
            'Benchmark Decision',
            'Committed Decision',
            'Target Placement',
            'Governance State',
        ]);

        $sn = 1;
        foreach ($students as $row) {
            $s = $row['student'];
            $terms = $row['cumulative_stats']['terms'] ?? [];
            $termVals = array_values($terms);

            $t1 = isset($termVals[0]['average']) && $termVals[0]['average'] !== null ? $termVals[0]['average'] . '%' : '-';
            $t2 = isset($termVals[1]['average']) && $termVals[1]['average'] !== null ? $termVals[1]['average'] . '%' : '-';
            $t3 = isset($termVals[2]['average']) && $termVals[2]['average'] !== null ? $termVals[2]['average'] . '%' : '-';

            $targetPlacement = $row['target_class_name'] ?? ($row['is_terminal'] ? 'Alumni / Graduated' : 'Pending Placement');

            fputcsv($output, [
                $sn++,
                $s->admissionNumber,
                $s->name,
                $t1,
                $t2,
                $t3,
                number_format($row['cumulative_stats']['annual_average'] ?? 0, 2) . '%',
                $row['rank'] ?? '-',
                strtoupper($row['suggested_decision'] ?? '-'),
                strtoupper($row['decision'] ?? '-'),
                $targetPlacement,
                strtoupper(str_replace('_', ' ', $row['status'] ?? '-')),
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return new Response(
            content: $csvContent ?: '',
            headers: [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }
}
