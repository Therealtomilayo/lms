<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        Session::destroy();
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    public function testGeneratesValidCsrfToken(): void
    {
        $token = Csrf::generateToken();

        $this->assertNotEmpty($token);
        $this->assertTrue(Csrf::validate($token));
        $this->assertFalse(Csrf::validate('invalid-token-string'));
    }

    public function testGeneratesHiddenFormField(): void
    {
        $field = Csrf::field();

        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="_csrf_token"', $field);
    }
}
