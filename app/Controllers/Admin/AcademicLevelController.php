<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Services\AcademicStructureService;

/**
 * Controller for Academic Level Administration
 */
class AcademicLevelController extends Controller
{
    private AcademicStructureService $structureService;
    private AcademicRepository $repository;

    public function __construct(
        ?AcademicStructureService $structureService = null,
        ?AcademicRepository $repository = null
    ) {
        $this->structureService = $structureService ?? new AcademicStructureService();
        $this->repository = $repository ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to manage academic levels.');
        }

        $levels = $this->repository->getAllLevels();
        $gradingScales = $this->repository->getAllGradingScales();

        return $this->view('admin/academic_levels/index', [
            'title' => 'Academic Levels — Claret LMS',
            'headerTitle' => 'Academic Levels',
            'levels' => $levels,
            'gradingScales' => $gradingScales,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to create academic levels.');
        }

        try {
            $this->structureService->createLevel([
                'name' => $request->post('name'),
                'stage' => $request->post('stage'),
                'rank_order' => $request->post('rank_order', 0),
                'grading_scale_id' => $request->post('grading_scale_id') ?: null,
            ]);

            return $this->redirectWithSuccess('/admin/academic-levels', 'Academic level created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/academic-levels', $e->getMessage());
        }
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)$id;

        try {
            $this->structureService->updateLevel($id, [
                'name' => $request->post('name'),
                'stage' => $request->post('stage'),
                'rank_order' => $request->post('rank_order', 0),
                'grading_scale_id' => $request->post('grading_scale_id') ?: null,
            ]);

            return $this->redirectWithSuccess('/admin/academic-levels', 'Academic level updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError('/admin/academic-levels', $e->getMessage());
        }
    }
}
