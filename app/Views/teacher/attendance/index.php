<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Attendance Management</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Attendance Register & Roll Call
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Today: <?= date('D, M d, Y', strtotime($today)) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Take daily homeroom roll calls or record period-by-period subject attendance for your allocated classroom cohorts.
                </p>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Assigned Classes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Class Cohorts</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($classes)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">classes</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Assigned homerooms
            </span>
        </div>

        <!-- Subject Allocations -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Subject Periods</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format(count($allocations)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">allocations</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Teaching assignments
            </span>
        </div>

        <!-- Academic Session -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Academic Session</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($currentSession?->name ?? 'Active Session') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Current school calendar
            </span>
        </div>

        <!-- Today's Date -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Roll Call Date</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-base font-extrabold text-slate-900 truncate"><?= date('M d, Y', strtotime($today)) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Default marking date
            </span>
        </div>
    </div>

    <!-- Daily Class Roll Call Cards -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900">Daily Homeroom Roll Call</h2>
                <p class="text-xs text-slate-500 mt-0.5">Take daily morning attendance for your assigned class rosters.</p>
            </div>
        </div>

        <?php if (empty($classes)): ?>
            <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p class="text-xs text-slate-500">You do not have any teaching allocations in the current academic session.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($classes as $cls): ?>
                    <div class="p-5 border border-slate-200 rounded-2xl hover:border-slate-300 transition bg-slate-50/60 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                    <?= htmlspecialchars($cls['level_name'] ?? 'Class') ?>
                                </span>
                                <?php $this->include('components/badge', [
                                    'label' => 'Active',
                                    'variant' => 'success',
                                    'size' => 'sm'
                                ]); ?>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mt-1">
                                <?= htmlspecialchars($cls['name']) ?>
                            </h3>
                        </div>

                        <div class="pt-3 border-t border-slate-200">
                            <a href="/teacher/attendance/<?= (int)$cls['id'] ?>/<?= htmlspecialchars($today) ?>" 
                               class="w-full inline-flex justify-center items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 shadow-xs transition">
                                <span>Take Today's Roll Call</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Subject / Period Attendance Selection -->
    <?php if (!empty($allocations)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200">
                <h2 class="text-base font-bold text-slate-900">Subject & Period Attendance</h2>
                <p class="text-xs text-slate-500 mt-0.5">Record attendance for individual class periods and subject lessons.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Class Cohort</th>
                            <th class="py-3.5 px-4">Subject</th>
                            <th class="py-3.5 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($allocations as $alloc): ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-900">
                                    <?= htmlspecialchars($alloc['class_name']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700">
                                    <span class="font-semibold text-slate-900"><?= htmlspecialchars($alloc['subject_name']) ?></span>
                                    <span class="text-slate-400 font-mono text-[11px] ml-1">(<?= htmlspecialchars($alloc['subject_code']) ?>)</span>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <a href="/teacher/attendance/<?= (int)$alloc['class_id'] ?>/<?= htmlspecialchars($today) ?>?class_subject_id=<?= (int)$alloc['class_subject_id'] ?>" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
                                        <span>Take Subject Attendance</span>
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
