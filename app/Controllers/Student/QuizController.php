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
 * Controller for Student Quiz Catalog and Pre-Exam Instructions
 */
class QuizController extends Controller
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
     * List quizzes available for student.
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $this->quizService->getStudentQuizzes($userContext);

        return $this->view('student/quizzes/index', [
            'user' => $userContext,
            'activeQuizzes' => $data['active'],
            'completedQuizzes' => $data['completed'],
        ]);
    }

    /**
     * Show quiz instructions before taking.
     */
    public function show(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $data = $this->quizService->getQuizForStudent($id, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->view('student/quizzes/show', [
            'user' => $userContext,
            'quiz' => $data['quiz'],
            'attempts' => $data['attempts'],
            'activeAttempt' => $data['active_attempt'],
            'canStart' => $data['can_start'],
        ]);
    }
}
