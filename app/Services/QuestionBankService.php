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

            $correctIndex = isset($data['correct_option']) && is_numeric($data['correct_option']) ? (int)$data['correct_option'] : null;

            $hasCorrect = false;
            foreach ($rawOptions as $idx => $opt) {
                $text = trim((string)($opt['option_text'] ?? ''));
                $isCorrect = ($correctIndex !== null) ? ($idx === $correctIndex) : !empty($opt['is_correct']);
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
                throw new ValidationException(['options' => 'Please select the single correct answer option.']);
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

            $correctIndex = isset($data['correct_option']) && is_numeric($data['correct_option']) ? (int)$data['correct_option'] : null;

            $options = [];
            $hasCorrect = false;
            foreach ($rawOptions as $idx => $opt) {
                $text = trim((string)($opt['option_text'] ?? ''));
                $isCorrect = ($correctIndex !== null) ? ($idx === $correctIndex) : !empty($opt['is_correct']);
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
                throw new ValidationException(['options' => 'Please select the single correct answer option.']);
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
     * Parse and bulk create multiple questions from formatted text into the Question Bank.
     */
    public function createBulkQuestions(
        int $subjectId,
        string $bulkText,
        ?string $defaultTopic,
        float $defaultPoints,
        UserContext $userContext
    ): ServiceResult {
        if ($subjectId <= 0) {
            throw new ValidationException(['subject_id' => 'Target subject is required.']);
        }

        if (!QuestionPolicy::canManageQuestionBank($userContext, $subjectId, $this->teacherRepository, $this->academicRepository)) {
            throw new AuthorizationException('You are not authorized to manage questions for this subject.');
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", trim($bulkText));
        if ($normalized === '') {
            throw new ValidationException(['bulk_text' => 'Please paste or enter formatted question text.']);
        }

        // Split text by double blank lines
        $rawBlocks = preg_split('/\n\s*\n/', $normalized);
        if (empty($rawBlocks)) {
            throw new ValidationException(['bulk_text' => 'No question blocks identified.']);
        }

        $parsedQuestions = [];
        $errors = [];

        foreach ($rawBlocks as $bIdx => $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $lines = explode("\n", $block);
            $questionNumber = count($parsedQuestions) + 1;
            
            $promptLines = [];
            $options = []; // ['A' => text, 'B' => text, ...]
            $correctLetter = null;
            $itemTopic = $defaultTopic;
            $itemPoints = $defaultPoints;
            $itemType = Question::TYPE_MCQ;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                // Check meta tags
                if (preg_match('/^TYPE\s*:\s*(mcq|short_answer|short)/i', $trimmed, $m)) {
                    $itemType = str_starts_with(strtolower($m[1]), 'short') ? Question::TYPE_SHORT_ANSWER : Question::TYPE_MCQ;
                    continue;
                }

                if (preg_match('/^TOPIC\s*:\s*(.+)$/i', $trimmed, $m)) {
                    $itemTopic = trim($m[1]);
                    continue;
                }

                if (preg_match('/^POINTS\s*:\s*([0-9\.]+)$/i', $trimmed, $m)) {
                    $itemPoints = max(0.25, (float)$m[1]);
                    continue;
                }

                if (preg_match('/^(?:ANSWER|CORRECT)\s*:\s*([A-Za-z0-9])/i', $trimmed, $m)) {
                    $correctLetter = strtoupper(trim($m[1]));
                    continue;
                }

                // Check MCQ option: e.g. "A) option", "A. option", "1) option"
                if (preg_match('/^([A-Za-z0-9])[\)\.\:]\s*(.+)$/', $trimmed, $m)) {
                    $key = strtoupper(trim($m[1]));
                    $options[$key] = trim($m[2]);
                    continue;
                }

                // If no options have started yet, this line is part of the question prompt
                if (empty($options)) {
                    $promptLines[] = $trimmed;
                }
            }

            $questionText = implode("\n", $promptLines);
            // Remove leading question numbering if present (e.g. "1. What is...", "Q1: What is...")
            $questionText = preg_replace('/^(?:Q\d+[\:\.]|\d+[\.\)])\s*/i', '', $questionText);

            if ($questionText === '') {
                $errors[] = "Question #{$questionNumber}: Question statement prompt is missing.";
                continue;
            }

            if ($itemType === Question::TYPE_MCQ) {
                if (count($options) < 2) {
                    $errors[] = "Question #{$questionNumber} (\"" . substr($questionText, 0, 40) . "...\"): MCQ requires at least 2 options (e.g. A) ..., B) ...).";
                    continue;
                }

                if ($correctLetter === null || !isset($options[$correctLetter])) {
                    // Try numeric match (e.g. 1 -> A, 2 -> B)
                    $optKeys = array_keys($options);
                    $numIdx = is_numeric($correctLetter) ? ((int)$correctLetter - 1) : null;
                    if ($numIdx !== null && isset($optKeys[$numIdx])) {
                        $correctLetter = $optKeys[$numIdx];
                    } else {
                        $errors[] = "Question #{$questionNumber}: Missing or invalid ANSWER key (e.g. ANSWER: B). Options available: " . implode(', ', array_keys($options)) . ".";
                        continue;
                    }
                }

                $formattedOptions = [];
                foreach ($options as $key => $optText) {
                    $formattedOptions[] = [
                        'option_text' => $optText,
                        'is_correct' => ((string)$key === (string)$correctLetter),
                    ];
                }

                $parsedQuestions[] = [
                    'question_text' => $questionText,
                    'type' => Question::TYPE_MCQ,
                    'topic' => $itemTopic,
                    'default_points' => $itemPoints,
                    'options' => $formattedOptions,
                ];
            } else {
                $parsedQuestions[] = [
                    'question_text' => $questionText,
                    'type' => Question::TYPE_SHORT_ANSWER,
                    'topic' => $itemTopic,
                    'default_points' => $itemPoints,
                    'options' => [],
                ];
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(['bulk_text' => implode("<br>", $errors)]);
        }

        if (empty($parsedQuestions)) {
            throw new ValidationException(['bulk_text' => 'No valid questions could be parsed from the provided text.']);
        }

        // Execute bulk insertion in transaction
        $pdo = $this->questionBankRepository->getPdo();
        $pdo->beginTransaction();

        try {
            foreach ($parsedQuestions as $qData) {
                $this->questionBankRepository->createQuestion(
                    subjectId: $subjectId,
                    questionText: $qData['question_text'],
                    type: $qData['type'],
                    defaultPoints: $qData['default_points'],
                    createdBy: $userContext->id,
                    topic: $qData['topic'],
                    options: $qData['options']
                );
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw new ValidationException(['bulk_text' => 'Failed to save questions: ' . $e->getMessage()]);
        }

        $count = count($parsedQuestions);
        return ServiceResult::success(
            null,
            "Successfully created {$count} assessment question" . ($count > 1 ? 's' : '') . " in the Question Bank."
        );
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
