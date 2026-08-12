<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Attendance Oversight</h1>
            <p class="text-sm text-slate-600 mt-1">Review school-wide attendance, inspect class records, or make audited historical corrections.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/attendance/report" class="inline-flex items-center px-4 py-2 bg-brand-50 text-brand-700 border border-brand-200 rounded-lg text-sm font-medium hover:bg-brand-100 transition">
                Attendance Analytics & Report &rarr;
            </a>
        </div>
    </div>

    <!-- Class Selector -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">Class Attendance Registers</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($classes as $cls): ?>
                <div class="p-5 border border-slate-200 rounded-lg hover:border-brand-500 transition bg-slate-50 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-500">Class</span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1"><?= htmlspecialchars($cls->name) ?></h3>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                        <a href="/admin/attendance/<?= (int)$cls->id ?>/<?= htmlspecialchars($today) ?>/edit" 
                           class="text-sm font-medium text-brand-600 hover:text-brand-800">
                            Inspect / Edit Register &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
