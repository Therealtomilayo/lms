<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\GradingScaleRepository;
use App\Services\GradebookService;

/**
 * Controller for Admin Institutional Gradebook Oversight, Class Arm Broadsheets, and Continuous Assessment Audits
 */
class GradebookController extends Controller
{
    private GradebookService $gradebookService;
    private GradebookRepository $gradebookRepo;
    private GradingScaleRepository $gradingScaleRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradebookService $gradebookService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?GradingScaleRepository $gradingScaleRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradebookService = $gradebookService ?? new GradebookService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->gradingScaleRepo = $gradingScaleRepo ?? new GradingScaleRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
    }

    /**
     * Institutional Gradebook Directory (Classes & Arms Overview)
     * Route: GET /admin/gradebook
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $allSessions = $this->academicRepo->getAllSessions();
        $activeSession = $this->academicRepo->findCurrentSession() ?? $this->academicRepo->findActiveSession();

        $selectedSessionId = (int)($request->get('session_id') ?: ($activeSession?->id ?? ($allSessions[0]->id ?? 0)));
        $selectedSession = $selectedSessionId > 0 ? $this->academicRepo->findSessionById($selectedSessionId) : null;

        $terms = $selectedSessionId > 0 
            ? $this->academicRepo->getTermsBySession($selectedSessionId) 
            : $this->academicRepo->findAllTerms();

        $activeTerm = $this->academicRepo->findCurrentTerm();
        $defaultTermId = ($activeTerm && $activeTerm->sessionId === $selectedSessionId) 
            ? $activeTerm->id 
            : ($terms[0]->id ?? 0);

        $selectedTermId = (int)($request->get('term_id') ?: $defaultTermId);
        $selectedTerm = $selectedTermId > 0 ? $this->academicRepo->findTermById($selectedTermId) : null;

        $allLevels = $this->academicRepo->getAllLevels();
        $selectedLevelId = (int)($request->get('level_id') ?: 0);
        $searchQuery = trim((string)($request->get('q') ?: ''));

        // Retrieve classes (filtered by level if selected)
        $rawClasses = $selectedLevelId > 0 
            ? $this->academicRepo->getClassesByLevel($selectedLevelId) 
            : $this->academicRepo->getAllClasses();

        // Apply textual search filter
        $classes = [];
        foreach ($rawClasses as $c) {
            if ($searchQuery !== '') {
                $q = mb_strtolower($searchQuery);
                $cName = mb_strtolower($c->name ?? '');
                $cArm = mb_strtolower($c->sectionArm ?? '');
                if (!str_contains($cName, $q) && !str_contains($cArm, $q)) {
                    continue;
                }
            }
            $classes[] = $c;
        }

        // Aggregate statistics for each Class & Arm
        $classRows = [];
        $totalStudentsAllClasses = 0;
        $totalSubjectsAllClasses = 0;
        $totalLockedClassesCount = 0;
        $schoolScoreSum = 0.0;
        $schoolScoreCount = 0;

        foreach ($classes as $c) {
            $students = $this->enrollmentRepo->getStudentsByClassAndSession($c->id, $selectedSessionId);
            $enrolledCount = count($students);
            $totalStudentsAllClasses += $enrolledCount;

            $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($c->id, $selectedSessionId);
            $subjectsCount = count($classSubjects);
            $totalSubjectsAllClasses += $subjectsCount;

            $evaluatedSubjects = 0;
            $lockedSubjects = 0;
            $classScoreSum = 0.0;
            $classScoreCount = 0;

            if ($selectedTerm) {
                foreach ($classSubjects as $cs) {
                    $stats = $this->gradebookRepo->getTermResultsStatsByClassSubject($cs->id, $selectedTerm->id);
                    if ($stats['total_students'] > 0) {
                        $evaluatedSubjects++;
                    }
                    if ($stats['locked']) {
                        $lockedSubjects++;
                    }
                    if ($stats['avg_score'] !== null) {
                        $classScoreSum += $stats['avg_score'];
                        $classScoreCount++;
                        $schoolScoreSum += $stats['avg_score'];
                        $schoolScoreCount++;
                    }
                }
            }

            $classAverage = $classScoreCount > 0 ? round($classScoreSum / $classScoreCount, 1) : null;
            $isFullyLocked = ($subjectsCount > 0 && $lockedSubjects === $subjectsCount);
            if ($isFullyLocked) {
                $totalLockedClassesCount++;
            }

            $classRows[] = [
                'class' => $c,
                'enrolledCount' => $enrolledCount,
                'subjectsCount' => $subjectsCount,
                'evaluatedSubjects' => $evaluatedSubjects,
                'lockedSubjects' => $lockedSubjects,
                'classAverage' => $classAverage,
                'isFullyLocked' => $isFullyLocked,
            ];
        }

        $institutionalMean = $schoolScoreCount > 0 ? round($schoolScoreSum / $schoolScoreCount, 1) : null;

        return Response::html($this->render('admin/gradebook/index', [
            'title' => 'Institutional Gradebook — Classes & Arms Overview',
            'headerTitle' => 'Institutional Gradebook',
            'user' => $userContext,
            'sessions' => $allSessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'selectedTermId' => $selectedTermId,
            'levels' => $allLevels,
            'selectedLevelId' => $selectedLevelId,
            'searchQuery' => $searchQuery,
            'classRows' => $classRows,
            'totalClasses' => count($classRows),
            'totalStudentsAllClasses' => $totalStudentsAllClasses,
            'totalSubjectsAllClasses' => $totalSubjectsAllClasses,
            'totalLockedClassesCount' => $totalLockedClassesCount,
            'institutionalMean' => $institutionalMean,
        ], 'layouts/admin'));
    }

    /**
     * Dedicated Class Arm Gradebook Page
     * Allows switching between "Master Broadsheet (All Subjects)" and specific subject continuous assessment sheets.
     * Route: GET /admin/gradebook/class/{classId}
     */
    public function showClass(Request $request, array|string|int $classId): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $cId = is_array($classId) ? (int)($classId['classId'] ?? $classId['id'] ?? 0) : (int)$classId;
        $class = $this->academicRepo->findClassById($cId);
        if (!$class) {
            return $this->notFound('Class arm not found.');
        }

        $allSessions = $this->academicRepo->getAllSessions();
        $activeSession = $this->academicRepo->findCurrentSession() ?? $this->academicRepo->findActiveSession();

        $selectedSessionId = (int)($request->get('session_id') ?: ($activeSession?->id ?? ($allSessions[0]->id ?? 0)));
        $selectedSession = $selectedSessionId > 0 ? $this->academicRepo->findSessionById($selectedSessionId) : null;

        $terms = $selectedSessionId > 0 
            ? $this->academicRepo->getTermsBySession($selectedSessionId) 
            : $this->academicRepo->findAllTerms();

        $activeTerm = $this->academicRepo->findCurrentTerm();
        $defaultTermId = ($activeTerm && $activeTerm->sessionId === $selectedSessionId) 
            ? $activeTerm->id 
            : ($terms[0]->id ?? 0);

        $selectedTermId = (int)($request->get('term_id') ?: $defaultTermId);
        $selectedTerm = $selectedTermId > 0 ? $this->academicRepo->findTermById($selectedTermId) : null;

        // Class subjects offered in this class arm
        $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($cId, $selectedSessionId);

        // Enrolled students in this class arm
        $students = $this->enrollmentRepo->getStudentsByClassAndSession($cId, $selectedSessionId);

        // Check if a specific subject is selected
        $selectedSubjectId = (int)($request->get('subject_id') ?: 0);
        $selectedClassSubjectId = (int)($request->get('class_subject_id') ?: 0);

        $targetClassSubject = null;
        if ($selectedClassSubjectId > 0) {
            foreach ($classSubjects as $cs) {
                if ((int)$cs->id === $selectedClassSubjectId) {
                    $targetClassSubject = $cs;
                    $selectedSubjectId = $cs->subjectId;
                    break;
                }
            }
        } elseif ($selectedSubjectId > 0) {
            foreach ($classSubjects as $cs) {
                if ((int)$cs->subjectId === $selectedSubjectId) {
                    $targetClassSubject = $cs;
                    break;
                }
            }
        }

        // Mode B: Individual Subject CA Sheet Selected
        $subjectSheetData = null;
        if ($targetClassSubject) {
            $categories = $selectedTerm 
                ? $this->gradebookRepo->getCategoriesByContext($selectedSessionId, $selectedTerm->id, $targetClassSubject->id) 
                : [];

            $subjectStudents = $this->enrollmentRepo->getStudentsBySubjectAndSession(
                $cId,
                $targetClassSubject->subjectId,
                $selectedSessionId
            );

            $rawScores = $this->gradebookRepo->getScoresByClassSubject($targetClassSubject->id);
            $scoreMatrix = [];
            foreach ($rawScores as $s) {
                $scoreMatrix[$s->studentId][$s->assessmentCategoryId] = $s->rawScore;
            }

            $termResults = $selectedTerm 
                ? $this->gradebookRepo->getTermResultsByClassSubject($targetClassSubject->id, $selectedTerm->id) 
                : [];

            $resultMap = [];
            foreach ($termResults as $tr) {
                $resultMap[$tr->studentId] = $tr;
            }

            $isLocked = $selectedTerm 
                ? $this->gradebookRepo->isClassSubjectLocked($targetClassSubject->id, $selectedTerm->id) 
                : false;

            $stats = $selectedTerm 
                ? $this->gradebookRepo->getTermResultsStatsByClassSubject($targetClassSubject->id, $selectedTerm->id) 
                : ['total_students' => 0, 'avg_score' => null, 'min_score' => null, 'max_score' => null, 'locked' => false];

            $subjectSheetData = [
                'classSubject' => $targetClassSubject,
                'categories' => $categories,
                'students' => $subjectStudents,
                'scoreMatrix' => $scoreMatrix,
                'resultMap' => $resultMap,
                'isLocked' => $isLocked,
                'stats' => $stats,
            ];
        }

        // Mode A: Master Broadsheet (All Subjects for this Class Arm)
        $broadsheetData = null;
        if (!$targetClassSubject && $selectedTerm) {
            $resultsMatrix = $this->gradebookRepo->getTermResultsMatrixByClass($cId, $selectedTerm->id);
            $summaries = $this->gradebookRepo->getSummariesByClassAndTerm($cId, $selectedTerm->id);
            $summaryMap = [];
            foreach ($summaries as $sm) {
                $summaryMap[$sm->studentId] = $sm;
            }

            // Calculate subject-level means for broadsheet footer
            $subjectAverages = [];
            foreach ($classSubjects as $cs) {
                $stats = $this->gradebookRepo->getTermResultsStatsByClassSubject($cs->id, $selectedTerm->id);
                $subjectAverages[$cs->subjectId] = $stats['avg_score'];
            }

            $broadsheetData = [
                'resultsMatrix' => $resultsMatrix,
                'summaryMap' => $summaryMap,
                'subjectAverages' => $subjectAverages,
            ];
        }

        return Response::html($this->render('admin/gradebook/class', [
            'title' => "{$class->name} ({$class->sectionArm}) — Gradebook & Broadsheet",
            'headerTitle' => 'Class Gradebook & Broadsheet',
            'user' => $userContext,
            'class' => $class,
            'sessions' => $allSessions,
            'selectedSession' => $selectedSession,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'selectedTermId' => $selectedTermId,
            'classSubjects' => $classSubjects,
            'students' => $students,
            'selectedSubjectId' => $selectedSubjectId,
            'targetClassSubject' => $targetClassSubject,
            'subjectSheetData' => $subjectSheetData,
            'broadsheetData' => $broadsheetData,
        ], 'layouts/admin'));
    }

    /**
     * Lock student term results for a class-subject gradebook
     * Route: POST /admin/gradebook/{id}/lock
     */
    public function lock(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $csId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $termId = (int)($request->input('term_id') ?? $request->get('term_id', 0));

        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            return $this->notFound('Class subject not found.');
        }

        $term = $termId > 0 ? $this->academicRepo->findTermById($termId) : $this->academicRepo->findCurrentTerm();
        if (!$term) {
            return $this->redirectWithError('/admin/gradebook', 'Valid academic term required to lock gradebook.');
        }

        // If no term results have been computed yet, compute them so they exist and lock cleanly
        $existingCount = $this->gradebookRepo->getTermResultsStatsByClassSubject($csId, $term->id)['total_students'];
        if ($existingCount === 0) {
            try {
                $this->gradebookService->computeClassSubjectResults($csId, $term->sessionId, $term->id, true, $userContext->id);
            } catch (\Throwable $e) {
                // Ignore if scores were empty
            }
        }

        $this->gradebookRepo->lockClassSubjectResults($csId, $term->id, $userContext->id);

        $redirectUrl = "/admin/gradebook/class/{$classSubject->classId}?session_id={$term->sessionId}&term_id={$term->id}&subject_id={$classSubject->subjectId}";

        return $this->redirectWithSuccess(
            $redirectUrl,
            'Gradebook results locked successfully. Teachers can no longer modify scores.'
        );
    }

    /**
     * Unlock student term results for a class-subject gradebook
     * Route: POST /admin/gradebook/{id}/unlock
     */
    public function unlock(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $csId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $termId = (int)($request->input('term_id') ?? $request->get('term_id', 0));

        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            return $this->notFound('Class subject not found.');
        }

        $term = $termId > 0 ? $this->academicRepo->findTermById($termId) : $this->academicRepo->findCurrentTerm();
        if (!$term) {
            return $this->redirectWithError('/admin/gradebook', 'Valid academic term required to unlock gradebook.');
        }

        $this->gradebookRepo->unlockClassSubjectResults($csId, $term->id);

        $redirectUrl = "/admin/gradebook/class/{$classSubject->classId}?session_id={$term->sessionId}&term_id={$term->id}&subject_id={$classSubject->subjectId}";

        return $this->redirectWithSuccess(
            $redirectUrl,
            'Gradebook unlocked successfully. Teachers may now update scores.'
        );
    }
}
