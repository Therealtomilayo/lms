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
use App\Repositories\StudentRepository;
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
    private StudentRepository $studentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
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

        $reportData = $this->reportCardService->getReportCardData($studentId, $tId);
        $reportData['user'] = $userContext;
        $reportData['student'] = $student;

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
}
