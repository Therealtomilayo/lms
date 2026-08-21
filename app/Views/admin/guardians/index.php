<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Guardian Links — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Guardian Links'
]);

// Build parent options for select dropdown in modal
$parentOptions = ['' => 'Select Guardian...'];
foreach ($parents as $p) {
    $parentOptions[$p->id] = e($p->user?->name ?? 'Parent #' . $p->id) . ' (' . e($p->user?->email ?? '') . ')';
}

// Build student options for select dropdown in modal
$studentOptions = ['' => 'Select Student...'];
foreach ($allStudents as $st) {
    $studentOptions[$st->id] = e($st->user?->name ?? 'Student #' . $st->id) . ' (' . e($st->admissionNumber) . ')';
}

// Build relationship type options
$relationshipOptions = [
    'Father' => 'Father',
    'Mother' => 'Mother',
    'Guardian' => 'Guardian',
    'Sponsor' => 'Sponsor',
];
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Guardian Links</h2>
            <p class="text-sm text-slate-500 mt-1">Manage parent and guardian relationships linked to enrolled students.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Link Guardian to Student',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'link-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Search Form -->
    <form method="GET" action="/admin/guardians" class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row items-end gap-4">
        <div class="flex-1 w-full">
            <?php $this->include('components/input', [
                'name' => 'q',
                'id' => 'search_query',
                'label' => 'Search Guardians',
                'value' => $search ?? '',
                'placeholder' => 'Search parent name or email...',
            ]); ?>
        </div>
        <div class="w-full sm:w-auto flex gap-2">
            <?php $this->include('components/button', [
                'type' => 'submit',
                'variant' => 'primary',
                'label' => 'Search',
                'class' => 'w-full sm:w-auto min-h-[44px]'
            ]); ?>
            <?php if (!empty($search)): ?>
                <?php $this->include('components/button', [
                    'variant' => 'secondary',
                    'label' => 'Clear',
                    'href' => '/admin/guardians',
                    'class' => 'w-full sm:w-auto min-h-[44px]'
                ]); ?>
            <?php endif; ?>
        </div>
    </form>

    <!-- Guardians Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <?php if (empty($parents)): ?>
            <div class="p-6">
                <?php $this->include('components/empty_state', [
                    'title' => 'No Guardian Accounts',
                    'message' => 'No parent or guardian accounts found. Use the Search to clear filters or create parent accounts from the User Directory.'
                ]); ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Guardian / Parent</th>
                            <th class="px-6 py-3.5">Contact</th>
                            <th class="px-6 py-3.5">Linked Children / Students</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        <?php foreach ($parents as $p): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($p->user?->name ?? '—') ?></div>
                                    <div class="text-xs text-slate-500 font-normal"><?= e($p->user?->email ?? '—') ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-normal">
                                    <?= e($p->user?->phone ?? '—') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (empty($p->students)): ?>
                                        <span class="text-xs text-slate-400 italic font-normal">No students linked yet</span>
                                    <?php else: ?>
                                        <div class="space-y-2">
                                            <?php foreach ($p->students as $st): ?>
                                                <div class="flex items-center justify-between gap-3 text-xs bg-slate-50/50 p-2.5 rounded-lg border border-slate-200 hover:border-slate-300 transition duration-150 max-w-md shadow-2xs">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-bold text-slate-900"><?= e($st->user?->name ?? '') ?></span>
                                                        <span class="text-slate-500 font-mono font-normal">(<?= e($st->admissionNumber) ?>)</span>
                                                        <?php if (!empty($st->relationshipType)): ?>
                                                            <?php
                                                            $badgeVariant = 'neutral';
                                                            if (in_array($st->relationshipType, ['Father', 'Mother'])) {
                                                                $badgeVariant = 'info';
                                                            } elseif ($st->relationshipType === 'Guardian') {
                                                                $badgeVariant = 'success';
                                                            }
                                                            $this->include('components/badge', [
                                                                'label' => $st->relationshipType,
                                                                'variant' => $badgeVariant,
                                                                'class' => 'font-bold'
                                                            ]);
                                                            ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form method="POST" action="/admin/guardians/unlink" class="inline flex-shrink-0" onsubmit="return confirm('Unlink this child?')">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="parent_id" value="<?= e($p->id) ?>">
                                                        <input type="hidden" name="student_id" value="<?= e($st->id) ?>">
                                                        <button type="submit" class="p-1 rounded-lg text-slate-400 hover:bg-danger-50 hover:text-danger-600 transition duration-150 cursor-pointer" title="Unlink Student" aria-label="Unlink Student">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php $this->include('components/button', [
                                        'type' => 'button',
                                        'variant' => 'secondary',
                                        'label' => 'Link Child',
                                        'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                        'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                                        'attributes' => 'onclick="openLinkModal(' . $p->id . ', \'' . e(addslashes($p->user?->name ?? '')) . '\')"'
                                    ]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Component -->
            <?php $this->include('components/pagination', [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'baseUrl' => '/admin/guardians?q=' . urlencode($search ?? ''),
                'totalResults' => $totalParents,
                'perPage' => 25
            ]); ?>
        <?php endif; ?>
    </div>
</div>

<!-- Link Guardian Modal Body -->
<?php ob_start(); ?>
<form method="POST" action="/admin/guardians/link" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/select', [
        'name' => 'parent_id',
        'id' => 'modal-parent-id',
        'label' => 'Parent / Guardian',
        'options' => $parentOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'student_id',
        'id' => 'modal-student-id',
        'label' => 'Student / Child',
        'options' => $studentOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'relationship_type',
        'id' => 'modal-relationship-type',
        'label' => 'Relationship Type',
        'options' => $relationshipOptions,
        'selected' => 'Father',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'link-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Establish Link'
        ]); ?>
    </div>
</form>
<?php $modalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'link-modal',
    'title' => 'Link Guardian to Student',
    'body' => $modalBody,
    'size' => 'md'
]); ?>

<script>
function openLinkModal(parentId, parentName) {
    var select = document.getElementById('modal-parent-id');
    if (select) {
        select.value = parentId;
    }
    window.LMS.showModal('link-modal');
}
</script>
