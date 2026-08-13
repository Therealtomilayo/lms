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
 * Controller for Classes and Arms Administration
 */
class ClassController extends Controller
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
            return $this->forbidden('You are not authorized to manage classes.');
        }

        $classes = $this->repository->getAllClasses();
        $levels = $this->repository->getAllLevels();

        return $this->view('admin/classes/index', [
            'title' => 'Classes & Arms — Claret LMS',
            'headerTitle' => 'Classes & Arms',
            'classes' => $classes,
            'levels' => $levels,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to create classes.');
        }

        try {
            $this->structureService->createClass([
                'academic_level_id' => $request->post('academic_level_id'),
                'name' => $request->post('name'),
                'section_arm' => $request->post('section_arm'),
                'status' => $request->post('status', 'active'),
            ]);

            return $this->redirectWithSuccess('/admin/classes', 'Class created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/classes', $e->getMessage());
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
            $this->structureService->updateClass($id, [
                'academic_level_id' => $request->post('academic_level_id'),
                'name' => $request->post('name'),
                'section_arm' => $request->post('section_arm'),
            ]);

            return $this->redirectWithSuccess('/admin/classes', 'Class updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError('/admin/classes', $e->getMessage());
        }
    }

    public function status(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return Response::html('Forbidden', 403);
        }

        $id = (int)($id ?: $request->post('id', 0));
        $status = (string)$request->post('status', 'active');

        try {
            $this->structureService->updateClassStatus($id, $status);
            return $this->redirectWithSuccess('/admin/classes', 'Class status updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/admin/classes', $e->getMessage());
        }
    }
}
