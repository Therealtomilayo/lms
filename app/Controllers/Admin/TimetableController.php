<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\TimetableRepository;
use App\Services\TimetableService;

/**
 * Admin Timetable Controller
 * Manages class timetables, slot creation, updates, and deletions with server-side conflict detection.
 */
class TimetableController extends Controller
{
    private TimetableService $timetableService;
    private AcademicRepository $academicRepo;
    private TimetableRepository $timetableRepo;

    public function __construct(
        ?TimetableService $timetableService = null,
        ?AcademicRepository $academicRepo = null,
        ?TimetableRepository $timetableRepo = null
    ) {
        parent::__construct();
        $this->timetableService = $timetableService ?? new TimetableService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->timetableRepo = $timetableRepo ?? new TimetableRepository();
    }

    /**
     * Index screen: Overview list of classes with active terms and timetable statistics.
     */
    public function index(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $termId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : null;
        $terms = $this->academicRepo->getAllTerms();
        $selectedTerm = null;

        if ($termId) {
            $selectedTerm = $this->academicRepo->findTermById($termId);
        }
        if (!$selectedTerm) {
            $selectedTerm = $this->academicRepo->findCurrentTerm() ?? $this->academicRepo->findActiveTerm() ?? (!empty($terms) ? $terms[0] : null);
        }

        $classes = $this->academicRepo->getAllClasses();
        $classSlotsCount = [];

        if ($selectedTerm) {
            foreach ($classes as $c) {
                $slots = $this->timetableRepo->findByClass((int)$c->id, (int)$selectedTerm->id);
                $classSlotsCount[$c->id] = count($slots);
            }
        }

        return Response::html($this->render('admin/timetable/index', [
            'title' => 'Timetable Management — Admin Portal',
            'headerTitle' => 'Timetable Setup & Builder',
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'classes' => $classes,
            'classSlotsCount' => $classSlotsCount,
        ], 'layouts/admin'));
    }

    /**
     * Edit screen: Weekly schedule matrix for a specific class cohort.
     */
    public function edit(Request $request, string|int $classId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $termId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : null;

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return Response::notFound('Class not found.');
        }

        $terms = $this->academicRepo->getAllTerms();
        $selectedTerm = null;

        if ($termId) {
            $selectedTerm = $this->academicRepo->findTermById($termId);
        }
        if (!$selectedTerm) {
            $selectedTerm = $this->academicRepo->findCurrentTerm() ?? $this->academicRepo->findActiveTerm() ?? (!empty($terms) ? $terms[0] : null);
        }

        if (!$selectedTerm) {
            return $this->redirectWithError('/admin/timetable', 'No academic terms found. Please create a term first.');
        }

        try {
            $scheduleData = $this->timetableService->getClassTimetable($classId, $selectedTerm->id, $user);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/admin/timetable', $e->getMessage());
        }

        // Available class_subjects for this class and the term's session
        $classSubjects = $this->academicRepo->getClassSubjectsByClassAndSession($classId, (int)$selectedTerm->sessionId);

        $structure = (new \App\Repositories\TimetableStructureRepository())->findForClass($classId);
        $periods = $structure ? $structure->periods : \App\Utils\PeriodSchedule::getAllPeriods($classId);
        $instructionalPeriods = $structure ? $structure->getInstructionalPeriods() : \App\Utils\PeriodSchedule::getInstructionalPeriods($classId);

