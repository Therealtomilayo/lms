<?php
/**
 * Admin Fee Structures & Schedules View
 * 
 * @var \App\Models\FeeStructure[] $structures
 * @var \App\Models\AcademicSession[] $sessions
 * @var \App\Models\Term[] $terms
 * @var \App\Models\AcademicLevel[] $academicLevels
 * @var \App\Models\SchoolClass[] $classes
 * @var \App\Models\FeeCategory[] $categories
 * @var int $selectedSessionId
 * @var int $selectedTermId
 */
$this->layout('layouts/admin', [
    'title' => 'Fee Schedules & Structures — Claret LMS',
]);
?>

<div class="space-y-6">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                    Institutional Bursary
                </span>
                <span class="text-xs text-slate-500">&bull; Tuition &amp; Levies</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-1">Fee Schedules &amp; Structures</h1>
            <p class="text-xs text-slate-500 mt-0.5">Configure termly fee categories, tuition rates, and result clearance rules per academic level.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/fees/invoices" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                <i data-lucide="file-text" class="w-4 h-4 text-slate-500"></i>
                Student Invoices Ledger
            </a>
            <button onclick="document.getElementById('new-structure-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition shadow-sm hover:shadow">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                New Fee Schedule
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="/admin/fees/structures" class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex items-center gap-2 font-bold text-slate-600">
                <i data-lucide="filter" class="w-4 h-4 text-slate-400"></i>
                Filter Schedules:
            </div>
            <select name="session_id" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
                <option value="">-- All Sessions --</option>
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s->id ?>" <?= $selectedSessionId === $s->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="term_id" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
                <option value="">-- All Terms --</option>
                <?php foreach ($terms as $t): ?>
                    <option value="<?= $t->id ?>" <?= $selectedTermId === $t->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 transition">
                Apply Filter
            </button>
            <?php if ($selectedSessionId || $selectedTermId): ?>
                <a href="/admin/fees/structures" class="text-slate-500 hover:text-slate-700 underline font-semibold">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Fee Schedules List -->
    <?php if (empty($structures)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="size-16 rounded-3xl bg-brand-50 border border-brand-100 flex items-center justify-center mx-auto text-brand-600 mb-4">
                <i data-lucide="scale" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Fee Schedules Configured</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">Configure your first termly fee structure to define tuition, PTA levies, and development fees for automatic student billing.</p>
            <button onclick="document.getElementById('new-structure-modal').classList.remove('hidden')" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                Create First Fee Schedule
            </button>
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($structures as $st): ?>
                <div class="bg-white rounded-3xl border <?= $st->isActive ? 'border-slate-200' : 'border-slate-200/60 bg-slate-50/50 opacity-80' ?> p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider <?= $st->isActive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= $st->isActive ? 'Active Schedule' : 'Inactive' ?>
                                    </span>
                                    <?php if ($st->dueDate): ?>
                                        <span class="text-[11px] text-slate-400 font-medium">Due: <?= date('M d, Y', strtotime($st->dueDate)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-base font-black text-slate-900 mt-1"><?= htmlspecialchars($st->title, ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" onclick="openEditStructureModal(<?= $st->id ?>)" title="Edit Schedule" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 hover:text-brand-700 transition">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <form method="POST" action="/admin/fees/structures/<?= $st->id ?>/toggle">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button type="submit" title="<?= $st->isActive ? 'Deactivate' : 'Activate' ?>" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 transition">
                                        <i data-lucide="<?= $st->isActive ? 'toggle-right' : 'toggle-left' ?>" class="w-4 h-4 <?= $st->isActive ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 text-[11px] text-slate-600">
                            <span class="px-2 py-1 rounded-lg bg-slate-100 font-bold">
                                <?= htmlspecialchars($st->sessionName ?? 'All Sessions', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="px-2 py-1 rounded-lg bg-slate-100 font-bold">
                                <?= htmlspecialchars($st->termName ?? 'All Terms', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="px-2 py-1 rounded-lg bg-brand-50 text-brand-700 font-bold border border-brand-100">
                                <?= htmlspecialchars($st->levelName ?? ($st->className ?? 'School-Wide'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <!-- Itemized breakdown with Result-Lock Badges -->
                        <div class="border-t border-slate-100 pt-3 space-y-1.5">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fee Components</div>
                            <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                                <?php foreach ($st->items as $item): ?>
                                    <div class="flex items-center justify-between text-xs py-0.5">
                                        <div class="flex items-center gap-1.5 truncate pr-2">
                                            <span class="text-slate-700 font-medium truncate">
                                                <?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <?php if ($item->isRequiredForResult): ?>
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Mandatory for Academic Report Card Clearance">
                                                    <i data-lucide="lock" class="w-2.5 h-2.5"></i> Lock Result
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="font-bold text-slate-900 font-mono flex-shrink-0">
                                            ₦<?= number_format($item->amount, 2) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-3 mt-4 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Schedule Fee</span>
                            <span class="text-lg font-black text-brand-700 font-mono">₦<?= number_format($st->getTotalAmount(), 2) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <form method="POST" action="/admin/fees/invoices/generate" class="inline">
                                <?= \App\Core\Csrf::field() ?>
                                <input type="hidden" name="session_id" value="<?= $st->sessionId ?>">
                                <input type="hidden" name="term_id" value="<?= $st->termId ?>">
                                <input type="hidden" name="academic_level_id" value="<?= $st->academicLevelId ?? '' ?>">
                                <input type="hidden" name="class_id" value="<?= $st->classId ?? '' ?>">
                                <button type="submit" title="Generate invoices for enrolled students matching this schedule" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white text-slate-700 hover:text-brand-600 text-xs font-bold transition flex items-center gap-1 shadow-2xs">
                                    <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-500"></i>
                                    Issue Invoices
                                </button>
                            </form>
                            <a href="/admin/fees/invoices?session_id=<?= $st->sessionId ?>&term_id=<?= $st->termId ?>" class="text-xs font-bold text-brand-600 hover:text-brand-800 flex items-center gap-1">
                                Invoices &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: New Fee Structure -->
<div id="new-structure-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-2xl w-full p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900">Configure Termly Fee Schedule</h3>
                <p class="text-xs text-slate-500">Define academic fees, component levies, and result lock gates.</p>
            </div>
            <button onclick="document.getElementById('new-structure-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/fees/structures" class="space-y-4 text-xs">
            <?= \App\Core\Csrf::field() ?>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Schedule Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="e.g. 2026/2027 1st Term Senior Secondary Fees" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Academic Session <span class="text-rose-500">*</span></label>
                    <select name="session_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Select Academic Session --</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= $s->id ?>"><?= htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Term <span class="text-rose-500">*</span></label>
                    <select name="term_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Select Term --</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t->id ?>"><?= htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Target Academic Level</label>
                    <select name="academic_level_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- School-Wide (All Levels) --</option>
                        <?php foreach ($academicLevels as $al): ?>
                            <option value="<?= $al->id ?>"><?= htmlspecialchars($al->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-0.5">Leave blank if this schedule applies school-wide.</p>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Due Date</label>
                    <input type="date" name="due_date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
            </div>

            <!-- Dynamic Fee Items Repeater with Lock Result selector -->
            <div class="border-t border-slate-100 pt-3 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-slate-800 text-xs">Fee Breakdown Components</span>
                        <p class="text-[10px] text-slate-400">Check "Lock Result" to require settlement before students/parents can access term report cards.</p>
                    </div>
                    <button type="button" onclick="addFeeItemRow('fee-items-container')" class="text-brand-600 hover:text-brand-800 font-bold text-xs flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Component
                    </button>
                </div>

                <div id="fee-items-container" class="space-y-2">
                    <div class="grid grid-cols-12 gap-2 items-center fee-row">
                        <div class="col-span-3">
                            <select name="category_id[]" required class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs bg-white text-slate-700">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="text" name="item_name[]" value="Tuition Fee" required placeholder="Component Name" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" min="0" name="item_amount[]" value="75000.00" required placeholder="Amount (₦)" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800">
                        </div>
                        <div class="col-span-2 text-center">
                            <input type="hidden" name="is_required_for_result[]" value="1" class="req-result-hidden">
                            <label class="inline-flex items-center gap-1 cursor-pointer font-bold text-slate-700" title="Result is locked until this fee is settled">
                                <input type="checkbox" checked onchange="this.previousElementSibling.value = this.checked ? '1' : '0'" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-[10px]">Lock Result</span>
                            </label>
                        </div>
                        <div class="col-span-1 text-center">
                            <button type="button" onclick="removeFeeRow(this)" class="p-1 rounded text-slate-400 hover:text-rose-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-center fee-row">
                        <div class="col-span-3">
                            <select name="category_id[]" required class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs bg-white text-slate-700">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat->id ?>" <?= $cat->id === 3 ? 'selected' : '' ?>><?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="text" name="item_name[]" value="ICT & E-Learning Levy" required placeholder="Component Name" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800">
                        </div>
                        <div class="col-span-3">
                            <input type="number" step="0.01" min="0" name="item_amount[]" value="15000.00" required placeholder="Amount (₦)" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800">
                        </div>
                        <div class="col-span-2 text-center">
                            <input type="hidden" name="is_required_for_result[]" value="1" class="req-result-hidden">
                            <label class="inline-flex items-center gap-1 cursor-pointer font-bold text-slate-700" title="Result is locked until this fee is settled">
                                <input type="checkbox" checked onchange="this.previousElementSibling.value = this.checked ? '1' : '0'" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-[10px]">Lock Result</span>
                            </label>
                        </div>
                        <div class="col-span-1 text-center">
                            <button type="button" onclick="removeFeeRow(this)" class="p-1 rounded text-slate-400 hover:text-rose-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-brand-50/80 border border-brand-200/80 rounded-2xl flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-800">
                    <input type="checkbox" name="generate_invoices" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Issue invoices immediately to all currently enrolled students</span>
                </label>
                <span class="text-[10px] text-brand-700 bg-white px-2 py-0.5 rounded-full border border-brand-200 font-bold hidden sm:inline">
                    Automatic Billing
                </span>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Active schedule
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('new-structure-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition shadow-xs">
                        Save Fee Schedule
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Fee Structure -->
<div id="edit-structure-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-2xl w-full p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900">Edit Fee Schedule</h3>
                <p class="text-xs text-slate-500">Update tuition items and result clearance requirements.</p>
            </div>
            <button onclick="document.getElementById('edit-structure-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="edit-structure-form" method="POST" action="" class="space-y-4 text-xs">
            <?= \App\Core\Csrf::field() ?>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Schedule Title <span class="text-rose-500">*</span></label>
                <input type="text" id="edit-title" name="title" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Academic Session <span class="text-rose-500">*</span></label>
                    <select id="edit-session-id" name="session_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Select Session --</option>
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= $s->id ?>"><?= htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Term <span class="text-rose-500">*</span></label>
                    <select id="edit-term-id" name="term_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Select Term --</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t->id ?>"><?= htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Target Academic Level</label>
                    <select id="edit-level-id" name="academic_level_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- School-Wide (All Levels) --</option>
                        <?php foreach ($academicLevels as $al): ?>
                            <option value="<?= $al->id ?>"><?= htmlspecialchars($al->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Due Date</label>
                    <input type="date" id="edit-due-date" name="due_date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                </div>
            </div>

            <!-- Dynamic Fee Items Repeater -->
            <div class="border-t border-slate-100 pt-3 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-slate-800 text-xs">Fee Breakdown Components</span>
                        <p class="text-[10px] text-slate-400">Select which fees are mandatory for report card result clearance.</p>
                    </div>
                    <button type="button" onclick="addFeeItemRow('edit-fee-items-container')" class="text-brand-600 hover:text-brand-800 font-bold text-xs flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Component
                    </button>
                </div>

                <div id="edit-fee-items-container" class="space-y-2">
                    <!-- Injected dynamically via openEditStructureModal -->
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                    <input type="checkbox" id="edit-is-active" name="is_active" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Active schedule
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('edit-structure-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition shadow-xs">
                        Update Fee Schedule
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<template id="fee-row-template">
    <div class="grid grid-cols-12 gap-2 items-center fee-row">
        <div class="col-span-3">
            <select name="category_id[]" required class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs bg-white text-slate-700 cat-select">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-span-3">
            <input type="text" name="item_name[]" required placeholder="Component Name" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 name-input">
        </div>
        <div class="col-span-3">
            <input type="number" step="0.01" min="0" name="item_amount[]" required placeholder="Amount (₦)" class="w-full px-2.5 py-2 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 amt-input">
        </div>
        <div class="col-span-2 text-center">
            <input type="hidden" name="is_required_for_result[]" value="1" class="req-result-hidden">
            <label class="inline-flex items-center gap-1 cursor-pointer font-bold text-slate-700" title="Result is locked until this fee is settled">
                <input type="checkbox" checked onchange="this.previousElementSibling.value = this.checked ? '1' : '0'" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 req-checkbox">
                <span class="text-[10px]">Lock Result</span>
            </label>
        </div>
        <div class="col-span-1 text-center">
            <button type="button" onclick="removeFeeRow(this)" class="p-1 rounded text-slate-400 hover:text-rose-600">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</template>

<script>
function addFeeItemRow(containerId) {
    const container = document.getElementById(containerId);
    const template = document.getElementById('fee-row-template');
    if (!container || !template) return;

    const clone = template.content.cloneNode(true);
    container.appendChild(clone);
    if (window.lucide) lucide.createIcons();
}

function removeFeeRow(btn) {
    const container = btn.closest('#fee-items-container') || btn.closest('#edit-fee-items-container');
    const rows = container.querySelectorAll('.fee-row');
    if (rows.length > 1) {
        btn.closest('.fee-row').remove();
    }
}

async function openEditStructureModal(structureId) {
    const modal = document.getElementById('edit-structure-modal');
    const form = document.getElementById('edit-structure-form');
    const container = document.getElementById('edit-fee-items-container');
    const template = document.getElementById('fee-row-template');

    container.innerHTML = '<div class="text-slate-400 text-center py-4">Loading structure details...</div>';
    modal.classList.remove('hidden');

    try {
        const res = await fetch(`/admin/fees/structures/${structureId}/data`);
        if (!res.ok) throw new Error('Failed to load schedule data');
        const data = await res.json();

        form.action = `/admin/fees/structures/${structureId}/update`;
        document.getElementById('edit-title').value = data.title || '';
        document.getElementById('edit-session-id').value = data.session_id || '';
        document.getElementById('edit-term-id').value = data.term_id || '';
        document.getElementById('edit-level-id').value = data.academic_level_id || '';
        document.getElementById('edit-due-date').value = data.due_date || '';
        document.getElementById('edit-is-active').checked = !!data.is_active;

        container.innerHTML = '';
        if (data.items && data.items.length > 0) {
            data.items.forEach(it => {
                const clone = template.content.cloneNode(true);
                clone.querySelector('.cat-select').value = it.fee_category_id;
                clone.querySelector('.name-input').value = it.name;
                clone.querySelector('.amt-input').value = parseFloat(it.amount).toFixed(2);
                
                const hiddenReq = clone.querySelector('.req-result-hidden');
                const checkReq = clone.querySelector('.req-checkbox');
                const isReq = it.is_required_for_result !== false && it.is_required_for_result !== 0;
                hiddenReq.value = isReq ? '1' : '0';
                checkReq.checked = isReq;

                container.appendChild(clone);
            });
        } else {
            addFeeItemRow('edit-fee-items-container');
        }

        if (window.lucide) lucide.createIcons();
    } catch (err) {
        alert('Could not fetch fee structure details: ' + err.message);
        modal.classList.add('hidden');
    }
}
</script>
