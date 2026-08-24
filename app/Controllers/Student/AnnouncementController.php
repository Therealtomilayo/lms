<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\StudentRepository;
use App\Services\AnnouncementService;

/**
 * Controller for Student Campus Bulletins and Announcements Feed
 */
class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;
    private StudentRepository $studentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AnnouncementService $announcementService = null,
        ?StudentRepository $studentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    /**
     * Route: GET /student/announcements
     */
    public function index(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($user->id);

        if (!$student && !$user->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $studentId = $student ? $student->id : ($user->getStudentId() ?: 0);
        $feed = $this->announcementService->getUserFeed($user, $studentId);

        return Response::html($this->render('student/announcements/index', [
            'title' => 'Announcements & Bulletins — Student Portal',
            'headerTitle' => 'Campus Announcements',
            'user' => $user,
            'student' => $student,
            'feed' => $feed,
        ], 'layouts/student'));
    }

    /**
     * Route: POST /student/announcements/{id}/read
     */
    public function read(Request $request, array|string|int $id): Response
    {
        $user = $this->requireAuthContext($request);
        $announcementId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $this->announcementService->markAsRead($announcementId, $user);
            if ($request->isAjax()) {
                return Response::json(['success' => true]);
            }
            return $this->redirectWithSuccess('/student/announcements', 'Notice marked as read.');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
