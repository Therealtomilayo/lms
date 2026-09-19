<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AdmissionSession;
use PHPUnit\Framework\TestCase;

final class AdmissionSessionTest extends TestCase
{
    public function testIsOpenWhenActiveAndWithinWindow(): void
    {
        $session = new AdmissionSession(
            id: 1,
            academicSessionId: 1,
            title: '2026/2027 Admissions',
            applicationFee: 10000.00,
            currency: 'NGN',
            opensAt: date('Y-m-d H:i:s', time() - 3600),
            closesAt: date('Y-m-d H:i:s', time() + 3600),
            isActive: true
        );

        $this->assertTrue($session->isOpen());
        $this->assertFalse($session->isUpcoming());
        $this->assertFalse($session->isPast());
        $this->assertSame('₦10,000.00', $session->getFormattedFee());
    }

    public function testIsClosedWhenInactive(): void
    {
        $session = new AdmissionSession(
            id: 1,
            academicSessionId: 1,
            title: '2026/2027 Admissions',
            applicationFee: 10000.00,
            currency: 'NGN',
            opensAt: date('Y-m-d H:i:s', time() - 3600),
            closesAt: date('Y-m-d H:i:s', time() + 3600),
            isActive: false
        );

        $this->assertFalse($session->isOpen());
    }

    public function testIsUpcomingBeforeOpeningDate(): void
    {
        $session = new AdmissionSession(
            id: 1,
            academicSessionId: 1,
            title: 'Future Admissions',
            applicationFee: 15000.00,
            currency: 'NGN',
            opensAt: date('Y-m-d H:i:s', time() + 7200),
            closesAt: date('Y-m-d H:i:s', time() + 86400),
            isActive: true
        );

        $this->assertFalse($session->isOpen());
        $this->assertTrue($session->isUpcoming());
        $this->assertFalse($session->isPast());
    }

    public function testIsPastAfterClosingDate(): void
    {
        $session = new AdmissionSession(
            id: 1,
            academicSessionId: 1,
            title: 'Expired Admissions',
            applicationFee: 15000.00,
            currency: 'NGN',
            opensAt: date('Y-m-d H:i:s', time() - 86400),
            closesAt: date('Y-m-d H:i:s', time() - 3600),
            isActive: true
        );

        $this->assertFalse($session->isOpen());
        $this->assertFalse($session->isUpcoming());
        $this->assertTrue($session->isPast());
    }
}
