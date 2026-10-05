<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Levels & Stages — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Academic Levels'
]);

// Build stage options & lookup map
$stageOptions = [];
$stageNameLookup = [];
$stageOrder = [];
if (!empty($stages)) {
    foreach ($stages as $stg) {
        $stageOptions[$stg->key] = $stg->name;
        $stageNameLookup[$stg->key] = $stg->name;
        $stageOrder[$stg->key] = $stg->rankOrder;
    }
}

// Build grading scale options for the select components
$scaleOptions = ['' => 'Stage Default Scale'];
$scaleNameLookup = [];
foreach ($gradingScales as $scale) {
    $stageTag = !empty($scale->stage) ? ' [' . ($stageNameLookup[$scale->stage] ?? ucwords(str_replace('_', ' ', $scale->stage))) . ']' : '';
    $scaleOptions[$scale->id] = e($scale->name) . $stageTag;
    $scaleNameLookup[$scale->id] = $scale->name;
}

// Group levels by educational stage
$levelsByStage = [];
$totalLevelsCount = count($levels);
$totalClassCount = 0;
$totalStudentCount = 0;

$levelStats = $levelStats ?? [];

foreach ($levels as $lvl) {
    $stgKey = $lvl->stage ?: 'unassigned';
    if (!isset($levelsByStage[$stgKey])) {
        $levelsByStage[$stgKey] = [];
    }
    $levelsByStage[$stgKey][] = $lvl;

    $cCount = $levelStats[$lvl->id]['classes'] ?? 0;
    $sCount = $levelStats[$lvl->id]['students'] ?? 0;
    $totalClassCount += $cCount;
    $totalStudentCount += $sCount;
}

// Sort stage groups according to stage rank_order
uksort($levelsByStage, function($a, $b) use ($stageOrder) {
    $rankA = $stageOrder[$a] ?? 999;
    $rankB = $stageOrder[$b] ?? 999;
    return $rankA <=> $rankB;
});

// Stage descriptive metadata & themes
$stageMeta = [
    'eyfs' => [
        'title' => 'Early Years Foundation (EYFS)',
        'subtitle' => 'Early childhood education: Creche, Pre-Nursery, Nursery 1 & 2',
        'badge_bg' => 'bg-amber-50 text-amber-700 border-amber-200',
        'icon_bg' => 'bg-amber-100 text-amber-600',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    ],
    'primary' => [
        'title' => 'Primary School',
        'subtitle' => 'Foundational basic education: Primary 1 to Primary 6',
        'badge_bg' => 'bg-blue-50 text-blue-700 border-blue-200',
        'icon_bg' => 'bg-blue-100 text-blue-600',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
    ],
    'junior_secondary' => [
        'title' => 'Junior Secondary School (JSS)',
        'subtitle' => 'Lower secondary education: JSS 1 to JSS 3 (Universal Basic Education)',
        'badge_bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'icon_bg' => 'bg-emerald-100 text-emerald-600',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'
    ],
    'senior_secondary' => [
        'title' => 'Senior Secondary School (SSS)',
        'subtitle' => 'Upper secondary education & specialized departmental tracks: SS 1 to SS 3',
        'badge_bg' => 'bg-purple-50 text-purple-700 border-purple-200',
        'icon_bg' => 'bg-purple-100 text-purple-600',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>'
    ],
];
?>

