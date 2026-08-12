<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Question Bank and Question Options
 */
class QuestionBankRepository
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

    /**
     * Create a new question with optional MCQ options.
     *
     * @param array<int, array{option_text: string, is_correct: bool}> $options
     */
    public function createQuestion(
        int $subjectId,
        string $questionText,
        string $type,
        float $defaultPoints,
        int $createdBy,
        ?string $topic = null,
        array $options = []
    ): Question {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `questions` (
                `subject_id`, `topic`, `question_text`, `type`, `default_points`, `created_by`, `created_at`, `updated_at`
            ) VALUES (
                :subject_id, :topic, :question_text, :type, :default_points, :created_by, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            ':subject_id' => $subjectId,
            ':topic' => $topic !== '' ? $topic : null,
            ':question_text' => $questionText,
            ':type' => $type,
            ':default_points' => $defaultPoints,
            ':created_by' => $createdBy,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $questionId = (int)$this->pdo->lastInsertId();

        if ($type === Question::TYPE_MCQ && !empty($options)) {
            $this->saveOptions($questionId, $options);
        }

        return $this->findById($questionId) ?? new Question(
            id: $questionId,
            subjectId: $subjectId,
            topic: $topic,
            questionText: $questionText,
            type: $type,
            defaultPoints: $defaultPoints,
            createdBy: $createdBy,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * Update an existing question and its options.
     *
     * @param array<int, array{option_text: string, is_correct: bool}>|null $options
     */
    public function updateQuestion(
        int $id,
        string $questionText,
        string $type,
        float $defaultPoints,
        ?string $topic = null,
        ?array $options = null
    ): ?Question {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `questions` SET 
                `question_text` = :question_text,
                `type` = :type,
                `default_points` = :default_points,
                `topic` = :topic,
                `updated_at` = :updated_at
            WHERE `id` = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':question_text' => $questionText,
            ':type' => $type,
            ':default_points' => $defaultPoints,
            ':topic' => $topic !== '' ? $topic : null,
            ':updated_at' => $now,
        ]);

        if ($options !== null) {
            $this->saveOptions($id, $options);
        }

        return $this->findById($id);
    }

    /**
     * Delete a question (will cascade delete options, but restrict if used in quiz).
     */
    public function deleteQuestion(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `questions` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find a question by ID including attached options.
     */
    public function findById(int $id): ?Question
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.*, s.name AS subject_name, s.code AS subject_code, u.name AS author_name, u.email AS author_email
             FROM `questions` q
             LEFT JOIN `subjects` s ON q.subject_id = s.id
             LEFT JOIN `users` u ON q.created_by = u.id
             WHERE q.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $options = $this->getOptionsByQuestionId($id);

        $subject = null;
        if (!empty($row['subject_id'])) {
            $subject = Subject::fromArray([
                'id' => $row['subject_id'],
                'name' => $row['subject_name'] ?? '',
                'code' => $row['subject_code'] ?? '',
            ]);
        }

        $author = null;
        if (!empty($row['created_by'])) {
            $author = User::fromArray([
                'id' => $row['created_by'],
                'name' => $row['author_name'] ?? '',
                'email' => $row['author_email'] ?? '',
            ]);
        }

        return Question::fromArray($row, $subject, $author, $options);
    }

    /**
     * List questions for a subject with optional filters.
     *
     * @return array<int, Question>
     */
    public function findBySubject(
        int $subjectId,
        ?string $topic = null,
        ?string $type = null,
        ?string $search = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $sql = 'SELECT q.*, s.name AS subject_name, s.code AS subject_code, u.name AS author_name, u.email AS author_email
                FROM `questions` q
                LEFT JOIN `subjects` s ON q.subject_id = s.id
                LEFT JOIN `users` u ON q.created_by = u.id
                WHERE q.subject_id = :subject_id';
        $params = [':subject_id' => $subjectId];

        if ($topic !== null && $topic !== '') {
            $sql .= ' AND q.topic = :topic';
            $params[':topic'] = $topic;
        }

        if ($type !== null && $type !== '') {
            $sql .= ' AND q.type = :type';
            $params[':type'] = $type;
        }

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (q.question_text LIKE :search OR q.topic LIKE :search)';
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY q.id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $questionIds = array_map(fn($r) => (int)$r['id'], $rows);
        $optionsGrouped = $this->getOptionsByQuestionIds($questionIds);

        $questions = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $subject = Subject::fromArray([
                'id' => $row['subject_id'],
                'name' => $row['subject_name'] ?? '',
                'code' => $row['subject_code'] ?? '',
            ]);
            $author = User::fromArray([
                'id' => $row['created_by'],
                'name' => $row['author_name'] ?? '',
                'email' => $row['author_email'] ?? '',
            ]);

            $options = $optionsGrouped[$id] ?? [];
            $questions[] = Question::fromArray($row, $subject, $author, $options);
        }

        return $questions;
    }

    /**
     * Get distinct topics in the question bank for a subject.
     *
     * @return array<int, string>
     */
    public function getTopicsBySubject(int $subjectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT `topic` FROM `questions` 
             WHERE `subject_id` = :subject_id AND `topic` IS NOT NULL AND `topic` != "" 
             ORDER BY `topic` ASC'
        );
        $stmt->execute([':subject_id' => $subjectId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Save/overwrite options for a question.
     *
     * @param array<int, array{option_text: string, is_correct: bool}> $options
     */
    public function saveOptions(int $questionId, array $options): void
    {
        // Delete existing options
        $deleteStmt = $this->pdo->prepare('DELETE FROM `question_options` WHERE `question_id` = :question_id');
        $deleteStmt->execute([':question_id' => $questionId]);

        if (empty($options)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $insertStmt = $this->pdo->prepare(
            'INSERT INTO `question_options` (`question_id`, `option_text`, `is_correct`, `created_at`, `updated_at`)
             VALUES (:question_id, :option_text, :is_correct, :created_at, :updated_at)'
        );

        foreach ($options as $opt) {
            $insertStmt->execute([
                ':question_id' => $questionId,
                ':option_text' => (string)$opt['option_text'],
                ':is_correct' => !empty($opt['is_correct']) ? 1 : 0,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }
    }

    /**
     * Get options for a single question.
     *
     * @return array<int, QuestionOption>
     */
    public function getOptionsByQuestionId(int $questionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `question_options` WHERE `question_id` = :question_id ORDER BY `id` ASC'
        );
        $stmt->execute([':question_id' => $questionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($r) => QuestionOption::fromArray($r), $rows);
    }

    /**
     * Batch fetch options for multiple questions.
     *
     * @param array<int, int> $questionIds
     * @return array<int, array<int, QuestionOption>>
     */
    public function getOptionsByQuestionIds(array $questionIds): array
    {
        if (empty($questionIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM `question_options` WHERE `question_id` IN ($placeholders) ORDER BY `id` ASC"
        );
        $stmt->execute(array_values($questionIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $qId = (int)$row['question_id'];
            $grouped[$qId][] = QuestionOption::fromArray($row);
        }

        return $grouped;
    }
}
