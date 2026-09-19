<?php
/**
 * Admin Admission Application Dossier Review View
 *
 * @var array $dossier
 * @var \App\Models\AdmissionApplication $application
 * @var \App\Models\User $applicant
 * @var \App\Models\AdmissionSession $session
 * @var array $wards
 * @var array $history
 * @var array $classes
 * @var string|null $success
 * @var string|null $error
 */
$this->layout('layouts/admin', [
    'title' => 'Application Dossier ' . $application->applicationNumber . ' — Claret LMS',
    'headerTitle' => 'Admissions'
]);
?>

<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="/admin/admissions/applications" class="hover:text-brand-600 transition flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Applications
                </a>
                <span>/</span>
                <span class="text-slate-800 font-mono"><?= htmlspecialchars($application->applicationNumber, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight font-mono"><?= htmlspecialchars($application->applicationNumber, ENT_QUOTES, 'UTF-8') ?></h1>
                <?php
                $statusStyles = [
                    'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                    'submitted' => 'bg-sky-50 text-sky-700 border-sky-200',
                    'under_review' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                ];
                $style = $statusStyles[$application->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border <?= $style ?>">
                    <?= htmlspecialchars(str_replace('_', ' ', $application->status), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Session: <span class="font-bold text-slate-700"><?= htmlspecialchars($session->title, ENT_QUOTES, 'UTF-8') ?></span>
                • Applied on: <span class="font-medium text-slate-700"><?= date('F d, Y \a\t h:i A', strtotime($application->createdAt)) ?></span>
                <?php if ($application->submittedAt): ?>
                    • Submitted on: <span class="font-semibold text-sky-700"><?= date('F d, Y \a\t h:i A', strtotime($application->submittedAt)) ?></span>
                <?php endif; ?>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition shadow-sm">
                <i data-lucide="printer" class="w-4 h-4 text-slate-500"></i>
                <span>Print Dossier</span>
            </button>
        </div>
    </div>

    <!-- Flash Notifications -->
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

    <!-- Status Specific Banners -->
    <?php if ($application->status === 'approved'): ?>
        <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/10">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-xl bg-white/20">
                    <i data-lucide="graduation-cap" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h3 class="text-base font-black tracking-tight">Application Approved & Students Matriculated</h3>
                    <p class="text-xs text-emerald-100 mt-0.5">Prospective wards have been assigned official STD registration numbers, enrolled as active students, and the guardian parent portal account has been provisioned.</p>
                </div>
            </div>
        </div>
    <?php elseif ($application->status === 'rejected'): ?>
        <div class="p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-sm">
            <div class="flex items-start gap-3">
                <i data-lucide="x-circle" class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h3 class="text-sm font-bold">Application Rejected</h3>
                    <p class="text-xs text-rose-700 mt-1 font-medium">
                        <strong>Reason:</strong> <?= htmlspecialchars($application->rejectionReason ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2-Column Dossier Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Dossier Content (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Guardian / Applicant Information Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Parent / Guardian Information</h2>
                    </div>
                    <span class="text-xs font-mono text-slate-400">User ID #<?= (int)$applicant->id ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold block mb-0.5">Full Name</span>
                        <p class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($applicant->name, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block mb-0.5">Email Address</span>
                        <p class="font-medium text-slate-800 flex items-center gap-1">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                            <?= htmlspecialchars($applicant->email, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block mb-0.5">Phone Number</span>
                        <p class="font-medium text-slate-800 flex items-center gap-1">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                            <?= htmlspecialchars($applicant->phone ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Registered Prospective Wards Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-brand-50 text-brand-600">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </span>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Registered Wards (<?= count($wards) ?>)</h2>
                    </div>
                </div>

                <?php if (empty($wards)): ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center text-slate-400 text-xs">
                        No wards found in this docket.
                    </div>
                <?php else: ?>
                    <?php foreach ($wards as $index => $item): 
                        $ward = $item['ward'];
                        $birthCert = $item['birth_cert'];
                        $passport = $item['passport'];
                        $report = $item['previous_report'];
                        $payment = $item['payment'];
                    ?>
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
                            <!-- Ward Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                                        <?php if ($passport): ?>
                                            <a href="/files/<?= $passport->id ?>/stream" target="_blank" title="Click to view full photo in new tab">
                                                <img src="/files/<?= $passport->id ?>/stream" alt="Passport Photo" class="w-full h-full object-cover hover:scale-105 transition-transform">
                                            </a>
                                        <?php else: ?>
                                            <i data-lucide="user" class="w-6 h-6 text-slate-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-black text-slate-900"><?= htmlspecialchars($ward->getFullName(), ENT_QUOTES, 'UTF-8') ?></h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $ward->gender === 'male' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' ?>">
                                                <?= htmlspecialchars($ward->gender, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Applying for: <span class="font-bold text-slate-800"><?= htmlspecialchars($ward->classGrade ?? ($ward->academicLevelName ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                                            • DOB: <?= htmlspecialchars($ward->dateOfBirth, ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Admission Number or Payment Badge -->
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($ward->studentAdmissionNumber)): ?>
                                        <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-right">
                                            <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Matriculated</div>
                                            <div class="font-mono font-black text-xs"><?= htmlspecialchars($ward->studentAdmissionNumber, ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="px-3 py-1.5 rounded-xl <?= $ward->paymentStatus === 'paid' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700' ?> border text-xs font-bold">
                                            <?= $ward->paymentStatus === 'paid' ? '₦10,000 Paid' : 'Fee Unpaid' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Ward Personal & Background Info Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div>
                                    <span class="text-slate-400 font-semibold block mb-0.5">State of Origin</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($ward->stateOfOrigin ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block mb-0.5">L.G.A.</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($ward->lga ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block mb-0.5">Previous School</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($ward->previousSchool ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block mb-0.5">Last Class Passed</span>
                                    <span class="font-bold text-slate-800"><?= htmlspecialchars($ward->previousClass ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>

                            <!-- Payment Record Details -->
                            <?php if ($payment): ?>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex flex-wrap items-center justify-between text-xs gap-2">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="receipt" class="w-4 h-4 text-slate-500"></i>
                                        <span class="text-slate-500">Paystack Ref:</span>
                                        <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($payment->reference, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="text-slate-500">
                                        Amount: <span class="font-bold text-slate-800"><?= htmlspecialchars($payment->currency, ENT_QUOTES, 'UTF-8') ?> <?= number_format($payment->amount, 2) ?></span>
                                        • Paid: <span class="font-medium text-slate-700"><?= $payment->paidAt ? date('M d, Y h:i A', strtotime($payment->paidAt)) : 'Confirmed' ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Uploaded Documents Review -->
                            <div class="space-y-2">
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider block">Attached Verification Documents</span>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <!-- Birth Certificate -->
                                    <div class="p-3.5 rounded-xl border <?= $birthCert ? 'border-emerald-200 bg-emerald-50/40' : 'border-dashed border-slate-200 bg-slate-50' ?> flex flex-col justify-between gap-3">
                                        <div class="flex items-start gap-2.5 truncate">
                                            <div class="p-2 rounded-lg <?= $birthCert ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-400' ?> flex-shrink-0">
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                            </div>
                                            <div class="truncate">
                                                <div class="font-bold text-xs text-slate-900 truncate">Birth Certificate</div>
                                                <div class="text-[11px] text-slate-500 truncate"><?= $birthCert ? htmlspecialchars($birthCert->originalName, ENT_QUOTES, 'UTF-8') : 'Not uploaded' ?></div>
                                            </div>
                                        </div>
                                        <?php if ($birthCert): ?>
                                            <div class="flex items-center gap-2 pt-2 border-t border-emerald-100">
                                                <a href="/files/<?= $birthCert->id ?>/stream" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>View (New Tab)</span>
                                                </a>
                                                <a href="/files/<?= $birthCert->id ?>/download" download class="p-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 transition shadow-xs" title="Download File">
                                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Passport Photograph -->
                                    <div class="p-3.5 rounded-xl border <?= $passport ? 'border-emerald-200 bg-emerald-50/40' : 'border-dashed border-slate-200 bg-slate-50' ?> flex flex-col justify-between gap-3">
                                        <div class="flex items-start gap-2.5 truncate">
                                            <div class="p-2 rounded-lg <?= $passport ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-400' ?> flex-shrink-0">
                                                <i data-lucide="image" class="w-4 h-4"></i>
                                            </div>
                                            <div class="truncate">
                                                <div class="font-bold text-xs text-slate-900 truncate">Passport Photo</div>
                                                <div class="text-[11px] text-slate-500 truncate"><?= $passport ? htmlspecialchars($passport->originalName, ENT_QUOTES, 'UTF-8') : 'Not uploaded' ?></div>
                                            </div>
                                        </div>
                                        <?php if ($passport): ?>
                                            <div class="flex items-center gap-2 pt-2 border-t border-emerald-100">
                                                <a href="/files/<?= $passport->id ?>/stream" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>View (New Tab)</span>
                                                </a>
                                                <a href="/files/<?= $passport->id ?>/download" download class="p-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 transition shadow-xs" title="Download File">
                                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Previous Report Card -->
                                    <div class="p-3.5 rounded-xl border <?= $report ? 'border-emerald-200 bg-emerald-50/40' : 'border-dashed border-slate-200 bg-slate-50' ?> flex flex-col justify-between gap-3">
                                        <div class="flex items-start gap-2.5 truncate">
                                            <div class="p-2 rounded-lg <?= $report ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-400' ?> flex-shrink-0">
                                                <i data-lucide="file-check" class="w-4 h-4"></i>
                                            </div>
                                            <div class="truncate">
                                                <div class="font-bold text-xs text-slate-900 truncate">Previous Report</div>
                                                <div class="text-[11px] text-slate-500 truncate"><?= $report ? htmlspecialchars($report->originalName, ENT_QUOTES, 'UTF-8') : 'Optional / None' ?></div>
                                            </div>
                                        </div>
                                        <?php if ($report): ?>
                                            <div class="flex items-center gap-2 pt-2 border-t border-emerald-100">
                                                <a href="/files/<?= $report->id ?>/stream" target="_blank" class="flex-1 inline-flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs">
                                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    <span>View (New Tab)</span>
                                                </a>
                                                <a href="/files/<?= $report->id ?>/download" download class="p-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 transition shadow-xs" title="Download File">
                                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Actions & Audit Trail Sidebar (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Decision Workflow Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </span>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Admissions Decision</h2>
                </div>

                <?php if ($application->status === 'submitted'): ?>
                    <!-- Step 1: Move to Under Review -->
                    <div class="space-y-3">
                        <p class="text-xs text-slate-500 leading-relaxed">
                            This docket has been finalized and submitted by the applicant. Mark it as <strong class="text-slate-800">Under Review</strong> while examining academic credentials and entrance evaluation.
                        </p>
                        <form method="POST" action="/admin/admissions/applications/<?= $application->id ?>/review">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm shadow-purple-500/20">
                                <i data-lucide="search" class="w-4 h-4"></i>
                                <span>Commence Review</span>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (in_array($application->status, ['submitted', 'under_review'], true)): ?>
                    <!-- Step 2: Approval Form with Optional Class Allocation -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Approve & Matriculate</h4>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Approving will generate official <code class="text-emerald-700 font-bold bg-emerald-50 px-1 py-0.5 rounded">STD-xxxxx</code> registration numbers for each ward, enroll them as students, and configure the parent portal account.
                        </p>

                        <form method="POST" action="/admin/admissions/applications/<?= $application->id ?>/approve" onsubmit="return confirm('Are you sure you want to approve this application docket? This will officially matriculate the prospective student(s).');" class="space-y-3">
                            <?= \App\Core\Csrf::field() ?>

                            <!-- Class allocation for each ward -->
                            <?php foreach ($wards as $item): 
                                $w = $item['ward'];
                            ?>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1 truncate">
                                        Assign Class for <?= htmlspecialchars($w->firstName, ENT_QUOTES, 'UTF-8') ?>:
                                    </label>
                                    <select name="ward_classes[<?= $w->id ?>]" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition bg-white">
                                        <option value="">-- Optional: Assign Later --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c->id ?>">
                                                <?= htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($c->academicLevel->name ?? 'Level', ENT_QUOTES, 'UTF-8') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Approval Note / Comments</label>
                                <textarea name="comment" rows="2" placeholder="e.g. Entrance examination passed, admitted to JSS 1 Gold." class="w-full p-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition"></textarea>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm shadow-emerald-500/20">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                <span>Approve & Matriculate</span>
                            </button>
                        </form>
                    </div>

                    <!-- Step 3: Reject Application -->
                    <div class="space-y-3 pt-3 border-t border-slate-100">
                        <details class="group/details">
                            <summary class="text-xs font-bold text-rose-600 cursor-pointer flex items-center justify-between hover:text-rose-700">
                                <span>Reject Application</span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 group-open/details:rotate-180 transition-transform"></i>
                            </summary>
                            <form method="POST" action="/admin/admissions/applications/<?= $application->id ?>/reject" onsubmit="return confirm('Are you sure you want to reject this application? This action will be logged with your comments.');" class="mt-3 space-y-3">
                                <?= \App\Core\Csrf::field() ?>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Reason for Rejection <span class="text-rose-500">*</span></label>
                                    <textarea name="rejection_reason" required rows="3" placeholder="Provide clear reason e.g. Entrance test criteria not met or documents could not be verified." class="w-full p-2.5 rounded-xl border border-rose-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition"></textarea>
                                </div>
                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm shadow-rose-500/20">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    <span>Confirm Rejection</span>
                                </button>
                            </form>
                        </details>
                    </div>
                <?php elseif ($application->status === 'approved'): ?>
                    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs space-y-2">
                        <div class="flex items-center gap-1.5 font-bold">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                            Docket Finalized
                        </div>
                        <p class="text-[11px] leading-relaxed text-emerald-700">
                            This application has been completed. Prospective students have been converted and enrolled.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Audit Trail & Timeline -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span class="p-1.5 rounded-lg bg-slate-100 text-slate-600">
                        <i data-lucide="history" class="w-4 h-4"></i>
                    </span>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Audit Trail</h2>
                </div>

                <?php if (empty($history)): ?>
                    <p class="text-xs text-slate-400 italic">No history logged yet.</p>
                <?php else: ?>
                    <div class="space-y-3 relative before:absolute before:inset-0 before:left-2.5 before:w-0.5 before:bg-slate-100">
                        <?php foreach ($history as $h): ?>
                            <div class="relative pl-6 text-xs space-y-0.5">
                                <div class="absolute left-1.5 top-1.5 w-2 h-2 rounded-full bg-slate-400 ring-4 ring-white"></div>
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-bold text-slate-800 uppercase text-[10px] tracking-wider">
                                        <?= htmlspecialchars($h['from_status'], ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($h['to_status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= date('M d, H:i', strtotime($h['created_at'])) ?></span>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    By: <span class="font-semibold text-slate-700"><?= htmlspecialchars($h['changed_by_name'] ?? 'System / User', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <?php if (!empty($h['comment'])): ?>
                                    <p class="text-[11px] text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100 mt-1 italic">
                                        "<?= htmlspecialchars($h['comment'], ENT_QUOTES, 'UTF-8') ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
