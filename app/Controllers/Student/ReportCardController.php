<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\ResultPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\ReportCardService;
use App\Services\ResultPinService;

/**
 * Controller for Student Published Grades and Report Card Access
 */
class ReportCardController extends Controller
{
    private ReportCardService $reportCardService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private ResultPinService $pinService;
    private FeeRepository $feeRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?ResultPinService $pinService = null,
        ?FeeRepository $feeRepo = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->pinService = $pinService ?? new ResultPinService();
        $this->feeRepo = $feeRepo ?? new FeeRepository();
    }

    /**
     * Term Continuous Assessment & Grade Breakdown Overview
     * Route: GET /student/grades
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile required.');
        }

        $studentId = $student ? $student->id : ($userContext->getStudentId() ?: 0);
        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $termId = (int)($request->get('term_id') ?: ($activeTerm?->id ?? 0));

        $terms = $this->academicRepo->getAllTerms();
        $isPublished = $this->publicationRepo->isPublished($termId);

        $subjectResults = [];
        $summary = null;

        if ($isPublished && ResultPolicy::canViewStudentResults($userContext, $studentId, $isPublished)) {
            $subjectResults = $this->gradebookRepo->getTermResultsByStudent($studentId, $termId);
            $summary = $this->gradebookRepo->findStudentTermSummary($studentId, $termId);
        }

        return Response::html($this->render('student/grades/index', [
            'title' => 'Academic Grades & Performance — Student Portal',
            'headerTitle' => 'Terminal Academic Performance',
            'user' => $userContext,
            'student' => $student,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'terms' => $terms,
            'selectedTermId' => $termId,
            'isPublished' => $isPublished,
            'subjectResults' => $subjectResults,
            'summary' => $summary,
        ], 'layouts/student'));
    }

    /**
     * Official Terminal Report Card View & Print
     * Route: GET /student/grades/report-card
     */
    public function show(Request $request, array|string|int|null $termId = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile required.');
        }

        $studentId = $student ? $student->id : ($userContext->getStudentId() ?: 0);
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $tId = is_array($termId) ? (int)($termId['term_id'] ?? 0) : ($termId !== null ? (int)$termId : (int)($request->get('term_id') ?: ($activeTerm?->id ?? 0)));

        $isPublished = $this->publicationRepo->isPublished($tId);
        if (!ResultPolicy::canViewStudentResults($userContext, $studentId, $isPublished)) {
            return Response::forbidden('Results for this academic term are not yet published.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $targetTerm = $this->academicRepo->findTermById($tId);
        $targetSessionId = $targetTerm ? $targetTerm->sessionId : ($activeSession?->id ?? 0);

        // Term-Scoped Bursary Clearance Gate:
        if (!$this->feeRepo->isStudentClearedForResult($studentId, $targetSessionId, $tId)) {
            $unpaidItems = $this->feeRepo->getUnpaidRequiredFeeItems($studentId, $targetSessionId, $tId);
            $invoice = $this->feeRepo->findInvoiceForStudentTerm($studentId, $targetSessionId, $tId);
            if (!$invoice && !empty($unpaidItems) && !empty($unpaidItems[0]['invoice_id'])) {
                $invoice = $this->feeRepo->findInvoiceById((int)$unpaidItems[0]['invoice_id']);
            }
            return Response::html($this->render('student/grades/fee_locked', [
                'student' => $student,
                'term' => $targetTerm,
                'session' => $this->academicRepo->findSessionById($targetSessionId),
                'unpaidItems' => $unpaidItems,
                'invoice' => $invoice,
                'backUrl' => '/student/grades',
            ], 'layouts/student'));
        }

        \App\Core\Session::start();
        $sessionKey = "_unlocked_pin_student_{$studentId}_{$tId}";
        $unlockedPinId = \App\Core\Session::get($sessionKey);
        $activePin = null;

        if ($unlockedPinId) {
            $candidate = $this->pinService->getPinById((int)$unlockedPinId);
            if ($candidate && $candidate->isUsable() && ($candidate->studentId === null || $candidate->studentId === $studentId)) {
                $activePin = $candidate;
            } else {
                \App\Core\Session::remove($sessionKey);
            }
        }

        // PIN Gate: Must be explicitly unlocked in the active login session
        if (!$activePin) {
            return Response::html($this->render('payments/pin_gate', [
                'student' => $student,
                'currentSession' => $activeSession,
                'currentTerm' => $activeTerm,
                'unlockUrl' => "/student/grades/unlock",
                'backUrl' => "/student/grades",
                'error' => \App\Core\Session::getFlash('error'),
                'success' => \App\Core\Session::getFlash('success'),
                'isParent' => false,
            ]));
        }

        $reportData = $this->reportCardService->getReportCardData($studentId, $tId);
        $reportData['user'] = $userContext;
        $reportData['student'] = $student;
        $reportData['pin'] = $activePin;
        $reportData['remainingUses'] = $activePin->getRemainingUses();

        return Response::html($this->render('student/grades/report_card', $reportData));
    }

    /**
     * Downloadable / PDF View
     * Route: GET /student/grades/report-card/pdf
     */
    public function pdf(Request $request, array|string|int|null $termId = null): Response
    {
        return $this->show($request, $termId);
    }

    /**
     * Consume physical Scratch-Card PIN to unlock student report card access for the current session.
     */
    public function unlock(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile required.');
        }

        $studentId = $student ? $student->id : ($userContext->getStudentId() ?: 0);
        $pinCode = trim((string)$request->post('pin_code', ''));
        $termId = (int)$request->post('term_id', 0);
        $sessionId = (int)$request->post('session_id', 0);

        if ($pinCode === '') {
            \App\Core\Session::flash('error', 'Please enter a valid Scratch-Card PIN.');
            return Response::redirect("/student/grades/report-card?term_id={$termId}");
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $targetTerm = $this->academicRepo->findTermById($termId);
        $targetSessionId = $sessionId > 0 ? $sessionId : ($targetTerm ? $targetTerm->sessionId : ($activeSession?->id ?? 0));

        \App\Core\Session::start();

        // Enforce Bursary Fee Clearance before consuming PIN
        if (!$this->feeRepo->isStudentClearedForResult($studentId, $targetSessionId, $termId)) {
            \App\Core\Session::flash('error', 'Cannot unlock report card: required school fees have not been cleared. Please settle outstanding fees before using a Scratch-Card PIN.');
            return Response::redirect("/student/grades/report-card?term_id={$termId}");
        }

        $admNo = $student ? $student->admissionNumber : '';
        $result = $this->pinService->verifyAndConsumePin($admNo, $pinCode, $targetSessionId, $termId);
        if (!$result->success) {
            \App\Core\Session::flash('error', $result->error ?? 'PIN verification failed.');
            return Response::redirect("/student/grades/report-card?term_id={$termId}");
        }

        $pin = $result->data['pin'];
        $sessionKey = "_unlocked_pin_student_{$studentId}_{$termId}";
        \App\Core\Session::set($sessionKey, $pin->id);

        \App\Core\Session::flash('success', "PIN verified successfully! {$pin->getRemainingUses()} view(s) remaining.");
        return Response::redirect("/student/grades/report-card?term_id={$termId}");
    }
}
