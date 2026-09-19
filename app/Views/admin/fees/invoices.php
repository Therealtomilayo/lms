<?php
/**
 * Admin Fee Invoices Ledger View
 * 
 * @var \App\Models\FeeInvoice[] $invoices
 * @var array $summary
 * @var array $filters
 * @var \App\Models\AcademicSession[] $sessions
 * @var \App\Models\Term[] $terms
 * @var \App\Models\SchoolClass[] $classes
 * @var \App\Models\AcademicLevel[] $academicLevels
 * @var int $page
 * @var int $totalPages
 * @var int $totalCount
 */
$this->layout('layouts/admin', [
    'title' => 'Student Fee Invoices & Bursary Ledger — Claret LMS',
]);
?>

<div class="space-y-6">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                    Bursary &amp; Accounts Ledger
                </span>
                <span class="text-xs text-slate-500">&bull; Term Invoicing</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-1">Student Fee Invoices</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage student fee billing, track partial payments, log manual bank wires, and issue receipts.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/fees/structures" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                <i data-lucide="scale" class="w-4 h-4 text-slate-500"></i>
                Fee Schedules
            </a>
            <button onclick="document.getElementById('generate-invoices-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition shadow-sm hover:shadow">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                Generate Term Invoices
            </button>
        </div>
    </div>

    <!-- Bursary Financial Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Invoices</span>
                <span class="size-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 font-mono mt-2">
                <?= number_format($summary['total_invoices'] ?? 0) ?>
            </div>
            <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
                <span class="text-emerald-600 font-bold"><?= $summary['paid_count'] ?? 0 ?> Paid</span> &bull; 
                <span class="text-amber-600 font-bold"><?= $summary['partial_count'] ?? 0 ?> Partial</span> &bull; 
                <span class="text-rose-600 font-bold"><?= $summary['unpaid_count'] ?? 0 ?> Unpaid</span>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Billed</span>
                <span class="size-8 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 font-mono mt-2">
                ₦<?= number_format($summary['total_billed'] ?? 0.0, 2) ?>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Gross termly tuition &amp; levies</div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Collected</span>
                <span class="size-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-2">
                ₦<?= number_format($summary['total_collected'] ?? 0.0, 2) ?>
            </div>
            <div class="text-[11px] text-emerald-600 font-bold mt-1">
                <?= $summary['total_billed'] > 0 ? round(($summary['total_collected'] / $summary['total_billed']) * 100, 1) : 0 ?>% Collection Rate
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Outstanding Arrears</span>
                <span class="size-8 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="text-2xl font-black text-rose-700 font-mono mt-2">
                ₦<?= number_format($summary['total_outstanding'] ?? 0.0, 2) ?>
            </div>
            <div class="text-[11px] text-rose-600 font-bold mt-1">Pending parent payments</div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="/admin/fees/invoices" class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex items-center gap-1.5 font-bold text-slate-700">
                <i data-lucide="filter" class="w-4 h-4 text-slate-400"></i>
                Filter Ledger:
            </div>
            <select name="session_id" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
                <option value="">-- All Sessions --</option>
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s->id ?>" <?= ($filters['session_id'] ?? 0) === $s->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="term_id" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
                <option value="">-- All Terms --</option>
                <?php foreach ($terms as $t): ?>
                    <option value="<?= $t->id ?>" <?= ($filters['term_id'] ?? 0) === $t->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="class_id" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
                <option value="">-- All Classes --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c->id ?>" <?= ($filters['class_id'] ?? 0) === $c->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
                <option value="">-- All Statuses --</option>
                <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Fully Paid</option>
                <option value="partially_paid" <?= ($filters['status'] ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                <option value="unpaid" <?= ($filters['status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
            </select>
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Search student name, admission #, or invoice #..." class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder:text-slate-400 text-xs focus:ring-2 focus:ring-brand-500/20 focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-1.5 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 transition">
                Search
            </button>
            <?php if (!empty(array_filter($filters))): ?>
                <a href="/admin/fees/invoices" class="text-slate-500 hover:text-slate-700 underline font-semibold">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Invoices Data Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($invoices)): ?>
            <div class="p-12 text-center">
                <div class="size-16 rounded-3xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-4">
                    <i data-lucide="file-text" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">No Student Invoices Found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">No invoices match your current filter criteria or no term bills have been generated yet.</p>
                <button onclick="document.getElementById('generate-invoices-modal').classList.remove('hidden')" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    Generate Invoices Now
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3.5 px-5">Invoice #</th>
                            <th class="py-3.5 px-5">Student / Admission</th>
                            <th class="py-3.5 px-5">Class &amp; Term</th>
                            <th class="py-3.5 px-5 text-right">Total Billed</th>
                            <th class="py-3.5 px-5 text-right">Paid</th>
                            <th class="py-3.5 px-5 text-right">Balance Due</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($invoices as $inv): ?>
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="py-3.5 px-5 font-mono font-bold text-slate-900">
                                    <a href="/admin/fees/invoices/<?= $inv->id ?>" class="hover:text-brand-600">
                                        <?= htmlspecialchars($inv->invoiceNumber, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($inv->studentName ?? 'Student', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="font-mono text-[11px] text-slate-400"><?= htmlspecialchars($inv->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="text-slate-800 font-medium"><?= htmlspecialchars($inv->className ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($inv->sessionName . ' &bull; ' . $inv->termName, ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-slate-900">
                                    ₦<?= number_format($inv->totalAmount, 2) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-emerald-700">
                                    ₦<?= number_format($inv->amountPaid, 2) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold <?= $inv->balanceDue > 0 ? 'text-rose-700' : 'text-slate-400' ?>">
                                    ₦<?= number_format($inv->balanceDue, 2) ?>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <?php if ($inv->isPaid()): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Paid
                                        </span>
                                    <?php elseif ($inv->isPartiallyPaid()): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                            Partial
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            Unpaid
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/admin/fees/invoices/<?= $inv->id ?>" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-bold transition">
                                            Inspect
                                        </a>
                                        <a href="/admin/fees/invoices/<?= $inv->id ?>/print" target="_blank" title="Print Invoice" class="p-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="border-t border-slate-100 p-4 flex items-center justify-between text-xs text-slate-500">
                    <div>Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalCount ?> total records)</div>
                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($filters)) ?>" class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 font-bold">&larr; Prev</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($filters)) ?>" class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 font-bold">Next &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Generate Invoices -->
<div id="generate-invoices-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-lg w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900">Batch Generate Term Invoices</h3>
                <p class="text-xs text-slate-500">Issue itemized bills to enrolled students based on active fee schedules.</p>
            </div>
            <button onclick="document.getElementById('generate-invoices-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/fees/invoices/generate" class="space-y-4 text-xs">
            <?= \App\Core\Csrf::field() ?>

            <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] leading-relaxed">
                <span class="font-bold block mb-0.5">Idempotency Guarantee:</span>
                Students who have already been billed for the selected term will be automatically skipped. No double invoicing can occur.
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Academic Session <span class="text-rose-500">*</span></label>
                    <select name="session_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Select Session --</option>
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

            <div>
                <label class="block font-bold text-slate-700 mb-1">Target Scope</label>
                <div class="grid grid-cols-2 gap-3">
                    <select name="academic_level_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Entire School / Level --</option>
                        <?php foreach ($academicLevels as $al): ?>
                            <option value="<?= $al->id ?>"><?= htmlspecialchars($al->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="class_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                        <option value="">-- Or Specific Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Leave both empty to generate invoices for all actively enrolled students across the entire school.</p>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" onclick="document.getElementById('generate-invoices-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition shadow-xs">
                    Start Batch Invoicing
                </button>
            </div>
        </form>
    </div>
</div>
