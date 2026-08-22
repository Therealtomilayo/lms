<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\TeacherRepository;
use App\Services\AnnouncementService;

/**
 * Controller for Teacher Targeted Bulletins & Broadcasts
 */
class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;
    private AnnouncementRepository $announcementRepo;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AnnouncementService $announcementService = null,
        ?AnnouncementRepository $announcementRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        parent::__construct($authenticator);
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    /**
     * View announcements feed and personal broadcasts.
     * Route: GET /teacher/announcements
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $feed = $this->announcementService->getUserFeed($userContext);
        $myAnnouncements = $this->announcementRepo->listByAuthor($userContext->id);

        return Response::html($this->render('teacher/announcements/index', [
            'title' => 'Announcements & School Bulletins — Faculty Portal',
            'headerTitle' => 'Announcements & Bulletins',
            'user' => $userContext,
            'feed' => $feed,
            'myAnnouncements' => $myAnnouncements,
        ], 'layouts/teacher'));
    }

    /**
     * Show announcement creation form.
     * Route: GET /teacher/announcements/create
     */
    public function create(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);

        if (!$teacher && !$userContext->isAdmin()) {
            return Response::forbidden('Teacher profile not found.');
        }

        $teacherId = $teacher ? $teacher->id : 0;
        $currentSession = $this->academicRepo->findCurrentSession();
        $allocations = ($currentSession && $teacherId > 0)
            ? $this->teacherRepo->getTeachingAllocations($teacherId, $currentSession->id)
            : [];

        // Collect distinct classes
        $classes = [];
        foreach ($allocations as $alloc) {
            $classes[$alloc['class_id']] = [
                'id' => $alloc['class_id'],
                'name' => $alloc['class_name'],
            ];
        }

        return Response::html($this->render('teacher/announcements/create', [
            'title' => 'Post Announcement — Faculty Portal',
            'headerTitle' => 'Compose Class Announcement',
            'user' => $userContext,
            'classes' => array_values($classes),
            'allocations' => $allocations,
            'errors' => $request->getSession()?->getFlash('errors') ?? [],
            'old' => $request->getSession()?->getFlash('old') ?? [],
        ], 'layouts/teacher'));
    }

    /**
     * Publish a new targeted announcement.
     * Route: POST /teacher/announcements/create
     */
    public function store(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $data = $request->all();

        $title = (string)($data['title'] ?? '');
        $body = (string)($data['body'] ?? '');
        $scope = (string)($data['scope'] ?? 'class');
        $scopeId = !empty($data['scope_id']) ? (int)$data['scope_id'] : null;
        $expiresAt = !empty($data['expires_at']) ? (string)$data['expires_at'] : null;

        try {
            $this->announcementService->createAnnouncement([
                'title' => $title,
                'body' => $body,
                'scope' => $scope,
                'scope_id' => $scopeId,
                'expires_at' => $expiresAt,
            ], $userContext);

            return $this->redirectWithSuccess('/teacher/announcements', 'Announcement published successfully.');
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/teacher/announcements/create', $e->getErrors(), $data);
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
