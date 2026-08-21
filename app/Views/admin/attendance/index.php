<?php
/**
 * ADMIN-17 — Attendance Register (Index)
 *
 * Layout is declared by the controller via:
 *   $this->render('admin/attendance/index', $data, 'layouts/admin')
 *
 * Available variables:
 *   $classes  — array of class objects (each has ->id, ->name)
 *   $today    — string 'Y-m-d', today's date
 *
 * Do NOT call $this->layout() here — it is already injected above.
 */
?>
<div class="space-y-6">

    <!-- Page Header Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4
                bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Attendance Oversight</h1>
            <p class="text-sm text-slate-500 mt-1">
                Review school-wide attendance, inspect class registers, or make audited historical corrections.
            </p>
        </div>
        <div class="flex-shrink-0">
            <?php $this->include('components/button', [
                'href'    => '/admin/attendance/report',
                'variant' => 'secondary',
                'label'   => 'Attendance Analytics & Report',
                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
            ]); ?>
        </div>
    </div>

    <!-- Class Registers Grid -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Class Attendance Registers</h2>
            <?php $this->include('components/badge', [
                'label'   => 'Today: ' . e($today),
                'variant' => 'info',
            ]); ?>
        </div>

        <?php if (empty($classes)): ?>
            <?php $this->include('components/empty_state', [
                'title'   => 'No Classes Found',
                'message' => 'No classes are configured in the system. Set up classes via the Classes & Arms management screen before taking attendance.',
                'actionUrl'   => '/admin/classes',
                'actionLabel' => 'Manage Classes',
            ]); ?>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($classes as $cls): ?>
                    <div class="group flex flex-col justify-between
                                rounded-xl border border-slate-200 bg-slate-50/50
                                hover:border-brand-400 hover:bg-white hover:shadow-md
                                transition-all duration-200 p-5">

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                Class
                            </span>
                            <h3 class="mt-1 text-lg font-bold text-slate-900 leading-tight">
                                <?= e($cls->name) ?>
                            </h3>
                        </div>

                        <div class="mt-5 pt-4 border-t border-slate-200
                                    flex items-center justify-between gap-2">
                            <?php $this->include('components/button', [
                                'href'    => '/admin/attendance/' . (int)$cls->id . '/' . e($today) . '/edit',
                                'variant' => 'quiet',
                                'label'   => 'Inspect / Edit Register',
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
                            ]); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
