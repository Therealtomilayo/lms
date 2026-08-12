<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
    }

    public function testLoadsDefaultConfigurations(): void
    {
        Config::load(__DIR__ . '/non-existent-file.env');

        $this->assertSame('Claret LMS', Config::get('app.name'));
        $this->assertSame('Africa/Lagos', Config::get('app.timezone'));
        $this->assertSame('mysql', Config::get('database.driver'));
        $this->assertSame(3306, Config::get('database.port'));
    }

    public function testGetNestedWithFallback(): void
    {
        Config::load(__DIR__ . '/non-existent-file.env');

        $this->assertSame('fallback_val', Config::get('non.existent.key', 'fallback_val'));
    }

    public function testSetAndGetRuntimeConfig(): void
    {
        Config::load(__DIR__ . '/non-existent-file.env');
        Config::set('custom.setting.key', 'custom_value');

        $this->assertSame('custom_value', Config::get('custom.setting.key'));
    }
}
