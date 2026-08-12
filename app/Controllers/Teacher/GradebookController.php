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

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext) {
            return $this->redirect('/login');
        }

        $teacherId = $userContext->getTeacherId();
        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $activeSession = $this->academicRepo->getCurrentSession();
        $activeTerm = $this->academicRepo->getCurrentTerm();

        $classSubjects = $teacherId !== null
            ? $this->academicRepo->getClassSubjectsByTeacher($teacherId)
            : $this->academicRepo->getAllClassSubjects();

        return $this->view('teacher/gradebook/index', [
            'classSubjects' => $classSubjects,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
        ]);
    }

    public function show(Request $request, int|string $classSubjectId, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext) {
            return $this->redirect('/login');
        }

        $csId = (int)$classSubjectId;
        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject not found.');
        }

        if (!GradebookPolicy::canView($userContext, $classSubject)) {
            throw new AuthorizationException('You are not authorized to view this gradebook.');
        }

        $tId = $termId !== null ? (int)$termId : (int)($request->get('term_id', 0) ?: 0);
        $term = $tId > 0
            ? $this->academicRepo->findTermById($tId)
            : $this->academicRepo->getCurrentTerm();

        if (!$term) {
            throw new ResourceNotFoundException('Academic term not found.');
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

        return $this->view('teacher/gradebook/sheet', [
            'classSubject' => $classSubject,
            'session' => $session,
            'term' => $term,
            'categories' => $categories,
            'students' => $enrolledStudents,
            'scoreMatrix' => $scoreMatrix,
            'resultMap' => $resultMap,
            'isLocked' => $isLocked,
        ]);
    }

    public function save(Request $request, int|string $classSubjectId): Response
    {
        $userContext = $this->user($request);
        if (!$userContext) {
            return $this->redirect('/login');
        }

        $csId = (int)$classSubjectId;
        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject not found.');
        }

        $termId = (int)($request->input('term_id') ?? $request->get('term_id') ?? 0);
        $term = $termId > 0
            ? $this->academicRepo->findTermById($termId)
            : $this->academicRepo->getCurrentTerm();

        if (!$term) {
            throw new ResourceNotFoundException('Academic term not found.');
        }

        $isLocked = $this->gradebookRepo->isClassSubjectLocked($csId, $term->id);
        if (!GradebookPolicy::canSaveScores($userContext, $classSubject, $isLocked)) {
            throw new AuthorizationException('Cannot save scores. Gradebook is locked or unauthorized.');
        }

        $scoresInput = $request->input('scores', []);
        $sessionId = $term->sessionId;

        $result = $this->gradebookService->saveScores(
            $csId,
            $sessionId,
            $term->id,
            is_array($scoresInput) ? $scoresInput : [],
            $userContext->getUserId()
        );

        if ($request->input('compute_results')) {
            $this->gradebookService->computeClassSubjectResults(
                $csId,
                $sessionId,
                $term->id,
                false
            );
        }

        return $this->redirectWithSuccess("/teacher/gradebook/{$csId}?term_id={$term->id}", $result->message);
    }
}
