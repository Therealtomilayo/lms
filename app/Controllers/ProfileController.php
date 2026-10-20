<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;

/**
 * Controller for Authenticated User Profile (AUTH-05)
 */
class ProfileController extends Controller
{
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?UserRepository $userRepo = null,
        ?StudentRepository $studentRepo = null,
        ?TeacherRepository $teacherRepo = null,
        ?ParentRepository $parentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Show Authenticated User's Profile
     * Route: GET /profile
     */
    public function show(Request $request): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        // Fetch fresh User model
        $userModel = $this->userRepo->findById($user->id) ?? $user;
        $roles = $userModel->roles ?? [];
        $primaryRole = $roles[0] ?? 'user';

        $roleData = [];
        $layoutName = 'layouts/app';

        if (in_array('super_admin', $roles, true) || in_array('admin', $roles, true)) {
            $layoutName = 'layouts/admin';
            $roleData['adminTier'] = in_array('super_admin', $roles, true) ? 'Super Administrator' : 'Administrator';
        } elseif (in_array('teacher', $roles, true)) {
            $layoutName = 'layouts/teacher';
            $teacher = $this->teacherRepo->findByUserId($user->id);
            $roleData['teacher'] = $teacher;
            if ($teacher) {
                $currentSession = $this->academicRepo->getCurrentSession();
                $sessionId = $currentSession ? (int)$currentSession->id : 0;
                $allocations = $sessionId > 0 ? $this->teacherRepo->getTeachingAllocations((int)$teacher->id, $sessionId) : [];
                $roleData['allocations'] = $allocations;
                $roleData['currentSession'] = $currentSession;
            }
        } elseif (in_array('student', $roles, true)) {
            $layoutName = 'layouts/student';
            $student = $this->studentRepo->findByUserId($user->id);
            $roleData['student'] = $student;
            if ($student) {
                $guardians = $this->parentRepo->getGuardiansForStudent((int)$student->id);
                $roleData['guardians'] = $guardians;
            }
        } elseif (in_array('parent', $roles, true)) {
            $layoutName = 'layouts/parent';
            $parent = $this->parentRepo->findByUserId($user->id);
            $roleData['parent'] = $parent;
            if ($parent) {
                $linkedStudents = $this->parentRepo->getLinkedStudents((int)$parent->id);
                $roleData['linkedStudents'] = $linkedStudents;
                $roleData['children'] = $linkedStudents;
                $roleData['selectedChild'] = !empty($linkedStudents) ? $linkedStudents[0] : null;
            }
        }

        return Response::html($this->render('profile/show', array_merge([
            'title' => 'My Profile — Claret LMS',
            'user' => $userModel,
            'primaryRole' => $primaryRole,
            'roles' => $roles,
        ], $roleData), $layoutName));
    }

    /**
     * Upload or update teacher's personal signature.
     * Route: POST /profile/signature
     */
    public function updateSignature(Request $request): Response
    {
        $user = $this->user($request);
        if (!$user) {
            return $this->redirect('/login');
        }

        $teacher = $this->teacherRepo->findByUserId($user->id);
        if (!$teacher) {
            return $this->redirectWithError('/profile', 'Only faculty members have access to signature uploads.');
        }

        $files = $request->files();
        if (empty($files['signature_file']) || $files['signature_file']['error'] !== UPLOAD_ERR_OK) {
            return $this->redirectWithError('/profile', 'Please choose a valid signature image file to upload.');
        }

        $file = $files['signature_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
            return $this->redirectWithError('/profile', 'Invalid image format. Allowed formats: PNG, JPG, JPEG, WEBP, SVG.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/assets/uploads/signatures';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'teacher_sig_' . $teacher->id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            $this->teacherRepo->updateSignatureUrl($teacher->id, '/assets/uploads/signatures/' . $filename);
            return $this->redirectWithSuccess('/profile', 'Your digital signature was successfully updated and attached to your dossier.');
        }

        return $this->redirectWithError('/profile', 'Failed to upload signature file. Please check folder permissions.');
    }
}
