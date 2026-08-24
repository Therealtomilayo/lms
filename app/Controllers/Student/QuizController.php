<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Services\QuizService;

/**
 * Controller for Student Quiz Catalog and Pre-Exam Instructions
 */
class QuizController extends Controller
{
    private QuizService $quizService;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?QuizService $quizService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->quizService = $quizService ?? new QuizService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    /**
     * List quizzes available for student.
     * Route: GET /student/quizzes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();

        $data = $this->quizService->getStudentQuizzes($userContext);

        return Response::html($this->render('student/quizzes/index', [
            'title' => 'Online CBT Quizzes & Assessments — Student Portal',
            'headerTitle' => 'Computer-Based Assessments',
            'user' => $userContext,
            'student' => $student,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'activeQuizzes' => $data['active'] ?? [],
            'completedQuizzes' => $data['completed'] ?? [],
        ], 'layouts/student'));
    }

    /**
     * Show quiz instructions before taking.
     * Route: GET /student/quizzes/{id}
     */
    public function show(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $data = $this->quizService->getQuizForStudent($quizId, $userContext);
        } catch (ResourceNotFoundException $e) {
            return Response::notFound('CBT Quiz assessment not found.');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }

        $student = $this->studentRepo->findByUserId($userContext->id);
        $activeTerm = $this->academicRepo->findCurrentTerm();

        return Response::html($this->render('student/quizzes/show', [
            'title' => "{$data['quiz']->title} — Exam Guidelines",
            'headerTitle' => 'Assessment Guidelines',
            'user' => $userContext,
            'student' => $student,
            'activeTerm' => $activeTerm,
            'quiz' => $data['quiz'],
            'attempts' => $data['attempts'],
            'activeAttempt' => $data['active_attempt'],
            'canStart' => $data['can_start'],
        ], 'layouts/student'));
    }
}
