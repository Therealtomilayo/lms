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

        return $this->view('teacher/quizzes/index', [
            'user' => $userContext,
            'quizzes' => $quizzes,
            'classSubjects' => $classSubjects,
            'terms' => $terms,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedTermId' => $selectedTermId,
        ]);
    }

    /**
     * Show quiz creation form.
     */
    public function create(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : 0;

        $classSubjects = $this->academicRepository->findClassSubjectsByTeacherId($teacherId);
        $currentTerm = $this->academicRepository->findCurrentTerm();
        $terms = $this->academicRepository->findAllTerms();

        return $this->view('teacher/quizzes/create', [
            'user' => $userContext,
            'classSubjects' => $classSubjects,
            'currentTerm' => $currentTerm,
            'terms' => $terms,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ]);
    }

    /**
     * Store a newly created quiz.
     */
    public function store(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();

        try {
            $result = $this->quizService->createQuiz($data, $userContext);
            $request->getSession()?->flash('success', $result->message);
            return $this->redirect('/teacher/quizzes/' . $result->data->id . '/questions');
        } catch (ValidationException $e) {
            $request->getSession()?->flash('errors', $e->getErrors());
            $request->getSession()?->flash('old', $data);
            return $this->redirect('/teacher/quizzes/create');
        } catch (AuthorizationException|DomainRuleException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect('/teacher/quizzes/create');
        }
    }

    /**
     * Show form to edit quiz settings.
     */
    public function edit(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quiz = $this->quizRepository->findById($id, false);

        if (!$quiz) {
            return $this->notFound();
        }

        $terms = $this->academicRepository->findAllTerms();

        return $this->view('teacher/quizzes/edit', [
            'user' => $userContext,
            'quiz' => $quiz,
            'terms' => $terms,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ]);
    }

    /**
     * Update quiz settings.
     */
    public function update(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();

        try {
            $result = $this->quizService->updateQuiz($id, $data, $userContext);
            $request->getSession()?->flash('success', $result->message);
            return $this->redirect('/teacher/quizzes');
        } catch (ValidationException $e) {
            $request->getSession()?->flash('errors', $e->getErrors());
            $request->getSession()?->flash('old', $data);
            return $this->redirect("/teacher/quizzes/{$id}/edit");
        } catch (AuthorizationException|DomainRuleException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect("/teacher/quizzes/{$id}/edit");
        }
    }

    /**
     * Show quiz question builder / question picker.
     */
    public function questions(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $quiz = $this->quizRepository->findById($id, true);

        if (!$quiz) {
            return $this->notFound();
        }

        $classSubject = $this->academicRepository->findClassSubjectById($quiz->classSubjectId);
        $subjectId = $classSubject ? $classSubject->subjectId : 0;

        // Fetch question bank questions for this subject
        $availableQuestions = $this->questionBankRepository->findBySubject($subjectId);

        // Get currently selected question IDs and their points
        $selectedQuestions = $quiz->quizQuestions;

        return $this->view('teacher/quizzes/questions', [
            'user' => $userContext,
            'quiz' => $quiz,
            'classSubject' => $classSubject,
            'availableQuestions' => $availableQuestions,
            'selectedQuestions' => $selectedQuestions,
        ]);
    }

    /**
     * Save questions attached to a quiz.
     */
    public function saveQuestions(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();
        $rawQuestions = $data['questions'] ?? [];

        try {
            $result = $this->quizService->setQuestions($id, is_array($rawQuestions) ? $rawQuestions : [], $userContext);
            $request->getSession()?->flash('success', $result->message);
            return $this->redirect("/teacher/quizzes/{$id}/questions");
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect("/teacher/quizzes/{$id}/questions");
        }
    }

    /**
     * Toggle quiz publication status.
     */
    public function publish(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $isPublished = !empty($request->getBody()['is_published']);

        try {
            $result = $this->quizService->publishQuiz($id, $isPublished, $userContext);
            $request->getSession()?->flash('success', $result->message);
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', $e->getMessage());
        }

        return $this->redirect('/teacher/quizzes');
    }

    /**
     * Delete quiz.
     */
    public function delete(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $result = $this->quizService->deleteQuiz($id, $userContext);
            $request->getSession()?->flash('success', $result->message);
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', $e->getMessage());
        }

        return $this->redirect('/teacher/quizzes');
    }
}
