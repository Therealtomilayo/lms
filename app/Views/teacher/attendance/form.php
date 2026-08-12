<div class="max-w-5xl mx-auto space-y-6" id="attendanceApp">
    <!-- Header -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/teacher/attendance" class="text-sm font-medium text-slate-500 hover:text-brand-600">&larr; Back to Register</a>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">
                <?= htmlspecialchars($class->name) ?> &mdash; <?= htmlspecialchars($date) ?>
            </h1>
            <p class="text-sm text-slate-600 mt-0.5">
                <?php if ($classSubject): ?>
                    Subject Attendance: <span class="font-medium text-slate-800"><?= htmlspecialchars($classSubject->subjectName ?? 'Subject') ?></span>
                    <?php if ($periodNumber): ?> &bull; Period #<?= (int)$periodNumber ?><?php endif; ?>
                <?php else: ?>
                    Daily Class Roll Call
                <?php endif; ?>
            </p>
        </div>

        <!-- Date switcher -->
        <div class="flex items-center gap-2">
            <label for="dateSelect" class="text-xs font-semibold uppercase text-slate-500">Date:</label>
            <input type="date" id="dateSelect" value="<?= htmlspecialchars($date) ?>" 
                   onchange="window.location.href='/teacher/attendance/<?= (int)$class->id ?>/' + this.value + '<?= $classSubjectId ? "?class_subject_id={$classSubjectId}" : '' ?>'"
                   class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
        </div>
    </div>

    <!-- Live Counter & Unsaved Sticky Header -->
    <div class="sticky top-2 z-20 bg-slate-900 text-white p-4 rounded-xl shadow-lg flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4 sm:gap-6 text-sm">
            <div><span class="text-slate-400">Total:</span> <strong id="countTotal" class="text-white"><?= count($roster) ?></strong></div>
            <div><span class="text-emerald-400">Present:</span> <strong id="countPresent" class="text-emerald-300">0</strong></div>
            <div><span class="text-rose-400">Absent:</span> <strong id="countAbsent" class="text-rose-300">0</strong></div>
            <div><span class="text-amber-400">Late:</span> <strong id="countLate" class="text-amber-300">0</strong></div>
            <div><span class="text-sky-400">Excused:</span> <strong id="countExcused" class="text-sky-300">0</strong></div>
        </div>
        <div class="flex items-center gap-3">
            <span id="dirtyIndicator" class="hidden text-xs font-semibold px-2.5 py-1 bg-amber-500 text-slate-950 rounded-full animate-pulse">
                Unsaved Changes
            </span>
            <button type="button" onclick="document.getElementById('attendanceForm').submit();" id="submitBtnTop"
                    class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white rounded-lg text-sm font-semibold transition flex items-center gap-2">
                Save Register
            </button>
        </div>
    </div>

    <!-- Attendance Form -->
    <form method="POST" action="/teacher/attendance/<?= (int)$class->id ?>/<?= htmlspecialchars($date) ?>" id="attendanceForm" class="space-y-4">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <?php if ($classSubjectId): ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$classSubjectId ?>">
        <?php endif; ?>
        <?php if ($periodNumber): ?>
            <input type="hidden" name="period_number" value="<?= (int)$periodNumber ?>">
        <?php endif; ?>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Student Roster (<?= count($roster) ?> Students)</span>
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-500">Quick Actions:</span>
                    <button type="button" onclick="setAll('present')" class="px-2 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded font-medium border border-emerald-200">All Present</button>
                    <button type="button" onclick="setAll('absent')" class="px-2 py-1 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded font-medium border border-rose-200">All Absent</button>
                </div>
            </div>

            <?php if (empty($roster)): ?>
                <div class="p-8 text-center text-slate-500">
                    No active students enrolled in this class.
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-200">
                    <?php foreach ($roster as $index => $row): ?>
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 transition student-row" data-student-id="<?= (int)$row['student_id'] ?>">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center">
                                    <?= $index + 1 ?>
                                </span>
                                <div>
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                    <div class="text-xs text-slate-500">Adm: <?= htmlspecialchars($row['admission_number']) ?></div>
                                </div>
                            </div>

                            <!-- Single-Tap Status Switcher -->
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <input type="hidden" name="status[<?= (int)$row['student_id'] ?>]" id="input_<?= (int)$row['student_id'] ?>" value="<?= htmlspecialchars($row['status']) ?>">
                                
                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'present')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_present"
                                        class="status-btn w-10 h-10 sm:w-11 sm:h-11 rounded-lg font-bold text-sm flex items-center justify-center transition border <?= $row['status'] === 'present' ? 'bg-emerald-600 text-white border-emerald-700 shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200' ?>"
                                        title="Present">
                                    P
                                </button>
                                
                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'absent')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_absent"
                                        class="status-btn w-10 h-10 sm:w-11 sm:h-11 rounded-lg font-bold text-sm flex items-center justify-center transition border <?= $row['status'] === 'absent' ? 'bg-rose-600 text-white border-rose-700 shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200' ?>"
                                        title="Absent">
                                    A
                                </button>

                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'late')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_late"
                                        class="status-btn w-10 h-10 sm:w-11 sm:h-11 rounded-lg font-bold text-sm flex items-center justify-center transition border <?= $row['status'] === 'late' ? 'bg-amber-500 text-white border-amber-600 shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200' ?>"
                                        title="Late">
                                    L
                                </button>

                                <button type="button" 
                                        onclick="setStatus(<?= (int)$row['student_id'] ?>, 'excused')" 
                                        id="btn_<?= (int)$row['student_id'] ?>_excused"
                                        class="status-btn w-10 h-10 sm:w-11 sm:h-11 rounded-lg font-bold text-sm flex items-center justify-center transition border <?= $row['status'] === 'excused' ? 'bg-sky-600 text-white border-sky-700 shadow-sm' : 'bg-slate-100 text-slate-600 border-slate-300 hover:bg-slate-200' ?>"
                                        title="Excused">
                                    E
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="p-6 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    Attendance records are automatically calculated into student report card term summaries upon saving.
                </p>
                <button type="submit" id="submitBtnBottom" class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 transition">
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
        'present': ['bg-emerald-600', 'text-white', 'border-emerald-700', 'shadow-sm'],
        'absent': ['bg-rose-600', 'text-white', 'border-rose-700', 'shadow-sm'],
        'late': ['bg-amber-500', 'text-white', 'border-amber-600', 'shadow-sm'],
        'excused': ['bg-sky-600', 'text-white', 'border-sky-700', 'shadow-sm']
    };
    const defaultClasses = ['bg-slate-100', 'text-slate-600', 'border-slate-300', 'hover:bg-slate-200'];

    statuses.forEach(s => {
        const btn = document.getElementById('btn_' + studentId + '_' + s);
        if (!btn) return;

        // remove all status styles
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
