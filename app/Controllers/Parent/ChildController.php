<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AcademicRepository;
use App\Services\BadgeService;
use App\Services\DiscussionService;
use App\Services\ParentService;

/**
 * Controller for Child Switching and Detailed Single-Child Academic Overview
 */
class ChildController extends Controller
{
    private ParentService $parentService;
    private BadgeService $badgeService;
    private DiscussionService $discussionService;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ParentService $parentService = null,
        ?BadgeService $badgeService = null,
        ?DiscussionService $discussionService = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->parentService = $parentService ?? new ParentService();
        $this->badgeService = $badgeService ?? new BadgeService();
        $this->discussionService = $discussionService ?? new DiscussionService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * Set active selected child in session after validating ownership.
     * Route: POST /parent/children/{studentId}/select
     */
    public function select(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->input('student_id', 0));
        }

        try {
            // Strictly validate ownership before storing in session
            $student = $this->parentService->validateChildAccess($userContext, $studentId);

            Session::start();
            Session::set('_selected_child_id', $student->id);

            $redirectUrl = (string)($request->input('redirect_to') ?? "/parent/children/{$student->id}");
            if (!str_starts_with($redirectUrl, '/parent')) {
                $redirectUrl = "/parent/children/{$student->id}";
            } else {
                // If redirecting from another child's scoped subpage (e.g. /parent/children/4/timetable),
                // dynamically rewrite the student ID segment to the newly selected child
                $redirectUrl = preg_replace('#^/parent/children/\d+#', "/parent/children/{$student->id}", $redirectUrl);
            }

            $this->setFlash($request, 'success', "Switched active student view to {$student->name}.");
            return Response::redirect($redirectUrl);
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * Show detailed academic overview and status for a linked child.
     * Route: GET /parent/children/{studentId}
     */
    public function show(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            // Re-validate parent-child access predicate
            $overviewData = $this->parentService->getChildOverview($userContext, $studentId);

            Session::start();
            Session::set('_selected_child_id', $studentId);

            $children = $this->parentService->getLinkedChildren($userContext);

            return Response::html($this->render('parent/children/show', array_merge($overviewData, [
                'title' => "{$overviewData['student']->name} — Student Profile & Academic Overview",
                'children' => $children,
                'selectedChild' => $overviewData['student'],
                'user' => $userContext,
                'csrf_token' => Session::get('_csrf_token', ''),
            ]), 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * View all achievement badges earned by a linked child.
     * Route: GET /parent/children/{studentId}/badges
     */
    public function badges(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            $student = $this->parentService->validateChildAccess($userContext, $studentId);
            $earnedBadges = $this->badgeService->getStudentBadges($studentId);
            $children = $this->parentService->getLinkedChildren($userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('parent/children/badges', [
                'title' => "{$student->name} — Achievements & Badges",
                'student' => $student,
                'selectedChild' => $student,
                'children' => $children,
                'earnedBadges' => $earnedBadges,
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * Read-only view of a child's class discussion feeds.
     * Route: GET /parent/children/{studentId}/discussions
     */
    public function discussions(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->query('student_id', 0));
        }

        try {
            $student = $this->parentService->validateChildAccess($userContext, $studentId);
            $subjects = $this->parentService->getChildSubjectEnrollments($userContext, $studentId);

            $selectedCsId = (int)$request->query('class_subject_id', 0);
            if ($selectedCsId <= 0 && !empty($subjects)) {
                $selectedCsId = (int)$subjects[0]->classSubjectId;
            }

            $discussionData = null;
            if ($selectedCsId > 0) {
                try {
                    $discussionData = $this->discussionService->getDiscussions($selectedCsId, $userContext);
                } catch (\Throwable $t) {
                    error_log('Parent child discussions retrieval error: ' . $t->getMessage());
                    $discussionData = null;
                }
            }

            $children = $this->parentService->getLinkedChildren($userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('parent/children/discussions', [
                'title' => "{$student->name} — Class Discussions Feed",
                'student' => $student,
                'selectedChild' => $student,
                'children' => $children,
                'subjects' => $subjects,
                'selectedClassSubjectId' => $selectedCsId,
                'discussionData' => $discussionData,
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/parent'));
        } catch (AuthorizationException $e) {
            return Response::html('<h1>403 Forbidden</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 403);
        } catch (ResourceNotFoundException $e) {
            return Response::html('<h1>404 Not Found</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 404);
        }
    }

    /**
     * Upload single passport photograph for a linked ward.
     * Route: POST /parent/children/{studentId}/passport
     */
    public function uploadPassport(Request $request, array|string|int $params = []): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = is_array($params) ? (int)($params['studentId'] ?? 0) : (int)$params;
        if ($studentId <= 0) {
            $studentId = (int)($request->getAttribute('studentId') ?? $request->input('student_id', 0));
        }

        try {
            $student = $this->parentService->validateChildAccess($userContext, $studentId);
            $file = $_FILES['passport'] ?? null;

            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $this->setFlash($request, 'error', 'Please select a valid image file to upload.');
                return Response::redirect("/parent/children/{$studentId}");
            }

            $url = $this->processPassportUpload($file, (int)$student->id);
            if (!$url) {
                $this->setFlash($request, 'error', 'Invalid file type or size. Only JPG, PNG, and WebP images up to 5MB are allowed.');
                return Response::redirect("/parent/children/{$studentId}");
            }

            if (!empty($student->userId)) {
                $pdo = \App\Core\Database::getInstance();
                $stmt = $pdo->prepare('UPDATE users SET avatar_url = :url, updated_at = NOW() WHERE id = :uid');
                $stmt->execute([':url' => $url, ':uid' => $student->userId]);
            }

            $this->setFlash($request, 'success', "Passport photograph updated successfully for {$student->name}.");
            $redirectUrl = $request->input('redirect_to') ?: "/parent/children/{$studentId}";
            return Response::redirect($redirectUrl);
        } catch (\Throwable $e) {
            $this->setFlash($request, 'error', 'Error uploading passport: ' . $e->getMessage());
            $redirectUrl = $request->input('redirect_to') ?: "/parent/children/{$studentId}";
            return Response::redirect($redirectUrl);
        }
    }

    /**
     * Batch upload passport photographs for multiple wards.
     * Route: POST /parent/passports/batch
     */
    public function batchUploadPassports(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $linkedChildren = $this->parentService->getLinkedChildren($userContext);
        $childrenById = [];
        foreach ($linkedChildren as $child) {
            $childrenById[(int)$child->id] = $child;
        }

        $pdo = \App\Core\Database::getInstance();
        $successCount = 0;

        // Mode 1: Individual file inputs keyed by student_id: name="passports[student_id]"
        if (isset($_FILES['passports']['name']) && is_array($_FILES['passports']['name'])) {
            foreach ($_FILES['passports']['name'] as $sId => $name) {
                $studentId = (int)$sId;
                if (!isset($childrenById[$studentId])) {
                    continue;
                }

                $child = $childrenById[$studentId];
                $fileError = $_FILES['passports']['error'][$sId] ?? UPLOAD_ERR_NO_FILE;
                if ($fileError !== UPLOAD_ERR_OK) {
                    continue;
                }

                $singleFile = [
                    'name' => $_FILES['passports']['name'][$sId],
                    'type' => $_FILES['passports']['type'][$sId],
                    'tmp_name' => $_FILES['passports']['tmp_name'][$sId],
                    'error' => $_FILES['passports']['error'][$sId],
                    'size' => $_FILES['passports']['size'][$sId],
                ];

                $url = $this->processPassportUpload($singleFile, $studentId);
                if ($url && !empty($child->userId)) {
                    $stmt = $pdo->prepare('UPDATE users SET avatar_url = :url, updated_at = NOW() WHERE id = :uid');
                    $stmt->execute([':url' => $url, ':uid' => $child->userId]);
                    $successCount++;
                }
            }
        }

        // Mode 2: Multi-upload input supporting multiple files: name="multiple_passports[]" multiple
        if (isset($_FILES['multiple_passports']['name']) && is_array($_FILES['multiple_passports']['name'])) {
            $targetStudentIds = (array)($request->input('target_student_ids') ?? []);
            foreach ($_FILES['multiple_passports']['name'] as $idx => $name) {
                $studentId = isset($targetStudentIds[$idx]) ? (int)$targetStudentIds[$idx] : 0;
                if ($studentId <= 0 || !isset($childrenById[$studentId])) {
                    continue;
                }

                $child = $childrenById[$studentId];
                $fileError = $_FILES['multiple_passports']['error'][$idx] ?? UPLOAD_ERR_NO_FILE;
                if ($fileError !== UPLOAD_ERR_OK) {
                    continue;
                }

                $singleFile = [
                    'name' => $_FILES['multiple_passports']['name'][$idx],
                    'type' => $_FILES['multiple_passports']['type'][$idx],
                    'tmp_name' => $_FILES['multiple_passports']['tmp_name'][$idx],
                    'error' => $_FILES['multiple_passports']['error'][$idx],
                    'size' => $_FILES['multiple_passports']['size'][$idx],
                ];

                $url = $this->processPassportUpload($singleFile, $studentId);
                if ($url && !empty($child->userId)) {
                    $stmt = $pdo->prepare('UPDATE users SET avatar_url = :url, updated_at = NOW() WHERE id = :uid');
                    $stmt->execute([':url' => $url, ':uid' => $child->userId]);
                    $successCount++;
                }
            }
        }

        if ($successCount > 0) {
            $this->setFlash($request, 'success', "Successfully uploaded passport photograph(s) for {$successCount} ward(s).");
        } else {
            $this->setFlash($request, 'error', 'No valid passport photographs were processed. Please select PNG, JPG, or WebP files under 5MB.');
        }

        $selectedChildId = Session::get('_selected_child_id');
        $redirectUrl = $request->input('redirect_to') ?: ($selectedChildId ? "/parent/children/{$selectedChildId}" : '/parent/dashboard');
        return Response::redirect($redirectUrl);
    }

    /**
     * Helper to validate and save uploaded passport image.
     */
    private function processPassportUpload(array $file, int $studentId): ?string
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $maxBytes = 5 * 1024 * 1024; // 5MB

        if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
            return null;
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpPath)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            $ext = ($mime === 'image/png') ? 'png' : (($mime === 'image/webp') ? 'webp' : 'jpg');
        }

        $targetDir = dirname(__DIR__, 3) . '/public/assets/uploads/passports';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = "passport_student_{$studentId}_" . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            return null;
        }

        return "/assets/uploads/passports/{$filename}";
    }
}
