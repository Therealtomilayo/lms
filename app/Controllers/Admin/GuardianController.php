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
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Services\GuardianService;

/**
 * Controller for Guardian (Parent-Student) Links Administration
 */
class GuardianController extends Controller
{
    private GuardianService $guardianService;
    private ParentRepository $parentRepository;
    private StudentRepository $studentRepository;

    public function __construct(
        ?GuardianService $guardianService = null,
        ?ParentRepository $parentRepository = null,
        ?StudentRepository $studentRepository = null
    ) {
        $this->guardianService = $guardianService ?? new GuardianService();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageGuardians($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $search = $request->query('q');
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $parents = $this->parentRepository->getAll($limit, $offset, $search ?: null);
        $totalParents = $this->parentRepository->countAll($search ?: null);
        $totalPages = (int)ceil($totalParents / $limit);

        $allStudents = $this->studentRepository->getAll(200);

        return $this->view('admin/guardians/index', [
            'title' => 'Guardian Links — Claret LMS',
            'headerTitle' => 'Guardian (Parent-Student) Links',
            'parents' => $parents,
            'totalParents' => $totalParents,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'allStudents' => $allStudents,
        ]);
    }

    public function link(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageGuardians($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $parentId = (int)$request->post('parent_id', 0);
        $studentId = (int)$request->post('student_id', 0);
        $relationship = $request->post('relationship_type');

        try {
            $this->guardianService->linkGuardian($parentId, $studentId, $relationship);
            return $this->redirectWithSuccess('/admin/guardians', 'Parent successfully linked to student.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/guardians', $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function unlink(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageGuardians($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $parentId = (int)$request->post('parent_id', 0);
        $studentId = (int)$request->post('student_id', 0);

        try {
            $this->guardianService->unlinkGuardian($parentId, $studentId);
            return $this->redirectWithSuccess('/admin/guardians', 'Parent-student link removed successfully.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError('/admin/guardians', $e->getMessage());
        }
    }
}
