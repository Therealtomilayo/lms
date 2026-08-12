<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Policies\QuizPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PDO;

/**
 * Application Service for Quizzes and CBT Assessment Engine
 */
class QuizService
{
    private QuizRepository $quizRepository;
    private QuestionBankRepository $questionBankRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;
    private StudentRepository $studentRepository;
    private EnrollmentRepository $enrollmentRepository;
    private ParentRepository $parentRepository;

    public function __construct(
        ?QuizRepository $quizRepository = null,
        ?QuestionBankRepository $questionBankRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?ParentRepository $parentRepository = null
    ) {
        $this->quizRepository = $quizRepository ?? new QuizRepository();
        $this->questionBankRepository = $questionBankRepository ?? new QuestionBankRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
    }

    // =========================================================================
    // 1. Teacher Authoring & Quiz Management
    // =========================================================================

    /**
     * Create a new quiz assessment.
     *
     * @param array<string, mixed> $data
     */
    public function createQuiz(array $data, UserContext $userContext): ServiceResult
    {
        $classSubjectId = (int)($data['class_subject_id'] ?? 0);
        $termId = (int)($data['term_id'] ?? 0);

        if ($classSubjectId <= 0 || $termId <= 0) {
            throw new ValidationException([
                'class_subject_id' => $classSubjectId <= 0 ? 'Class Subject is required.' : null,
                'term_id' => $termId <= 0 ? 'Term is required.' : null,
            ]);
        }

        if (!QuizPolicy::canCreateQuiz($userContext, $classSubjectId, $termId, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to create a quiz for this class and subject.');
        }

        $this->validateQuizPayload($data);

        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : 0;
        if ($userContext->hasAnyRole(['super_admin', 'admin']) && $teacherId === 0) {
            // Assign to class_subject teacher
            $cs = $this->academicRepository->findClassSubjectById($classSubjectId);
            $teacherId = $cs ? $cs->teacherId : 0;
        }

        $title = trim((string)($data['title'] ?? ''));
        $instructions = isset($data['instructions']) ? trim((string)$data['instructions']) : null;
        $timeLimitMinutes = max(0, (int)($data['time_limit_minutes'] ?? 0));
        $maxAttempts = max(1, (int)($data['max_attempts'] ?? 1));
        $isPublished = !empty($data['is_published']);
        $assessmentCategoryId = !empty($data['assessment_category_id']) ? (int)$data['assessment_category_id'] : null;

        $quiz = $this->quizRepository->create(
            classSubjectId: $classSubjectId,
            termId: $termId,
            teacherId: $teacherId,
            title: $title,
            instructions: $instructions,
            timeLimitMinutes: $timeLimitMinutes,
            maxAttempts: $maxAttempts,
            isPublished: $isPublished,
            assessmentCategoryId: $assessmentCategoryId
        );

        return ServiceResult::success($quiz, 'Quiz assessment created successfully.');
    }

    /**
     * Update quiz settings.
     *
     * @param array<string, mixed> $data
     */
    public function updateQuiz(int $id, array $data, UserContext $userContext): ServiceResult
    {
        $quiz = $this->quizRepository->findById($id, false);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$id} not found.");
        }

        if (!QuizPolicy::canEditQuiz($userContext, $quiz, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to edit this quiz.');
        }

        $this->validateQuizPayload($data);

        $title = trim((string)($data['title'] ?? $quiz->title));
        $instructions = isset($data['instructions']) ? trim((string)$data['instructions']) : $quiz->instructions;
        $timeLimitMinutes = max(0, (int)($data['time_limit_minutes'] ?? $quiz->timeLimitMinutes));
        $maxAttempts = max(1, (int)($data['max_attempts'] ?? $quiz->maxAttempts));
        $assessmentCategoryId = isset($data['assessment_category_id']) ? (!empty($data['assessment_category_id']) ? (int)$data['assessment_category_id'] : null) : $quiz->assessmentCategoryId;

        $updated = $this->quizRepository->update(
            id: $id,
            title: $title,
            instructions: $instructions,
            timeLimitMinutes: $timeLimitMinutes,
            maxAttempts: $maxAttempts,
            assessmentCategoryId: $assessmentCategoryId
        );

        return ServiceResult::success($updated, 'Quiz updated successfully.');
    }

