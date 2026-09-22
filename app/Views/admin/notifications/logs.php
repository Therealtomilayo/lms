<div class="space-y-6 pb-12">
    <!-- Top Navigation Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 sm:p-3">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Gateway Navigation Tabs">
            <a href="/admin/notifications/gateway"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Gateway Status &amp; Diagnostic Console</span>
            </a>

            <a href="/admin/notifications/logs"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200/60 shadow-xs transition" aria-current="page">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Delivery Audit Logs</span>
            </a>
        </nav>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <form method="GET" action="/admin/notifications/logs" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label for="filter_channel" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Channel</label>
                <select id="filter_channel" name="channel" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 bg-white">
                    <option value="">All Channels</option>
                    <option value="email" <?= $selectedChannel === 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="sms" <?= $selectedChannel === 'sms' ? 'selected' : '' ?>>SMS</option>
                    <option value="whatsapp" <?= $selectedChannel === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                </select>
            </div>

            <div>
                <label for="filter_status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Status</label>
                <select id="filter_status" name="status" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 bg-white">
                    <option value="">All Statuses</option>
                    <option value="sent" <?= $selectedStatus === 'sent' ? 'selected' : '' ?>>Sent / Delivered</option>
                    <option value="failed" <?= $selectedStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="queued" <?= $selectedStatus === 'queued' ? 'selected' : '' ?>>Queued</option>
                </select>
            </div>

            <div>
                <label for="filter_event" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Event Type</label>
                <select id="filter_event" name="event_type" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 bg-white">
                    <option value="">All Events</option>
                    <option value="student_absence" <?= $selectedEventType === 'student_absence' ? 'selected' : '' ?>>Student Absence</option>
                    <option value="fee_payment_receipt" <?= $selectedEventType === 'fee_payment_receipt' ? 'selected' : '' ?>>Fee Payment Receipt</option>
                    <option value="admission_approved" <?= $selectedEventType === 'admission_approved' ? 'selected' : '' ?>>Admission Approved</option>
                    <option value="password_reset" <?= $selectedEventType === 'password_reset' ? 'selected' : '' ?>>Password Reset</option>
                    <option value="result_published" <?= $selectedEventType === 'result_published' ? 'selected' : '' ?>>Result Published</option>
                    <option value="test_dispatch" <?= $selectedEventType === 'test_dispatch' ? 'selected' : '' ?>>Test Dispatch</option>
                </select>
            </div>

            <div>
                <label for="filter_search" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Search Keyword</label>
                <input type="text" id="filter_search" name="search" value="<?= e($search ?? '') ?>" placeholder="Recipient, subject, or text..."
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition">
                    Filter Logs
                </button>
                <a href="/admin/notifications/logs" class="px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition border border-slate-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($logs)): ?>
            <div class="p-12 text-center text-sm text-slate-500">
                No notification delivery records match your current filter parameters.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12">#</th>
                            <th class="py-3 px-3">Channel</th>
                            <th class="py-3 px-4">Recipient</th>
                            <th class="py-3 px-4">Event Type</th>
                            <th class="py-3 px-4 min-w-[280px]">Subject &amp; Message Content</th>
                            <th class="py-3 px-3">Gateway</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 text-slate-400 font-mono"><?= $log->id ?></td>
                                <td class="py-3 px-3">
                                    <?php if ($log->isEmail()): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Email
                                        </span>
                                    <?php elseif ($log->isSms()): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            SMS
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            WhatsApp
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono font-bold text-slate-900"><?= e($log->recipient) ?></div>
                                    <?php if ($log->userName): ?>
                                        <div class="text-[10px] text-slate-400"><?= e($log->userName) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-mono">
                                        <?= e($log->eventType) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if (!empty($log->subject)): ?>
                                        <div class="font-bold text-slate-900 text-xs mb-0.5"><?= e($log->subject) ?></div>
                                    <?php endif; ?>
                                    <div class="text-slate-600 text-xs line-clamp-2" title="<?= e($log->messageBody) ?>">
                                        <?= e($log->messageBody) ?>
                                    </div>
                                    <?php if (!empty($log->errorMessage)): ?>
                                        <div class="text-[10px] text-rose-600 mt-1 font-mono font-bold">
                                            Error: <?= e($log->errorMessage) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3 font-mono text-[11px] text-slate-500">
                                    <?= strtoupper(e($log->gatewayProvider)) ?>
                                    <?php if ($log->gatewayReference): ?>
                                        <div class="text-[9px] text-slate-400 truncate max-w-[90px]" title="<?= e($log->gatewayReference) ?>">
                                            <?= e($log->gatewayReference) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <?php if ($log->isSent()): ?>
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sent
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-rose-700 font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Failed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right text-slate-400 font-mono text-[11px]">
                                    <?= date('Y-m-d H:i:s', strtotime($log->sentAt ?? $log->createdAt)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
