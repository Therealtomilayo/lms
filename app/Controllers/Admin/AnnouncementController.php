<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    private AnnouncementService $announcementService;
    private AnnouncementRepository $announcementRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AnnouncementService $announcementService = null,
        ?AnnouncementRepository $announcementRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->announcementService = $announcementService ?? new AnnouncementService();
        $this->announcementRepo = $announcementRepo ?? new AnnouncementRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $announcements = $this->announcementRepo->listAllForAdmin();

        return Response::html($this->render('admin/announcements/index', [
            'title' => 'Broadcast Announcements — Admin Portal',
            'headerTitle' => 'Announcements Broadcast Hub',
            'announcements' => $announcements,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    public function create(Request $request): Response
    {
        $classes = $this->academicRepo->getAllClasses();
        $currentSession = $this->academicRepo->getCurrentSession();
        $classSubjects = $currentSession ? $this->academicRepo->getClassSubjectsBySession($currentSession->id) : [];

        return Response::html($this->render('admin/announcements/create', [
            'title' => 'New Announcement — Admin Portal',
            'headerTitle' => 'Broadcast New Announcement',
            'classes' => $classes,
            'classSubjects' => $classSubjects,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    public function store(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $title = (string)$request->getBodyParam('title', '');
        $body = (string)$request->getBodyParam('body', '');
        $scope = (string)$request->getBodyParam('scope', 'school');
        $scopeId = $request->getBodyParam('scope_id') ? (int)$request->getBodyParam('scope_id') : null;
        $publishedAt = $request->getBodyParam('published_at') ? (string)$request->getBodyParam('published_at') : date('Y-m-d H:i:s');
        $expiresAt = $request->getBodyParam('expires_at') ? (string)$request->getBodyParam('expires_at') : null;

        try {
            $this->announcementService->createAnnouncement([
                'title' => $title,
                'body' => $body,
                'scope' => $scope,
                'scope_id' => $scopeId,
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
            ], $user);

            $this->setFlash($request, 'success', 'Announcement broadcasted successfully.');
            return Response::redirect('/admin/announcements');
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
            return Response::redirect('/admin/announcements/create');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }

    public function edit(Request $request, string|int $id = 0): Response
    {
        $id = (int)($id ?: $request->getRouteParam('id', 0));
        $announcement = $this->announcementRepo->findById($id);
        if (!$announcement) {
            return Response::notFound('Announcement not found.');
        }

        $classes = $this->academicRepo->getAllClasses();
        $currentSession = $this->academicRepo->getCurrentSession();
        $classSubjects = $currentSession ? $this->academicRepo->getClassSubjectsBySession($currentSession->id) : [];

        return Response::html($this->render('admin/announcements/edit', [
            'title' => "Edit Announcement: {$announcement->title}",
            'headerTitle' => 'Edit Announcement',
            'announcement' => $announcement,
            'classes' => $classes,
            'classSubjects' => $classSubjects,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    public function update(Request $request, string|int $id = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)($id ?: $request->getRouteParam('id', 0));
        $title = (string)$request->getBodyParam('title', '');
        $body = (string)$request->getBodyParam('body', '');
        $scope = (string)$request->getBodyParam('scope', 'school');
        $scopeId = $request->getBodyParam('scope_id') ? (int)$request->getBodyParam('scope_id') : null;
        $publishedAt = $request->getBodyParam('published_at') ? (string)$request->getBodyParam('published_at') : null;
        $expiresAt = $request->getBodyParam('expires_at') ? (string)$request->getBodyParam('expires_at') : null;

        try {
            $this->announcementService->updateAnnouncement($id, [
                'title' => $title,
                'body' => $body,
                'scope' => $scope,
                'scope_id' => $scopeId,
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
            ], $user);

            $this->setFlash($request, 'success', 'Announcement updated successfully.');
            return Response::redirect('/admin/announcements');
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
            return Response::redirect("/admin/announcements/{$id}/edit");
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }

    public function delete(Request $request, string|int $id = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)($id ?: $request->getRouteParam('id', 0));

        try {
            $this->announcementService->deleteAnnouncement($id, $user);
            $this->setFlash($request, 'success', 'Announcement deleted successfully.');
            return Response::redirect('/admin/announcements');
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
