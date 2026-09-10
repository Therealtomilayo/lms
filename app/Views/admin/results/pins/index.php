<?php
/**
 * Admin Result Access Scratch-Card PIN Management View
 * 
 * @var array $pins
 * @var int $total
 * @var int $page
 * @var int $limit
 * @var array $stats
 * @var array $classes
 * @var array $sessions
 * @var object|null $currentSession
 * @var array $terms
 * @var string|null $status
 * @var int|null $termId
 * @var string|null $search
 */
$this->layout('layouts/admin');
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="/admin/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                <span>&rsaquo;</span>
                <a href="/admin/results/review" class="hover:text-brand-600 transition">Results Review</a>
                <span>&rsaquo;</span>
                <span class="text-brand-600">Scratch-Card PINs</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Result Access Scratch-Card PINs</h1>
            <p class="text-xs text-slate-500 mt-1">Generate, audit, print, and manage security PINs that gate terminal report cards (SRS §38–§40).</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/admin/results/pins/export" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            <a href="/admin/results/pins/print" target="_blank" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Cards
            </a>
            <button type="button" 
                    onclick="document.getElementById('generate-pin-modal').classList.remove('hidden')" 
                    class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Generate PINs
            </button>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total PINs Created</span>
            <div class="text-2xl font-black text-slate-900 mt-1"><?= $stats['total_pins'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Physical & online cards</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Available / Unused</span>
            <div class="text-2xl font-black text-emerald-600 mt-1"><?= $stats['unused_count'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Ready for sale / allocation</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Redeemed / Bound</span>
            <div class="text-2xl font-black text-brand-600 mt-1"><?= $stats['redeemed_count'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Actively viewed by pupils</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Purchased Online</span>
            <div class="text-2xl font-black text-purple-600 mt-1"><?= $stats['online_purchased_count'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Paystack automated cards</p>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="/admin/results/pins" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <input type="text" 
                   name="q" 
                   value="<?= e($search ?? '') ?>" 
                   placeholder="Search serial, PIN, student, or admission..."
                   class="text-xs border border-slate-300 rounded-xl px-3.5 py-2 w-full sm:w-64 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">

            <select name="status" class="text-xs border border-slate-300 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-brand-500">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="depleted" <?= $status === 'depleted' ? 'selected' : '' ?>>Depleted (5/5)</option>
                <option value="revoked" <?= $status === 'revoked' ? 'selected' : '' ?>>Revoked</option>
            </select>

            <select name="term_id" class="text-xs border border-slate-300 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-brand-500">
                <option value="">All Academic Terms</option>
                <?php foreach ($terms as $t): ?>
                    <option value="<?= $t->id ?>" <?= $termId === $t->id ? 'selected' : '' ?>><?= e($t->name) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition">
                Filter
            </button>
            <?php if (!empty($status) || !empty($termId) || !empty($search)): ?>
                <a href="/admin/results/pins" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            <?php endif; ?>
        </form>
        <span class="text-xs text-slate-500 font-medium">Showing <?= count($pins) ?> of <?= $total ?> PINs</span>
    </div>

    <!-- PINs Roster Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($pins)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No Scratch-Card PINs matching your criteria.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Serial Number</th>
                            <th class="py-3.5 px-4">PIN Code</th>
                            <th class="py-3.5 px-4">Assigned / Bound Student</th>
                            <th class="py-3.5 px-4">Term Lock</th>
                            <th class="py-3.5 px-4">Views Used</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Created</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($pins as $p): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    <?= e($p->serialNumber) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold">
                                    <span id="pin-val-<?= $p->id ?>" class="text-slate-800"><?= e($p->getMaskedPin()) ?></span>
                                    <button type="button" 
                                            onclick="togglePinMask(<?= $p->id ?>, '<?= e($p->getFormattedPin()) ?>', '<?= e($p->getMaskedPin()) ?>')"
                                            class="text-[10px] text-brand-600 hover:text-brand-800 underline ml-1.5 cursor-pointer">
                                        reveal
                                    </button>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($p->studentId): ?>
                                        <span class="font-bold text-slate-900 block"><?= e($p->studentName ?? 'Student #' . $p->studentId) ?></span>
                                        <span class="text-[10px] font-mono text-slate-400"><?= e($p->studentAdmissionNumber ?? '') ?></span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                            Unassigned (Binds on use)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <?= e($p->termName ?? 'Any Active Term') ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-brand-500" style="width: <?= ($p->timesUsed / max(1, $p->maxUses)) * 100 ?>%"></div>
                                        </div>
                                        <span class="font-mono text-[11px] font-bold <?= $p->timesUsed >= $p->maxUses ? 'text-rose-600' : 'text-slate-700' ?>">
                                            <?= $p->timesUsed ?> / <?= $p->maxUses ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($p->status === 'active'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                                    <?php elseif ($p->status === 'depleted'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Depleted</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">Revoked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap text-[11px]">
                                    <?= date('M j, Y', strtotime($p->createdAt)) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if ($p->status === 'active'): ?>
                                        <form method="POST" action="/admin/results/pins/<?= $p->id ?>/revoke" class="inline" onsubmit="return confirm('Revoke this Scratch-Card PIN? It will immediately stop working.')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 text-[11px] font-bold underline cursor-pointer">
                                                Revoke
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Generate Scratch-Card PINs -->
<div id="generate-pin-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Generate Result Scratch-Card PINs</h3>
            <button type="button" onclick="document.getElementById('generate-pin-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="/admin/results/pins/generate" class="p-6 space-y-4">
            <?= csrf_field() ?>

            <!-- Mode Selector -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Generation Mode</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="mode" value="bulk" checked onchange="toggleGenMode('bulk')" class="text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">Bulk Unassigned</span>
                            <span class="text-[10px] text-slate-500 block">Binds to student on use</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="mode" value="class" onchange="toggleGenMode('class')" class="text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">Whole Class Cohort</span>
                            <span class="text-[10px] text-slate-500 block">1 PIN per enrolled pupil</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Bulk Mode Panel -->
            <div id="panel-mode-bulk">
                <label class="block text-xs font-bold text-slate-700 mb-1">Quantity of PINs</label>
                <input type="number" name="qty" value="25" min="1" max="500" class="w-full text-xs border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-brand-500">
                <p class="text-[11px] text-slate-400 mt-1">Generates cryptographic cards for print distribution or sale.</p>
            </div>

            <!-- Class Mode Panel -->
            <div id="panel-mode-class" class="hidden">
                <label class="block text-xs font-bold text-slate-700 mb-1">Target Class Cohort</label>
                <select name="class_id" class="w-full text-xs border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-brand-500">
                    <option value="">— Select Class Cohort —</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c->id ?>"><?= e($c->name) ?><?= $c->sectionArm ? ' (' . e($c->sectionArm) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Configuration Parameters -->
            <div class="grid grid-cols-2 gap-3 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Max Views per PIN</label>
                    <input type="number" name="max_uses" value="5" min="1" max="50" class="w-full text-xs border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lock to Term</label>
                    <select name="term_id" class="w-full text-xs border border-slate-300 rounded-xl p-3 focus:ring-2 focus:ring-brand-500">
                        <option value="">— Current Active Term —</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t->id ?>"><?= e($t->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('generate-pin-modal').classList.add('hidden')" class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-sm transition">
                    Generate Batch Now
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleGenMode(mode) {
    if (mode === 'class') {
        document.getElementById('panel-mode-class').classList.remove('hidden');
        document.getElementById('panel-mode-bulk').classList.add('hidden');
    } else {
        document.getElementById('panel-mode-class').classList.add('hidden');
        document.getElementById('panel-mode-bulk').classList.remove('hidden');
    }
}

function togglePinMask(id, formatted, masked) {
    const el = document.getElementById('pin-val-' + id);
    if (el.textContent === masked) {
        el.textContent = formatted;
    } else {
        el.textContent = masked;
    }
}
</script>
