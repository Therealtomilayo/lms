<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\ResultPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Services\GradebookService;

/**
 * Controller for Admin Result Review, Calculation and Locking
 */
class ResultReviewController extends Controller
{
    private GradebookService $gradebookService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradebookService $gradebookService = null,
        ?GradebookRepository $gradebookRepo = null,
        ?ResultPublicationRepository $publicationRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradebookService = $gradebookService ?? new GradebookService();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->publicationRepo = $publicationRepo ?? new ResultPublicationRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $activeTerm = $this->academicRepo->getCurrentTerm();
        $selectedTermId = $termId !== null ? (int)$termId : (int)($request->get('term_id', 0) ?: ($activeTerm ? $activeTerm->id : 0));
        $selectedClassId = (int)($request->get('class_id', 0) ?: 0);

        $terms = $this->academicRepo->getAllTerms();
        $classes = $this->academicRepo->getAllClasses();

        $summaries = [];
        $isPublished = false;

        if ($selectedTermId > 0 && $selectedClassId > 0) {
            $summaries = $this->gradebookRepo->getSummariesByClassAndTerm($selectedClassId, $selectedTermId);
            $isPublished = $this->publicationRepo->isPublished($selectedTermId, $selectedClassId);
        }

        return $this->view('admin/results/review', [
            'terms' => $terms,
            'classes' => $classes,
            'selectedTermId' => $selectedTermId,
            'selectedClassId' => $selectedClassId,
            'summaries' => $summaries,
            'isPublished' => $isPublished,
        ]);
    }

    public function compute(Request $request, int|string|null $termId = null): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !ResultPolicy::canReview($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $tId = $termId !== null ? (int)$termId : (int)($request->input('term_id') ?? $request->get('term_id') ?? 0);
        $classId = (int)($request->input('class_id') ?? $request->get('class_id') ?? 0);

        $term = $this->academicRepo->findTermById($tId);
        if (!$term) {
            throw new AuthorizationException('Invalid term.');
        }

        $classSubjects = $this->academicRepo->getClassSubjectsByClass($classId);
        foreach ($classSubjects as $cs) {
            $this->gradebookService->computeClassSubjectResults(
                $cs->id,
                $term->sessionId,
                $term->id,
                (bool)$request->input('lock_results'),
                $userContext->getUserId()
            );
        }

        $this->gradebookService->computeClassTermSummaries(
            $classId,
            $term->sessionId,
            $term->id,
            (bool)$request->input('lock_results'),
            $userContext->getUserId()
        );

        return $this->redirectWithSuccess(
            "/admin/results/review?term_id={$tId}&class_id={$classId}",
            'Class term results and rankings computed successfully.'
        );
    }
}
