<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\GradeBoundary;
use App\Models\GradingScale;
use PHPUnit\Framework\TestCase;

final class GradingScaleTest extends TestCase
{
    private GradingScale $scale;

    protected function setUp(): void
    {
        $boundaries = [
            GradeBoundary::fromArray([
                'id' => 1,
                'grading_scale_id' => 1,
                'letter' => 'A',
                'min_score' => 70.0,
                'max_score' => 100.0,
                'grade_point' => 5.0,
                'remark' => 'Excellent',
            ]),
            GradeBoundary::fromArray([
                'id' => 2,
                'grading_scale_id' => 1,
                'letter' => 'B',
                'min_score' => 60.0,
                'max_score' => 69.99,
                'grade_point' => 4.0,
                'remark' => 'Very Good',
            ]),
            GradeBoundary::fromArray([
                'id' => 3,
                'grading_scale_id' => 1,
                'letter' => 'C',
                'min_score' => 50.0,
                'max_score' => 59.99,
                'grade_point' => 3.0,
                'remark' => 'Credit',
            ]),
            GradeBoundary::fromArray([
                'id' => 4,
                'grading_scale_id' => 1,
                'letter' => 'D',
                'min_score' => 45.0,
                'max_score' => 49.99,
                'grade_point' => 2.0,
                'remark' => 'Pass',
            ]),
            GradeBoundary::fromArray([
                'id' => 5,
                'grading_scale_id' => 1,
                'letter' => 'E',
                'min_score' => 40.0,
                'max_score' => 44.99,
                'grade_point' => 1.0,
                'remark' => 'Fair',
            ]),
            GradeBoundary::fromArray([
                'id' => 6,
                'grading_scale_id' => 1,
                'letter' => 'F',
                'min_score' => 0.0,
                'max_score' => 39.99,
                'grade_point' => 0.0,
                'remark' => 'Fail',
            ]),
        ];

        $this->scale = new GradingScale(
            id: 1,
            name: 'Standard Secondary Scale',
            description: 'WAEC / Standard 5-Point scale',
            isDefault: true,
            boundaries: $boundaries
        );
    }

    public function testGradingScaleMatchesScoresCorrectly(): void
    {
        $grade100 = $this->scale->resolveGrade(100.0);
        $this->assertNotNull($grade100);
        $this->assertSame('A', $grade100->letter);
        $this->assertEquals(5.0, $grade100->gradePoint);
        $this->assertSame('Excellent', $grade100->remark);

        $grade70 = $this->scale->resolveGrade(70.0);
        $this->assertNotNull($grade70);
        $this->assertSame('A', $grade70->letter);

        $grade69 = $this->scale->resolveGrade(69.5);
        $this->assertNotNull($grade69);
        $this->assertSame('B', $grade69->letter);

        $grade50 = $this->scale->resolveGrade(50.0);
        $this->assertNotNull($grade50);
        $this->assertSame('C', $grade50->letter);

        $grade45 = $this->scale->resolveGrade(45.0);
        $this->assertNotNull($grade45);
        $this->assertSame('D', $grade45->letter);

        $grade40 = $this->scale->resolveGrade(40.0);
        $this->assertNotNull($grade40);
        $this->assertSame('E', $grade40->letter);

        $grade0 = $this->scale->resolveGrade(0.0);
        $this->assertNotNull($grade0);
        $this->assertSame('F', $grade0->letter);
        $this->assertEquals(0.0, $grade0->gradePoint);
    }

    public function testGradingScaleHydrationAndSerialization(): void
    {
        $array = $this->scale->toArray();
        $this->assertSame(1, $array['id']);
        $this->assertSame('Standard Secondary Scale', $array['name']);
        $this->assertTrue($array['is_default']);
        $this->assertCount(6, $array['boundaries']);

        $hydrated = GradingScale::fromArray($array);
        $this->assertSame($this->scale->id, $hydrated->id);
        $this->assertSame($this->scale->name, $hydrated->name);
        $this->assertCount(6, $hydrated->boundaries);
    }
}
