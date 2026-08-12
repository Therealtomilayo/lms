<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Term;
use PHPUnit\Framework\TestCase;

final class TermTest extends TestCase
{
    public function testTermLifecycleTransitions(): void
    {
        $termPlanning = new Term(
            id: 1,
            sessionId: 1,
            name: 'First Term',
            startDate: '2026-09-01',
            endDate: '2026-12-15',
            status: Term::STATUS_PLANNING
        );

        $this->assertTrue($termPlanning->isPlanning());
        $this->assertTrue($termPlanning->canTransitionTo(Term::STATUS_ACTIVE));
        $this->assertTrue($termPlanning->canTransitionTo(Term::STATUS_ARCHIVED));
        $this->assertFalse($termPlanning->canTransitionTo(Term::STATUS_GRADING_OPEN));

        $termActive = new Term(
            id: 1,
            sessionId: 1,
            name: 'First Term',
            startDate: '2026-09-01',
            endDate: '2026-12-15',
            status: Term::STATUS_ACTIVE
        );

        $this->assertTrue($termActive->isActive());
        $this->assertTrue($termActive->canTransitionTo(Term::STATUS_GRADING_OPEN));
        $this->assertTrue($termActive->canTransitionTo(Term::STATUS_ARCHIVED));

        $termGradingOpen = new Term(
            id: 1,
            sessionId: 1,
            name: 'First Term',
            startDate: '2026-09-01',
            endDate: '2026-12-15',
            status: Term::STATUS_GRADING_OPEN
        );

        $this->assertTrue($termGradingOpen->isGradingOpen());
        $this->assertTrue($termGradingOpen->canTransitionTo(Term::STATUS_GRADING_LOCKED));
        $this->assertTrue($termGradingOpen->canTransitionTo(Term::STATUS_ACTIVE));

        $termGradingLocked = new Term(
            id: 1,
            sessionId: 1,
            name: 'First Term',
            startDate: '2026-09-01',
            endDate: '2026-12-15',
            status: Term::STATUS_GRADING_LOCKED
        );

        $this->assertTrue($termGradingLocked->isGradingLocked());
        $this->assertTrue($termGradingLocked->canTransitionTo(Term::STATUS_GRADING_OPEN));
        $this->assertTrue($termGradingLocked->canTransitionTo(Term::STATUS_ARCHIVED));

        $termArchived = new Term(
            id: 1,
            sessionId: 1,
            name: 'First Term',
            startDate: '2026-09-01',
            endDate: '2026-12-15',
            status: Term::STATUS_ARCHIVED
        );

        $this->assertTrue($termArchived->isArchived());
        $this->assertFalse($termArchived->canTransitionTo(Term::STATUS_ACTIVE));
        $this->assertFalse($termArchived->canTransitionTo(Term::STATUS_GRADING_OPEN));
    }
}
