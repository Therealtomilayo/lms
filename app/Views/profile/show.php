<?php
/**
 * Authenticated User Profile View
 * AUTH-05 — Shared across Admin, Teacher, Student, and Parent Portals
 * Adheres strictly to .ai/08-ui-design-system.md
 * 
 * @var \App\Models\User $user Authenticated user model
 * @var string $primaryRole Primary role identifier
 * @var array $roles All assigned roles
 * @var string|null $adminTier Optional admin tier label
 * @var \App\Models\Teacher|null $teacher Optional teacher profile
 * @var array|null $allocations Optional teacher allocations
 * @var \App\Models\Student|null $student Optional student profile
 * @var array|null $guardians Optional student linked guardians
 * @var \App\Models\ParentProfile|null $parent Optional parent profile
 * @var array|null $linkedStudents Optional parent linked students
 */

$userName = $user->name ?? 'User';
$userEmail = $user->email ?? '';
$userPhone = $user->phone ?? null;
$userUuid = $user->uuid ?? '';
$userStatus = $user->status ?? 'active';
$createdAt = !empty($user->created_at) ? date('M d, Y', strtotime($user->created_at)) : 'N/A';

// Resolve dashboard URL based on roles
$dashboardUrl = '/login';
if (in_array('super_admin', $roles, true) || in_array('admin', $roles, true)) {
    $dashboardUrl = '/admin/dashboard';
} elseif (in_array('teacher', $roles, true)) {
    $dashboardUrl = '/teacher/dashboard';
} elseif (in_array('student', $roles, true)) {
    $dashboardUrl = '/student/dashboard';
} elseif (in_array('parent', $roles, true)) {
    $dashboardUrl = '/parent/dashboard';
}

// Format nice role labels
$roleLabels = [
    'super_admin' => 'Super Administrator',
    'admin' => 'Administrator',
    'teacher' => 'Subject Teacher',
    'student' => 'Student Candidate',
    'parent' => 'Guardian / Parent',
];
$displayRole = $roleLabels[$primaryRole] ?? ucfirst($primaryRole);
?>