        return Response::html($this->render('admin/timetable/edit', [
            'title' => "Edit Timetable: {$class->name} — Admin Portal",
            'headerTitle' => "Timetable Builder: {$class->name}",
            'class' => $class,
            'term' => $selectedTerm,
            'terms' => $terms,
            'structure' => $structure,
            'scheduleData' => $scheduleData,
            'classSubjects' => $classSubjects,
            'standardPeriods' => $periods,
            'instructionalPeriods' => $instructionalPeriods,
            'standardDays' => \App\Utils\PeriodSchedule::getDays(),
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    /**
     * Master Institutional Timetable Grid across multiple cohorts.
     */
    public function master(Request $request): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $termId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : 0;
        $stageKey = (string)$request->getQuery('stage', 'high_school');
        $structureId = $request->getQuery('structure_id') ? (int)$request->getQuery('structure_id') : null;

        $terms = $this->academicRepo->getAllTerms();
        $structureRepo = new \App\Repositories\TimetableStructureRepository();
        $structures = $structureRepo->findAll();

        $selectedTerm = $termId ? $this->academicRepo->findTermById($termId) : null;
        if (!$selectedTerm) {
            $selectedTerm = $this->academicRepo->findCurrentTerm() ?? $this->academicRepo->findActiveTerm() ?? (!empty($terms) ? $terms[0] : null);
        }

        $masterData = $this->timetableService->getMasterTimetableMatrix(
            termId: (int)($selectedTerm?->id ?? 0),
            stageKey: $stageKey,
            structureId: $structureId,
            actor: $user
        );

        return Response::html($this->render('admin/timetable/master', [
            'title' => 'Master Institutional Timetable — Admin Portal',
            'headerTitle' => 'Master Timetable Grid',
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'structures' => $structures,
            'stageKey' => $stageKey,
            'selectedStructureId' => $structureId,
            'masterData' => $masterData,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    /**
     * Store new timetable slot.
     */
    public function store(Request $request, string|int $classId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $termId = (int)$request->input('term_id', 0);

        try {
            $this->timetableService->createSlot([
                'term_id' => $termId,
                'class_id' => $classId,
                'class_subject_id' => $request->input('class_subject_id') ? (int)$request->input('class_subject_id') : null,
                'structure_period_id' => $request->input('structure_period_id') ? (int)$request->input('structure_period_id') : null,
                'department' => $request->input('department') ? trim((string)$request->input('department')) : null,
                'custom_title' => $request->input('custom_title') ? trim((string)$request->input('custom_title')) : null,
                'slot_type' => $request->input('slot_type') ? trim((string)$request->input('slot_type')) : 'instructional',
                'day_of_week' => (string)$request->input('day_of_week', ''),
                'start_time' => (string)$request->input('start_time', ''),
                'end_time' => (string)$request->input('end_time', ''),
                'room' => $request->input('room') ? (string)$request->input('room') : null,
            ], $user);

            return $this->redirectWithSuccess(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'Timetable slot created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                $e->getErrors(),
                $request->all()
            );
        } catch (DomainRuleException | AuthorizationException | ResourceNotFoundException $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                $e->getMessage()
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'An unexpected error occurred while saving the timetable slot: ' . $e->getMessage()
            );
        }
    }

    /**
     * Update an existing timetable slot.
     */
    public function update(Request $request, string|int $classId = 0, string|int $slotId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $slotId = (int)($slotId ?: $request->getRouteParam('slotId', 0));
        $termId = (int)$request->input('term_id', 0);

        try {
            $this->timetableService->updateSlot($slotId, [
                'term_id' => $termId,
                'class_id' => $classId,
                'class_subject_id' => $request->input('class_subject_id') ? (int)$request->input('class_subject_id') : null,
                'structure_period_id' => $request->input('structure_period_id') ? (int)$request->input('structure_period_id') : null,
                'department' => $request->input('department') ? trim((string)$request->input('department')) : null,
                'custom_title' => $request->input('custom_title') ? trim((string)$request->input('custom_title')) : null,
                'slot_type' => $request->input('slot_type') ? trim((string)$request->input('slot_type')) : 'instructional',
                'day_of_week' => (string)$request->input('day_of_week', ''),
                'start_time' => (string)$request->input('start_time', ''),
                'end_time' => (string)$request->input('end_time', ''),
                'room' => $request->input('room') ? (string)$request->input('room') : null,
            ], $user);

            return $this->redirectWithSuccess(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'Timetable slot updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                $e->getErrors(),
                $request->all()
            );
        } catch (DomainRuleException | AuthorizationException | ResourceNotFoundException $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                $e->getMessage()
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'An unexpected error occurred while updating the slot: ' . $e->getMessage()
            );
        }
    }

    /**
     * Delete a timetable slot.
     */
    public function delete(Request $request, string|int $classId = 0, string|int $slotId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $slotId = (int)($slotId ?: $request->getRouteParam('slotId', 0));
        $termId = (int)$request->input('term_id', $request->getQuery('term_id', 0));

        try {
            $this->timetableService->deleteSlot($slotId, $user);

            return $this->redirectWithSuccess(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'Timetable slot removed successfully.'
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                $e->getMessage()
            );
        }
    }

    /**
     * Clone timetable schedule from another term.
     * Route: POST /admin/timetable/{classId}/clone
     */
    public function cloneSchedule(Request $request, string|int $classId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $sourceTermId = (int)$request->input('source_term_id', 0);
        $targetTermId = (int)$request->input('target_term_id', 0);
        $overwrite = (bool)$request->input('overwrite_existing', false);

        try {
            $result = $this->timetableService->cloneTermTimetable(
                $sourceTermId,
                $targetTermId,
                $classId > 0 ? $classId : null,
                $overwrite,
                $user
            );

            $msg = "Timetable successfully cloned! {$result['cloned_count']} period(s) copied.";
            if ($result['skipped_count'] > 0) {
                $msg .= " ({$result['skipped_count']} period(s) skipped due to existing schedule or conflicts).";
            }

            return $this->redirectWithSuccess(
                "/admin/timetable/{$classId}/edit?term_id={$targetTermId}",
                $msg
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors(
                "/admin/timetable/{$classId}/edit?term_id={$targetTermId}",
                $e->getErrors()
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$targetTermId}",
                $e->getMessage()
            );
        }
    }

    /**
     * Import timetable schedule from uploaded CSV spreadsheet.
     * Route: POST /admin/timetable/{classId}/import
     */
    public function importCsv(Request $request, string|int $classId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $classId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $termId = (int)$request->input('term_id', 0);
        $overwrite = (bool)$request->input('overwrite_existing', false);

        $content = null;
        $file = $request->file('csv_file') ?? ($_FILES['csv_file'] ?? null);
        if ($file && is_array($file) && !empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        }

        if (empty($content)) {
            $content = (string)$request->input('csv_content', '');
        }

        if (empty($content) || trim($content) === '') {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'Please select a valid CSV file to upload.'
            );
        }

        // Parse custom column mapping from UI if present
        $columnMapping = [];
        if ($request->filled('map_day')) {
            $columnMapping['day'] = (int)$request->input('map_day');
        }
        if ($request->filled('map_period')) {
            $columnMapping['period'] = (int)$request->input('map_period');
        }
        if ($request->filled('map_subject')) {
            $columnMapping['subject'] = (int)$request->input('map_subject');
        }
        if ($request->filled('map_room')) {
            $columnMapping['room'] = (int)$request->input('map_room');
        }
        if ($request->filled('map_class')) {
            $columnMapping['class'] = (int)$request->input('map_class');
        }

        try {
            $result = $this->timetableService->importFromCsv(
                $content,
                $termId,
                $user,
                $classId > 0 ? $classId : null,
                $overwrite,
                $columnMapping
            );

            return $this->redirectWithSuccess(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                "Successfully imported {$result['imported_count']} timetable slot(s) across {$result['classes_count']} class cohort(s)!"
            );
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $msg = $e->getMessage();
            if (!empty($errors['import_errors']) && is_array($errors['import_errors'])) {
                $msg = "Import validation failed:\n• " . implode("\n• ", array_slice($errors['import_errors'], 0, 5));
                if (count($errors['import_errors']) > 5) {
                    $msg .= "\n...and " . (count($errors['import_errors']) - 5) . " more error(s).";
                }
            }
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                $msg
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError(
                "/admin/timetable/{$classId}/edit?term_id={$termId}",
                'Import failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Download sample CSV template pre-populated with active class and subject codes.
     * Route: GET /admin/timetable/template/download
     */
    public function downloadTemplate(Request $request, string|int $classId = 0): Response
    {
        $user = $this->getUserContext($request);
        if (!$user) {
            return Response::redirect('/login');
        }

        $cId = (int)($classId ?: $request->getRouteParam('classId', 0));
        $classIdParam = $cId > 0 ? $cId : ($request->getQuery('class_id') ? (int)$request->getQuery('class_id') : null);
        $termId = $request->getQuery('term_id') ? (int)$request->getQuery('term_id') : null;
        $stageParam = $request->getQuery('stage') ?: ($request->getQuery('type') ?: null);

        $term = $termId ? $this->academicRepo->findTermById($termId) : null;
        $sessionId = $term ? (int)$term->sessionId : null;

        $csv = $this->timetableService->generateSampleCsv($classIdParam, $sessionId, $stageParam);

        $stageName = ($stageParam === 'primary' || $stageParam === 'grade_school') ? 'gradeschool' : 'highschool';
        if ($classIdParam) {
            $cls = $this->academicRepo->findClassById($classIdParam);
            if ($cls && in_array($cls->stage, ['primary', 'eyfs', 'nursery', 'creche'], true)) {
                $stageName = 'gradeschool';
            }
        }
        $filename = "claret_timetable_{$stageName}_template.csv";

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
