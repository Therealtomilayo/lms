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

        // Test phone string containing digits is not evaluated as float > max
        $vPhone = Validator::make(['phone' => '08031234567'], ['phone' => 'max:30']);
        $this->assertTrue($vPhone->passes(), 'Phone number with 11 digits should pass max:30');

        // Test numeric rule actually evaluates numerically
        $vNumeric = Validator::make(['score' => '150'], ['score' => 'numeric|max:100']);
        $this->assertTrue($vNumeric->fails(), 'Score 150 should fail numeric|max:100');
        $this->assertStringContainsString('must not exceed 100.', $vNumeric->firstError('score'));
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
