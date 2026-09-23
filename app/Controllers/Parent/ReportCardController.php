<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Policies\ParentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ParentRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\ReportCardService;
use App\Services\ResultPinService;

/**
 * Controller for Parent Access to Linked Child's Grades & Report Card
 */
class ReportCardController extends Controller
{
    private ReportCardService $reportCardService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private ParentRepository $parentRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private ResultPinService $pinService;
    private FeeRepository $feeRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?ResultPinService $pinService = null,
        ?FeeRepository $feeRepo = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->pinService = $pinService ?? new ResultPinService();
        $this->feeRepo = $feeRepo ?? new FeeRepository();
    }

    public function index(Request $request, array|string|int $studentId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $sId = is_array($studentId) ? (int)($studentId['studentId'] ?? 0) : (int)$studentId;
        if ($sId <= 0) {
            $sId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        $student = $this->studentRepo->findById($sId);
        if (!$student) {
            throw new AuthorizationException('Student not found.');
        }

        if (!ParentPolicy::canViewStudent($userContext, $sId, $this->parentRepo)) {
            throw new AuthorizationException('You are not authorized to view information for this student.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $termId = (int)($request->query('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        $terms = $this->academicRepo->getAllTerms();
        $isPublished = $termId > 0 && $this->publicationRepo->isPublished($termId);

        $subjectResults = [];
        $summary = null;

        if ($isPublished) {
            $subjectResults = $this->gradebookRepo->getTermResultsByStudent($sId, $termId);
            $summary = $this->gradebookRepo->findStudentTermSummary($sId, $termId);
        }

        $parent = $this->parentRepo->findByUserId($userContext->getUserId());
        $children = $parent ? $this->parentRepo->getLinkedStudents($parent->id) : [];

        Session::start();
        Session::set('_selected_child_id', $sId);

        $termsData = [];
        foreach ($terms as $t) {
            $tPub = $this->publicationRepo->isPublished($t->id);
            $tCleared = $this->feeRepo->isStudentClearedForResult($sId, $t->sessionId, $t->id);
            $tPinUnlocked = !empty(Session::get("_unlocked_pin_{$sId}_{$t->id}"));
            $termsData[] = [
                'term' => $t,
                'isPublished' => $tPub,
                'isCleared' => $tCleared,
                'isPinUnlocked' => $tPinUnlocked,
                'reportUrl' => "/parent/children/{$sId}/grades/report-card?term_id={$t->id}",
            ];
        }

        return Response::html($this->render('parent/grades/index', [
            'student' => $student,
            'selectedChild' => $student,
            'children' => $children,
            'terms' => $terms,
            'termsData' => $termsData,
            'selectedTermId' => $termId,
            'isPublished' => $isPublished,
            'subjectResults' => $subjectResults,
            'summary' => $summary,
            'user' => $userContext,
        ], 'layouts/parent'));
    }

    public function show(Request $request, array|string|int $studentId = 0, int|string|null $termId = null): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $sId = is_array($studentId) ? (int)($studentId['studentId'] ?? 0) : (int)$studentId;
        if ($sId <= 0) {
            $sId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $tId = $termId !== null ? (int)$termId : (int)($request->query('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        $isPublished = $tId > 0 && $this->publicationRepo->isPublished($tId);
        if (!ParentPolicy::canViewReportCard($userContext, $sId, $tId, $this->parentRepo, $this->publicationRepo)) {
            throw new AuthorizationException('You are not authorized to view these results or they are not yet published.');
        }

        $student = $this->studentRepo->findById($sId);
        $activeSession = $this->academicRepo->getCurrentSession();
        $targetTerm = $this->academicRepo->findTermById($tId);
        $targetSessionId = $targetTerm ? $targetTerm->sessionId : ($activeSession ? $activeSession->id : 0);

        // Term-Scoped Bursary Clearance Gate:
        // A parent who paid Term 1 can view Term 1 results indefinitely,
        // even if Term 2 is currently unpaid. Only the unpaid term is locked.
        if (!$this->feeRepo->isStudentClearedForResult($sId, $targetSessionId, $tId)) {
            $unpaidItems = $this->feeRepo->getUnpaidRequiredFeeItems($sId, $targetSessionId, $tId);
            $invoice = $this->feeRepo->findInvoiceForStudentTerm($sId, $targetSessionId, $tId);
            if (!$invoice && !empty($unpaidItems) && !empty($unpaidItems[0]['invoice_id'])) {
                $invoice = $this->feeRepo->findInvoiceById((int)$unpaidItems[0]['invoice_id']);
            }

            return Response::html($this->render('parent/grades/fee_locked', [
                'student' => $student,
                'term' => $targetTerm,
                'session' => $this->academicRepo->findSessionById($targetSessionId),
                'unpaidItems' => $unpaidItems,
                'invoice' => $invoice,
                'backUrl' => "/parent/children/{$sId}/grades",
            ], 'layouts/parent'));
        }

        // Check Session-Based PIN Clearance (1 view count consumed per login session)
        Session::start();
        $sessionKey = "_unlocked_pin_{$sId}_{$tId}";
        $unlockedPinId = Session::get($sessionKey);
        $activePin = null;

        if ($unlockedPinId) {
            $candidate = $this->pinService->getPinById((int)$unlockedPinId);
            if ($candidate && $candidate->isUsable() && ($candidate->studentId === null || $candidate->studentId === $sId)) {
                $activePin = $candidate;
            } else {
                Session::remove($sessionKey);
            }
        }

        // PIN Gate: Must be explicitly unlocked in the active login session
        if (!$activePin) {
            return Response::html($this->render('payments/pin_gate', [
                'student' => $student,
                'currentSession' => $activeSession,
                'currentTerm' => $activeTerm,
                'unlockUrl' => "/parent/children/{$sId}/grades/unlock",
                'backUrl' => "/parent/children/{$sId}/grades",
                'error' => Session::getFlash('error'),
                'success' => Session::getFlash('success'),
                'isParent' => true,
            ]));
        }

        $reportData = $this->reportCardService->getReportCardData($sId, $tId);
        $reportData['user'] = $userContext;
        $reportData['isParentPortal'] = true;
        $reportData['pin'] = $activePin;
        $reportData['remainingUses'] = $activePin->getRemainingUses();

        return Response::html($this->render('parent/grades/report_card', $reportData));
    }

    public function pdf(Request $request, array|string|int $studentId = 0, int|string|null $termId = null): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $sId = is_array($studentId) ? (int)($studentId['studentId'] ?? 0) : (int)$studentId;
        if ($sId <= 0) {
            $sId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $tId = $termId !== null ? (int)$termId : (int)($request->query('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        if (!ParentPolicy::canViewReportCard($userContext, $sId, $tId, $this->parentRepo, $this->publicationRepo)) {
            throw new AuthorizationException('You are not authorized to view these results or they are not yet published.');
        }

        $targetTerm = $this->academicRepo->findTermById($tId);
        $activeSession = $this->academicRepo->getCurrentSession();
        $targetSessionId = $targetTerm ? $targetTerm->sessionId : ($activeSession ? $activeSession->id : 0);

        if (!$this->feeRepo->isStudentClearedForResult($sId, $targetSessionId, $tId)) {
            return Response::redirect("/parent/children/{$sId}/grades/report-card?term_id={$tId}");
        }

        Session::start();
        $sessionKey = "_unlocked_pin_{$sId}_{$tId}";
        $unlockedPinId = Session::get($sessionKey);
        $activePin = $unlockedPinId ? $this->pinService->getPinById((int)$unlockedPinId) : null;
        if (!$activePin || !$activePin->isUsable() || ($activePin->studentId !== null && $activePin->studentId !== $sId)) {
            return Response::redirect("/parent/children/{$sId}/grades/report-card?term_id={$tId}");
        }

        $reportData = $this->reportCardService->getReportCardData($sId, $tId);
        $reportData['pin'] = $activePin;
        $reportData['remainingUses'] = $activePin->getRemainingUses();

        $html = $this->render('parent/grades/report_card', $reportData);

        return Response::html($html)
            ->withHeader('Content-Disposition', 'inline; filename="report-card-' . $sId . '.html"');
    }

    /**
     * Consume physical Scratch-Card PIN to unlock report card access for the current session.
     */
    public function unlock(Request $request, array|string|int $studentId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $sId = is_array($studentId) ? (int)($studentId['studentId'] ?? 0) : (int)$studentId;
        if ($sId <= 0) {
            $sId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        $student = $this->studentRepo->findById($sId);
        if (!$student || !ParentPolicy::canViewStudent($userContext, $sId, $this->parentRepo)) {
            throw new AuthorizationException('Unauthorized access to student.');
        }

        $pinCode = trim((string)$request->post('pin_code', ''));
        $termId = (int)$request->post('term_id', 0);
        $sessionId = (int)$request->post('session_id', 0);

        if ($pinCode === '') {
            Session::flash('error', 'Please enter a valid Scratch-Card PIN.');
            return Response::redirect("/parent/children/{$sId}/grades/report-card?term_id={$termId}");
        }

        $targetTerm = $this->academicRepo->findTermById($termId);
        $activeSession = $this->academicRepo->getCurrentSession();
        $targetSessionId = $sessionId > 0 ? $sessionId : ($targetTerm ? $targetTerm->sessionId : ($activeSession ? $activeSession->id : 0));

        Session::start();

        // Enforce Bursary Fee Clearance before consuming PIN
        if (!$this->feeRepo->isStudentClearedForResult($sId, $targetSessionId, $termId)) {
            Session::flash('error', 'Cannot unlock report card: required school fees have not been cleared. Please settle outstanding fees before using a Scratch-Card PIN.');
            return Response::redirect("/parent/children/{$sId}/grades/report-card?term_id={$termId}");
        }

        $result = $this->pinService->verifyAndConsumePin($student->admissionNumber, $pinCode, $targetSessionId, $termId);
        if (!$result->success) {
            Session::flash('error', $result->error ?? 'PIN verification failed.');
            return Response::redirect("/parent/children/{$sId}/grades/report-card?term_id={$termId}");
        }

        $pin = $result->data['pin'];
        $sessionKey = "_unlocked_pin_{$sId}_{$termId}";
        Session::set($sessionKey, $pin->id);

        Session::flash('success', "PIN verified successfully! {$pin->getRemainingUses()} view(s) remaining.");
        return Response::redirect("/parent/children/{$sId}/grades/report-card?term_id={$termId}");
    }
}
