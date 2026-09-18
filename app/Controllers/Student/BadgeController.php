<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Services\BadgeService;

class BadgeController extends Controller
{
    private BadgeService $badgeService;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?BadgeService $badgeService = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->badgeService = $badgeService ?? new BadgeService();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Display student achievements, honors, and earned badges.
     * Route: GET /student/badges
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $studentId = $student ? $student->id : 0;
        $earnedBadges = $studentId > 0 ? $this->badgeService->getStudentBadges($studentId) : [];
        $allBadges = $this->badgeService->getAllBadges();
        $activeSession = $this->academicRepo->findCurrentSession();

        // Map earned badge IDs for quick check
        $earnedBadgeIds = array_map(fn($sb) => (int)$sb->badgeId, $earnedBadges);

        return Response::html($this->render('student/badges/index', [
            'title' => 'My Achievements & Badges — Claret Academy',
            'headerTitle' => 'My Achievements & Honors',
            'student' => $student,
            'earnedBadges' => $earnedBadges,
            'allBadges' => $allBadges,
            'earnedBadgeIds' => $earnedBadgeIds,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/student'));
    }
}
