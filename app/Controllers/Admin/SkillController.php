<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SkillRepository;

/**
 * Controller for Affective & Psychomotor Skills Catalog and Remark Presets Management (ADMIN-33)
 */
class SkillController extends Controller
{
    private SkillRepository $skillRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?SkillRepository $skillRepo = null
    ) {
        parent::__construct($authenticator);
        $this->skillRepo = $skillRepo ?? new SkillRepository();
    }

    /**
     * Skills Catalog & Remark Presets Directory
     * Route: GET /admin/skills
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $tab = $request->get('tab', 'skills');
        $selectedStage = trim((string)$request->get('stage', ''));
        $stages = [
            'eyfs' => 'Early Years (EYFS)',
            'primary' => 'Primary School',
            'junior_secondary' => 'Junior Secondary (JSS)',
            'senior_secondary' => 'Senior Secondary (SSS)',
        ];

        // Fetch skills (filtered if specific stage requested, or all)
        $skills = $this->skillRepo->getAllSkills(null, null, $selectedStage !== '' ? $selectedStage : null, false);
        $presets = $this->skillRepo->getRemarkPresets(null, false);

        $psychomotorSkills = array_filter($skills, static fn($s) => $s->isPsychomotor());
        $affectiveSkills = array_filter($skills, static fn($s) => $s->isAffective());

        // Group skills by educational stage
        $skillsByStage = [];
        foreach ($stages as $stageKey => $stageLabel) {
            $skillsByStage[$stageKey] = [
                'label' => $stageLabel,
                'psychomotor' => [],
                'affective' => [],
                'total' => 0,
            ];
        }
        $skillsByStage['universal'] = [
            'label' => 'Universal (All Stages)',
            'psychomotor' => [],
            'affective' => [],
            'total' => 0,
        ];

        foreach ($skills as $skill) {
            $stKey = $skill->stage ?? 'universal';
            if (!isset($skillsByStage[$stKey])) {
                $skillsByStage[$stKey] = [
                    'label' => ucfirst(str_replace('_', ' ', $stKey)),
                    'psychomotor' => [],
                    'affective' => [],
                    'total' => 0,
                ];
            }
            if ($skill->isPsychomotor()) {
                $skillsByStage[$stKey]['psychomotor'][] = $skill;
            } else {
                $skillsByStage[$stKey]['affective'][] = $skill;
            }
            $skillsByStage[$stKey]['total']++;
        }

        $teacherPresets = array_filter($presets, static fn($p) => $p->type === 'teacher');
        $principalPresets = array_filter($presets, static fn($p) => $p->type === 'principal');

        return Response::html($this->render('admin/skills/index', [
            'user' => $userContext,
            'tab' => $tab,
            'selectedStage' => $selectedStage,
            'stages' => $stages,
            'skills' => $skills,
            'skillsByStage' => $skillsByStage,
            'psychomotorSkills' => $psychomotorSkills,
            'affectiveSkills' => $affectiveSkills,
            'teacherPresets' => $teacherPresets,
            'principalPresets' => $principalPresets,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ]));
    }

    /**
     * Store new Skill
     * Route: POST /admin/skills
     */
    public function storeSkill(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $name = trim((string)$request->post('name', ''));
        $category = trim((string)$request->post('category', 'psychomotor'));
        $stage = trim((string)$request->post('stage', ''));
        $displayOrder = (int)$request->post('display_order', 1);
        $status = trim((string)$request->post('status', 'active'));

        if ($name === '') {
            return $this->redirectWithError('/admin/skills?tab=skills', 'Skill trait name is required.');
        }

        if (!in_array($category, ['psychomotor', 'affective'], true)) {
            $category = 'psychomotor';
        }

        $allowedStages = ['eyfs', 'primary', 'junior_secondary', 'senior_secondary'];
        $stageVal = in_array($stage, $allowedStages, true) ? $stage : null;

        $this->skillRepo->createSkill([
            'name' => $name,
            'category' => $category,
            'stage' => $stageVal,
            'display_order' => $displayOrder,
            'status' => $status === 'inactive' ? 'inactive' : 'active',
        ]);

        $stageParam = $stageVal ? '&stage=' . urlencode($stageVal) : '';
        return $this->redirectWithSuccess('/admin/skills?tab=skills' . $stageParam, 'Skill trait added successfully.');
    }

    /**
     * Update existing Skill
     * Route: POST /admin/skills/{id}/update
     */
    public function updateSkill(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $id = (int)$request->routeParam('id', 0);
        $skill = $this->skillRepo->getSkillById($id);
        if (!$skill) {
            return $this->redirectWithError('/admin/skills?tab=skills', 'Skill not found.');
        }

        $name = trim((string)$request->post('name', ''));
        $category = trim((string)$request->post('category', $skill->category));
        $stage = trim((string)$request->post('stage', $skill->stage ?? ''));
        $displayOrder = (int)$request->post('display_order', $skill->displayOrder);
        $status = trim((string)$request->post('status', $skill->status));

        if ($name === '') {
            return $this->redirectWithError('/admin/skills?tab=skills', 'Skill trait name is required.');
        }

        $allowedStages = ['eyfs', 'primary', 'junior_secondary', 'senior_secondary'];
        $stageVal = in_array($stage, $allowedStages, true) ? $stage : null;

        $this->skillRepo->updateSkill($id, [
            'name' => $name,
            'category' => in_array($category, ['psychomotor', 'affective'], true) ? $category : 'psychomotor',
            'stage' => $stageVal,
            'display_order' => $displayOrder,
            'status' => $status === 'inactive' ? 'inactive' : 'active',
        ]);

        $stageParam = $stageVal ? '&stage=' . urlencode($stageVal) : '';
        return $this->redirectWithSuccess('/admin/skills?tab=skills' . $stageParam, 'Skill trait updated successfully.');
    }

    /**
     * Delete Skill
     * Route: POST /admin/skills/{id}/delete
     */
    public function deleteSkill(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $id = (int)$request->routeParam('id', 0);
        $this->skillRepo->deleteSkill($id);

        return $this->redirectWithSuccess('/admin/skills?tab=skills', 'Skill trait removed successfully.');
    }

    /**
     * Store new Remark Preset
     * Route: POST /admin/skills/presets
     */
    public function storePreset(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $type = trim((string)$request->post('type', 'teacher'));
        $category = trim((string)$request->post('category', 'general'));
        $text = trim((string)$request->post('text', ''));
        $isActive = (bool)$request->post('is_active', true);

        if ($text === '') {
            return $this->redirectWithError('/admin/skills?tab=presets', 'Remark template text is required.');
        }

        if (!in_array($type, ['teacher', 'principal', 'general'], true)) {
            $type = 'teacher';
        }

        $this->skillRepo->createRemarkPreset([
            'type' => $type,
            'category' => $category,
            'text' => $text,
            'is_active' => $isActive ? 1 : 0,
        ]);

        return $this->redirectWithSuccess('/admin/skills?tab=presets', 'Remark template created successfully.');
    }

    /**
     * Update Remark Preset
     * Route: POST /admin/skills/presets/{id}/update
     */
    public function updatePreset(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $id = (int)$request->routeParam('id', 0);
        $preset = $this->skillRepo->getRemarkPresetById($id);
        if (!$preset) {
            return $this->redirectWithError('/admin/skills?tab=presets', 'Remark template not found.');
        }

        $type = trim((string)$request->post('type', $preset->type));
        $category = trim((string)$request->post('category', $preset->category));
        $text = trim((string)$request->post('text', ''));
        $isActive = $request->post('is_active') !== null ? (bool)$request->post('is_active') : true;

        if ($text === '') {
            return $this->redirectWithError('/admin/skills?tab=presets', 'Remark template text is required.');
        }

        $this->skillRepo->updateRemarkPreset($id, [
            'type' => in_array($type, ['teacher', 'principal', 'general'], true) ? $type : 'teacher',
            'category' => $category,
            'text' => $text,
            'is_active' => $isActive ? 1 : 0,
        ]);

        return $this->redirectWithSuccess('/admin/skills?tab=presets', 'Remark template updated successfully.');
    }

    /**
     * Delete Remark Preset
     * Route: POST /admin/skills/presets/{id}/delete
     */
    public function deletePreset(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $id = (int)$request->routeParam('id', 0);
        $this->skillRepo->deleteRemarkPreset($id);

        return $this->redirectWithSuccess('/admin/skills?tab=presets', 'Remark template removed successfully.');
    }
}
