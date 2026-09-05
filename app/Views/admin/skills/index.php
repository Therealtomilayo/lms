<?php
$this->layout('layouts/admin', [
    'title' => 'Skills & Remark Presets — Claret LMS',
    'headerTitle' => 'Affective & Psychomotor Skills & Remark Presets',
    'headerSubtitle' => 'Configure standard secondary school behavioral domains, rating traits, and quick-fill remark templates.'
]);

$currentTab = $tab ?? 'skills';
?>

<div class="space-y-6">
    <!-- Top Flash Messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span><?= e($flashSuccess) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span><?= e($flashError) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation Tab Pill Bar -->
    <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-200 pb-4">
        <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-xl">
            <a href="/admin/skills?tab=skills" 
               class="px-5 py-2 text-sm font-semibold rounded-lg transition <?= $currentTab === 'skills' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                Behavioral Traits Catalog (<?= count($skills) ?>)
            </a>
            <a href="/admin/skills?tab=presets" 
               class="px-5 py-2 text-sm font-semibold rounded-lg transition <?= $currentTab === 'presets' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                Configurable Remark Presets (<?= count($teacherPresets) + count($principalPresets) ?>)
            </a>
        </div>

        <div class="flex items-center gap-3">
            <a href="/admin/results/comments" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-lg transition border border-brand-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span>Open Batch Remarks Workspace &rarr;</span>
            </a>
        </div>
    </div>

    <?php if ($currentTab === 'skills'): ?>
        <!-- SKILLS & TRAITS TAB CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Traits Lists by Domain -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Psychomotor Skills Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                Psychomotor Domain (Physical & Practical Skills)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Physical coordination, crafts, lab dexterity, creative artwork and sporting abilities (1–5 scale).</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                            <?= count($psychomotorSkills) ?> Traits
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-xs tracking-wider">
                                    <th class="py-3 px-4 w-12 text-center">Order</th>
                                    <th class="py-3 px-4">Skill Trait</th>
                                    <th class="py-3 px-4 w-28 text-center">Status</th>
                                    <th class="py-3 px-4 w-28 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php if (empty($psychomotorSkills)): ?>
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-slate-400">No psychomotor skills added yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($psychomotorSkills as $sk): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-4 text-center font-mono text-xs text-slate-500"><?= (int)$sk->displayOrder ?></td>
                                            <td class="py-3 px-4 font-bold text-slate-900"><?= e($sk->name) ?></td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $sk->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>">
                                                    <?= ucfirst(e($sk->status)) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right space-x-2">
                                                <button type="button" 
                                                        onclick="editSkill(<?= (int)$sk->id ?>, '<?= addslashes(e($sk->name)) ?>', 'psychomotor', <?= (int)$sk->displayOrder ?>, '<?= e($sk->status) ?>')"
                                                        class="text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                                    Edit
                                                </button>
                                                <form method="POST" action="/admin/skills/<?= (int)$sk->id ?>/delete" class="inline" onsubmit="return confirm('Remove skill dimension <?= addslashes(e($sk->name)) ?>?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Affective Domain Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                                Affective Domain (Behavioral & Moral Traits)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Discipline, politeness, neatness, honesty, leadership and social relationships (1–5 scale).</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                            <?= count($affectiveSkills) ?> Traits
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase text-xs tracking-wider">
                                    <th class="py-3 px-4 w-12 text-center">Order</th>
                                    <th class="py-3 px-4">Behavioral Trait</th>
                                    <th class="py-3 px-4 w-28 text-center">Status</th>
                                    <th class="py-3 px-4 w-28 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php if (empty($affectiveSkills)): ?>
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-slate-400">No affective traits added yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($affectiveSkills as $sk): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-4 text-center font-mono text-xs text-slate-500"><?= (int)$sk->displayOrder ?></td>
                                            <td class="py-3 px-4 font-bold text-slate-900"><?= e($sk->name) ?></td>
                                            <td class="py-3 px-4 text-center">
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $sk->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>">
                                                    <?= ucfirst(e($sk->status)) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right space-x-2">
                                                <button type="button" 
                                                        onclick="editSkill(<?= (int)$sk->id ?>, '<?= addslashes(e($sk->name)) ?>', 'affective', <?= (int)$sk->displayOrder ?>, '<?= e($sk->status) ?>')"
                                                        class="text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                                    Edit
                                                </button>
                                                <form method="POST" action="/admin/skills/<?= (int)$sk->id ?>/delete" class="inline" onsubmit="return confirm('Remove behavioral trait <?= addslashes(e($sk->name)) ?>?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right Column: Add / Edit Skill Trait Form -->
            <div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5 sticky top-24">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 id="skill-form-title" class="text-base font-bold text-slate-900">Add New Behavioral Dimension</h3>
                        <p class="text-xs text-slate-500 mt-1">Add or customize standard evaluative traits for student report cards.</p>
                    </div>

                    <form id="skill-form" method="POST" action="/admin/skills" class="space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tab" value="skills">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Trait Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" id="skill_name" required 
                                   placeholder="e.g., Musical & Cultural Skills"
                                   class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Domain Category</label>
                            <select name="category" id="skill_category" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition">
                                <option value="psychomotor">Psychomotor (Practical / Physical)</option>
                                <option value="affective">Affective (Behavioral / Character)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Display Order</label>
                                <input type="number" name="display_order" id="skill_display_order" min="1" value="1" 
                                       class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status</label>
                                <select name="status" id="skill_status" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center gap-2">
                            <button type="submit" id="skill-submit-btn" class="flex-1 py-2.5 px-4 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                                Save Dimension
                            </button>
                            <button type="button" id="skill-cancel-btn" onclick="resetSkillForm()" class="hidden py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- PRESETS TAB CONTENT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Preset Lists -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Teacher Remark Presets Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
                                Form / Class Teacher Remark Templates
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Quick suggestion pills available to form teachers during terminal comment evaluation.</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                            <?= count($teacherPresets) ?> Templates
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php if (empty($teacherPresets)): ?>
                            <div class="p-8 text-center text-slate-400">No teacher remark templates configured.</div>
                        <?php else: ?>
                            <?php foreach ($teacherPresets as $pr): ?>
                                <div class="p-4 hover:bg-slate-50/70 transition flex items-start justify-between gap-4">
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded-md <?php
                                                echo match($pr->category) {
                                                    'excellent' => 'bg-emerald-100 text-emerald-800',
                                                    'good' => 'bg-blue-100 text-blue-800',
                                                    'average' => 'bg-amber-100 text-amber-800',
                                                    'improvement' => 'bg-rose-100 text-rose-800',
                                                    default => 'bg-slate-100 text-slate-700'
                                                };
                                            ?>">
                                                <?= e($pr->category) ?>
                                            </span>
                                            <?php if (!$pr->isActive): ?>
                                                <span class="text-xs text-slate-400 font-medium">(Inactive)</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-800 leading-relaxed font-normal">&ldquo;<?= e($pr->text) ?>&rdquo;</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 pt-1">
                                        <button type="button" 
                                                onclick="editPreset(<?= (int)$pr->id ?>, 'teacher', '<?= e($pr->category) ?>', '<?= addslashes(e($pr->text)) ?>', <?= $pr->isActive ? 'true' : 'false' ?>)"
                                                class="text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                            Edit
                                        </button>
                                        <form method="POST" action="/admin/skills/presets/<?= (int)$pr->id ?>/delete" class="inline" onsubmit="return confirm('Remove template?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Principal Remark Presets Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                                Principal's / Head of School Endorsement Templates
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">High-level terminal sign-off remarks printed alongside school seal on report cards.</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-purple-100 text-purple-800">
                            <?= count($principalPresets) ?> Templates
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php if (empty($principalPresets)): ?>
                            <div class="p-8 text-center text-slate-400">No principal remark templates configured.</div>
                        <?php else: ?>
                            <?php foreach ($principalPresets as $pr): ?>
                                <div class="p-4 hover:bg-slate-50/70 transition flex items-start justify-between gap-4">
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded-md <?php
                                                echo match($pr->category) {
                                                    'excellent' => 'bg-emerald-100 text-emerald-800',
                                                    'good' => 'bg-blue-100 text-blue-800',
                                                    'average' => 'bg-amber-100 text-amber-800',
                                                    'improvement' => 'bg-rose-100 text-rose-800',
                                                    default => 'bg-slate-100 text-slate-700'
                                                };
                                            ?>">
                                                <?= e($pr->category) ?>
                                            </span>
                                            <?php if (!$pr->isActive): ?>
                                                <span class="text-xs text-slate-400 font-medium">(Inactive)</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-slate-800 leading-relaxed font-normal">&ldquo;<?= e($pr->text) ?>&rdquo;</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 pt-1">
                                        <button type="button" 
                                                onclick="editPreset(<?= (int)$pr->id ?>, 'principal', '<?= e($pr->category) ?>', '<?= addslashes(e($pr->text)) ?>', <?= $pr->isActive ? 'true' : 'false' ?>)"
                                                class="text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                            Edit
                                        </button>
                                        <form method="POST" action="/admin/skills/presets/<?= (int)$pr->id ?>/delete" class="inline" onsubmit="return confirm('Remove template?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right Column: Add / Edit Remark Template Form -->
            <div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5 sticky top-24">
                    <div class="border-b border-slate-100 pb-4">
                        <h3 id="preset-form-title" class="text-base font-bold text-slate-900">Add Remark Suggestion Template</h3>
                        <p class="text-xs text-slate-500 mt-1">Configure preset comments that auto-suggest in teacher & admin grading sheets.</p>
                    </div>

                    <form id="preset-form" method="POST" action="/admin/skills/presets" class="space-y-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tab" value="presets">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Sign-off Role</label>
                                <select name="type" id="preset_type" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition">
                                    <option value="teacher">Form Teacher</option>
                                    <option value="principal">Principal</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Category</label>
                                <select name="category" id="preset_category" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition">
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Good / Commendable</option>
                                    <option value="average">Average</option>
                                    <option value="improvement">Needs Focus</option>
                                    <option value="conduct">Conduct & Discipline</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Template Text <span class="text-rose-500">*</span></label>
                            <textarea name="text" id="preset_text" rows="4" required 
                                      placeholder="e.g., An exemplary student who demonstrates outstanding commitment to coursework..."
                                      class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition leading-relaxed"></textarea>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="preset_is_active" value="1" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                            <label for="preset_is_active" class="text-xs font-medium text-slate-700">Active template (available in quick-suggestion pills)</label>
                        </div>

                        <div class="pt-2 flex items-center gap-2">
                            <button type="submit" id="preset-submit-btn" class="flex-1 py-2.5 px-4 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                                Save Template
                            </button>
                            <button type="button" id="preset-cancel-btn" onclick="resetPresetForm()" class="hidden py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
