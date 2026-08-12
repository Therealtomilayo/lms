<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ClassSubject;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Quizzes, Quiz Questions, Attempts, and Answers
 */
class QuizRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ==========================================
    // 1. Quiz Management
    // ==========================================

    public function create(
        int $classSubjectId,
        int $termId,
        int $teacherId,
        string $title,
        ?string $instructions = null,
        int $timeLimitMinutes = 0,
        int $maxAttempts = 1,
        bool $isPublished = false,
        ?int $assessmentCategoryId = null
    ): Quiz {
        $now = date('Y-m-d H:i:s');
        $publishedAt = $isPublished ? $now : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO `quizzes` (
                `class_subject_id`, `term_id`, `teacher_id`, `assessment_category_id`,
                `title`, `instructions`, `time_limit_minutes`, `max_attempts`,
                `is_published`, `published_at`, `created_at`, `updated_at`
            ) VALUES (
                :class_subject_id, :term_id, :teacher_id, :assessment_category_id,
                :title, :instructions, :time_limit_minutes, :max_attempts,
                :is_published, :published_at, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            ':class_subject_id' => $classSubjectId,
            ':term_id' => $termId,
            ':teacher_id' => $teacherId,
            ':assessment_category_id' => $assessmentCategoryId,
            ':title' => $title,
            ':instructions' => $instructions !== '' ? $instructions : null,
            ':time_limit_minutes' => max(0, $timeLimitMinutes),
            ':max_attempts' => max(1, $maxAttempts),
            ':is_published' => $isPublished ? 1 : 0,
            ':published_at' => $publishedAt,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $quizId = (int)$this->pdo->lastInsertId();
        return $this->findById($quizId, false) ?? new Quiz(
            id: $quizId,
            classSubjectId: $classSubjectId,
            termId: $termId,
            assessmentCategoryId: $assessmentCategoryId,
            teacherId: $teacherId,
            title: $title,
            instructions: $instructions,
            timeLimitMinutes: $timeLimitMinutes,
            maxAttempts: $maxAttempts,
            isPublished: $isPublished,
            publishedAt: $publishedAt,
            createdAt: $now,
            updatedAt: $now
        );
    }

    public function update(
        int $id,
        string $title,
        ?string $instructions = null,
        int $timeLimitMinutes = 0,
        int $maxAttempts = 1,
        ?int $assessmentCategoryId = null
    ): ?Quiz {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `quizzes` SET
                `title` = :title,
                `instructions` = :instructions,
                `time_limit_minutes` = :time_limit_minutes,
                `max_attempts` = :max_attempts,
                `assessment_category_id` = :assessment_category_id,
                `updated_at` = :updated_at
            WHERE `id` = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':instructions' => $instructions !== '' ? $instructions : null,
            ':time_limit_minutes' => max(0, $timeLimitMinutes),
            ':max_attempts' => max(1, $maxAttempts),
            ':assessment_category_id' => $assessmentCategoryId,
            ':updated_at' => $now,
        ]);

        return $this->findById($id);
    }

    public function setPublished(int $id, bool $isPublished): bool
    {
        $now = date('Y-m-d H:i:s');
        $publishedAt = $isPublished ? $now : null;

        $stmt = $this->pdo->prepare(
            'UPDATE `quizzes` SET 
                `is_published` = :is_published,
                `published_at` = :published_at,
                `updated_at` = :updated_at
            WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':is_published' => $isPublished ? 1 : 0,
            ':published_at' => $publishedAt,
            ':updated_at' => $now,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `quizzes` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function findById(int $id, bool $includeQuestions = true): ?Quiz
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.*, 
                    cs.id AS cs_id, cs.session_id AS cs_session_id, cs.class_id AS cs_class_id, cs.subject_id AS cs_subject_id, cs.teacher_id AS cs_teacher_id,
                    t.name AS term_name, t.session_id AS term_session_id
             FROM `quizzes` q
             LEFT JOIN `class_subjects` cs ON q.class_subject_id = cs.id
             LEFT JOIN `terms` t ON q.term_id = t.id
             WHERE q.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $classSubject = null;
        if (!empty($row['cs_id'])) {
            $classSubject = ClassSubject::fromArray([
                'id' => $row['cs_id'],
                'session_id' => $row['cs_session_id'] ?? 0,
                'class_id' => $row['cs_class_id'],
                'subject_id' => $row['cs_subject_id'],
                'teacher_id' => $row['cs_teacher_id'],
            ]);
        }

        $term = null;
        if (!empty($row['term_id'])) {
            $term = Term::fromArray([
                'id' => $row['term_id'],
                'session_id' => $row['term_session_id'] ?? 0,
                'name' => $row['term_name'] ?? '',
            ]);
        }

        $quizQuestions = $includeQuestions ? $this->getQuestionsForQuiz($id) : [];

        return Quiz::fromArray($row, $classSubject, $term, null, $quizQuestions);
    }

    /**
     * Get quizzes taught by a teacher.
     *
     * @return array<int, Quiz>
     */
    public function findByTeacher(int $teacherId, ?int $classSubjectId = null, ?int $termId = null): array
    {
        $sql = 'SELECT q.*, t.name AS term_name, t.session_id AS term_session_id
                FROM `quizzes` q
                LEFT JOIN `terms` t ON q.term_id = t.id
                WHERE q.teacher_id = :teacher_id';
        $params = [':teacher_id' => $teacherId];

        if ($classSubjectId !== null) {
            $sql .= ' AND q.class_subject_id = :class_subject_id';
            $params[':class_subject_id'] = $classSubjectId;
        }

        if ($termId !== null) {
            $sql .= ' AND q.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        $sql .= ' ORDER BY q.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quizzes = [];
        foreach ($rows as $row) {
            $term = !empty($row['term_id']) ? Term::fromArray([
                'id' => $row['term_id'],
                'session_id' => $row['term_session_id'] ?? 0,
                'name' => $row['term_name'] ?? '',
            ]) : null;

            $quizzes[] = Quiz::fromArray($row, null, $term, null, []);
        }

        return $quizzes;
    }

    /**
     * Get quizzes for a class_subject.
     *
     * @return array<int, Quiz>
     */
    public function findByClassSubject(int $classSubjectId, ?int $termId = null, bool $publishedOnly = false): array
    {
        $sql = 'SELECT q.*, t.name AS term_name, t.session_id AS term_session_id
                FROM `quizzes` q
                LEFT JOIN `terms` t ON q.term_id = t.id
                WHERE q.class_subject_id = :class_subject_id';
        $params = [':class_subject_id' => $classSubjectId];

        if ($termId !== null) {
            $sql .= ' AND q.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        if ($publishedOnly) {
            $sql .= ' AND q.is_published = 1';
        }

        $sql .= ' ORDER BY q.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => Quiz::fromArray($r), $rows);
    }

    /**
     * Get published quizzes for enrolled subjects of a student.
     *
     * @return array<int, Quiz>
     */
    public function findByStudentEnrolled(int $studentId, int $sessionId, ?int $termId = null): array
    {
        $sql = 'SELECT q.*, t.name AS term_name, s.name AS subject_name, s.code AS subject_code
                FROM `quizzes` q
                JOIN `student_subject_enrollments` sse ON q.class_subject_id = sse.class_subject_id
                JOIN `class_subjects` cs ON q.class_subject_id = cs.id
                JOIN `subjects` s ON cs.subject_id = s.id
                LEFT JOIN `terms` t ON q.term_id = t.id
                WHERE sse.student_id = :student_id 
                  AND sse.session_id = :session_id
                  AND sse.status = "active"
                  AND q.is_published = 1';
        $params = [
            ':student_id' => $studentId,
            ':session_id' => $sessionId,
        ];

        if ($termId !== null) {
            $sql .= ' AND q.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        $sql .= ' ORDER BY q.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => Quiz::fromArray($r), $rows);
    }

    // ==========================================
    // 2. Quiz Questions Pivot
    // ==========================================

    /**
     * Sync questions mapped to a quiz.
     *
     * @param array<int, array{question_id: int, points: float, sort_order: int}> $questions
     */
    public function syncQuestions(int $quizId, array $questions): void
    {
        $this->pdo->prepare('DELETE FROM `quiz_questions` WHERE `quiz_id` = :quiz_id')->execute([':quiz_id' => $quizId]);

        if (empty($questions)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `quiz_questions` (`quiz_id`, `question_id`, `points`, `sort_order`, `created_at`, `updated_at`)
             VALUES (:quiz_id, :question_id, :points, :sort_order, :created_at, :updated_at)'
        );

        foreach ($questions as $q) {
            $stmt->execute([
                ':quiz_id' => $quizId,
                ':question_id' => (int)$q['question_id'],
                ':points' => (float)$q['points'],
                ':sort_order' => (int)($q['sort_order'] ?? 0),
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }
    }

    /**
     * Get all questions mapped to a quiz with full options (for teacher/admin).
     *
     * @return array<int, QuizQuestion>
     */
    public function getQuestionsForQuiz(int $quizId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT qq.*, q.subject_id, q.topic, q.question_text, q.type, q.default_points, q.created_by
             FROM `quiz_questions` qq
             JOIN `questions` q ON qq.question_id = q.id
             WHERE qq.quiz_id = :quiz_id
             ORDER BY qq.sort_order ASC, qq.id ASC'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $questionIds = array_map(fn($r) => (int)$r['question_id'], $rows);
        $optionsGrouped = (new QuestionBankRepository($this->pdo))->getOptionsByQuestionIds($questionIds);

        $list = [];
        foreach ($rows as $row) {
            $qId = (int)$row['question_id'];
            $options = $optionsGrouped[$qId] ?? [];
            $question = Question::fromArray([
                'id' => $qId,
                'subject_id' => $row['subject_id'],
                'topic' => $row['topic'],
                'question_text' => $row['question_text'],
                'type' => $row['type'],
                'default_points' => $row['default_points'],
                'created_by' => $row['created_by'],
            ], null, null, $options);

            $list[] = QuizQuestion::fromArray($row, $question);
        }

        return $list;
    }

    // ==========================================
    // 3. Quiz Attempts
    // ==========================================

    public function createAttempt(
        string $uuid,
        int $quizId,
        int $studentId,
        int $attemptNumber,
        string $startedAt,
        float $maxScore
    ): QuizAttempt {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `quiz_attempts` (
                `uuid`, `quiz_id`, `student_id`, `attempt_number`, `started_at`,
                `max_score`, `status`, `created_at`, `updated_at`
            ) VALUES (
                :uuid, :quiz_id, :student_id, :attempt_number, :started_at,
                :max_score, :status, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            ':uuid' => $uuid,
            ':quiz_id' => $quizId,
            ':student_id' => $studentId,
            ':attempt_number' => $attemptNumber,
            ':started_at' => $startedAt,
            ':max_score' => $maxScore,
            ':status' => QuizAttempt::STATUS_IN_PROGRESS,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $attemptId = (int)$this->pdo->lastInsertId();
        return $this->findAttemptById($attemptId) ?? new QuizAttempt(
            id: $attemptId,
            uuid: $uuid,
            quizId: $quizId,
            studentId: $studentId,
            attemptNumber: $attemptNumber,
            startedAt: $startedAt,
            maxScore: $maxScore,
            status: QuizAttempt::STATUS_IN_PROGRESS,
            createdAt: $now,
            updatedAt: $now
        );
    }

    public function findAttemptById(int $id): ?QuizAttempt
    {
        $stmt = $this->pdo->prepare(
            'SELECT qa.*, 
                    q.title AS quiz_title, q.time_limit_minutes, q.class_subject_id, q.term_id, q.teacher_id,
                    u.id AS student_user_id, u.name AS student_name, u.email AS student_email, s.admission_number
             FROM `quiz_attempts` qa
             JOIN `quizzes` q ON qa.quiz_id = q.id
             JOIN `students` s ON qa.student_id = s.id
             JOIN `users` u ON s.user_id = u.id
             WHERE qa.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $quiz = Quiz::fromArray([
            'id' => $row['quiz_id'],
            'title' => $row['quiz_title'] ?? '',
            'time_limit_minutes' => $row['time_limit_minutes'] ?? 0,
            'class_subject_id' => $row['class_subject_id'] ?? 0,
            'term_id' => $row['term_id'] ?? 0,
            'teacher_id' => $row['teacher_id'] ?? 0,
        ]);

        $student = Student::fromArray([
            'id' => $row['student_id'],
            'user_id' => $row['student_user_id'] ?? 0,
            'admission_number' => $row['admission_number'] ?? '',
        ], User::fromArray([
            'id' => $row['student_user_id'] ?? 0,
            'name' => $row['student_name'] ?? '',
            'email' => $row['student_email'] ?? '',
        ]));

        $answers = $this->getAnswersByAttemptId($id);

        return QuizAttempt::fromArray($row, $quiz, $student, $answers);
    }

    public function findAttemptByUuid(string $uuid): ?QuizAttempt
    {
        $stmt = $this->pdo->prepare('SELECT id FROM `quiz_attempts` WHERE `uuid` = :uuid');
        $stmt->execute([':uuid' => $uuid]);
        $id = $stmt->fetchColumn();

        return $id ? $this->findAttemptById((int)$id) : null;
    }

    public function getActiveAttempt(int $quizId, int $studentId): ?QuizAttempt
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM `quiz_attempts` 
             WHERE `quiz_id` = :quiz_id AND `student_id` = :student_id AND `status` = :status
             ORDER BY `id` DESC LIMIT 1'
        );
        $stmt->execute([
            ':quiz_id' => $quizId,
            ':student_id' => $studentId,
            ':status' => QuizAttempt::STATUS_IN_PROGRESS,
        ]);
        $id = $stmt->fetchColumn();

        return $id ? $this->findAttemptById((int)$id) : null;
    }

    /**
     * Get all attempts by a student for a quiz.
     *
     * @return array<int, QuizAttempt>
     */
    public function getStudentAttempts(int $quizId, int $studentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM `quiz_attempts` 
             WHERE `quiz_id` = :quiz_id AND `student_id` = :student_id 
             ORDER BY `attempt_number` ASC'
        );
        $stmt->execute([
            ':quiz_id' => $quizId,
            ':student_id' => $studentId,
        ]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $attempts = [];
        foreach ($ids as $id) {
            $attempt = $this->findAttemptById((int)$id);
            if ($attempt) {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
    }

    /**
     * Get all attempts for a quiz (for teacher grading overview).
     *
     * @return array<int, QuizAttempt>
     */
    public function getAttemptsByQuiz(int $quizId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT qa.*, u.id AS student_user_id, u.name AS student_name, u.email AS student_email, s.admission_number
             FROM `quiz_attempts` qa
             JOIN `students` s ON qa.student_id = s.id
             JOIN `users` u ON s.user_id = u.id
             WHERE qa.quiz_id = :quiz_id
             ORDER BY qa.submitted_at DESC, qa.id DESC'
        );
        $stmt->execute([':quiz_id' => $quizId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attempts = [];
        foreach ($rows as $row) {
            $student = Student::fromArray([
                'id' => $row['student_id'],
                'user_id' => $row['student_user_id'] ?? 0,
                'admission_number' => $row['admission_number'] ?? '',
            ], User::fromArray([
                'id' => $row['student_user_id'] ?? 0,
                'name' => $row['student_name'] ?? '',
                'email' => $row['student_email'] ?? '',
            ]));

            $attempts[] = QuizAttempt::fromArray($row, null, $student, []);
        }

        return $attempts;
    }

    public function getAttemptCount(int $quizId, int $studentId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `quiz_attempts` WHERE `quiz_id` = :quiz_id AND `student_id` = :student_id'
        );
        $stmt->execute([
            ':quiz_id' => $quizId,
            ':student_id' => $studentId,
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function updateAttemptStatus(
        int $id,
        string $status,
        ?float $score = null,
        ?string $submittedAt = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `quiz_attempts` SET `status` = :status, `updated_at` = :updated_at';
        $params = [
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => $now,
        ];

        if ($score !== null) {
            $sql .= ', `score` = :score';
            $params[':score'] = $score;
        }

        if ($submittedAt !== null) {
            $sql .= ', `submitted_at` = :submitted_at';
            $params[':submitted_at'] = $submittedAt;
        }

        $sql .= ' WHERE `id` = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteAttempt(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `quiz_attempts` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    // ==========================================
    // 4. Quiz Answers
    // ==========================================

    /**
     * Upsert a student's answer to a question in an attempt (Idempotent autosave).
     */
    public function upsertAnswer(
        int $attemptId,
        int $questionId,
        ?int $selectedOptionId = null,
        ?string $textAnswer = null,
        ?float $pointsAwarded = null,
        ?string $teacherComment = null
    ): QuizAnswer {
        $now = date('Y-m-d H:i:s');

        // Check if answer already exists
        $stmtCheck = $this->pdo->prepare(
            'SELECT id FROM `quiz_answers` WHERE `attempt_id` = :attempt_id AND `question_id` = :question_id'
        );
        $stmtCheck->execute([
            ':attempt_id' => $attemptId,
            ':question_id' => $questionId,
        ]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $sql = 'UPDATE `quiz_answers` SET 
                        `selected_option_id` = :selected_option_id,
                        `text_answer` = :text_answer,
                        `updated_at` = :updated_at';
            $params = [
                ':id' => (int)$existingId,
                ':selected_option_id' => $selectedOptionId,
                ':text_answer' => $textAnswer,
                ':updated_at' => $now,
            ];

            if ($pointsAwarded !== null) {
                $sql .= ', `points_awarded` = :points_awarded';
                $params[':points_awarded'] = $pointsAwarded;
            }

            if ($teacherComment !== null) {
                $sql .= ', `teacher_comment` = :teacher_comment';
                $params[':teacher_comment'] = $teacherComment;
            }

            $sql .= ' WHERE `id` = :id';
            $stmtUpdate = $this->pdo->prepare($sql);
            $stmtUpdate->execute($params);
            $answerId = (int)$existingId;
        } else {
            $stmtInsert = $this->pdo->prepare(
                'INSERT INTO `quiz_answers` (
                    `attempt_id`, `question_id`, `selected_option_id`, `text_answer`,
                    `points_awarded`, `teacher_comment`, `created_at`, `updated_at`
                ) VALUES (
                    :attempt_id, :question_id, :selected_option_id, :text_answer,
                    :points_awarded, :teacher_comment, :created_at, :updated_at
                )'
            );
            $stmtInsert->execute([
                ':attempt_id' => $attemptId,
                ':question_id' => $questionId,
                ':selected_option_id' => $selectedOptionId,
                ':text_answer' => $textAnswer,
                ':points_awarded' => $pointsAwarded,
                ':teacher_comment' => $teacherComment,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
            $answerId = (int)$this->pdo->lastInsertId();
        }

        return new QuizAnswer(
            id: $answerId,
            attemptId: $attemptId,
            questionId: $questionId,
            selectedOptionId: $selectedOptionId,
            textAnswer: $textAnswer,
            pointsAwarded: $pointsAwarded,
            teacherComment: $teacherComment,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * Get all answers for an attempt.
     *
     * @return array<int, QuizAnswer>
     */
    public function getAnswersByAttemptId(int $attemptId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT qa.*, 
                    q.question_text, q.type AS question_type, q.default_points,
                    qo.option_text AS selected_option_text, qo.is_correct AS selected_option_is_correct
             FROM `quiz_answers` qa
             JOIN `questions` q ON qa.question_id = q.id
             LEFT JOIN `question_options` qo ON qa.selected_option_id = qo.id
             WHERE qa.attempt_id = :attempt_id
             ORDER BY qa.id ASC'
        );
        $stmt->execute([':attempt_id' => $attemptId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $answers = [];
        foreach ($rows as $row) {
            $question = Question::fromArray([
                'id' => $row['question_id'],
                'question_text' => $row['question_text'],
                'type' => $row['question_type'],
                'default_points' => $row['default_points'],
            ]);

            $selectedOption = !empty($row['selected_option_id']) ? QuestionOption::fromArray([
                'id' => $row['selected_option_id'],
                'question_id' => $row['question_id'],
                'option_text' => $row['selected_option_text'] ?? '',
                'is_correct' => !empty($row['selected_option_is_correct']),
            ]) : null;

            $answers[] = QuizAnswer::fromArray($row, $question, $selectedOption);
        }

        return $answers;
    }

    public function gradeAnswer(int $answerId, float $pointsAwarded, ?string $teacherComment = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `quiz_answers` SET
                `points_awarded` = :points_awarded,
                `teacher_comment` = :teacher_comment,
                `updated_at` = :updated_at
            WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $answerId,
            ':points_awarded' => $pointsAwarded,
            ':teacher_comment' => $teacherComment !== '' ? $teacherComment : null,
            ':updated_at' => $now,
        ]);
    }
}
