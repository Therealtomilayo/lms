<?php

declare(strict_types=1);

namespace App\Utils;

use App\Models\SchoolClass;
use App\Repositories\AcademicRepository;
use App\Repositories\ImportRepository;

/**
 * Conservative Class Resolution Engine
 * Normalizes, maps, and validates incoming spreadsheet class strings
 * against canonical system classes without false matching.
 */
class ClassResolver
{
    public const STATUS_AUTO_RESOLVED = 'auto_resolved';
    public const STATUS_PREVIOUSLY_MAPPED = 'previously_mapped';
    public const STATUS_AMBIGUOUS = 'ambiguous';
    public const STATUS_UNMATCHED = 'unmatched';
    public const STATUS_INVALID = 'invalid';

    private AcademicRepository $academicRepository;
    private ImportRepository $importRepository;

    /**
     * Cached active canonical classes.
     * @var SchoolClass[]
     */
    private array $activeClasses;

    public function __construct(
        ?AcademicRepository $academicRepository = null,
        ?ImportRepository $importRepository = null
    ) {
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->importRepository = $importRepository ?? new ImportRepository();
        $this->activeClasses = array_filter(
            $this->academicRepository->getAllClasses(),
            fn(SchoolClass $c) => $c->isActive()
        );
    }

    /**
     * Normalize a raw class string into a consistent uppercase search token.
     * e.g. " jss-1 (a) " -> "JSS 1 A"
     * e.g. "SSS1-A" -> "SSS 1 A"
     * e.g. "Pre-Nursery Ruby" -> "PRE NURSERY RUBY"
     */
    public static function normalize(string $raw): string
    {
        $str = trim($raw);
        if ($str === '') {
            return '';
        }

        // Convert to uppercase
        $str = mb_strtoupper($str, 'UTF-8');

        // Replace hyphens, underscores, dots, parentheses, brackets, forward/back slashes with space
        $str = preg_replace('/[\-_.\(\)\[\]\/\\\]+/', ' ', $str) ?? $str;

        // Separate letters from numbers if stuck together (e.g. JSS1 -> JSS 1, SS2 -> SS 2)
        $str = preg_replace('/([A-Z])([0-9])/', '$1 $2', $str) ?? $str;

        // Separate numbers from arm letters (e.g. 1A -> 1 A, 2B -> 2 B)
        $str = preg_replace('/([0-9])([A-Z])/', '$1 $2', $str) ?? $str;

        // Collapse multiple whitespace into a single space
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;

        return trim($str);
    }

