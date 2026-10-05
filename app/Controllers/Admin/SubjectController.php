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
 * Controller for Subjects Administration
 */
class SubjectController extends Controller
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
            return $this->forbidden('You are not authorized to manage subjects.');
        }

        $search = trim((string)$request->query('search', '')) ?: null;
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $subjects = $this->repository->getAllSubjects();

        if ($search !== null) {
            $sLower = strtolower($search);
            $subjects = array_filter($subjects, function($s) use ($sLower) {
                return str_contains(strtolower($s->name), $sLower) ||
                       str_contains(strtolower($s->code), $sLower);
            });
        }

        $subjects = array_values($subjects);
        $totalSubjects = count($subjects);
        $totalPages = max(1, (int)ceil($totalSubjects / $limit));
        $pagedSubjects = array_slice($subjects, $offset, $limit);

        return $this->view('admin/subjects/index', [
            'title' => 'Subjects — Claret LMS',
            'headerTitle' => 'Subjects',
            'subjects' => $pagedSubjects,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalResults' => $totalSubjects,
            'perPage' => $limit,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            return $this->forbidden('You are not authorized to create subjects.');
        }

        try {
            $this->structureService->createSubject([
                'name' => $request->post('name'),
                'code' => $request->post('code'),
                'status' => $request->post('status', 'active'),
            ]);

            return $this->redirectWithSuccess('/admin/subjects', 'Subject created successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/subjects', $e->getMessage());
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
            $this->structureService->updateSubject($id, [
                'name' => $request->post('name'),
                'code' => $request->post('code'),
            ]);

            return $this->redirectWithSuccess('/admin/subjects', 'Subject updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError('/admin/subjects', $e->getMessage());
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
            $this->structureService->updateSubjectStatus($id, $status);
            return $this->redirectWithSuccess('/admin/subjects', 'Subject status updated successfully.');
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/admin/subjects', $e->getMessage());
        }
    }
}
