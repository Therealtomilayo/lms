<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        Session::destroy();
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    public function testSetGetAndHas(): void
    {
        Session::set('user_id', 42);

        $this->assertTrue(Session::has('user_id'));
        $this->assertSame(42, Session::get('user_id'));
        $this->assertNull(Session::get('missing_key'));
    }

    public function testFlashMessages(): void
    {
        Session::setFlash('status', 'Profile updated');

        $this->assertTrue(Session::hasFlash('status'));
        $this->assertSame('Profile updated', Session::getFlash('status'));
    }

    public function testRemove(): void
    {
        Session::set('temp', 'value');
        $this->assertTrue(Session::has('temp'));

        Session::remove('temp');
        $this->assertFalse(Session::has('temp'));
    }
}
