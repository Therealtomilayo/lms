<div class="space-y-6">
    <!-- Top actions & filters -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-slate-900">User Directory</h2>
            <p class="text-sm text-slate-500">Manage user credentials, multi-role assignments, and account statuses.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/imports/users" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Bulk Import
            </a>
            <a href="/admin/users/create" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-brand-600 rounded-lg hover:bg-brand-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create User
            </a>
        </div>
    </div>

    <!-- Search & Filters -->
    <form method="GET" action="/admin/users" class="grid grid-cols-1 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div class="sm:col-span-2">
            <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Search name, email, or phone..." 
                   class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
        </div>
        <div>
            <select name="role" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                <option value="">All Roles</option>
                <option value="super_admin" <?= ($selectedRole ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                <option value="admin" <?= ($selectedRole ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="teacher" <?= ($selectedRole ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                <option value="student" <?= ($selectedRole ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="parent" <?= ($selectedRole ?? '') === 'parent' ? 'selected' : '' ?>>Parent / Guardian</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <select name="status" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                <option value="">All Statuses</option>
                <option value="active" <?= ($selectedStatus ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($selectedStatus ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="suspended" <?= ($selectedStatus ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium hover:bg-slate-900 transition">
                Filter
            </button>
        </div>
    </form>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">User</th>
                        <th class="px-6 py-3.5">Assigned Roles</th>
                        <th class="px-6 py-3.5">Contact</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Created</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                No users found matching the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($user->name) ?></div>
                                    <div class="text-xs text-slate-500"><?= e($user->email) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($user->roles as $r): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                                <?= $r === 'super_admin' ? 'bg-purple-100 text-purple-800' : ($r === 'admin' ? 'bg-blue-100 text-blue-800' : ($r === 'teacher' ? 'bg-amber-100 text-amber-800' : ($r === 'student' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700'))) ?>">
                                                <?= e(ucfirst(str_replace('_', ' ', $r))) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($user->phone ?? '—') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        <?= $user->status === 'active' ? 'bg-success-100 text-success-700' : ($user->status === 'suspended' ? 'bg-danger-100 text-danger-700' : 'bg-slate-100 text-slate-600') ?>">
                                        <?= e(ucfirst($user->status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($user->createdAt ? date('M j, Y', strtotime($user->createdAt)) : '—') ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="/admin/users/<?= e($user->id) ?>/edit" class="text-brand-600 hover:text-brand-800 font-semibold text-xs transition">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">Showing page <?= e($currentPage) ?> of <?= e($totalPages) ?> (<?= e($totalUsers) ?> total)</span>
                <div class="flex items-center gap-1">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="/admin/users?page=<?= $p ?><?= !empty($selectedRole) ? '&role=' . urlencode($selectedRole) : '' ?><?= !empty($selectedStatus) ? '&status=' . urlencode($selectedStatus) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                           class="px-3 py-1 rounded text-xs font-medium <?= $p === $currentPage ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
