<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Models\AcademicSession;
use App\Models\Term;
use App\Repositories\AcademicRepository;
use App\Services\AcademicSessionService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AcademicSessionServiceTest extends TestCase
{
    private PDO $pdo;
    private AcademicRepository $repository;
    private AcademicSessionService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `grading_starts_at` DATETIME NULL,
                `grading_ends_at` DATETIME NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`session_id`, `name`)
            );
        ");

        $this->repository = new AcademicRepository($this->pdo);
        $this->service = new AcademicSessionService($this->repository);
    }

    public function testCreateSessionSuccess(): void
    {
        $res = $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-20',
        ]);

        $this->assertTrue($res->isSuccess());
        /** @var AcademicSession $session */
        $session = $res->getData();
        $this->assertSame('2026/2027', $session->name);
        $this->assertTrue($session->isPlanning());
    }

    public function testCreateSessionRejectsInvalidDateSpan(): void
    {
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('Session start date must be before the end date.');

        $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2027-09-01',
            'end_date' => '2026-07-20',
        ]);
    }

    public function testCreateSessionRejectsDuplicateName(): void
    {
        $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-20',
        ]);

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage("An academic session with name '2026/2027' already exists.");

        $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-20',
        ]);
    }

    public function testSingleActiveSessionInvariant(): void
    {
        $s1 = $this->service->createSession([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-20',
        ])->getData();

        $s2 = $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-20',
        ])->getData();

        $this->service->makeSessionActive($s1->id);
        $this->assertTrue($this->repository->findSessionById($s1->id)->isActive());

        // Activating s2 must deactivate s1
        $this->service->makeSessionActive($s2->id);
        $this->assertTrue($this->repository->findSessionById($s2->id)->isActive());
        $this->assertTrue($this->repository->findSessionById($s1->id)->isArchived());
    }

    public function testTermCreationAndCrossSessionIntegrity(): void
    {
        $session = $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-20',
        ])->getData();

        $termRes = $this->service->createTerm([
            'session_id' => $session->id,
            'name' => 'First Term',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-15',
        ]);

        $this->assertTrue($termRes->isSuccess());
        /** @var Term $term */
        $term = $termRes->getData();
        $this->assertSame('First Term', $term->name);
        $this->assertTrue($this->service->validateTermBelongsToSession($term->id, $session->id));
        $this->assertFalse($this->service->validateTermBelongsToSession($term->id, 99999));
    }

    public function testTermCreationRejectsOutOfRangeDates(): void
    {
        $session = $this->service->createSession([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-20',
        ])->getData();

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('Term dates');

        $this->service->createTerm([
            'session_id' => $session->id,
            'name' => 'Invalid Date Term',
            'start_date' => '2025-01-01', // before session starts
            'end_date' => '2026-12-15',
        ]);
    }
}
