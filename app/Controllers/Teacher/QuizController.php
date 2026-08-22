<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\QuizRepository;
use App\Repositories\TeacherRepository;
use App\Services\QuizService;

/**
 * Controller for Teacher Quiz Authoring, Question Composition, and Publication
 */
class QuizController extends Controller
{
    private QuizService $quizService;
    private QuizRepository $quizRepository;
    private QuestionBankRepository $questionBankRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?QuizService $quizService = null,
        ?QuizRepository $quizRepository = null,
        ?QuestionBankRepository $questionBankRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ) {
        parent::__construct($authenticator);
        $this->quizService = $quizService ?? new QuizService();
        $this->quizRepository = $quizRepository ?? new QuizRepository();
        $this->questionBankRepository = $questionBankRepository ?? new QuestionBankRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
    }

    /**
     * List quizzes created by teacher.
     * Route: GET /teacher/quizzes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : 0;

        $classSubjects = $this->academicRepository->findClassSubjectsByTeacherId($teacherId);
        $terms = $this->academicRepository->findAllTerms();

        $selectedClassSubjectId = (int)$request->get('class_subject_id', 0) ?: null;
        $selectedTermId = (int)$request->get('term_id', 0) ?: null;

        $quizzes = $this->quizService->getTeacherQuizzes($userContext, $selectedClassSubjectId, $selectedTermId);

        return Response::html($this->render('teacher/quizzes/index', [
            'title' => 'CBT Quizzes & Assessments — Claret Faculty Portal',
            'headerTitle' => 'Computer-Based Testing (CBT)',
            'user' => $userContext,
            'quizzes' => $quizzes,
            'classSubjects' => $classSubjects,
            'terms' => $terms,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedTermId' => $selectedTermId,
        ], 'layouts/teacher'));
    }

    /**
     * Show quiz creation form.
     * Route: GET /teacher/quizzes/create
     */
    public function create(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : 0;

        $classSubjects = $this->academicRepository->findClassSubjectsByTeacherId($teacherId);
        $currentTerm = $this->academicRepository->findCurrentTerm();
        $terms = $this->academicRepository->findAllTerms();

        return Response::html($this->render('teacher/quizzes/create', [
            'title' => 'Create CBT Quiz — Claret Faculty Portal',
            'headerTitle' => 'Setup New CBT Exam',
            'user' => $userContext,
            'classSubjects' => $classSubjects,
            'currentTerm' => $currentTerm,
            'terms' => $terms,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ], 'layouts/teacher'));
    }

    /**
     * Store a newly created quiz.
     * Route: POST /teacher/quizzes/create
     */
    public function store(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->all();

        try {
            $result = $this->quizService->createQuiz($data, $userContext);
            return $this->redirectWithSuccess(
                '/teacher/quizzes/' . $result->data->id . '/questions',
                $result->message
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/teacher/quizzes/create', $e->getErrors(), $data);
        } catch (AuthorizationException|DomainRuleException $e) {
            return $this->redirectWithError('/teacher/quizzes/create', $e->getMessage());
        }
    }

    /**
     * Show form to edit quiz settings.
     * Route: GET /teacher/quizzes/{id}/edit
     */
    public function edit(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $quiz = $this->quizRepository->findById($quizId, false);

        if (!$quiz) {
            return $this->notFound('Quiz not found.');
        }

        $terms = $this->academicRepository->findAllTerms();

        return Response::html($this->render('teacher/quizzes/edit', [
            'title' => 'Edit Quiz Settings — ' . htmlspecialchars($quiz->title),
            'headerTitle' => 'Edit Quiz Settings',
            'user' => $userContext,
            'quiz' => $quiz,
            'terms' => $terms,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ], 'layouts/teacher'));
    }

    /**
     * Update quiz settings.
     * Route: POST /teacher/quizzes/{id}/edit
     */
    public function update(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->all();

        try {
            $result = $this->quizService->updateQuiz($quizId, $data, $userContext);
            return $this->redirectWithSuccess('/teacher/quizzes', $result->message);
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/teacher/quizzes/{$quizId}/edit", $e->getErrors(), $data);
        } catch (AuthorizationException|DomainRuleException $e) {
            return $this->redirectWithError("/teacher/quizzes/{$quizId}/edit", $e->getMessage());
        }
    }

    /**
     * Show quiz question builder / question picker.
     * Route: GET /teacher/quizzes/{id}/questions
     */
    public function questions(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $quiz = $this->quizRepository->findById($quizId, true);

        if (!$quiz) {
            return $this->notFound('Quiz not found.');
        }

        $classSubject = $this->academicRepository->findClassSubjectById($quiz->classSubjectId);
        $subjectId = $classSubject ? $classSubject->subjectId : 0;

        // Fetch question bank questions for this subject
        $availableQuestions = $this->questionBankRepository->findBySubject($subjectId);

        // Get currently selected question IDs and their points
        $selectedQuestions = $quiz->quizQuestions;

        return Response::html($this->render('teacher/quizzes/questions', [
            'title' => 'Question Builder — ' . htmlspecialchars($quiz->title),
            'headerTitle' => 'Quiz Questions & Point Allocation',
            'user' => $userContext,
            'quiz' => $quiz,
            'classSubject' => $classSubject,
            'availableQuestions' => $availableQuestions,
            'selectedQuestions' => $selectedQuestions,
        ], 'layouts/teacher'));
    }

    /**
     * Save questions attached to a quiz.
     * Route: POST /teacher/quizzes/{id}/questions
     */
    public function saveQuestions(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->all();
        $rawQuestions = $data['questions'] ?? [];

        try {
            $result = $this->quizService->setQuestions($quizId, is_array($rawQuestions) ? $rawQuestions : [], $userContext);
            return $this->redirectWithSuccess("/teacher/quizzes/{$quizId}/questions", $result->message);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/quizzes/{$quizId}/questions", $e->getMessage());
        }
    }

    /**
     * Toggle quiz publication status.
     * Route: POST /teacher/quizzes/{id}/publish
     */
    public function publish(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->all();
        $isPublished = !empty($data['is_published']);

        try {
            $result = $this->quizService->publishQuiz($quizId, $isPublished, $userContext);
            return $this->redirectWithSuccess('/teacher/quizzes', $result->message);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/quizzes', $e->getMessage());
        }
    }

    /**
     * Delete quiz.
     * Route: POST /teacher/quizzes/{id}/delete
     */
    public function delete(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quizId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->quizService->deleteQuiz($quizId, $userContext);
            return $this->redirectWithSuccess('/teacher/quizzes', $result->message);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/quizzes', $e->getMessage());
        }
    }
}
