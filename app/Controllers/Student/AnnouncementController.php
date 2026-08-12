<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\StudentRepository;
use App\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;
    private StudentRepository $studentRepo;

    public function __construct(
        ?AnnouncementService $announcementService = null,
        ?StudentRepository $studentRepo = null
    ) {
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $student = $this->studentRepo->findByUserId($user->getUserId());
        $feed = $this->announcementService->getUserFeed($user, $student?->id);

        return Response::html($this->render('student/announcements/index', [
            'title' => 'Announcements Feed — Student Portal',
            'feed' => $feed,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/student'));
    }

    public function read(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)$request->getRouteParam('id', 0);

        try {
            $this->announcementService->markAsRead($id, $user);
            if ($request->isAjax()) {
                return Response::json(['success' => true]);
            }
            return Response::redirect('/student/announcements');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
