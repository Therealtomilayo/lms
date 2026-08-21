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

        return Response::html($this->render('admin/timetable/edit', [
            'title' => "Edit Timetable: {$class->name} — Admin Portal",
            'headerTitle' => "Timetable Builder: {$class->name}",
            'class' => $class,
            'term' => $selectedTerm,
            'terms' => $terms,
            'scheduleData' => $scheduleData,
            'classSubjects' => $classSubjects,
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
                'class_subject_id' => (int)$request->input('class_subject_id', 0),
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
                'class_subject_id' => (int)$request->input('class_subject_id', 0),
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
}
