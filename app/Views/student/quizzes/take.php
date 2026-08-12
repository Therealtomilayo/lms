<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Assessment: <?= e($quiz->title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="h-full flex flex-col antialiased text-slate-900 selection:bg-brand-500 selection:text-white">

    <!-- Top CBT Assessment Bar -->
    <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between sticky top-0 z-30 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-brand-600 text-white flex items-center justify-center font-bold text-sm">
                CBT
            </span>
            <div>
                <h1 class="text-base font-bold text-slate-900 leading-tight"><?= e($quiz->title) ?></h1>
                <p class="text-xs text-slate-500">Attempt #<?= $attempt->attemptNumber ?> · <?= count($questions) ?> Questions</p>
            </div>
        </div>

        <!-- Server-Authoritative Live Countdown Timer -->
        <div class="flex items-center gap-4">
            <div id="autosave-status" class="text-xs text-slate-400 font-medium flex items-center gap-1.5 transition-all">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>All changes saved</span>
            </div>

            <?php if ($quiz->hasTimeLimit()): ?>
                <div id="timer-box" class="px-4 py-1.5 rounded-xl font-mono text-sm font-bold bg-slate-900 text-white flex items-center gap-2 shadow-inner">
                    <svg class="w-4 h-4 text-brand-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span id="countdown">--:--</span>
                </div>
            <?php else: ?>
                <div class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-100 text-slate-600">
                    Untimed Test
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Player Workspace -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
        
        <!-- Left: Question Navigator Grid -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4 lg:sticky lg:top-20 order-2 lg:order-1">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Question Palette</h3>
                <span id="answered-count" class="text-xs font-semibold text-brand-600">0 / <?= count($questions) ?> Answered</span>
            </div>

            <div class="grid grid-cols-5 gap-2 max-h-60 overflow-y-auto custom-scrollbar p-1">
                <?php foreach ($questions as $idx => $q): 
                    $isAnswered = isset($answers[$q['id']]);
                ?>
                    <button type="button" 
                            id="palette-btn-<?= $idx ?>" 
                            onclick="jumpToQuestion(<?= $idx ?>)"
                            class="w-10 h-10 rounded-xl font-bold text-xs flex items-center justify-center transition border <?= $isAnswered ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' ?>">
                        <?= $idx + 1 ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Answered</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span> Unanswered</span>
            </div>
        </div>

        <!-- Center/Right: Active Question Card -->
        <div class="lg:col-span-3 space-y-6 order-1 lg:order-2">
            <form id="cbt-exam-form" method="POST" action="/student/quiz-attempts/<?= $attempt->id ?>/submit">
                <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">

                <?php foreach ($questions as $idx => $q): 
                    $currentAnswer = $answers[$q['id']] ?? null;
                    $selectedOptId = $currentAnswer['selected_option_id'] ?? null;
                    $textAns = $currentAnswer['text_answer'] ?? '';
                ?>
                    <div id="question-card-<?= $idx ?>" class="question-card <?= $idx === 0 ? '' : 'hidden' ?> bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                        
                        <!-- Question Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-brand-50 text-brand-700 rounded-xl font-bold text-xs">
                                    Question <?= $idx + 1 ?> of <?= count($questions) ?>
                                </span>
                                <?php if (!empty($q['topic'])): ?>
                                    <span class="text-xs text-slate-400 font-medium">· <?= e($q['topic']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">
                                <?= number_format($q['points'], 2) ?> pt<?= $q['points'] != 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <!-- Question Statement -->
                        <div class="text-base sm:text-lg font-semibold text-slate-900 leading-relaxed">
                            <?= nl2br(e($q['question_text'])) ?>
                        </div>

                        <!-- Options / Answer Area -->
                        <input type="hidden" name="answers[<?= $idx ?>][question_id]" value="<?= $q['id'] ?>">

                        <?php if ($q['type'] === 'mcq'): ?>
                            <div class="space-y-3 pt-2">
                                <?php foreach ($q['options'] as $optIdx => $opt): 
                                    $checked = $selectedOptId == $opt['id'];
                                ?>
                                    <label class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/30 cursor-pointer transition <?= $checked ? 'border-brand-500 bg-brand-50/40 ring-1 ring-brand-500 font-medium' : 'bg-slate-50/50' ?>">
                                        <input type="radio" 
                                               name="answers[<?= $idx ?>][selected_option_id]" 
                                               value="<?= $opt['id'] ?>" 
                                               <?= $checked ? 'checked' : '' ?>
                                               onchange="handleOptionChange(<?= $idx ?>, <?= $q['id'] ?>, <?= $opt['id'] ?>)"
                                               class="w-4 h-4 text-brand-600 focus:ring-brand-500 border-slate-300">
                                        <span class="text-sm text-slate-800 flex-1"><?= e($opt['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2 pt-2">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Type Your Answer Below:</label>
                                <textarea name="answers[<?= $idx ?>][text_answer]" 
                                          rows="4" 
                                          placeholder="Enter your response here..." 
                                          onblur="handleTextBlur(<?= $idx ?>, <?= $q['id'] ?>, this.value)"
                                          class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"><?= e($textAns) ?></textarea>
                            </div>
                        <?php endif; ?>

                        <!-- Card Bottom Navigation -->
                        <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" 
                                    onclick="prevQuestion(<?= $idx ?>)" 
                                    class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition <?= $idx === 0 ? 'invisible' : '' ?>">
                                &larr; Previous
                            </button>

                            <?php if ($idx < count($questions) - 1): ?>
                                <button type="button" 
                                        onclick="nextQuestion(<?= $idx ?>)" 
                                        class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition">
                                    Next Question &rarr;
                                </button>
                            <?php else: ?>
                                <button type="button" 
                                        onclick="promptSubmit()" 
                                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                                    Submit Test ✓
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </form>
        </div>

    </main>

    <!-- Confirmation Modal -->
    <div id="submit-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <h3 class="text-lg font-bold text-slate-900">Finish & Submit Assessment?</h3>
            <p class="text-sm text-slate-600">
                Are you sure you want to finalize your exam submission? Once submitted, answers are permanently locked for grading.
            </p>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeSubmitModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Continue Test
                </button>
                <button type="button" onclick="document.getElementById('cbt-exam-form').submit()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                    Yes, Submit Now
                </button>
            </div>
        </div>
    </div>

    <!-- CBT Player Script: Real-Time Timer, Autosaving & Pagination -->
    <script>
        const totalQuestions = <?= count($questions) ?>;
        const attemptId = <?= $attempt->id ?>;
        let currentQuestionIdx = 0;
        let remainingSeconds = <?= (int)$remainingSeconds ?>;
        const hasTimeLimit = <?= $quiz->hasTimeLimit() ? 'true' : 'false' ?>;

        // Timer Execution
        if (hasTimeLimit && remainingSeconds > 0) {
            const timerEl = document.getElementById('countdown');
            const interval = setInterval(() => {
                remainingSeconds--;
                if (remainingSeconds <= 0) {
                    clearInterval(interval);
                    timerEl.textContent = '00:00';
                    alert('Time limit has expired. Your assessment will now be automatically submitted.');
                    document.getElementById('cbt-exam-form').submit();
                    return;
                }

                const mins = Math.floor(remainingSeconds / 60);
                const secs = remainingSeconds % 60;
                timerEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

                if (remainingSeconds < 120) {
                    document.getElementById('timer-box').classList.remove('bg-slate-900');
                    document.getElementById('timer-box').classList.add('bg-rose-600', 'animate-pulse');
                }
            }, 1000);
        }

        function showQuestion(idx) {
            document.querySelectorAll('.question-card').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById('question-card-' + idx);
            if (target) {
                target.classList.remove('hidden');
                currentQuestionIdx = idx;
            }
        }

        function jumpToQuestion(idx) {
            showQuestion(idx);
        }

        function nextQuestion(idx) {
            if (idx + 1 < totalQuestions) {
                showQuestion(idx + 1);
            }
        }

        function prevQuestion(idx) {
            if (idx - 1 >= 0) {
                showQuestion(idx - 1);
            }
        }

        function promptSubmit() {
            document.getElementById('submit-modal').classList.remove('hidden');
        }

        function closeSubmitModal() {
            document.getElementById('submit-modal').classList.add('hidden');
        }

        // Autosave Handling via AJAX
        function setAutosaveStatus(state, msg) {
            const el = document.getElementById('autosave-status');
            if (state === 'saving') {
                el.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span><span>Saving answer...</span>';
            } else if (state === 'saved') {
                el.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span><span>All changes saved</span>';
            } else {
                el.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-500"></span><span>Save error</span>';
            }
        }

        async function sendAutosave(questionId, selectedOptionId, textAnswer, paletteIdx) {
            setAutosaveStatus('saving');
            try {
                const res = await fetch(`/student/quiz-attempts/${attemptId}/answers`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        question_id: questionId,
                        selected_option_id: selectedOptionId,
                        text_answer: textAnswer
                    })
                });

                if (res.ok) {
                    setAutosaveStatus('saved');
                    markPaletteAnswered(paletteIdx);
                } else {
                    setAutosaveStatus('error');
                }
            } catch (err) {
                setAutosaveStatus('error');
            }
        }

        function handleOptionChange(idx, questionId, optionId) {
            sendAutosave(questionId, optionId, null, idx);
        }

        function handleTextBlur(idx, questionId, textVal) {
            if (textVal.trim() !== '') {
                sendAutosave(questionId, null, textVal, idx);
            }
        }

        function markPaletteAnswered(idx) {
            const btn = document.getElementById('palette-btn-' + idx);
            if (btn) {
                btn.className = 'w-10 h-10 rounded-xl font-bold text-xs flex items-center justify-center transition border bg-emerald-50 border-emerald-300 text-emerald-700';
            }
            updateAnsweredCount();
        }

        function updateAnsweredCount() {
            const answered = document.querySelectorAll('.bg-emerald-50.border-emerald-300').length;
            const el = document.getElementById('answered-count');
            if (el) {
                el.textContent = `${answered} / ${totalQuestions} Answered`;
            }
        }

        // Initialize answered count
        updateAnsweredCount();
    </script>
</body>
</html>
