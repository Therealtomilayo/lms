<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Models\AssessmentCategory;
use App\Models\GradeBoundary;
use App\Models\GradingScale;
use App\Models\StudentAssessmentScore;
use App\Services\GradebookService;
use PHPUnit\Framework\TestCase;

final class GradeCalculationTest extends TestCase
{
    private GradebookService $service;
    private GradingScale $gradingScale;

    protected function setUp(): void
    {
        $this->service = new GradebookService();

        $boundaries = [
            GradeBoundary::fromArray(['id' => 1, 'grading_scale_id' => 1, 'letter' => 'A', 'min_score' => 70.0, 'max_score' => 100.0, 'grade_point' => 5.0, 'remark' => 'Excellent']),
            GradeBoundary::fromArray(['id' => 2, 'grading_scale_id' => 1, 'letter' => 'B', 'min_score' => 60.0, 'max_score' => 69.99, 'grade_point' => 4.0, 'remark' => 'Very Good']),
            GradeBoundary::fromArray(['id' => 3, 'grading_scale_id' => 1, 'letter' => 'C', 'min_score' => 50.0, 'max_score' => 59.99, 'grade_point' => 3.0, 'remark' => 'Credit']),
            GradeBoundary::fromArray(['id' => 4, 'grading_scale_id' => 1, 'letter' => 'D', 'min_score' => 45.0, 'max_score' => 49.99, 'grade_point' => 2.0, 'remark' => 'Pass']),
            GradeBoundary::fromArray(['id' => 5, 'grading_scale_id' => 1, 'letter' => 'E', 'min_score' => 40.0, 'max_score' => 44.99, 'grade_point' => 1.0, 'remark' => 'Fair']),
            GradeBoundary::fromArray(['id' => 6, 'grading_scale_id' => 1, 'letter' => 'F', 'min_score' => 0.0,  'max_score' => 39.99, 'grade_point' => 0.0, 'remark' => 'Fail']),
        ];

        $this->gradingScale = new GradingScale(
            id: 1,
            name: '5-Point Scale',
            description: 'Standard',
            isDefault: true,
            boundaries: $boundaries
        );
    }

    public function testWeightValidationFailsWhenTotalNot100(): void
    {
        $categories = [
            AssessmentCategory::fromArray(['id' => 1, 'name' => 'CA 1', 'weight_percentage' => 30.0, 'max_points' => 30.0]),
            AssessmentCategory::fromArray(['id' => 2, 'name' => 'Exam', 'weight_percentage' => 50.0, 'max_points' => 70.0]),
        ];

        $this->expectException(DomainRuleException::class);
        $this->service->validateCategoryWeights($categories);
    }

    public function testWeightValidationFailsOnZeroOrNegative(): void
    {
        $categories = [
            AssessmentCategory::fromArray(['id' => 1, 'name' => 'CA 1', 'weight_percentage' => -10.0, 'max_points' => 30.0]),
            AssessmentCategory::fromArray(['id' => 2, 'name' => 'Exam', 'weight_percentage' => 110.0, 'max_points' => 70.0]),
        ];

        $this->expectException(ValidationException::class);
        $this->service->validateCategoryWeights($categories);
    }

    public function testCalculateSubjectScoreWeightedProperly(): void
    {
        $categories = [
            AssessmentCategory::fromArray(['id' => 10, 'name' => 'CA 1', 'weight_percentage' => 20.0, 'max_points' => 20.0]),
            AssessmentCategory::fromArray(['id' => 20, 'name' => 'CA 2', 'weight_percentage' => 20.0, 'max_points' => 20.0]),
            AssessmentCategory::fromArray(['id' => 30, 'name' => 'Exam', 'weight_percentage' => 60.0, 'max_points' => 100.0]),
        ];

        $scores = [
            10 => StudentAssessmentScore::fromArray(['id' => 1, 'assessment_category_id' => 10, 'student_id' => 5, 'class_subject_id' => 1, 'raw_score' => 15.0, 'recorded_by' => 1]),
            20 => StudentAssessmentScore::fromArray(['id' => 2, 'assessment_category_id' => 20, 'student_id' => 5, 'class_subject_id' => 1, 'raw_score' => 18.0, 'recorded_by' => 1]),
            30 => StudentAssessmentScore::fromArray(['id' => 3, 'assessment_category_id' => 30, 'student_id' => 5, 'class_subject_id' => 1, 'raw_score' => 75.0, 'recorded_by' => 1]),
        ];

        // CA 1: (15/20) * 20 = 15.0
        // CA 2: (18/20) * 20 = 18.0
        // Exam: (75/100) * 60 = 45.0
        // Total = 15 + 18 + 45 = 78.0 (Grade A)
        $result = $this->service->calculateSubjectScore($categories, $scores, $this->gradingScale);

        $this->assertEquals(78.0, $result['computed_score']);
        $this->assertSame('A', $result['grade_letter']);
        $this->assertEquals(5.0, $result['grade_point']);
        $this->assertSame('Excellent', $result['remark']);
        $this->assertCount(3, $result['breakdown']['categories']);
    }
}
