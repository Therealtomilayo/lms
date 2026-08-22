<div class="max-w-5xl mx-auto space-y-6" id="attendanceApp">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/attendance" class="text-slate-400 hover:text-emerald-600 transition">Attendance</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Roll Call Sheet</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($class->name) ?>
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?php if ($classSubject): ?>
                        Subject Lesson: <strong class="text-slate-900"><?= htmlspecialchars($classSubject->subject?->name ?? 'Subject') ?></strong>
                        <?php if ($periodNumber): ?> &bull; Period #<?= (int)$periodNumber ?><?php endif; ?>
                    <?php else: ?>
                        Daily Homeroom Morning Attendance
                    <?php endif; ?>
                </p>
            </div>

            <!-- Locked Today Date Badge & Back Button -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-2 bg-emerald-50 px-3.5 py-2 rounded-xl border border-emerald-200">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xs font-bold text-emerald-950">Today: <?= date('l, M d, Y', strtotime($date)) ?></span>
                    <span class="text-[10px] uppercase tracking-wider font-extrabold bg-emerald-200/80 text-emerald-800 px-1.5 py-0.5 rounded">Active Session</span>
                </div>

                <?php $this->include('components/button', [
                    'label' => 'Back to Register',
                    'variant' => 'secondary',
                    'href' => '/teacher/attendance',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Live Counter & Sticky Header Bar -->
    <div class="sticky top-2 z-20 bg-slate-900 text-white p-4 rounded-2xl shadow-md flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 sm:gap-6 text-xs font-semibold">
            <div><span class="text-slate-400 uppercase tracking-wider text-[10px] block">Roster</span> <strong id="countTotal" class="text-white text-sm font-extrabold"><?= count($roster) ?></strong></div>
            <div><span class="text-emerald-400 uppercase tracking-wider text-[10px] block">Present</span> <strong id="countPresent" class="text-emerald-300 text-sm font-extrabold">0</strong></div>
            <div><span class="text-rose-400 uppercase tracking-wider text-[10px] block">Absent</span> <strong id="countAbsent" class="text-rose-300 text-sm font-extrabold">0</strong></div>
            <div><span class="text-amber-400 uppercase tracking-wider text-[10px] block">Late</span> <strong id="countLate" class="text-amber-300 text-sm font-extrabold">0</strong></div>
            <div><span class="text-sky-400 uppercase tracking-wider text-[10px] block">Excused</span> <strong id="countExcused" class="text-sky-300 text-sm font-extrabold">0</strong></div>
        </div>
        <div class="flex items-center gap-3">
            <span id="dirtyIndicator" class="hidden text-[11px] font-bold px-2.5 py-1 bg-amber-500 text-slate-950 rounded-full animate-pulse">
                Unsaved Changes
            </span>
            <button type="button" onclick="document.getElementById('attendanceForm').submit();" id="submitBtnTop"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-2">
                <span>Save Register</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        </div>
    </div>

    <!-- Attendance Form -->
    <form method="POST" action="/teacher/attendance/<?= (int)$class->id ?>/<?= htmlspecialchars($date) ?>" id="attendanceForm" class="space-y-4">
        <?= csrf_field() ?>
        <?php if ($classSubjectId): ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$classSubjectId ?>">
        <?php endif; ?>
        <?php if ($periodNumber): ?>
            <input type="hidden" name="period_number" value="<?= (int)$periodNumber ?>">
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <!-- Table Header Toolbar with Generous Padding -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    Student Roster (<?= count($roster) ?> Candidate<?= count($roster) === 1 ? '' : 's' ?>)
                </span>
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-400 font-semibold mr-1">Quick Actions:</span>
                    <button type="button" onclick="setAll('present')" class="px-3 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg font-bold border border-emerald-200 transition">
                        All Present
                    </button>
                    <button type="button" onclick="setAll('absent')" class="px-3 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg font-bold border border-rose-200 transition">
                        All Absent
                    </button>
                </div>
            </div>

            <?php if (empty($roster)): ?>
                <div class="p-12 text-center text-slate-400 text-xs">
                    No active students enrolled in this classroom cohort.
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($roster as $index => $row): ?>
                        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 transition student-row" data-student-id="<?= (int)$row['student_id'] ?>">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                    <?= $index + 1 ?>
                                </span>
                                <div>
                                    <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($row['student_name']) ?></div>
                                    <div class="text-[11px] font-mono font-semibold text-slate-400">Adm: <?= htmlspecialchars($row['admission_number']) ?></div>
                                </div>
                            </div>

                            <!-- Single-Tap Status Switcher -->
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <input type="hidden" name="status[<?= (int)$row['student_id'] ?>]" id="input_<?= (int)$row['student_id'] ?>" value="<?= htmlspecialchars($row['status']) ?>">
                                
                                <!-- Present (P) -->
                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'present')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_present"
                                        class="status-btn w-9 h-9 sm:w-10 sm:h-10 rounded-xl font-bold text-xs flex items-center justify-center transition border <?= $row['status'] === 'present' ? 'bg-emerald-600 text-white border-emerald-700 shadow-xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' ?>"
                                        title="Mark Present">
                                    P
                                </button>
                                
                                <!-- Absent (A) -->
                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'absent')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_absent"
                                        class="status-btn w-9 h-9 sm:w-10 sm:h-10 rounded-xl font-bold text-xs flex items-center justify-center transition border <?= $row['status'] === 'absent' ? 'bg-rose-600 text-white border-rose-700 shadow-xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' ?>"
                                        title="Mark Absent">
                                    A
                                </button>

                                <!-- Late (L) -->
                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'late')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_late"
                                        class="status-btn w-9 h-9 sm:w-10 sm:h-10 rounded-xl font-bold text-xs flex items-center justify-center transition border <?= $row['status'] === 'late' ? 'bg-amber-500 text-white border-amber-600 shadow-xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' ?>"
                                        title="Mark Late">
                                    L
                                </button>

                                <!-- Excused (E) -->
                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'excused')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_excused"
                                        class="status-btn w-9 h-9 sm:w-10 sm:h-10 rounded-xl font-bold text-xs flex items-center justify-center transition border <?= $row['status'] === 'excused' ? 'bg-sky-600 text-white border-sky-700 shadow-xs' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' ?>"
                                        title="Mark Excused">
                                    E
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Footer Bar -->
            <div class="px-6 py-4.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-[11px] text-slate-500">
                    Attendance records are automatically calculated into student report card term summaries upon saving.
                </p>
                <button type="submit" id="submitBtnBottom" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 shadow-xs transition">
                    Save Attendance Register
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let isDirty = false;

