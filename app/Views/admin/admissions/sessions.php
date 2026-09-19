<?php
/**
 * Admin Admission Sessions Management View
 *
 * @var array $sessions
 * @var array $academicSessions
 * @var string|null $success
 * @var string|null $error
 */
$csrfToken = \App\Core\Session::get('csrf_token', '');
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="/admin/admissions/applications" class="hover:text-brand-600 transition flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Admissions
                </a>
            </div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-brand-500/10 text-brand-600">
                    <i data-lucide="calendar-cog" class="w-5 h-5"></i>
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Admission Sessions</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">Configure admission windows, application fees per prospective ward, and instructions.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('new-session-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs transition shadow-sm shadow-brand-500/20">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Configure New Session</span>
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium text-emerald-800"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium text-rose-800"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <!-- Sessions List -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <?php if (empty($sessions)): ?>
            <div class="p-12 text-center text-slate-400 text-xs">
                No admission sessions configured yet.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Session Title</th>
                            <th class="py-3.5 px-5">Academic Year</th>
                            <th class="py-3.5 px-5">Fee (Per Ward)</th>
                            <th class="py-3.5 px-5">Enrollment Window</th>
                            <th class="py-3.5 px-5">Portal Status</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($sessions as $s): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($s->title, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($s->instructions)): ?>
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs"><?= htmlspecialchars($s->instructions, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-5 font-medium text-slate-700">
                                    <?= htmlspecialchars($s->academicSessionName ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="font-mono font-black text-slate-900"><?= htmlspecialchars($s->currency, ENT_QUOTES, 'UTF-8') ?> <?= number_format($s->applicationFee, 2) ?></span>
                                    <span class="text-[10px] text-slate-400 block font-normal">per ward</span>
                                </td>
                                <td class="py-4 px-5 text-slate-600 text-[11px]">
                                    <div>Opens: <span class="font-semibold text-slate-800"><?= date('M d, Y', strtotime($s->opensAt)) ?></span></div>
                                    <div>Closes: <span class="font-semibold text-slate-800"><?= date('M d, Y', strtotime($s->closesAt)) ?></span></div>
                                </td>
                                <td class="py-4 px-5">
                                    <?php if ($s->isActive && $s->isOpen()): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Open & Active
                                        </span>
                                    <?php elseif ($s->isActive): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Active (Dates Closed)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <form method="POST" action="/admin/admissions/sessions/<?= $s->id ?>" class="inline">
                                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="is_active" value="<?= $s->isActive ? '0' : '1' ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold text-xs transition">
                                            <?= $s->isActive ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Session Modal -->
<div id="new-session-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-lg w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900">Configure Admission Session</h3>
            <button onclick="document.getElementById('new-session-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/admissions/sessions" class="space-y-4 text-xs">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label class="block font-bold text-slate-700 mb-1">Academic Session Year <span class="text-rose-500">*</span></label>
                <select name="academic_session_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition bg-white">
                    <option value="">-- Select Academic Session --</option>
                    <?php foreach ($academicSessions as $as): ?>
                        <option value="<?= $as->id ?>"><?= htmlspecialchars($as->name, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Admission Session Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="e.g. 2026/2027 Claret Academic Admissions" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Application Fee (per ward) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="application_fee" value="10000.00" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Currency</label>
                    <input type="text" name="currency" value="NGN" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Opens At <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="opens_at" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Closes At <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="closes_at" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Instructions / Welcome Note</label>
                <textarea name="instructions" rows="3" placeholder="Special admissions guidance for prospective parents and applicants..." class="w-full p-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="is_active" name="is_active" value="1" checked class="w-4 h-4 text-brand-600 rounded border-slate-300 focus:ring-brand-500">
                <label for="is_active" class="font-bold text-slate-700 cursor-pointer">Set as Active Enrollment Session</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('new-session-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition shadow-sm shadow-brand-500/20">
                    Save Session
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