<div class="space-y-6">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Academic Levels & Educational Hierarchy</h2>
            <p class="text-sm text-slate-500 mt-1">Hierarchically structured stages (EYFS &rarr; Primary &rarr; JSS &rarr; SSS) and academic levels with cohort statistics.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'secondary',
                'label' => '+ Add Stage',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'stage-modal\')"'
            ]); ?>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Level',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Institutional Hierarchy Metric Overview Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Educational Stages</p>
                <p class="text-xl font-bold text-slate-800"><?= count($stages) ?></p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Academic Levels</p>
                <p class="text-xl font-bold text-slate-800"><?= $totalLevelsCount ?></p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Class Arms</p>
                <p class="text-xl font-bold text-slate-800"><?= $totalClassCount ?></p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Enrolled</p>
                <p class="text-xl font-bold text-slate-800"><?= number_format($totalStudentCount) ?> Students</p>
            </div>
        </div>
    </div>

    <!-- Grouped Educational Stages & Levels -->
    <?php if (empty($levels)): ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No Academic Levels',
            'message' => 'No academic levels configured yet. Click "Create Level" to get started.'
        ]); ?>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($levelsByStage as $stageKey => $stageLevels): ?>
                <?php 
                $meta = $stageMeta[$stageKey] ?? [
                    'title' => $stageNameLookup[$stageKey] ?? ucwords(str_replace('_', ' ', $stageKey)),
                    'subtitle' => 'Configured academic levels for this stage',
                    'badge_bg' => 'bg-slate-100 text-slate-700 border-slate-200',
                    'icon_bg' => 'bg-slate-100 text-slate-600',
                    'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'
                ];

                // Calculate stage aggregates
                $stageClassCount = 0;
                $stageStudentCount = 0;
                foreach ($stageLevels as $l) {
                    $stageClassCount += ($levelStats[$l->id]['classes'] ?? 0);
                    $stageStudentCount += ($levelStats[$l->id]['students'] ?? 0);
                }
                ?>
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs transition hover:border-slate-300">
                    <!-- Stage Section Header -->
                    <div class="px-6 py-4 bg-slate-50/70 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg <?= $meta['icon_bg'] ?> flex items-center justify-center shrink-0">
                                <?= $meta['icon'] ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-900"><?= e($meta['title']) ?></h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $meta['badge_bg'] ?> border">
                                        <?= count($stageLevels) ?> <?= count($stageLevels) === 1 ? 'Level' : 'Levels' ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5"><?= e($meta['subtitle']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium text-slate-600 self-start md:self-auto">
                            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-md border border-slate-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <strong><?= $stageClassCount ?></strong> Class Arms
                            </span>
                            <span class="inline-flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-md border border-slate-200">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <strong><?= number_format($stageStudentCount) ?></strong> Students
                            </span>
                            <a href="/admin/grading-scales" class="text-brand-600 hover:text-brand-700 font-semibold hover:underline">
                                Stage Grading Scale &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Stage Levels Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50/40 text-slate-500 font-medium text-xs uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-3 w-16">Rank</th>
                                    <th scope="col" class="px-6 py-3">Academic Level</th>
                                    <th scope="col" class="px-6 py-3">Active Cohorts</th>
                                    <th scope="col" class="px-6 py-3">Enrolled Students</th>
                                    <th scope="col" class="px-6 py-3">Grading Scale</th>
                                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($stageLevels as $lvl): ?>
                                    <?php 
                                    $cCount = $levelStats[$lvl->id]['classes'] ?? 0;
                                    $sCount = $levelStats[$lvl->id]['students'] ?? 0;
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition group">
                                        <td class="px-6 py-3.5">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-slate-100 text-slate-600 text-xs font-bold font-mono">
                                                <?= e((string)$lvl->rankOrder) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span class="font-bold text-slate-900 block group-hover:text-brand-600 transition"><?= e($lvl->name) ?></span>
                                            <span class="text-[11px] text-slate-400 font-mono">ID: #<?= $lvl->id ?></span>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <?php if ($cCount > 0): ?>
                                                <a href="/admin/classes" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition border border-emerald-200">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                    <?= $cCount ?> <?= $cCount === 1 ? 'Arm' : 'Arms' ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400 italic">No classes</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span class="text-sm font-semibold text-slate-800"><?= number_format($sCount) ?></span>
                                            <span class="text-xs text-slate-400">students</span>
                                        </td>
                                        <td class="px-6 py-3.5 text-slate-600">
                                            <?php if ($lvl->gradingScaleId && isset($scaleNameLookup[$lvl->gradingScaleId])): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                    <?= e($scaleNameLookup[$lvl->gradingScaleId]) ?>
                                                </span>
                                                <span class="text-[10px] text-slate-400 block mt-0.5">Level Override</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-xs text-slate-600 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Stage Default
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-3.5 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="/admin/classes" class="text-xs text-slate-500 hover:text-slate-800 font-medium px-2 py-1 rounded hover:bg-slate-100 transition">
                                                    View Classes
                                                </a>
                                                <?php $this->include('components/button', [
                                                    'type' => 'button',
                                                    'variant' => 'secondary',
                                                    'label' => 'Edit',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                                    'attributes' => 'onclick="openEditModal(' . $lvl->id . ', \'' . e(addslashes($lvl->name)) . '\', \'' . e(addslashes($lvl->stage)) . '\', ' . $lvl->rankOrder . ', \'' . ($lvl->gradingScaleId ?? '') . '\')"'
                                                ]); ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Stage Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/academic-levels/stages" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'stage_name',
        'label' => 'Stage Display Name',
        'placeholder' => 'e.g. Early Years, Primary, Junior Secondary',
        'required' => true,
        'helpText' => 'The human-readable label shown across the system.'
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'key',
        'id' => 'stage_key',
        'label' => 'Stage Code / Key (Optional)',
        'placeholder' => 'e.g. early_years, primary, junior_secondary',
        'required' => false,
        'helpText' => 'Leave blank to automatically generate from name.'
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'rank_order',
        'id' => 'stage_rank_order',
        'label' => 'Sort Order',
        'type' => 'number',
        'value' => '10',
        'required' => true,
        'helpText' => 'Ordering index for listing educational stages.'
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'stage-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Save Stage'
        ]); ?>
    </div>
