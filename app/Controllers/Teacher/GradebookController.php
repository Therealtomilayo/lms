<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\GradebookPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\GradingScaleRepository;
use App\Repositories\TeacherRepository;
use App\Services\GradebookService;

/**
 * Controller for Teacher Gradebook Score Entry and Viewing
 */
class GradebookController extends Controller
{
    private GradebookService $gradebookService;
    private GradebookRepository $gradebookRepo;
    private GradingScaleRepository $gradingScaleRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradebookService $gradebookService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?GradingScaleRepository $gradingScaleRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradebookService = $gradebookService ?? new GradebookService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->gradingScaleRepo = $gradingScaleRepo ?? new GradingScaleRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    /**
     * List gradebooks for assigned class-subjects.
     * Route: GET /teacher/gradebook
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();

        $classSubjects = $teacherId !== null
            ? $this->academicRepo->findClassSubjectsByTeacherId($teacherId)
            : $this->academicRepo->findAllClassSubjects();

        return Response::html($this->render('teacher/gradebook/index', [
            'title' => 'Gradebooks & Continuous Assessment — Claret Faculty Portal',
            'headerTitle' => 'Academic Gradebooks',
            'user' => $userContext,
            'classSubjects' => $classSubjects,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
        ], 'layouts/teacher'));
    }

    /**
     * Show gradebook sheet for a specific class-subject.
     * Route: GET /teacher/gradebook/{classSubjectId}
     */
    public function show(Request $request, array|string|int $classSubjectId, array|string|int|null $termId = null): Response
    {
        $userContext = $this->requireAuthContext($request);
        $csId = is_array($classSubjectId) ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0) : (int)$classSubjectId;
        
        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            return $this->notFound('Class subject not found.');
        }

        if (!GradebookPolicy::canView($userContext, $classSubject)) {
            return $this->forbidden('You are not authorized to view this gradebook.');
        }

        $allTerms = $this->academicRepo->findAllTerms();

        $rawTermId = $termId !== null ? $termId : $request->get('term_id', 0);
        $tId = is_array($rawTermId) ? (int)($rawTermId['termId'] ?? 0) : (int)$rawTermId;
        
        $term = $tId > 0
            ? $this->academicRepo->findTermById($tId)
            : $this->academicRepo->findCurrentTerm();

        if (!$term) {
            $session = $this->academicRepo->getCurrentSession() ?? $this->academicRepo->findActiveSession();
            if ($session) {
                $term = $this->academicRepo->createTerm([
                    'session_id' => $session->id,
                    'name' => '1st Term',
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+90 days')),
                    'status' => 'active',
                ]);
            }
        }

        if (!$term) {
            return $this->notFound('Academic term not found.');
        }

        $session = $this->academicRepo->findSessionById($term->sessionId);
        $sessionId = $session ? $session->id : $term->sessionId;

        $categories = $this->gradebookRepo->getCategoriesByContext($sessionId, $term->id, $csId);
        $enrolledStudents = $this->enrollmentRepo->getStudentsBySubjectAndSession(
            $classSubject->classId,
            $classSubject->subjectId,
            $sessionId
        );

        $scores = $this->gradebookRepo->getScoresByClassSubject($csId);
        $scoreMatrix = [];
        foreach ($scores as $s) {
            $scoreMatrix[$s->studentId][$s->assessmentCategoryId] = $s->rawScore;
        }

        $isLocked = $this->gradebookRepo->isClassSubjectLocked($csId, $term->id);
        $termResults = $this->gradebookRepo->getTermResultsByClassSubject($csId, $term->id);
        $resultMap = [];
        foreach ($termResults as $tr) {
            $resultMap[$tr->studentId] = $tr;
        }

        $sName = $classSubject->subject?->name ?? 'Subject';
        $cName = $classSubject->schoolClass?->name ?? 'Class';

        return Response::html($this->render('teacher/gradebook/sheet', [
            'title' => "{$sName} ({$cName}) — Gradebook Sheet",
            'headerTitle' => 'Score Sheet & Evaluation',
            'user' => $userContext,
            'classSubject' => $classSubject,
            'session' => $session,
            'term' => $term,
            'allTerms' => $allTerms,
            'categories' => $categories,
            'students' => $enrolledStudents,
            'scoreMatrix' => $scoreMatrix,
            'resultMap' => $resultMap,
            'isLocked' => $isLocked,
        ], 'layouts/teacher'));
    }

    /**
     * Save score records for class-subject gradebook.
     * Route: POST /teacher/gradebook/{classSubjectId}/save
     */
    public function save(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $csId = is_array($classSubjectId) ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0) : (int)$classSubjectId;
        
        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            return $this->notFound('Class subject not found.');
        }

        $data = $request->all();
        $termId = (int)($data['term_id'] ?? $request->get('term_id', 0));
        
        $term = $termId > 0
            ? $this->academicRepo->findTermById($termId)
            : $this->academicRepo->findCurrentTerm();

        if (!$term) {
            $session = $this->academicRepo->getCurrentSession() ?? $this->academicRepo->findActiveSession();
            if ($session) {
                $term = $this->academicRepo->createTerm([
                    'session_id' => $session->id,
                    'name' => '1st Term',
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+90 days')),
                    'status' => 'active',
                ]);
            }
        }

        if (!$term) {
            return $this->notFound('Academic term not found.');
        }

        $isLocked = $this->gradebookRepo->isClassSubjectLocked($csId, $term->id);
        if (!GradebookPolicy::canSaveScores($userContext, $classSubject, $isLocked)) {
            return $this->redirectWithError(
                "/teacher/gradebook/{$csId}?term_id={$term->id}",
                'Cannot save scores. Gradebook is locked by administration or unauthorized.'
            );
        }

        $scoresInput = $data['scores'] ?? [];
        $sessionId = $term->sessionId;

        try {
            $result = $this->gradebookService->saveScores(
                $csId,
                $sessionId,
                $term->id,
                is_array($scoresInput) ? $scoresInput : [],
                $userContext->id
            );

            if (!empty($data['compute_results'])) {
                $this->gradebookService->computeClassSubjectResults(
                    $csId,
                    $sessionId,
                    $term->id,
                    false
                );
            }

            return $this->redirectWithSuccess(
                "/teacher/gradebook/{$csId}?term_id={$term->id}",
                $result->message
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError(
                "/teacher/gradebook/{$csId}?term_id={$term->id}",
                $e->getMessage()
            );
        }
    }
}