<div class="space-y-6">

    <!-- 1. HEADER CARD WITH BREADCRUMB, USER SUMMARY & ACTIONS -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            <!-- Left: Avatar & Identity Particulars -->
            <div class="flex items-center gap-4">
                <div class="relative">
                    <div class="w-16 h-16 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs flex-shrink-0">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white" title="Account Active"></span>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="<?= e($dashboardUrl) ?>" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">My Profile</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                            <?= htmlspecialchars($userName) ?>
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                            <?= htmlspecialchars($displayRole) ?>
                        </span>
                        <?php if ($userStatus === 'active'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <?= htmlspecialchars(ucfirst($userStatus)) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 font-mono">
                        <?= htmlspecialchars($userEmail) ?>
                    </p>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="/profile/password" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-bold rounded-xl border border-brand-200 transition">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Change Password</span>
                </a>

                <a href="<?= e($dashboardUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Return to Dashboard</span>
                </a>
            </div>

        </div>
    </div>

    <!-- 2. 4-CARD OVERVIEW STATS STRIP -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Account Status Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Account Status</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    Active
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Verified Claret LMS Identity
                </p>
            </div>
        </div>

        <!-- System Role Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Primary Role</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 truncate">
                    <?= htmlspecialchars($displayRole) ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?= count($roles) > 1 ? count($roles) . ' Roles Assigned' : 'Standard Authorization' ?>
                </p>
            </div>
        </div>

        <!-- Security Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Security Credentials</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    Protected
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Bcrypt Enforced &bull; Secure Session
                </p>
            </div>
        </div>

        <!-- Role-Specific Metric Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <?php if (!empty($parent)): ?>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Linked Wards</span>
                    <span class="p-2 rounded-xl bg-pink-50 text-pink-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                <?php elseif (!empty($student)): ?>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Class Cohort</span>
                    <span class="p-2 rounded-xl bg-sky-50 text-sky-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </span>
                <?php elseif (!empty($teacher)): ?>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Teaching Cohorts</span>
                    <span class="p-2 rounded-xl bg-amber-50 text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </span>
                <?php else: ?>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Access Scope</span>
                    <span class="p-2 rounded-xl bg-purple-50 text-purple-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?php if (!empty($parent)): ?>
                        <?= count($linkedStudents ?? []) ?> <?= count($linkedStudents ?? []) === 1 ? 'Child' : 'Children' ?>
                    <?php elseif (!empty($student)): ?>
                        <?= htmlspecialchars($student->className ?: 'JSS 1A') ?>
                    <?php elseif (!empty($teacher)): ?>
                        <?= count($allocations ?? []) ?> <?= count($allocations ?? []) === 1 ? 'Subject' : 'Subjects' ?>
                    <?php else: ?>
                        System Admin
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?php if (!empty($parent)): ?>
                        Under active guardianship
                    <?php elseif (!empty($student)): ?>
                        Admission: <?= htmlspecialchars($student->admissionNumber ?? 'N/A') ?>
                    <?php elseif (!empty($teacher)): ?>
                        Staff ID: <?= htmlspecialchars($teacher->staffId ?? ($teacher->staff_id ?? 'N/A')) ?>
                    <?php else: ?>
                        Full administrative oversight
                    <?php endif; ?>
                </p>
            </div>
        </div>

    </div>

    <!-- 3. TWO-COLUMN RESPONSIVE LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT COLUMN (2/3): Primary Profile & Role Details -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Primary Account Particulars Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                            Account &amp; Identity Particulars
                        </h3>
                    </div>
                    <span class="text-xs font-mono text-slate-400">ID: #<?= (int)$user->id ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Full Legal Name</span>
                        <span class="font-extrabold text-slate-900 text-sm"><?= htmlspecialchars($userName) ?></span>
                    </div>

                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Email Address</span>
                        <span class="font-mono font-semibold text-slate-800 text-sm"><?= htmlspecialchars($userEmail) ?></span>
                    </div>

                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Phone Number</span>
                        <span class="font-semibold text-slate-800 text-sm">
                            <?= !empty($userPhone) ? htmlspecialchars($userPhone) : '<span class="text-slate-400 italic">Not provided</span>' ?>
                        </span>
                    </div>

                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Account Enrolled</span>
                        <span class="font-semibold text-slate-800 text-sm"><?= $createdAt ?></span>
                    </div>

                    <div class="sm:col-span-2 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                        <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">System UUID Identifier</span>
                        <span class="font-mono text-xs text-slate-600 break-all select-all"><?= htmlspecialchars($userUuid) ?></span>
                    </div>
                </div>
            </div>

            <!-- Role-Specific Dossier Card -->
            <?php if (!empty($parent) && !empty($linkedStudents)): ?>
                <!-- PARENT DOSSIER: Linked Children -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                Linked Wards / Children Dossier
                            </h3>
                        </div>
                        <span class="text-xs font-bold text-brand-700 bg-brand-50 px-2.5 py-0.5 rounded-full border border-brand-200">
                            <?= count($linkedStudents) ?> <?= count($linkedStudents) === 1 ? 'Ward' : 'Wards' ?>
                        </span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($linkedStudents as $c): ?>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-brand-200 hover:shadow-xs transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-brand-700 text-white font-extrabold text-sm flex items-center justify-center flex-shrink-0">
                                        <?= strtoupper(substr($c->name, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-900 leading-tight">
                                            <?= htmlspecialchars($c->name) ?>
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                            <span>Adm: <strong class="font-mono text-slate-700"><?= htmlspecialchars($c->admissionNumber ?: 'N/A') ?></strong></span>
                                            <span>&bull;</span>
                                            <span>Class: <strong class="text-brand-700 font-semibold"><?= htmlspecialchars($c->className ?: 'Assigned') ?></strong></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Links to child pages -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="/parent/children/<?= (int)$c->id ?>" 
                                       class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-brand-700 transition">
                                        Profile
                                    </a>
                                    <a href="/parent/children/<?= (int)$c->id ?>/grades" 
                                       class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-brand-700 transition">
                                        Grades
                                    </a>
                                    <a href="/parent/children/<?= (int)$c->id ?>/timetable" 
                                       class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-brand-700 transition">
                                        Timetable
                                    </a>
                                    <a href="/parent/children/<?= (int)$c->id ?>/attendance" 
                                       class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-brand-700 transition">
                                        Attendance
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif (!empty($student)): ?>
                <!-- STUDENT DOSSIER: Class & Guardians -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                Academic Enrolment &amp; Guardians
                            </h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mb-6">
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Admission Number</span>
                            <span class="font-mono font-extrabold text-slate-900 text-sm"><?= htmlspecialchars($student->admissionNumber ?: 'N/A') ?></span>
                        </div>
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Current Class Cohort</span>
                            <span class="font-extrabold text-brand-700 text-sm"><?= htmlspecialchars($student->className ?: 'JSS 1A') ?></span>
                        </div>
                    </div>

                    <!-- Linked Guardians List -->
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                        Linked Parents / Guardians
                    </h4>
                    <?php if (empty($guardians)): ?>
                        <div class="py-4 text-center text-xs text-slate-400 italic bg-slate-50 rounded-xl border border-slate-100">
                            No linked guardians on file. Please contact the school administration office.
                        </div>
                    <?php else: ?>
                        <div class="space-y-2.5">
                            <?php foreach ($guardians as $g): ?>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold text-slate-900 block"><?= htmlspecialchars($g->name) ?></span>
                                        <span class="text-slate-500 font-mono"><?= htmlspecialchars($g->email) ?></span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-pink-50 text-pink-700 border border-pink-200">
                                        Primary Guardian
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif (!empty($teacher)): ?>
                <!-- TEACHER DOSSIER: Teaching Allocations -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                Faculty Profile &amp; Teaching Allocations
                            </h3>
                        </div>
                        <span class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-0.5 rounded border border-slate-200">
                            Staff ID: <?= htmlspecialchars($teacher->staffId ?? ($teacher->staff_id ?: 'TCH-001')) ?>
                        </span>
                    </div>

                    <?php if (empty($allocations)): ?>
                        <div class="py-6 text-center text-xs text-slate-400 italic bg-slate-50 rounded-xl border border-slate-100">
                            No teaching subject allocations assigned for the active academic session.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                        <th class="py-2.5 px-3">Class Cohort</th>
                                        <th class="py-2.5 px-3">Subject Name</th>
                                        <th class="py-2.5 px-3">Subject Code</th>
                                        <th class="py-2.5 px-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($allocations as $alloc): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                                <?= htmlspecialchars($alloc['class_name']) ?> <?= htmlspecialchars($alloc['section_arm'] ? "({$alloc['section_arm']})" : '') ?>
                                            </td>
                                            <td class="py-2.5 px-3 font-semibold text-slate-700">
                                                <?= htmlspecialchars($alloc['subject_name']) ?>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-brand-700">
                                                <?= htmlspecialchars($alloc['subject_code']) ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                <a href="/teacher/gradebook/<?= (int)$alloc['class_subject_id'] ?>" 
                                                   class="px-2.5 py-1 text-xs font-bold rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 transition inline-block">
                                                    Gradebook
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- ADMIN DOSSIER: System Privileges -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                Administrative Privileges &amp; Institutional Oversight
                            </h3>
                        </div>
                        <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200">
                            <?= htmlspecialchars($adminTier ?? 'Administrator') ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700">Academic Structure &amp; Sessions Management</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700">Student &amp; Staff Directory Oversight</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700">Institutional Attendance &amp; Roll-Call Control</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700">Master Class Timetables &amp; Period Allocations</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700">Continuous Assessment &amp; Result Approval</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700">System Telemetry, Backups &amp; Audit Logs</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- RIGHT COLUMN (1/3): Account Security & Institutional Card -->
        <div class="space-y-6">

            <!-- Security & Password Management Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <div class="flex items-center gap-2.5 pb-4 border-b border-slate-100 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                        Account Security
                    </h3>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Protect your Claret International School portal identity. Regularly updating your password prevents unauthorized access to sensitive academic records.
                </p>

                <div class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                    <a href="/profile/password" 
                       class="w-full flex items-center justify-between p-3 rounded-xl bg-brand-50 hover:bg-brand-100 border border-brand-200 text-brand-700 text-xs font-bold transition group">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            <span>Update Password</span>
                        </div>
                        <span class="text-brand-400 group-hover:translate-x-1 transition">&rarr;</span>
                    </a>

                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5 px-1">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Password encryption: Bcrypt algorithm</span>
                    </div>
                </div>
            </div>

            <!-- Institutional Identity Card -->
            <div class="bg-brand-50 rounded-2xl border border-brand-100 p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-white flex items-center justify-center p-1 shadow-xs flex-shrink-0">
                        <img src="/assets/img/logo.png" alt="Claret International School" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-brand-700 text-sm\'>CL</span>'">
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-brand-900 leading-tight">Claret International School</h4>
                        <span class="text-[11px] font-semibold text-brand-700">Secondary School</span>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed mt-2">
                    Learning Management System &bull; Academic Management &amp; Guardian Communications Portal.
                </p>

                <div class="mt-4 pt-3 border-t border-brand-100/80 text-[11px] text-brand-800 font-semibold space-y-1">
                    <div>&bull; Need account adjustments? Contact the Registrar.</div>
                    <div>&bull; Support: <span class="font-mono text-brand-900">support@claret.edu</span></div>
                </div>
            </div>

        </div>

    </div>

</div>
