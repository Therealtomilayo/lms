<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';

use App\Core\Database;

$pdo = Database::getConnection();
echo "Executing Migration 0041: Consolidate Levels, Classes, Terms, and Assessment Categories..." . PHP_EOL;

$pdo->beginTransaction();

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    }

    // 1. STANDARDIZE ACADEMIC LEVELS (Edit already existing ones in place)
    // Update existing core levels
    $pdo->exec("UPDATE academic_levels SET name = 'JSS 1', stage = 'junior_secondary', rank_order = 11 WHERE id = 4");
    $pdo->exec("UPDATE academic_levels SET name = 'JSS 2', stage = 'junior_secondary', rank_order = 12 WHERE id = 5");
    $pdo->exec("UPDATE academic_levels SET name = 'SS 1', stage = 'senior_secondary', rank_order = 14 WHERE id = 6");
    $pdo->exec("UPDATE academic_levels SET name = 'JSS 3', stage = 'junior_secondary', rank_order = 13 WHERE id = 7");
    $pdo->exec("UPDATE academic_levels SET name = 'SS 2', stage = 'senior_secondary', rank_order = 15 WHERE id = 8");
    $pdo->exec("UPDATE academic_levels SET name = 'SS 3', stage = 'senior_secondary', rank_order = 16 WHERE id = 9");
    $pdo->exec("UPDATE academic_levels SET name = 'Pre-Nursery', stage = 'eyfs', rank_order = 2 WHERE id = 10");

    // Standardize EYFS and Primary levels
    $pdo->exec("UPDATE academic_levels SET name = 'Creche / Daycare', stage = 'eyfs', rank_order = 1 WHERE id = 27");
    $pdo->exec("UPDATE academic_levels SET name = 'Nursery 1', stage = 'eyfs', rank_order = 3 WHERE id = 29");
    $pdo->exec("UPDATE academic_levels SET name = 'Nursery 2', stage = 'eyfs', rank_order = 4 WHERE id = 30");
    $pdo->exec("UPDATE academic_levels SET name = 'Primary 1', stage = 'primary', rank_order = 5 WHERE id = 31");
    $pdo->exec("UPDATE academic_levels SET name = 'Primary 2', stage = 'primary', rank_order = 6 WHERE id = 32");
    $pdo->exec("UPDATE academic_levels SET name = 'Primary 3', stage = 'primary', rank_order = 7 WHERE id = 33");
    $pdo->exec("UPDATE academic_levels SET name = 'Primary 4', stage = 'primary', rank_order = 8 WHERE id = 34");
    $pdo->exec("UPDATE academic_levels SET name = 'Primary 5', stage = 'primary', rank_order = 9 WHERE id = 35");
    $pdo->exec("UPDATE academic_levels SET name = 'Primary 6', stage = 'primary', rank_order = 10 WHERE id = 36");

    // Re-point references from duplicate levels into canonical levels
    $levelMapping = [
        28 => 10, // Pre-Nursery -> ID 10
        37 => 4,  // JSS 1 (Grade 7) -> ID 4
        38 => 5,  // JSS 2 (Grade 8) -> ID 5
        39 => 7,  // JSS 3 (Grade 9) -> ID 7
        40 => 6,  // SS 1 (Grade 10) -> ID 6
        41 => 8,  // SS 2 (Grade 11) -> ID 8
        42 => 9,  // SS 3 (Grade 12) -> ID 9
    ];

    foreach ($levelMapping as $duplicateId => $canonicalId) {
        $pdo->exec("UPDATE classes SET academic_level_id = {$canonicalId} WHERE academic_level_id = {$duplicateId}");
        $pdo->exec("UPDATE assessment_categories SET academic_level_id = {$canonicalId} WHERE academic_level_id = {$duplicateId}");
        $pdo->exec("UPDATE admission_wards SET applying_for_level_id = {$canonicalId} WHERE applying_for_level_id = {$duplicateId}");
        $pdo->exec("DELETE FROM academic_levels WHERE id = {$duplicateId}");
    }
    echo "Academic levels consolidated to 16 canonical levels." . PHP_EOL;

    // 2. CONSOLIDATE CLASSES & MERGE DUPLICATE ARMS
    // JSS 1: Merge 67 -> 4 (Arm A), 68 -> 5 (Arm B)
    // JSS 2: Merge 69 -> 7 (Arm A)
    // JSS 3: Merge 71 -> 8 (Arm A)
    // SS 1: Merge 73 -> 6 (Science Arm A)
    // SS 2: Merge 76 -> 9 (Science Arm A)
    // SS 3: Merge 79 -> 10 (Science Arm A)
    // Pre-Nursery: Merge 49 -> 11 (Arm A)
    $classMerge = [
        67 => 4,
        68 => 5,
        69 => 7,
        71 => 8,
        73 => 6,
        76 => 9,
        79 => 10,
        49 => 11,
    ];

    foreach ($classMerge as $fromClassId => $toClassId) {
        $pdo->exec("UPDATE students SET current_class_id = {$toClassId} WHERE current_class_id = {$fromClassId}");

        // Deduplicate class_enrollments
        $fromEnrollments = $pdo->query("SELECT id, student_id, session_id FROM class_enrollments WHERE class_id = {$fromClassId}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fromEnrollments as $fe) {
            $checkEnr = $pdo->prepare("SELECT id FROM class_enrollments WHERE student_id = :stid AND class_id = :cid AND session_id = :sid LIMIT 1");
            $checkEnr->execute([':stid' => $fe['student_id'], ':cid' => $toClassId, ':sid' => $fe['session_id']]);
            if ($checkEnr->fetchColumn()) {
                $pdo->exec("DELETE FROM class_enrollments WHERE id = {$fe['id']}");
            } else {
                $pdo->exec("UPDATE class_enrollments SET class_id = {$toClassId} WHERE id = {$fe['id']}");
            }
        }

        // Deduplicate class_subjects
        $fromSubjects = $pdo->query("SELECT id, session_id, subject_id, teacher_id FROM class_subjects WHERE class_id = {$fromClassId}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fromSubjects as $fs) {
            $checkExisting = $pdo->prepare("SELECT id FROM class_subjects WHERE session_id = :sid AND class_id = :cid AND subject_id = :subid LIMIT 1");
            $checkExisting->execute([':sid' => $fs['session_id'], ':cid' => $toClassId, ':subid' => $fs['subject_id']]);
            $existingId = $checkExisting->fetchColumn();
            if ($existingId) {
                $pdo->exec("UPDATE assignments SET class_subject_id = {$existingId} WHERE class_subject_id = {$fs['id']}");
                $pdo->exec("UPDATE quizzes SET class_subject_id = {$existingId} WHERE class_subject_id = {$fs['id']}");

                // Delete duplicate student subject enrollments before re-pointing
                $pdo->exec("
                    DELETE sse_from FROM student_subject_enrollments sse_from
                    JOIN student_subject_enrollments sse_to 
                      ON sse_to.student_id = sse_from.student_id 
                     AND sse_to.class_subject_id = {$existingId}
                    WHERE sse_from.class_subject_id = {$fs['id']}
                ");
                $pdo->exec("UPDATE student_subject_enrollments SET class_subject_id = {$existingId} WHERE class_subject_id = {$fs['id']}");

                try {
                    $pdo->exec("UPDATE gradebooks SET class_subject_id = {$existingId} WHERE class_subject_id = {$fs['id']}");
                } catch (\Throwable) {}
                try {
                    $pdo->exec("UPDATE timetables SET class_subject_id = {$existingId} WHERE class_subject_id = {$fs['id']}");
                } catch (\Throwable) {}
                $pdo->exec("DELETE FROM class_subjects WHERE id = {$fs['id']}");
            } else {
                $pdo->exec("UPDATE class_subjects SET class_id = {$toClassId} WHERE id = {$fs['id']}");
            }
        }

        try {
            $pdo->exec("UPDATE attendance_records SET class_id = {$toClassId} WHERE class_id = {$fromClassId}");
        } catch (\Throwable) {}
        try {
            $pdo->exec("UPDATE timetables SET class_id = {$toClassId} WHERE class_id = {$fromClassId}");
        } catch (\Throwable) {}
        try {
            $pdo->exec("UPDATE result_publications SET class_id = {$toClassId} WHERE class_id = {$fromClassId}");
        } catch (\Throwable) {}
        $pdo->exec("DELETE FROM classes WHERE id = {$fromClassId}");
    }

    // Standardize canonical class names & section arms
    $canonicalClasses = [
        // EYFS
        47 => ['name' => 'Creche A', 'section_arm' => 'A', 'academic_level_id' => 27],
        48 => ['name' => 'Creche B', 'section_arm' => 'B', 'academic_level_id' => 27],
        11 => ['name' => 'Pre-Nursery A', 'section_arm' => 'A', 'academic_level_id' => 10],
        50 => ['name' => 'Pre-Nursery B', 'section_arm' => 'B', 'academic_level_id' => 10],
        51 => ['name' => 'Nursery 1A', 'section_arm' => 'A', 'academic_level_id' => 29],
        52 => ['name' => 'Nursery 1B', 'section_arm' => 'B', 'academic_level_id' => 29],
        53 => ['name' => 'Nursery 2A', 'section_arm' => 'A', 'academic_level_id' => 30],
        54 => ['name' => 'Nursery 2B', 'section_arm' => 'B', 'academic_level_id' => 30],
        // Primary
        55 => ['name' => 'Primary 1A', 'section_arm' => 'A', 'academic_level_id' => 31],
        56 => ['name' => 'Primary 1B', 'section_arm' => 'B', 'academic_level_id' => 31],
        57 => ['name' => 'Primary 2A', 'section_arm' => 'A', 'academic_level_id' => 32],
        58 => ['name' => 'Primary 2B', 'section_arm' => 'B', 'academic_level_id' => 32],
        59 => ['name' => 'Primary 3A', 'section_arm' => 'A', 'academic_level_id' => 33],
        60 => ['name' => 'Primary 3B', 'section_arm' => 'B', 'academic_level_id' => 33],
        61 => ['name' => 'Primary 4A', 'section_arm' => 'A', 'academic_level_id' => 34],
        62 => ['name' => 'Primary 4B', 'section_arm' => 'B', 'academic_level_id' => 34],
        63 => ['name' => 'Primary 5A', 'section_arm' => 'A', 'academic_level_id' => 35],
        64 => ['name' => 'Primary 5B', 'section_arm' => 'B', 'academic_level_id' => 35],
        65 => ['name' => 'Primary 6A', 'section_arm' => 'A', 'academic_level_id' => 36],
        66 => ['name' => 'Primary 6B', 'section_arm' => 'B', 'academic_level_id' => 36],
        // JSS
        4  => ['name' => 'JSS 1A', 'section_arm' => 'A', 'academic_level_id' => 4],
        5  => ['name' => 'JSS 1B', 'section_arm' => 'B', 'academic_level_id' => 4],
        7  => ['name' => 'JSS 2A', 'section_arm' => 'A', 'academic_level_id' => 5],
        70 => ['name' => 'JSS 2B', 'section_arm' => 'B', 'academic_level_id' => 5],
        8  => ['name' => 'JSS 3A', 'section_arm' => 'A', 'academic_level_id' => 7],
        72 => ['name' => 'JSS 3B', 'section_arm' => 'B', 'academic_level_id' => 7],
        // SSS
        6  => ['name' => 'SS 1A (Science)', 'section_arm' => 'A', 'academic_level_id' => 6],
        74 => ['name' => 'SS 1B (Art)', 'section_arm' => 'B', 'academic_level_id' => 6],
        75 => ['name' => 'SS 1C (Commercial)', 'section_arm' => 'C', 'academic_level_id' => 6],
        9  => ['name' => 'SS 2A (Science)', 'section_arm' => 'A', 'academic_level_id' => 8],
        77 => ['name' => 'SS 2B (Art)', 'section_arm' => 'B', 'academic_level_id' => 8],
        78 => ['name' => 'SS 2C (Commercial)', 'section_arm' => 'C', 'academic_level_id' => 8],
        10 => ['name' => 'SS 3A (Science)', 'section_arm' => 'A', 'academic_level_id' => 9],
        80 => ['name' => 'SS 3B (Art)', 'section_arm' => 'B', 'academic_level_id' => 9],
        81 => ['name' => 'SS 3C (Commercial)', 'section_arm' => 'C', 'academic_level_id' => 9],
    ];

    $classUpd = $pdo->prepare("UPDATE classes SET name = :name, section_arm = :section_arm, academic_level_id = :academic_level_id, status = 'active' WHERE id = :id");
    foreach ($canonicalClasses as $cId => $cData) {
        $classUpd->execute([
            ':id' => $cId,
            ':name' => $cData['name'],
            ':section_arm' => $cData['section_arm'],
            ':academic_level_id' => $cData['academic_level_id'],
        ]);
    }
    echo "Classes consolidated to 35 canonical classes and arms." . PHP_EOL;

    // 3. TERMS & DATE SPAN CLEANUP (Set Autumn Term active, non-overlapping dates)
    // Session 2 is 2026/2027
    $pdo->exec("UPDATE terms SET session_id = 2 WHERE session_id != 2");
    
    // Term 4: AUTUMN TERM (First term)
    $pdo->exec("UPDATE terms SET 
        name = 'AUTUMN TERM (First term)',
        start_date = '2026-09-01',
        end_date = '2026-12-18',
        status = 'active'
        WHERE id = 4
    ");

    // Term 5: SPRING TERM (Second term)
    $pdo->exec("UPDATE terms SET 
        name = 'SPRING TERM (Second term)',
        start_date = '2027-01-11',
        end_date = '2027-04-09',
        status = 'planning'
        WHERE id = 5
    ");

    // Term 6: SUMMER TERM (Third term)
    $pdo->exec("UPDATE terms SET 
        name = 'SUMMER TERM (Third term)',
        start_date = '2027-04-26',
        end_date = '2027-07-23',
        status = 'planning'
        WHERE id = 6
    ");

    // Re-point any records referencing Term 8 into Term 4, then remove Term 8
    foreach (['assignments', 'quizzes', 'attendance', 'timetables', 'result_publications', 'term_results', 'assessment_categories'] as $tbl) {
        try {
            $pdo->exec("UPDATE `{$tbl}` SET term_id = 4 WHERE term_id = 8");
        } catch (\Throwable) {}
    }
    $pdo->exec("DELETE FROM terms WHERE id = 8");

    // Ensure only Term 4 is active
    $pdo->exec("UPDATE terms SET status = 'planning' WHERE session_id = 2 AND id != 4");
    $pdo->exec("UPDATE terms SET status = 'active' WHERE id = 4");

    // Synchronize system settings
    $pdo->exec("UPDATE system_settings SET setting_value = 'AUTUMN TERM (First term)' WHERE setting_key = 'current_term'");
    $pdo->exec("UPDATE system_settings SET setting_value = '2026/2027' WHERE setting_key = 'academic_year'");
    echo "Academic terms cleaned up and AUTUMN TERM (First term) set active." . PHP_EOL;

    // 4. STANDARDIZE ASSESSMENT CATEGORIES (20%, 20%, 10%, 10%, 40% = 100%)
    $pdo->exec("DELETE FROM student_assessment_scores");
    $pdo->exec("DELETE FROM assessment_categories");

    $categories = [
        ['name' => 'Class Activity', 'weight' => 20.00, 'max_points' => 20.00],
        ['name' => 'End of Unit Test', 'weight' => 20.00, 'max_points' => 20.00],
        ['name' => 'SPAT', 'weight' => 10.00, 'max_points' => 10.00],
        ['name' => 'Home Fun', 'weight' => 10.00, 'max_points' => 10.00],
        ['name' => 'End of Term Test', 'weight' => 40.00, 'max_points' => 40.00],
    ];

    $insCat = $pdo->prepare("
        INSERT INTO assessment_categories (session_id, term_id, academic_level_id, name, weight_percentage, max_points)
        VALUES (:session_id, :term_id, :academic_level_id, :name, :weight_percentage, :max_points)
    ");

    // Seed global categories for Term 4 (Autumn Term)
    foreach ($categories as $cat) {
        $insCat->execute([
            ':session_id' => 2,
            ':term_id' => 4,
            ':academic_level_id' => null,
            ':name' => $cat['name'],
            ':weight_percentage' => $cat['weight'],
            ':max_points' => $cat['max_points'],
        ]);
    }
    echo "Assessment categories standardized to 100% (Class Activity 20%, End of Unit 20%, SPAT 10%, Home Fun 10%, End of Term 40%)." . PHP_EOL;

    if ($driver === 'mysql') {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    $pdo->commit();
    echo "Migration 0041 completed successfully!" . PHP_EOL;
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR in Migration 0041: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