function editSkill(id, name, category, displayOrder, status) {
    document.getElementById('skill-form-title').innerText = 'Edit Dimension: ' + name;
    document.getElementById('skill-form').action = '/admin/skills/' + id + '/update';
    document.getElementById('skill_name').value = name;
    document.getElementById('skill_category').value = category;
    document.getElementById('skill_display_order').value = displayOrder;
    document.getElementById('skill_status').value = status;
    document.getElementById('skill-submit-btn').innerText = 'Update Dimension';
    document.getElementById('skill-cancel-btn').classList.remove('hidden');
    document.getElementById('skill_name').focus();
}

function resetSkillForm() {
    document.getElementById('skill-form-title').innerText = 'Add New Behavioral Dimension';
    document.getElementById('skill-form').action = '/admin/skills';
    document.getElementById('skill_name').value = '';
    document.getElementById('skill_category').value = 'psychomotor';
    document.getElementById('skill_display_order').value = '1';
    document.getElementById('skill_status').value = 'active';
    document.getElementById('skill-submit-btn').innerText = 'Save Dimension';
    document.getElementById('skill-cancel-btn').classList.add('hidden');
}

function editPreset(id, type, category, text, isActive) {
    document.getElementById('preset-form-title').innerText = 'Edit Remark Template';
    document.getElementById('preset-form').action = '/admin/skills/presets/' + id + '/update';
    document.getElementById('preset_type').value = type;
    document.getElementById('preset_category').value = category;
    document.getElementById('preset_text').value = text;
    document.getElementById('preset_is_active').checked = isActive;
    document.getElementById('preset-submit-btn').innerText = 'Update Template';
    document.getElementById('preset-cancel-btn').classList.remove('hidden');
    document.getElementById('preset_text').focus();
}

function resetPresetForm() {
    document.getElementById('preset-form-title').innerText = 'Add Remark Suggestion Template';
    document.getElementById('preset-form').action = '/admin/skills/presets';
    document.getElementById('preset_type').value = 'teacher';
    document.getElementById('preset_category').value = 'excellent';
    document.getElementById('preset_text').value = '';
    document.getElementById('preset_is_active').checked = true;
    document.getElementById('preset-submit-btn').innerText = 'Save Template';
    document.getElementById('preset-cancel-btn').classList.add('hidden');
}
</script>
