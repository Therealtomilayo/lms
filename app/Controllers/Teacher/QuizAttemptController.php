<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\QuizRepository;
use App\Services\QuizService;

/**
 * Controller for Teacher Quiz Attempts Overview, Manual Short-Answer Grading, and Reset Actions
 */
class QuizAttemptController extends Controller
{
    private QuizService $quizService;
    private QuizRepository $quizRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?QuizService $quizService = null,
        ?QuizRepository $quizRepository = null
    ) {
        parent::__construct($authenticator);
        $this->quizService = $quizService ?? new QuizService();
        $this->quizRepository = $quizRepository ?? new QuizRepository();
    }

    /**
     * View list of student attempts for a quiz.
     * Route: GET /teacher/quizzes/{id}/attempts
     */
    public function index(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $data = $this->quizService->getQuizAttempts($quizId, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return Response::html($this->render('teacher/quizzes/attempts', [
            'title' => 'Student Attempts — ' . htmlspecialchars($data['quiz']->title),
            'headerTitle' => 'Student Exam Results & Attempts',
            'user' => $userContext,
            'quiz' => $data['quiz'],
            'attempts' => $data['attempts'],
        ], 'layouts/teacher'));
    }

    /**
     * Show grading interface for an individual student attempt.
     * Route: GET /teacher/quizzes/{quizId}/attempts/{attemptId}/grade
     */
    public function showGradeForm(Request $request, array|string|int $quizId, array|string|int $attemptId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizIdInt = is_array($quizId) ? (int)($quizId['quizId'] ?? $quizId['id'] ?? 0) : (int)$quizId;
        $attemptIdInt = is_array($attemptId) ? (int)($attemptId['attemptId'] ?? $attemptId['id'] ?? 0) : (int)$attemptId;

        try {
            $data = $this->quizService->getAttemptResult($attemptIdInt, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return Response::html($this->render('teacher/quizzes/grade_attempt', [
            'title' => 'Grade Attempt #' . $attemptIdInt . ' — ' . htmlspecialchars($data['quiz']->title),
            'headerTitle' => 'Manual Evaluation & Grading',
            'user' => $userContext,
            'quiz' => $data['quiz'],
            'attempt' => $data['attempt'],
            'answers' => $data['answers'],
        ], 'layouts/teacher'));
    }

    /**
     * Submit manual grades and comments for short answer questions.
     * Route: POST /teacher/quizzes/{quizId}/attempts/{attemptId}/grade
     */
    public function gradeShortAnswers(Request $request, array|string|int $quizId, array|string|int $attemptId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizIdInt = is_array($quizId) ? (int)($quizId['quizId'] ?? $quizId['id'] ?? 0) : (int)$quizId;
        $attemptIdInt = is_array($attemptId) ? (int)($attemptId['attemptId'] ?? $attemptId['id'] ?? 0) : (int)$attemptId;

        $data = $request->all();
        $rawGrades = $data['grades'] ?? [];

        try {
            $result = $this->quizService->gradeAttemptShortAnswers($attemptIdInt, is_array($rawGrades) ? $rawGrades : [], $userContext);
            return $this->redirectWithSuccess("/teacher/quizzes/{$quizIdInt}/attempts", $result->message);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/quizzes/{$quizIdInt}/attempts/{$attemptIdInt}/grade", $e->getMessage());
        }
    }

    /**
     * Reset a student attempt.
     * Route: POST /teacher/quizzes/{quizId}/attempts/{attemptId}/reset
     */
    public function reset(Request $request, array|string|int $quizId, array|string|int $attemptId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizIdInt = is_array($quizId) ? (int)($quizId['quizId'] ?? $quizId['id'] ?? 0) : (int)$quizId;
        $attemptIdInt = is_array($attemptId) ? (int)($attemptId['attemptId'] ?? $attemptId['id'] ?? 0) : (int)$attemptId;

        try {
            $result = $this->quizService->resetStudentAttempt($attemptIdInt, $userContext);
            return $this->redirectWithSuccess("/teacher/quizzes/{$quizIdInt}/attempts", $result->message);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/quizzes/{$quizIdInt}/attempts", $e->getMessage());
        }
    }
}
