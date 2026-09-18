<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AcademicRepository;
use App\Services\BadgeService;
use App\Services\DiscussionService;
use App\Services\ParentService;

/**
 * Controller for Child Switching and Detailed Single-Child Academic Overview
 */
class ChildController extends Controller
{
    private ParentService $parentService;
    private BadgeService $badgeService;
    private DiscussionService $discussionService;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ParentService $parentService = null,
        ?BadgeService $badgeService = null,
        ?DiscussionService $discussionService = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->parentService = $parentService ?? new ParentService();
        $this->badgeService = $badgeService ?? new BadgeService();
        $this->discussionService = $discussionService ?? new DiscussionService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Set active selected child in session after validating ownership.
     * Route: POST /parent/children/{studentId}/select
     */
    public function select(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->input('student_id', 0));
        }

        try {
            // Strictly validate ownership before storing in session
            $student = $this->parentService->validateChildAccess($userContext, $studentId);

            Session::start();
            Session::set('_selected_child_id', $student->id);

            $redirectUrl = (string)($request->input('redirect_to') ?? "/parent/children/{$student->id}");
            if (!str_starts_with($redirectUrl, '/parent')) {
                $redirectUrl = "/parent/children/{$student->id}";
            } else {
                // If redirecting from another child's scoped subpage (e.g. /parent/children/4/timetable),
                // dynamically rewrite the student ID segment to the newly selected child
                $redirectUrl = preg_replace('#^/parent/children/\d+#', "/parent/children/{$student->id}", $redirectUrl);
            }

            $this->setFlash($request, 'success', "Switched active student view to {$student->name}.");
            return Response::redirect($redirectUrl);
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * Show detailed academic overview and status for a linked child.
     * Route: GET /parent/children/{studentId}
     */
    public function show(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            // Re-validate parent-child access predicate
            $overviewData = $this->parentService->getChildOverview($userContext, $studentId);

            Session::start();
            Session::set('_selected_child_id', $studentId);

            $children = $this->parentService->getLinkedChildren($userContext);

            return Response::html($this->render('parent/children/show', array_merge($overviewData, [
                'title' => "{$overviewData['student']->name} — Student Profile & Academic Overview",
                'children' => $children,
                'selectedChild' => $overviewData['student'],
                'user' => $userContext,
                'csrf_token' => Session::get('_csrf_token', ''),
            ]), 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * View all achievement badges earned by a linked child.
     * Route: GET /parent/children/{studentId}/badges
     */
    public function badges(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            $student = $this->parentService->validateChildAccess($userContext, $studentId);
            $earnedBadges = $this->badgeService->getStudentBadges($studentId);
            $children = $this->parentService->getLinkedChildren($userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('parent/children/badges', [
                'title' => "{$student->name} — Achievements & Badges",
                'student' => $student,
                'selectedChild' => $student,
                'children' => $children,
                'earnedBadges' => $earnedBadges,
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * Read-only view of a child's class discussion feeds.
     * Route: GET /parent/children/{studentId}/discussions
     */
    public function discussions(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            $student = $this->parentService->validateChildAccess($userContext, $studentId);
            $subjects = $this->parentService->getChildSubjectEnrollments($userContext, $studentId);

            $selectedCsId = (int)$request->query('class_subject_id', 0);
            if ($selectedCsId <= 0 && !empty($subjects)) {
                $selectedCsId = (int)$subjects[0]->classSubjectId;
            }

            $discussionData = null;
            if ($selectedCsId > 0) {
                try {
                    $discussionData = $this->discussionService->getDiscussions($selectedCsId, $userContext);
                } catch (\Throwable $t) {
                    error_log('Parent child discussions retrieval error: ' . $t->getMessage());
                    $discussionData = null;
                }
            }

            $children = $this->parentService->getLinkedChildren($userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('parent/children/discussions', [
                'title' => "{$student->name} — Class Discussions Feed",
                'student' => $student,
                'selectedChild' => $student,
                'children' => $children,
                'subjects' => $subjects,
                'selectedClassSubjectId' => $selectedCsId,
                'discussionData' => $discussionData,
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }
}
