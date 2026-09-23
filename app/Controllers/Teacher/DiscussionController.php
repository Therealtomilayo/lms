<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\DiscussionService;

class DiscussionController extends Controller
{
    private DiscussionService $discussionService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?DiscussionService $discussionService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        parent::__construct($authenticator);
        $this->discussionService = $discussionService ?? new DiscussionService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    /**
     * Overview of all class discussions for the teacher's assigned subjects.
     * Route: GET /teacher/discussions
     */
    public function hub(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        if (!$teacher) {
            return Response::redirect('/login');
        }

        $classSubjects = $this->academicRepo->findClassSubjectsByTeacherId($teacher->id);
        if (count($classSubjects) === 1) {
            return Response::redirect("/teacher/subjects/{$classSubjects[0]->id}/discussions");
        }

        $activeSession = $this->academicRepo->findCurrentSession();

        return Response::html($this->render('teacher/discussions/hub', [
            'title' => 'Class Discussions Overview — Faculty Portal',
            'headerTitle' => 'Class Discussions',
            'classSubjects' => $classSubjects,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Display discussion topics for a class subject.
     * Route: GET /teacher/subjects/{classSubjectId}/discussions
     */
    public function index(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $csId = is_array($classSubjectId)
            ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0)
            : (int)$classSubjectId;

        if ($csId <= 0) {
            return $this->view('errors/404', ['message' => 'Invalid class subject ID provided.'], 404);
        }

        try {
            $data = $this->discussionService->getDiscussions($csId, $userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('teacher/discussions/index', [
                'title' => 'Class Discussions — Claret Faculty Portal',
                'headerTitle' => 'Class Group Discussions',
                'classSubject' => $data['class_subject'],
                'discussions' => $data['discussions'],
                'totalCount' => $data['total_count'],
                'canModerate' => $data['can_moderate'],
                'canPost' => $data['can_post'],
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/teacher'));
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            error_log("Error loading class discussions: " . $e->getMessage());
            return $this->view('errors/500', ['message' => 'Unable to load class discussions.'], 500);
        }
    }

    /**
     * Show single discussion topic and replies thread.
     * Route: GET /teacher/subjects/{classSubjectId}/discussions/{discussionId}
     */
    public function show(Request $request, array|string|int $classSubjectId, array|string|int $discussionId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $discId = is_array($discussionId) ? (int)($discussionId['discussionId'] ?? 0) : (int)$discussionId;
        }

        if ($csId <= 0 || $discId <= 0) {
            return $this->view('errors/404', ['message' => 'Invalid parameters provided.'], 404);
        }

        try {
            $data = $this->discussionService->getDiscussionThread($discId, $userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('teacher/discussions/show', [
                'title' => $data['discussion']->title . ' — Claret Faculty Portal',
                'headerTitle' => 'Discussion Topic Thread',
                'discussion' => $data['discussion'],
                'classSubject' => $data['class_subject'],
                'canModerate' => $data['can_moderate'],
                'canReply' => $data['can_reply'],
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/teacher'));
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            error_log("Error viewing discussion thread: " . $e->getMessage());
            return $this->view('errors/500', ['message' => 'Unable to load discussion thread.'], 500);
        }
    }

    /**
     * Create a new discussion topic.
     * Route: POST /teacher/subjects/{classSubjectId}/discussions
     */
    public function store(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $csId = is_array($classSubjectId)
            ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0)
            : (int)$classSubjectId;

        $title = (string)$request->post('title', '');
        $content = (string)$request->post('content', '');
        $redirectUrl = "/teacher/subjects/{$csId}/discussions";

        try {
            $newId = $this->discussionService->createDiscussion($csId, $title, $content, $userContext);
            return $this->redirectWithSuccess("/teacher/subjects/{$csId}/discussions/{$newId}", 'Discussion topic created successfully.');
        } catch (ValidationException $e) {
            $firstError = current($e->getErrors());
            $message = is_array($firstError) ? current($firstError) : (string)$firstError;
            return $this->redirectWithError($redirectUrl, $message);
        } catch (AuthorizationException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Error creating discussion: " . $e->getMessage());
            return $this->redirectWithError($redirectUrl, 'Unable to create discussion topic.');
        }
    }

    /**
     * Reply to a discussion topic.
     * Route: POST /teacher/subjects/{classSubjectId}/discussions/{discussionId}/replies
     */
    public function reply(Request $request, array|string|int $classSubjectId, array|string|int $discussionId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $discId = is_array($discussionId) ? (int)($discussionId['discussionId'] ?? 0) : (int)$discussionId;
        }

        $content = (string)$request->post('content', '');
        $redirectUrl = "/teacher/subjects/{$csId}/discussions/{$discId}";

        try {
            $this->discussionService->addReply($discId, $content, $userContext);
            return $this->redirectWithSuccess($redirectUrl, 'Reply posted successfully.');
        } catch (ValidationException $e) {
            $firstError = current($e->getErrors());
            $message = is_array($firstError) ? current($firstError) : (string)$firstError;
            return $this->redirectWithError($redirectUrl, $message);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Error posting reply: " . $e->getMessage());
            return $this->redirectWithError($redirectUrl, 'Unable to post reply.');
        }
    }

    /**
     * Toggle pinned state.
     * Route: POST /teacher/subjects/{classSubjectId}/discussions/{discussionId}/pin
     */
    public function togglePin(Request $request, array|string|int $classSubjectId, array|string|int $discussionId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $discId = is_array($discussionId) ? (int)($discussionId['discussionId'] ?? 0) : (int)$discussionId;
        }

        $isPinned = (bool)$request->post('is_pinned', false);
        $redirectUrl = "/teacher/subjects/{$csId}/discussions/{$discId}";

        try {
            $this->discussionService->togglePin($discId, $isPinned, $userContext);
            return $this->redirectWithSuccess($redirectUrl, $isPinned ? 'Discussion topic pinned.' : 'Discussion topic unpinned.');
        } catch (\Throwable $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        }
    }

    /**
     * Toggle locked state.
     * Route: POST /teacher/subjects/{classSubjectId}/discussions/{discussionId}/lock
     */
    public function toggleLock(Request $request, array|string|int $classSubjectId, array|string|int $discussionId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $discId = is_array($discussionId) ? (int)($discussionId['discussionId'] ?? 0) : (int)$discussionId;
        }

        $isLocked = (bool)$request->post('is_locked', false);
        $redirectUrl = "/teacher/subjects/{$csId}/discussions/{$discId}";

        try {
            $this->discussionService->toggleLock($discId, $isLocked, $userContext);
            return $this->redirectWithSuccess($redirectUrl, $isLocked ? 'Discussion topic locked.' : 'Discussion topic unlocked.');
        } catch (\Throwable $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        }
    }

    /**
     * Delete discussion topic.
     * Route: POST /teacher/subjects/{classSubjectId}/discussions/{discussionId}/delete
     */
    public function delete(Request $request, array|string|int $classSubjectId, array|string|int $discussionId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $discId = is_array($discussionId) ? (int)($discussionId['discussionId'] ?? 0) : (int)$discussionId;
        }

        $redirectUrl = "/teacher/subjects/{$csId}/discussions";

        try {
            $this->discussionService->deleteDiscussion($discId, $userContext);
            return $this->redirectWithSuccess($redirectUrl, 'Discussion topic removed.');
        } catch (\Throwable $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        }
    }
}
