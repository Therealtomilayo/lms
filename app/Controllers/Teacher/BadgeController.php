<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\BadgeService;

class BadgeController extends Controller
{
    private BadgeService $badgeService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;
    private StudentRepository $studentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?BadgeService $badgeService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?StudentRepository $studentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->badgeService = $badgeService ?? new BadgeService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
    }

    /**
     * Display badge directory and awarding workbench for teachers.
     * Route: GET /teacher/badges
     */
    public function index(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $userId = (int)($userContext->id ?? $userContext->userId ?? 0);
        $teacher = $this->teacherRepo->findTeacherByUserId($userId);

        if (!$teacher && !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->view('errors/403', ['message' => 'Teacher profile not found.'], 403);
        }

        $activeSession = $this->academicRepo->getActiveSession();
        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $teacher
            ? $this->academicRepo->getClassSubjectsByTeacher($teacherId, $activeSession?->id)
            : $this->academicRepo->getAllClassSubjects($activeSession?->id);

        $selectedCsId = (int)$request->get('class_subject_id', 0);
        if ($selectedCsId <= 0 && !empty($classSubjects)) {
            $selectedCsId = (int)$classSubjects[0]->id;
        }

        $badges = $this->badgeService->getAllBadges();

        // Enrolled students in selected subject for quick award dropdown
        $enrolledStudents = $selectedCsId > 0
            ? $this->studentRepo->getStudentsByClassSubjectIds([$selectedCsId])
            : [];

        return Response::html($this->render('teacher/badges/index', [
            'title' => 'Badges & Rewards — Claret Faculty Portal',
            'headerTitle' => 'Badges & Rewards Gamification',
            'badges' => $badges,
            'classSubjects' => $classSubjects,
            'selectedClassSubjectId' => $selectedCsId,
            'students' => $enrolledStudents,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Award a badge to an enrolled student.
     * Route: POST /teacher/badges/award
     */
    public function award(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = (int)$request->post('student_id', 0);
        $badgeId = (int)$request->post('badge_id', 0);
        $classSubjectId = (int)$request->post('class_subject_id', 0);
        $reason = (string)$request->post('reason', '');

        $redirectUrl = "/teacher/badges?class_subject_id={$classSubjectId}";

        try {
            $this->badgeService->awardBadge(
                studentId: $studentId,
                badgeId: $badgeId,
                reason: $reason,
                actor: $userContext,
                classSubjectId: $classSubjectId > 0 ? $classSubjectId : null
            );

            return $this->redirectWithSuccess($redirectUrl, 'Achievement badge successfully awarded to student!');
        } catch (ValidationException $e) {
            $firstError = current($e->getErrors());
            $message = is_array($firstError) ? current($firstError) : (string)$firstError;
            return $this->redirectWithError($redirectUrl, $message);
        } catch (AuthorizationException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Error awarding badge: " . $e->getMessage());
            return $this->redirectWithError($redirectUrl, 'An unexpected error occurred while awarding the badge.');
        }
    }
}
