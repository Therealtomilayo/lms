<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Policies\ParentPolicy;
use App\Repositories\ParentRepository;
use App\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;
    private ParentRepository $parentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AnnouncementService $announcementService = null,
        ?ParentRepository $parentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
    }

    public function index(Request $request, array|string|int $params = []): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $parent = $this->parentRepo->findByUserId($user->getUserId());
        if (!$parent) {
            return Response::html('<h1>403 Forbidden</h1><p>Parent profile not found.</p>', 403);
        }

        $children = $this->parentRepo->getLinkedStudents($parent->id);
        Session::start();
        $sessionSelectedId = Session::get('_selected_child_id');

        $routeParamId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        $studentId = $routeParamId > 0 
            ? $routeParamId 
            : (int)($request->getAttribute('studentId') ?? $request->query('student_id', $sessionSelectedId ?: (!empty($children) ? $children[0]->id : 0)));

        $selectedChild = null;
        if ($studentId > 0) {
            if (!ParentPolicy::canViewAnnouncements($user, $studentId, $this->parentRepo)) {
                return Response::html('<h1>403 Forbidden</h1><p>You are not authorized to view announcements for this student.</p>', 403);
            }
            foreach ($children as $c) {
                if ($c->id === $studentId) {
                    $selectedChild = $c;
                    break;
                }
            }
            Session::set('_selected_child_id', $studentId);
        }

        $feed = $this->announcementService->getUserFeed($user, $studentId > 0 ? $studentId : null);

        return Response::html($this->render('parent/announcements/index', [
            'title' => 'School & Class Announcements — Guardian Portal',
            'children' => $children,
            'selectedChild' => $selectedChild,
            'feed' => $feed,
            'user' => $user,
            'csrf_token' => Session::get('_csrf_token', ''),
        ], 'layouts/parent'));
    }

    public function read(Request $request, array|string|int $params = []): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id <= 0) {
            $id = (int)($request->getAttribute('id') ?? $request->input('id', 0));
        }

        try {
            $this->announcementService->markAsRead($id, $user);
            if ($request->isAjax()) {
                return Response::json(['success' => true]);
            }
            return Response::redirect('/parent/announcements');
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        }
    }
}
