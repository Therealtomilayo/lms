<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Class Dossier — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Class Cohort Dossier'
]);
?>

<div class="space-y-6">
    <!-- Back to Classes Directory -->
    <div>
        <a href="/admin/classes" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Classes &amp; Arms Directory</span>
        </a>
    </div>

    <!-- Class Hero Card -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#7B3046] to-[#551E2E] text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center shrink-0 shadow-md">
                <?= strtoupper(substr($class->name, 0, 2)) ?>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900"><?= e($class->name) ?></h2>
                    <?php if (!empty($class->sectionArm)): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-brand-50 text-brand-700 text-xs font-mono font-bold border border-brand-100">
                            Arm <?= e($class->sectionArm) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($class->isActive()): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Active Cohort
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">
                            Inactive
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-500 mt-2">
                    <span class="font-medium text-slate-700"><?= e($level?->name ?? 'Level #' . $class->academicLevelId) ?></span>
                    <?php if (!empty($level?->stage)): ?>
                        <span>•</span>
                        <span class="capitalize text-slate-600"><?= e(ucwords(str_replace('_', ' ', $level->stage))) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Class Stats -->
        <div class="flex items-center gap-3">
            <div class="text-center px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Students</span>
                <span class="text-lg font-extrabold text-slate-900"><?= count($students) ?></span>
            </div>
            <div class="text-center px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Subjects</span>
                <span class="text-lg font-extrabold text-blue-700"><?= count($subjects) ?></span>
            </div>
            <a href="/admin/attendance" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#7B3046] text-white hover:bg-[#632738] transition shadow-xs">
                <span>Class Attendance</span>
            </a>
        </div>
    </div>

    <!-- Form Master / Class Teacher Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <span>Assigned Form Teacher (Class Master)</span>
        </h3>

        <?php if ($formTeacher): ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-amber-50/60 border border-amber-200">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-amber-200/80 text-amber-900 font-bold text-lg flex items-center justify-center shrink-0">
                        <?= strtoupper(substr($formTeacher->name, 0, 1)) ?>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm"><?= e($formTeacher->name) ?></h4>
                        <div class="flex items-center gap-2 text-xs text-slate-600 mt-0.5">
                            <span class="font-mono font-bold text-amber-900"><?= e($formTeacher->staffId) ?></span>
                            <?php if (!empty($formTeacher->phone)): ?>
                                <span>•</span>
                                <span><?= e($formTeacher->phone) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($formTeacher->email)): ?>
                                <span>•</span>
                                <span><?= e($formTeacher->email) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="/admin/teachers/<?= $formTeacher->id ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 transition shadow-xs">
                        <span>Teacher Profile</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center">
                <p class="text-xs text-slate-400 italic">No Form Master currently assigned to this class.</p>
                <p class="text-[11px] text-slate-400 mt-1">Assign a teacher via <a href="/admin/classes" class="text-[#7B3046] underline">Edit Class</a>.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Assigned Subjects & Teachers Section -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Curriculum Subjects &amp; Assigned Teachers</h3>
                <p class="text-xs text-slate-500 mt-0.5">Subjects taught in this class arm and the instructional staff assigned to each subject.</p>
            </div>
            <a href="/admin/class-subjects" class="text-xs font-semibold text-[#7B3046] hover:underline flex items-center gap-1">
                <span>Manage Class Subjects</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <?php if (empty($subjects)): ?>
            <div class="p-12 text-center">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <p class="text-sm font-semibold text-slate-700">No subjects currently allocated to this class</p>
                <p class="text-xs text-slate-400 mt-0.5">Allocate curriculum subjects via <a href="/admin/class-subjects" class="text-[#7B3046] underline">Class Subjects</a>.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">Subject</th>
                            <th class="px-6 py-3.5">Code</th>
                            <th class="px-6 py-3.5">Assigned Subject Teacher</th>
                            <th class="px-6 py-3.5">Contact</th>
                            <th class="px-6 py-3.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($subjects as $sub): ?>
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
                                            <div class="text-[10px] font-mono text-slate-500"><?= e($sub['teacher_staff_id']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-mono">
                                    <?= !empty($sub['teacher_phone']) ? e($sub['teacher_phone']) : '—' ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (($sub['status'] ?? 'active') === 'active'): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Enrolled Students Roster -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Enrolled Student Cohort (<?= count($students) ?> Students)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Students officially enrolled in this class arm for the active academic session.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    <?= $maleCount ?> Male
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                    <?= $femaleCount ?> Female
                </span>
            </div>
        </div>

        <?php if (empty($students)): ?>
            <div class="p-12 text-center">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <p class="text-sm font-semibold text-slate-700">No students currently enrolled in this class arm</p>
                <p class="text-xs text-slate-400 mt-0.5">Enroll students into this class via <a href="/admin/enrollments" class="text-[#7B3046] underline">Enrollment Administration</a>.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">Student Profile</th>
                            <th class="px-6 py-3.5">Admission Number</th>
                            <th class="px-6 py-3.5">Gender</th>
                            <th class="px-6 py-3.5">Origin / Demographics</th>
                            <th class="px-6 py-3.5">Primary Guardian</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($students as $s): ?>
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-purple-100 text-purple-800 font-bold text-xs flex items-center justify-center shrink-0 border border-purple-200">
                                            <?= strtoupper(substr($s['user_name'] ?? 'S', 0, 1)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <a href="/admin/students/<?= $s['id'] ?>" class="font-bold text-slate-900 hover:text-[#7B3046] hover:underline truncate block">
                                                <?= e($s['user_name']) ?>
                                            </a>
                                            <span class="text-xs text-slate-400 block"><?= e($s['user_email'] ?? '—') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= e($s['admission_number']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs capitalize text-slate-700">
                                    <?= e($s['gender'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <?= !empty($s['state_of_origin']) ? e($s['state_of_origin']) . ' State' : '—' ?>
                                    <?php if (!empty($s['lga'])): ?>
                                        <span class="text-slate-400 block text-[11px]">(<?= e($s['lga']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-700">
                                    <?php if (!empty($s['parent_name'])): ?>
                                        <div class="font-semibold text-slate-900"><?= e($s['parent_name']) ?></div>
                                        <?php if (!empty($s['parent_phone'])): ?>
                                            <div class="text-[11px] text-slate-500 font-mono"><?= e($s['parent_phone']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Not linked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/students/<?= $s['id'] ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-[#7B3046] hover:underline">
                                        <span>View Dossier</span>
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
