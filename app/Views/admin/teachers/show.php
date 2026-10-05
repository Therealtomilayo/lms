<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Teacher Dossier — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Teacher Personnel Dossier'
]);
?>

<div class="space-y-6">
    <!-- Back to Directory Navigation -->
    <div>
        <a href="/admin/teachers" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Teachers Directory</span>
        </a>
    </div>

    <!-- Teacher Profile Hero Banner -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#7B3046] to-[#551E2E] text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center shrink-0 shadow-md">
                <?= strtoupper(substr($teacher->name, 0, 1)) ?>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900"><?= e($teacher->name) ?></h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-mono font-bold border border-slate-200">
                        <?= e($teacher->staffId) ?>
                    </span>
                    <?php if (($teacher->userStatus ?? 'active') === 'active'): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Active Staff
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-500 mt-2">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <a href="mailto:<?= e($teacher->email) ?>" class="text-slate-700 hover:underline"><?= e($teacher->email) ?></a>
                    </span>
                    <?php if (!empty($teacher->phone)): ?>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <a href="tel:<?= e($teacher->phone) ?>" class="text-slate-700 hover:underline"><?= e($teacher->phone) ?></a>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="/admin/users/<?= $teacher->userId ?>/edit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span>Edit Account</span>
            </a>
            <a href="/admin/class-subjects" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-[#7B3046] text-white hover:bg-[#632738] transition shadow-xs">
                <span>Manage Allocations</span>
            </a>
        </div>
    </div>

    <!-- Form Teacher (Class Master) Section -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <span>Assigned Form Teacher Class (Class Master)</span>
        </h3>

        <?php if (empty($formClasses)): ?>
            <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                <p class="text-xs text-slate-500 font-medium">This teacher is currently not assigned as a Form Master for any class cohort.</p>
                <p class="text-[11px] text-slate-400 mt-1">Form teachers can be assigned via the <a href="/admin/classes" class="text-[#7B3046] underline">Classes administration portal</a>.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($formClasses as $fc): ?>
                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-amber-100 text-amber-800">
                                    Form Master
                                </span>
                                <?php if (!empty($fc['section_arm'])): ?>
                                    <span class="text-xs font-mono font-bold text-slate-600">Arm <?= e($fc['section_arm']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-lg font-bold text-slate-900 mt-2"><?= e($fc['name']) ?></h4>
                            <p class="text-xs text-slate-500"><?= e($fc['level_name'] ?? '') ?> • <?= e(ucwords(str_replace('_', ' ', (string)($fc['stage_name'] ?? '')))) ?></p>
                        </div>

                        <div class="pt-4 mt-3 border-t border-amber-200/60 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700"><?= (int)($fc['student_count'] ?? 0) ?> Students Enrolled</span>
                            <a href="/admin/classes/<?= $fc['id'] ?>" class="text-xs font-bold text-[#7B3046] hover:underline flex items-center gap-1">
                                <span>Class Roster</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Teaching Allocations (Subjects Taught Across Classes) -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Teaching Allocations &amp; Subject Assignments</h3>
                <p class="text-xs text-slate-500 mt-0.5">Classes and subjects assigned to this teacher for instructional delivery.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    <?= count($allocations) ?> Allocations
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <?= $totalStudentsTaught ?> Total Students Reached
                </span>
            </div>
        </div>

        <?php if (empty($allocations)): ?>
            <div class="p-12 text-center">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <p class="text-sm font-semibold text-slate-700">No active teaching allocations</p>
                <p class="text-xs text-slate-400 mt-0.5">Assign subjects to this teacher via <a href="/admin/class-subjects" class="text-[#7B3046] underline">Class Subjects management</a>.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">Subject</th>
                            <th class="px-6 py-3.5">Class / Arm</th>
                            <th class="px-6 py-3.5">Academic Level &amp; Stage</th>
                            <th class="px-6 py-3.5">Enrolled Students</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Class Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($allocations as $a): ?>
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($a['subject_name']) ?></div>
                                    <span class="text-[11px] font-mono font-semibold text-slate-400"><?= e($a['subject_code']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="/admin/classes/<?= $a['class_id'] ?>" class="font-bold text-slate-800 hover:text-[#7B3046] hover:underline">
                                        <?= e($a['class_name']) ?>
                                    </a>
                                    <?php if (!empty($a['section_arm'])): ?>
                                        <span class="text-xs font-mono text-slate-500 block">Arm <?= e($a['section_arm']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <span class="font-medium text-slate-800"><?= e($a['academic_level_name'] ?? '—') ?></span>
                                    <span class="text-slate-400 block"><?= e(ucwords(str_replace('_', ' ', (string)($a['stage_name'] ?? '')))) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-800 text-xs font-semibold">
                                        <?= (int)$a['student_count'] ?> Students
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (($a['status'] ?? 'active') === 'active'): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/classes/<?= $a['class_id'] ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-[#7B3046] hover:underline">
                                        <span>View Class</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
