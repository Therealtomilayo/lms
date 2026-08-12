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
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Services\ReportCardService;

/**
 * Controller for Student Published Grades and Report Card Access
 */
class ReportCardController extends Controller
{
    private ReportCardService $reportCardService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext) {
            return $this->redirect('/login');
        }

        $studentId = $userContext->getStudentId();
        if (!$studentId) {
            throw new AuthorizationException('Student profile required.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $termId = (int)($request->get('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        $terms = $this->academicRepo->getAllTerms();
        $isPublished = $this->publicationRepo->isPublished($termId);

        $subjectResults = [];
        $summary = null;

        if ($isPublished && ResultPolicy::canViewStudentResults($userContext, $studentId, $isPublished)) {
            $subjectResults = $this->gradebookRepo->getTermResultsByStudent($studentId, $termId);
            $summary = $this->gradebookRepo->findStudentTermSummary($studentId, $termId);
        }

        return $this->view('student/grades/index', [
            'terms' => $terms,
            'selectedTermId' => $termId,
            'isPublished' => $isPublished,
            'subjectResults' => $subjectResults,
            'summary' => $summary,
        ]);
    }

    public function show(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext) {
            return $this->redirect('/login');
        }

        $studentId = $userContext->getStudentId();
        if (!$studentId) {
            throw new AuthorizationException('Student profile required.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $tId = $termId !== null ? (int)$termId : (int)($request->get('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        $isPublished = $this->publicationRepo->isPublished($tId);
        if (!ResultPolicy::canViewStudentResults($userContext, $studentId, $isPublished)) {
            throw new AuthorizationException('Results for this term are not yet published.');
        }

        $reportData = $this->reportCardService->getReportCardData($studentId, $tId);

        return $this->view('student/grades/report_card', $reportData);
    }

    public function pdf(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext) {
            return $this->redirect('/login');
        }

        $studentId = $userContext->getStudentId();
        if (!$studentId) {
            throw new AuthorizationException('Student profile required.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $tId = $termId !== null ? (int)$termId : (int)($request->get('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        $isPublished = $this->publicationRepo->isPublished($tId);
        if (!ResultPolicy::canViewStudentResults($userContext, $studentId, $isPublished)) {
            throw new AuthorizationException('Results for this term are not yet published.');
        }

        $reportData = $this->reportCardService->getReportCardData($studentId, $tId);
        $reportData['isPdf'] = true;

        return $this->view('student/grades/report_card', $reportData);
    }
}
