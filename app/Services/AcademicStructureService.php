<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\DTO\ServiceResult;
use App\Models\AcademicLevel;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Repositories\AcademicRepository;

/**
 * Business Service for Academic Levels, Classes, and Subjects
 */
class AcademicStructureService
{
    private AcademicRepository $repository;

    public function __construct(?AcademicRepository $repository = null)
    {
        $this->repository = $repository ?? new AcademicRepository();
    }

    // ==========================================
    // ACADEMIC LEVELS
    // ==========================================

    public function createLevel(array $data): ServiceResult
    {
        $name = trim((string)($data['name'] ?? ''));
        $stage = trim((string)($data['stage'] ?? ''));
        $rankOrder = (int)($data['rank_order'] ?? 0);
        $gradingScaleId = !empty($data['grading_scale_id']) ? (int)$data['grading_scale_id'] : null;

        if ($name === '' || $stage === '') {
            throw new ValidationException(['general' => ['Level name and educational stage are required.']]);
        }

        if ($this->repository->findLevelByName($name) !== null) {
            throw new DomainRuleException("Academic level '{$name}' already exists.");
        }

        if ($gradingScaleId !== null && $this->repository->findGradingScaleById($gradingScaleId) === null) {
            throw new DomainRuleException("Referenced grading scale #{$gradingScaleId} does not exist.");
        }

        $level = $this->repository->createLevel([
            'name' => $name,
            'stage' => $stage,
            'rank_order' => $rankOrder,
            'grading_scale_id' => $gradingScaleId,
        ]);

        return ServiceResult::success($level);
    }

    public function updateLevel(int $id, array $data): ServiceResult
    {
        $level = $this->repository->findLevelById($id);
        if (!$level) {
            throw new ResourceNotFoundException("Academic level #{$id} not found.");
        }

        $name = trim((string)($data['name'] ?? $level->name));
        $stage = trim((string)($data['stage'] ?? $level->stage));
        $rankOrder = isset($data['rank_order']) ? (int)$data['rank_order'] : $level->rankOrder;
        $gradingScaleId = array_key_exists('grading_scale_id', $data)
            ? (!empty($data['grading_scale_id']) ? (int)$data['grading_scale_id'] : null)
            : $level->gradingScaleId;

        $existing = $this->repository->findLevelByName($name);
        if ($existing !== null && $existing->id !== $id) {
            throw new DomainRuleException("Academic level '{$name}' already exists.");
        }

        if ($gradingScaleId !== null && $this->repository->findGradingScaleById($gradingScaleId) === null) {
            throw new DomainRuleException("Referenced grading scale #{$gradingScaleId} does not exist.");
        }

        $this->repository->updateLevel($id, [
            'name' => $name,
            'stage' => $stage,
            'rank_order' => $rankOrder,
            'grading_scale_id' => $gradingScaleId,
        ]);

        return ServiceResult::success($this->repository->findLevelById($id));
    }

    // ==========================================
    // CLASSES
    // ==========================================

