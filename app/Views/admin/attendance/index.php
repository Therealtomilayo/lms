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

    <!-- Attendance Policy & Weights Configuration Card (SRS §26) -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                    </svg>
                    Institutional Attendance Calculation Policy (SRS §26)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Configure institutional credit weights for late attendance. This dynamically computes student term percentages and report card rates.
                </p>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-sky-50 text-sky-800 border border-sky-200">
                    Current Late Credit: <?= (float)($policy['late_weight_percentage'] ?? 60.0) ?>%
                </span>
            </div>
        </div>

        <form method="POST" action="/admin/attendance/settings" class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Present Weight (Fixed 100%) -->
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Present Roll Call</span>
                    <div class="text-lg font-black text-emerald-600">100% Credit</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Full academic credit (1.00)</span>
                </div>

                <!-- Late Arrival Weight (Configurable) -->
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <label for="late_weight" class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1">
                        Late Arrival Credit (%)
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="1" min="0" max="100" id="late_weight" name="late_weight" 
                               value="<?= (int)round(($policy['late_weight'] ?? 0.6) * 100) ?>" 
                               class="w-full px-3 py-1.5 text-sm font-bold rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-brand-500" required>
                        <span class="text-xs font-extrabold text-slate-500">%</span>
                    </div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Configured policy weight (e.g. 60% = 0.60)</span>
                </div>

                <!-- Absent Weight (Fixed 0%) -->
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Absent Roll Call</span>
                    <div class="text-lg font-black text-rose-600">0% Credit</div>
                    <span class="text-[11px] text-slate-400 mt-1 block">Zero academic credit (0.00)</span>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <p class="text-xs text-slate-500">
                    Formula: <code>Attendance Rate = [Present + (Late × LateWeight)] ÷ Total × 100</code>
                </p>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-brand-600 text-white hover:bg-brand-700 shadow-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Policy Settings
                </button>
            </div>
        </form>
    </div>

</div>
