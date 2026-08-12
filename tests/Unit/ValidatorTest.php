<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\ValidationException;
use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredRulePasses(): void
    {
        $v = Validator::make(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($v->passes());
        $this->assertFalse($v->fails());
        $this->assertSame(['name' => 'John'], $v->validated());
    }

    public function testRequiredRuleFailsOnEmpty(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertFalse($v->passes());
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors());
    }

    public function testEmailRule(): void
    {
        $v1 = Validator::make(['email' => 'admin@claret.edu'], ['email' => 'required|email']);
        $this->assertTrue($v1->passes());

        $v2 = Validator::make(['email' => 'not-an-email'], ['email' => 'required|email']);
        $this->assertTrue($v2->fails());
    }

    public function testMinMaxRules(): void
    {
        $v1 = Validator::make(['pwd' => 'secret123'], ['pwd' => 'min:8|max:20']);
        $this->assertTrue($v1->passes());

        $v2 = Validator::make(['pwd' => 'short'], ['pwd' => 'min:8']);
        $this->assertTrue($v2->fails());
        $this->assertStringContainsString('at least 8 characters', $v2->firstError('pwd'));
    }

    public function testConfirmedRule(): void
    {
        $v1 = Validator::make([
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], ['password' => 'required|confirmed']);
        $this->assertTrue($v1->passes());

        $v2 = Validator::make([
            'password' => 'secret123',
            'password_confirmation' => 'mismatch',
        ], ['password' => 'required|confirmed']);
        $this->assertTrue($v2->fails());
    }

    public function testValidateDataThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        Validator::validateData(['email' => 'invalid'], ['email' => 'required|email']);
    }
}
