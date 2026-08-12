<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Database\Seeders\DatabaseSeeder;

echo "=======================================================\n";
echo "       Claret LMS Database Seeder Runner\n";
echo "=======================================================\n\n";

try {
    echo "Seeding comprehensive demonstration dataset...\n";
    $seeder = new DatabaseSeeder();
    $summary = $seeder->run();

    echo "SUCCESS: Database seeded successfully!\n\n";

    echo "-------------------------------------------------------\n";
    echo "  Default Demo Credentials (Password: Password123!)\n";
    echo "-------------------------------------------------------\n";
    echo sprintf("%-15s | %-30s | %-12s\n", "Role", "Email", "Password");
    echo "-------------------------------------------------------\n";
    echo sprintf("%-15s | %-30s | %-12s\n", "Super Admin", "superadmin@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Admin", "admin@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Teacher (Math)", "teacher.adebayo@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Teacher (Eng)", "teacher.okoro@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Student (JSS1A)", "student.john@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Student (JSS1A)", "student.mary@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Student (JSS1B)", "student.david@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Parent (2 Kids)", "parent.doe@claret.edu", "Password123!");
    echo sprintf("%-15s | %-30s | %-12s\n", "Parent (1 Kid)", "parent.smith@claret.edu", "Password123!");
    echo "-------------------------------------------------------\n\n";

} catch (Throwable $e) {
    echo "ERROR: Seeder failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