    /**
     * Sync questions from the Question Bank into a quiz with points and ordering.
     *
     * @param array<int, array{question_id: int, points: float, sort_order: int}> $questions
     */
    public function setQuestions(int $quizId, array $questions, UserContext $userContext): ServiceResult
    {
        $quiz = $this->quizRepository->findById($quizId, false);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$quizId} not found.");
        }

        if (!QuizPolicy::canEditQuiz($userContext, $quiz, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to modify questions for this quiz.');
        }

        $formatted = [];
        $order = 1;
        foreach ($questions as $q) {
            $qId = (int)($q['question_id'] ?? 0);
            $pts = max(0.25, (float)($q['points'] ?? 1.00));
            if ($qId > 0) {
                $formatted[] = [
                    'question_id' => $qId,
                    'points' => $pts,
                    'sort_order' => (int)($q['sort_order'] ?? $order++),
                ];
            }
        }

        $this->quizRepository->syncQuestions($quizId, $formatted);
        $updatedQuiz = $this->quizRepository->findById($quizId, true);

        return ServiceResult::success($updatedQuiz, 'Quiz questions updated successfully.');
    }

    /**
     * Publish or unpublish a quiz.
     */
    public function publishQuiz(int $id, bool $isPublished, UserContext $userContext): ServiceResult
    {
        $quiz = $this->quizRepository->findById($id, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$id} not found.");
        }

        if (!QuizPolicy::canEditQuiz($userContext, $quiz, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to change the publication status of this quiz.');
        }

        if ($isPublished && empty($quiz->quizQuestions)) {
            throw new DomainRuleException('Cannot publish a quiz that has no questions. Please add at least one question.');
        }

        $this->quizRepository->setPublished($id, $isPublished);
        $quiz = $this->quizRepository->findById($id, true);

        $msg = $isPublished ? 'Quiz published and visible to enrolled students.' : 'Quiz unpublished (draft mode).';
        return ServiceResult::success($quiz, $msg);
    }

    /**
     * Delete a quiz assessment.
     */
    public function deleteQuiz(int $id, UserContext $userContext): ServiceResult
    {
        $quiz = $this->quizRepository->findById($id, false);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$id} not found.");
        }

        if (!QuizPolicy::canEditQuiz($userContext, $quiz, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to delete this quiz.');
        }

        $attempts = $this->quizRepository->getAttemptsByQuiz($id);
        if (!empty($attempts)) {
            throw new DomainRuleException('Cannot delete a quiz that already has student attempts. Please unpublish it instead.');
        }

        $this->quizRepository->delete($id);
        return ServiceResult::success(null, 'Quiz deleted successfully.');
    }

    /**
     * Get quizzes managed by the teacher.
     */
    public function getTeacherQuizzes(UserContext $userContext, ?int $classSubjectId = null, ?int $termId = null): array
    {
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        if (!$teacher && !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return [];
        }

        $teacherId = $teacher ? $teacher->id : 0;
        return $this->quizRepository->findByTeacher($teacherId, $classSubjectId, $termId);
    }

    // =========================================================================
    // 2. Student CBT Assessment Execution (Player & Lifecycle)
    // =========================================================================

    /**
     * Get list of quizzes for student dashboard.
     */
    public function getStudentQuizzes(UserContext $userContext): array
    {
        $student = $this->studentRepository->findByUserId($userContext->id);
        if (!$student) {
            return ['active' => [], 'completed' => []];
        }

        $currentSession = $this->academicRepository->findCurrentSession();
        $currentTerm = $this->academicRepository->findCurrentTerm();
        if (!$currentSession) {
            return ['active' => [], 'completed' => []];
        }

        $quizzes = $this->quizRepository->findByStudentEnrolled($student->id, $currentSession->id, $currentTerm?->id);

        $active = [];
        $completed = [];

        foreach ($quizzes as $quiz) {
            $attempts = $this->quizRepository->getStudentAttempts((int)$quiz->id, $student->id);
            $activeAttempt = $this->quizRepository->getActiveAttempt((int)$quiz->id, $student->id);

            $data = [
                'quiz' => $quiz,
                'attempts_taken' => count($attempts),
                'max_attempts' => $quiz->maxAttempts,
                'can_attempt' => count($attempts) < $quiz->maxAttempts || $activeAttempt !== null,
                'has_active_attempt' => $activeAttempt !== null,
                'active_attempt_id' => $activeAttempt?->id,
                'latest_attempt' => !empty($attempts) ? end($attempts) : null,
            ];

            if ($activeAttempt !== null || count($attempts) < $quiz->maxAttempts) {
                $active[] = $data;
            } else {
                $completed[] = $data;
            }
        }

        return [
            'active' => $active,
            'completed' => $completed,
        ];
    }

    /**
     * Get quiz overview before starting attempt.
     */
    public function getQuizForStudent(int $quizId, UserContext $userContext): array
    {
        $quiz = $this->quizRepository->findById($quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$quizId} not found.");
        }

        if (!QuizPolicy::canViewQuiz($userContext, $quiz, $this->academicRepository, $this->teacherRepository, $this->studentRepository, $this->enrollmentRepository, $this->parentRepository)) {
            throw new AuthorizationException('You are not authorized to view this quiz.');
        }

        $student = $this->studentRepository->findByUserId($userContext->id);
        $attempts = $student ? $this->quizRepository->getStudentAttempts($quizId, $student->id) : [];
        $activeAttempt = $student ? $this->quizRepository->getActiveAttempt($quizId, $student->id) : null;

        return [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'active_attempt' => $activeAttempt,
            'can_start' => QuizPolicy::canStartAttempt($userContext, $quiz, $this->studentRepository, $this->enrollmentRepository, $this->quizRepository),
        ];
    }

    /**
     * Start a new quiz attempt for the authenticated student.
     */
    public function startAttempt(int $quizId, UserContext $userContext): ServiceResult
    {
        $quiz = $this->quizRepository->findById($quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$quizId} not found.");
        }

        $student = $this->studentRepository->findByUserId($userContext->id);
        if (!$student) {
            throw new AuthorizationException('Student profile not found.');
        }

        // Check if there is already an in-progress attempt to resume
        $existing = $this->quizRepository->getActiveAttempt($quizId, $student->id);
        if ($existing !== null) {
            return ServiceResult::success($existing, 'Resuming active attempt.');
        }

        if (!QuizPolicy::canStartAttempt($userContext, $quiz, $this->studentRepository, $this->enrollmentRepository, $this->quizRepository)) {
            throw new AuthorizationException('You cannot start a new attempt for this quiz (attempt limit reached or subject not enrolled).');
        }

        $attemptNumber = $this->quizRepository->getAttemptCount($quizId, $student->id) + 1;
        $uuid = $this->generateUuid();
        $startedAt = date('Y-m-d H:i:s');
        $maxScore = $quiz->getTotalMaxScore();

        $attempt = $this->quizRepository->createAttempt(
            uuid: $uuid,
            quizId: $quizId,
            studentId: $student->id,
            attemptNumber: $attemptNumber,
            startedAt: $startedAt,
            maxScore: $maxScore
        );

        return ServiceResult::success($attempt, 'Quiz attempt started successfully.');
    }

    /**
     * Get attempt details and questions sanitized for taking the quiz.
     * Implements deterministic question/option shuffling tied to attempt ID seed.
     *
     * @return array<string, mixed>
     */
    public function getAttemptForPlayer(int $attemptId, UserContext $userContext): array
    {
        $attempt = $this->quizRepository->findAttemptById($attemptId);
        if (!$attempt) {
            throw new ResourceNotFoundException("Quiz attempt #{$attemptId} not found.");
        }

        $quiz = $this->quizRepository->findById($attempt->quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz not found.");
        }

        if (!QuizPolicy::canTakeAttempt($userContext, $attempt, $quiz, $this->studentRepository)) {
            // If attempt has expired on the server side, auto-finalize it
            if ($attempt->isInProgress() && $attempt->hasExpired($quiz->timeLimitMinutes)) {
                $this->submitAttempt($attemptId, [], $userContext);
            }
            throw new AuthorizationException('This quiz attempt is not currently active or has expired.');
        }

        // Deterministic randomization seeded by attempt ID
        $questions = $quiz->quizQuestions;
        $seed = $attempt->id ?? 1;
        mt_srand($seed);

        $shuffledQuestions = $questions;
        // Fisher-Yates shuffle
        for ($i = count($shuffledQuestions) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $tmp = $shuffledQuestions[$i];
            $shuffledQuestions[$i] = $shuffledQuestions[$j];
            $shuffledQuestions[$j] = $tmp;
        }

        // Re-seed for options
        $sanitizedQuestions = [];
        foreach ($shuffledQuestions as $qq) {
            $q = $qq->question;
            if (!$q) continue;

            $options = $q->options;
            if ($q->isMcq() && !empty($options)) {
                for ($i = count($options) - 1; $i > 0; $i--) {
                    $j = mt_rand(0, $i);
                    $tmp = $options[$i];
                    $options[$i] = $options[$j];
                    $options[$j] = $tmp;
                }
            }

            // Strip isCorrect for client
            $sanitizedOptions = array_map(fn($opt) => $opt->toArray(false), $options);
            $sanitizedQuestions[] = [
                'id' => $q->id,
                'type' => $q->type,
                'topic' => $q->topic,
                'question_text' => $q->questionText,
                'points' => $qq->points,
                'options' => $sanitizedOptions,
            ];
        }
        mt_srand(); // reset RNG

        $answers = $this->quizRepository->getAnswersByAttemptId($attemptId);
        $answersGrouped = [];
        foreach ($answers as $a) {
            $answersGrouped[$a->questionId] = [
                'selected_option_id' => $a->selectedOptionId,
                'text_answer' => $a->textAnswer,
            ];
        }

        $remainingSeconds = $attempt->getRemainingSeconds($quiz->timeLimitMinutes);

        return [
            'attempt' => $attempt,
            'quiz' => $quiz,
            'questions' => $sanitizedQuestions,
            'answers' => $answersGrouped,
            'remaining_seconds' => $remainingSeconds,
            'expires_at' => $attempt->getExpiresAt($quiz->timeLimitMinutes),
        ];
    }

    /**
     * Autosave a student's answer to a single question during an active attempt.
     */
    public function autosaveAnswer(
        int $attemptId,
        int $questionId,
        ?int $selectedOptionId,
        ?string $textAnswer,
        UserContext $userContext
    ): ServiceResult {
        $attempt = $this->quizRepository->findAttemptById($attemptId);
        if (!$attempt) {
            throw new ResourceNotFoundException("Attempt #{$attemptId} not found.");
        }

        $quiz = $this->quizRepository->findById($attempt->quizId, false);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz not found.");
        }

        if (!QuizPolicy::canTakeAttempt($userContext, $attempt, $quiz, $this->studentRepository)) {
            throw new AuthorizationException('Cannot save answer: attempt is not active or timer has expired.');
        }

        $answer = $this->quizRepository->upsertAnswer(
            attemptId: $attemptId,
            questionId: $questionId,
            selectedOptionId: $selectedOptionId,
            textAnswer: $textAnswer !== null ? trim($textAnswer) : null
        );

        return ServiceResult::success($answer, 'Answer saved.');
    }

    /**
     * Transactional final submission and auto-grading of a quiz attempt.
     *
     * @param array<int, array{question_id: int, selected_option_id?: ?int, text_answer?: ?string}> $submittedAnswers
     */
    public function submitAttempt(
        int $attemptId,
        array $submittedAnswers,
        UserContext $userContext
    ): ServiceResult {
        $attempt = $this->quizRepository->findAttemptById($attemptId);
        if (!$attempt) {
            throw new ResourceNotFoundException("Attempt #{$attemptId} not found.");
        }

        $quiz = $this->quizRepository->findById($attempt->quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz not found.");
        }

        $student = $this->studentRepository->findByUserId($userContext->id);
        // Allow auto-submission by student owner or system/teacher timeout
        if ($student && $attempt->studentId !== $student->id && !$userContext->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            throw new AuthorizationException('Unauthorized attempt submission.');
        }

        if ($attempt->isSubmitted()) {
            return ServiceResult::success($attempt, 'Attempt was already submitted.');
        }

        $pdo = $this->quizRepository->getPdo();
        $pdo->beginTransaction();

        try {
            // Persist any last-second batch answers submitted
            foreach ($submittedAnswers as $ans) {
                $qId = (int)($ans['question_id'] ?? 0);
                if ($qId > 0) {
                    $optId = isset($ans['selected_option_id']) && $ans['selected_option_id'] !== '' ? (int)$ans['selected_option_id'] : null;
                    $txt = isset($ans['text_answer']) && trim((string)$ans['text_answer']) !== '' ? trim((string)$ans['text_answer']) : null;
                    $this->quizRepository->upsertAnswer($attemptId, $qId, $optId, $txt);
                }
            }

            // Retrieve all answers for auto-grading
            $answers = $this->quizRepository->getAnswersByAttemptId($attemptId);
            $answersByQuestionId = [];
            foreach ($answers as $a) {
                $answersByQuestionId[$a->questionId] = $a;
            }

            $totalScore = 0.0;
            $hasShortAnswer = false;

            // Auto-grade MCQs against true options in Question Bank
            foreach ($quiz->quizQuestions as $qq) {
                $q = $qq->question;
                if (!$q) continue;

                $ans = $answersByQuestionId[$q->id] ?? null;

                if ($q->isMcq()) {
                    $correctOption = $q->getCorrectOption();
                    $isCorrect = $ans && $correctOption && $ans->selectedOptionId === $correctOption->id;

                    $pointsAwarded = $isCorrect ? $qq->points : 0.00;
                    $totalScore += $pointsAwarded;

                    if ($ans && $ans->id) {
                        $this->quizRepository->gradeAnswer($ans->id, $pointsAwarded, $isCorrect ? 'Correct' : 'Incorrect');
                    } else {
                        $createdAns = $this->quizRepository->upsertAnswer($attemptId, $q->id, null, null);
                        if ($createdAns && $createdAns->id) {
                            $this->quizRepository->gradeAnswer($createdAns->id, 0.00, 'Unanswered');
                        }
                    }
                } elseif ($q->isShortAnswer()) {
                    $hasShortAnswer = true;
                    if (!$ans) {
                        $this->quizRepository->upsertAnswer($attemptId, $q->id, null, null);
                    } elseif ($ans->pointsAwarded !== null) {
                        $totalScore += $ans->pointsAwarded;
                    }
                }
            }

            $now = date('Y-m-d H:i:s');
            // If there are short answer questions pending grading, status is 'submitted', otherwise fully 'graded'
            $status = $hasShortAnswer ? QuizAttempt::STATUS_SUBMITTED : QuizAttempt::STATUS_GRADED;

            $this->quizRepository->updateAttemptStatus(
                id: $attemptId,
                status: $status,
                score: $totalScore,
                submittedAt: $now
            );

            $pdo->commit();

            $finalAttempt = $this->quizRepository->findAttemptById($attemptId);
            return ServiceResult::success($finalAttempt, 'Quiz attempt submitted successfully.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get attempt results, score summary, and question breakdown.
     */
    public function getAttemptResult(int $attemptId, UserContext $userContext): array
    {
        $attempt = $this->quizRepository->findAttemptById($attemptId);
        if (!$attempt) {
            throw new ResourceNotFoundException("Quiz attempt #{$attemptId} not found.");
        }

        $quiz = $this->quizRepository->findById($attempt->quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz not found.");
        }

        if (!QuizPolicy::canViewAttemptResult($userContext, $attempt, $quiz, $this->studentRepository, $this->teacherRepository, $this->parentRepository)) {
            throw new AuthorizationException('You are not authorized to view the results of this attempt.');
        }

        $answers = $this->quizRepository->getAnswersByAttemptId($attemptId);

        return [
            'attempt' => $attempt,
            'quiz' => $quiz,
            'answers' => $answers,
            'is_teacher' => $userContext->hasAnyRole(['teacher', 'admin', 'super_admin']),
        ];
    }

    // =========================================================================
    // 3. Teacher Manual Grading & Administration
    // =========================================================================

    /**
     * Get all attempts for a quiz (grading table for teacher).
     */
    public function getQuizAttempts(int $quizId, UserContext $userContext): array
    {
        $quiz = $this->quizRepository->findById($quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz #{$quizId} not found.");
        }

        if (!QuizPolicy::canEditQuiz($userContext, $quiz, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to view attempts for this quiz.');
        }

        $attempts = $this->quizRepository->getAttemptsByQuiz($quizId);

        return [
            'quiz' => $quiz,
            'attempts' => $attempts,
        ];
    }

    /**
     * Manual grading queue for short answers.
     *
     * @param array<int, array{answer_id: int, points_awarded: float, teacher_comment?: ?string}> $grades
     */
    public function gradeAttemptShortAnswers(
        int $attemptId,
        array $grades,
        UserContext $userContext
    ): ServiceResult {
        $attempt = $this->quizRepository->findAttemptById($attemptId);
        if (!$attempt) {
            throw new ResourceNotFoundException("Attempt #{$attemptId} not found.");
        }

        $quiz = $this->quizRepository->findById($attempt->quizId, true);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz not found.");
        }

        if (!QuizPolicy::canGradeOrResetAttempt($userContext, $quiz, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to grade this attempt.');
        }

        $pdo = $this->quizRepository->getPdo();
        $pdo->beginTransaction();

        try {
            foreach ($grades as $g) {
                $ansId = (int)($g['answer_id'] ?? 0);
                $pts = max(0.0, (float)($g['points_awarded'] ?? 0.0));
                $comment = isset($g['teacher_comment']) ? trim((string)$g['teacher_comment']) : null;
                if ($ansId > 0) {
                    $this->quizRepository->gradeAnswer($ansId, $pts, $comment);
                }
            }

            // Recalculate total attempt score
            $answers = $this->quizRepository->getAnswersByAttemptId($attemptId);
            $totalScore = 0.0;
            foreach ($answers as $a) {
                if ($a->pointsAwarded !== null) {
                    $totalScore += $a->pointsAwarded;
                }
            }

            $this->quizRepository->updateAttemptStatus(
                id: $attemptId,
                status: QuizAttempt::STATUS_GRADED,
                score: $totalScore
            );

            $pdo->commit();

            $updated = $this->quizRepository->findAttemptById($attemptId);
            return ServiceResult::success($updated, 'Attempt graded successfully.');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reset a student attempt (e.g. if technical disruption occurred).
     */
    public function resetStudentAttempt(int $attemptId, UserContext $userContext): ServiceResult
    {
        $attempt = $this->quizRepository->findAttemptById($attemptId);
        if (!$attempt) {
            throw new ResourceNotFoundException("Attempt #{$attemptId} not found.");
        }

        $quiz = $this->quizRepository->findById($attempt->quizId, false);
        if (!$quiz) {
            throw new ResourceNotFoundException("Quiz not found.");
        }

        if (!QuizPolicy::canGradeOrResetAttempt($userContext, $quiz, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to reset this attempt.');
        }

        $this->quizRepository->deleteAttempt($attemptId);
        return ServiceResult::success(null, 'Student quiz attempt has been reset.');
    }

    private function validateQuizPayload(array $data): void
    {
        $errors = [];

        if (empty(trim((string)($data['title'] ?? '')))) {
            $errors['title'] = 'Quiz title is required.';
        }

        if (isset($data['time_limit_minutes']) && (int)$data['time_limit_minutes'] < 0) {
            $errors['time_limit_minutes'] = 'Time limit cannot be negative.';
        }

        if (isset($data['max_attempts']) && (int)$data['max_attempts'] < 1) {
            $errors['max_attempts'] = 'Max attempts must be at least 1.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
