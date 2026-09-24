<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\StudentRepository;
use App\Services\TranscriptService;

/**
 * Controller for Admin Student Transcripts Management & Dossier Viewer
 */
class TranscriptController extends Controller
{
    private TranscriptService $transcriptService;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?TranscriptService $transcriptService = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        parent::__construct($authenticator);
        $this->transcriptService = $transcriptService ?? new TranscriptService();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $search = trim((string)$request->query('search', ''));
        $classId = (int)$request->query('class_id', 0);

        $classes = $this->academicRepo->getAllClasses();
        $students = $this->studentRepo->getAll(
            limit: 50,
            offset: 0,
            classId: $classId > 0 ? $classId : null,
            search: $search !== '' ? $search : null
        );

        return $this->view('admin/transcripts/index', [
            'title' => 'Official Student Transcripts — Claret LMS',
            'headerTitle' => 'Student Transcripts',
            'students' => $students,
            'classes' => $classes,
            'selectedClassId' => $classId,
            'search' => $search,
        ]);
    }

    public function show(Request $request, string|int $studentId = 0): Response
    {
        $userContext = $this->user($request);
        if (!$userContext || !AcademicPolicy::canManageAcademicStructure($userContext)) {
            throw new AuthorizationException('Administrator access required.');
        }

        $studentId = (int)$studentId;
        $scope = trim((string)$request->query('scope', 'all'));
        $fromSession = (int)$request->query('from_session', 0) ?: null;
        $toSession = (int)$request->query('to_session', 0) ?: null;
        $showWatermark = $request->query('watermark', '1') !== '0';
        $showRemarks = $request->query('remarks', '1') !== '0';

        try {
            $transcript = $this->transcriptService->getStudentTranscriptData(
                $studentId,
                $scope,
                $fromSession,
                $toSession
            );
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }

        $allSessions = $this->academicRepo->getAllSessions();

        return $this->view('admin/transcripts/show', [
            'title' => "Academic Transcript — {$transcript['student']['name']} — Claret LMS",
            'headerTitle' => 'Cumulative Academic Transcript',
            'transcript' => $transcript,
            'scope' => $scope,
            'fromSession' => $fromSession,
            'toSession' => $toSession,
            'showWatermark' => $showWatermark,
            'showRemarks' => $showRemarks,
            'allSessions' => $allSessions,
        ]);
    }
}
