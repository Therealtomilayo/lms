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
                                 <td class="px-6 py-4 text-right space-x-2">
                                     <?php $this->include('components/button', [
                                         'href' => '/admin/users/' . e($user->id) . '/edit',
                                         'variant' => 'secondary',
                                         'label' => 'Edit',
                                         'class' => 'px-3 py-1.5 text-xs font-semibold'
                                     ]); ?>

                                     <?php if (\App\Policies\UserPolicy::canDeleteUser($actor, $user)): ?>
                                         <button type="button"
                                             onclick="openDeleteModal(<?= e($user->id) ?>, '<?= e(addslashes($user->name)) ?>', '<?= e(addslashes($user->email)) ?>')"
                                             class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 transition border border-rose-200">
                                             Delete
                                         </button>
                                     <?php endif; ?>
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

<!-- User Deletion / Deletion Request Modal -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <div class="p-6">
            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <h3 id="delModalTitle" class="text-lg font-bold text-slate-900">
                <?= $actor->hasRole('super_admin') ? 'Confirm User Deletion' : 'Request User Deletion' ?>
            </h3>
            
            <p id="delModalDescription" class="text-sm text-slate-600 mt-2">
                <?= $actor->hasRole('super_admin') 
                    ? 'Are you sure you want to deactivate / delete the account for <strong id="delUserName" class="text-slate-900"></strong>? This will revoke all sessions.' 
                    : 'As an administrator, deleting user accounts requires Super Admin approval. Please provide the justification for <strong id="delUserName" class="text-slate-900"></strong> below.' ?>
            </p>

            <form id="deleteUserForm" method="POST" action="" class="mt-5 space-y-4">
                <?= csrf_field() ?>

                <?php if (!$actor->hasRole('super_admin')): ?>
                    <div>
                        <label for="deletion_reason" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Justification Reason <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="deletion_reason" name="reason" rows="3" required
                            placeholder="e.g. Student transferred to another institution / Expelled / Left school"
                            class="w-full text-sm rounded-xl border border-slate-300 focus:border-rose-500 focus:ring-rose-500 p-3"></textarea>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeDeleteModal()"
                        class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition">
                        <?= $actor->hasRole('super_admin') ? 'Delete User' : 'Submit Deletion Request' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(userId, userName, userEmail) {
    document.getElementById('delUserName').textContent = userName + ' (' + userEmail + ')';
    document.getElementById('deleteUserForm').action = '/admin/users/' + userId + '/delete';
    var reasonInput = document.getElementById('deletion_reason');
    if (reasonInput) reasonInput.value = '';
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').classList.add('hidden');
}
</script>