    /**
     * Resolve a raw class string against canonical classes and persistent mappings.
     *
     * @return array{
     *     raw: string,
     *     normalized: string,
     *     status: string,
     *     class_id: ?int,
     *     class_name: ?string,
     *     section_arm: ?string,
     *     candidate_classes: array<int, array{id: int, name: string, arm: ?string, full_name: string}>,
     *     message: string
     * }
     */
    public function resolve(string $rawClass): array
    {
        $raw = trim($rawClass);
        if ($raw === '') {
            return [
                'raw' => $rawClass,
                'normalized' => '',
                'status' => self::STATUS_INVALID,
                'class_id' => null,
                'class_name' => null,
                'section_arm' => null,
                'candidate_classes' => [],
                'message' => 'Class name cannot be empty.',
            ];
        }

        $normalized = self::normalize($raw);

        // Stage 1: Check persistent database translation memory (import_class_mappings)
        $persistedMapping = $this->importRepository->findClassMapping($normalized);
        if ($persistedMapping !== null) {
            $class = $this->findActiveClassById($persistedMapping['canonical_class_id']);
            if ($class !== null) {
                return [
                    'raw' => $raw,
                    'normalized' => $normalized,
                    'status' => self::STATUS_PREVIOUSLY_MAPPED,
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'section_arm' => $class->sectionArm,
                    'candidate_classes' => [],
                    'message' => "Resolved via persistent mapping to '{$class->getFullName()}'.",
                ];
            }
        }

        // Stage 2: Exact canonical match against classes
        // Note: A match is only exact if:
        // 1. Full name including arm matches (e.g. "JSS 1 A" == "JSS 1 A", "SSS 1A" == "SSS 1A")
        // 2. OR the class has no section arm and the base name matches uniquely (e.g. "JSS 2" where JSS 2 has no arms)
        $exactMatches = [];
        foreach ($this->activeClasses as $class) {
            $canonicalFullNorm = self::normalize($class->name . ' ' . ($class->sectionArm ?? ''));
            $canonicalDisplayNameNorm = self::normalize($class->getFullName());
            $hasArm = !empty($class->sectionArm);

            if ($normalized === $canonicalFullNorm || $normalized === $canonicalDisplayNameNorm) {
                $exactMatches[] = $class;
            } elseif (!$hasArm && $normalized === self::normalize($class->name)) {
                $exactMatches[] = $class;
            }
        }

        if (count($exactMatches) === 1) {
            $class = $exactMatches[0];
            return [
                'raw' => $raw,
                'normalized' => $normalized,
                'status' => self::STATUS_AUTO_RESOLVED,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'section_arm' => $class->sectionArm,
                'candidate_classes' => [],
                'message' => "Exact match with '{$class->getFullName()}'.",
            ];
        }

        // Stage 3: Parse potential base class & section arm
        // e.g. "JSS 1 A" -> base: "JSS 1", arm: "A"
        $parts = explode(' ', $normalized);
        $candidateArm = null;
        $candidateBase = $normalized;

        if (count($parts) >= 2) {
            $lastPart = end($parts);
            // Arm is typically 1-3 letters or standard name (e.g. A, B, C, GOLD, RUBY)
            if (strlen($lastPart) <= 10) {
                $candidateArm = $lastPart;
                $candidateBase = implode(' ', array_slice($parts, 0, -1));
            }
        }

        // Check if candidateBase matches canonical class name and candidateArm matches arm
        $matchedWithArm = [];
        $matchedBaseOnly = [];

        foreach ($this->activeClasses as $class) {
            $classNameNorm = self::normalize($class->name);
            $classArmNorm = self::normalize((string)($class->sectionArm ?? ''));

            if ($candidateArm !== null && $classNameNorm === $candidateBase && $classArmNorm === $candidateArm) {
                $matchedWithArm[] = $class;
            }

            // Check if base matches
            if ($classNameNorm === $candidateBase || $classNameNorm === $normalized) {
                $matchedBaseOnly[] = $class;
            }
        }

        if (count($matchedWithArm) === 1) {
            $class = $matchedWithArm[0];
            return [
                'raw' => $raw,
                'normalized' => $normalized,
                'status' => self::STATUS_AUTO_RESOLVED,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'section_arm' => $class->sectionArm,
                'candidate_classes' => [],
                'message' => "Matched with '{$class->getFullName()}'.",
            ];
        }

        // Stage 4: Ambiguity Detection
        // If the base class was recognized, but multiple arms exist, we DO NOT guess.
        if (count($matchedBaseOnly) > 1) {
            $candidates = array_map(fn(SchoolClass $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'arm' => $c->sectionArm,
                'full_name' => $c->getFullName(),
            ], $matchedBaseOnly);

            return [
                'raw' => $raw,
                'normalized' => $normalized,
                'status' => self::STATUS_AMBIGUOUS,
                'class_id' => null,
                'class_name' => null,
                'section_arm' => null,
                'candidate_classes' => $candidates,
                'message' => "Ambiguous class '{$raw}'. Multiple arms exist (" . implode(', ', array_column($candidates, 'full_name')) . "). Administrator resolution required.",
            ];
        }

        // If exactly 1 base class exists and it has no arms or only 1 arm, and user didn't specify arm
        if (count($matchedBaseOnly) === 1) {
            $class = $matchedBaseOnly[0];
            // Only auto-resolve if canonical class has no arm or user didn't give conflicting arm
            if (empty($class->sectionArm) || $candidateArm === null) {
                return [
                    'raw' => $raw,
                    'normalized' => $normalized,
                    'status' => self::STATUS_AUTO_RESOLVED,
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'section_arm' => $class->sectionArm,
                    'candidate_classes' => [],
                    'message' => "Auto-resolved to '{$class->getFullName()}'.",
                ];
            }
        }

        // Stage 5: Unmatched
        $allActive = array_map(fn(SchoolClass $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'arm' => $c->sectionArm,
            'full_name' => $c->getFullName(),
        ], $this->activeClasses);

        return [
            'raw' => $raw,
            'normalized' => $normalized,
            'status' => self::STATUS_UNMATCHED,
            'class_id' => null,
            'class_name' => null,
            'section_arm' => null,
            'candidate_classes' => $allActive,
            'message' => "Could not match '{$raw}' to any active school class.",
        ];
    }

    /**
     * Batch resolve multiple raw class strings.
     *
     * @param string[] $rawClasses
     * @return array<string, array<string, mixed>> Keyed by raw class string
     */
    public function resolveBatch(array $rawClasses): array
    {
        $results = [];
        $unique = array_unique(array_filter(array_map('trim', $rawClasses)));

        foreach ($unique as $raw) {
            $results[$raw] = $this->resolve($raw);
        }

        return $results;
    }

    public static function normalizeClassString(string $raw): string
    {
        return self::normalize($raw);
    }

    /**
     * Resolve multiple class strings and partition into matched, unmatched, and ambiguous buckets.
     *
     * @param string[] $rawClasses
     * @return array{
     *     matched: array<string, array{canonical_class_id: int, name: string, section_arm: ?string, status: string, message: string}>,
     *     unmatched: string[],
     *     ambiguous: array<string, array{reason: string, candidates: array}>
     * }
     */
    public function resolveMultiple(array $rawClasses): array
    {
        $batch = $this->resolveBatch($rawClasses);
        $matched = [];
        $unmatched = [];
        $ambiguous = [];

        foreach ($batch as $raw => $res) {
            if ($res['status'] === self::STATUS_AUTO_RESOLVED || $res['status'] === self::STATUS_PREVIOUSLY_MAPPED) {
                $matched[$raw] = [
                    'canonical_class_id' => (int)$res['class_id'],
                    'name' => (string)$res['class_name'],
                    'section_arm' => $res['section_arm'],
                    'status' => (string)$res['status'],
                    'message' => (string)$res['message'],
                ];
            } elseif ($res['status'] === self::STATUS_AMBIGUOUS) {
                $ambiguous[$raw] = [
                    'reason' => (string)$res['message'],
                    'candidates' => $res['candidate_classes'],
                ];
            } else {
                $unmatched[] = $raw;
            }
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'ambiguous' => $ambiguous,
        ];
    }

    private function findActiveClassById(int $classId): ?SchoolClass
    {
        foreach ($this->activeClasses as $class) {
            if ($class->id === $classId) {
                return $class;
            }
        }
        return null;
    }
}
