<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\TeacherRepository;
use App\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;
    private AnnouncementRepository $announcementRepo;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AnnouncementService $announcementService = null,
        ?AnnouncementRepository $announcementRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $feed = $this->announcementService->getUserFeed($user);
        $myAnnouncements = $this->announcementRepo->listByAuthor($user->getUserId());

        return Response::html($this->render('teacher/announcements/index', [
            'title' => 'Announcements — Teacher Portal',
            'feed' => $feed,
            'myAnnouncements' => $myAnnouncements,
        ], 'layouts/teacher'));
    }

    public function create(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $teacher = $this->teacherRepo->findByUserId($user->getUserId());
        if (!$teacher) {
            return Response::forbidden('Teacher profile not found.');
        }

        $currentSession = $this->academicRepo->getCurrentSession();
        $allocations = $currentSession ? $this->teacherRepo->getTeachingAllocations($teacher->id, $currentSession->id) : [];

        // Collect distinct classes
        $classes = [];
        foreach ($allocations as $alloc) {
            $classes[$alloc['class_id']] = [
                'id' => $alloc['class_id'],
                'name' => $alloc['class_name'],
            ];
        }

        return Response::html($this->render('teacher/announcements/create', [
            'title' => 'Post Announcement — Teacher Portal',
            'classes' => array_values($classes),
            'allocations' => $allocations,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/teacher'));
    }

    public function store(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $title = (string)$request->getBodyParam('title', '');
        $body = (string)$request->getBodyParam('body', '');
        $scope = (string)$request->getBodyParam('scope', 'class');
        $scopeId = $request->getBodyParam('scope_id') ? (int)$request->getBodyParam('scope_id') : null;
        $expiresAt = $request->getBodyParam('expires_at') ? (string)$request->getBodyParam('expires_at') : null;

        try {
            $this->announcementService->createAnnouncement([
                'title' => $title,
                'body' => $body,
                'scope' => $scope,
                'scope_id' => $scopeId,
                'expires_at' => $expiresAt,
            ], $user);

            $this->setFlash($request, 'success', 'Announcement published successfully.');
            return Response::redirect('/teacher/announcements');
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
            return Response::redirect('/teacher/announcements/create');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
