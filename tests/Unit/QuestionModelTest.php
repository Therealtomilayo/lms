<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use PHPUnit\Framework\TestCase;

final class QuestionModelTest extends TestCase
{
    public function testQuestionInstantiationAndOptions(): void
    {
        $optA = new QuestionOption(id: 1, questionId: 10, optionText: 'Option A', isCorrect: false);
        $optB = new QuestionOption(id: 2, questionId: 10, optionText: 'Option B', isCorrect: true);

        $question = new Question(
            id: 10,
            subjectId: 5,
            topic: 'Algebra',
            questionText: 'Solve for x: 2x = 4',
            type: Question::TYPE_MCQ,
            defaultPoints: 2.50,
            createdBy: 1,
            options: [$optA, $optB]
        );

        $this->assertTrue($question->isMcq());
        $this->assertFalse($question->isShortAnswer());
        $this->assertSame(10, $question->id);
        $this->assertSame('Algebra', $question->topic);
        $this->assertSame(2.50, $question->defaultPoints);
        $this->assertSame($optB, $question->getCorrectOption());

        // Test array transformation with and without correctness
        $studentArray = $question->toArray(false);
        $this->assertArrayNotHasKey('is_correct', $studentArray['options'][0]);
        $this->assertArrayNotHasKey('is_correct', $studentArray['options'][1]);

        $teacherArray = $question->toArray(true);
        $this->assertFalse($teacherArray['options'][0]['is_correct']);
        $this->assertTrue($teacherArray['options'][1]['is_correct']);
    }

    public function testQuizAndQuizQuestionTotalPoints(): void
    {
        $qq1 = new QuizQuestion(id: 1, quizId: 100, questionId: 10, points: 2.50, sortOrder: 1);
        $qq2 = new QuizQuestion(id: 2, quizId: 100, questionId: 11, points: 5.00, sortOrder: 2);

        $quiz = new Quiz(
            id: 100,
            classSubjectId: 50,
            termId: 2,
            assessmentCategoryId: null,
            teacherId: 10,
            title: 'Math Quiz',
            instructions: 'Answer all questions',
            timeLimitMinutes: 45,
            maxAttempts: 2,
            isPublished: true,
            quizQuestions: [$qq1, $qq2]
        );

        $this->assertTrue($quiz->isPublished());
        $this->assertTrue($quiz->hasTimeLimit());
        $this->assertSame(45, $quiz->timeLimitMinutes);
        $this->assertSame(2, $quiz->maxAttempts);
        $this->assertSame(7.50, $quiz->getTotalMaxScore());
    }

    public function testQuizAttemptServerAuthoritativeTimer(): void
    {
        $startedAt = date('Y-m-d H:i:s', time() - (20 * 60)); // Started 20 mins ago

        $attempt = new QuizAttempt(
            id: 1,
            uuid: 'test-uuid',
            quizId: 100,
            studentId: 5,
            attemptNumber: 1,
            startedAt: $startedAt,
            maxScore: 10.00,
            status: QuizAttempt::STATUS_IN_PROGRESS
        );

        $this->assertTrue($attempt->isInProgress());
        $this->assertFalse($attempt->isSubmitted());

        // For a 30-minute quiz: 20 minutes passed, 10 minutes (600s) left -> not expired
        $this->assertFalse($attempt->hasExpired(30));
        $remaining = $attempt->getRemainingSeconds(30);
        $this->assertGreaterThan(580, $remaining);
        $this->assertLessThanOrEqual(600, $remaining);

        // For a 15-minute quiz: 20 minutes passed -> expired
        $this->assertTrue($attempt->hasExpired(15));
        $this->assertSame(0, $attempt->getRemainingSeconds(15));

        // For an untimed quiz: (time_limit = 0) -> never expired
        $this->assertFalse($attempt->hasExpired(0));
        $this->assertSame(PHP_INT_MAX, $attempt->getRemainingSeconds(0));
    }
}
