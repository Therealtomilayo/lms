<?php

/**
 * Super Admin Two-Tier Approval Queue Workspace (ADMIN-30, §6, §58.1-§58.3)
 * 
 * @var array<int, \App\Models\ApprovalRequest> $requests
 * @var array{total: int, pending: int, approved: int, rejected: int, student_reg: int, teacher_reg: int, repetition: int} $counts
 * @var string $selectedStatus
 * @var string $selectedType
 */

$this->layout('layouts/admin', [
    'title' => 'Super Admin Approval Queue — Claret LMS',
    'headerTitle' => 'Super Admin Approval Queue',
    'headerSubtitle' => 'Two-tier administrative governance queue for candidate user activations and repetition requests (§6, §58.1–§58.3)',
]);

$pendingCount = $counts['pending'] ?? 0;
?>

<div class="space-y-6 pb-12">

    <!-- Header Card with Governance Context & Pending Badge -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-purple-100 text-purple-700 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900">Two-Tier Governance Queue</h1>
                        <p class="text-xs text-slate-500 font-medium">Claret International School &bull; Super Admin Verification Authority</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 max-w-3xl leading-relaxed">
                    Under the institutional governance charter (SRS §6, §58.1–§58.3), high-risk operations initiated by Standard Administrators—such as user account deletions, administrative role elevations, and student academic repetitions—are staged for Super Admin authorization.
                </p>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <?php if ($pendingCount > 0): ?>
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <?php endif; ?>
                        <span class="relative inline-flex rounded-full h-3 w-3 <?= $pendingCount > 0 ? 'bg-amber-500' : 'bg-slate-300' ?>"></span>
                    </span>
                    <div>
                        <div class="text-xs font-bold text-amber-900"><?= $pendingCount ?> <?= $pendingCount === 1 ? 'Action Required' : 'Actions Required' ?></div>
                        <div class="text-[11px] text-amber-700 font-medium">Awaiting Super Admin Decision</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Stats Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pending Review -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Review</p>
                <p class="text-2xl font-black text-slate-900"><?= number_format($counts['pending'] ?? 0) ?></p>
                <p class="text-[11px] text-amber-700 font-medium">Immediate decision needed</p>
            </div>
        </div>

        <!-- Approved All-Time -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Approved Requests</p>
                <p class="text-2xl font-black text-emerald-700"><?= number_format($counts['approved'] ?? 0) ?></p>
                <p class="text-[11px] text-slate-400 font-medium">Actioned across platform</p>
            </div>
        </div>

        <!-- Rejected All-Time -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rejected Requests</p>
                <p class="text-2xl font-black text-rose-700"><?= number_format($counts['rejected'] ?? 0) ?></p>
                <p class="text-[11px] text-slate-400 font-medium">Returned with reasons</p>
            </div>
        </div>

        <!-- Breakdown: Deletions & Privilege Escalations -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">High-Risk Queue</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-bold text-rose-700"><?= $counts['user_deletion'] ?? 0 ?> Deletions</span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-xs font-bold text-purple-700"><?= $counts['role_elevation'] ?? 0 ?> Admin Roles</span>
                </div>
                <p class="text-[11px] text-slate-400 font-medium"><?= $counts['repetition'] ?? 0 ?> repetition requests</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar & Search Toolbar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
        <form method="GET" action="/admin/approvals" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                <a href="/admin/approvals?status=pending<?= $selectedType !== 'all' ? "&type={$selectedType}" : '' ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= $selectedStatus === 'pending' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">
                    Pending Review (<?= $counts['pending'] ?? 0 ?>)
                </a>
                <a href="/admin/approvals?status=approved<?= $selectedType !== 'all' ? "&type={$selectedType}" : '' ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= $selectedStatus === 'approved' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">
                    Approved (<?= $counts['approved'] ?? 0 ?>)
                </a>
                <a href="/admin/approvals?status=rejected<?= $selectedType !== 'all' ? "&type={$selectedType}" : '' ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= $selectedStatus === 'rejected' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">
                    Rejected (<?= $counts['rejected'] ?? 0 ?>)
                </a>
                <a href="/admin/approvals?status=all<?= $selectedType !== 'all' ? "&type={$selectedType}" : '' ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= $selectedStatus === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>">
                    All Records (<?= $counts['total'] ?? 0 ?>)
                </a>
            </div>

            <!-- Request Type Selector & Client Instant Search -->
            <div class="flex items-center gap-3">
                <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">

                <select name="type" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="all" <?= $selectedType === 'all' ? 'selected' : '' ?>>All Request Types</option>
                    <option value="user_deletion" <?= $selectedType === 'user_deletion' ? 'selected' : '' ?>>User Deletion Requests</option>
                    <option value="admin_role_assignment" <?= $selectedType === 'admin_role_assignment' ? 'selected' : '' ?>>Admin Role Assignments</option>
                    <option value="student_repetition" <?= $selectedType === 'student_repetition' ? 'selected' : '' ?>>Student Repetitions</option>
                    <option value="student_registration" <?= $selectedType === 'student_registration' ? 'selected' : '' ?>>Student Registrations</option>
                    <option value="teacher_registration" <?= $selectedType === 'teacher_registration' ? 'selected' : '' ?>>Faculty Registrations</option>
                </select>

                <div class="relative">
                    <input type="text" id="approval-search" placeholder="Filter requests..."
                           onkeyup="filterApprovals()"
                           class="w-48 sm:w-64 pl-8 pr-3 py-2 rounded-xl text-xs bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </form>
    </div>

    <!-- Approvals Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($requests)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Queue is Clear</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    There are no approval requests matching the current filter criteria.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" id="approvals-table">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-5 py-3.5">Candidate / Target</th>
                            <th class="px-4 py-3.5">Request Type</th>
                            <th class="px-4 py-3.5">Originating Admin</th>
                            <th class="px-4 py-3.5">Submission Date</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php foreach ($requests as $req): ?>
                            <?php
                            $payload = $req->payload ?? [];
                            $name = $req->entityName ?: ($payload['name'] ?? 'Unknown Candidate');
                            $identifier = $req->entityIdentifier ?: ($payload['admission_number'] ?? $payload['staff_id'] ?? $payload['email'] ?? '—');
                            $roleTag = $payload['role'] ?? ($req->requestType === 'teacher_registration' ? 'teacher' : 'student');
                            $initials = strtoupper(substr($name, 0, 1) . (strpos($name, ' ') !== false ? substr($name, strpos($name, ' ') + 1, 1) : ''));
                            ?>
                            <tr class="hover:bg-slate-50/60 transition approval-row"
                                data-search="<?= strtolower(e($name . ' ' . $identifier . ' ' . ($req->requesterName ?? '') . ' ' . $req->getTypeLabel())) ?>">
                                
                                <!-- Candidate Info -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl <?= $roleTag === 'teacher' ? 'bg-amber-100 text-amber-800' : 'bg-brand-50 text-brand-700' ?> flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            <?= e($initials) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900"><?= e($name) ?></p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="font-mono text-[10px] text-slate-500 font-semibold"><?= e($identifier) ?></span>
                                                <span class="text-slate-300">&bull;</span>
                                                <span class="text-[10px] font-semibold text-slate-500 uppercase"><?= e($roleTag) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Request Type -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <?php
                                    $typeBadgeStyle = match ($req->requestType) {
                                        \App\Models\ApprovalRequest::TYPE_USER_DELETION => 'bg-rose-50 text-rose-800 border border-rose-200/80',
                                        \App\Models\ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT => 'bg-purple-50 text-purple-800 border border-purple-200/80',
                                        \App\Models\ApprovalRequest::TYPE_STUDENT_REPETITION => 'bg-amber-50 text-amber-800 border border-amber-200/80',
                                        \App\Models\ApprovalRequest::TYPE_TEACHER_REGISTRATION => 'bg-sky-50 text-sky-800 border border-sky-200/80',
                                        default => 'bg-brand-50 text-brand-700 border border-brand-200/80',
                                    };
                                    ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold <?= $typeBadgeStyle ?>">
                                        <?= e($req->getTypeLabel()) ?>
                                    </span>
                                </td>

                                <!-- Originating Requester -->
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-800"><?= e($req->requesterName ?? 'Administrator') ?></p>
                                    <p class="text-[11px] text-slate-400"><?= e($req->requesterEmail ?? '') ?></p>
                                </td>

                                <!-- Date & Reviewer -->
                                <td class="px-4 py-4 whitespace-nowrap text-slate-500 text-[11px]">
                                    <div><?= date('M j, Y &bull; H:i', strtotime($req->createdAt)) ?></div>
                                    <?php if ($req->reviewedAt && $req->reviewerName): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            Decided by <?= e($req->reviewerName) ?> on <?= date('M j', strtotime($req->reviewedAt)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <?php if ($req->isPending()): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending Review
                                        </span>
                                    <?php elseif ($req->isApproved()): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Rejected
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($req->isRejected() && $req->rejectionReason): ?>
                                        <p class="text-[10px] text-rose-600 mt-1 italic max-w-xs truncate" title="<?= e($req->rejectionReason) ?>">
                                            "<?= e($req->rejectionReason) ?>"
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Inspect Details Button -->
                                        <button type="button"
                                                onclick="openDetailsModal(<?= htmlspecialchars(json_encode($req->toArray()), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 transition shadow-2xs">
                                            Inspect
                                        </button>

                                        <?php if ($req->isPending()): ?>
                                            <!-- Approve Form -->
                                            <?php
                                            $confirmMsg = match ($req->requestType) {
                                                \App\Models\ApprovalRequest::TYPE_USER_DELETION => 'Are you sure you want to approve this deletion? The user account will be permanently deactivated and all sessions revoked.',
                                                \App\Models\ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT => 'Are you sure you want to approve and grant the Admin role to this user?',
                                                default => 'Are you sure you want to approve this request?',
                                            };
                                            ?>
                                            <form method="POST" action="/admin/approvals/<?= $req->id ?>/approve"
                                                  onsubmit="return confirm('<?= addslashes($confirmMsg) ?>');">
                                                <?= csrf_field() ?>
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-xs cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Approve</span>
                                                </button>
                                            </form>

                                            <!-- Reject Button (Opens Modal) -->
                                            <button type="button"
                                                    onclick="openRejectModal(<?= $req->id ?>, '<?= e(addslashes($name)) ?>')"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition shadow-xs cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                <span>Reject</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Reject Modal With Mandatory Feedback Reason -->
<div id="reject-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900">Reject Request</h3>
                    <p class="text-xs text-slate-500" id="reject-candidate-name">Candidate Name</p>
                </div>
            </div>
            <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="reject-form" method="POST" action="" class="space-y-4">
            <?= csrf_field() ?>

            <div class="space-y-1.5">
                <label for="rejection_reason" class="block text-xs font-bold text-slate-700">
                    Reason for Rejection <span class="text-rose-500">*</span>
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required
                          placeholder="State the exact deficiency (e.g., incorrect staff ID prefix, missing mandatory birth certificate, wrong arm assignment)..."
                          class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                <p class="text-[11px] text-slate-500">
                    This note will be transmitted to the submitting administrator so they can correct the particulars and resubmit.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition shadow-sm">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Candidate Inspection Modal -->
<div id="details-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div>
                <h3 class="text-base font-black text-slate-900" id="modal-candidate-name">Candidate Particulars</h3>
                <p class="text-xs text-slate-500" id="modal-request-type">Request Type</p>
            </div>
            <button type="button" onclick="closeDetailsModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs" id="modal-payload-grid">
            <!-- Dynamically populated via JS -->
        </div>

        <div id="modal-rejection-block" class="hidden p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800">
            <span class="font-bold">Rejection Feedback:</span>
            <p id="modal-rejection-text" class="mt-1 italic"></p>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-200">
            <button type="button" onclick="closeDetailsModal()"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function filterApprovals() {
    const input = document.getElementById('approval-search');
    const filter = input.value.toLowerCase().trim();
    const rows = document.querySelectorAll('.approval-row');

    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        if (searchData.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openRejectModal(requestId, candidateName) {
    const modal = document.getElementById('reject-modal');
    const form = document.getElementById('reject-form');
    const nameEl = document.getElementById('reject-candidate-name');

    form.action = '/admin/approvals/' + requestId + '/reject';
    nameEl.textContent = 'Candidate: ' + candidateName;
    document.getElementById('rejection_reason').value = '';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('rejection_reason').focus();
}

function closeRejectModal() {
    const modal = document.getElementById('reject-modal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function openDetailsModal(data) {
    const modal = document.getElementById('details-modal');
    const nameEl = document.getElementById('modal-candidate-name');
    const typeEl = document.getElementById('modal-request-type');
    const grid = document.getElementById('modal-payload-grid');
    const rejectBlock = document.getElementById('modal-rejection-block');
    const rejectText = document.getElementById('modal-rejection-text');

    nameEl.textContent = data.entity_name || 'Candidate Particulars';
    typeEl.textContent = (data.request_type || '').replace(/_/g, ' ').toUpperCase() + ' • Submitted by ' + (data.requester_name || 'Admin');

    const payload = data.payload || {};
    let html = '';

    html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Full Name</span><p class="font-bold text-slate-800 mt-0.5">' + (payload.name || data.entity_name || '—') + '</p></div>';
    html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Email Address</span><p class="font-bold text-slate-800 mt-0.5">' + (payload.email || data.entity_identifier || '—') + '</p></div>';
    html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Telephone</span><p class="font-bold text-slate-800 mt-0.5">' + (payload.phone || '—') + '</p></div>';
    html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Target Role</span><p class="font-bold text-slate-800 mt-0.5 uppercase">' + (payload.role || '—') + '</p></div>';

    if (payload.admission_number) {
        html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Admission Number</span><p class="font-mono font-bold text-slate-800 mt-0.5">' + payload.admission_number + '</p></div>';
    }
    if (payload.staff_id) {
        html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Staff ID</span><p class="font-mono font-bold text-slate-800 mt-0.5">' + payload.staff_id + '</p></div>';
    }
    if (payload.gender) {
        html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Gender</span><p class="font-bold text-slate-800 mt-0.5 uppercase">' + payload.gender + '</p></div>';
    }
    if (payload.date_of_birth) {
        html += '<div class="p-3 bg-slate-50 rounded-xl border border-slate-100"><span class="text-slate-400 font-medium">Date of Birth</span><p class="font-bold text-slate-800 mt-0.5">' + payload.date_of_birth + '</p></div>';
    }
    if (payload.reason) {
        const reasonLabel = data.request_type === 'user_deletion' 
            ? 'Account Deletion Justification' 
            : (data.request_type === 'admin_role_assignment' ? 'Admin Role Elevation Justification' : 'Justification Reason');
        const boxStyle = data.request_type === 'user_deletion' ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-purple-50 border-purple-200 text-purple-900';
        html += '<div class="col-span-2 p-3.5 rounded-xl border ' + boxStyle + '"><span class="font-bold uppercase tracking-wider text-[10px] block opacity-80">' + reasonLabel + '</span><p class="font-semibold mt-1">' + payload.reason + '</p></div>';
    }

    grid.innerHTML = html;

    if (data.status === 'rejected' && data.rejection_reason) {
        rejectBlock.classList.remove('hidden');
        rejectText.textContent = data.rejection_reason;
    } else {
        rejectBlock.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDetailsModal() {
    const modal = document.getElementById('details-modal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

// Esc key listener
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRejectModal();
        closeDetailsModal();
    }
});
</script>
