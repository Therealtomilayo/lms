<?php

declare(strict_types=1);

use App\Core\Config;

$envFile = __DIR__ . '/.env';
Config::load($envFile);

date_default_timezone_set((string)Config::get('app.timezone', 'Africa/Lagos'));

require_once dirname(__DIR__) . '/app/Core/helpers.php';