</form>
<?php $stageModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'stage-modal',
    'title' => 'Configure Educational Stage',
    'body' => $stageModalBody,
    'size' => 'md'
]); ?>

<!-- Create Level Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/academic-levels" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_level_name',
        'label' => 'Level Name',
        'placeholder' => 'e.g. JSS 1, Grade 7',
        'required' => true
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'stage',
        'id' => 'create_level_stage',
        'label' => 'Educational Stage',
        'options' => $stageOptions,
        'selected' => 'junior_secondary',
        'required' => true,
        'helpText' => 'Select educational stage for stage-wide grading scales and settings.'
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'rank_order',
            'id' => 'create_rank_order',
            'label' => 'Rank Order',
            'type' => 'number',
            'value' => '1',
            'required' => true,
            'helpText' => 'Determines sort position in lists.'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'grading_scale_id',
            'id' => 'create_scale',
            'label' => 'Grading Scale',
            'options' => $scaleOptions,
            'selected' => '',
            'placeholder' => '',
            'helpText' => 'Leave as "Stage Default" to inherit stage scale.'
        ]); ?>
    </div>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'create-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Create Level'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Academic Level',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Edit Level Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_level_name',
        'label' => 'Level Name',
        'required' => true
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'stage',
        'id' => 'edit_level_stage',
        'label' => 'Educational Stage',
        'options' => $stageOptions,
        'required' => true,
        'helpText' => 'Select educational stage for stage-wide grading scales and settings.'
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'rank_order',
            'id' => 'edit_rank_order',
            'label' => 'Rank Order',
            'type' => 'number',
            'required' => true,
            'helpText' => 'Determines sort position in lists.'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'grading_scale_id',
            'id' => 'edit_scale',
            'label' => 'Grading Scale',
            'options' => $scaleOptions,
            'selected' => '',
            'placeholder' => '',
            'helpText' => 'Leave as "Stage Default" to inherit stage scale.'
        ]); ?>
    </div>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'edit-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Save Changes'
        ]); ?>
    </div>
</form>
<?php $editModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'edit-modal',
    'title' => 'Edit Academic Level',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, name, stage, rankOrder, scaleId) {
        document.getElementById('edit-form').action = '/admin/academic-levels/' + id;
        document.getElementById('edit_level_name').value = name;
        document.getElementById('edit_level_stage').value = stage;
        document.getElementById('edit_rank_order').value = rankOrder;
        document.getElementById('edit_scale').value = scaleId || '';
        window.LMS.showModal('edit-modal');
    }
</script>
