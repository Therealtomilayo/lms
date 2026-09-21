<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\ReportCardService;
use App\Services\ResultPinService;

/**
 * Public Self-Service Result Checker Gateway (SRS §38, §39, §40, §58.5)
 */
class PublicResultCheckerController extends Controller
{
    private ResultPinService $pinService;
    private StudentRepository $studentRepository;
    private AcademicRepository $academicRepository;
    private ResultPublicationRepository $publicationRepository;
    private ReportCardService $reportCardService;
    private FeeRepository $feeRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ResultPinService $pinService = null,
        ?StudentRepository $studentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?ResultPublicationRepository $publicationRepository = null,
        ?ReportCardService $reportCardService = null,
        ?FeeRepository $feeRepository = null
    ) {
        parent::__construct($authenticator);
        $this->pinService = $pinService ?? new ResultPinService();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->publicationRepository = $publicationRepository ?? new ResultPublicationRepository();
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->feeRepository = $feeRepository ?? new FeeRepository();
    }

    public function show(Request $request): Response
    {
        Session::start();
        $csrfToken = \App\Core\Csrf::getToken();
        $currentSession = $this->academicRepository->getCurrentSession();
        $currentTerm = $this->academicRepository->getCurrentTerm();
        $sessions = $this->academicRepository->getAllSessions();
        $terms = $currentSession ? $this->academicRepository->getTermsBySession($currentSession->id) : [];

        return $this->view('public/results/check', [
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'sessions' => $sessions,
            'terms' => $terms,
            'csrfToken' => $csrfToken,
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    public function verify(Request $request): Response
    {
        $admissionNumber = trim((string)$request->post('admission_number', ''));
        $pinCode = trim((string)$request->post('pin_code', ''));
        $sessionId = (int)$request->post('session_id', 0);
        $termId = (int)$request->post('term_id', 0);

        if ($admissionNumber === '' || $pinCode === '') {
            return $this->redirectWithError('/results/check', 'Both Student ID / Admission Number and Scratch-Card PIN are required.');
        }

        $cleanPin = strtoupper(str_replace(['-', ' '], '', $pinCode));
        if (strlen($cleanPin) !== 12) {
            return $this->redirectWithError('/results/check', 'The Scratch-Card PIN must be exactly 12 characters (format: XXXX-XXXX-XXXX).');
        }

        $activeSession = $this->academicRepository->getCurrentSession();
        $activeTerm = $this->academicRepository->getCurrentTerm();
        $targetSessionId = $sessionId > 0 ? $sessionId : ($activeSession?->id ?? 1);
        $targetTermId = $termId > 0 ? $termId : ($activeTerm?->id ?? 1);

        // Pre-check student bursary clearance before consuming PIN
        $studentRecord = $this->studentRepository->findByAdmissionNumber($admissionNumber);
        if ($studentRecord && !$this->feeRepository->isStudentClearedForResult($studentRecord->id, $targetSessionId, $targetTermId)) {
            return $this->redirectWithError(
                '/results/check',
                'Result access for this student is currently locked due to unsettled termly fees. Please contact the bursary or settle outstanding fees to unlock results.'
            );
        }

        // 1. Verify and Consume PIN view attempt
        $result = $this->pinService->verifyAndConsumePin(
            $admissionNumber,
            $pinCode,
            $targetSessionId,
            $targetTermId
        );

        if (!$result->success) {
            return $this->redirectWithError('/results/check', $result->error ?? 'PIN verification failed.');
        }

        $student = $result->data['student'];
        $pin = $result->data['pin'];

        // 2. Check if results for this term are published
        if (!$this->publicationRepository->isPublished($targetTermId)) {
            return $this->redirectWithError(
                '/results/check',
                'Results for the selected academic term have not yet been officially approved and published by the administration.'
            );
        }

        // 3. Store Result Clearance in Session to protect against refresh count increments (PRG Pattern)
        Session::start();
        $clearanceKey = "_public_report_{$student->id}_{$targetTermId}";
        Session::set($clearanceKey, [
            'pin_id' => $pin->id,
            'student_id' => $student->id,
            'term_id' => $targetTermId,
            'session_id' => $targetSessionId,
            'unlocked_at' => time(),
        ]);

        return Response::redirect("/results/view?student_id={$student->id}&term_id={$targetTermId}");
    }

    /**
     * Display report card for verified public result checker session (PRG pattern).
     * Prevents browser refreshes from re-submitting POST and burning multiple view attempts.
     */
    public function viewReport(Request $request): Response
    {
        Session::start();
        $studentId = (int)$request->query('student_id', 0);
        $termId = (int)$request->query('term_id', 0);

        if ($studentId <= 0 || $termId <= 0) {
            return $this->redirectWithError('/results/check', 'Invalid student or academic term selected.');
        }

        $clearanceKey = "_public_report_{$studentId}_{$termId}";
        $clearance = Session::get($clearanceKey);

        if (!$clearance || ($clearance['student_id'] ?? 0) !== $studentId || ($clearance['term_id'] ?? 0) !== $termId) {
            return $this->redirectWithError(
                '/results/check',
                'Your result viewing session has expired or is invalid. Please enter your Scratch-Card PIN to view again.'
            );
        }

        $student = $this->studentRepository->findById($studentId);
        $pin = $this->pinService->getPinById((int)$clearance['pin_id']);

        if (!$student || !$pin) {
            return $this->redirectWithError('/results/check', 'Scratch-Card PIN or student record could not be verified.');
        }

        if (!$this->publicationRepository->isPublished($termId)) {
            return $this->redirectWithError(
                '/results/check',
                'Results for the selected academic term have not yet been officially approved and published by the administration.'
            );
        }

        $reportData = $this->reportCardService->getReportCardData($student->id, $termId);
        $reportData['pin'] = $pin;
        $reportData['remainingUses'] = $pin->getRemainingUses();
        $reportData['isPublicChecker'] = true;

        return $this->view('parent/grades/report_card', $reportData);
    }
}
