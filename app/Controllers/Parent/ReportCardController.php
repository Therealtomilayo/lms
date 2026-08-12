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
use App\Repositories\GradebookRepository;
use App\Repositories\ParentRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\ReportCardService;

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

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
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

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $termId = (int)($request->query('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));

        $terms = $this->academicRepo->getAllTerms();
        $isPublished = $termId > 0 && $this->publicationRepo->isPublished($termId);

        if (!ParentPolicy::canViewReportCard($userContext, $sId, $termId, $this->parentRepo, $this->publicationRepo)) {
            throw new AuthorizationException('You are not authorized to view these results or they are not yet published.');
        }

        $subjectResults = $this->gradebookRepo->getTermResultsByStudent($sId, $termId);
        $summary = $this->gradebookRepo->findStudentTermSummary($sId, $termId);

        $parent = $this->parentRepo->findByUserId($userContext->getUserId());
        $children = $parent ? $this->parentRepo->getLinkedStudents($parent->id) : [];

        Session::start();
        Session::set('_selected_child_id', $sId);

        return Response::html($this->render('parent/grades/index', [
            'student' => $student,
            'selectedChild' => $student,
            'children' => $children,
            'terms' => $terms,
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

        $reportData = $this->reportCardService->getReportCardData($sId, $tId);
        $reportData['user'] = $userContext;

        return Response::html($this->render('parent/grades/report_card', $reportData, 'layouts/parent'));
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

        $reportData = $this->reportCardService->getReportCardData($sId, $tId);

        $html = $this->render('parent/grades/report_card', $reportData);

        return Response::html($html)
            ->withHeader('Content-Disposition', 'inline; filename="report-card-' . $sId . '.html"');
    }
}
