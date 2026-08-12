<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\DTO\ServiceResult;
use App\Models\AssessmentCategory;
use App\Models\GradeBoundary;
use App\Models\GradingScale;
use App\Models\StudentAssessmentScore;
use App\Models\StudentTermSummary;
use App\Models\TermResult;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\GradingScaleRepository;

/**
 * Service for Gradebook calculations, Score Management, and Class Rankings
 */
final class GradebookService
{
    private readonly GradebookRepository $gradebookRepo;
    private readonly GradingScaleRepository $gradingScaleRepo;
    private readonly EnrollmentRepository $enrollmentRepo;
    private readonly AcademicRepository $academicRepo;

    public function __construct(
        ?GradebookRepository $gradebookRepo = null,
        ?GradingScaleRepository $gradingScaleRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->gradingScaleRepo = $gradingScaleRepo ?? new GradingScaleRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Validate that assessment categories for a given context sum to exactly 100%
     *
     * @param array<int, AssessmentCategory> $categories
     */
    public function validateCategoryWeights(array $categories): void
    {
        if (empty($categories)) {
            throw new DomainRuleException('At least one assessment category is required.');
        }

        $totalWeight = 0.0;
        foreach ($categories as $category) {
            if ($category->weightPercentage <= 0) {
                throw new ValidationException(['weight_percentage' => "Category '{$category->name}' weight must be greater than 0."]);
            }
            if ($category->maxPoints <= 0) {
                throw new ValidationException(['max_points' => "Category '{$category->name}' max points must be greater than 0."]);
            }
            $totalWeight += $category->weightPercentage;
        }

        if (abs($totalWeight - 100.0) > 0.01) {
            throw new DomainRuleException(sprintf(
                'Assessment category weights must sum to exactly 100%%. Current total: %.2f%%.',
                $totalWeight
            ));
        }
    }

    /**
     * Save raw assessment scores for a class-subject
     *
     * @param array<int, array<int, float>> $scoresMatrix [studentId => [categoryId => rawScore]]
     */
    public function saveScores(
        int $classSubjectId,
        int $sessionId,
        int $termId,
        array $scoresMatrix,
        int $recordedBy
    ): ServiceResult {
        if ($this->gradebookRepo->isClassSubjectLocked($classSubjectId, $termId)) {
            throw new DomainRuleException('Cannot modify scores. Results for this class subject and term have been locked.');
        }

        $categories = $this->gradebookRepo->getCategoriesByContext($sessionId, $termId, $classSubjectId);
        if (empty($categories)) {
            throw new DomainRuleException('No assessment categories configured for this term.');
        }

        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat->id] = $cat;
        }

        // Validate each score against category maxPoints
        foreach ($scoresMatrix as $studentId => $catScores) {
            foreach ($catScores as $catId => $rawScore) {
                if ($rawScore === null || $rawScore === '') {
                    continue;
                }
                $rawScoreFloat = (float)$rawScore;
                if (!isset($categoryMap[$catId])) {
                    throw new ValidationException(['category_id' => "Invalid category ID: {$catId}"]);
                }

                $maxPoints = $categoryMap[$catId]->maxPoints;
                if ($rawScoreFloat < 0.0 || $rawScoreFloat > $maxPoints) {
                    throw new ValidationException([
                        'score' => sprintf(
                            'Score %.2f for student %d in category %s exceeds valid range (0.00 - %.2f).',
                            $rawScoreFloat,
                            $studentId,
                            $categoryMap[$catId]->name,
                            $maxPoints
                        )
                    ]);
                }
            }
        }

        // Persist scores
        foreach ($scoresMatrix as $studentId => $catScores) {
            foreach ($catScores as $catId => $rawScore) {
                if ($rawScore === null || $rawScore === '') {
                    continue;
                }
                $this->gradebookRepo->upsertScore(
                    (int)$catId,
                    (int)$studentId,
                    $classSubjectId,
                    (float)$rawScore,
                    $recordedBy
                );
            }
        }

