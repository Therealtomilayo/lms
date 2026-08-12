<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Models\SchoolClass;
use App\Repositories\AcademicRepository;
use App\Services\AcademicStructureService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AcademicStructureServiceTest extends TestCase
{
    private PDO $pdo;
    private AcademicRepository $repository;
    private AcademicStructureService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `grading_scales` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `is_default` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `stage` VARCHAR(50) NOT NULL,
                `rank_order` INTEGER NOT NULL DEFAULT 0,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`academic_level_id`, `name`, `section_arm`)
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );
        ");

        $this->repository = new AcademicRepository($this->pdo);
        $this->service = new AcademicStructureService($this->repository);
    }

    public function testCreateLevelAndDuplicateRejection(): void
    {
        $res = $this->service->createLevel([
            'name' => 'JSS 1',
            'stage' => 'Junior Secondary',
            'rank_order' => 7,
        ]);

        $this->assertTrue($res->isSuccess());

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage("Academic level 'JSS 1' already exists.");

        $this->service->createLevel([
            'name' => 'JSS 1',
            'stage' => 'Junior Secondary',
            'rank_order' => 7,
        ]);
    }

    public function testCreateClassAndArm(): void
    {
        $level = $this->service->createLevel([
            'name' => 'JSS 2',
            'stage' => 'Junior Secondary',
            'rank_order' => 8,
        ])->getData();

        $classRes = $this->service->createClass([
            'academic_level_id' => $level->id,
            'name' => 'JSS 2 Gold',
            'section_arm' => 'Gold',
        ]);

        $this->assertTrue($classRes->isSuccess());
        /** @var SchoolClass $class */
        $class = $classRes->getData();
        $this->assertSame('JSS 2 Gold', $class->name);
        $this->assertSame('Gold', $class->sectionArm);
    }

    public function testCreateSubjectAndUniqueCodeEnforcement(): void
    {
        $res = $this->service->createSubject([
            'name' => 'Basic Science',
            'code' => 'bsc101',
        ]);

        $this->assertTrue($res->isSuccess());
        $subject = $res->getData();
        $this->assertSame('BSC101', $subject->code);

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage("A subject with code 'BSC101' already exists.");

        $this->service->createSubject([
            'name' => 'Another Basic Science',
            'code' => 'BSC101',
        ]);
    }
}
