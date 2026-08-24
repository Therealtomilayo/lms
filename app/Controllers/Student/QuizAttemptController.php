<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Services\QuizService;

/**
 * Controller for Student CBT Exam Player, Autosaving, Submission, and Results
 */
class QuizAttemptController extends Controller
{
    private QuizService $quizService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?QuizService $quizService = null
    ) {
        parent::__construct($authenticator);
        $this->quizService = $quizService ?? new QuizService();
    }

    /**
     * Start a new quiz attempt.
     * Route: POST /student/quizzes/{id}/attempts
     */
    public function start(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->quizService->startAttempt($quizId, $userContext);
            $attempt = $result->data;
            return Response::redirect("/student/quiz-attempts/{$attempt->id}");
        } catch (AuthorizationException $e) {
            return $this->redirectWithError("/student/quizzes/{$quizId}", $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::notFound('CBT Quiz not found.');
        }
    }

    /**
     * CBT Exam Player view (distraction-free interface with server-authoritative timer).
     * Route: GET /student/quiz-attempts/{id}
     */
    public function take(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $attemptId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $data = $this->quizService->getAttemptForPlayer($attemptId, $userContext);
        } catch (ResourceNotFoundException $e) {
            return Response::notFound('Active exam attempt not found.');
        } catch (AuthorizationException $e) {
            return $this->redirectWithError('/student/quizzes', $e->getMessage());
        }

        return Response::html($this->render('student/quizzes/take', [
            'user' => $userContext,
            'attempt' => $data['attempt'],
            'quiz' => $data['quiz'],
            'questions' => $data['questions'],
            'answers' => $data['answers'],
            'remainingSeconds' => $data['remaining_seconds'],
            'expiresAt' => $data['expires_at'],
        ]));
    }

    /**
     * Autosave a single answer (AJAX or form POST).
     * Route: POST /student/quiz-attempts/{id}/answers
     */
    public function autosave(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $attemptId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->json() ?? $request->all();

        $questionId = (int)($data['question_id'] ?? 0);
        $selectedOptionId = isset($data['selected_option_id']) && $data['selected_option_id'] !== '' ? (int)$data['selected_option_id'] : null;
        $textAnswer = isset($data['text_answer']) ? (string)$data['text_answer'] : null;

        try {
            $result = $this->quizService->autosaveAnswer(
                attemptId: $attemptId,
                questionId: $questionId,
                selectedOptionId: $selectedOptionId,
                textAnswer: $textAnswer,
                userContext: $userContext
            );

            return Response::json([
                'success' => true,
                'message' => $result->message,
            ]);
        } catch (AuthorizationException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Final submission of the quiz attempt.
     * Route: POST /student/quiz-attempts/{id}/submit
     */
    public function submit(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $attemptId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->all();
        $rawAnswers = $data['answers'] ?? $request->post('answers', []);

        try {
            $result = $this->quizService->submitAttempt(
                attemptId: $attemptId,
                submittedAnswers: is_array($rawAnswers) ? $rawAnswers : [],
                userContext: $userContext
            );

            return $this->redirectWithSuccess(
                "/student/quiz-attempts/{$attemptId}/result",
                $result->message
            );
        } catch (AuthorizationException $e) {
            return $this->redirectWithError('/student/quizzes', $e->getMessage());
        } catch (\Throwable $e) {
            return $this->redirectWithError("/student/quiz-attempts/{$attemptId}", 'Error submitting attempt: ' . $e->getMessage());
        }
    }

    /**
     * View attempt result and score summary.
     * Route: GET /student/quiz-attempts/{id}/result
     */
    public function result(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $attemptId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $data = $this->quizService->getAttemptResult($attemptId, $userContext);
        } catch (ResourceNotFoundException $e) {
            return Response::notFound('Quiz attempt result not found.');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }

        return Response::html($this->render('student/quizzes/result', [
            'title' => 'Exam Results & Performance — Student Portal',
            'headerTitle' => 'Assessment Performance',
            'user' => $userContext,
            'attempt' => $data['attempt'],
            'quiz' => $data['quiz'],
            'answers' => $data['answers'],
            'isTeacher' => $data['is_teacher'],
        ], 'layouts/student'));
    }
}
