<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\TeacherRepository;
use App\Services\QuestionBankService;

/**
 * Controller for Teacher Question Bank authoring and repository management
 */
class QuestionBankController extends Controller
{
    private QuestionBankService $questionBankService;
    private QuestionBankRepository $questionBankRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?QuestionBankService $questionBankService = null,
        ?QuestionBankRepository $questionBankRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ) {
        parent::__construct($authenticator);
        $this->questionBankService = $questionBankService ?? new QuestionBankService();
        $this->questionBankRepository = $questionBankRepository ?? new QuestionBankRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
    }

    /**
     * List Question Bank questions for assigned subjects.
     * Route: GET /teacher/question-bank
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);

        if (!$teacher && !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            throw new AuthorizationException('Teacher profile not found.');
        }

        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $this->academicRepository->findClassSubjectsByTeacherId($teacherId);

        // Extract distinct subjects
        $subjects = [];
        foreach ($classSubjects as $cs) {
            if ($cs->subject && !isset($subjects[$cs->subjectId])) {
                $subjects[$cs->subjectId] = $cs->subject;
            }
        }

        $selectedSubjectId = (int)$request->get('subject_id', !empty($subjects) ? (int)array_key_first($subjects) : 0);
        $selectedTopic = $request->get('topic') !== '' ? $request->get('topic') : null;
        $selectedType = $request->get('type') !== '' ? $request->get('type') : null;
        $search = $request->get('search') !== '' ? $request->get('search') : null;

        $questionsData = ['questions' => [], 'topics' => []];
        if ($selectedSubjectId > 0) {
            try {
                $questionsData = $this->questionBankService->getQuestionsForSubject(
                    subjectId: $selectedSubjectId,
                    topic: $selectedTopic,
                    type: $selectedType,
                    search: $search,
                    userContext: $userContext
                );
            } catch (AuthorizationException $e) {
                // Ignore if unassigned subject requested
            }
        }

        return Response::html($this->render('teacher/question_bank/index', [
            'title' => 'Question Bank Directory — Claret Faculty Portal',
            'headerTitle' => 'Assessment Question Bank',
            'user' => $userContext,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedTopic' => $selectedTopic,
            'selectedType' => $selectedType,
            'search' => $search,
            'questions' => $questionsData['questions'],
            'topics' => $questionsData['topics'],
        ], 'layouts/teacher'));
    }

    /**
     * Show form to create a new Question.
     * Route: GET /teacher/question-bank/create
     */
    public function create(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $this->academicRepository->findClassSubjectsByTeacherId($teacherId);

        $subjects = [];
        foreach ($classSubjects as $cs) {
            if ($cs->subject && !isset($subjects[$cs->subjectId])) {
                $subjects[$cs->subjectId] = $cs->subject;
            }
        }

        $subjectId = (int)$request->get('subject_id', !empty($subjects) ? (int)array_key_first($subjects) : 0);

        return Response::html($this->render('teacher/question_bank/create', [
            'title' => 'Create Question — Question Bank',
            'headerTitle' => 'Add Assessment Question',
            'user' => $userContext,
            'subjects' => $subjects,
            'selectedSubjectId' => $subjectId,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ], 'layouts/teacher'));
    }

    /**
     * Store a newly created Question.
     * Route: POST /teacher/question-bank/create
     */
    public function store(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->all();

        try {
            $result = $this->questionBankService->createQuestion($data, $userContext);
            return $this->redirectWithSuccess(
                '/teacher/question-bank?subject_id=' . (int)($data['subject_id'] ?? 0),
                $result->message
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors(
                '/teacher/question-bank/create?subject_id=' . (int)($data['subject_id'] ?? 0),
                $e->getErrors(),
                $data
            );
        } catch (AuthorizationException $e) {
            return $this->redirectWithError('/teacher/question-bank', $e->getMessage());
        }
    }

    /**
     * Show bulk question creator / importer interface.
     * Route: GET /teacher/question-bank/bulk
     */
    public function bulkCreate(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $this->academicRepository->findClassSubjectsByTeacherId($teacherId);

        $subjects = [];
        foreach ($classSubjects as $cs) {
            if ($cs->subject && !isset($subjects[$cs->subjectId])) {
                $subjects[$cs->subjectId] = $cs->subject;
            }
        }

        $subjectId = (int)$request->get('subject_id', !empty($subjects) ? (int)array_key_first($subjects) : 0);

        return Response::html($this->render('teacher/question_bank/bulk', [
            'title' => 'Bulk Import Questions — Question Bank',
            'headerTitle' => 'Bulk Question Importer',
            'user' => $userContext,
            'subjects' => $subjects,
            'selectedSubjectId' => $subjectId,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ], 'layouts/teacher'));
    }

    /**
     * Parse and persist multiple questions in bulk.
     * Route: POST /teacher/question-bank/bulk
     */
    public function bulkStore(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->all();

        $subjectId = (int)($data['subject_id'] ?? 0);
        $bulkText = (string)($data['bulk_text'] ?? '');
        $defaultTopic = isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null;
        $defaultPoints = max(0.25, (float)($data['default_points'] ?? 1.00));

        try {
            $result = $this->questionBankService->createBulkQuestions(
                subjectId: $subjectId,
                bulkText: $bulkText,
                defaultTopic: $defaultTopic,
                defaultPoints: $defaultPoints,
                userContext: $userContext
            );

            return $this->redirectWithSuccess(
                '/teacher/question-bank?subject_id=' . $subjectId,
                $result->message
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors(
                '/teacher/question-bank/bulk?subject_id=' . $subjectId,
                $e->getErrors(),
                $data
            );
        } catch (AuthorizationException $e) {
            return $this->redirectWithError('/teacher/question-bank', $e->getMessage());
        }
    }

    /**
     * Show form to edit an existing Question.
     * Route: GET /teacher/question-bank/{id}/edit
     */
    public function edit(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $questionId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $question = $this->questionBankService->getQuestionById($questionId, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return Response::html($this->render('teacher/question_bank/edit', [
            'title' => 'Edit Question #' . $questionId . ' — Question Bank',
            'headerTitle' => 'Edit Assessment Question',
            'user' => $userContext,
            'question' => $question,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ], 'layouts/teacher'));
    }

    /**
     * Update an existing Question.
     * Route: POST /teacher/question-bank/{id}/edit
     */
    public function update(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $questionId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->all();

        try {
            $result = $this->questionBankService->updateQuestion($questionId, $data, $userContext);
            return $this->redirectWithSuccess(
                '/teacher/question-bank?subject_id=' . (int)$result->data->subjectId,
                $result->message
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors(
                "/teacher/question-bank/{$questionId}/edit",
                $e->getErrors(),
                $data
            );
        } catch (AuthorizationException $e) {
            return $this->redirectWithError('/teacher/question-bank', $e->getMessage());
        }
    }

    /**
     * Delete a question.
     * Route: POST /teacher/question-bank/{id}/delete
     */
    public function delete(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $questionId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->questionBankService->deleteQuestion($questionId, $userContext);
            return $this->redirectWithSuccess('/teacher/question-bank', $result->message);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/question-bank', $e->getMessage());
        }
    }
}
