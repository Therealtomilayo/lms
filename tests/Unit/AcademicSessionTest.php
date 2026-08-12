<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicSession;
use PHPUnit\Framework\TestCase;

final class AcademicSessionTest extends TestCase
{
    public function testSessionLifecycleTransitions(): void
    {
        $sessionPlanning = new AcademicSession(
            id: 1,
            name: '2026/2027',
            startDate: '2026-09-01',
            endDate: '2027-07-20',
            status: AcademicSession::STATUS_PLANNING
        );

        $this->assertTrue($sessionPlanning->isPlanning());
        $this->assertFalse($sessionPlanning->isActive());
        $this->assertFalse($sessionPlanning->isArchived());

        $this->assertTrue($sessionPlanning->canTransitionTo(AcademicSession::STATUS_ACTIVE));
        $this->assertTrue($sessionPlanning->canTransitionTo(AcademicSession::STATUS_ARCHIVED));
        $this->assertFalse($sessionPlanning->canTransitionTo(AcademicSession::STATUS_PLANNING));

        $sessionActive = new AcademicSession(
            id: 1,
            name: '2026/2027',
            startDate: '2026-09-01',
            endDate: '2027-07-20',
            status: AcademicSession::STATUS_ACTIVE
        );

        $this->assertTrue($sessionActive->isActive());
        $this->assertTrue($sessionActive->canTransitionTo(AcademicSession::STATUS_ARCHIVED));
        $this->assertFalse($sessionActive->canTransitionTo(AcademicSession::STATUS_PLANNING));

        $sessionArchived = new AcademicSession(
            id: 1,
            name: '2026/2027',
            startDate: '2026-09-01',
            endDate: '2027-07-20',
            status: AcademicSession::STATUS_ARCHIVED
        );

        $this->assertTrue($sessionArchived->isArchived());
        $this->assertFalse($sessionArchived->canTransitionTo(AcademicSession::STATUS_ACTIVE));
        $this->assertFalse($sessionArchived->canTransitionTo(AcademicSession::STATUS_PLANNING));
    }
}
