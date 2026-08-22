<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/question-bank<?= $selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : '' ?>" class="text-slate-400 hover:text-emerald-600 transition">Question Bank</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Bulk Import</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Bulk Question Importer
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Paste dozens of questions simultaneously using the standard formatted text template.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/teacher/question-bank/create<?= $selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : '' ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Single Question Form</span>
                </a>

                <?php $this->include('components/button', [
                    'label' => 'Back to Bank',
                    'variant' => 'secondary',
                    'href' => '/teacher/question-bank' . ($selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : ''),
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Format Guide Card -->
    <div class="bg-emerald-900 text-emerald-50 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-200">Bulk Import Format Guide</h3>
                </div>
                <p class="text-xs text-emerald-100/90 leading-relaxed max-w-2xl">
                    Separate each question with a blank line. For multiple choice questions, prefix options with <strong>A)</strong>, <strong>B)</strong>, <strong>C)</strong>, <strong>D)</strong> and specify the correct answer with <strong>ANSWER: B</strong>.
                </p>
            </div>

            <button type="button" onclick="copySampleTemplate()" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-800 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex-shrink-0">
                <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <span>Copy Sample Template</span>
            </button>
        </div>

        <div class="mt-4 p-4 bg-emerald-950/70 rounded-xl border border-emerald-800 font-mono text-xs text-emerald-200 leading-relaxed overflow-x-auto">
            What is the powerhouse of the cell?<br>
            A) Nucleus<br>
            B) Mitochondria<br>
            C) Ribosome<br>
            D) Endoplasmic Reticulum<br>
            ANSWER: B<br>
            TOPIC: Cell Biology<br>
            POINTS: 1.0<br>
            <br>
            Which gas is essential for human respiration?<br>
            A) Carbon Dioxide<br>
            B) Nitrogen<br>
            C) Oxygen<br>
            D) Hydrogen<br>
            ANSWER: C<br>
            TOPIC: General Science
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/question-bank/bulk" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Subject & Global Settings Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Subject -->
                <div class="md:col-span-1">
                    <label for="subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Target Subject <span class="text-rose-500">*</span>
                    </label>
                    <select name="subject_id" id="subject_id" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= (int)$s->id ?>" <?= $selectedSubjectId === (int)$s->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name) ?> (<?= htmlspecialchars($s->code) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Default Topic -->
                <div class="md:col-span-1">
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Default Topic (If omitted in text)
                    </label>
                    <input type="text" name="topic" id="topic" maxlength="100"
                           value="<?= htmlspecialchars((string)old('topic', '')) ?>"
                           placeholder="e.g. General Revision"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>

                <!-- Default Points -->
                <div class="md:col-span-1">
                    <label for="default_points" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Default Points Weight
                    </label>
                    <div class="relative">
                        <input type="number" step="0.25" min="0.25" max="100" name="default_points" id="default_points"
                               value="<?= htmlspecialchars((string)old('default_points', '1.00')) ?>"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 pl-3 pr-12 font-bold text-slate-900 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">PTS</span>
                    </div>
                </div>
            </div>

            <!-- Bulk Textarea -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="bulk_text" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Paste Questions Text Block <span class="text-rose-500">*</span>
                    </label>
                    <span id="detected-counter" class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
                        0 Questions Detected
                    </span>
                </div>
                <textarea name="bulk_text" id="bulk_text" rows="14" required
                          oninput="updateQuestionCounter()"
                          placeholder="Paste questions here formatted like the guide above..."
                          class="w-full rounded-xl border border-slate-300 text-xs font-mono focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-3 px-4 text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('bulk_text', '')) ?></textarea>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/question-bank' . ($selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : '')
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Import Questions to Bank',
                    'variant' => 'primary',
                    'type' => 'submit',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>'
                ]); ?>
            </div>
        </form>
    </div>
</div>

<script>
const sampleTemplate = `What is the powerhouse of the cell?
A) Nucleus
B) Mitochondria
C) Ribosome
D) Endoplasmic Reticulum
ANSWER: B
TOPIC: Cell Biology
POINTS: 1.0

Which planet is known as the Red Planet?
A) Venus
B) Mars
C) Jupiter
D) Saturn
ANSWER: B
TOPIC: Solar System
POINTS: 1.0

What is the chemical formula for table salt?
A) H2O
B) CO2
C) NaCl
D) CaCO3
ANSWER: C
TOPIC: Chemistry
POINTS: 1.0`;

function copySampleTemplate() {
    const textarea = document.getElementById('bulk_text');
    textarea.value = sampleTemplate;
    updateQuestionCounter();
    textarea.focus();
}

function updateQuestionCounter() {
    const text = document.getElementById('bulk_text').value.trim();
    if (!text) {
        document.getElementById('detected-counter').textContent = '0 Questions Detected';
        return;
    }
    const blocks = text.split(/\n\s*\n/).filter(b => b.trim().length > 0);
    const count = blocks.length;
    document.getElementById('detected-counter').textContent = count + ' Question' + (count === 1 ? '' : 's') + ' Detected';
}

document.addEventListener('DOMContentLoaded', updateQuestionCounter);
</script>
