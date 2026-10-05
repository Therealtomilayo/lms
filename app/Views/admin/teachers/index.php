<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Teachers Directory & Workspaces — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Teachers & Staff Directory'
]);
?>

<div class="space-y-6">
    <!-- Header with Breadcrumbs & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Instructional Staff</h2>
            <p class="text-sm text-slate-500 mt-1">Directory of teachers, assigned Form Masters (Class Teachers), and subject teaching allocations across arms.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/users/create?role=teacher" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-[#7B3046] hover:bg-[#632738] text-white shadow-xs transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Teacher</span>
            </a>
            <a href="/admin/class-subjects" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Subject Allocations</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center shrink-0 border border-purple-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Instructional Staff</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= e((string)$totalTeachers) ?></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Form Teachers (Class Masters)</p>
                <h3 class="text-2xl font-extrabold text-amber-700 mt-0.5"><?= e((string)$formTeachersCount) ?></h3>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 border border-blue-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Subject Allocations</p>
                <h3 class="text-2xl font-extrabold text-blue-700 mt-0.5"><?= e((string)$subjectTeachersCount) ?></h3>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <form method="GET" action="/admin/teachers" data-lms-filter="true" class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <div class="relative w-full sm:w-80">
                <input type="text" 
                       id="teacher_search" 
                       name="search" 
                       value="<?= e($search ?? '') ?>" 
                       placeholder="Search by name, staff ID, email..." 
                       autocomplete="off"
                       class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-[#7B3046] focus:ring-2 focus:ring-[#7B3046]/10 outline-none transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="w-full sm:w-auto">
                <select name="filter" id="filter_teacher_type" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-[#7B3046] focus:ring-2 focus:ring-[#7B3046]/10 outline-none transition cursor-pointer">
                    <option value="all" <?= ($filter ?? 'all') === 'all' ? 'selected' : '' ?>>All Teachers</option>
                    <option value="form_teachers" <?= ($filter ?? '') === 'form_teachers' ? 'selected' : '' ?>>Form Teachers Only</option>
                    <option value="subject_teachers" <?= ($filter ?? '') === 'subject_teachers' ? 'selected' : '' ?>>With Subject Allocations</option>
                </select>
            </div>

            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition cursor-pointer">
                Filter
            </button>
            <?php if (!empty($search) || ($filter ?? 'all') !== 'all'): ?>
                <a href="/admin/teachers" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs font-semibold text-slate-500 self-center">Showing <?= count($teachers) ?> of <?= (int)($totalResults ?? count($teachers)) ?> personnel records</span>
    </div>

    <!-- Teachers Table Container -->
    <div data-lms-table-container="true" class="space-y-4">
    <?php if (empty($teachers)): ?>
        <div class="bg-white p-12 text-center rounded-2xl border border-slate-200">
            <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <h3 class="text-base font-bold text-slate-900">No teachers found</h3>
            <p class="text-xs text-slate-500 mt-1">Try adjusting your search criteria or register a new teacher.</p>
        </div>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-6 py-4">Teacher Profile</th>
                            <th class="px-6 py-4">Staff ID</th>
                            <th class="px-6 py-4">Form Teacher (Class Master)</th>
                            <th class="px-6 py-4">Subjects Allocated</th>
                            <th class="px-6 py-4">Account Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($teachers as $t): ?>
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-[#7B3046]/10 text-[#7B3046] font-bold text-sm flex items-center justify-center shrink-0 border border-[#7B3046]/20">
                                            <?= strtoupper(substr($t['user_name'] ?? 'T', 0, 1)) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <a href="/admin/teachers/<?= $t['id'] ?>" class="font-bold text-slate-900 hover:text-[#7B3046] transition truncate block">
                                                <?= e($t['user_name']) ?>
                                            </a>
                                            <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                                <span><?= e($t['user_email'] ?? '—') ?></span>
                                                <?php if (!empty($t['user_phone'])): ?>
                                                    <span>•</span>
                                                    <span><?= e($t['user_phone']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-mono font-bold border border-slate-200">
                                        <?= e($t['staff_id']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($t['form_classes'])): ?>
                                        <div class="flex flex-wrap gap-1.5">
                                            <?php foreach (explode(', ', (string)$t['form_classes']) as $fc): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 text-xs font-semibold border border-amber-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    <?= e($fc) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php $subCount = (int)($t['subjects_count'] ?? 0); ?>
                                    <?php if ($subCount > 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-200">
                                            <?= $subCount ?> <?= $subCount === 1 ? 'Subject' : 'Subjects' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No allocations</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (($t['user_status'] ?? '') === 'active'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <a href="/admin/teachers/<?= $t['id'] ?>" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-[#7B3046] hover:text-white text-slate-700 transition">
                                            <span>View Profile</span>
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
            $paginationBaseUrl = '/admin/teachers?' . http_build_query(array_filter([
                'filter' => ($filter ?? 'all') !== 'all' ? $filter : null,
                'search' => !empty($search) ? $search : null,
            ]));
            $this->include('components/pagination', [
                'currentPage' => $currentPage ?? 1,
                'totalPages' => $totalPages ?? 1,
                'totalResults' => $totalResults ?? count($teachers),
                'perPage' => $perPage ?? 25,
                'baseUrl' => $paginationBaseUrl,
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>
