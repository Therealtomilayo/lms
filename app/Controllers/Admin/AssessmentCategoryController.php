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
use App\Repositories\GradebookRepository;

/**
 * Controller for Admin Assessment Category and Weight Configuration
 */
class AssessmentCategoryController extends Controller
{
    private GradebookRepository $gradebookRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradebookRepository $gradebookRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageCategories($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $sessions = $this->academicRepo->getAllSessions();
        $terms = $this->academicRepo->getAllTerms();
        $activeSession = $this->academicRepo->getCurrentSession();
        $activeTerm = $this->academicRepo->getCurrentTerm();

        $selectedSessionId = (int)($request->get('session_id') ?? ($activeSession ? $activeSession->id : 0));
        $selectedTermId = (int)($request->get('term_id') ?? ($activeTerm ? $activeTerm->id : 0));

        $categories = [];
        if ($selectedSessionId > 0 && $selectedTermId > 0) {
            $categories = $this->gradebookRepo->getCategoriesByContext($selectedSessionId, $selectedTermId);
        }

        return $this->view('admin/assessment_categories/index', [
            'sessions' => $sessions,
            'terms' => $terms,
            'selectedSessionId' => $selectedSessionId,
            'selectedTermId' => $selectedTermId,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageCategories($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $sessionId = (int)$request->input('session_id');
        $termId = (int)$request->input('term_id');

        $this->gradebookRepo->createCategory([
            'session_id' => $sessionId,
            'term_id' => $termId,
            'class_subject_id' => $request->input('class_subject_id'),
            'name' => (string)$request->input('name'),
            'weight_percentage' => (float)$request->input('weight_percentage'),
            'max_points' => (float)($request->input('max_points') ?? 100.0),
        ]);

        return $this->redirectWithSuccess(
            "/admin/assessment-categories?session_id={$sessionId}&term_id={$termId}",
            'Assessment category added successfully.'
        );
    }

    public function delete(Request $request, int|string $id): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !GradebookPolicy::canManageCategories($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $catId = (int)$id;
        $cat = $this->gradebookRepo->findCategoryById($catId);
        $sessionId = $cat ? $cat->sessionId : 0;
        $termId = $cat ? $cat->termId : 0;

        $this->gradebookRepo->deleteCategory($catId);

        return $this->redirectWithSuccess(
            "/admin/assessment-categories?session_id={$sessionId}&term_id={$termId}",
            'Assessment category deleted successfully.'
        );
    }
}