        return ServiceResult::success(null, 'Assessment scores saved successfully.');
    }

    /**
     * Calculate subject result for a student based on raw scores and categories
     *
     * @param array<int, AssessmentCategory> $categories
     * @param array<int, StudentAssessmentScore> $studentScores [categoryId => StudentAssessmentScore]
     */
    public function calculateSubjectScore(
        array $categories,
        array $studentScores,
        GradingScale $gradingScale
    ): array {
        $computedScore = 0.0;
        $breakdownCategories = [];

        foreach ($categories as $cat) {
            $scoreRecord = $studentScores[$cat->id] ?? null;
            $rawScore = $scoreRecord ? $scoreRecord->rawScore : 0.0;
            $maxPoints = $cat->maxPoints > 0 ? $cat->maxPoints : 100.0;

            // weighted_contribution = (raw_score / max_points) * weight_percentage
            $weightedContribution = ($rawScore / $maxPoints) * $cat->weightPercentage;
            $computedScore += $weightedContribution;

            $breakdownCategories[] = [
                'category_id' => $cat->id,
                'name' => $cat->name,
                'weight_percentage' => $cat->weightPercentage,
                'max_points' => $cat->maxPoints,
                'raw_score' => round($rawScore, 2),
                'weighted_contribution' => round($weightedContribution, 2),
            ];
        }

        $computedScore = round($computedScore, 2);
        $computedScore = max(0.0, min(100.0, $computedScore));

        $matchedBoundary = $gradingScale->resolveGrade($computedScore);

        $gradeLetter = $matchedBoundary ? $matchedBoundary->letter : 'F';
        $gradePoint = $matchedBoundary ? $matchedBoundary->gradePoint : 0.0;
        $remark = $matchedBoundary ? $matchedBoundary->remark : 'Fail';

        return [
            'computed_score' => $computedScore,
            'grade_letter' => $gradeLetter,
            'grade_point' => $gradePoint,
            'remark' => $remark,
            'breakdown' => [
                'categories' => $breakdownCategories,
                'computed_score' => $computedScore,
                'grade_letter' => $gradeLetter,
                'grade_point' => $gradePoint,
                'remark' => $remark,
                'grading_scale_name' => $gradingScale->name,
            ],
        ];
    }

    /**
     * Compute and snapshot term results for all enrolled students in a class-subject
     */
    public function computeClassSubjectResults(
        int $classSubjectId,
        int $sessionId,
        int $termId,
        bool $lockResults = false,
        ?int $lockedBy = null
    ): ServiceResult {
        $classSubject = $this->academicRepo->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject not found.');
        }

        $categories = $this->gradebookRepo->getCategoriesByContext($sessionId, $termId, $classSubjectId);
        $this->validateCategoryWeights($categories);

        $gradingScale = $this->gradingScaleRepo->getDefaultScale();
        if (!$gradingScale) {
            throw new DomainRuleException('No grading scale configured in the system.');
        }

        $enrolledStudents = $this->enrollmentRepo->getStudentsBySubjectAndSession(
            $classSubject->classId,
            $classSubject->subjectId,
            $sessionId
        );

        $existingScores = $this->gradebookRepo->getScoresByClassSubject($classSubjectId);
        $scoresByStudent = [];
        foreach ($existingScores as $score) {
            $scoresByStudent[$score->studentId][$score->assessmentCategoryId] = $score;
        }

        $computedCount = 0;
        foreach ($enrolledStudents as $student) {
            $studentCatScores = $scoresByStudent[$student->id] ?? [];
            $calc = $this->calculateSubjectScore($categories, $studentCatScores, $gradingScale);

            $this->gradebookRepo->upsertTermResult(
                $student->id,
                $classSubjectId,
                $termId,
                $calc['computed_score'],
                $calc['grade_letter'],
                $calc['grade_point'],
                $calc['remark'],
                $calc['breakdown'],
                $lockResults,
                $lockedBy
            );
            $computedCount++;
        }

        return ServiceResult::success([
            'computed_count' => $computedCount,
        ], 'Subject term results computed successfully.');
    }

    /**
     * Compute term summary (averages, GPA, class ranking with ties) for an entire class
     */
    public function computeClassTermSummaries(
        int $classId,
        int $sessionId,
        int $termId,
        bool $lockSummaries = false,
        ?int $lockedBy = null
    ): ServiceResult {
        $enrolledStudents = $this->enrollmentRepo->getStudentsByClassAndSession($classId, $sessionId);
        if (empty($enrolledStudents)) {
            return ServiceResult::success(['processed_count' => 0], 'No students enrolled in class.');
        }

        $studentSummaries = [];

        foreach ($enrolledStudents as $student) {
            $termResults = $this->gradebookRepo->getTermResultsByStudent($student->id, $termId);
            $totalScore = 0.0;
            $subjectCount = count($termResults);
            $totalGradePoints = 0.0;
            $gradePointCount = 0;

            foreach ($termResults as $tr) {
                $totalScore += $tr->computedScore;
                if ($tr->gradePoint !== null) {
                    $totalGradePoints += $tr->gradePoint;
                    $gradePointCount++;
                }
            }

            $averageScore = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0.0;
            $gpa = $gradePointCount > 0 ? round($totalGradePoints / $gradePointCount, 2) : null;

            $studentSummaries[] = [
                'student_id' => $student->id,
                'total_score' => round($totalScore, 2),
                'average_score' => $averageScore,
                'gpa' => $gpa,
                'subject_count' => $subjectCount,
            ];
        }

        // Rank students by average_score DESC (deterministic tie-breaking)
        usort($studentSummaries, function ($a, $b) {
            if ($b['average_score'] !== $a['average_score']) {
                return ($b['average_score'] <=> $a['average_score']);
            }
            return $a['student_id'] <=> $b['student_id'];
        });

        // Compute rank with standard competition ranking (1, 2, 2, 4...)
        $currentRank = 1;
        $itemsCount = count($studentSummaries);
        for ($i = 0; $i < $itemsCount; $i++) {
            if ($i > 0 && $studentSummaries[$i]['average_score'] < $studentSummaries[$i - 1]['average_score']) {
                $currentRank = $i + 1;
            }
            $studentSummaries[$i]['rank_in_class'] = $currentRank;
        }

        // Persist student term summaries
        foreach ($studentSummaries as $summary) {
            $this->gradebookRepo->upsertStudentTermSummary(
                $summary['student_id'],
                $termId,
                $classId,
                $summary['total_score'],
                $summary['average_score'],
                $summary['gpa'],
                $summary['rank_in_class'],
                0, // attendance_present_count placeholder
                0, // attendance_total_count placeholder
                null,
                null,
                'pending',
                $lockSummaries,
                $lockedBy
            );
        }

        return ServiceResult::success([
            'processed_count' => count($studentSummaries),
        ], 'Class term summaries computed and ranked successfully.');
    }
}
