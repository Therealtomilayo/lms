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
        $skills = $this->skillRepo->getAllSkills();
        $presets = $this->skillRepo->getRemarkPresets(null, false);

        $psychomotorSkills = array_filter($skills, static fn($s) => $s->isPsychomotor());
        $affectiveSkills = array_filter($skills, static fn($s) => $s->isAffective());

        $teacherPresets = array_filter($presets, static fn($p) => $p->type === 'teacher');
        $principalPresets = array_filter($presets, static fn($p) => $p->type === 'principal');

        return Response::html($this->render('admin/skills/index', [
            'user' => $userContext,
            'tab' => $tab,
            'skills' => $skills,
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
        $displayOrder = (int)$request->post('display_order', 1);
        $status = trim((string)$request->post('status', 'active'));

        if ($name === '') {
            return $this->redirectWithError('/admin/skills?tab=skills', 'Skill trait name is required.');
        }

        if (!in_array($category, ['psychomotor', 'affective'], true)) {
            $category = 'psychomotor';
        }

        $this->skillRepo->createSkill([
            'name' => $name,
            'category' => $category,
            'display_order' => $displayOrder,
            'status' => $status === 'inactive' ? 'inactive' : 'active',
        ]);

        return $this->redirectWithSuccess('/admin/skills?tab=skills', 'Skill trait added successfully.');
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
        $displayOrder = (int)$request->post('display_order', $skill->displayOrder);
        $status = trim((string)$request->post('status', $skill->status));

        if ($name === '') {
            return $this->redirectWithError('/admin/skills?tab=skills', 'Skill trait name is required.');
        }

        $this->skillRepo->updateSkill($id, [
            'name' => $name,
            'category' => in_array($category, ['psychomotor', 'affective'], true) ? $category : 'psychomotor',
            'display_order' => $displayOrder,
            'status' => $status === 'inactive' ? 'inactive' : 'active',
        ]);

        return $this->redirectWithSuccess('/admin/skills?tab=skills', 'Skill trait updated successfully.');
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
