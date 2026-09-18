<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Services\BadgeService;

/**
 * Screen ADMIN-34: Badges & Rewards Central Management (SRS §33, §57 Phase 3)
 */
class BadgeController extends Controller
{
    private BadgeService $badgeService;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;

    public function __construct(
        ?BadgeService $badgeService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        $this->badgeService = $badgeService ?? new BadgeService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    /**
     * Route: GET /admin/badges
     */
    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $badgeFilter = $request->getQuery('badge_id') ? (int)$request->getQuery('badge_id') : null;
        $classFilter = $request->getQuery('class_id') ? (int)$request->getQuery('class_id') : null;

        $badges = $this->badgeService->getAllBadges();
        $classes = $this->academicRepo->getAllClasses();
        $awardedBadges = $this->badgeService->getAllAwardedBadges($badgeFilter, $classFilter, 100);
        $students = $this->studentRepo->getAll(limit: 200);

        return Response::html($this->render('admin/badges/index', [
            'title' => 'Badges & Rewards Management — Admin Portal',
            'headerTitle' => 'Student Honors & Badges',
            'badges' => $badges,
            'classes' => $classes,
            'awardedBadges' => $awardedBadges,
            'students' => $students,
            'selectedBadgeId' => $badgeFilter,
            'selectedClassId' => $classFilter,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    /**
     * Route: POST /admin/badges/award
     */
    public function award(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $studentId = (int)$request->getBodyParam('student_id', 0);
        $badgeId = (int)$request->getBodyParam('badge_id', 0);
        $reason = (string)$request->getBodyParam('reason', '');
        $classSubjectId = $request->getBodyParam('class_subject_id') ? (int)$request->getBodyParam('class_subject_id') : null;

        if ($studentId <= 0 || $badgeId <= 0) {
            $this->setFlash($request, 'error', 'Please select both a valid student and badge.');
            return Response::redirect('/admin/badges');
        }

        try {
            $this->badgeService->awardBadge(
                studentId: $studentId,
                badgeId: $badgeId,
                reason: $reason,
                actor: $user,
                classSubjectId: $classSubjectId
            );

            $this->setFlash($request, 'success', 'Achievement badge successfully awarded to student.');
        } catch (ValidationException $e) {
            $this->setFlash($request, 'error', implode(' ', $e->getErrors()));
        } catch (AuthorizationException $e) {
            return Response::forbidden($e->getMessage());
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect('/admin/badges');
    }

    /**
     * Route: POST /admin/badges/{id}/revoke
     */
    public function revoke(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $id = (int)$request->getRouteParam('id', 0);
        if ($id <= 0) {
            $this->setFlash($request, 'error', 'Invalid award record.');
            return Response::redirect('/admin/badges');
        }

        try {
            $this->badgeService->revokeBadge($id, $user);
            $this->setFlash($request, 'success', 'Badge award successfully revoked.');
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', $e->getMessage());
        }

        return Response::redirect('/admin/badges');
    }
}
