<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AcademicLevel;
use App\Models\SchoolClass;
use App\Models\Subject;
use PHPUnit\Framework\TestCase;

final class AcademicStructureTest extends TestCase
{
    public function testAcademicLevelInstantiation(): void
    {
        $level = new AcademicLevel(
            id: 1,
            name: 'JSS 1',
            stage: 'Junior Secondary',
            rankOrder: 7,
            gradingScaleId: null
        );

        $this->assertSame('JSS 1', $level->name);
        $this->assertSame('Junior Secondary', $level->stage);
        $this->assertSame(7, $level->rankOrder);
        $this->assertNull($level->gradingScaleId);
    }

    public function testSchoolClassInstantiation(): void
    {
        $class = new SchoolClass(
            id: 1,
            academicLevelId: 2,
            name: 'Grade 7A',
            sectionArm: 'A',
            status: SchoolClass::STATUS_ACTIVE
        );

        $this->assertTrue($class->isActive());
        $this->assertSame('Grade 7A', $class->name);
        $this->assertSame('A', $class->sectionArm);
    }

    public function testSubjectInstantiationAndCodeSanitization(): void
    {
        $subject = Subject::fromArray([
            'id' => 1,
            'name' => 'General Mathematics',
            'code' => ' mth101 ',
            'status' => 'active',
        ]);

        $this->assertTrue($subject->isActive());
        $this->assertSame('MTH101', $subject->code);
    }
}
