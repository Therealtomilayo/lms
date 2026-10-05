<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Students Directory & Enrollment Roster — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Students Directory'
]);
?>

<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Enrolled Student Cohorts</h2>
            <p class="text-sm text-slate-500 mt-1">Institutional student directory, class enrollments, guardian linkages, and academic records.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/users/create?role=student" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#7B3046] hover:bg-[#632738] text-white shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Enroll Student</span>
            </a>
            <a href="/admin/enrollments" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Manage Enrollments</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0 border border-purple-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Enrolled Students</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= e((string)$totalCount) ?></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                <span class="text-lg font-bold">M</span>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Male Students</p>
                <h3 class="text-2xl font-extrabold text-blue-700 mt-0.5"><?= e((string)$maleCount) ?></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 border border-rose-100">
                <span class="text-lg font-bold">F</span>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Female Students</p>
                <h3 class="text-2xl font-extrabold text-rose-700 mt-0.5"><?= e((string)$femaleCount) ?></h3>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="/admin/students" data-lms-filter="true" class="flex flex-wrap items-center gap-3 w-full">
            <div class="relative w-full sm:w-64">
                <input type="text" 
                       id="student_search" 
                       name="search" 
                       value="<?= e($search ?? '') ?>" 
                       placeholder="Search name, admission no..." 
                       autocomplete="off"
                       class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-300 focus:border-[#7B3046] focus:ring-2 focus:ring-[#7B3046]/10 outline-none transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Class Filter -->
            <div class="w-full sm:w-auto">
                <select name="class_id" id="filter_student_class" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:border-[#7B3046] outline-none transition cursor-pointer">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c->id ?>" <?= ($selectedClassId ?? 0) === $c->id ? 'selected' : '' ?>>
                            <?= e($c->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Stage Filter -->
            <div class="w-full sm:w-auto">
                <select name="stage" id="filter_student_stage" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:border-[#7B3046] outline-none transition cursor-pointer">
                    <option value="">All Stages</option>
                    <?php foreach ($stages as $stg): ?>
                        <?php 
                            $stgKey = is_array($stg) ? ($stg['key'] ?? $stg['stage_key'] ?? '') : ($stg->key ?? '');
                            $stgName = is_array($stg) ? ($stg['name'] ?? '') : ($stg->name ?? '');
                        ?>
                        <option value="<?= e($stgKey) ?>" <?= ($selectedStage ?? '') === $stgKey ? 'selected' : '' ?>>
                            <?= e($stgName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Gender Filter -->
            <div class="w-full sm:w-auto">
                <select name="gender" id="filter_student_gender" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:border-[#7B3046] outline-none transition cursor-pointer">
                    <option value="">All Genders</option>
                    <option value="male" <?= ($selectedGender ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= ($selectedGender ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition cursor-pointer">
                Filter
            </button>
            <?php if (!empty($search) || !empty($selectedClassId) || !empty($selectedStage) || !empty($selectedGender)): ?>
                <a href="/admin/students" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Students Table Container -->
    <div data-lms-table-container="true" class="space-y-4">
        <?php if (empty($students)): ?>
            <div class="bg-white p-12 text-center rounded-2xl border border-slate-200">
                <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <h3 class="text-base font-bold text-slate-900">No student records found</h3>
                <p class="text-xs text-slate-500 mt-1">Try adjusting the filters or register a new student.</p>
            </div>
        <?php else: ?>
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-6 py-4">Student Profile</th>
                                <th class="px-6 py-4">Admission Number</th>
                                <th class="px-6 py-4">Class &amp; Arm</th>
                                <th class="px-6 py-4">Stage &amp; Level</th>
                                <th class="px-6 py-4">Primary Parent / Guardian</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($students as $s): ?>
                                <tr class="hover:bg-slate-50/75 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-800 font-bold text-sm flex items-center justify-center shrink-0 border border-purple-200">
                                                <?= strtoupper(substr($s['user_name'] ?? 'S', 0, 1)) ?>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="/admin/students/<?= $s['id'] ?>" class="font-bold text-slate-900 hover:text-[#7B3046] transition truncate block">
                                                    <?= e($s['user_name']) ?>
                                                </a>
                                                <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                                    <span class="capitalize"><?= e($s['gender'] ?? '—') ?></span>
                                                    <?php if (!empty($s['state_of_origin'])): ?>
                                                        <span>•</span>
                                                        <span><?= e($s['state_of_origin']) ?> State</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 text-xs font-mono font-bold border border-slate-200">
                                            <?= e($s['admission_number']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($s['current_class_id'])): ?>
                                            <a href="/admin/classes/<?= $s['current_class_id'] ?>" class="font-bold text-[#7B3046] hover:underline">
                                                <?= e($s['class_name'] ?? 'Class #' . $s['current_class_id']) ?>
                                            </a>
                                            <?php if (!empty($s['section_arm'])): ?>
                                                <span class="text-xs text-slate-400 block font-mono">Arm <?= e($s['section_arm']) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600">
                                        <span class="font-medium text-slate-800 block"><?= e($s['level_name'] ?? '—') ?></span>
                                        <span class="text-slate-400"><?= e(ucwords(str_replace('_', ' ', (string)($s['stage_name'] ?? '')))) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($s['parent_name'])): ?>
                                            <div class="font-semibold text-slate-900 text-xs"><?= e($s['parent_name']) ?></div>
                                            <?php if (!empty($s['parent_phone'])): ?>
                                                <div class="text-[11px] text-slate-500 font-mono"><?= e($s['parent_phone']) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Not linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="/admin/users/<?= $s['user_id'] ?>/edit" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-xs" title="Edit Student Profile & Account">
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                <span>Edit</span>
                                            </a>
                                            <a href="/admin/students/<?= $s['id'] ?>" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-[#7B3046] hover:text-white text-slate-700 transition">
                                                <span>View Dossier</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <?php
                $paginationBaseUrl = '/admin/students?' . http_build_query(array_filter([
                    'class_id' => $selectedClassId ?: null,
                    'stage' => $selectedStage ?: null,
                    'gender' => $selectedGender ?: null,
                    'search' => $search ?: null,
                ]));
                $this->include('components/pagination', [
                    'currentPage' => $currentPage ?? 1,
                    'totalPages' => $totalPages ?? 1,
                    'totalResults' => $totalResults ?? count($students),
                    'perPage' => $perPage ?? 25,
                    'baseUrl' => $paginationBaseUrl,
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>
