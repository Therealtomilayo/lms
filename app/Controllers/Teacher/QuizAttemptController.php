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
     */
    public function index(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $data = $this->quizService->getQuizAttempts($id, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->view('teacher/quizzes/attempts', [
            'user' => $userContext,
            'quiz' => $data['quiz'],
            'attempts' => $data['attempts'],
        ]);
    }

    /**
     * Show grading interface for an individual student attempt.
     */
    public function showGradeForm(Request $request, int $quizId, int $attemptId): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $data = $this->quizService->getAttemptResult($attemptId, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->view('teacher/quizzes/grade_attempt', [
            'user' => $userContext,
            'quiz' => $data['quiz'],
            'attempt' => $data['attempt'],
            'answers' => $data['answers'],
        ]);
    }

    /**
     * Submit manual grades and comments for short answer questions.
     */
    public function gradeShortAnswers(Request $request, int $quizId, int $attemptId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();
        $rawGrades = $data['grades'] ?? [];

        try {
            $result = $this->quizService->gradeAttemptShortAnswers($attemptId, is_array($rawGrades) ? $rawGrades : [], $userContext);
            $request->getSession()?->flash('success', $result->message);
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', $e->getMessage());
        }

        return $this->redirect("/teacher/quizzes/{$quizId}/attempts");
    }

    /**
     * Reset a student attempt.
     */
    public function reset(Request $request, int $quizId, int $attemptId): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $result = $this->quizService->resetStudentAttempt($attemptId, $userContext);
            $request->getSession()?->flash('success', $result->message);
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', $e->getMessage());
        }

        return $this->redirect("/teacher/quizzes/{$quizId}/attempts");
    }
}
