<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\GradebookPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\GradingScaleRepository;

/**
 * Controller for Admin Grading Scale & Grade Boundary Management
 */
class GradingScaleController extends Controller
{
    private GradingScaleRepository $gradingScaleRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradingScaleRepository $gradingScaleRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradingScaleRepo = $gradingScaleRepo ?? new GradingScaleRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageGradingScales($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $scales = $this->gradingScaleRepo->getAll();
        $stages = $this->academicRepo->getAllStages();

        return $this->view('admin/grading_scales/index', [
            'scales' => $scales,
            'stages' => $stages,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageGradingScales($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->redirectWithError('/admin/grading-scales', 'Scale name is required.');
        }

        $stage = trim((string)$request->input('stage', ''));
        $description = trim((string)$request->input('description', ''));
        $isDefault = (bool)$request->input('is_default', false);

        $scaleId = $this->gradingScaleRepo->createScale([
            'name' => $name,
            'stage' => $stage !== '' ? $stage : null,
            'description' => $description !== '' ? $description : null,
            'is_default' => $isDefault,
        ]);

        $boundariesInput = $request->input('boundaries', []);
        $cleanBoundaries = $this->cleanBoundariesInput($boundariesInput);
        if (!empty($cleanBoundaries)) {
            $this->gradingScaleRepo->syncBoundaries($scaleId, $cleanBoundaries);
        }

        return $this->redirectWithSuccess('/admin/grading-scales', 'Grading scale created successfully.');
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageGradingScales($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $scaleId = (int)$id;
        $scale = $this->gradingScaleRepo->findById($scaleId);
        if (!$scale) {
            return $this->redirectWithError('/admin/grading-scales', "Grading scale #{$scaleId} not found.");
        }

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->redirectWithError('/admin/grading-scales', 'Scale name cannot be empty.');
        }

        $stage = trim((string)$request->input('stage', ''));
        $description = trim((string)$request->input('description', ''));
        $isDefault = (bool)$request->input('is_default', false);

        $this->gradingScaleRepo->updateScale($scaleId, [
            'name' => $name,
            'stage' => $stage !== '' ? $stage : null,
            'description' => $description !== '' ? $description : null,
            'is_default' => $isDefault,
        ]);

        $boundariesInput = $request->input('boundaries', []);
        $cleanBoundaries = $this->cleanBoundariesInput($boundariesInput);
        if (!empty($cleanBoundaries)) {
            $this->gradingScaleRepo->syncBoundaries($scaleId, $cleanBoundaries);
        }

        return $this->redirectWithSuccess('/admin/grading-scales', "Grading scale '{$name}' updated successfully.");
    }

    public function delete(Request $request, string|int $id = 0): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageGradingScales($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $scaleId = (int)$id;
        $scale = $this->gradingScaleRepo->findById($scaleId);
        if (!$scale) {
            return $this->redirectWithError('/admin/grading-scales', "Grading scale #{$scaleId} not found.");
        }

        if ($scale->isDefault) {
            return $this->redirectWithError('/admin/grading-scales', 'Cannot delete the system default scale. Set another scale as default first.');
        }

        $this->gradingScaleRepo->deleteScale($scaleId);
        return $this->redirectWithSuccess('/admin/grading-scales', "Grading scale '{$scale->name}' removed successfully.");
    }

    /**
     * @param mixed $input
     * @return array<int, array<string, mixed>>
     */
    private function cleanBoundariesInput(mixed $input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $clean = [];
        foreach ($input as $row) {
            if (!is_array($row)) {
                continue;
            }
            $letter = trim((string)($row['letter'] ?? ''));
            if ($letter === '') {
                continue;
            }

            $min = (float)($row['min_score'] ?? 0.0);
            $max = (float)($row['max_score'] ?? 100.0);
            $remark = isset($row['remark']) && trim((string)$row['remark']) !== '' ? trim((string)$row['remark']) : null;

            $clean[] = [
                'letter' => $letter,
                'min_score' => min($min, $max),
                'max_score' => max($min, $max),
                'grade_point' => null, // Explicitly no GPA / gradepoints
                'remark' => $remark,
            ];
        }

        // Sort descending by min_score
        usort($clean, fn($a, $b) => $b['min_score'] <=> $a['min_score']);

        return $clean;
    }
}
