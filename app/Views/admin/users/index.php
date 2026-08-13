<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'User Management — Claret LMS',
    'headerTitle' => $headerTitle ?? 'User Management'
]);

// Build URL query string preserving current search, role, status filters
$queryParams = [];
if (!empty($selectedRole)) {
    $queryParams['role'] = $selectedRole;
}
if (!empty($selectedStatus)) {
    $queryParams['status'] = $selectedStatus;
}
if (!empty($search)) {
    $queryParams['q'] = $search;
}
$paginationBaseUrl = '/admin/users' . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');

$roleOptions = [
    '' => 'All Roles',
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'teacher' => 'Teacher',
    'student' => 'Student',
    'parent' => 'Parent / Guardian',
];

$statusOptions = [
    '' => 'All Statuses',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'suspended' => 'Suspended',
];
?>

<div class="space-y-6">
    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">User Directory</h2>
            <p class="text-sm text-slate-500 mt-1">Manage user credentials, multi-role assignments, and account statuses.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <?php $this->include('components/button', [
                'href' => '/admin/imports/users',
                'variant' => 'secondary',
                'label' => 'Bulk Import',
                'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>'
            ]); ?>

            <?php $this->include('components/button', [
                'href' => '/admin/users/create',
                'variant' => 'primary',
                'label' => 'Create User',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'
            ]); ?>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <form method="GET" action="/admin/users" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div class="sm:col-span-2">
                <?php $this->include('components/input', [
                    'name' => 'q',
                    'id' => 'filter_search',
                    'label' => 'Search Users',
                    'placeholder' => 'Search name, email, or phone...',
                    'value' => $search ?? ''
                ]); ?>
            </div>

            <div>
                <?php $this->include('components/select', [
                    'name' => 'role',
                    'id' => 'filter_role',
                    'label' => 'Role',
                    'options' => $roleOptions,
                    'selected' => $selectedRole ?? '',
                    'placeholder' => ''
                ]); ?>
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <?php $this->include('components/select', [
                        'name' => 'status',
                        'id' => 'filter_status',
                        'label' => 'Status',
                        'options' => $statusOptions,
                        'selected' => $selectedStatus ?? '',
                        'placeholder' => ''
                    ]); ?>
                </div>

                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => 'Filter',
                    'class' => 'px-4 h-[42px]'
                ]); ?>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <?php if (empty($users)): ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No Users Found',
            'message' => 'No user records matched your selected criteria or search term.'
        ]); ?>
    <?php else: ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">User</th>
                            <th scope="col" class="px-6 py-3.5">Assigned Roles</th>
                            <th scope="col" class="px-6 py-3.5">Contact</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5">Created</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($user->name) ?></div>
                                    <div class="text-xs text-slate-500 font-normal mt-0.5"><?= e($user->email) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($user->roles as $r): ?>
                                            <?php
                                            $variant = match ($r) {
                                                'super_admin' => 'purple',
                                                'admin' => 'info',
                                                'teacher' => 'warning',
                                                'student' => 'success',
                                                default => 'neutral',
                                            };
                                            ?>
                                            <?php $this->include('components/badge', [
                                                'label' => ucfirst(str_replace('_', ' ', $r)),
                                                'variant' => $variant
                                            ]); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 font-mono">
                                    <?= e($user->phone ?? '—') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusVariant = match ($user->status) {
                                        'active' => 'success',
                                        'suspended' => 'danger',
                                        default => 'neutral',
                                    };
                                    ?>
                                    <?php $this->include('components/badge', [
                                        'label' => ucfirst($user->status),
                                        'variant' => $statusVariant
                                    ]); ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($user->createdAt ? date('M j, Y', strtotime($user->createdAt)) : '—') ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php $this->include('components/button', [
                                        'href' => '/admin/users/' . e($user->id) . '/edit',
                                        'variant' => 'secondary',
                                        'label' => 'Edit',
                                        'class' => 'px-3 py-1.5 text-xs font-semibold'
                                    ]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Component -->
            <?php $this->include('components/pagination', [
                'currentPage' => $currentPage ?? 1,
                'totalPages' => $totalPages ?? 1,
                'totalResults' => $totalUsers ?? null,
                'perPage' => 25,
                'baseUrl' => $paginationBaseUrl
            ]); ?>
        </div>
    <?php endif; ?>
</div>
