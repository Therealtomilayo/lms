<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Repositories\AnnouncementRepository;
use App\Repositories\ParentRepository;
use App\Repositories\UserRepository;

/**
 * Unified Notifications & Bulletins Controller (AUTH-06)
 * Accessible to all authenticated roles (Admin, Teacher, Student, Parent)
 */
class NotificationController extends Controller
{
    private AnnouncementRepository $announcementRepo;
    private UserRepository $userRepo;
    private ParentRepository $parentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AnnouncementRepository $announcementRepo = null,
        ?UserRepository $userRepo = null,
        ?ParentRepository $parentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
    }

    /**
     * Show notifications index
     * Route: GET /notifications
     */
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        $userModel = $this->userRepo->findById($user->id) ?? $user;
        $userContext = UserContext::fromUser($userModel);
        $roles = $userModel->roles ?? [];

        // Determine layout shell based on primary role
        $layoutName = 'layouts/app';
        if (in_array('super_admin', $roles, true) || in_array('admin', $roles, true)) {
            $layoutName = 'layouts/admin';
        } elseif (in_array('teacher', $roles, true)) {
            $layoutName = 'layouts/teacher';
        } elseif (in_array('student', $roles, true)) {
            $layoutName = 'layouts/student';
        } elseif (in_array('parent', $roles, true)) {
            $layoutName = 'layouts/parent';
        }

        // For parent role, check linked children and active ward filter
        $linkedChildren = [];
        $activeChild = null;
        $activeChildId = null;

        if (in_array('parent', $roles, true)) {
            $parent = $this->parentRepo->findByUserId($user->id);
            if ($parent) {
                $linkedChildren = $this->parentRepo->getLinkedStudents((int)$parent->id);
                if (!empty($linkedChildren)) {
                    Session::start();
                    $sessionSelectedId = (int)Session::get('_selected_child_id', 0);
                    $requestedChildId = (int)$request->query('child_id', 0);

                    $targetId = $requestedChildId > 0 ? $requestedChildId : ($sessionSelectedId > 0 ? $sessionSelectedId : (int)$linkedChildren[0]->id);
                    foreach ($linkedChildren as $child) {
                        if ((int)$child->id === $targetId) {
                            $activeChild = $child;
                            $activeChildId = (int)$child->id;
                            break;
                        }
                    }
                    if (!$activeChild) {
                        $activeChild = $linkedChildren[0];
                        $activeChildId = (int)$activeChild->id;
                    }
                }
            }
        }

        // Retrieve feed and counts
        $feed = $this->announcementRepo->getFeedForUser($userContext, $activeChildId, 100, 0);
        $unreadCount = $this->announcementRepo->getUnreadCount($userContext, $activeChildId);

        return Response::html($this->render('notifications/index', [
            'title' => 'Notifications & Bulletins — Claret LMS',
            'user' => $userModel,
            'userContext' => $userContext,
            'roles' => $roles,
            'feed' => $feed,
            'unreadCount' => $unreadCount,
            'linkedChildren' => $linkedChildren,
            'activeChild' => $activeChild,
            'activeChildId' => $activeChildId,
            'children' => $linkedChildren,
            'selectedChild' => $activeChild,
        ], $layoutName));
    }

    /**
     * Mark single notification as read
     * Route: POST /notifications/{id}/read
     */
    public function markAsRead(Request $request, array|string|int $params = []): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id <= 0) {
            $id = (int)$request->getAttribute('id', 0);
        }

        if ($id > 0) {
            $this->announcementRepo->markAsRead($id, $user->id);
            $this->setFlash($request, 'success', 'Notification marked as read.');
        }

        $redirectTo = (string)($request->input('redirect_to') ?? '/notifications');
        if (!str_starts_with($redirectTo, '/notifications') && !str_starts_with($redirectTo, '/parent') && !str_starts_with($redirectTo, '/teacher') && !str_starts_with($redirectTo, '/student') && !str_starts_with($redirectTo, '/admin')) {
            $redirectTo = '/notifications';
        }

        return Response::redirect($redirectTo);
    }

    /**
     * Mark all notifications as read for current user
     * Route: POST /notifications/read-all
     */
    public function markAllAsRead(Request $request): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        $userModel = $this->userRepo->findById($user->id) ?? $user;
        $userContext = UserContext::fromUser($userModel);

        $childId = (int)$request->input('child_id', 0);
        $count = $this->announcementRepo->markAllAsReadForUser($userContext, $childId > 0 ? $childId : null);

        $this->setFlash($request, 'success', $count > 0 ? "Marked {$count} notifications as read." : "All notifications are already read.");
        
        $redirectTo = (string)($request->input('redirect_to') ?? '/notifications');
        if (!str_starts_with($redirectTo, '/notifications')) {
            $redirectTo = '/notifications';
        }

        return Response::redirect($redirectTo);
    }
}
