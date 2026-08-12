<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Migrator;

echo "=== LMS Database Migration Runner ===\n";

try {
    Migrator::createDatabaseIfNotExists();
    $migrator = new Migrator();
    $applied = $migrator->run();

    if (empty($applied)) {
        echo "Nothing to migrate. Database is up to date.\n";
    } else {
        echo "Successfully executed " . count($applied) . " migration(s):\n";
        foreach ($applied as $name) {
            echo "  - {$name}\n";
        }
    }
} catch (Throwable $e) {
    echo "Migration failed with error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
