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
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\ResultSubmissionRepository;
use App\Services\GradebookService;

/**
 * Controller for Admin Result Review, Calculation and Locking
 */
class ResultReviewController extends Controller
{
    private GradebookService $gradebookService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private ResultSubmissionRepository $submissionRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradebookService $gradebookService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?ResultSubmissionRepository $submissionRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradebookService = $gradebookService ?? new GradebookService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->submissionRepo = $submissionRepo ?? new ResultSubmissionRepository();
    }

    public function index(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $selectedTermId = $termId !== null ? (int)$termId : (int)($request->get('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));
        $selectedClassId = (int)($request->get('class_id', 0) ?: 0);

        $terms = $this->academicRepo->getAllTerms();
        $classes = $this->academicRepo->getAllClasses();

        $summaries = [];
        $isPublished = false;
        $submission = null;

        if ($selectedTermId > 0 && $selectedClassId > 0) {
            $summaries = $this->gradebookRepo->getSummariesByClassAndTerm($selectedClassId, $selectedTermId);
            $isPublished = $this->publicationRepo->isPublished($selectedTermId, $selectedClassId);
            $submission = $this->submissionRepo->findSubmission($selectedClassId, $selectedTermId);
        }

        return $this->view('admin/results/review', [
            'terms' => $terms,
            'classes' => $classes,
            'selectedTermId' => $selectedTermId,
            'selectedClassId' => $selectedClassId,
            'summaries' => $summaries,
            'isPublished' => $isPublished,
            'submission' => $submission,
        ]);
    }

    public function compute(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $tId = $termId !== null ? (int)$termId : (int)($request->input('term_id') ?? $request->get('term_id') ?? 0);
        $classId = (int)($request->input('class_id') ?? $request->get('class_id') ?? 0);

        $term = $this->academicRepo->findTermById($tId);
        if (!$term) {
            return $this->redirectWithError('/admin/results/review', 'Invalid academic term selected.');
        }

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return $this->redirectWithError("/admin/results/review?term_id={$tId}", 'Invalid class selected.');
        }

        $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($classId, $term->sessionId);
        foreach ($classSubjects as $cs) {
            $this->gradebookService->computeClassSubjectResults(
                $cs->id,
                $term->sessionId,
                $term->id,
                (bool)$request->input('lock_results'),
                $userContext->getUserId()
            );
        }

        $this->gradebookService->computeClassTermSummaries(
            $classId,
            $term->sessionId,
            $term->id,
            (bool)$request->input('lock_results'),
            $userContext->getUserId()
        );

        return $this->redirectWithSuccess(
            "/admin/results/review?term_id={$tId}&class_id={$classId}",
            'Class term results and rankings computed successfully.'
        );
    }

    public function broadsheet(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $selectedTermId = (int)($request->get('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));
        $selectedClassId = (int)($request->get('class_id', 0) ?: 0);

        $terms = $this->academicRepo->getAllTerms();
        $classes = $this->academicRepo->getAllClasses();

        $selectedTerm = $selectedTermId > 0 ? $this->academicRepo->findTermById($selectedTermId) : null;
        $selectedClass = $selectedClassId > 0 ? $this->academicRepo->findClassById($selectedClassId) : null;

        $classSubjects = [];
        $students = [];
        $resultsMatrix = [];
        $summaryMap = [];
        $subjectAverages = [];
        $classMean = null;
        $isPublished = false;

        if ($selectedTerm && $selectedClass) {
            $sessionId = $selectedTerm->sessionId;
            $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($selectedClassId, $sessionId);
            $students = $this->enrollmentRepo->getStudentsByClassAndSession($selectedClassId, $sessionId);
            $resultsMatrix = $this->gradebookRepo->getTermResultsMatrixByClass($selectedClassId, $selectedTerm->id);
            $submission = $this->submissionRepo->findSubmission($selectedClassId, $selectedTerm->id);
            $summaries = $this->gradebookRepo->getSummariesByClassAndTerm($selectedClassId, $selectedTerm->id);
            $isPublished = $this->publicationRepo->isPublished($selectedTerm->id, $selectedClassId);

            $totalSum = 0;
            $countSum = 0;
            foreach ($summaries as $sm) {
                $summaryMap[$sm->studentId] = $sm;
                if ($sm->averageScore !== null) {
                    $totalSum += (float)$sm->averageScore;
                    $countSum++;
                }
            }
            if ($countSum > 0) {
                $classMean = round($totalSum / $countSum, 1);
            }

            foreach ($classSubjects as $cs) {
                $stats = $this->gradebookRepo->getTermResultsStatsByClassSubject($cs->id, $selectedTerm->id);
                $subjectAverages[$cs->subjectId] = [
                    'avg' => $stats['avg_score'],
                    'min' => $stats['min_score'],
                    'max' => $stats['max_score'],
                ];
            }

            // Sort students by rank ascending
            usort($students, function ($a, $b) use ($summaryMap) {
                $rankA = $summaryMap[$a->id]->classRank ?? 9999;
                $rankB = $summaryMap[$b->id]->classRank ?? 9999;
                if ($rankA === $rankB) {
                    return strcmp($a->name, $b->name);
                }
                return $rankA <=> $rankB;
            });
        }

        return $this->view('admin/results/broadsheet', [
            'terms' => $terms,
            'classes' => $classes,
            'selectedTerm' => $selectedTerm,
            'selectedClass' => $selectedClass,
            'selectedTermId' => $selectedTermId,
            'selectedClassId' => $selectedClassId,
            'classSubjects' => $classSubjects,
            'students' => $students,
            'resultsMatrix' => $resultsMatrix,
            'summaryMap' => $summaryMap,
            'subjectAverages' => $subjectAverages,
            'classMean' => $classMean,
            'isPublished' => $isPublished,
            'submission' => $submission,
        ]);
    }

    public function exportBroadsheet(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $termId = (int)$request->get('term_id', 0);
        $classId = (int)$request->get('class_id', 0);

        $term = $this->academicRepo->findTermById($termId);
        $class = $this->academicRepo->findClassById($classId);

        if (!$term || !$class) {
            return $this->redirectWithError('/admin/results/broadsheet', 'Invalid term or class selected for export.');
        }

        $sessionId = $term->sessionId;
        $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($classId, $sessionId);
        $students = $this->enrollmentRepo->getStudentsByClassAndSession($classId, $sessionId);
        $resultsMatrix = $this->gradebookRepo->getTermResultsMatrixByClass($classId, $term->id);
        $summaries = $this->gradebookRepo->getSummariesByClassAndTerm($classId, $term->id);

        $summaryMap = [];
        foreach ($summaries as $sm) {
            $summaryMap[$sm->studentId] = $sm;
        }

        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name . ($class->sectionArm ? '_' . $class->sectionArm : ''));
        $cleanTermName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $term->name);
        $filename = "Broadsheet_{$cleanClassName}_{$cleanTermName}.csv";

        $output = fopen('php://temp', 'r+');

        // Institution Letterhead Meta Rows
        fputcsv($output, ['CLARET INTERNATIONAL SCHOOL']);
        fputcsv($output, ['OFFICIAL TERMINAL BROADSHEET MATRIX']);
        fputcsv($output, ['Class Cohort:', $class->name . ($class->sectionArm ? ' (Arm ' . $class->sectionArm . ')' : '')]);
        fputcsv($output, ['Academic Term:', $term->name]);
        fputcsv($output, ['Form Teacher (Class Master):', $class->formTeacherName ?? 'Unassigned']);
        fputcsv($output, ['Generated Date:', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // blank spacing row

        // Column Headers
        $headers = ['Rank', 'Admission No', 'Student Name', 'Gender'];
        foreach ($classSubjects as $cs) {
            $code = $cs->subject?->code ?: ($cs->subject?->name ?: 'SUB_' . $cs->subjectId);
            $headers[] = $code . ' Total';
            $headers[] = $code . ' Grade';
        }
        $headers[] = 'Overall Total';
        $headers[] = 'Term Average (%)';
        $headers[] = 'Class Position';
        $headers[] = 'Class Teacher Remark';
        $headers[] = 'Principal Endorsement';
        fputcsv($output, $headers);

        // Sort students by class rank
        usort($students, function ($a, $b) use ($summaryMap) {
            $rankA = $summaryMap[$a->id]->classRank ?? 9999;
            $rankB = $summaryMap[$b->id]->classRank ?? 9999;
            if ($rankA === $rankB) {
                return strcmp($a->name, $b->name);
            }
            return $rankA <=> $rankB;
        });

        foreach ($students as $st) {
            $sm = $summaryMap[$st->id] ?? null;
            $row = [
                $sm?->classRank ?? '—',
                $st->admissionNumber,
                $st->name,
                $st->gender ? ucfirst($st->gender) : '—',
            ];

            foreach ($classSubjects as $cs) {
                $subRes = $resultsMatrix[$st->id][$cs->subjectId] ?? null;
                if ($subRes) {
                    $row[] = number_format((float)($subRes['computed_score'] ?? $subRes['total_score'] ?? 0), 1);
                    $row[] = $subRes['grade_letter'] ?? '—';
                } else {
                    $row[] = '—';
                    $row[] = '—';
                }
            }

            $row[] = $sm ? number_format((float)$sm->totalScore, 1) : '—';
            $row[] = $sm && $sm->averageScore !== null ? number_format((float)$sm->averageScore, 1) . '%' : '—';
            $row[] = $sm?->classRank ?? '—';
            $row[] = $sm?->classTeacherRemark ?? '';
            $row[] = $sm?->principalRemark ?? '';

            fputcsv($output, $row);
        }

        // Subject Class Averages Footer Row
        $avgRow = ['', '', 'CLASS SUBJECT AVERAGE', ''];
        foreach ($classSubjects as $cs) {
            $stats = $this->gradebookRepo->getTermResultsStatsByClassSubject($cs->id, $term->id);
            $avgRow[] = $stats['avg_score'] !== null ? number_format((float)$stats['avg_score'], 1) : '—';
            $avgRow[] = '';
        }
        $avgRow[] = '';
        $avgRow[] = '';
        $avgRow[] = '';
        $avgRow[] = '';
        $avgRow[] = '';
        fputcsv($output, $avgRow);

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return new Response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function approveSubmission(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $termId = (int)$request->post('term_id', 0);
        $classId = (int)$request->post('class_id', 0);

        if ($termId > 0 && $classId > 0) {
            $this->submissionRepo->approve($classId, $termId, $userContext->id);
        }

        return $this->redirectWithSuccess(
            "/admin/results/review?term_id={$termId}&class_id={$classId}",
            'Form Teacher terminal results submission approved successfully.'
        );
    }
}
