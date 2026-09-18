<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ActivityProgress;
use App\Repositories\ActivityProgressRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class ActivityProgressTest extends TestCase
{
    private PDO $pdo;
    private ActivityProgressRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `learning_activity_progress` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `last_page` INTEGER NULL DEFAULT NULL,
                `total_pages` INTEGER NULL DEFAULT NULL,
                `pages_read_json` TEXT NULL DEFAULT NULL,
                `progress_percent` REAL NOT NULL DEFAULT 0.00,
                `is_completed` INTEGER NOT NULL DEFAULT 0,
                `completed_at` TEXT NULL DEFAULT NULL,
                `last_accessed_at` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL,
                UNIQUE (`student_id`, `activity_type`, `activity_id`)
            );
        ");

        $this->repository = new ActivityProgressRepository($this->pdo);
    }

    public function testInitialStateHasNoProgress(): void
    {
        $progress = $this->repository->findDocumentProgress(1, 10);
        $this->assertNull($progress);
    }

    public function testFirstPageCreatesProgressAndDeduplicates(): void
    {
        // Student views page 1 of a 10-page document
        $progress = $this->repository->recordDocumentReadingProgress(
            studentId: 1,
            contentItemId: 10,
            lastPage: 1,
            totalPages: 10,
            newPagesNewlyViewed: [1, 1, 1] // duplicate submissions
        );

        $this->assertSame(1, $progress->studentId);
        $this->assertSame(10, $progress->activityId);
        $this->assertSame(1, $progress->lastPage);
        $this->assertSame(10, $progress->totalPages);
        $this->assertSame([1], $progress->pagesRead);
        $this->assertSame(1, $progress->getUniquePagesCount());
        $this->assertSame(10.0, $progress->progressPercent);
        $this->assertFalse($progress->isCompleted());
        $this->assertNull($progress->completedAt);
    }

    public function testMultipleUniquePagesIncreasesProgress(): void
    {
        // First view: pages 1, 2
        $this->repository->recordDocumentReadingProgress(1, 10, 2, 10, [1, 2]);

        // Second view: page 3, 4
        $progress = $this->repository->recordDocumentReadingProgress(1, 10, 4, 10, [3, 4]);

        $this->assertSame([1, 2, 3, 4], $progress->pagesRead);
        $this->assertSame(4, $progress->getUniquePagesCount());
        $this->assertSame(40.0, $progress->progressPercent);
        $this->assertFalse($progress->isCompleted());
    }

    public function testProgressDoesNotRegressWhenStaleClientSendsFewerPages(): void
    {
        // Server already has pages 1, 2, 3, 4, 5
        $this->repository->recordDocumentReadingProgress(1, 10, 5, 10, [1, 2, 3, 4, 5]);

        // Stale client tab sends only page 2
        $progress = $this->repository->recordDocumentReadingProgress(1, 10, 2, 10, [2]);

        // Merged set must retain 1, 2, 3, 4, 5
        $this->assertSame([1, 2, 3, 4, 5], $progress->pagesRead);
        $this->assertSame(5, $progress->getUniquePagesCount());
        $this->assertSame(50.0, $progress->progressPercent);
        $this->assertSame(2, $progress->lastPage); // lastPage updates to current position
    }

    public function testCompletionRuleThresholdAtNinetyPercent(): void
    {
        // 100-page document: 89 pages = 89% -> Not complete
        $pages89 = range(1, 89);
        $progress89 = $this->repository->recordDocumentReadingProgress(1, 10, 89, 100, $pages89);

        $this->assertSame(89.0, $progress89->progressPercent);
        $this->assertFalse($progress89->isCompleted());
        $this->assertNull($progress89->completedAt);

        // Add 1 more unique page (page 90) -> 90 / 100 = 90% -> Completed!
        $progress90 = $this->repository->recordDocumentReadingProgress(1, 10, 90, 100, [90]);

        $this->assertSame(90.0, $progress90->progressPercent);
        $this->assertTrue($progress90->isCompleted());
        $this->assertNotNull($progress90->completedAt);
    }

    public function testCompletionTimestampIsImmutableOnSubsequentSaves(): void
    {
        // Complete document (pages 1 to 9 of 10)
        $progress1 = $this->repository->recordDocumentReadingProgress(1, 10, 9, 10, range(1, 9));
        $this->assertTrue($progress1->isCompleted());
        $firstCompletedAt = $progress1->completedAt;
        $this->assertNotNull($firstCompletedAt);

        // Subsequent save on page 10
        $progress2 = $this->repository->recordDocumentReadingProgress(1, 10, 10, 10, [10]);
        $this->assertTrue($progress2->isCompleted());
        $this->assertSame($firstCompletedAt, $progress2->completedAt);

        // Stale save with fewer pages must NOT revert completion
        $progress3 = $this->repository->recordDocumentReadingProgress(1, 10, 1, 10, [1]);
        $this->assertTrue($progress3->isCompleted());
        $this->assertSame($firstCompletedAt, $progress3->completedAt);
    }

    public function testInvalidPageNumbersAreSanitizedAndClamped(): void
    {
        // 5-page document: pass negative page, page 0, page 999
        $progress = $this->repository->recordDocumentReadingProgress(
            studentId: 1,
            contentItemId: 10,
            lastPage: 999, // Should clamp to 5
            totalPages: 5,
            newPagesNewlyViewed: [-1, 0, 2, 3, 999] // Only 2 and 3 are valid
        );

        $this->assertSame(5, $progress->lastPage);
        $this->assertSame([2, 3, 5], $progress->pagesRead); // 2, 3 and clamped lastPage (5)
        $this->assertSame(3, $progress->getUniquePagesCount());
        $this->assertSame(60.0, $progress->progressPercent);
    }
}
