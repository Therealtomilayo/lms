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
     */
    public function start(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $result = $this->quizService->startAttempt($id, $userContext);
            $attempt = $result->data;
            return $this->redirect("/student/quiz-attempts/{$attempt->id}");
        } catch (AuthorizationException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect("/student/quizzes/{$id}");
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        }
    }

    /**
     * CBT Exam Player view (distraction-free interface with server-authoritative timer).
     */
    public function take(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $data = $this->quizService->getAttemptForPlayer($id, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (AuthorizationException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect('/student/quizzes');
        }

        return $this->view('student/quizzes/take', [
            'user' => $userContext,
            'attempt' => $data['attempt'],
            'quiz' => $data['quiz'],
            'questions' => $data['questions'],
            'answers' => $data['answers'],
            'remainingSeconds' => $data['remaining_seconds'],
            'expiresAt' => $data['expires_at'],
        ]);
    }

    /**
     * Autosave a single answer (AJAX or form POST).
     */
    public function autosave(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();

        $questionId = (int)($data['question_id'] ?? 0);
        $selectedOptionId = isset($data['selected_option_id']) && $data['selected_option_id'] !== '' ? (int)$data['selected_option_id'] : null;
        $textAnswer = isset($data['text_answer']) ? (string)$data['text_answer'] : null;

        try {
            $result = $this->quizService->autosaveAnswer(
                attemptId: $id,
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
     */
    public function submit(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();
        $rawAnswers = $data['answers'] ?? [];

        try {
            $result = $this->quizService->submitAttempt(
                attemptId: $id,
                submittedAnswers: is_array($rawAnswers) ? $rawAnswers : [],
                userContext: $userContext
            );

            $request->getSession()?->flash('success', $result->message);
            return $this->redirect("/student/quiz-attempts/{$id}/result");
        } catch (AuthorizationException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect('/student/quizzes');
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', 'Error submitting attempt: ' . $e->getMessage());
            return $this->redirect("/student/quiz-attempts/{$id}");
        }
    }

    /**
     * View attempt result and score summary.
     */
    public function result(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $data = $this->quizService->getAttemptResult($id, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->view('student/quizzes/result', [
            'user' => $userContext,
            'attempt' => $data['attempt'],
            'quiz' => $data['quiz'],
            'answers' => $data['answers'],
            'isTeacher' => $data['is_teacher'],
        ]);
    }
}
