<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\ServiceResult;
use PHPUnit\Framework\TestCase;

final class ServiceResultTest extends TestCase
{
    public function testSuccessResult(): void
    {
        $result = ServiceResult::success(['id' => 123, 'status' => 'created']);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertSame(['id' => 123, 'status' => 'created'], $result->data);
        $this->assertEmpty($result->errors);
        $this->assertNull($result->errorCode);
    }

    public function testFailureWithStringError(): void
    {
        $result = ServiceResult::failure('Unauthorized access', 'UNAUTHORIZED');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertNull($result->data);
        $this->assertSame('UNAUTHORIZED', $result->errorCode);
        $this->assertSame('Unauthorized access', $result->getFirstError());
    }

    public function testFailureWithStructuredErrors(): void
    {
        $errors = [
            'email' => ['Invalid email format', 'Email already in use'],
            'password' => ['Password too short'],
        ];
        $result = ServiceResult::failure($errors, 'VALIDATION_FAILED');

        $this->assertTrue($result->isFailure());
        $this->assertSame('VALIDATION_FAILED', $result->errorCode);
        $this->assertSame('Invalid email format', $result->getFirstError());
        $this->assertSame($errors, $result->errors);
    }

    public function testToArrayRepresentation(): void
    {
        $result = ServiceResult::success('ok');
        $array = $result->toArray();

        $this->assertSame([
            'success' => true,
            'data' => 'ok',
            'errors' => [],
            'error_code' => null,
        ], $array);
    }
}
