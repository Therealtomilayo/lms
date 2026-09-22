<div class="space-y-6 pb-12">
    <!-- Top Navigation Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 sm:p-3">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Gateway Navigation Tabs">
            <a href="/admin/notifications/gateway"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200/60 shadow-xs transition" aria-current="page">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Gateway Status &amp; Diagnostic Console</span>
            </a>

            <a href="/admin/notifications/logs"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Delivery Audit Logs</span>
            </a>
        </nav>
    </div>

    <!-- Gateway Driver Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Email Driver Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <?php if ($driverInfo['mail']['mailer'] === 'smtp'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            SMTP Active
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Log Driver (Dev)
                        </span>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900">Email Gateway</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Automated receipts, letters &amp; recovery</p>
                </div>

                <div class="space-y-1.5 pt-2 text-xs border-t border-slate-100">
                    <div class="flex justify-between text-slate-600">
                        <span>Driver:</span>
                        <strong class="font-mono text-slate-900"><?= strtoupper(e($driverInfo['mail']['mailer'])) ?></strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Sender:</span>
                        <strong class="text-slate-900 truncate max-w-[170px]" title="<?= e($driverInfo['mail']['from_address']) ?>">
                            <?= e($driverInfo['mail']['from_address']) ?>
                        </strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Server Host:</span>
                        <span class="font-mono text-slate-500 truncate max-w-[150px]"><?= e($driverInfo['mail']['host']) ?>:<?= e((string)$driverInfo['mail']['port']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Driver Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <?php if ($driverInfo['sms']['termii_configured'] || $driverInfo['sms']['twilio_configured']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <?= strtoupper(e($driverInfo['sms']['gateway'])) ?> Online
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Log Driver (Dev)
                        </span>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900">SMS Gateway</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Absence alerts, OTP &amp; flash notices</p>
                </div>

                <div class="space-y-1.5 pt-2 text-xs border-t border-slate-100">
                    <div class="flex justify-between text-slate-600">
                        <span>Provider:</span>
                        <strong class="font-mono text-slate-900"><?= strtoupper(e($driverInfo['sms']['gateway'])) ?></strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Sender ID:</span>
                        <strong class="font-mono text-slate-900"><?= e($driverInfo['sms']['sender_id']) ?></strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Route:</span>
                        <span class="text-slate-500">Tier-1 DND/Direct</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp Driver Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $driverInfo['whatsapp']['gateway'] !== 'log' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' ?>">
                        <?= strtoupper(e($driverInfo['whatsapp']['gateway'])) ?>
                    </span>
                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900">WhatsApp Gateway</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Rich parent messages &amp; bulletins</p>
                </div>

                <div class="space-y-1.5 pt-2 text-xs border-t border-slate-100">
                    <div class="flex justify-between text-slate-600">
                        <span>Channel:</span>
                        <strong class="text-slate-900">WhatsApp Business</strong>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Integration:</span>
                        <span class="text-slate-500">Termii / Meta API</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Template Gating:</span>
                        <span class="text-slate-500">Auto-formatted</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Dispatches</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Emails Sent</div>
            <div class="text-2xl font-black text-blue-700 mt-1"><?= number_format($stats['email']) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">SMS Dispatched</div>
            <div class="text-2xl font-black text-purple-700 mt-1"><?= number_format($stats['sms']) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">WhatsApp Sent</div>
            <div class="text-2xl font-black text-emerald-700 mt-1"><?= number_format($stats['whatsapp']) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Delivered</div>
            <div class="text-2xl font-black text-emerald-700 mt-1"><?= number_format($stats['sent']) ?></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Failed</div>
            <div class="text-2xl font-black text-rose-700 mt-1"><?= number_format($stats['failed']) ?></div>
        </div>
    </div>

    <!-- Diagnostic Test Console & Quick Broadcast -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Live Diagnostic Console -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Live Gateway Test Console</h3>
                    <p class="text-xs text-slate-500">Test live deliverability directly to your own inbox or mobile phone</p>
                </div>
                <span class="p-2 rounded-xl bg-slate-50 text-slate-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
            </div>

            <form method="POST" action="/admin/notifications/gateway/test" class="space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="test_channel" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Communication Channel <span class="text-rose-500">*</span>
                        </label>
                        <select id="test_channel" name="channel" 
                                class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-white font-semibold">
                            <option value="email">Email (SMTP / HTML)</option>
                            <option value="sms">SMS (Termii / Twilio)</option>
                            <option value="whatsapp">WhatsApp Message</option>
                        </select>
                    </div>

                    <div>
                        <label for="test_recipient" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Recipient Address / Phone <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="test_recipient" name="recipient" required
                               placeholder="e.g. parent@gmail.com or 08012345678"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                </div>

                <div>
                    <label for="test_message" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Diagnostic Payload / Body
                    </label>
                    <textarea id="test_message" name="message" rows="3"
                              class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                              placeholder="Type custom test message or leave blank to send institutional test greeting..."></textarea>
                </div>

                <div class="pt-2">
                    <?php $this->include('components/button', [
                        'type' => 'submit',
                        'variant' => 'primary',
                        'label' => 'Dispatch Test Transmission',
                        'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>',
                        'class' => 'w-full justify-center min-h-[44px]',
                    ]); ?>
                </div>
            </form>
        </div>

        <!-- Cohort Broadcast Console -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Parent Cohort Broadcast</h3>
                    <p class="text-xs text-slate-500">Broadcast official announcements to registered parent contacts</p>
                </div>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </span>
            </div>

            <form method="POST" action="/admin/notifications/broadcast" class="space-y-4">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="broadcast_channel" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Channel</label>
                        <select id="broadcast_channel" name="channel" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 transition bg-white">
                            <option value="email">Email Bulletin (HTML)</option>
                            <option value="sms">SMS Text Alert</option>
                            <option value="whatsapp">WhatsApp Message</option>
                        </select>
                    </div>

                    <div>
                        <label for="broadcast_target" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Target Audience</label>
                        <select id="broadcast_target" name="target_group" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 transition bg-white font-semibold">
                            <option value="all_parents">All Registered Guardians</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="broadcast_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Bulletin Title / Headline</label>
                    <input type="text" id="broadcast_title" name="title" required placeholder="e.g. End-of-Term Holiday Notice"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div>
                    <label for="broadcast_body" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Message Body</label>
                    <textarea id="broadcast_body" name="body" rows="2" required placeholder="Type the announcement to be broadcast..."
                              class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 focus:ring-2 focus:ring-blue-500 transition"></textarea>
                </div>

                <div class="pt-1">
                    <?php $this->include('components/button', [
                        'type' => 'submit',
                        'variant' => 'secondary',
                        'label' => 'Dispatch Broadcast Campaign',
                        'class' => 'w-full justify-center',
                    ]); ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Delivery Feed -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Recent Outbound Transmissions</h3>
                <p class="text-xs text-slate-500">Live feed of notifications dispatched by the system</p>
            </div>
            <a href="/admin/notifications/logs" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">
                View Full Audit Logs &rarr;
            </a>
        </div>

        <?php if (empty($recentLogs)): ?>
            <div class="p-8 text-center text-sm text-slate-500">
                No notification delivery records found yet. Use the diagnostic console above to trigger a test dispatch.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Channel</th>
                            <th class="py-3 px-4">Recipient</th>
                            <th class="py-3 px-4">Event</th>
                            <th class="py-3 px-4">Message Snippet</th>
                            <th class="py-3 px-3">Gateway</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-4 text-right">Dispatched</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php foreach ($recentLogs as $log): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4">
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
                                <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($log->recipient) ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-mono">
                                        <?= e($log->eventType) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 max-w-[280px] truncate" title="<?= e($log->messageBody) ?>">
                                    <?= e($log->subject ? ($log->subject . ' — ') : '') ?><?= e($log->messageBody) ?>
                                </td>
                                <td class="py-3 px-3 font-mono text-[11px] text-slate-500"><?= strtoupper(e($log->gatewayProvider)) ?></td>
                                <td class="py-3 px-3">
                                    <?php if ($log->isSent()): ?>
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sent
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-rose-700 font-bold" title="<?= e($log->errorMessage ?? '') ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Failed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-right text-slate-400">
                                    <?= date('d M, H:i', strtotime($log->sentAt ?? $log->createdAt)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