function updateCounters() {
    let present = 0, absent = 0, late = 0, excused = 0;
    const inputs = document.querySelectorAll('input[name^="status["]');
    inputs.forEach(input => {
        const val = input.value;
        if (val === 'present') present++;
        else if (val === 'absent') absent++;
        else if (val === 'late') late++;
        else if (val === 'excused') excused++;
    });

    document.getElementById('countPresent').innerText = present;
    document.getElementById('countAbsent').innerText = absent;
    document.getElementById('countLate').innerText = late;
    document.getElementById('countExcused').innerText = excused;
}

function setStatus(studentId, status) {
    const input = document.getElementById('input_' + studentId);
    if (!input) return;
    
    if (input.value !== status) {
        input.value = status;
        markDirty();
    }

    const statuses = ['present', 'absent', 'late', 'excused'];
    const activeClasses = {
        'present': ['bg-emerald-600', 'text-white', 'border-emerald-700', 'shadow-xs'],
        'absent': ['bg-rose-600', 'text-white', 'border-rose-700', 'shadow-xs'],
        'late': ['bg-amber-500', 'text-white', 'border-amber-600', 'shadow-xs'],
        'excused': ['bg-sky-600', 'text-white', 'border-sky-700', 'shadow-xs']
    };
    const defaultClasses = ['bg-slate-100', 'text-slate-600', 'border-slate-200', 'hover:bg-slate-200'];

    statuses.forEach(s => {
        const btn = document.getElementById('btn_' + studentId + '_' + s);
        if (!btn) return;

        Object.values(activeClasses).flat().forEach(c => btn.classList.remove(c));
        defaultClasses.forEach(c => btn.classList.remove(c));

        if (s === status) {
            activeClasses[s].forEach(c => btn.classList.add(c));
        } else {
            defaultClasses.forEach(c => btn.classList.add(c));
        }
    });

    updateCounters();
}

function setAll(status) {
    const rows = document.querySelectorAll('.student-row');
    rows.forEach(row => {
        const studentId = row.getAttribute('data-student-id');
        if (studentId) {
            setStatus(studentId, status);
        }
    });
}

function markDirty() {
    isDirty = true;
    const ind = document.getElementById('dirtyIndicator');
    if (ind) ind.classList.remove('hidden');
}

document.getElementById('attendanceForm').addEventListener('submit', function() {
    isDirty = false;
    const btnTop = document.getElementById('submitBtnTop');
    const btnBottom = document.getElementById('submitBtnBottom');
    if (btnTop) { btnTop.disabled = true; btnTop.innerText = 'Saving...'; }
    if (btnBottom) { btnBottom.disabled = true; btnBottom.innerText = 'Saving...'; }
});

window.addEventListener('beforeunload', function(e) {
    if (isDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Initialize on load
updateCounters();
</script>
