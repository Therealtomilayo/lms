<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\LiveClassRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\LiveClassService;

/**
 * Controller for Guardian/Parent Oversight of Ward Live Classes (SRS §31)
 */
class LiveClassController extends Controller
{
    private LiveClassService $liveClassService;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?LiveClassService $liveClassService = null,
        ?ParentRepository $parentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();

        $this->liveClassService = $liveClassService ?? new LiveClassService(
            liveClassRepository: new LiveClassRepository(),
            teacherRepository: new TeacherRepository(),
            studentRepository: new StudentRepository(),
            parentRepository: $this->parentRepo,
            academicRepository: $this->academicRepo,
            enrollmentRepository: new EnrollmentRepository()
        );
    }

    /**
     * View live classes scheduled for all linked children.
     * Route: GET /parent/live-classes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $parent = $this->parentRepo->findByUserId($userContext->id);

        if (!$parent) {
            return $this->redirectWithError('/parent/dashboard', 'Guardian profile not found.');
        }

        $currentSession = $this->academicRepo->getCurrentSession();
        $currentTerm = $this->academicRepo->getCurrentTerm();

        $result = $this->liveClassService->getParentLiveClasses(
            parentUserId: $userContext->id,
            sessionId: $currentSession?->id,
            termId: $currentTerm?->id
        );

        $childrenData = $result->isSuccess() ? $result->data : [];

        return Response::html($this->render('parent/live_classes/index', [
            'title' => 'Live Classes Schedule — Guardian Portal',
            'headerTitle' => 'Live Online Classes',
            'user' => $userContext,
            'parent' => $parent,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'childrenData' => $childrenData,
        ], 'layouts/parent'));
    }
}
