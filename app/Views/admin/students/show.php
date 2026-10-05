<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Student Dossier — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Student Academic Dossier'
]);

$dob = $student['date_of_birth'] ?? null;
$age = null;
if ($dob) {
    $birthDate = new DateTime($dob);
    $todayDate = new DateTime('today');
    $age = $birthDate->diff($todayDate)->y;
}
?>

<div class="space-y-6">
    <!-- Back to Directory Navigation -->
    <div>
        <a href="/admin/students" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Students Directory</span>
        </a>
    </div>

    <!-- Student Hero Banner -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-purple-700 to-indigo-800 text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center shrink-0 shadow-md">
                <?= strtoupper(substr($student['user_name'] ?? 'S', 0, 1)) ?>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900"><?= e($student['user_name']) ?></h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-800 text-xs font-mono font-bold border border-slate-200">
                        <?= e($student['admission_number']) ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active Student
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-500 mt-2">
                    <span class="capitalize font-semibold text-slate-700"><?= e($student['gender'] ?? '—') ?></span>
                    <?php if ($age !== null): ?>
                        <span>•</span>
                        <span><?= $age ?> years old (DOB: <?= e($dob) ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($student['state_of_origin'])): ?>
                        <span>•</span>
                        <span><?= e($student['state_of_origin']) ?> State (<?= e($student['lga'] ?? '') ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="/admin/transcripts/<?= $student['id'] ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Cumulative Transcript</span>
            </a>
            <a href="/admin/users/<?= $student['user_id'] ?>/edit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-[#7B3046] text-white hover:bg-[#632738] transition shadow-xs">
                <span>Edit Account</span>
            </a>
        </div>
    </div>

    <!-- Academic Placement & Form Teacher Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Academic Cohort Placement</span>
            </h3>

            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <span class="text-xs text-slate-500">Current Class</span>
                    <?php if (!empty($student['current_class_id'])): ?>
                        <a href="/admin/classes/<?= $student['current_class_id'] ?>" class="text-sm font-bold text-[#7B3046] hover:underline">
                            <?= e($student['class_name']) ?>
                        </a>
                    <?php else: ?>
                        <span class="text-xs text-slate-400 italic">Not Assigned</span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <span class="text-xs text-slate-500">Section / Arm</span>
                    <span class="text-xs font-mono font-bold text-slate-800">
                        <?= !empty($student['section_arm']) ? 'Arm ' . e($student['section_arm']) : '—' ?>
                    </span>
                </div>

                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <span class="text-xs text-slate-500">Academic Level</span>
                    <span class="text-xs font-semibold text-slate-800"><?= e($student['level_name'] ?? '—') ?></span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500">Academic Stage</span>
                    <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200">
                        <?= e(ucwords(str_replace('_', ' ', (string)($student['stage_name'] ?? '')))) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Form Master Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span>Class Form Master (Class Teacher)</span>
            </h3>

            <?php if (!empty($student['form_teacher_name'])): ?>
                <div class="flex items-center gap-4 p-4 rounded-xl bg-amber-50/60 border border-amber-200">
                    <div class="w-12 h-12 rounded-xl bg-amber-200/80 text-amber-900 font-bold text-lg flex items-center justify-center shrink-0">
                        <?= strtoupper(substr($student['form_teacher_name'], 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-slate-900 text-sm"><?= e($student['form_teacher_name']) ?></h4>
                        <?php if (!empty($student['form_teacher_staff_id'])): ?>
                            <span class="text-xs font-mono font-bold text-amber-900 block"><?= e($student['form_teacher_staff_id']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($student['form_teacher_phone'])): ?>
                            <span class="text-xs text-slate-500 block mt-0.5"><?= e($student['form_teacher_phone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <p class="text-xs text-slate-400 italic">No Form Master currently assigned to this class cohort.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enrolled Subjects Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Enrolled Subjects Roster</h3>
                <p class="text-xs text-slate-500 mt-0.5">Active curriculum subjects and assigned instructional teachers for this student.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                <?= count($enrolledSubjects) ?> Enrolled Subjects
            </span>
        </div>

        <?php if (empty($enrolledSubjects)): ?>
            <div class="p-12 text-center">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <p class="text-sm font-semibold text-slate-700">No enrolled subjects registered</p>
                <p class="text-xs text-slate-400 mt-0.5">Subject enrollments are generated based on class allocations.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">Subject</th>
                            <th class="px-6 py-3.5">Subject Code</th>
                            <th class="px-6 py-3.5">Assigned Teacher</th>
                            <th class="px-6 py-3.5">Class Cohort</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($enrolledSubjects as $sub): ?>
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?= e($sub['subject_name']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= e($sub['subject_code']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($sub['teacher_name'])): ?>
                                        <div class="font-semibold text-xs text-slate-900"><?= e($sub['teacher_name']) ?></div>
                                        <?php if (!empty($sub['teacher_staff_id'])): ?>
                                            <div class="text-[10px] font-mono text-slate-400"><?= e($sub['teacher_staff_id']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                                    <?= e($sub['class_name']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Linked Parents / Guardians -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Linked Parents &amp; Legal Guardians</span>
        </h3>

        <?php if (empty($parents)): ?>
            <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center">
                <p class="text-xs text-slate-500 font-medium">No parent or guardian records currently linked to this student.</p>
                <p class="text-[11px] text-slate-400 mt-1">Parents can be linked via the <a href="/admin/guardians" class="text-[#7B3046] underline">Guardian Links manager</a>.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($parents as $p): ?>
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-purple-100 text-purple-800">
                                    <?= e($p['relationship_type'] ?? 'Guardian') ?>
                                </span>
                                <?php if (!empty($p['is_primary_contact'])): ?>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Primary Contact
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 mt-2"><?= e($p['name']) ?></h4>
                        </div>

                        <div class="pt-3 mt-3 border-t border-slate-200/80 space-y-1 text-xs text-slate-600">
                            <?php if (!empty($p['phone'])): ?>
                                <div class="flex items-center gap-1.5 font-mono">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <a href="tel:<?= e($p['phone']) ?>" class="hover:underline"><?= e($p['phone']) ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($p['email'])): ?>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <a href="mailto:<?= e($p['email']) ?>" class="hover:underline"><?= e($p['email']) ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
