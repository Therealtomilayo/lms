<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\GradingScaleRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class StageBasedGradingScaleRepositoryTest extends TestCase
{
    private PDO $pdo;
    private GradingScaleRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE `grading_scales` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `stage` VARCHAR(50) NULL,
                `description` TEXT NULL,
                `is_default` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `grade_boundaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `grading_scale_id` INTEGER NOT NULL,
                `letter` VARCHAR(5) NOT NULL,
                `min_score` DECIMAL(5,2) NOT NULL,
                `max_score` DECIMAL(5,2) NOT NULL,
                `grade_point` DECIMAL(3,2) NULL,
                `remark` VARCHAR(100) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `stage` VARCHAR(50) NOT NULL,
                `rank_order` INTEGER NOT NULL DEFAULT 0,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->repo = new GradingScaleRepository($this->pdo);
    }

    public function testCreateAndUpdateScaleWithStage(): void
    {
        $scaleId = $this->repo->createScale([
            'name' => 'Junior Secondary Scale',
            'stage' => 'junior_secondary',
            'description' => 'For JSS 1-3',
            'is_default' => 0,
        ]);

        $scale = $this->repo->findById($scaleId);
        $this->assertNotNull($scale);
        $this->assertSame('Junior Secondary Scale', $scale->name);
        $this->assertSame('junior_secondary', $scale->stage);

        // Update stage and description
        $this->repo->updateScale($scaleId, [
            'name' => 'Updated JSS Scale',
            'stage' => 'junior_secondary',
            'description' => 'Updated description',
            'is_default' => 1,
        ]);

        $updated = $this->repo->findById($scaleId);
        $this->assertNotNull($updated);
        $this->assertSame('Updated JSS Scale', $updated->name);
        $this->assertTrue($updated->isDefault);
    }

    public function testFindByStage(): void
    {
        $jssId = $this->repo->createScale([
            'name' => 'JSS Scale',
            'stage' => 'junior_secondary',
            'is_default' => 0,
        ]);

        $sssId = $this->repo->createScale([
            'name' => 'SSS Scale',
            'stage' => 'senior_secondary',
            'is_default' => 0,
        ]);

        $foundJss = $this->repo->findByStage('junior_secondary');
        $this->assertNotNull($foundJss);
        $this->assertSame($jssId, $foundJss->id);

        $foundSss = $this->repo->findByStage('senior_secondary');
        $this->assertNotNull($foundSss);
        $this->assertSame($sssId, $foundSss->id);

        $this->assertNull($this->repo->findByStage('non_existent_stage'));
    }

    public function testResolutionHierarchyForLevel(): void
    {
        // Default scale
        $defaultId = $this->repo->createScale([
            'name' => 'Universal Default Scale',
            'stage' => null,
            'is_default' => 1,
        ]);

        // JSS stage scale
        $jssScaleId = $this->repo->createScale([
            'name' => 'Stage Scale for JSS',
            'stage' => 'junior_secondary',
            'is_default' => 0,
        ]);

        // Custom override scale
        $overrideScaleId = $this->repo->createScale([
            'name' => 'Special Override Scale',
            'stage' => null,
            'is_default' => 0,
        ]);

        // 1. Level with explicit scale override
        $this->pdo->exec("INSERT INTO academic_levels (id, name, stage, rank_order, grading_scale_id) VALUES (1, 'JSS 1 Special', 'junior_secondary', 1, {$overrideScaleId})");
        $resolvedOverride = $this->repo->getScaleForLevel(1);
        $this->assertNotNull($resolvedOverride);
        $this->assertSame($overrideScaleId, $resolvedOverride->id);

        // 2. Level without explicit scale (grading_scale_id NULL) -> falls back to stage scale
        $this->pdo->exec("INSERT INTO academic_levels (id, name, stage, rank_order, grading_scale_id) VALUES (2, 'JSS 2 Standard', 'junior_secondary', 2, NULL)");
        $resolvedStage = $this->repo->getScaleForLevel(2);
        $this->assertNotNull($resolvedStage);
        $this->assertSame($jssScaleId, $resolvedStage->id);

        // 3. Level with stage that has no specific scale -> falls back to system default scale
        $this->pdo->exec("INSERT INTO academic_levels (id, name, stage, rank_order, grading_scale_id) VALUES (3, 'Primary 1', 'primary', 3, NULL)");
        $resolvedDefault = $this->repo->getScaleForLevel(3);
        $this->assertNotNull($resolvedDefault);
        $this->assertSame($defaultId, $resolvedDefault->id);
    }
}
