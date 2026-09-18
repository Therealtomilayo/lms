<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Services\DiscussionService;

/**
 * Screen ADMIN-35: Class Discussions Oversight & Moderation (SRS §47, §57 Phase 3)
 */
class DiscussionController extends Controller
{
    private DiscussionService $discussionService;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?DiscussionService $discussionService = null,
        ?AcademicRepository $academicRepo = null,
        ?AuthenticatorInterface $authenticator = null
    ) {
        parent::__construct($authenticator);
        $this->discussionService = $discussionService ?? new DiscussionService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Route: GET /admin/discussions
     */
    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $sessions = $this->academicRepo->getAllSessions();
        $activeSession = $this->academicRepo->findActiveSession() ?? ($sessions[0] ?? null);
        $sessionId = (int)$request->getQuery('session_id', (string)($activeSession?->id ?? 0));
        $classes = $this->academicRepo->getAllClasses();

        $selectedClassId = $request->getQuery('class_id') ? (int)$request->getQuery('class_id') : null;
        $classSubjects = $sessionId > 0 ? $this->academicRepo->getClassSubjectsBySession($sessionId, $selectedClassId) : [];

        $selectedClassSubjectId = $request->getQuery('class_subject_id') 
            ? (int)$request->getQuery('class_subject_id') 
            : ($classSubjects[0]->id ?? 0);

        $discussions = [];
        $totalCount = 0;
        $selectedClassSubject = null;

        if ($selectedClassSubjectId > 0) {
            $selectedClassSubject = $this->academicRepo->findClassSubjectById($selectedClassSubjectId);
            if ($selectedClassSubject) {
                $discussionBundle = $this->discussionService->getDiscussions($selectedClassSubjectId, $user);
                $discussions = $discussionBundle['discussions'] ?? [];
                $totalCount = (int)($discussionBundle['total_count'] ?? count($discussions));
            }
        }

        return Response::html($this->render('admin/discussions/index', [
            'title' => 'Class Discussions Oversight & Moderation — Admin Portal',
            'headerTitle' => 'Class Discussions Oversight',
            'sessions' => $sessions,
            'selectedSessionId' => $sessionId,
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'classSubjects' => $classSubjects,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedClassSubject' => $selectedClassSubject,
            'discussions' => $discussions,
            'totalCount' => $totalCount,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    /**
     * Route: GET /admin/discussions/{classSubjectId}/{discussionId}
     */
    public function show(Request $request, string|int|null $classSubjectId = null, string|int|null $discussionId = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
        $discId = (int)($discussionId ?? $request->getRouteParam('discussionId', 0));

        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            return Response::notFound('Class subject allocation not found.');
        }

        try {
            $bundle = $this->discussionService->getDiscussionThread($discId, $user);
        } catch (ResourceNotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }

        return Response::html($this->render('admin/discussions/show', [
            'title' => 'Moderate Discussion: ' . htmlspecialchars($bundle['discussion']->title),
            'headerTitle' => 'Class Discussion Moderation',
            'classSubject' => $bundle['class_subject'] ?? $classSubject,
            'discussion' => $bundle['discussion'],
            'replies' => $bundle['replies'] ?? $bundle['discussion']->replies ?? [],
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    /**
     * Route: POST /admin/discussions/{classSubjectId}/{discussionId}/replies
     */
    public function reply(Request $request, string|int|null $classSubjectId = null, string|int|null $discussionId = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
        $discId = (int)($discussionId ?? $request->getRouteParam('discussionId', 0));
        $content = (string)$request->getBodyParam('content', '');

        try {
            $this->discussionService->addReply($discId, $content, $user);
            $this->setFlash($request, 'success', 'Administrative reply posted to class discussion feed.');
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect("/admin/discussions/{$csId}/{$discId}");
    }

    /**
     * Route: POST /admin/discussions/{classSubjectId}/{discussionId}/pin
     */
    public function togglePin(Request $request, string|int|null $classSubjectId = null, string|int|null $discussionId = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
        $discId = (int)($discussionId ?? $request->getRouteParam('discussionId', 0));

        try {
            $bundle = $this->discussionService->getDiscussionThread($discId, $user);
            $newPinned = !$bundle['discussion']->isPinned;
            $this->discussionService->togglePin($discId, $newPinned, $user);
            $this->setFlash($request, 'success', $newPinned ? 'Discussion pinned to top.' : 'Discussion unpinned.');
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect("/admin/discussions/{$csId}/{$discId}");
    }

    /**
     * Route: POST /admin/discussions/{classSubjectId}/{discussionId}/lock
     */
    public function toggleLock(Request $request, string|int|null $classSubjectId = null, string|int|null $discussionId = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
        $discId = (int)($discussionId ?? $request->getRouteParam('discussionId', 0));

        try {
            $bundle = $this->discussionService->getDiscussionThread($discId, $user);
            $newLocked = !$bundle['discussion']->isLocked;
            $this->discussionService->toggleLock($discId, $newLocked, $user);
            $this->setFlash($request, 'success', $newLocked ? 'Discussion thread locked.' : 'Discussion thread unlocked.');
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect("/admin/discussions/{$csId}/{$discId}");
    }

    /**
     * Route: POST /admin/discussions/{classSubjectId}/{discussionId}/delete
     */
    public function delete(Request $request, string|int|null $classSubjectId = null, string|int|null $discussionId = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
        $discId = (int)($discussionId ?? $request->getRouteParam('discussionId', 0));

        try {
            $this->discussionService->deleteDiscussion($discId, $user);
            $this->setFlash($request, 'success', 'Discussion thread deleted by administrator.');
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect("/admin/discussions?class_subject_id={$csId}");
    }

    /**
     * Route: POST /admin/discussions/{classSubjectId}/{discussionId}/replies/{replyId}/delete
     */
    public function deleteReply(Request $request, string|int|null $classSubjectId = null, string|int|null $discussionId = null, string|int|null $replyId = null): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
        $discId = (int)($discussionId ?? $request->getRouteParam('discussionId', 0));
        $rId = (int)($replyId ?? $request->getRouteParam('replyId', 0));

        try {
            $this->discussionService->deleteReply($rId, $user);
            $this->setFlash($request, 'success', 'Inappropriate reply deleted by administrator.');
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect("/admin/discussions/{$csId}/{$discId}");
    }
}
