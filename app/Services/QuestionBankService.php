<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\Question;
use App\Policies\QuestionPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\TeacherRepository;

/**
 * Application Service for Reusable Question Bank Management
 */
class QuestionBankService
{
    private QuestionBankRepository $questionBankRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;

    public function __construct(
        ?QuestionBankRepository $questionBankRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ) {
        $this->questionBankRepository = $questionBankRepository ?? new QuestionBankRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
    }

    /**
     * Create a new question in the Question Bank.
     *
     * @param array<string, mixed> $data
     */
    public function createQuestion(array $data, UserContext $userContext): ServiceResult
    {
        $subjectId = (int)($data['subject_id'] ?? 0);
        if ($subjectId <= 0) {
            throw new ValidationException(['subject_id' => 'Subject is required.']);
        }

        if (!QuestionPolicy::canManageQuestionBank($userContext, $subjectId, $this->teacherRepository, $this->academicRepository)) {
            throw new AuthorizationException('You are not authorized to manage questions for this subject.');
        }

        $this->validateQuestionPayload($data);

        $type = (string)($data['type'] ?? Question::TYPE_MCQ);
        $topic = isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null;
        $questionText = trim((string)($data['question_text'] ?? ''));
        $defaultPoints = max(0.25, (float)($data['default_points'] ?? 1.00));

        $options = [];
        if ($type === Question::TYPE_MCQ) {
            $rawOptions = $data['options'] ?? [];
            if (!is_array($rawOptions) || count($rawOptions) < 2) {
                throw new ValidationException(['options' => 'MCQ questions require at least two answer options.']);
            }

            $hasCorrect = false;
            foreach ($rawOptions as $opt) {
                $text = trim((string)($opt['option_text'] ?? ''));
                $isCorrect = !empty($opt['is_correct']);
                if ($text !== '') {
                    $options[] = [
                        'option_text' => $text,
                        'is_correct' => $isCorrect,
                    ];
                    if ($isCorrect) {
                        $hasCorrect = true;
                    }
                }
            }

            if (count($options) < 2) {
                throw new ValidationException(['options' => 'At least two non-empty answer options are required.']);
            }
            if (!$hasCorrect) {
                throw new ValidationException(['options' => 'Please designate at least one correct option.']);
            }
        }

        $question = $this->questionBankRepository->createQuestion(
            subjectId: $subjectId,
            questionText: $questionText,
            type: $type,
            defaultPoints: $defaultPoints,
            createdBy: $userContext->id,
            topic: $topic,
            options: $options
        );

        return ServiceResult::success($question, 'Question successfully created in the Question Bank.');
    }

    /**
     * Update an existing Question in the Question Bank.
     *
     * @param array<string, mixed> $data
     */
    public function updateQuestion(int $id, array $data, UserContext $userContext): ServiceResult
    {
        $question = $this->questionBankRepository->findById($id);
        if (!$question) {
            throw new ResourceNotFoundException("Question #{$id} not found.");
        }

        if (!QuestionPolicy::canEditQuestion($userContext, $question, $this->teacherRepository, $this->academicRepository)) {
            throw new AuthorizationException('You are not authorized to edit this question.');
        }

        $this->validateQuestionPayload($data);

        $type = (string)($data['type'] ?? $question->type);
        $topic = isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null;
        $questionText = trim((string)($data['question_text'] ?? ''));
        $defaultPoints = max(0.25, (float)($data['default_points'] ?? 1.00));

        $options = null;
        if ($type === Question::TYPE_MCQ) {
            $rawOptions = $data['options'] ?? [];
            if (!is_array($rawOptions) || count($rawOptions) < 2) {
                throw new ValidationException(['options' => 'MCQ questions require at least two answer options.']);
            }

            $options = [];
            $hasCorrect = false;
            foreach ($rawOptions as $opt) {
                $text = trim((string)($opt['option_text'] ?? ''));
                $isCorrect = !empty($opt['is_correct']);
                if ($text !== '') {
                    $options[] = [
                        'option_text' => $text,
                        'is_correct' => $isCorrect,
                    ];
                    if ($isCorrect) {
                        $hasCorrect = true;
                    }
                }
            }

            if (count($options) < 2) {
                throw new ValidationException(['options' => 'At least two non-empty answer options are required.']);
            }
            if (!$hasCorrect) {
                throw new ValidationException(['options' => 'Please designate at least one correct option.']);
            }
        }

        $updated = $this->questionBankRepository->updateQuestion(
            id: $id,
            questionText: $questionText,
            type: $type,
            defaultPoints: $defaultPoints,
            topic: $topic,
            options: $options
        );

        return ServiceResult::success($updated, 'Question successfully updated.');
    }

    /**
     * Delete a question from the Question Bank.
     */
    public function deleteQuestion(int $id, UserContext $userContext): ServiceResult
    {
        $question = $this->questionBankRepository->findById($id);
        if (!$question) {
            throw new ResourceNotFoundException("Question #{$id} not found.");
        }

        if (!QuestionPolicy::canEditQuestion($userContext, $question, $this->teacherRepository, $this->academicRepository)) {
            throw new AuthorizationException('You are not authorized to delete this question.');
        }

        $this->questionBankRepository->deleteQuestion($id);
        return ServiceResult::success(null, 'Question deleted from Question Bank.');
    }

    /**
     * Get questions for a subject with filters.
     *
     * @return array<string, mixed>
     */
    public function getQuestionsForSubject(
        int $subjectId,
        ?string $topic = null,
        ?string $type = null,
        ?string $search = null,
        UserContext $userContext = null
    ): array {
        if ($userContext && !QuestionPolicy::canManageQuestionBank($userContext, $subjectId, $this->teacherRepository, $this->academicRepository)) {
            throw new AuthorizationException('You are not authorized to view questions for this subject.');
        }

        $questions = $this->questionBankRepository->findBySubject($subjectId, $topic, $type, $search);
        $topics = $this->questionBankRepository->getTopicsBySubject($subjectId);

        return [
            'questions' => $questions,
            'topics' => $topics,
        ];
    }

    public function getQuestionById(int $id, UserContext $userContext): Question
    {
        $question = $this->questionBankRepository->findById($id);
        if (!$question) {
            throw new ResourceNotFoundException("Question #{$id} not found.");
        }

        if (!QuestionPolicy::canEditQuestion($userContext, $question, $this->teacherRepository, $this->academicRepository)) {
            throw new AuthorizationException('You are not authorized to access this question.');
        }

        return $question;
    }

    private function validateQuestionPayload(array $data): void
    {
        $errors = [];

        if (empty(trim((string)($data['question_text'] ?? '')))) {
            $errors['question_text'] = 'Question text cannot be blank.';
        }

        $type = (string)($data['type'] ?? '');
        if (!in_array($type, [Question::TYPE_MCQ, Question::TYPE_SHORT_ANSWER], true)) {
            $errors['type'] = 'Invalid question type.';
        }

        if (isset($data['default_points']) && (float)$data['default_points'] <= 0) {
            $errors['default_points'] = 'Default points must be greater than 0.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
