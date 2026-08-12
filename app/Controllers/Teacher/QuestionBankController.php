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

        return $this->view('teacher/question_bank/index', [
            'user' => $userContext,
            'subjects' => $subjects,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedTopic' => $selectedTopic,
            'selectedType' => $selectedType,
            'search' => $search,
            'questions' => $questionsData['questions'],
            'topics' => $questionsData['topics'],
        ]);
    }

    /**
     * Show form to create a new Question.
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

        return $this->view('teacher/question_bank/create', [
            'user' => $userContext,
            'subjects' => $subjects,
            'selectedSubjectId' => $subjectId,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ]);
    }

    /**
     * Store a newly created Question.
     */
    public function store(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();

        try {
            $result = $this->questionBankService->createQuestion($data, $userContext);
            $request->getSession()?->flash('success', $result->message);
            return $this->redirect('/teacher/question-bank?subject_id=' . (int)($data['subject_id'] ?? 0));
        } catch (ValidationException $e) {
            $request->getSession()?->flash('errors', $e->getErrors());
            $request->getSession()?->flash('old', $data);
            return $this->redirect('/teacher/question-bank/create?subject_id=' . (int)($data['subject_id'] ?? 0));
        } catch (AuthorizationException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect('/teacher/question-bank');
        }
    }

    /**
     * Show form to edit an existing Question.
     */
    public function edit(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $question = $this->questionBankService->getQuestionById($id, $userContext);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (AuthorizationException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->view('teacher/question_bank/edit', [
            'user' => $userContext,
            'question' => $question,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ]);
    }

    /**
     * Update an existing Question.
     */
    public function update(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->getBody();

        try {
            $result = $this->questionBankService->updateQuestion($id, $data, $userContext);
            $request->getSession()?->flash('success', $result->message);
            return $this->redirect('/teacher/question-bank?subject_id=' . $result->data->subjectId);
        } catch (ValidationException $e) {
            $request->getSession()?->flash('errors', $e->getErrors());
            $request->getSession()?->flash('old', $data);
            return $this->redirect("/teacher/question-bank/{$id}/edit");
        } catch (AuthorizationException $e) {
            $request->getSession()?->flash('error', $e->getMessage());
            return $this->redirect('/teacher/question-bank');
        }
    }

    /**
     * Delete a question.
     */
    public function delete(Request $request, int $id): Response
    {
        $userContext = $this->requireAuthContext($request);

        try {
            $result = $this->questionBankService->deleteQuestion($id, $userContext);
            $request->getSession()?->flash('success', $result->message);
        } catch (\Throwable $e) {
            $request->getSession()?->flash('error', $e->getMessage());
        }

        return $this->redirect('/teacher/question-bank');
    }
}
