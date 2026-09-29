<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\ResultPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\TeacherRepository;
use App\Services\ReportCardService;

/**
 * Controller for Admin Report Card PDF Generation
 */
class ReportController extends Controller
{
    private ReportCardService $reportCardService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;
    private GradebookRepository $gradebookRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ReportCardService $reportCardService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?GradebookRepository $gradebookRepo = null
    ) {
        parent::__construct($authenticator);
        $this->reportCardService = $reportCardService ?? new ReportCardService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
    }

    public function pdf(Request $request, int|string $studentId, int|string $termId): Response
    {
        $userContext = $this->user($request);
        $sId = (int)$studentId;
        $tId = (int)$termId;

        if (!$userContext || !ResultPolicy::canViewReportCard($userContext, $sId, $tId, $this->academicRepo, $this->teacherRepo, $this->gradebookRepo)) {
            throw new AuthorizationException('Access denied. Administrator or assigned Class Teacher authorization required.');
        }

        $reportData = $this->reportCardService->getReportCardData($sId, $tId);
        $reportData['isPdf'] = true;
        $reportData['isAdmin'] = $userContext->isAdmin();
        $reportData['isTeacher'] = $userContext->isTeacher();

        $referer = $request->header('referer') ?? ($_SERVER['HTTP_REFERER'] ?? null);
        $backUrl = $userContext->isTeacher() ? '/teacher/results/overview' : '/admin/results/review';
        if ($referer && (str_contains($referer, '/admin/results') || str_contains($referer, '/admin/reports') || str_contains($referer, '/admin/students') || str_contains($referer, '/admin/') || str_contains($referer, '/teacher/results/'))) {
            $backUrl = $referer;
        }
        $reportData['backUrl'] = $backUrl;

        return $this->view('student/grades/report_card', $reportData);
    }
}