    public function createClass(array $data): ServiceResult
    {
        $levelId = (int)($data['academic_level_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $sectionArm = !empty($data['section_arm']) ? trim((string)$data['section_arm']) : null;

        if ($levelId <= 0 || $name === '') {
            throw new ValidationException(['general' => ['Academic level and class name are required.']]);
        }

        $level = $this->repository->findLevelById($levelId);
        if (!$level) {
            throw new ResourceNotFoundException("Academic level #{$levelId} not found.");
        }

        if ($this->repository->findClassByNameAndLevel($name, $levelId, $sectionArm) !== null) {
            throw new DomainRuleException("A class named '{$name}'" . ($sectionArm ? " (Arm {$sectionArm})" : "") . " already exists for this level.");
        }

        $class = $this->repository->createClass([
            'academic_level_id' => $levelId,
            'name' => $name,
            'section_arm' => $sectionArm,
            'status' => $data['status'] ?? SchoolClass::STATUS_ACTIVE,
        ]);

        return ServiceResult::success($this->repository->findClassById($class->id));
    }

    public function updateClass(int $id, array $data): ServiceResult
    {
        $class = $this->repository->findClassById($id);
        if (!$class) {
            throw new ResourceNotFoundException("Class #{$id} not found.");
        }

        $levelId = (int)($data['academic_level_id'] ?? $class->academicLevelId);
        $name = trim((string)($data['name'] ?? $class->name));
        $sectionArm = array_key_exists('section_arm', $data)
            ? (!empty($data['section_arm']) ? trim((string)$data['section_arm']) : null)
            : $class->sectionArm;

        $level = $this->repository->findLevelById($levelId);
        if (!$level) {
            throw new ResourceNotFoundException("Academic level #{$levelId} not found.");
        }

        $existing = $this->repository->findClassByNameAndLevel($name, $levelId, $sectionArm);
        if ($existing !== null && $existing->id !== $id) {
            throw new DomainRuleException("A class named '{$name}'" . ($sectionArm ? " (Arm {$sectionArm})" : "") . " already exists for this level.");
        }

        $this->repository->updateClass($id, [
            'academic_level_id' => $levelId,
            'name' => $name,
            'section_arm' => $sectionArm,
        ]);

        return ServiceResult::success($this->repository->findClassById($id));
    }

    public function updateClassStatus(int $id, string $status): ServiceResult
    {
        $class = $this->repository->findClassById($id);
        if (!$class) {
            throw new ResourceNotFoundException("Class #{$id} not found.");
        }

        if (!in_array($status, [SchoolClass::STATUS_ACTIVE, SchoolClass::STATUS_INACTIVE], true)) {
            throw new DomainRuleException("Invalid class status '{$status}'.");
        }

        $this->repository->updateClassStatus($id, $status);

        return ServiceResult::success($this->repository->findClassById($id));
    }

    // ==========================================
    // SUBJECTS
    // ==========================================

    public function createSubject(array $data): ServiceResult
    {
        $name = trim((string)($data['name'] ?? ''));
        $code = strtoupper(trim((string)($data['code'] ?? '')));

        if ($name === '' || $code === '') {
            throw new ValidationException(['general' => ['Subject name and code are required.']]);
        }

        if ($this->repository->findSubjectByCode($code) !== null) {
            throw new DomainRuleException("A subject with code '{$code}' already exists.");
        }

        $subject = $this->repository->createSubject([
            'name' => $name,
            'code' => $code,
            'status' => $data['status'] ?? Subject::STATUS_ACTIVE,
        ]);

        return ServiceResult::success($subject);
    }

    public function updateSubject(int $id, array $data): ServiceResult
    {
        $subject = $this->repository->findSubjectById($id);
        if (!$subject) {
            throw new ResourceNotFoundException("Subject #{$id} not found.");
        }

        $name = trim((string)($data['name'] ?? $subject->name));
        $code = strtoupper(trim((string)($data['code'] ?? $subject->code)));

        $existing = $this->repository->findSubjectByCode($code);
        if ($existing !== null && $existing->id !== $id) {
            throw new DomainRuleException("A subject with code '{$code}' already exists.");
        }

        $this->repository->updateSubject($id, [
            'name' => $name,
            'code' => $code,
        ]);

        return ServiceResult::success($this->repository->findSubjectById($id));
    }

    public function updateSubjectStatus(int $id, string $status): ServiceResult
    {
        $subject = $this->repository->findSubjectById($id);
        if (!$subject) {
            throw new ResourceNotFoundException("Subject #{$id} not found.");
        }

        if (!in_array($status, [Subject::STATUS_ACTIVE, Subject::STATUS_INACTIVE], true)) {
            throw new DomainRuleException("Invalid subject status '{$status}'.");
        }

        $this->repository->updateSubjectStatus($id, $status);

        return ServiceResult::success($this->repository->findSubjectById($id));
    }
}
