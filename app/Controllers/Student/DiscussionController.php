<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Services\DiscussionService;

class DiscussionController extends Controller
{
    private DiscussionService $discussionService;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?DiscussionService $discussionService = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->discussionService = $discussionService ?? new DiscussionService();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Display discussion topics for an enrolled class subject.
     * Route: GET /student/subjects/{classSubjectId}/discussions
     */
    public function index(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $csId = is_array($classSubjectId)
            ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0)
            : (int)$classSubjectId;

        if ($csId <= 0) {
            return Response::notFound('Invalid class subject ID.');
        }

        try {
            $data = $this->discussionService->getDiscussions($csId, $userContext);
            $activeSession = $this->academicRepo->findCurrentSession();

            return Response::html($this->render('student/discussions/index', [
                'title' => 'Class Discussions — ' . ($data['class_subject']->subject->name ?? 'Subject'),
                'headerTitle' => 'Class Discussions',
                'classSubject' => $data['class_subject'],
                'discussions' => $data['discussions'],
                'totalCount' => $data['total_count'],
                'canPost' => $data['can_post'],
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/student'));
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Throwable $e) {
            error_log("Student discussion index error: " . $e->getMessage());
            return $this->view('errors/500', ['message' => 'Unable to load class discussions.'], 500);
        }
    }

    /**
     * Show single discussion topic and replies thread.
     * Route: GET /student/subjects/{classSubjectId}/discussions/{discussionId}
     */
    public function show(Request $request, array|string|int|null $classSubjectId = null, array|string|int|null $discussionId = null): Response
    {
        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)($classSubjectId ?? $request->getRouteParam('classSubjectId', 0));
            $discId = is_array($discussionId)
                ? (int)($discussionId['discussionId'] ?? $discussionId['id'] ?? 0)
                : (int)($discussionId ?? $request->getRouteParam('discussionId', 0));
        }

        if ($csId <= 0 || $discId <= 0) {
            return Response::notFound('Discussion topic not found.');
        }

        $userContext = $this->requireAuthContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        try {
            $thread = $this->discussionService->getDiscussionThread($discId, $userContext);
            $classSubject = $thread['class_subject'] ?? $this->academicRepo->findClassSubjectById($csId);
            $activeSession = $this->academicRepo->findCurrentSession();

            return Response::html($this->render('student/discussions/show', [
                'title' => ($thread['discussion']->title ?? 'Discussion') . ' — Student Learning Portal',
                'headerTitle' => 'Class Discussion',
                'discussion' => $thread['discussion'],
                'thread' => $thread,
                'classSubject' => $classSubject,
                'canReply' => $thread['can_reply'] ?? true,
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/student'));
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Throwable $e) {
            error_log("Student discussion show error: " . $e->getMessage());
            return Response::html($this->render('errors/500', ['message' => 'Unable to load discussion thread.']), 500);
        }
    }

    /**
     * Create a new discussion topic in a class subject.
     * Route: POST /student/subjects/{classSubjectId}/discussions
     */
    public function store(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $csId = is_array($classSubjectId)
            ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0)
            : (int)$classSubjectId;

        $title = (string)$request->post('title', '');
        $content = (string)$request->post('content', '');
        $redirectUrl = "/student/subjects/{$csId}/discussions";

        try {
            $newId = $this->discussionService->createDiscussion($csId, $title, $content, $userContext);
            return Response::redirect("/student/subjects/{$csId}/discussions/{$newId}?success=" . urlencode('Discussion question posted.'));
        } catch (ValidationException $e) {
            $firstError = current($e->getErrors());
            $msg = is_array($firstError) ? current($firstError) : (string)$firstError;
            return Response::redirect("{$redirectUrl}?error=" . urlencode($msg));
        } catch (AuthorizationException $e) {
            return Response::redirect("{$redirectUrl}?error=" . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return Response::redirect("{$redirectUrl}?error=" . urlencode('Unable to create discussion topic.'));
        }
    }

    /**
     * Reply to a discussion topic.
     * Route: POST /student/subjects/{classSubjectId}/discussions/{discussionId}/replies
     */
    public function reply(Request $request, array|string|int $classSubjectId, array|string|int $discussionId = 0): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $discId = (int)($classSubjectId['discussionId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $discId = is_array($discussionId) ? (int)($discussionId['discussionId'] ?? 0) : (int)$discussionId;
        }

        $content = (string)$request->post('content', '');
        $redirectUrl = "/student/subjects/{$csId}/discussions/{$discId}";

        try {
            $this->discussionService->addReply($discId, $content, $userContext);
            return Response::redirect("{$redirectUrl}?success=" . urlencode('Reply posted.'));
        } catch (ValidationException $e) {
            $firstError = current($e->getErrors());
            $msg = is_array($firstError) ? current($firstError) : (string)$firstError;
            return Response::redirect("{$redirectUrl}?error=" . urlencode($msg));
        } catch (DomainRuleException | AuthorizationException $e) {
            return Response::redirect("{$redirectUrl}?error=" . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return Response::redirect("{$redirectUrl}?error=" . urlencode('Unable to post reply.'));
        }
    }
}
